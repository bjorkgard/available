<?php

use App\Actions\Bookings\TransferBookings;
use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\User;

beforeEach(function () {
    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->source = User::factory()->create();
    $this->target = User::factory()->create();
    $this->congregation->members()->attach($this->source, ['role' => CongregationRole::Member->value]);
    $this->congregation->members()->attach($this->target, ['role' => CongregationRole::Member->value]);
});

test('transfers future bookings from source to target user', function () {
    $futureBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->source->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $action = app(TransferBookings::class);
    $count = $action->handle($this->source, $this->target, $this->congregation);

    expect($count)->toBe(1);
    expect($futureBooking->fresh()->user_id)->toBe($this->target->id);
});

test('does not transfer past bookings', function () {
    $pastBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->source->id,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->subDays(3)->addHour(),
    ]);

    $action = app(TransferBookings::class);
    $count = $action->handle($this->source, $this->target, $this->congregation);

    expect($count)->toBe(0);
    expect($pastBooking->fresh()->user_id)->toBe($this->source->id);
});

test('does not transfer bookings from other congregations', function () {
    $otherCongregation = Congregation::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);

    $otherBooking = Booking::factory()->create([
        'congregation_id' => $otherCongregation->id,
        'user_id' => $this->source->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $action = app(TransferBookings::class);
    $count = $action->handle($this->source, $this->target, $this->congregation);

    expect($count)->toBe(0);
    expect($otherBooking->fresh()->user_id)->toBe($this->source->id);
});

test('returns zero when source has no future bookings', function () {
    $action = app(TransferBookings::class);
    $count = $action->handle($this->source, $this->target, $this->congregation);

    expect($count)->toBe(0);
});

test('transfers multiple future bookings at once', function () {
    Booking::factory()->count(3)->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->source->id,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHour(),
    ]);

    $action = app(TransferBookings::class);
    $count = $action->handle($this->source, $this->target, $this->congregation);

    expect($count)->toBe(3);
    expect(Booking::where('user_id', $this->target->id)->count())->toBe(3);
    expect(Booking::where('user_id', $this->source->id)->count())->toBe(0);
});
