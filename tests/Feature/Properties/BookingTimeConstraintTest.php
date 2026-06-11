<?php

// Feature: booking-system, Property 1: Booking time constraint invariant

use App\Actions\Bookings\CreateBooking;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// **Validates: Requirements 1.3, 1.7**
test('created bookings always have ends_at > starts_at and both aligned to 15-minute boundaries', function () {
    $user = User::factory()->create();
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Member,
    ]);

    // Generate random 15-min aligned start time
    $hour = rand(0, 22);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $day = rand(1, 28);
    $month = rand(1, 12);
    $startsAt = Carbon::create(2025, $month, $day, $hour, $minute, 0, 'Europe/Stockholm');

    // Generate random duration in 15-min increments (15 min to 4 hours)
    $durationMinutes = fake()->randomElement([15, 30, 45, 60, 75, 90, 105, 120, 135, 150, 180, 240]);
    $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

    $action = app(CreateBooking::class);
    $bookings = $action->handle($user, $congregation, [
        'name' => fake()->words(rand(2, 4), true),
        'starts_at' => $startsAt->toIso8601String(),
        'ends_at' => $endsAt->toIso8601String(),
        'room_ids' => [$room->id],
    ]);

    $booking = $bookings->first();

    // Property: ends_at is strictly after starts_at
    expect($booking->ends_at->greaterThan($booking->starts_at))->toBeTrue();

    // Property: starts_at is aligned to 15-minute boundary
    expect($booking->starts_at->minute % 15)->toBe(0);
    expect($booking->starts_at->second)->toBe(0);

    // Property: ends_at is aligned to 15-minute boundary
    expect($booking->ends_at->minute % 15)->toBe(0);
    expect($booking->ends_at->second)->toBe(0);
})->repeat(30);

// **Validates: Requirements 1.3, 1.7**
test('non-15-minute-aligned start times are rejected by the booking action', function () {
    $user = User::factory()->create();
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Member,
    ]);

    // Generate a non-aligned minute (anything not 0, 15, 30, 45)
    $validMinutes = [0, 15, 30, 45];
    $invalidMinute = fake()->randomElement(array_diff(range(0, 59), $validMinutes));
    $startsAt = Carbon::create(2025, rand(1, 12), rand(1, 28), rand(0, 22), $invalidMinute, 0, 'Europe/Stockholm');
    $endsAt = $startsAt->copy()->addHour();

    $action = app(CreateBooking::class);

    expect(fn () => $action->handle($user, $congregation, [
        'name' => fake()->words(3, true),
        'starts_at' => $startsAt->toIso8601String(),
        'ends_at' => $endsAt->toIso8601String(),
        'room_ids' => [$room->id],
    ]))->toThrow(ValidationException::class);
})->repeat(30);

// **Validates: Requirements 1.3, 1.7**
test('non-15-minute-aligned end times are rejected by the booking action', function () {
    $user = User::factory()->create();
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Member,
    ]);

    // Valid aligned start time
    $startsAt = Carbon::create(2025, rand(1, 12), rand(1, 28), rand(0, 22), fake()->randomElement([0, 15, 30, 45]), 0, 'Europe/Stockholm');

    // Non-aligned end time
    $validMinutes = [0, 15, 30, 45];
    $invalidMinute = fake()->randomElement(array_diff(range(0, 59), $validMinutes));
    $endsAt = $startsAt->copy()->addHour()->setMinute($invalidMinute);

    $action = app(CreateBooking::class);

    expect(fn () => $action->handle($user, $congregation, [
        'name' => fake()->words(3, true),
        'starts_at' => $startsAt->toIso8601String(),
        'ends_at' => $endsAt->toIso8601String(),
        'room_ids' => [$room->id],
    ]))->toThrow(ValidationException::class);
})->repeat(30);
