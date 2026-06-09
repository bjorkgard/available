<?php

// Feature: congregation-management, Property 19: Exclusive user removal on entity deletion

use App\Actions\Congregations\DeleteCongregation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 11.1, 11.2, 11.3**
test('exclusive users are removed and multi-congregation users are retained on deletion', function () {
    $kingdomHall = KingdomHall::factory()->create();

    // Create 2-3 congregations in the same Kingdom Hall
    $congregationCount = rand(2, 3);
    $congregations = Congregation::factory()
        ->count($congregationCount)
        ->withKingdomHall($kingdomHall)
        ->create();

    $targetCongregation = $congregations->first();

    // Create a random number of users (3-8)
    $userCount = rand(3, 8);
    $users = User::factory()->count($userCount)->create();

    // Randomly distribute users across congregations
    // Ensure at least one user is exclusive to the target and at least one is shared
    $exclusiveUsers = collect();
    $sharedUsers = collect();

    foreach ($users as $index => $user) {
        $role = fake()->randomElement([CongregationRole::Admin, CongregationRole::Member]);

        if ($index === 0) {
            // Force first user to be exclusive to the target congregation
            Membership::create([
                'congregation_id' => $targetCongregation->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);
            $user->update(['current_congregation_id' => $targetCongregation->id]);
            $exclusiveUsers->push($user);
        } elseif ($index === 1) {
            // Force second user to be shared (in target + at least one other)
            Membership::create([
                'congregation_id' => $targetCongregation->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);

            $otherCongregation = $congregations->skip(1)->random();
            Membership::create([
                'congregation_id' => $otherCongregation->id,
                'user_id' => $user->id,
                'role' => CongregationRole::Member,
            ]);
            $user->update(['current_congregation_id' => $targetCongregation->id]);
            $sharedUsers->push($user);
        } else {
            // Randomly decide if this user is exclusive to target or shared
            $isExclusive = fake()->boolean(50);

            if ($isExclusive) {
                Membership::create([
                    'congregation_id' => $targetCongregation->id,
                    'user_id' => $user->id,
                    'role' => $role,
                ]);
                $user->update(['current_congregation_id' => $targetCongregation->id]);
                $exclusiveUsers->push($user);
            } else {
                // Add to target congregation
                Membership::create([
                    'congregation_id' => $targetCongregation->id,
                    'user_id' => $user->id,
                    'role' => $role,
                ]);

                // Also add to one or more other congregations
                $otherCongs = $congregations->skip(1)->random(rand(1, $congregations->count() - 1));
                foreach ($otherCongs as $otherCong) {
                    Membership::create([
                        'congregation_id' => $otherCong->id,
                        'user_id' => $user->id,
                        'role' => CongregationRole::Member,
                    ]);
                }
                $user->update(['current_congregation_id' => $targetCongregation->id]);
                $sharedUsers->push($user);
            }
        }
    }

    // Record exclusive and shared user IDs before deletion
    $exclusiveUserIds = $exclusiveUsers->pluck('id')->toArray();
    $sharedUserIds = $sharedUsers->pluck('id')->toArray();

    // Perform deletion
    $action = new DeleteCongregation;
    $action->handle($targetCongregation);

    // Verify: exclusive users are removed from the system
    foreach ($exclusiveUserIds as $userId) {
        expect(User::find($userId))->toBeNull(
            "Exclusive user {$userId} should be deleted from the system"
        );
    }

    // Verify: multi-congregation users are retained
    foreach ($sharedUserIds as $userId) {
        $user = User::find($userId);
        expect($user)->not->toBeNull(
            "Shared user {$userId} should be retained in the system"
        );
        // Their current_congregation_id should point to an active congregation
        expect($user->current_congregation_id)->not->toBe($targetCongregation->id);
    }

    // Verify: the congregation is soft-deleted
    expect($targetCongregation->fresh()->trashed())->toBeTrue();
})->repeat(100);
