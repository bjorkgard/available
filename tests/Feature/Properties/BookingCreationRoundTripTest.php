<?php

// Feature: booking-system, Property 12: Booking creation round-trip

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// **Validates: Requirements 1.8, 1.11**
test('creating a booking and fetching it returns data matching the original input', function () {
    Event::fake();

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    // Create 1-3 rooms so we can randomly pick a subset
    $roomCount = fake()->numberBetween(1, 3);
    $rooms = Room::factory()->count($roomCount)->create(['kingdom_hall_id' => $kingdomHall->id]);

    $user = User::factory()->create(['current_congregation_id' => $congregation->id]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);

    // Generate randomized valid booking input
    $name = fake()->words(fake()->numberBetween(2, 5), true);

    $hour = fake()->numberBetween(6, 20);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $day = fake()->numberBetween(1, 28);
    $month = fake()->numberBetween(1, 12);
    $startsAt = Carbon::create(2025, $month, $day, $hour, $minute, 0, 'Europe/Stockholm');

    $durationMinutes = fake()->randomElement([15, 30, 45, 60, 75, 90, 105, 120]);
    $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

    // Select a random subset of rooms (at least 1)
    $selectedRooms = $rooms->random(fake()->numberBetween(1, $roomCount));
    $roomIds = $selectedRooms->pluck('id')->all();

    // Create the booking via the store endpoint (POST)
    $storeResponse = $this->actingAs($user)
        ->postJson(route('bookings.store', ['current_congregation' => $congregation]), [
            'name' => $name,
            'starts_at' => $startsAt->toIso8601String(),
            'ends_at' => $endsAt->toIso8601String(),
            'room_ids' => $roomIds,
        ]);

    $storeResponse->assertStatus(201);

    $createdBookingId = $storeResponse->json('data.0.id');
    expect($createdBookingId)->not->toBeNull();

    // Fetch it back via the show endpoint (GET)
    $showResponse = $this->actingAs($user)
        ->getJson(route('bookings.show', [
            'current_congregation' => $congregation,
            'booking' => $createdBookingId,
        ]));

    $showResponse->assertOk();

    $fetched = $showResponse->json('data');

    // Property: name matches
    expect($fetched['name'])->toBe($name);

    // Property: time range matches
    $fetchedStartsAt = Carbon::parse($fetched['starts_at']);
    $fetchedEndsAt = Carbon::parse($fetched['ends_at']);
    expect($fetchedStartsAt->equalTo($startsAt))->toBeTrue(
        "starts_at mismatch: expected {$startsAt->toIso8601String()}, got {$fetched['starts_at']}"
    );
    expect($fetchedEndsAt->equalTo($endsAt))->toBeTrue(
        "ends_at mismatch: expected {$endsAt->toIso8601String()}, got {$fetched['ends_at']}"
    );

    // Property: rooms match (same IDs regardless of order)
    $fetchedRoomIds = collect($fetched['rooms'])->pluck('id')->sort()->values()->all();
    $expectedRoomIds = collect($roomIds)->sort()->values()->all();
    expect($fetchedRoomIds)->toBe($expectedRoomIds);

    // Property: congregation matches
    expect($fetched['congregation_id'])->toBe($congregation->id);
    expect($fetched['congregation_name'])->toBe($congregation->name);
})->repeat(30);
