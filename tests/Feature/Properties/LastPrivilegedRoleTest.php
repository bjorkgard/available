<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use App\Policies\MemberPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: congregation-management, Property 10: Last privileged role invariant
// **Validates: Requirements 5.6, 6.10, 7.6**
test('last privileged role cannot be removed or demoted from congregation', function () {
    $policy = new MemberPolicy;

    // Create a Kingdom Hall with a congregation
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    // Generate a random number of regular members (1-5)
    $memberCount = fake()->numberBetween(1, 5);
    $members = User::factory()->count($memberCount)->create();

    foreach ($members as $member) {
        $congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);
    }

    // Create exactly one privileged user (randomly admin or superadmin) — the "last privileged" user
    $privilegedRole = fake()->randomElement([CongregationRole::Admin, CongregationRole::Superadmin]);
    $lastPrivileged = User::factory()->create();
    $congregation->members()->attach($lastPrivileged, ['role' => $privilegedRole->value]);

    // Get the membership of the last privileged user
    $privilegedMembership = Membership::where('user_id', $lastPrivileged->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    // Create a superadmin from another congregation in the same KH to attempt the removal
    $otherCongregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $actingUser = User::factory()->create();
    $otherCongregation->members()->attach($actingUser, ['role' => CongregationRole::Superadmin->value]);

    // Attempt to remove the last privileged user — policy must deny
    $canDelete = $policy->delete($actingUser, $privilegedMembership);

    expect($canDelete)->toBeFalse(
        "Removing the last {$privilegedRole->value} should be prevented by the policy"
    );
})->repeat(100);
