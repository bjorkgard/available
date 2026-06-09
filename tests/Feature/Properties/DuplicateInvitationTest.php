<?php

// Feature: congregation-management, Property 15: Duplicate invitation replacement

use App\Actions\Congregations\SendInvitation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 8.8**

test('duplicate invitation replaces previous pending invitation for same email-congregation pair', function () {
    $admin = User::factory()->create();
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $admin->update(['current_congregation_id' => $congregation->id]);

    $email = fake()->unique()->safeEmail();

    $action = new SendInvitation;

    // Send first invitation
    $action->handle($admin, $congregation, [
        'name' => fake()->name(),
        'email' => $email,
        'role' => CongregationRole::Member->value,
    ]);

    // Send second invitation to the same email+congregation
    $action->handle($admin, $congregation, [
        'name' => fake()->name(),
        'email' => $email,
        'role' => CongregationRole::Admin->value,
    ]);

    // Verify exactly one pending (unaccepted, non-expired) invitation exists
    $pendingCount = CongregationInvitation::where('congregation_id', $congregation->id)
        ->where('email', $email)
        ->whereNull('accepted_at')
        ->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        })
        ->count();

    expect($pendingCount)->toBe(1);
})->repeat(30);
