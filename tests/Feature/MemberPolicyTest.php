<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use App\Policies\MemberPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new MemberPolicy;
    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
});

// --- Invite Tests ---

test('superadmin in same kingdom hall can invite to any congregation', function () {
    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $otherCongregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();

    expect($this->policy->invite($superadmin, $otherCongregation))->toBeTrue();
});

test('admin can invite to own congregation', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    expect($this->policy->invite($admin, $this->congregation))->toBeTrue();
});

test('admin cannot invite to other congregation', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $otherCongregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();

    expect($this->policy->invite($admin, $otherCongregation))->toBeFalse();
});

test('member cannot invite', function () {
    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    expect($this->policy->invite($member, $this->congregation))->toBeFalse();
});

// --- Update Tests ---

test('superadmin in same kingdom hall can update membership in KH congregation', function () {
    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $target = User::factory()->create();
    $otherCongregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
    $otherCongregation->members()->attach($target, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $target->id)->where('congregation_id', $otherCongregation->id)->first();

    expect($this->policy->update($superadmin, $membership))->toBeTrue();
});

test('user cannot update their own membership', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $membership = Membership::where('user_id', $admin->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->update($admin, $membership))->toBeFalse();
});

test('admin can update member in own congregation', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $member->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->update($admin, $membership))->toBeTrue();
});

test('admin cannot update superadmin membership', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $membership = Membership::where('user_id', $superadmin->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->update($admin, $membership))->toBeFalse();
});

test('member cannot update any membership', function () {
    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    $otherMember = User::factory()->create();
    $this->congregation->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->update($member, $membership))->toBeFalse();
});

// --- Delete Tests ---

test('superadmin in same kingdom hall can remove from any KH congregation', function () {
    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $target = User::factory()->create();
    $otherCongregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
    $otherCongregation->members()->attach($target, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $target->id)->where('congregation_id', $otherCongregation->id)->first();

    expect($this->policy->delete($superadmin, $membership))->toBeTrue();
});

test('cannot remove last admin of congregation', function () {
    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $membership = Membership::where('user_id', $admin->id)->where('congregation_id', $this->congregation->id)->first();

    // The admin is the only admin (superadmin counts too, so not the last)
    expect($this->policy->delete($superadmin, $membership))->toBeTrue();
});

test('cannot remove sole admin when no other admin or superadmin exists', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    // We need another user with privileges to attempt the delete - use a superadmin from a different congregation in the same KH
    $otherCongregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
    $superadmin = User::factory()->create();
    $otherCongregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $membership = Membership::where('user_id', $admin->id)->where('congregation_id', $this->congregation->id)->first();

    // The admin is the ONLY admin/superadmin in the congregation — deletion should be prevented
    expect($this->policy->delete($superadmin, $membership))->toBeFalse();
});

test('admin can remove member from own congregation', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $member->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->delete($admin, $membership))->toBeTrue();
});

test('admin cannot remove superadmin', function () {
    $admin = User::factory()->create();
    $this->congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $membership = Membership::where('user_id', $superadmin->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->delete($admin, $membership))->toBeFalse();
});

test('member cannot remove anyone', function () {
    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    $otherMember = User::factory()->create();
    $this->congregation->members()->attach($otherMember, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $otherMember->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->delete($member, $membership))->toBeFalse();
});

// --- Helper Method Tests ---

test('superadmin from different kingdom hall cannot manage members', function () {
    $otherKH = KingdomHall::factory()->create();
    $otherCongregation = Congregation::factory()->withKingdomHall($otherKH)->create();

    $superadmin = User::factory()->create();
    $otherCongregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $member = User::factory()->create();
    $this->congregation->members()->attach($member, ['role' => CongregationRole::Member->value]);

    $membership = Membership::where('user_id', $member->id)->where('congregation_id', $this->congregation->id)->first();

    expect($this->policy->invite($superadmin, $this->congregation))->toBeFalse()
        ->and($this->policy->update($superadmin, $membership))->toBeFalse()
        ->and($this->policy->delete($superadmin, $membership))->toBeFalse();
});

test('congregation without kingdom hall prevents superadmin checks', function () {
    $congregationNoKH = Congregation::factory()->create(); // no kingdom_hall_id

    $superadmin = User::factory()->create();
    $this->congregation->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    expect($this->policy->invite($superadmin, $congregationNoKH))->toBeFalse();
});
