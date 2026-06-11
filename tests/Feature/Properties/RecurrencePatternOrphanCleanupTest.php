<?php

// Feature: booking-system, Property 11: Recurrence pattern orphan cleanup

use App\Models\Booking;
use App\Models\Congregation;
use App\Models\RecurrencePattern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 16.4, 10.4**
test('recurrence pattern is deleted when all its bookings are cleaned up', function () {
    $congregation = Congregation::factory()->create();
    $user = User::factory()->create();

    $pattern = RecurrencePattern::factory()->create([
        'congregation_id' => $congregation->id,
    ]);

    // Create multiple expired bookings (all > 6 months old) belonging to this pattern
    $bookingCount = rand(2, 8);
    $cutoff = now('Europe/Stockholm')->subMonths(6);

    for ($i = 0; $i < $bookingCount; $i++) {
        // Random expired date between 7 and 18 months ago
        $monthsAgo = rand(7, 18);
        $startsAt = now('Europe/Stockholm')->subMonths($monthsAgo)->subHours(rand(1, 5));
        $endsAt = (clone $startsAt)->addMinutes(fake()->randomElement([15, 30, 45, 60, 90, 120]));

        Booking::factory()->create([
            'congregation_id' => $congregation->id,
            'user_id' => $user->id,
            'recurrence_pattern_id' => $pattern->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }

    // Verify preconditions: bookings and pattern exist
    expect(Booking::where('recurrence_pattern_id', $pattern->id)->count())->toBe($bookingCount);
    expect(RecurrencePattern::where('id', $pattern->id)->exists())->toBeTrue();

    // All bookings should be expired (ends_at < cutoff)
    $nonExpiredCount = Booking::where('recurrence_pattern_id', $pattern->id)
        ->where('ends_at', '>=', $cutoff)
        ->count();
    expect($nonExpiredCount)->toBe(0);

    // Run cleanup
    $this->artisan('bookings:cleanup')->assertSuccessful();

    // Assert: all bookings for this pattern were deleted
    expect(Booking::where('recurrence_pattern_id', $pattern->id)->count())->toBe(0);

    // Assert: the orphaned recurrence pattern was also deleted
    expect(RecurrencePattern::where('id', $pattern->id)->exists())->toBeFalse();
})->repeat(30);
