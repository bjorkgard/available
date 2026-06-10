<?php

use App\Enums\CongregationRole;
use App\Enums\RecurrenceFrequency;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\RecurrencePattern;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
    $this->room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->user = User::factory()->create();
    $this->congregation->members()->attach($this->user, ['role' => CongregationRole::Member->value]);
    $this->user->update(['current_congregation_id' => $this->congregation->id]);
});

test('PATCH bookings.reschedule preserves booking duration', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:30:00',
    ]);
    $booking->rooms()->attach($this->room);

    $response = $this->actingAs($this->user)
        ->patchJson(route('bookings.reschedule', [
            'current_congregation' => $this->congregation,
            'booking' => $booking,
        ]), [
            'starts_at' => '2025-03-12 14:00:00',
            'scope' => 'this_only',
        ]);

    $response->assertOk();

    $booking->refresh();

    // Duration should remain 90 minutes (10:00 → 11:30 = 90 min)
    expect($booking->starts_at->toDateTimeString())->toBe('2025-03-12 14:00:00');
    expect($booking->ends_at->toDateTimeString())->toBe('2025-03-12 15:30:00');

    // Verify duration is preserved (90 minutes)
    $durationMinutes = $booking->starts_at->diffInMinutes($booking->ends_at);
    expect((int) $durationMinutes)->toBe(90);
});

test('PATCH bookings.reschedule into a conflict returns 422', function () {
    // Create an existing booking occupying the target slot
    $existingBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-12 14:00:00',
        'ends_at' => '2025-03-12 15:30:00',
    ]);
    $existingBooking->rooms()->attach($this->room);

    // Create the booking to reschedule
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room);

    $response = $this->actingAs($this->user)
        ->patchJson(route('bookings.reschedule', [
            'current_congregation' => $this->congregation,
            'booking' => $booking,
        ]), [
            'starts_at' => '2025-03-12 14:30:00',
            'scope' => 'this_only',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('conflicts');

    // Booking should remain unchanged
    $booking->refresh();
    expect($booking->starts_at->toDateTimeString())->toBe('2025-03-10 10:00:00');
    expect($booking->ends_at->toDateTimeString())->toBe('2025-03-10 11:00:00');
});

test('PATCH bookings.reschedule on recurring booking with scope this_only works', function () {
    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $this->congregation->id,
        'frequency' => RecurrenceFrequency::Weekly,
        'end_count' => 3,
    ]);

    $booking1 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking1->rooms()->attach($this->room);

    $booking2 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-17 10:00:00',
        'ends_at' => '2025-03-17 11:00:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking2->rooms()->attach($this->room);

    $booking3 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-24 10:00:00',
        'ends_at' => '2025-03-24 11:00:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking3->rooms()->attach($this->room);

    // Reschedule only the second occurrence
    $response = $this->actingAs($this->user)
        ->patchJson(route('bookings.reschedule', [
            'current_congregation' => $this->congregation,
            'booking' => $booking2,
        ]), [
            'starts_at' => '2025-03-18 14:00:00',
            'scope' => 'this_only',
        ]);

    $response->assertOk();

    // The rescheduled booking should be updated and marked as exception
    $booking2->refresh();
    expect($booking2->starts_at->toDateTimeString())->toBe('2025-03-18 14:00:00');
    expect($booking2->ends_at->toDateTimeString())->toBe('2025-03-18 15:00:00');
    expect($booking2->is_exception)->toBeTrue();
    expect($booking2->original_starts_at->toDateTimeString())->toBe('2025-03-17 10:00:00');

    // Other occurrences should remain unchanged
    $booking1->refresh();
    $booking3->refresh();
    expect($booking1->starts_at->toDateTimeString())->toBe('2025-03-10 10:00:00');
    expect($booking3->starts_at->toDateTimeString())->toBe('2025-03-24 10:00:00');
});

test('PATCH bookings.reschedule on recurring booking with scope this_and_future shifts all future', function () {
    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $this->congregation->id,
        'frequency' => RecurrenceFrequency::Weekly,
        'end_count' => 4,
    ]);

    $booking1 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-03 09:00:00',
        'ends_at' => '2025-03-03 10:30:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking1->rooms()->attach($this->room);

    $booking2 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:30:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking2->rooms()->attach($this->room);

    $booking3 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-17 09:00:00',
        'ends_at' => '2025-03-17 10:30:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking3->rooms()->attach($this->room);

    $booking4 = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-24 09:00:00',
        'ends_at' => '2025-03-24 10:30:00',
        'recurrence_pattern_id' => $pattern->id,
    ]);
    $booking4->rooms()->attach($this->room);

    // Reschedule from booking2 forward: shift +3 hours (09:00 → 12:00)
    $response = $this->actingAs($this->user)
        ->patchJson(route('bookings.reschedule', [
            'current_congregation' => $this->congregation,
            'booking' => $booking2,
        ]), [
            'starts_at' => '2025-03-10 12:00:00',
            'scope' => 'this_and_future',
        ]);

    $response->assertOk();

    // Booking1 (past, before the rescheduled one) should be unchanged
    $booking1->refresh();
    expect($booking1->starts_at->toDateTimeString())->toBe('2025-03-03 09:00:00');
    expect($booking1->ends_at->toDateTimeString())->toBe('2025-03-03 10:30:00');

    // Booking2, 3, 4 should all be shifted +3 hours, duration preserved (90 min)
    $booking2->refresh();
    expect($booking2->starts_at->toDateTimeString())->toBe('2025-03-10 12:00:00');
    expect($booking2->ends_at->toDateTimeString())->toBe('2025-03-10 13:30:00');

    $booking3->refresh();
    expect($booking3->starts_at->toDateTimeString())->toBe('2025-03-17 12:00:00');
    expect($booking3->ends_at->toDateTimeString())->toBe('2025-03-17 13:30:00');

    $booking4->refresh();
    expect($booking4->starts_at->toDateTimeString())->toBe('2025-03-24 12:00:00');
    expect($booking4->ends_at->toDateTimeString())->toBe('2025-03-24 13:30:00');
});
