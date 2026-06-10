<?php

use App\Models\Booking;
use App\Models\Congregation;
use App\Models\RecurrencePattern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it deletes bookings older than 6 months', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();

    // Expired booking (7 months ago)
    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'starts_at' => now('Europe/Stockholm')->subMonths(7)->subHour(),
        'ends_at' => now('Europe/Stockholm')->subMonths(7),
    ]);

    // Recent booking (1 month ago — should NOT be deleted)
    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'starts_at' => now('Europe/Stockholm')->subMonth()->subHour(),
        'ends_at' => now('Europe/Stockholm')->subMonth(),
    ]);

    $this->artisan('bookings:cleanup')
        ->expectsOutputToContain('Deleted 1 bookings and 0 orphaned patterns.')
        ->assertSuccessful();

    expect(Booking::count())->toBe(1);
});

test('it deletes orphaned recurrence patterns', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();

    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $congregation->id,
    ]);

    // Only booking in this pattern is expired
    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'recurrence_pattern_id' => $pattern->id,
        'starts_at' => now('Europe/Stockholm')->subMonths(7)->subHour(),
        'ends_at' => now('Europe/Stockholm')->subMonths(7),
    ]);

    $this->artisan('bookings:cleanup')
        ->expectsOutputToContain('Deleted 1 bookings and 1 orphaned patterns.')
        ->assertSuccessful();

    expect(Booking::count())->toBe(0);
    expect(RecurrencePattern::count())->toBe(0);
});

test('it preserves recurrence patterns with remaining bookings', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();

    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $congregation->id,
    ]);

    // Expired booking in this pattern
    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'recurrence_pattern_id' => $pattern->id,
        'starts_at' => now('Europe/Stockholm')->subMonths(7)->subHour(),
        'ends_at' => now('Europe/Stockholm')->subMonths(7),
    ]);

    // Future booking in the same pattern
    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'recurrence_pattern_id' => $pattern->id,
        'starts_at' => now('Europe/Stockholm')->addWeek(),
        'ends_at' => now('Europe/Stockholm')->addWeek()->addHour(),
    ]);

    $this->artisan('bookings:cleanup')
        ->expectsOutputToContain('Deleted 1 bookings and 0 orphaned patterns.')
        ->assertSuccessful();

    expect(Booking::count())->toBe(1);
    expect(RecurrencePattern::count())->toBe(1);
});

test('it is idempotent — second run deletes nothing additional', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();

    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'starts_at' => now('Europe/Stockholm')->subMonths(7)->subHour(),
        'ends_at' => now('Europe/Stockholm')->subMonths(7),
    ]);

    $this->artisan('bookings:cleanup')
        ->expectsOutputToContain('Deleted 1 bookings and 0 orphaned patterns.')
        ->assertSuccessful();

    $this->artisan('bookings:cleanup')
        ->expectsOutputToContain('Deleted 0 bookings and 0 orphaned patterns.')
        ->assertSuccessful();
});
