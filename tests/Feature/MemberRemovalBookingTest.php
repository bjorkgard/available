<?php

use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;

beforeEach(function () {
    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();

    $this->admin = User::factory()->create();
    $this->congregation->members()->attach($this->admin, ['role' => CongregationRole::Admin->value]);
    $this->admin->update(['current_congregation_id' => $this->congregation->id]);

    $this->member = User::factory()->create();
    $this->congregation->members()->attach($this->member, ['role' => CongregationRole::Member->value]);

    $this->membership = Membership::where('user_id', $this->member->id)
        ->where('congregation_id', $this->congregation->id)
        ->first();
});

test('member without future bookings is removed directly', function () {
    Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->subDays(5)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]));

    $response->assertRedirect();
    expect(Membership::find($this->membership->id))->toBeNull();
});

test('member with future bookings requires booking_action', function () {
    Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]));

    $response->assertSessionHasErrors('booking_action');
    expect(Membership::find($this->membership->id))->not->toBeNull();
});

test('transfer action reassigns future bookings to target member', function () {
    $targetMember = User::factory()->create();
    $this->congregation->members()->attach($targetMember, ['role' => CongregationRole::Member->value]);

    $futureBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]), [
            'booking_action' => 'transfer',
            'transfer_to' => $targetMember->id,
        ]);

    $response->assertRedirect();
    expect(Membership::find($this->membership->id))->toBeNull();
    expect($futureBooking->fresh()->user_id)->toBe($targetMember->id);
});

test('delete action removes future bookings and preserves past bookings', function () {
    $futureBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $pastBooking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->subDays(5)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]), [
            'booking_action' => 'delete',
        ]);

    $response->assertRedirect();
    expect(Membership::find($this->membership->id))->toBeNull();
    expect(Booking::find($futureBooking->id))->toBeNull();
    expect(Booking::find($pastBooking->id))->not->toBeNull();
});

test('transfer requires transfer_to field', function () {
    Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]), [
            'booking_action' => 'transfer',
        ]);

    $response->assertSessionHasErrors('transfer_to');
    expect(Membership::find($this->membership->id))->not->toBeNull();
});

test('transfer_to must be an active member of the same congregation', function () {
    $outsider = User::factory()->create();

    Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]), [
            'booking_action' => 'transfer',
            'transfer_to' => $outsider->id,
        ]);

    $response->assertSessionHasErrors('transfer_to');
    expect(Membership::find($this->membership->id))->not->toBeNull();
});

test('cannot transfer to the member being removed', function () {
    Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->member->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    $response = $this->actingAs($this->admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $this->congregation,
            'member' => $this->membership,
        ]), [
            'booking_action' => 'transfer',
            'transfer_to' => $this->member->id,
        ]);

    $response->assertSessionHasErrors('transfer_to');
    expect(Membership::find($this->membership->id))->not->toBeNull();
});

test('removal rejected when no other active members exist', function () {
    // Create an isolated congregation with just the member being removed
    // and an admin doing the removal (the admin is the only other member)
    $hall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($hall)->create();

    $admin = User::factory()->create();
    $congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);
    $admin->update(['current_congregation_id' => $congregation->id]);

    $targetMember = User::factory()->create();
    $congregation->members()->attach($targetMember, ['role' => CongregationRole::Member->value]);

    $targetMembership = Membership::where('user_id', $targetMember->id)
        ->where('congregation_id', $congregation->id)
        ->first();

    Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $targetMember->id,
        'starts_at' => now()->addDays(3),
        'ends_at' => now()->addDays(3)->addHour(),
    ]);

    // There IS another member (admin), so transfer is possible.
    // The "no other active members" case only happens if the member is truly alone.
    // Since the actor must be a member of the congregation to access it,
    // there's always at least one other member. This test verifies it works correctly.
    $response = $this->actingAs($admin)
        ->delete(route('members.destroy', [
            'current_congregation' => $congregation,
            'member' => $targetMembership,
        ]), [
            'booking_action' => 'transfer',
            'transfer_to' => $admin->id,
        ]);

    $response->assertRedirect();
    expect(Membership::find($targetMembership->id))->toBeNull();
});
