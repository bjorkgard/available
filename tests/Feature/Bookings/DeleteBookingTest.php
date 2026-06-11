<?php

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\DeleteBooking;
use App\Enums\CongregationRole;
use App\Enums\DeleteScope;
use App\Events\BookingDeleted;
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
    $this->congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->user = User::factory()->create();
    $this->congregation->members()->attach($this->user, ['role' => CongregationRole::Member->value]);
});

test('deletes a standalone booking with "all" scope', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $action = app(DeleteBooking::class);
    $action->handle($this->user, $booking, DeleteScope::All);

    expect(Booking::find($booking->id))->toBeNull();
    Event::assertDispatched(BookingDeleted::class);
});

test('deletes a single occurrence with "this_only" scope', function () {
    $bookings = app(CreateBooking::class)->handle($this->user, $this->congregation, [
        'name' => 'Weekly Meeting',
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->room->id],
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 4,
        ],
    ]);

    $secondOccurrence = $bookings[1];
    $patternId = $secondOccurrence->recurrence_pattern_id;

    $action = app(DeleteBooking::class);
    $action->handle($this->user, $secondOccurrence, DeleteScope::ThisOnly);

    expect(Booking::find($secondOccurrence->id))->toBeNull();
    // Other occurrences remain
    expect(Booking::where('recurrence_pattern_id', $patternId)->count())->toBe(3);
    // Pattern still exists
    expect(RecurrencePattern::find($patternId))->not->toBeNull();

    Event::assertDispatched(BookingDeleted::class);
});

test('deletes all future occurrences with "all_future" scope', function () {
    $bookings = app(CreateBooking::class)->handle($this->user, $this->congregation, [
        'name' => 'Weekly Meeting',
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->room->id],
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 4,
        ],
    ]);

    $secondOccurrence = $bookings[1];
    $patternId = $secondOccurrence->recurrence_pattern_id;

    $action = app(DeleteBooking::class);
    $action->handle($this->user, $secondOccurrence, DeleteScope::AllFuture);

    // First occurrence remains, 2nd-4th are deleted
    expect(Booking::where('recurrence_pattern_id', $patternId)->count())->toBe(1);
    expect(Booking::find($bookings[0]->id))->not->toBeNull();
    expect(Booking::find($bookings[1]->id))->toBeNull();
    expect(Booking::find($bookings[2]->id))->toBeNull();
    expect(Booking::find($bookings[3]->id))->toBeNull();
    // Pattern still exists (one booking remains)
    expect(RecurrencePattern::find($patternId))->not->toBeNull();

    Event::assertDispatched(BookingDeleted::class);
});

test('deletes recurrence pattern when all occurrences are removed via "all_future"', function () {
    $bookings = app(CreateBooking::class)->handle($this->user, $this->congregation, [
        'name' => 'Weekly Meeting',
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->room->id],
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 3,
        ],
    ]);

    $firstOccurrence = $bookings[0];
    $patternId = $firstOccurrence->recurrence_pattern_id;

    $action = app(DeleteBooking::class);
    $action->handle($this->user, $firstOccurrence, DeleteScope::AllFuture);

    // All bookings deleted
    expect(Booking::where('recurrence_pattern_id', $patternId)->count())->toBe(0);
    // Pattern also deleted
    expect(RecurrencePattern::find($patternId))->toBeNull();

    Event::assertDispatched(BookingDeleted::class);
});

test('deletes recurrence pattern when last occurrence is removed via "this_only"', function () {
    $bookings = app(CreateBooking::class)->handle($this->user, $this->congregation, [
        'name' => 'One-time Recurring',
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->room->id],
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 1,
        ],
    ]);

    $onlyOccurrence = $bookings[0];
    $patternId = $onlyOccurrence->recurrence_pattern_id;

    $action = app(DeleteBooking::class);
    $action->handle($this->user, $onlyOccurrence, DeleteScope::ThisOnly);

    expect(Booking::find($onlyOccurrence->id))->toBeNull();
    expect(RecurrencePattern::find($patternId))->toBeNull();

    Event::assertDispatched(BookingDeleted::class);
});

test('dispatches BookingDeleted event with correct booking IDs', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);
    $bookingId = $booking->id;

    $action = app(DeleteBooking::class);
    $action->handle($this->user, $booking, DeleteScope::All);

    Event::assertDispatched(BookingDeleted::class, function ($event) use ($bookingId) {
        return $event->bookingIds->contains($bookingId);
    });
});
