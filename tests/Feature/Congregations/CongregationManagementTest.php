<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can delete their congregation', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->delete("/{$congregation->slug}");

    $response->assertRedirect(route('home'));
});

test('congregation deletion soft-deletes the record', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $this->actingAs($admin)
        ->delete("/{$congregation->slug}");

    $this->assertSoftDeleted('congregations', ['id' => $congregation->id]);
});

test('deleting congregation removes exclusive users', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $exclusiveMember = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $exclusiveMember->id,
        'role' => CongregationRole::Member,
    ]);

    $this->actingAs($admin)
        ->delete("/{$congregation->slug}");

    $this->assertDatabaseMissing('users', ['id' => $exclusiveMember->id]);
    $this->assertDatabaseMissing('users', ['id' => $admin->id]);
});

test('multi-congregation user is retained when one is deleted', function () {
    $kh = KingdomHall::factory()->create();
    $congregation1 = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $congregation2 = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation1->id]);
    Membership::create([
        'congregation_id' => $congregation1->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $sharedUser = User::factory()->create(['current_congregation_id' => $congregation1->id]);
    Membership::create([
        'congregation_id' => $congregation1->id,
        'user_id' => $sharedUser->id,
        'role' => CongregationRole::Member,
    ]);
    Membership::create([
        'congregation_id' => $congregation2->id,
        'user_id' => $sharedUser->id,
        'role' => CongregationRole::Member,
    ]);

    $this->actingAs($admin)
        ->delete("/{$congregation1->slug}");

    $this->assertDatabaseHas('users', ['id' => $sharedUser->id]);
    expect($sharedUser->fresh()->current_congregation_id)->toBe($congregation2->id);
});

test('admin can move congregation to different kingdom hall', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->post("/{$congregation->slug}/move", [
            'target_kingdom_hall_id' => $targetKh->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('move updates the kingdom hall association', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $this->actingAs($admin)
        ->post("/{$congregation->slug}/move", [
            'target_kingdom_hall_id' => $targetKh->id,
        ]);

    expect($congregation->fresh()->kingdom_hall_id)->toBe($targetKh->id);
});

test('move revokes superadmin for users exclusive to original KH', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $this->actingAs($superadmin)
        ->post("/{$congregation->slug}/move", [
            'target_kingdom_hall_id' => $targetKh->id,
        ]);

    $membership = Membership::where('user_id', $superadmin->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    expect($membership->role)->toBe(CongregationRole::Admin);
});

test('move preserves admin and member roles', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $member = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $member->id,
        'role' => CongregationRole::Member,
    ]);

    $this->actingAs($admin)
        ->post("/{$congregation->slug}/move", [
            'target_kingdom_hall_id' => $targetKh->id,
        ]);

    $adminMembership = Membership::where('user_id', $admin->id)
        ->where('congregation_id', $congregation->id)
        ->first();
    $memberMembership = Membership::where('user_id', $member->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    expect($adminMembership->role)->toBe(CongregationRole::Admin)
        ->and($memberMembership->role)->toBe(CongregationRole::Member);
});

test('move rejects same kingdom hall as target', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->post("/{$congregation->slug}/move", [
            'target_kingdom_hall_id' => $kh->id,
        ]);

    $response->assertSessionHasErrors('kingdom_hall');
});

test('move rejects non-existent target', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->post("/{$congregation->slug}/move", [
            'target_kingdom_hall_id' => '00000000-0000-0000-0000-000000000000',
        ]);

    $response->assertSessionHasErrors('target_kingdom_hall_id');
});
