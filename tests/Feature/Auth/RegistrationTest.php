<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('valid registration creates user, congregation, kingdom hall and superadmin membership', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'CONG123',
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and($user->currentCongregation)->not->toBeNull()
        ->and($user->currentCongregation->name)->toBe('Test Congregation')
        ->and($user->currentCongregation->congregation_number)->toBe('CONG123')
        ->and($user->currentCongregation->kingdom_hall_id)->not->toBeNull();

    $kingdomHall = $user->currentCongregation->kingdomHall;
    expect($kingdomHall)->not->toBeNull()
        ->and($kingdomHall->street_address)->toBe('Testvägen 1')
        ->and($kingdomHall->zip_code)->toBe('12345')
        ->and($kingdomHall->city)->toBe('Teststad')
        ->and($kingdomHall->country)->toBe('Sverige')
        ->and($kingdomHall->rooms)->toHaveCount(1);

    $membership = $user->congregationMemberships()->first();
    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(CongregationRole::Superadmin);

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
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
    ]);

    $response->assertSessionHasErrors('congregation_number');
    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});

test('registration rejects duplicate kingdom hall address and shows superadmins', function () {
    $hall = KingdomHall::factory()->create([
        'street_address' => 'Hallvägen 5',
        'zip_code' => '11111',
        'city' => 'Hallstad',
        'country' => 'Sverige',
    ]);

    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $hall->id]);
    $superadmin = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);
    $congregation->memberships()->create([
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'NEWCONG1',
        'street_address' => 'Hallvägen 5',
        'zip_code' => '11111',
        'city' => 'Hallstad',
        'country' => 'Sverige',
    ]);

    $response->assertSessionHasErrors(['street_address', 'existing_hall_superadmins']);
    $this->assertGuest();
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
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
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
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
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
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
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
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
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
    'street_address' => 'street_address',
    'zip_code' => 'zip_code',
    'city' => 'city',
    'country' => 'country',
]);

test('registration preserves non-password fields on validation error', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'short',
        'password_confirmation' => 'short',
        'congregation_name' => 'Test Congregation',
        'congregation_number' => 'CONG123',
        'street_address' => 'Testvägen 1',
        'zip_code' => '12345',
        'city' => 'Teststad',
        'country' => 'Sverige',
    ]);

    $response->assertSessionHasErrors('email');
    $response->assertRedirect(route('register'));

    $response->assertSessionHasInput('name', 'Test User');
    $response->assertSessionHasInput('congregation_name', 'Test Congregation');
    $response->assertSessionHasInput('congregation_number', 'CONG123');
    $response->assertSessionHasInput('street_address', 'Testvägen 1');
    $response->assertSessionHasInput('zip_code', '12345');
    $response->assertSessionHasInput('city', 'Teststad');
    $response->assertSessionHasInput('country', 'Sverige');

    $inputKeys = array_keys(session()->getOldInput());
    expect($inputKeys)->not->toContain('password')
        ->and($inputKeys)->not->toContain('password_confirmation');
});
