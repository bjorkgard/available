<?php

// Feature: booking-system, Property 6: Edit scope split — "this and all future"

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Enums\CongregationRole;
use App\Enums\RecurrenceFrequency;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\RecurrencePattern;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// **Validates: Requirements 8.3, 8.4**
test('editing "this and future" ends original pattern, creates new pattern, regenerates future only', function () {
    Event::fake();

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);

    // Create a recurring booking series with 4-8 weekly occurrences using end_date
    $occurrenceCount = fake()->numberBetween(4, 8);
    $hour = fake()->numberBetween(6, 18);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $startsAt = now()->addDays(1)->setTime($hour, $minute, 0);
    $durationMinutes = fake()->randomElement([30, 45, 60, 90, 120]);
    $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

    // Use end_date to control the number of occurrences — end_date bounds new pattern too
    $endDate = $startsAt->copy()->addWeeks($occurrenceCount - 1)->addDay();

    $createAction = app(CreateBooking::class);
    $bookings = $createAction->handle($user, $congregation, [
        'name' => fake()->words(3, true),
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        'room_ids' => [$room->id],
        'recurrence' => [
            'frequency' => RecurrenceFrequency::Weekly->value,
            'end_date' => $endDate->toDateString(),
        ],
    ]);

    expect($bookings)->toHaveCount($occurrenceCount);

    $originalPatternId = $bookings->first()->recurrence_pattern_id;
    $originalBookingName = $bookings->first()->name;

    // Pick a random middle occurrence (not first, not last) as the split point
    $splitIndex = fake()->numberBetween(1, $occurrenceCount - 2);
    $splitBooking = $bookings->sortBy('starts_at')->values()->get($splitIndex);

    // Record bookings before the split point
    $bookingsBeforeSplit = $bookings->sortBy('starts_at')->values()->take($splitIndex);
    $bookingsBeforeSplitIds = $bookingsBeforeSplit->pluck('id')->all();

    // Edit with "this_and_future" scope — change the name
    $newName = fake()->words(4, true);
    $updateAction = app(UpdateBooking::class);
    $updatedBookings = $updateAction->handle($user, $splitBooking, [
        'scope' => 'this_and_future',
        'name' => $newName,
    ]);

    // PROPERTY 1: Bookings before the edit point are unchanged
    foreach ($bookingsBeforeSplitIds as $beforeId) {
        $beforeBooking = Booking::find($beforeId);
        expect($beforeBooking)->not->toBeNull(
            'Booking before split point should still exist'
        );
        expect($beforeBooking->name)->toBe($originalBookingName,
            'Booking before split point should retain original name'
        );
        expect($beforeBooking->recurrence_pattern_id)->toBe($originalPatternId,
            'Booking before split point should retain original pattern'
        );
    }

    // PROPERTY 2: A new RecurrencePattern was created from the edit point forward
    $newPatternId = $updatedBookings->first()->recurrence_pattern_id;
    expect($newPatternId)->not->toBeNull('New bookings should belong to a recurrence pattern');
    expect($newPatternId)->not->toBe($originalPatternId,
        'New pattern must be different from original pattern'
    );
    expect(RecurrencePattern::find($newPatternId))->not->toBeNull(
        'New recurrence pattern should exist in the database'
    );

    // PROPERTY 3: The new bookings have the updated name
    foreach ($updatedBookings as $newBooking) {
        expect($newBooking->name)->toBe($newName,
            'All regenerated bookings should have the new name'
        );
    }

    // PROPERTY 4: With end_date constraint, regenerated count equals expected future count
    $expectedFutureCount = $occurrenceCount - $splitIndex;
    expect($updatedBookings)->toHaveCount($expectedFutureCount,
        "Expected {$expectedFutureCount} future bookings from split point onward"
    );

    // PROPERTY 5: Total bookings is consistent — before + after = original count
    $totalBookings = Booking::where('recurrence_pattern_id', $originalPatternId)->count()
        + Booking::where('recurrence_pattern_id', $newPatternId)->count();
    expect($totalBookings)->toBe($occurrenceCount,
        "Total bookings (before + after split) should equal original occurrence count ({$occurrenceCount})"
    );
})->repeat(30);

// **Validates: Requirements 8.3, 8.4**
test('editing "this and future" discards pre-existing exceptions on future dates and regenerates them', function () {
    Event::fake();

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);

    // Create a recurring series with 5-8 occurrences using end_date
    $occurrenceCount = fake()->numberBetween(5, 8);
    $hour = fake()->numberBetween(6, 18);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $startsAt = now()->addDays(1)->setTime($hour, $minute, 0);
    $durationMinutes = fake()->randomElement([30, 45, 60, 90]);
    $endsAt = $startsAt->copy()->addMinutes($durationMinutes);
    $endDate = $startsAt->copy()->addWeeks($occurrenceCount - 1)->addDay();

    $createAction = app(CreateBooking::class);
    $bookings = $createAction->handle($user, $congregation, [
        'name' => fake()->words(3, true),
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        'room_ids' => [$room->id],
        'recurrence' => [
            'frequency' => RecurrenceFrequency::Weekly->value,
            'end_date' => $endDate->toDateString(),
        ],
    ]);

    $originalPatternId = $bookings->first()->recurrence_pattern_id;

    // Pick split at index 1 (second occurrence), and make an exception at a later one
    $sortedBookings = $bookings->sortBy('starts_at')->values();
    $splitIndex = 1;
    $exceptionIndex = fake()->numberBetween($splitIndex + 1, $occurrenceCount - 1);
    $exceptionBooking = $sortedBookings->get($exceptionIndex);

    // Mark it as an exception with a different name using "this_only"
    $updateAction = app(UpdateBooking::class);
    $updateAction->handle($user, $exceptionBooking, [
        'scope' => 'this_only',
        'name' => 'Exception Name',
    ]);

    // Verify the exception was created
    $exceptionBooking->refresh();
    expect($exceptionBooking->is_exception)->toBeTrue();
    expect($exceptionBooking->name)->toBe('Exception Name');

    // Now edit "this and future" from the split point
    $splitBooking = $sortedBookings->get($splitIndex);
    $newName = fake()->words(4, true);
    $updatedBookings = $updateAction->handle($user, $splitBooking, [
        'scope' => 'this_and_future',
        'name' => $newName,
    ]);

    // PROPERTY: The pre-existing exception was discarded — all regenerated bookings
    // have the new name (none retain the "Exception Name")
    foreach ($updatedBookings as $booking) {
        $booking->refresh();
        expect($booking->name)->toBe($newName,
            'Regenerated bookings should not retain old exception names'
        );
        expect($booking->is_exception)->toBeFalse(
            'Regenerated bookings should not be marked as exceptions'
        );
    }

    // The old exception booking should no longer exist
    expect(Booking::find($exceptionBooking->id))->toBeNull(
        'The pre-existing exception should have been discarded'
    );
})->repeat(30);
