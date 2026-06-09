<?php

// Feature: congregation-management, Property 9: Role scope enforcement

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 5.2, 5.3, 5.4, 7.1, 7.7**
test('role scope enforcement verifies access based on random role and action', function () {
    $roles = [CongregationRole::Superadmin, CongregationRole::Admin, CongregationRole::Member];
    $actions = ['invite', 'update_member', 'remove_member'];

    $role = fake()->randomElement($roles);
    $action = fake()->randomElement($actions);
    $targetOwn = fake()->boolean();

    // Create Kingdom Hall with two congregations
    $kingdomHall = KingdomHall::factory()->create();
    $congregationA = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $congregationB = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    // Create the acting user with the random role in congregation A
    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregationA->members()->attach($user, ['role' => $role->value]);
    $user->update(['current_congregation_id' => $congregationA->id]);

    // For superadmin testing cross-congregation, add membership to congregation B
    if ($role === CongregationRole::Superadmin && ! $targetOwn) {
        $congregationB->members()->attach($user, ['role' => CongregationRole::Superadmin->value]);
    }

    // Determine target congregation
    $targetCongregation = $targetOwn ? $congregationA : $congregationB;

    // Ensure there's an additional admin in congregation A so we're not blocked by last-admin checks
    $extraAdmin = User::factory()->create(['email_verified_at' => now()]);
    $congregationA->members()->attach($extraAdmin, ['role' => CongregationRole::Admin->value]);

    // Create a target member in the target congregation for update/remove actions
    $targetMember = User::factory()->create(['email_verified_at' => now()]);
    $targetCongregation->members()->attach($targetMember, ['role' => CongregationRole::Member->value]);
    $targetMembership = Membership::where('user_id', $targetMember->id)
        ->where('congregation_id', $targetCongregation->id)
        ->first();

    // Ensure there's an admin in congregation B so last-admin doesn't interfere
    $extraAdminB = User::factory()->create(['email_verified_at' => now()]);
    $congregationB->members()->attach($extraAdminB, ['role' => CongregationRole::Admin->value]);

    // Execute the action
    $response = match ($action) {
        'invite' => $this->actingAs($user)->post(
            route('members.invite', ['current_congregation' => $targetCongregation->slug]),
            ['name' => 'Test Invite', 'email' => fake()->unique()->safeEmail(), 'role' => CongregationRole::Member->value]
        ),
        'update_member' => $this->actingAs($user)->put(
            route('members.update', ['current_congregation' => $targetCongregation->slug, 'member' => $targetMembership->id]),
            ['role' => CongregationRole::Admin->value]
        ),
        'remove_member' => $this->actingAs($user)->delete(
            route('members.destroy', ['current_congregation' => $targetCongregation->slug, 'member' => $targetMembership->id])
        ),
    };

    // Determine expected outcome based on role and scope
    $shouldSucceed = match ($role) {
        CongregationRole::Superadmin => true, // Superadmin succeeds on all KH congregations
        CongregationRole::Admin => $targetOwn, // Admin succeeds only on own congregation
        CongregationRole::Member => false, // Member always fails
    };

    if ($shouldSucceed) {
        expect($response->status())->not->toBe(403,
            "Role [{$role->value}] should be able to [{$action}] on ".($targetOwn ? 'own' : 'other').' congregation'
        );
    } else {
        expect($response->status())->toBe(403,
            "Role [{$role->value}] should NOT be able to [{$action}] on ".($targetOwn ? 'own' : 'other').' congregation'
        );
    }
})->repeat(30);
