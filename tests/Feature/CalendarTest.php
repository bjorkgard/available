<?php

use App\Models\Congregation;
use App\Models\User;

test('authenticated users can visit the calendar', function () {
    $congregation = Congregation::factory()->withKingdomHall()->create();
    $user = User::factory()->withCongregation($congregation)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('calendar', ['current_congregation' => $congregation->slug]));

    $response->assertOk();
});

test('guests are redirected to the login page', function () {
    $congregation = Congregation::factory()->withKingdomHall()->create();

    $response = $this->get(route('calendar', ['current_congregation' => $congregation->slug]));

    $response->assertRedirect(route('login'));
});

test('the old dashboard route returns 404', function () {
    $congregation = Congregation::factory()->withKingdomHall()->create();
    $user = User::factory()->withCongregation($congregation)->create();

    $response = $this
        ->actingAs($user)
        ->get("/{$congregation->slug}/dashboard");

    $response->assertNotFound();
});

test('after login user is redirected to the calendar', function () {
    $congregation = Congregation::factory()->withKingdomHall()->create();
    $user = User::factory()->withCongregation($congregation)->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('calendar', ['current_congregation' => $congregation->slug]));
});
