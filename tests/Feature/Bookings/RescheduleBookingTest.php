<?php

use App\Actions\Bookings\RescheduleBooking;
use App\Enums\CongregationRole;
use App\Enums\RecurrenceFrequency;
use App\Events\BookingUpdated;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\RecurrencePattern;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Event::fake();

    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->user = User::factory()->create();
    $this->congregation->members()->attach($this->user, ['role' => CongregationRole::Member->value]);
});

test('reschedules a single booking preserving duration', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:30:00'),
    ]);
    $booking->rooms()->attach($this->room);

    $newStartsAt = Carbon::parse('2025-03-11 14:00:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $booking, $newStartsAt, 'this_only');

    expect($result)->toHaveCount(1);
    expect($result->first()->starts_at->toDateTimeString())->toBe('2025-03-11 14:00:00');
    expect($result->first()->ends_at->toDateTimeString())->toBe('2025-03-11 15:30:00');

    Event::assertDispatched(BookingUpdated::class);
});

test('snaps start time to nearest 15-minute boundary (rounds down)', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:00:00'),
    ]);
    $booking->rooms()->attach($this->room);

    // 14:07 should snap down to 14:00
    $newStartsAt = Carbon::parse('2025-03-11 14:07:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $booking, $newStartsAt, 'this_only');

    expect($result->first()->starts_at->toDateTimeString())->toBe('2025-03-11 14:00:00');
    expect($result->first()->ends_at->toDateTimeString())->toBe('2025-03-11 15:00:00');
});

test('snaps start time to nearest 15-minute boundary (rounds up)', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:00:00'),
    ]);
    $booking->rooms()->attach($this->room);

    // 14:08 should snap up to 14:15
    $newStartsAt = Carbon::parse('2025-03-11 14:08:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $booking, $newStartsAt, 'this_only');

    expect($result->first()->starts_at->toDateTimeString())->toBe('2025-03-11 14:15:00');
    expect($result->first()->ends_at->toDateTimeString())->toBe('2025-03-11 15:15:00');
});

test('marks recurring booking as exception when rescheduling this_only', function () {
    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $this->congregation->id,
        'frequency' => RecurrenceFrequency::Weekly,
    ]);

    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:00:00'),
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking->rooms()->attach($this->room);

    $newStartsAt = Carbon::parse('2025-03-11 14:00:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $booking, $newStartsAt, 'this_only');

    $updated = $result->first();
    expect($updated->is_exception)->toBeTrue();
    expect($updated->original_starts_at->toDateTimeString())->toBe('2025-03-10 10:00:00');
});

test('detects conflicts when rescheduling', function () {
    // Create an existing booking in the target slot
    $existing = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-11 14:00:00'),
        'ends_at' => Carbon::parse('2025-03-11 15:00:00'),
    ]);
    $existing->rooms()->attach($this->room);

    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:00:00'),
    ]);
    $booking->rooms()->attach($this->room);

    $newStartsAt = Carbon::parse('2025-03-11 14:30:00');

    expect(fn () => app(RescheduleBooking::class)->handle($this->user, $booking, $newStartsAt, 'this_only'))
        ->toThrow(ValidationException::class);
});

test('reschedules this_and_future adjusts all future occurrences', function () {
    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $this->congregation->id,
        'frequency' => RecurrenceFrequency::Weekly,
    ]);

    // Create 3 weekly occurrences
    $booking1 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:30:00'),
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking1->rooms()->attach($this->room);

    $booking2 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-17 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-17 11:30:00'),
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking2->rooms()->attach($this->room);

    $booking3 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-24 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-24 11:30:00'),
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking3->rooms()->attach($this->room);

    // Reschedule from the first booking: move +2 hours (10:00 → 12:00)
    $newStartsAt = Carbon::parse('2025-03-10 12:00:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $booking1, $newStartsAt, 'this_and_future');

    expect($result)->toHaveCount(3);

    // All bookings should be shifted +2 hours
    $booking1->refresh();
    $booking2->refresh();
    $booking3->refresh();

    expect($booking1->starts_at->toDateTimeString())->toBe('2025-03-10 12:00:00');
    expect($booking1->ends_at->toDateTimeString())->toBe('2025-03-10 13:30:00');

    expect($booking2->starts_at->toDateTimeString())->toBe('2025-03-17 12:00:00');
    expect($booking2->ends_at->toDateTimeString())->toBe('2025-03-17 13:30:00');

    expect($booking3->starts_at->toDateTimeString())->toBe('2025-03-24 12:00:00');
    expect($booking3->ends_at->toDateTimeString())->toBe('2025-03-24 13:30:00');
});

test('this_and_future does not affect past occurrences', function () {
    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $this->congregation->id,
        'frequency' => RecurrenceFrequency::Weekly,
    ]);

    // Past occurrence
    $pastBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-03 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-03 11:00:00'),
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $pastBooking->rooms()->attach($this->room);

    // Current occurrence (the one being rescheduled)
    $currentBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:00:00'),
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $currentBooking->rooms()->attach($this->room);

    $newStartsAt = Carbon::parse('2025-03-10 14:00:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $currentBooking, $newStartsAt, 'this_and_future');

    expect($result)->toHaveCount(1);

    // Past booking should be unchanged
    $pastBooking->refresh();
    expect($pastBooking->starts_at->toDateTimeString())->toBe('2025-03-03 10:00:00');

    // Current booking should be rescheduled
    $currentBooking->refresh();
    expect($currentBooking->starts_at->toDateTimeString())->toBe('2025-03-10 14:00:00');
});

test('this_and_future on non-recurring booking updates the single booking', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => Carbon::parse('2025-03-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-03-10 11:00:00'),
    ]);
    $booking->rooms()->attach($this->room);

    $newStartsAt = Carbon::parse('2025-03-11 14:00:00');

    $result = app(RescheduleBooking::class)->handle($this->user, $booking, $newStartsAt, 'this_and_future');

    expect($result)->toHaveCount(1);
    $booking->refresh();
    expect($booking->starts_at->toDateTimeString())->toBe('2025-03-11 14:00:00');
    expect($booking->ends_at->toDateTimeString())->toBe('2025-03-11 15:00:00');
});
