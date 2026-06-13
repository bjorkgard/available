<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setup wizard page renders for authenticated user without kingdom hall', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->get(route('setup.show'));

    $response->assertOk();
});

test('valid setup submission creates kingdom hall with rooms and assigns superadmin', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '123 Main Street',
        'zip_code' => '12345',
        'city' => 'Springfield',
        'number_of_rooms' => 3,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $congregation->refresh();

    expect($congregation->kingdom_hall_id)->not->toBeNull();

    $kingdomHall = $congregation->kingdomHall;
    expect($kingdomHall->street_address)->toBe('123 Main Street');
    expect($kingdomHall->zip_code)->toBe('12345');
    expect($kingdomHall->city)->toBe('Springfield');
    expect($kingdomHall->number_of_rooms)->toBe(3);

    $rooms = $kingdomHall->rooms()->orderBy('sort_order')->get();
    expect($rooms)->toHaveCount(3);
    expect($rooms[0]->name)->toBe(__('Room :number', ['number' => 1]));
    expect($rooms[1]->name)->toBe(__('Room :number', ['number' => 2]));
    expect($rooms[2]->name)->toBe(__('Room :number', ['number' => 3]));

    $membership = $congregation->memberships()->where('user_id', $user->id)->first();
    expect($membership->role)->toBe(CongregationRole::Superadmin);
});

test('middleware redirects to setup wizard when user has no kingdom hall', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->get(route('calendar', ['current_congregation' => $congregation->slug]));

    $response->assertRedirect(route('setup.show'));
});

test('setup wizard rejects invalid data and returns validation errors', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '',
        'zip_code' => '',
        'city' => '',
        'number_of_rooms' => '',
    ]);

    $response->assertSessionHasErrors(['street_address', 'zip_code', 'city', 'number_of_rooms']);

    expect(KingdomHall::count())->toBe(0);
    expect(Room::count())->toBe(0);
});

test('setup wizard rejects room count outside valid range', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '123 Main Street',
        'zip_code' => '12345',
        'city' => 'Springfield',
        'number_of_rooms' => 0,
    ]);

    $response->assertSessionHasErrors(['number_of_rooms']);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '123 Main Street',
        'zip_code' => '12345',
        'city' => 'Springfield',
        'number_of_rooms' => 51,
    ]);

    $response->assertSessionHasErrors(['number_of_rooms']);

    expect(KingdomHall::count())->toBe(0);
});

test('setup wizard sets congregation locale when provided', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '123 Main Street',
        'zip_code' => '12345',
        'city' => 'Springfield',
        'number_of_rooms' => 2,
        'locale' => 'en',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $congregation->refresh();
    expect($congregation->locale)->toBe('en');
});

test('setup wizard defaults congregation locale to sv when not provided', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '456 Oak Ave',
        'zip_code' => '67890',
        'city' => 'Shelbyville',
        'number_of_rooms' => 1,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $congregation->refresh();
    expect($congregation->locale)->toBe('sv');
});

test('setup wizard rejects invalid locale value', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->post(route('setup.store'), [
        'street_address' => '123 Main Street',
        'zip_code' => '12345',
        'city' => 'Springfield',
        'number_of_rooms' => 2,
        'locale' => 'fr',
    ]);

    $response->assertSessionHasErrors(['locale']);
});

test('user can access dashboard after completing setup', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $user = User::factory()->create();
    $congregation->members()->attach($user, ['role' => CongregationRole::Superadmin->value]);
    $user->switchCongregation($congregation);

    $response = $this->actingAs($user)->get(route('calendar', ['current_congregation' => $congregation->slug]));

    $response->assertOk();
});
