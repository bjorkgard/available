<?php

// Feature: kingdom-hall-page-refactor, Property 6: Authorization enforcement

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 4.1, 4.2**
test('non-superadmin users receive 403 on all kingdom hall management endpoints and no data changes', function () {
    // Create a Kingdom Hall with rooms and congregations
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $otherCongregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id, 'name' => 'Main Hall', 'sort_order' => 1]);

    // Randomly pick a non-superadmin role (Admin or Member)
    $role = fake()->randomElement([CongregationRole::Admin, CongregationRole::Member]);

    // Create a user with the non-superadmin role
    $user = User::factory()->create([
        'current_congregation_id' => $congregation->id,
        'email_verified_at' => now(),
    ]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => $role,
    ]);

    // Snapshot state before requests
    $originalAddress = $kingdomHall->street_address;
    $originalRoomCount = $kingdomHall->rooms()->count();

    // PUT /{congregation->slug}/kingdom-hall (address update)
    $response = $this->actingAs($user)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => 'New Street 123',
            'zip_code' => '12345',
            'city' => 'New City',
        ]);
    expect($response->status())->toBe(403, "PUT kingdom-hall should return 403 for role [{$role->value}]");

    // POST /{congregation->slug}/kingdom-hall/rooms (create room)
    $response = $this->actingAs($user)
        ->post("/{$congregation->slug}/kingdom-hall/rooms", [
            'name' => 'Unauthorized Room',
        ]);
    expect($response->status())->toBe(403, "POST kingdom-hall/rooms should return 403 for role [{$role->value}]");

    // PUT /{congregation->slug}/kingdom-hall/rooms/{room} (rename room)
    $response = $this->actingAs($user)
        ->put("/{$congregation->slug}/kingdom-hall/rooms/{$room->id}", [
            'name' => 'Renamed Room',
        ]);
    expect($response->status())->toBe(403, "PUT kingdom-hall/rooms/{room} should return 403 for role [{$role->value}]");

    // DELETE /{congregation->slug}/kingdom-hall/rooms/{room} (delete room)
    $response = $this->actingAs($user)
        ->delete("/{$congregation->slug}/kingdom-hall/rooms/{$room->id}");
    expect($response->status())->toBe(403, "DELETE kingdom-hall/rooms/{room} should return 403 for role [{$role->value}]");

    // DELETE /{congregation->slug}/kingdom-hall/congregations/{congregation} (delete another congregation)
    $response = $this->actingAs($user)
        ->delete("/{$congregation->slug}/kingdom-hall/congregations/{$otherCongregation->slug}");
    expect($response->status())->toBe(403, "DELETE kingdom-hall/congregations/{congregation} should return 403 for role [{$role->value}]");

    // Assert no data was modified
    $kingdomHall->refresh();
    expect($kingdomHall->street_address)->toBe($originalAddress, 'Address should not have changed')
        ->and($kingdomHall->rooms()->count())->toBe($originalRoomCount, 'Room count should not have changed')
        ->and($room->fresh()->name)->toBe('Main Hall', 'Room name should not have changed')
        ->and(Congregation::where('id', $otherCongregation->id)->exists())->toBeTrue('Other congregation should still exist');
})->repeat(30);
