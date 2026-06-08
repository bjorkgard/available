<?php

// Feature: congregation-management, Property 3: Registration uniqueness constraints

use App\Models\Congregation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.4, 2.5**

test('registration rejects duplicate congregation number', function () {
    $congregationNumber = strtoupper(fake()->unique()->bothify('??######??'));

    Congregation::factory()->create(['congregation_number' => $congregationNumber]);

    $response = $this->post(route('register.store'), [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'congregation_name' => fake()->company(),
        'congregation_number' => $congregationNumber,
    ]);

    $response->assertSessionHasErrors('congregation_number');
    $this->assertGuest();

    expect(Congregation::where('congregation_number', $congregationNumber)->count())->toBe(1);
})->repeat(100);

test('registration rejects duplicate email', function () {
    $email = fake()->unique()->safeEmail();

    User::factory()->create(['email' => $email]);

    $congregationNumber = strtoupper(fake()->unique()->bothify('??######??'));

    $response = $this->post(route('register.store'), [
        'name' => fake()->name(),
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'congregation_name' => fake()->company(),
        'congregation_number' => $congregationNumber,
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    expect(User::where('email', $email)->count())->toBe(1);
    expect(Congregation::where('congregation_number', $congregationNumber)->exists())->toBeFalse();
})->repeat(100);
