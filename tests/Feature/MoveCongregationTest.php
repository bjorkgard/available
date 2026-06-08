<?php

use App\Actions\Congregations\MoveCongregation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new MoveCongregation;
});

test('it moves a congregation to a different kingdom hall', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $result = $this->action->handle($congregation, $targetKh);

    expect($result->kingdom_hall_id)->toBe($targetKh->id);
});

test('it rejects move when target is the same as current kingdom hall', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $this->action->handle($congregation, $kh);
})->throws(ValidationException::class);

test('it rejects move when congregation has no current kingdom hall', function () {
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => null]);

    $this->action->handle($congregation, $targetKh);
})->throws(ValidationException::class);

test('it revokes superadmin role for users whose only congregation in original kh was the moved one', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $user = User::factory()->create();
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $this->action->handle($congregation, $targetKh);

    $membership = Membership::where('user_id', $user->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    expect($membership->role)->toBe(CongregationRole::Admin);
});

test('it preserves superadmin role for users who have another congregation in original kh', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);
    $otherCongregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $user = User::factory()->create();
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Superadmin,
    ]);
    Membership::create([
        'congregation_id' => $otherCongregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Admin,
    ]);

    $this->action->handle($congregation, $targetKh);

    $membership = Membership::where('user_id', $user->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    expect($membership->role)->toBe(CongregationRole::Superadmin);
});

test('it preserves all memberships and congregation-scoped roles for non-superadmin users', function () {
    $originalKh = KingdomHall::factory()->create();
    $targetKh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $originalKh->id]);

    $admin = User::factory()->create();
    $member = User::factory()->create();

    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $member->id,
        'role' => CongregationRole::Member,
    ]);

    $this->action->handle($congregation, $targetKh);

    $adminMembership = Membership::where('user_id', $admin->id)
        ->where('congregation_id', $congregation->id)
        ->first();
    $memberMembership = Membership::where('user_id', $member->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    expect($adminMembership->role)->toBe(CongregationRole::Admin)
        ->and($memberMembership->role)->toBe(CongregationRole::Member);
});
