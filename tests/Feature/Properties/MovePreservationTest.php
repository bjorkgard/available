<?php

// Feature: congregation-management, Property 16: Congregation move preserves membership and roles

use App\Actions\Congregations\MoveCongregation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 10.3**
test('congregation move preserves all memberships and roles', function () {
    $kh1 = KingdomHall::factory()->create();
    $kh2 = KingdomHall::factory()->create();

    $congregation = Congregation::factory()->withKingdomHall($kh1)->create();

    // Create a random number of members (2-5) with random roles (admin or member only)
    $memberCount = rand(2, 5);
    $roles = [CongregationRole::Admin, CongregationRole::Member];

    $membershipsBeforeMove = [];

    for ($i = 0; $i < $memberCount; $i++) {
        $user = User::factory()->create();
        $role = $roles[array_rand($roles)];

        Membership::create([
            'congregation_id' => $congregation->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $membershipsBeforeMove[] = [
            'user_id' => $user->id,
            'congregation_id' => $congregation->id,
            'role' => $role->value,
        ];
    }

    // Perform the move
    $action = app(MoveCongregation::class);
    $action->handle($congregation, $kh2);

    // Verify ALL memberships still exist with the same congregation_id + user_id + role
    foreach ($membershipsBeforeMove as $expected) {
        $membership = Membership::where('congregation_id', $expected['congregation_id'])
            ->where('user_id', $expected['user_id'])
            ->first();

        expect($membership)->not->toBeNull()
            ->and($membership->role->value)->toBe($expected['role']);
    }

    // Verify the total count of memberships is preserved
    $totalMemberships = Membership::where('congregation_id', $congregation->id)->count();
    expect($totalMemberships)->toBe($memberCount);
})->repeat(100);
