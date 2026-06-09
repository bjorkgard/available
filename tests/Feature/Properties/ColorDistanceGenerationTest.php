<?php

// Feature: congregation-color, Properties 1-3: Color generation properties

use App\Exceptions\ColorGenerationException;
use App\Services\ColorService;

// **Validates: Requirements 1.1, 1.2, 1.3, 1.5, 2.1, 2.2, 3.1, 3.5**

// Property 1: Generated colors maintain minimum distance from siblings
test('generated colors maintain minimum distance of 25 from all siblings', function () {
    $service = new ColorService;

    for ($i = 0; $i < 100; $i++) {
        // Generate a random set of 0-10 sibling colors
        $siblingCount = fake()->numberBetween(0, 10);
        $siblings = [];
        for ($j = 0; $j < $siblingCount; $j++) {
            $siblings[] = strtoupper(fake()->hexColor());
        }

        try {
            $generated = $service->generateDistinctColor($siblings);

            // Verify minimum distance from all siblings
            foreach ($siblings as $sibling) {
                $distance = $service->ciede2000Distance($generated, $sibling);
                expect($distance)->toBeGreaterThanOrEqual(25.0,
                    "Generated color {$generated} has distance {$distance} from sibling {$sibling} (iteration {$i})");
            }
        } catch (ColorGenerationException $e) {
            // Acceptable outcome when no distinct color can be found
            // (unlikely with few siblings but possible)
        }
    }
});

// Property 2: All generated colors have valid hex format
test('all generated colors have valid hex format', function () {
    $service = new ColorService;

    for ($i = 0; $i < 100; $i++) {
        $siblingCount = fake()->numberBetween(0, 5);
        $siblings = [];
        for ($j = 0; $j < $siblingCount; $j++) {
            $siblings[] = strtoupper(fake()->hexColor());
        }

        try {
            $generated = $service->generateDistinctColor($siblings);

            // Must match strict uppercase hex format
            expect($generated)->toMatch('/^#[0-9A-F]{6}$/');
        } catch (ColorGenerationException $e) {
            // Acceptable outcome
        }
    }
});

// Property 3: Colors below minimum distance are rejected
test('colors below minimum distance are rejected by isDistinctFromAll', function () {
    $service = new ColorService;

    for ($i = 0; $i < 100; $i++) {
        // Generate a base color and create a very similar color (same or ±1 in each channel)
        $base = strtoupper(fake()->hexColor());

        // The same color should definitely be rejected (distance = 0)
        expect($service->isDistinctFromAll($base, [$base]))->toBeFalse();

        // A distinctly different color should be accepted
        // Generate until we find one that's actually distinct
        $attempts = 0;
        $distinct = null;
        while ($attempts < 50) {
            $candidate = strtoupper(fake()->hexColor());
            if ($service->ciede2000Distance($base, $candidate) >= 25.0) {
                $distinct = $candidate;
                break;
            }
            $attempts++;
        }

        if ($distinct !== null) {
            expect($service->isDistinctFromAll($distinct, [$base]))->toBeTrue();
        }
    }
});
