<?php

// Feature: booking-system, Property 8: Cascade deletion completeness

use App\Actions\Congregations\DeleteCongregation;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\RecurrencePattern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 11.1, 11.2, 11.3**
test('zero bookings and recurrence patterns reference a congregation after deletion', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $user = User::factory()->create(['current_congregation_id' => $congregation->id]);

    // Create a membership so DeleteCongregation can process this user
    $congregation->memberships()->create([
        'user_id' => $user->id,
        'role' => fake()->randomElement(['admin', 'member']),
    ]);

    // Randomly decide how many standalone bookings (1-5)
    $standaloneCount = rand(1, 5);
    Booking::factory()
        ->count($standaloneCount)
        ->create([
            'congregation_id' => $congregation->id,
            'user_id' => $user->id,
        ]);

    // Randomly decide how many recurrence patterns (0-3)
    $patternCount = rand(0, 3);
    for ($i = 0; $i < $patternCount; $i++) {
        $pattern = RecurrencePattern::factory()->create([
            'congregation_id' => $congregation->id,
        ]);

        // Create 2-4 recurring bookings per pattern
        $recurringCount = rand(2, 4);
        Booking::factory()
            ->count($recurringCount)
            ->create([
                'congregation_id' => $congregation->id,
                'user_id' => $user->id,
                'recurrence_pattern_id' => $pattern->id,
            ]);

        // Randomly add 0-2 exception bookings per pattern
        $exceptionCount = rand(0, 2);
        if ($exceptionCount > 0) {
            Booking::factory()
                ->count($exceptionCount)
                ->exception()
                ->create([
                    'congregation_id' => $congregation->id,
                    'user_id' => $user->id,
                    'recurrence_pattern_id' => $pattern->id,
                ]);
        }
    }

    // Verify we actually have bookings and patterns before deletion
    $totalBookings = Booking::where('congregation_id', $congregation->id)->count();
    $totalPatterns = RecurrencePattern::where('congregation_id', $congregation->id)->count();
    expect($totalBookings)->toBeGreaterThan(0);

    // Perform deletion
    $action = new DeleteCongregation;
    $action->handle($congregation);

    // Assert zero bookings exist with that congregation's ID
    expect(Booking::where('congregation_id', $congregation->id)->count())->toBe(0);

    // Assert zero recurrence patterns exist with that congregation's ID
    expect(RecurrencePattern::where('congregation_id', $congregation->id)->count())->toBe(0);
})->repeat(30);
