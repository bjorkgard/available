<?php

// Feature: booking-system, Property 10: Cleanup idempotence

use App\Models\Booking;
use App\Models\Congregation;
use App\Models\RecurrencePattern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 16.6**
test('running cleanup twice on the same day produces the same final state', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();

    // Create a randomized mix of expired and recent bookings
    $expiredCount = rand(1, 5);
    $recentCount = rand(1, 5);

    // Expired bookings (older than 6 months)
    for ($i = 0; $i < $expiredCount; $i++) {
        $monthsAgo = rand(7, 18);
        Booking::factory()->create([
            'congregation_id' => $congregation->id,
            'user_id' => $user->id,
            'starts_at' => now('Europe/Stockholm')->subMonths($monthsAgo)->subHour(),
            'ends_at' => now('Europe/Stockholm')->subMonths($monthsAgo),
        ]);
    }

    // Recent bookings (within 6 months — should NOT be deleted)
    for ($i = 0; $i < $recentCount; $i++) {
        $monthsAgo = rand(0, 5);
        Booking::factory()->create([
            'congregation_id' => $congregation->id,
            'user_id' => $user->id,
            'starts_at' => now('Europe/Stockholm')->subMonths($monthsAgo)->subHour(),
            'ends_at' => now('Europe/Stockholm')->subMonths($monthsAgo),
        ]);
    }

    // Randomly add some expired bookings attached to recurrence patterns
    $patternCount = rand(0, 2);
    for ($i = 0; $i < $patternCount; $i++) {
        $pattern = RecurrencePattern::factory()->create([
            'congregation_id' => $congregation->id,
        ]);

        $expiredOccurrences = rand(1, 3);
        for ($j = 0; $j < $expiredOccurrences; $j++) {
            $monthsAgo = rand(7, 12);
            Booking::factory()->create([
                'congregation_id' => $congregation->id,
                'user_id' => $user->id,
                'recurrence_pattern_id' => $pattern->id,
                'starts_at' => now('Europe/Stockholm')->subMonths($monthsAgo)->subHour(),
                'ends_at' => now('Europe/Stockholm')->subMonths($monthsAgo),
            ]);
        }
    }

    // Run cleanup the first time
    $this->artisan('bookings:cleanup')->assertSuccessful();

    // Record the state after first run
    $bookingsAfterFirstRun = Booking::count();
    $patternsAfterFirstRun = RecurrencePattern::count();

    // Run cleanup a second time
    $this->artisan('bookings:cleanup')->assertSuccessful();

    // The second run should produce exactly the same final state (0 additional deletions)
    expect(Booking::count())->toBe($bookingsAfterFirstRun);
    expect(RecurrencePattern::count())->toBe($patternsAfterFirstRun);
})->repeat(30);
