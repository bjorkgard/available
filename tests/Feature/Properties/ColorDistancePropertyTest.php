<?php

// Feature: congregation-color, Properties 5-8: CIEDE2000 distance properties

use App\Services\ColorService;

// **Validates: Requirements 4.2**
// Property 5: CIEDE2000 distance is non-negative
test('CIEDE2000 distance is non-negative for any two valid colors', function () {
    $service = new ColorService;

    for ($i = 0; $i < 100; $i++) {
        $color1 = strtoupper(fake()->hexColor());
        $color2 = strtoupper(fake()->hexColor());

        $distance = $service->ciede2000Distance($color1, $color2);

        expect($distance)->toBeGreaterThanOrEqual(0.0);
    }
});

// **Validates: Requirements 4.3**
// Property 6: CIEDE2000 distance is symmetric
test('CIEDE2000 distance is symmetric', function () {
    $service = new ColorService;

    for ($i = 0; $i < 100; $i++) {
        $color1 = strtoupper(fake()->hexColor());
        $color2 = strtoupper(fake()->hexColor());

        $distanceAB = $service->ciede2000Distance($color1, $color2);
        $distanceBA = $service->ciede2000Distance($color2, $color1);

        expect(abs($distanceAB - $distanceBA))->toBeLessThanOrEqual(0.0001);
    }
});

// **Validates: Requirements 4.4**
// Property 7: CIEDE2000 distance identity
test('CIEDE2000 distance of a color with itself is zero', function () {
    $service = new ColorService;

    for ($i = 0; $i < 100; $i++) {
        $color = strtoupper(fake()->hexColor());

        $distance = $service->ciede2000Distance($color, $color);

        expect($distance)->toBe(0.0);
    }
});

// **Validates: Requirements 4.5**
// Property 8: Invalid hex input throws validation error
test('CIEDE2000 distance throws for invalid hex inputs', function () {
    $service = new ColorService;

    $invalidInputs = [
        'not-a-color', '#GGG', '#12345', '123456', '#1234567', '', ' #AABBCC',
        '#AABBCC ', 'rgb(0,0,0)', '#ABC', 'AABBCC', '#aabbcc00',
    ];

    for ($i = 0; $i < 100; $i++) {
        // Generate random invalid strings
        $invalid = fake()->regexify('[^#0-9A-Fa-f]{1,10}');
        if (ColorService::isValidHex($invalid)) {
            continue; // Skip any accidental valid hex
        }

        // Test first argument invalid
        expect(fn () => $service->ciede2000Distance($invalid, '#FF0000'))
            ->toThrow(InvalidArgumentException::class);

        // Test second argument invalid
        expect(fn () => $service->ciede2000Distance('#FF0000', $invalid))
            ->toThrow(InvalidArgumentException::class);
    }

    // Also test the curated list
    foreach ($invalidInputs as $invalid) {
        expect(fn () => $service->ciede2000Distance($invalid, '#FF0000'))
            ->toThrow(InvalidArgumentException::class);
    }
});
