<?php

use App\Exceptions\ColorGenerationException;
use App\Services\ColorService;

/*
|--------------------------------------------------------------------------
| hexToLab — Known Reference Color Conversions
|--------------------------------------------------------------------------
| Validates: Requirements 4.1 — D65/2° observer, RGB→XYZ→Lab conversion
*/

test('hexToLab converts white correctly', function () {
    $service = new ColorService;
    $lab = $service->hexToLab('#FFFFFF');

    // White: L*=100, a*=0, b*=0
    expect($lab['L'])->toBeBetween(99.9, 100.1);
    expect($lab['a'])->toBeBetween(-0.5, 0.5);
    expect($lab['b'])->toBeBetween(-0.5, 0.5);
});

test('hexToLab converts black correctly', function () {
    $service = new ColorService;
    $lab = $service->hexToLab('#000000');

    expect($lab['L'])->toBe(0.0);
    expect($lab['a'])->toBe(0.0);
    expect($lab['b'])->toBe(0.0);
});

test('hexToLab converts pure red correctly', function () {
    $service = new ColorService;
    $lab = $service->hexToLab('#FF0000');

    // Known values for pure sRGB red: L*≈53.23, a*≈80.11, b*≈67.22
    expect($lab['L'])->toBeBetween(52.7, 53.8);
    expect($lab['a'])->toBeBetween(79.5, 80.7);
    expect($lab['b'])->toBeBetween(66.7, 67.8);
});

test('hexToLab converts pure green correctly', function () {
    $service = new ColorService;
    $lab = $service->hexToLab('#00FF00');

    // Known values for pure sRGB green: L*≈87.74, a*≈-86.18, b*≈83.18
    expect($lab['L'])->toBeBetween(87.2, 88.3);
    expect($lab['a'])->toBeBetween(-86.7, -85.7);
    expect($lab['b'])->toBeBetween(82.7, 83.7);
});

test('hexToLab converts pure blue correctly', function () {
    $service = new ColorService;
    $lab = $service->hexToLab('#0000FF');

    // Known values for pure sRGB blue: L*≈32.30, a*≈79.20, b*≈-107.86
    expect($lab['L'])->toBeBetween(31.8, 32.8);
    expect($lab['a'])->toBeBetween(78.7, 79.7);
    expect($lab['b'])->toBeBetween(-108.4, -107.4);
});

/*
|--------------------------------------------------------------------------
| ciede2000Distance — Published Reference Pairs
|--------------------------------------------------------------------------
| Validates: Requirements 4.2, 4.3, 4.4 — CIEDE2000 correctness
*/

test('ciede2000Distance of identical colors is zero', function () {
    $service = new ColorService;

    expect($service->ciede2000Distance('#FF0000', '#FF0000'))->toBe(0.0);
    expect($service->ciede2000Distance('#000000', '#000000'))->toBe(0.0);
    expect($service->ciede2000Distance('#FFFFFF', '#FFFFFF'))->toBe(0.0);
    expect($service->ciede2000Distance('#3B82F6', '#3B82F6'))->toBe(0.0);
});

test('ciede2000Distance of black and white is approximately 100', function () {
    $service = new ColorService;

    $distance = $service->ciede2000Distance('#000000', '#FFFFFF');

    // Black and white should have a large distance (around 100)
    expect($distance)->toBeGreaterThan(90.0);
    expect($distance)->toBeLessThan(110.0);
});

test('ciede2000Distance is symmetric', function () {
    $service = new ColorService;

    $pairs = [
        ['#FF0000', '#00FF00'],
        ['#0000FF', '#FFFF00'],
        ['#FF00FF', '#00FFFF'],
        ['#123456', '#654321'],
    ];

    foreach ($pairs as [$a, $b]) {
        expect($service->ciede2000Distance($a, $b))
            ->toBe($service->ciede2000Distance($b, $a));
    }
});

test('ciede2000Distance of similar colors is small', function () {
    $service = new ColorService;

    // Two very similar blues should have small distance
    $distance = $service->ciede2000Distance('#3B82F6', '#3B84F8');

    expect($distance)->toBeLessThan(2.0);
    expect($distance)->toBeGreaterThan(0.0);
});

test('ciede2000Distance of dissimilar colors is large', function () {
    $service = new ColorService;

    // Red vs cyan should be very different
    $distance = $service->ciede2000Distance('#FF0000', '#00FFFF');

    expect($distance)->toBeGreaterThan(50.0);
});

/*
|--------------------------------------------------------------------------
| generateDistinctColor — Generation with No Siblings
|--------------------------------------------------------------------------
| Validates: Requirements 1.2 — random color without distance check
*/

test('generateDistinctColor with no siblings returns valid hex', function () {
    $service = new ColorService;

    $color = $service->generateDistinctColor([]);

    expect($color)->toMatch('/^#[0-9A-F]{6}$/');
    expect(ColorService::isValidHex($color))->toBeTrue();
});

test('generateDistinctColor with no siblings produces different colors', function () {
    $service = new ColorService;

    $colors = [];
    for ($i = 0; $i < 10; $i++) {
        $colors[] = $service->generateDistinctColor([]);
    }

    // With 10 random colors, we should get at least 2 unique values
    expect(count(array_unique($colors)))->toBeGreaterThan(1);
});

/*
|--------------------------------------------------------------------------
| generateDistinctColor — Exception After 100 Failed Attempts
|--------------------------------------------------------------------------
| Validates: Requirements 1.4 — max 100 attempts before failure
*/

test('generateDistinctColor throws after 100 failed attempts', function () {
    $service = Mockery::mock(ColorService::class)->makePartial();
    $service->shouldReceive('isDistinctFromAll')->andReturn(false);

    // Need at least one sibling for the distance check to trigger
    expect(fn () => $service->generateDistinctColor(['#FF0000']))
        ->toThrow(ColorGenerationException::class);
});
