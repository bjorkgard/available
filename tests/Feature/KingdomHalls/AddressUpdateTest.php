<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin can update address', function () {
    $kh = KingdomHall::factory()->create([
        'street_address' => '123 Old Street',
        'zip_code' => '12345',
        'city' => 'Old City',
    ]);
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '456 New Avenue',
            'zip_code' => '99999',
            'city' => 'New City',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $kh->refresh();
    expect($kh->street_address)->toBe('456 New Avenue')
        ->and($kh->zip_code)->toBe('99999')
        ->and($kh->city)->toBe('New City');
});

test('validation requires street_address', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'zip_code' => '12345',
            'city' => 'Test City',
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('street_address');
});

test('validation requires zip_code', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '123 Test Street',
            'city' => 'Test City',
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('zip_code');
});

test('validation requires city', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '123 Test Street',
            'zip_code' => '12345',
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('city');
});

test('validation rejects street_address over 255 chars', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => str_repeat('a', 256),
            'zip_code' => '12345',
            'city' => 'Test City',
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('street_address');
});

test('validation rejects zip_code over 20 chars', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '123 Test Street',
            'zip_code' => str_repeat('a', 21),
            'city' => 'Test City',
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('zip_code');
});

test('validation rejects city over 100 chars', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '123 Test Street',
            'zip_code' => '12345',
            'city' => str_repeat('a', 101),
        ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('city');
});

test('non-superadmin cannot update address', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '456 New Avenue',
            'zip_code' => '99999',
            'city' => 'New City',
        ]);

    $response->assertForbidden();

    $kh->refresh();
    expect($kh->street_address)->not->toBe('456 New Avenue');
});

test('unauthenticated user is redirected to login', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $response = $this->put("/{$congregation->slug}/kingdom-hall", [
        'street_address' => '456 New Avenue',
        'zip_code' => '99999',
        'city' => 'New City',
    ]);

    $response->assertRedirect(route('login'));
});
