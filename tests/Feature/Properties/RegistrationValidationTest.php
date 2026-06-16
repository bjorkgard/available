<?php

// Feature: congregation-management, Property 2: Registration validation

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Validates: Requirements 2.1, 2.6**
 *
 * For any registration form submission where one or more required fields are missing,
 * the system SHALL reject the submission, return field-specific errors,
 * preserve non-password field values, and NOT authenticate the user.
 */
test('registration validation rejects incomplete submissions with correct errors', function () {
    $requiredFields = [
        'name',
        'email',
        'password',
        'congregation_name',
        'congregation_number',
        'street_address',
        'zip_code',
        'city',
        'country',
    ];

    $completePayload = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'SecurePass1!',
        'password_confirmation' => 'SecurePass1!',
        'congregation_name' => fake()->company(),
        'congregation_number' => strtoupper(fake()->bothify('??##??')),
        'street_address' => fake()->streetAddress(),
        'zip_code' => fake()->postcode(),
        'city' => fake()->city(),
        'country' => 'Sverige',
    ];

    // Randomly select 1-3 fields to remove
    $numberOfFieldsToRemove = fake()->numberBetween(1, 3);
    $fieldsToRemove = fake()->randomElements($requiredFields, $numberOfFieldsToRemove);

    $incompletePayload = $completePayload;
    foreach ($fieldsToRemove as $field) {
        unset($incompletePayload[$field]);
    }

    // If password is removed, also remove password_confirmation to keep them paired
    if (in_array('password', $fieldsToRemove)) {
        unset($incompletePayload['password_confirmation']);
    }

    $response = $this->post(route('register.store'), $incompletePayload);

    // Should have validation errors for each missing field
    foreach ($fieldsToRemove as $field) {
        $response->assertSessionHasErrors($field);
    }

    // User should NOT be authenticated
    $this->assertGuest();

    // Non-password fields that were submitted should be preserved in session old input
    $nonPasswordFields = ['name', 'email', 'congregation_name', 'congregation_number', 'street_address', 'zip_code', 'city', 'country'];
    foreach ($nonPasswordFields as $field) {
        if (array_key_exists($field, $incompletePayload)) {
            $response->assertSessionHasInput($field, $incompletePayload[$field]);
        }
    }

    // Password fields should never be preserved
    $inputKeys = array_keys(session()->getOldInput());
    expect($inputKeys)->not->toContain('password')
        ->and($inputKeys)->not->toContain('password_confirmation');

    // No user should have been created
    if (isset($incompletePayload['email'])) {
        expect(User::where('email', $incompletePayload['email'])->exists())->toBeFalse();
    }
})->repeat(30);
