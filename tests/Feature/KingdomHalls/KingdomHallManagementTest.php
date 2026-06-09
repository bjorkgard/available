<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin can update kingdom hall details', function () {
    $kh = KingdomHall::factory()->create(['number_of_rooms' => 2]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 1', 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 2', 'sort_order' => 2]);

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

test('address update does not affect existing rooms', function () {
    $kh = KingdomHall::factory()->create(['number_of_rooms' => 2]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 1', 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 2', 'sort_order' => 2]);

    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => '789 Updated Street',
            'zip_code' => '11111',
            'city' => 'Updated City',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    // Rooms should remain unchanged
    expect($kh->rooms()->count())->toBe(2);
});

test('superadmin can delete kingdom hall', function () {
    $kh = KingdomHall::factory()->create(['number_of_rooms' => 2]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 1', 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 2', 'sort_order' => 2]);

    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->delete("/{$congregation->slug}/kingdom-hall");

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('success');

    // KH and rooms should be deleted
    $this->assertDatabaseMissing('kingdom_halls', ['id' => $kh->id]);
    $this->assertDatabaseMissing('rooms', ['kingdom_hall_id' => $kh->id]);

    // Congregation should be soft-deleted
    $this->assertSoftDeleted('congregations', ['id' => $congregation->id]);

    // Exclusive user should be removed
    $this->assertDatabaseMissing('users', ['id' => $superadmin->id]);
});

test('non-superadmin cannot update kingdom hall', function () {
    $kh = KingdomHall::factory()->create(['number_of_rooms' => 2]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 1', 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 2', 'sort_order' => 2]);

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
});

test('non-superadmin cannot delete kingdom hall', function () {
    $kh = KingdomHall::factory()->create(['number_of_rooms' => 2]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 1', 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 2', 'sort_order' => 2]);

    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $member = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $member->id,
        'role' => CongregationRole::Member,
    ]);

    $response = $this->actingAs($member)
        ->delete("/{$congregation->slug}/kingdom-hall");

    $response->assertForbidden();

    // KH should still exist
    $this->assertDatabaseHas('kingdom_halls', ['id' => $kh->id]);
});

test('superadmin can add new congregation to kingdom hall', function () {
    $kh = KingdomHall::factory()->create(['number_of_rooms' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room 1', 'sort_order' => 1]);

    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->post("/{$congregation->slug}/kingdom-hall/congregations", [
            'name' => 'New Congregation',
            'congregation_number' => 'NEW123',
            'initial_user_name' => 'John Doe',
            'initial_user_email' => 'john@example.com',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('congregations', [
        'name' => 'New Congregation',
        'congregation_number' => 'NEW123',
        'kingdom_hall_id' => $kh->id,
    ]);

    // An invitation should have been created for the initial user
    $this->assertDatabaseHas('congregation_invitations', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);
});
