<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('valid registration creates user, congregation, and admin membership', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'CONG123',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->currentCongregation)->not->toBeNull()
        ->and($user->currentCongregation->name)->toBe('Test Congregation')
        ->and($user->currentCongregation->congregation_number)->toBe('CONG123');

    $membership = $user->congregationMemberships()->first();
    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(CongregationRole::Admin);

    $response->assertRedirect();
});

test('registration rejects duplicate congregation number', function () {
    Congregation::factory()->create(['congregation_number' => 'EXISTING1']);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'New Congregation',
        'congregation_number' => 'EXISTING1',
    ]);

    $response->assertSessionHasErrors('congregation_number');
    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});

test('registration rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'UNIQUE123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    expect(Congregation::where('congregation_number', 'UNIQUE123')->exists())->toBeFalse();
});

test('registration rejects non-alphanumeric congregation number', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'CONG-123!',
    ]);

    $response->assertSessionHasErrors('congregation_number');
    $this->assertGuest();
});

test('registration rejects lowercase congregation number', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'cong123',
    ]);

    $response->assertSessionHasErrors('congregation_number');
    $this->assertGuest();
});

test('registration rejects missing required fields', function (string $missingField) {
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'CONG123',
    ];

    unset($data[$missingField]);

    $response = $this->post(route('register.store'), $data);

    $response->assertSessionHasErrors($missingField);
    $this->assertGuest();
})->with([
    'name' => 'name',
    'email' => 'email',
    'password' => 'password',
    'congregation_name' => 'congregation_name',
    'congregation_number' => 'congregation_number',
]);

test('registration preserves non-password fields on validation error', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'short',
        'password_confirmation' => 'short',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'CONG123',
    ]);

    $response->assertSessionHasErrors('email');
    $response->assertRedirect(route('register'));

    $response->assertSessionHasInput('name', 'Test User');
    $response->assertSessionHasInput('congregation_name', 'Test Congregation');
    $response->assertSessionHasInput('congregation_number', 'CONG123');

    $inputKeys = array_keys(session()->getOldInput());
    expect($inputKeys)->not->toContain('password')
        ->and($inputKeys)->not->toContain('password_confirmation');
});
