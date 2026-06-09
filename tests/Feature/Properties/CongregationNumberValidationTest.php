<?php

// Feature: congregation-management, Property 1: Congregation number validation

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Validates: Requirements 1.3, 2.7**
 *
 * For any string input used as a congregation number, the system SHALL accept it
 * if and only if it is 1–20 characters long and consists exclusively of digits (0–9)
 * and uppercase Latin letters (A–Z). All other strings SHALL be rejected.
 */
test('congregation number accepts only valid uppercase alphanumeric strings 1-20 chars', function () {
    $congregationNumber = generateRandomCongregationNumberInput();

    $response = $this->post(route('register.store'), [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'congregation_name' => fake()->company(),
        'congregation_number' => $congregationNumber,
    ]);

    $isValid = preg_match('/^[A-Z0-9]{1,20}$/', $congregationNumber) === 1;

    if ($isValid) {
        $response->assertSessionDoesntHaveErrors('congregation_number');
        $this->assertAuthenticated();
    } else {
        $response->assertSessionHasErrors('congregation_number');
        $this->assertGuest();
    }

    // Clean up auth state for next iteration
    auth()->logout();
})->repeat(30);

/**
 * Generates a random string that mixes valid and invalid congregation number inputs.
 * Covers: uppercase letters, lowercase letters, digits, symbols, spaces, unicode,
 * empty strings, and strings exceeding the max length.
 */
function generateRandomCongregationNumberInput(): string
{
    $strategy = fake()->numberBetween(1, 8);

    return match ($strategy) {
        // Valid: uppercase alphanumeric 1-20 chars
        1 => fake()->regexify('[A-Z0-9]{'.fake()->numberBetween(1, 20).'}'),
        // Invalid: contains lowercase letters
        2 => fake()->regexify('[a-zA-Z0-9]{'.fake()->numberBetween(1, 20).'}'),
        // Invalid: contains special characters/symbols
        3 => fake()->regexify('[A-Z0-9!@#$%^&*()-_=+]{'.fake()->numberBetween(1, 20).'}'),
        // Invalid: contains spaces
        4 => 'CONG '.fake()->regexify('[A-Z0-9]{1,14}'),
        // Invalid: too long (21+ characters)
        5 => fake()->regexify('[A-Z0-9]{21,40}'),
        // Invalid: empty string
        6 => '',
        // Invalid: contains unicode characters
        7 => fake()->regexify('[A-Z0-9]{1,10}').fake()->randomElement(['é', 'ñ', 'ü', '中', '日', 'α', 'β']),
        // Mixed: randomly generated string of any printable ASCII character
        8 => implode('', array_map(fn () => chr(fake()->numberBetween(32, 126)), range(1, fake()->numberBetween(1, 25)))),
    };
}
