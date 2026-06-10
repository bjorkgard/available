<?php

// Feature: booking-system, Property 7: Drag-and-drop duration preservation

use App\Actions\Bookings\RescheduleBooking;
use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// **Validates: Requirements 9.2**
test('rescheduling a booking preserves its duration', function () {
    Event::fake();

    // Set up kingdom hall, congregation, room, and user
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);

    $user = User::factory()->create();
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Member,
    ]);

    // Generate a random duration in 15-minute increments (15 min to 4 hours)
    $durationMinutes = fake()->randomElement([15, 30, 45, 60, 75, 90, 105, 120, 135, 150, 180, 240]);

    // Generate a random 15-minute-aligned start time
    $hour = fake()->numberBetween(6, 20);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $day = fake()->numberBetween(1, 28);
    $month = fake()->numberBetween(1, 12);
    $startsAt = Carbon::create(2025, $month, $day, $hour, $minute, 0, 'Europe/Stockholm');
    $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

    // Create a booking with the random duration
    $booking = Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
    $booking->rooms()->attach($room->id);

    // Record the original duration
    $originalDurationMinutes = $booking->starts_at->diffInMinutes($booking->ends_at);

    // Generate a random new start time (different date/time, also 15-min aligned)
    $newHour = fake()->numberBetween(6, 20);
    $newMinute = fake()->randomElement([0, 15, 30, 45]);
    $newDay = fake()->numberBetween(1, 28);
    $newMonth = fake()->numberBetween(1, 12);
    $newStartsAt = Carbon::create(2025, $newMonth, $newDay, $newHour, $newMinute, 0, 'Europe/Stockholm');

    // Reschedule the booking via the RescheduleBooking action
    $action = app(RescheduleBooking::class);
    $result = $action->handle($user, $booking, $newStartsAt, 'this_only');

    // Reload the booking from the database
    $rescheduledBooking = $result->first();

    // Property: duration (ends_at - starts_at) is identical before and after reschedule
    $newDurationMinutes = $rescheduledBooking->starts_at->diffInMinutes($rescheduledBooking->ends_at);

    expect($newDurationMinutes)->toBe($originalDurationMinutes,
        "Duration should be preserved after reschedule. Original: {$originalDurationMinutes} min, After: {$newDurationMinutes} min"
    );
})->repeat(30);
