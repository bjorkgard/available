<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: congregation-management, Property 7: Setup wizard gate blocks all protected routes
test('setup wizard gate redirects all protected routes when congregation has no kingdom hall', function () {
    $protectedRoutes = [
        'dashboard',
        'members.index',
        'members.invite',
        'members.update',
        'members.destroy',
        'kingdom-hall.show',
        'kingdom-hall.update',
        'kingdom-hall.destroy',
        'kingdom-hall.add-congregation',
        'congregation.move',
        'congregation.destroy',
    ];

    $route = fake()->randomElement($protectedRoutes);

    $congregation = Congregation::factory()->create(['kingdom_hall_id' => null]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Admin->value]);
    $user->switchCongregation($congregation);

    $routeParams = ['current_congregation' => $congregation->slug];

    // Routes that require a member parameter
    if (in_array($route, ['members.update', 'members.destroy'])) {
        $otherUser = User::factory()->create();
        $congregation->members()->attach($otherUser, ['role' => CongregationRole::Member->value]);
        $membership = $congregation->memberships()->where('user_id', $otherUser->id)->first();
        $routeParams['member'] = $membership->id;
    }

    $method = match ($route) {
        'members.invite', 'kingdom-hall.add-congregation', 'congregation.move' => 'post',
        'members.update', 'kingdom-hall.update' => 'put',
        'members.destroy', 'kingdom-hall.destroy', 'congregation.destroy' => 'delete',
        default => 'get',
    };

    $response = $this->actingAs($user)->{$method}(route($route, $routeParams));

    expect($response->status())->toBe(302, "Route [{$route}] should redirect when no Kingdom Hall");
    expect($response->headers->get('Location'))->toBe(route('setup.show'), "Route [{$route}] should redirect to setup wizard");
})->repeat(100);
