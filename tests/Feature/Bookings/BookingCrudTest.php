<?php

use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->kingdomHall = KingdomHall::factory()->create();

    $this->congregationA = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();
    $this->congregationB = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();

    $this->room = Room::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id]);

    // Member in congregation A
    $this->member = User::factory()->create();
    $this->congregationA->members()->attach($this->member, ['role' => CongregationRole::Member->value]);
    $this->member->update(['current_congregation_id' => $this->congregationA->id]);

    // Admin in congregation A
    $this->admin = User::factory()->create();
    $this->congregationA->members()->attach($this->admin, ['role' => CongregationRole::Admin->value]);
    $this->admin->update(['current_congregation_id' => $this->congregationA->id]);

    // Superadmin in congregation A (also member of B for cross-hall access)
    $this->superadmin = User::factory()->create();
    $this->congregationA->members()->attach($this->superadmin, ['role' => CongregationRole::Superadmin->value]);
    $this->congregationB->members()->attach($this->superadmin, ['role' => CongregationRole::Superadmin->value]);
    $this->superadmin->update(['current_congregation_id' => $this->congregationA->id]);

    // Another member in congregation A (to test cross-user restrictions)
    $this->otherMember = User::factory()->create();
    $this->congregationA->members()->attach($this->otherMember, ['role' => CongregationRole::Member->value]);
    $this->otherMember->update(['current_congregation_id' => $this->congregationA->id]);

    // Member in congregation B (to test cross-congregation restrictions)
    $this->memberB = User::factory()->create();
    $this->congregationB->members()->attach($this->memberB, ['role' => CongregationRole::Member->value]);
    $this->memberB->update(['current_congregation_id' => $this->congregationB->id]);
});

// --- Store (Create) ---

test('member can create a booking', function () {
    $response = $this->actingAs($this->member)
        ->postJson(route('bookings.store', ['current_congregation' => $this->congregationA]), [
            'name' => 'Team Meeting',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:30:00',
            'room_ids' => [$this->room->id],
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.0.name', 'Team Meeting');
});

test('admin can create a booking', function () {
    $response = $this->actingAs($this->admin)
        ->postJson(route('bookings.store', ['current_congregation' => $this->congregationA]), [
            'name' => 'Admin Meeting',
            'starts_at' => '2025-06-10 14:00:00',
            'ends_at' => '2025-06-10 15:00:00',
            'room_ids' => [$this->room->id],
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.0.name', 'Admin Meeting');
});

test('superadmin can create a booking', function () {
    $response = $this->actingAs($this->superadmin)
        ->postJson(route('bookings.store', ['current_congregation' => $this->congregationA]), [
            'name' => 'Superadmin Event',
            'starts_at' => '2025-06-10 16:00:00',
            'ends_at' => '2025-06-10 17:00:00',
            'room_ids' => [$this->room->id],
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('data.0.name', 'Superadmin Event');
});

// --- Update ---

test('member can update their own booking', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationA->id,
        'user_id' => $this->member->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->member)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'name' => 'Updated Name',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:30:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ]);

    $response->assertOk();
    $response->assertJsonPath('data.0.name', 'Updated Name');
});

test('member cannot update another members booking', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationA->id,
        'user_id' => $this->otherMember->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->member)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'name' => 'Hijacked Name',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:00:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ]);

    $response->assertForbidden();
});

test('admin can update any booking in their congregation', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationA->id,
        'user_id' => $this->member->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->admin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'name' => 'Admin Override',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:30:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ]);

    $response->assertOk();
    $response->assertJsonPath('data.0.name', 'Admin Override');
});

test('admin cannot update a booking from another congregation', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationB->id,
        'user_id' => $this->memberB->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    // Admin of A tries to update booking in congregation B
    $response = $this->actingAs($this->admin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'name' => 'Cross-Congregation Hack',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:00:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ]);

    $response->assertForbidden();
});

test('superadmin can update any booking in the same kingdom hall', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationB->id,
        'user_id' => $this->memberB->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->superadmin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'name' => 'Superadmin Override',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:30:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ]);

    $response->assertOk();
    $response->assertJsonPath('data.0.name', 'Superadmin Override');
});

// --- Delete ---

test('member can delete their own booking', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationA->id,
        'user_id' => $this->member->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->member)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'scope' => 'all',
        ]);

    $response->assertStatus(204);
    expect(Booking::find($booking->id))->toBeNull();
});

test('member cannot delete another members booking', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationA->id,
        'user_id' => $this->otherMember->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->member)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'scope' => 'all',
        ]);

    $response->assertForbidden();
    expect(Booking::find($booking->id))->not->toBeNull();
});

test('admin can delete any booking in their congregation', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationA->id,
        'user_id' => $this->member->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->admin)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'scope' => 'all',
        ]);

    $response->assertStatus(204);
    expect(Booking::find($booking->id))->toBeNull();
});

test('admin cannot delete a booking from another congregation', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationB->id,
        'user_id' => $this->memberB->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->admin)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'scope' => 'all',
        ]);

    $response->assertForbidden();
    expect(Booking::find($booking->id))->not->toBeNull();
});

test('superadmin can delete any booking in the same kingdom hall', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregationB->id,
        'user_id' => $this->memberB->id,
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $response = $this->actingAs($this->superadmin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'name' => 'check permissions first',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 11:00:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ]);

    // Superadmin can also delete
    $response = $this->actingAs($this->superadmin)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregationA, 'booking' => $booking]), [
            'scope' => 'all',
        ]);

    $response->assertStatus(204);
    expect(Booking::find($booking->id))->toBeNull();
});
