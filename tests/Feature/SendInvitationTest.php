<?php

use App\Actions\Congregations\SendInvitation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new SendInvitation;
    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id]);
    $this->inviter = User::factory()->create();
    $this->congregation->memberships()->create([
        'user_id' => $this->inviter->id,
        'role' => CongregationRole::Admin,
    ]);
});

test('it creates an invitation for a new user with 72-hour expiry', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => CongregationRole::Member->value,
    ];

    $invitation = $this->action->handle($this->inviter, $this->congregation, $data);

    expect($invitation)
        ->toBeInstanceOf(CongregationInvitation::class)
        ->congregation_id->toBe($this->congregation->id)
        ->name->toBe('John Doe')
        ->email->toBe('john@example.com')
        ->role->toBe(CongregationRole::Member)
        ->invited_by->toBe($this->inviter->id)
        ->accepted_at->toBeNull();

    expect($invitation->code)->toHaveLength(64);
    expect(now()->diffInHours($invitation->expires_at))->toBeBetween(71, 72);
});

test('it adds existing user directly to congregation with invited role', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $data = [
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'role' => CongregationRole::Admin->value,
    ];

    $invitation = $this->action->handle($this->inviter, $this->congregation, $data);

    // Invitation should be marked as accepted immediately
    expect($invitation->accepted_at)->not->toBeNull();

    // User should have membership in the congregation
    $membership = Membership::where('user_id', $existingUser->id)
        ->where('congregation_id', $this->congregation->id)
        ->first();

    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe(CongregationRole::Admin);
});

test('it replaces existing pending invitation for same email and congregation', function () {
    // Create an existing pending invitation
    $existingInvitation = CongregationInvitation::factory()->create([
        'congregation_id' => $this->congregation->id,
        'email' => 'john@example.com',
        'role' => CongregationRole::Member,
        'invited_by' => $this->inviter->id,
        'expires_at' => now()->addHours(48),
    ]);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => CongregationRole::Admin->value,
    ];

    $newInvitation = $this->action->handle($this->inviter, $this->congregation, $data);

    // Old invitation should be deleted
    expect(CongregationInvitation::find($existingInvitation->id))->toBeNull();

    // New invitation should exist with updated role
    expect($newInvitation->role)->toBe(CongregationRole::Admin);

    // Only one pending invitation for this email+congregation
    $pendingCount = CongregationInvitation::where('congregation_id', $this->congregation->id)
        ->where('email', 'john@example.com')
        ->whereNull('accepted_at')
        ->count();

    expect($pendingCount)->toBe(1);
});

test('it does not replace expired invitations', function () {
    // Create an expired invitation
    $expiredInvitation = CongregationInvitation::factory()->expired()->create([
        'congregation_id' => $this->congregation->id,
        'email' => 'john@example.com',
        'role' => CongregationRole::Member,
        'invited_by' => $this->inviter->id,
    ]);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => CongregationRole::Admin->value,
    ];

    $this->action->handle($this->inviter, $this->congregation, $data);

    // Expired invitation should still exist
    expect(CongregationInvitation::find($expiredInvitation->id))->not->toBeNull();

    // Should now have two invitations (one expired, one new)
    $totalCount = CongregationInvitation::where('congregation_id', $this->congregation->id)
        ->where('email', 'john@example.com')
        ->count();

    expect($totalCount)->toBe(2);
});

test('it does not replace accepted invitations', function () {
    // Create an accepted invitation
    $acceptedInvitation = CongregationInvitation::factory()->accepted()->create([
        'congregation_id' => $this->congregation->id,
        'email' => 'john@example.com',
        'role' => CongregationRole::Member,
        'invited_by' => $this->inviter->id,
    ]);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => CongregationRole::Admin->value,
    ];

    $this->action->handle($this->inviter, $this->congregation, $data);

    // Accepted invitation should still exist
    expect(CongregationInvitation::find($acceptedInvitation->id))->not->toBeNull();
});

test('it validates required fields', function () {
    $this->action->handle($this->inviter, $this->congregation, []);
})->throws(ValidationException::class);

test('it validates email format', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'not-an-email',
        'role' => CongregationRole::Member->value,
    ];

    $this->action->handle($this->inviter, $this->congregation, $data);
})->throws(ValidationException::class);

test('it validates role is a valid enum value', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'invalid-role',
    ];

    $this->action->handle($this->inviter, $this->congregation, $data);
})->throws(ValidationException::class);

test('it does not duplicate membership for existing user already in congregation', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    // User is already a member
    $this->congregation->memberships()->create([
        'user_id' => $existingUser->id,
        'role' => CongregationRole::Member,
    ]);

    $data = [
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'role' => CongregationRole::Admin->value,
    ];

    $invitation = $this->action->handle($this->inviter, $this->congregation, $data);

    // Should not create a duplicate membership
    $membershipCount = Membership::where('user_id', $existingUser->id)
        ->where('congregation_id', $this->congregation->id)
        ->count();

    expect($membershipCount)->toBe(1);

    // Invitation is still created and marked as accepted
    expect($invitation->accepted_at)->not->toBeNull();
});

test('admin inviter cannot assign superadmin role', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $admin = User::factory()->create();
    $congregation->memberships()->create([
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $data = [
        'name' => 'New Superadmin',
        'email' => 'superadmin@example.com',
        'role' => CongregationRole::Superadmin->value,
    ];

    $this->action->handle($admin, $congregation, $data);
})->throws(ValidationException::class);

test('superadmin inviter can assign superadmin role', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $superadmin = User::factory()->create();
    $congregation->memberships()->create([
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $data = [
        'name' => 'New Superadmin',
        'email' => 'newsuperadmin@example.com',
        'role' => CongregationRole::Superadmin->value,
    ];

    $invitation = $this->action->handle($superadmin, $congregation, $data);

    expect($invitation->role)->toBe(CongregationRole::Superadmin);
});

test('admin inviter can assign admin role', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $admin = User::factory()->create();
    $congregation->memberships()->create([
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $data = [
        'name' => 'New Admin',
        'email' => 'newadmin@example.com',
        'role' => CongregationRole::Admin->value,
    ];

    $invitation = $this->action->handle($admin, $congregation, $data);

    expect($invitation->role)->toBe(CongregationRole::Admin);
});

test('member inviter cannot assign any role', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $member = User::factory()->create();
    $congregation->memberships()->create([
        'user_id' => $member->id,
        'role' => CongregationRole::Member,
    ]);

    $data = [
        'name' => 'Someone',
        'email' => 'someone@example.com',
        'role' => CongregationRole::Member->value,
    ];

    $this->action->handle($member, $congregation, $data);
})->throws(ValidationException::class);
