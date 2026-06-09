<?php

use App\Models\Congregation;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->withCongregation()->create();
    $congregation = $user->fresh()->currentCongregation;

    $response = $this->get(route('dashboard', ['current_congregation' => $congregation->slug]));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $congregation = Congregation::factory()->withKingdomHall()->create();
    $user = User::factory()->withCongregation($congregation)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard', ['current_congregation' => $congregation->slug]));

    $response->assertOk();
});
