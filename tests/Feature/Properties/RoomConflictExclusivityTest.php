<?php

// Feature: booking-system, Property 2: Room conflict exclusivity

use App\Actions\Bookings\CreateBooking;
use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.5, 6.5, 8.5, 9.7**
test('no two bookings sharing a room overlap in time after creation attempts', function () {
    Event::fake();

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    // Create between 2-4 rooms
    $roomCount = fake()->numberBetween(2, 4);
    $rooms = Room::factory()->count($roomCount)->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);

    $action = app(CreateBooking::class);

    // Generate a random base date in the future
    $baseDate = fake()->dateTimeBetween('+1 day', '+30 days');
    $baseDate->setTime((int) $baseDate->format('H'), 0, 0);

    // Attempt to create multiple bookings with randomized times and room selections
    $bookingAttempts = fake()->numberBetween(3, 6);

    for ($i = 0; $i < $bookingAttempts; $i++) {
        // Random start hour (6-20) and 15-minute aligned minute
        $hour = fake()->numberBetween(6, 20);
        $minute = fake()->randomElement([0, 15, 30, 45]);

        // Duration in 15-min increments (15 min to 2 hours)
        $durationMinutes = fake()->randomElement([15, 30, 45, 60, 75, 90, 105, 120]);

        $startsAt = (clone $baseDate)->setTime($hour, $minute, 0);
        $endsAt = (clone $startsAt)->modify("+{$durationMinutes} minutes");

        // Select 1 or more random rooms
        $selectedRoomIds = $rooms->random(fake()->numberBetween(1, min(2, $roomCount)))->pluck('id')->all();

        $data = [
            'name' => fake()->words(fake()->numberBetween(2, 4), true),
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'room_ids' => $selectedRoomIds,
        ];

        try {
            $action->handle($user, $congregation, $data);
        } catch (ValidationException) {
            // Conflict rejection is valid — the system prevented the overlap
        }
    }

    // PROPERTY ASSERTION: For all persisted bookings sharing a room, time ranges do not overlap
    $allBookings = Booking::with('rooms')->get();

    foreach ($allBookings as $bookingA) {
        foreach ($allBookings as $bookingB) {
            if ($bookingA->id === $bookingB->id) {
                continue;
            }

            // Check if they share at least one room
            $sharedRooms = $bookingA->rooms->pluck('id')
                ->intersect($bookingB->rooms->pluck('id'));

            if ($sharedRooms->isEmpty()) {
                continue;
            }

            // Assert non-overlapping: A starts after B ends, OR A ends before B starts
            $aStartsAfterBEnds = $bookingA->starts_at >= $bookingB->ends_at;
            $aEndsBeforeBStarts = $bookingA->ends_at <= $bookingB->starts_at;

            expect($aStartsAfterBEnds || $aEndsBeforeBStarts)->toBeTrue(
                "Bookings [{$bookingA->id}] ({$bookingA->starts_at} - {$bookingA->ends_at}) and ".
                "[{$bookingB->id}] ({$bookingB->starts_at} - {$bookingB->ends_at}) ".
                "share room(s) [{$sharedRooms->implode(', ')}] and overlap in time"
            );
        }
    }
})->repeat(30);
