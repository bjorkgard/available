<?php

use App\Actions\Bookings\CreateBooking;
use App\Enums\CongregationRole;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Event::fake();

    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->rooms = Room::factory()->count(2)->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->user = User::factory()->create();
    $this->congregation->members()->attach($this->user, ['role' => CongregationRole::Member->value]);
});

test('creates a single booking with rooms', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Weekly Meeting',
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:30:00',
        'room_ids' => [$this->rooms[0]->id],
    ];

    $bookings = $action->handle($this->user, $this->congregation, $data);

    expect($bookings)->toHaveCount(1);
    expect($bookings->first()->name)->toBe('Weekly Meeting');
    expect($bookings->first()->congregation_id)->toBe($this->congregation->id);
    expect($bookings->first()->user_id)->toBe($this->user->id);
    expect($bookings->first()->rooms)->toHaveCount(1);
    expect($bookings->first()->recurrence_pattern_id)->toBeNull();

    Event::assertDispatched(BookingCreated::class);
});

test('creates a recurring booking with weekly frequency', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Service Group',
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->rooms[0]->id],
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 4,
        ],
    ];

    $bookings = $action->handle($this->user, $this->congregation, $data);

    expect($bookings)->toHaveCount(4);
    expect($bookings->first()->recurrence_pattern_id)->not->toBeNull();

    // All share the same recurrence pattern
    $patternId = $bookings->first()->recurrence_pattern_id;
    expect($bookings->every(fn ($b) => $b->recurrence_pattern_id === $patternId))->toBeTrue();
});

test('rejects booking with non-15-minute-aligned start time', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Invalid Meeting',
        'starts_at' => '2025-03-10 10:07:00',
        'ends_at' => '2025-03-10 11:00:00',
        'room_ids' => [$this->rooms[0]->id],
    ];

    $action->handle($this->user, $this->congregation, $data);
})->throws(ValidationException::class);

test('rejects booking with end time before start time', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Invalid Meeting',
        'starts_at' => '2025-03-10 11:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->rooms[0]->id],
    ];

    $action->handle($this->user, $this->congregation, $data);
})->throws(ValidationException::class);

test('rejects booking with rooms not belonging to congregation kingdom hall', function () {
    $otherHall = KingdomHall::factory()->create();
    $otherRoom = Room::factory()->create(['kingdom_hall_id' => $otherHall->id]);

    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Invalid Meeting',
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
        'room_ids' => [$otherRoom->id],
    ];

    $action->handle($this->user, $this->congregation, $data);
})->throws(ValidationException::class);

test('rejects booking with empty room selection', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Invalid Meeting',
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
        'room_ids' => [],
    ];

    $action->handle($this->user, $this->congregation, $data);
})->throws(ValidationException::class);

test('detects conflict with existing booking in same room and time range', function () {
    // Create an existing booking
    $existing = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
    ]);
    $existing->rooms()->attach($this->rooms[0]->id);

    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Conflicting Meeting',
        'starts_at' => '2025-03-10 10:30:00',
        'ends_at' => '2025-03-10 11:30:00',
        'room_ids' => [$this->rooms[0]->id],
    ];

    $action->handle($this->user, $this->congregation, $data);
})->throws(ValidationException::class);

test('allows booking in different room at same time', function () {
    // Create an existing booking in room 0
    $existing = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
    ]);
    $existing->rooms()->attach($this->rooms[0]->id);

    $action = app(CreateBooking::class);

    // Book room 1 at the same time — should succeed
    $data = [
        'name' => 'Parallel Meeting',
        'starts_at' => '2025-03-10 10:00:00',
        'ends_at' => '2025-03-10 11:00:00',
        'room_ids' => [$this->rooms[1]->id],
    ];

    $bookings = $action->handle($this->user, $this->congregation, $data);

    expect($bookings)->toHaveCount(1);
});

test('rejects entire recurring booking if any occurrence conflicts', function () {
    // Create an existing booking on the 3rd week
    $existing = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-03-24 09:00:00',
        'ends_at' => '2025-03-24 10:00:00',
    ]);
    $existing->rooms()->attach($this->rooms[0]->id);

    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Weekly Conflict',
        'starts_at' => '2025-03-10 09:00:00',
        'ends_at' => '2025-03-10 10:00:00',
        'room_ids' => [$this->rooms[0]->id],
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 4,
        ],
    ];

    $action->handle($this->user, $this->congregation, $data);
})->throws(ValidationException::class);

test('limits recurrence occurrences to 365 maximum', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Daily Long',
        'starts_at' => '2025-01-01 08:00:00',
        'ends_at' => '2025-01-01 09:00:00',
        'room_ids' => [$this->rooms[0]->id],
        'recurrence' => [
            'frequency' => 'daily',
            'end_date' => '2027-01-01',
        ],
    ];

    $bookings = $action->handle($this->user, $this->congregation, $data);

    expect($bookings)->toHaveCount(365);
});

test('creates booking with multiple rooms', function () {
    $action = app(CreateBooking::class);

    $data = [
        'name' => 'Big Event',
        'starts_at' => '2025-03-10 14:00:00',
        'ends_at' => '2025-03-10 16:00:00',
        'room_ids' => $this->rooms->pluck('id')->all(),
    ];

    $bookings = $action->handle($this->user, $this->congregation, $data);

    expect($bookings)->toHaveCount(1);
    expect($bookings->first()->rooms)->toHaveCount(2);
});
