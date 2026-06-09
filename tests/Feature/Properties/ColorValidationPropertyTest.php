<?php

// Feature: congregation-color, Property 4: Hex format validation

use App\Services\ColorService;

/**
 * **Validates: Requirements 3.3, 3.4**
 *
 * For any string, ColorService::isValidHex() SHALL return true if and only if
 * the string matches /^#[0-9A-Fa-f]{6}$/. Valid input in any casing is accepted.
 */
test('isValidHex accepts valid hex colors and rejects invalid strings', function () {
    // Generate valid hex colors (both cases should be accepted)
    $validUpper = '#'.strtoupper(fake()->regexify('[0-9A-F]{6}'));
    $validLower = '#'.strtolower(fake()->regexify('[0-9a-f]{6}'));
    $validMixed = strtoupper(fake()->hexColor());

    expect(ColorService::isValidHex($validUpper))->toBeTrue("Expected valid: {$validUpper}");
    expect(ColorService::isValidHex($validLower))->toBeTrue("Expected valid: {$validLower}");
    expect(ColorService::isValidHex($validMixed))->toBeTrue("Expected valid: {$validMixed}");

    // Generate invalid strings
    $invalidNoHash = fake()->regexify('[0-9A-F]{6}');
    $invalidShort = '#'.fake()->regexify('[0-9A-F]{3}');
    $invalidLong = '#'.fake()->regexify('[0-9A-F]{8}');
    $invalidChars = '#'.fake()->regexify('[G-Z]{6}');
    $invalidEmpty = '';
    $invalidSpace = ' #AABBCC';

    expect(ColorService::isValidHex($invalidNoHash))->toBeFalse("Expected invalid: {$invalidNoHash}");
    expect(ColorService::isValidHex($invalidShort))->toBeFalse("Expected invalid: {$invalidShort}");
    expect(ColorService::isValidHex($invalidLong))->toBeFalse("Expected invalid: {$invalidLong}");
    expect(ColorService::isValidHex($invalidChars))->toBeFalse("Expected invalid: {$invalidChars}");
    expect(ColorService::isValidHex($invalidEmpty))->toBeFalse();
    expect(ColorService::isValidHex($invalidSpace))->toBeFalse();
})->repeat(30);
