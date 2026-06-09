<?php

use App\Actions\Congregations\CreateCongregation;
use App\Actions\Congregations\CreateKingdomHall;
use App\Actions\Congregations\MoveCongregation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use App\Services\ColorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('color is assigned on congregation creation with kingdom hall', function () {
    $user = User::factory()->create();
    $kingdomHall = KingdomHall::factory()->create();

    $action = app(CreateCongregation::class);
    $congregation = $action->handle($user, $kingdomHall, [
        'name' => 'Test Congregation',
        'congregation_number' => 'TC1234',
        'initial_user_name' => 'John Doe',
        'initial_user_email' => 'john@example.com',
    ]);

    expect($congregation->color)->not->toBeNull();
    expect($congregation->color)->toMatch('/^#[0-9A-F]{6}$/');
});

test('color respects minimum distance from existing congregations in same hall', function () {
    $user = User::factory()->create();
    $kingdomHall = KingdomHall::factory()->create();
    $service = new ColorService;

    $action = app(CreateCongregation::class);
    $cong1 = $action->handle($user, $kingdomHall, [
        'name' => 'First Congregation',
        'congregation_number' => 'FC0001',
        'initial_user_name' => 'Jane Doe',
        'initial_user_email' => 'jane@example.com',
    ]);

    $cong2 = $action->handle($user, $kingdomHall, [
        'name' => 'Second Congregation',
        'congregation_number' => 'SC0002',
        'initial_user_name' => 'Bob Doe',
        'initial_user_email' => 'bob@example.com',
    ]);

    $distance = $service->ciede2000Distance($cong1->color, $cong2->color);
    expect($distance)->toBeGreaterThanOrEqual(25.0);
});

test('color is validated and regenerated on move to hall with conflicting colors', function () {
    $service = new ColorService;

    $sourceHall = KingdomHall::factory()->create();
    $targetHall = KingdomHall::factory()->create();

    // Create a congregation in the target hall with a specific color
    $existingCong = Congregation::factory()->create([
        'kingdom_hall_id' => $targetHall->id,
        'color' => '#FF0000',
    ]);

    // Create a congregation in the source hall with the SAME color (will conflict)
    $movingCong = Congregation::factory()->create([
        'kingdom_hall_id' => $sourceHall->id,
        'color' => '#FF0000',
    ]);

    $action = app(MoveCongregation::class);
    $result = $action->handle($movingCong, $targetHall);

    // Color should have been regenerated
    expect($result->color)->not->toBe('#FF0000');
    expect($result->color)->toMatch('/^#[0-9A-F]{6}$/');

    // Should be distinct from the existing congregation
    $distance = $service->ciede2000Distance($result->color, $existingCong->color);
    expect($distance)->toBeGreaterThanOrEqual(25.0);
});

test('color is assigned on kingdom hall setup for congregation without color', function () {
    $user = User::factory()->create();
    $congregation = Congregation::factory()->create(['color' => null, 'kingdom_hall_id' => null]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Admin,
    ]);

    $action = app(CreateKingdomHall::class);
    $action->handle($user, $congregation, [
        'street_address' => '123 Main St',
        'zip_code' => '12345',
        'city' => 'Test City',
        'number_of_rooms' => 2,
    ]);

    $congregation->refresh();
    expect($congregation->color)->not->toBeNull();
    expect($congregation->color)->toMatch('/^#[0-9A-F]{6}$/');
});

test('self-exclusion: congregation own color not compared against itself on move', function () {
    $sourceHall = KingdomHall::factory()->create();
    $targetHall = KingdomHall::factory()->create();

    $congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $sourceHall->id,
        'color' => '#3B82F6',
    ]);

    // Target hall has no other congregations — move should succeed without changing color
    $action = app(MoveCongregation::class);
    $result = $action->handle($congregation, $targetHall);

    // Color should remain unchanged since there are no siblings
    expect($result->color)->toBe('#3B82F6');
});

test('admin can update congregation color successfully', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id, 'color' => '#FF0000']);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->patch("/{$congregation->slug}/congregation/color", [
            'color' => '#00FF00',
        ]);

    $response->assertRedirect();
    expect($congregation->fresh()->color)->toBe('#00FF00');
});

test('member cannot update congregation color', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $member = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $member->id,
        'role' => CongregationRole::Member,
    ]);

    $response = $this->actingAs($member)
        ->patch("/{$congregation->slug}/congregation/color", [
            'color' => '#00FF00',
        ]);

    $response->assertStatus(403);
});

test('invalid hex format returns validation error on color update', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->patch("/{$congregation->slug}/congregation/color", [
            'color' => 'not-a-color',
        ]);

    $response->assertSessionHasErrors('color');
});

test('too-similar color returns validation error on color update', function () {
    $kh = KingdomHall::factory()->create();
    $congregation1 = Congregation::factory()->create(['kingdom_hall_id' => $kh->id, 'color' => '#FF0000']);
    $congregation2 = Congregation::factory()->create(['kingdom_hall_id' => $kh->id, 'color' => '#0000FF']);

    $admin = User::factory()->create(['current_congregation_id' => $congregation2->id]);
    Membership::create([
        'congregation_id' => $congregation2->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    // Try to set congregation2's color to exactly the same as congregation1
    $response = $this->actingAs($admin)
        ->patch("/{$congregation2->slug}/congregation/color", [
            'color' => '#FF0000',
        ]);

    $response->assertSessionHasErrors('color');
});

test('shared props include color on congregation objects', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id, 'color' => '#3B82F6']);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->get("/{$congregation->slug}/calendar");

    $response->assertInertia(fn ($page) => $page
        ->has('currentCongregation')
        ->where('currentCongregation.color', '#3B82F6')
    );
});
