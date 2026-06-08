<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createKingdomHallWithCongregation(CongregationRole $role = CongregationRole::Admin): array
{
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $user = User::factory()->create(['current_congregation_id' => $congregation->id]);

    $congregation->members()->attach($user, ['role' => $role->value]);

    return [$kingdomHall, $congregation, $user];
}

test('admin can invite to own congregation', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);

    $response = $this->actingAs($admin)->post(
        route('members.invite', ['current_congregation' => $congregation->slug]),
        [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'role' => CongregationRole::Member->value,
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('congregation_invitations', [
        'congregation_id' => $congregation->id,
        'email' => 'newmember@example.com',
        'role' => CongregationRole::Member->value,
    ]);
});

test('existing user is added directly to congregation on invitation', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->actingAs($admin)->post(
        route('members.invite', ['current_congregation' => $congregation->slug]),
        [
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'role' => CongregationRole::Admin->value,
        ]
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('congregation_members', [
        'congregation_id' => $congregation->id,
        'user_id' => $existingUser->id,
        'role' => CongregationRole::Admin->value,
    ]);

    $invitation = CongregationInvitation::where('email', 'existing@example.com')
        ->where('congregation_id', $congregation->id)
        ->first();

    expect($invitation->accepted_at)->not->toBeNull();
});

test('expired invitation cannot be accepted', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);
    $user = User::factory()->create();

    $invitation = CongregationInvitation::factory()->expired()->create([
        'congregation_id' => $congregation->id,
        'invited_by' => $admin->id,
    ]);

    $response = $this->actingAs($user)->get(
        route('invitations.accept', ['invitation' => $invitation->code])
    );

    $response->assertStatus(410);
});

test('duplicate invitation replaces previous pending one', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);

    $firstInvitation = CongregationInvitation::factory()->create([
        'congregation_id' => $congregation->id,
        'email' => 'john@example.com',
        'role' => CongregationRole::Member,
        'invited_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->post(
        route('members.invite', ['current_congregation' => $congregation->slug]),
        [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => CongregationRole::Admin->value,
        ]
    );

    $response->assertRedirect();

    $this->assertDatabaseMissing('congregation_invitations', [
        'id' => $firstInvitation->id,
    ]);

    $pendingCount = CongregationInvitation::where('congregation_id', $congregation->id)
        ->where('email', 'john@example.com')
        ->whereNull('accepted_at')
        ->count();

    expect($pendingCount)->toBe(1);
});

test('admin cannot invite to another congregation', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);
    $otherCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);

    $response = $this->actingAs($admin)->post(
        route('members.invite', ['current_congregation' => $otherCongregation->slug]),
        [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'role' => CongregationRole::Member->value,
        ]
    );

    $response->assertStatus(403);
});

test('superadmin can invite to any congregation in kingdom hall', function () {
    [$kingdomHall, $congregation, $superadmin] = createKingdomHallWithCongregation(CongregationRole::Superadmin);
    $otherCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);

    // Superadmin needs to be a member of the other congregation for the membership middleware to allow access
    $otherCongregation->members()->attach($superadmin, ['role' => CongregationRole::Member->value]);

    $response = $this->actingAs($superadmin)->post(
        route('members.invite', ['current_congregation' => $otherCongregation->slug]),
        [
            'name' => 'New Person',
            'email' => 'newperson@example.com',
            'role' => CongregationRole::Member->value,
        ]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('congregation_invitations', [
        'congregation_id' => $otherCongregation->id,
        'email' => 'newperson@example.com',
    ]);
});

test('member cannot send invitations', function () {
    [$kingdomHall, $congregation, $member] = createKingdomHallWithCongregation(CongregationRole::Member);

    $response = $this->actingAs($member)->post(
        route('members.invite', ['current_congregation' => $congregation->slug]),
        [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'role' => CongregationRole::Member->value,
        ]
    );

    $response->assertStatus(403);
});

test('valid invitation can be accepted', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);
    $user = User::factory()->create();

    $invitation = CongregationInvitation::factory()->create([
        'congregation_id' => $congregation->id,
        'email' => $user->email,
        'role' => CongregationRole::Member,
        'invited_by' => $admin->id,
    ]);

    $response = $this->actingAs($user)->get(
        route('invitations.accept', ['invitation' => $invitation->code])
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('congregation_members', [
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Member->value,
    ]);

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('invitation cannot be accepted by a different user', function () {
    [$kingdomHall, $congregation, $admin] = createKingdomHallWithCongregation(CongregationRole::Admin);

    $intendedUser = User::factory()->create(['email' => 'intended@example.com']);
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);

    $invitation = CongregationInvitation::factory()->create([
        'congregation_id' => $congregation->id,
        'email' => 'intended@example.com',
        'role' => CongregationRole::Member,
        'invited_by' => $admin->id,
    ]);

    $response = $this->actingAs($wrongUser)->get(
        route('invitations.accept', ['invitation' => $invitation->code])
    );

    $response->assertForbidden();

    // Wrong user should not have been added to the congregation
    $this->assertDatabaseMissing('congregation_members', [
        'congregation_id' => $congregation->id,
        'user_id' => $wrongUser->id,
    ]);

    // Invitation should remain unconsumed
    expect($invitation->fresh()->accepted_at)->toBeNull();
});
