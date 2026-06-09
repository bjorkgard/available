<?php

// Feature: congregation-management, Property 13: Invitation expiry enforcement

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Validates: Requirements 8.1, 8.7**
 *
 * For any invitation, the system SHALL set the expiry to exactly 72 hours from creation.
 * For any attempt to accept an invitation after its expiry timestamp, the system SHALL
 * reject the acceptance. If already accepted, the system SHALL also reject.
 */
test('invitation expiry is enforced based on expires_at timestamp', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $inviter = User::factory()->create();
    $congregation->members()->attach($inviter, ['role' => CongregationRole::Admin->value]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);
    $user->switchCongregation($congregation);

    $scenario = generateExpiryScenario();

    $invitation = CongregationInvitation::factory()->create([
        'congregation_id' => $congregation->id,
        'email' => $user->email,
        'role' => CongregationRole::Member,
        'invited_by' => $inviter->id,
        'expires_at' => $scenario['expires_at'],
        'accepted_at' => $scenario['accepted_at'],
    ]);

    $response = $this->actingAs($user)->get(route('invitations.accept', ['invitation' => $invitation->code]));

    if ($scenario['accepted_at'] !== null) {
        // Already accepted → 410
        expect($response->status())->toBe(410, 'Already accepted invitations should return 410');
    } elseif ($scenario['expires_at'] !== null && $scenario['expires_at']->isPast()) {
        // Expired → 410
        expect($response->status())->toBe(410, 'Expired invitations should return 410');
    } else {
        // Valid (future expiry or null expires_at) → redirect (302)
        expect($response->status())->toBe(302, 'Valid invitations should redirect on acceptance');
    }
})->repeat(30);

/**
 * Generates a random invitation expiry scenario.
 * Covers: expired (past), valid (future), null expiry, and already accepted.
 *
 * @return array{expires_at: Carbon|null, accepted_at: Carbon|null}
 */
function generateExpiryScenario(): array
{
    $strategy = fake()->numberBetween(1, 4);

    return match ($strategy) {
        // Expired: random past timestamp (1 minute to 30 days ago)
        1 => [
            'expires_at' => now()->subMinutes(fake()->numberBetween(1, 43200)),
            'accepted_at' => null,
        ],
        // Valid: random future timestamp (1 minute to 72 hours from now)
        2 => [
            'expires_at' => now()->addMinutes(fake()->numberBetween(1, 4320)),
            'accepted_at' => null,
        ],
        // Null expiry (no expiration set) → should succeed
        3 => [
            'expires_at' => null,
            'accepted_at' => null,
        ],
        // Already accepted → should be rejected
        4 => [
            'expires_at' => now()->addHours(72),
            'accepted_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ],
    };
}
