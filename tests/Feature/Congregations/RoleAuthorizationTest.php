<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->kingdomHall = KingdomHall::factory()->create();

    $this->congregationA = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
    $this->congregationB = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();

    // Superadmin in congregation A
    $this->superadmin = User::factory()->create();
    $this->congregationA->members()->attach($this->superadmin, ['role' => CongregationRole::Superadmin->value]);
    $this->superadmin->update(['current_congregation_id' => $this->congregationA->id]);

    // Admin in congregation A
    $this->admin = User::factory()->create();
    $this->congregationA->members()->attach($this->admin, ['role' => CongregationRole::Admin->value]);
    $this->admin->update(['current_congregation_id' => $this->congregationA->id]);

    // Member in congregation A
    $this->member = User::factory()->create();
    $this->congregationA->members()->attach($this->member, ['role' => CongregationRole::Member->value]);
    $this->member->update(['current_congregation_id' => $this->congregationA->id]);

    // Admin in congregation B (to test cross-congregation access)
    $this->adminB = User::factory()->create();
    $this->congregationB->members()->attach($this->adminB, ['role' => CongregationRole::Admin->value]);
    $this->adminB->update(['current_congregation_id' => $this->congregationB->id]);
});

// --- Superadmin access across Kingdom Hall congregations ---

test('superadmin can access members page of any congregation in KH', function () {
    // Add superadmin to congregation B so the membership middleware allows access
    $this->congregationB->members()->attach($this->superadmin, ['role' => CongregationRole::Superadmin->value]);

    $response = $this->actingAs($this->superadmin)
        ->get(route('members.index', ['current_congregation' => $this->congregationB]));

    $response->assertOk();
});

test('superadmin can manage members across KH congregations', function () {
    // Superadmin should be able to update a member in congregation B
    $this->congregationB->members()->attach($this->superadmin, ['role' => CongregationRole::Superadmin->value]);

    $memberInB = User::factory()->create();
    $this->congregationB->members()->attach($memberInB, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $memberInB->id)
        ->where('congregation_id', $this->congregationB->id)
        ->first();

    $response = $this->actingAs($this->superadmin)
        ->put(route('members.update', ['current_congregation' => $this->congregationB, 'member' => $membership]), [
            'role' => CongregationRole::Admin->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($membership->fresh()->role)->toBe(CongregationRole::Admin);
});

// --- Admin restricted to own congregation ---

test('admin can access members page of own congregation only', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('members.index', ['current_congregation' => $this->congregationA]));

    $response->assertOk();
});

test('admin cannot access members page of other congregation', function () {
    // Admin of A tries to access congregation B — not a member, so middleware blocks
    $response = $this->actingAs($this->admin)
        ->get(route('members.index', ['current_congregation' => $this->congregationB]));

    $response->assertForbidden();
});

// --- Member management action restrictions ---

test('member gets 403 when trying to invite', function () {
    $response = $this->actingAs($this->member)
        ->post(route('members.invite', ['current_congregation' => $this->congregationA]), [
            'name' => 'New Person',
            'email' => 'newperson@example.com',
            'role' => CongregationRole::Member->value,
        ]);

    $response->assertForbidden();
});

test('member gets 403 when trying to update member role', function () {
    $otherMember = User::factory()->create();
    $this->congregationA->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->member)
        ->put(route('members.update', ['current_congregation' => $this->congregationA, 'member' => $membership]), [
            'role' => CongregationRole::Admin->value,
        ]);

    $response->assertForbidden();
});

test('member gets 403 when trying to remove member', function () {
    $otherMember = User::factory()->create();
    $this->congregationA->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->member)
        ->delete(route('members.destroy', ['current_congregation' => $this->congregationA, 'member' => $membership]));

    $response->assertForbidden();
});

// --- Last admin removal prevented ---

test('last admin cannot be removed from congregation', function () {
    // Create a congregation with only one admin
    $lonelyKH = KingdomHall::factory()->create();
    $lonelyCongregation = Congregation::factory()->withKingdomHall($lonelyKH)->create();

    $soleAdmin = User::factory()->create();
    $lonelyCongregation->members()->attach($soleAdmin, ['role' => CongregationRole::Admin->value]);
    $soleAdmin->update(['current_congregation_id' => $lonelyCongregation->id]);

    // Create a superadmin in another congregation of the same KH
    $otherCongregation = Congregation::factory()->withKingdomHall($lonelyKH)->create();
    $khSuperadmin = User::factory()->create();
    $otherCongregation->members()->attach($khSuperadmin, ['role' => CongregationRole::Superadmin->value]);
    $khSuperadmin->update(['current_congregation_id' => $otherCongregation->id]);

    // Add superadmin as a regular member of the lonely congregation so middleware allows access
    // (member role means they don't count toward admin count)
    $lonelyCongregation->members()->attach($khSuperadmin, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $soleAdmin->id)
        ->where('congregation_id', $lonelyCongregation->id)
        ->first();

    $response = $this->actingAs($khSuperadmin)
        ->delete(route('members.destroy', ['current_congregation' => $lonelyCongregation, 'member' => $membership]));

    $response->assertForbidden();

    // The membership should still exist
    expect(Membership::find($membership->id))->not->toBeNull();
});

// --- Last admin self-demotion prevented ---

test('last admin cannot demote themselves', function () {
    // The policy prevents users from updating their own membership
    $membership = Membership::where('user_id', $this->admin->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->admin)
        ->put(route('members.update', ['current_congregation' => $this->congregationA, 'member' => $membership]), [
            'role' => CongregationRole::Member->value,
        ]);

    $response->assertForbidden();

    // Role should remain admin
    expect($membership->fresh()->role)->toBe(CongregationRole::Admin);
});

// --- Role assignment escalation prevention ---

test('admin cannot promote a member to superadmin', function () {
    $otherMember = User::factory()->create();
    $this->congregationA->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->admin)
        ->put(route('members.update', ['current_congregation' => $this->congregationA, 'member' => $membership]), [
            'role' => CongregationRole::Superadmin->value,
        ]);

    $response->assertForbidden();

    // Role should remain member
    expect($membership->fresh()->role)->toBe(CongregationRole::Member);
});

test('admin can promote a member to admin', function () {
    $otherMember = User::factory()->create();
    $this->congregationA->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->admin)
        ->put(route('members.update', ['current_congregation' => $this->congregationA, 'member' => $membership]), [
            'role' => CongregationRole::Admin->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($membership->fresh()->role)->toBe(CongregationRole::Admin);
});

test('superadmin can promote a member to superadmin', function () {
    $otherMember = User::factory()->create();
    $this->congregationA->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->superadmin)
        ->put(route('members.update', ['current_congregation' => $this->congregationA, 'member' => $membership]), [
            'role' => CongregationRole::Superadmin->value,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($membership->fresh()->role)->toBe(CongregationRole::Superadmin);
});

test('update rejects invalid role value', function () {
    $otherMember = User::factory()->create();
    $this->congregationA->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)
        ->where('congregation_id', $this->congregationA->id)
        ->first();

    $response = $this->actingAs($this->admin)
        ->put(route('members.update', ['current_congregation' => $this->congregationA, 'member' => $membership]), [
            'role' => 'invented-role',
        ]);

    $response->assertSessionHasErrors('role');

    // Role should remain member
    expect($membership->fresh()->role)->toBe(CongregationRole::Member);
});
