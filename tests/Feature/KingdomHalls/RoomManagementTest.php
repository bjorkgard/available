<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->kingdomHall = KingdomHall::factory()->create(['number_of_rooms' => 0]);
    $this->congregation = Congregation::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id]);
    $this->superadmin = User::factory()->create(['current_congregation_id' => $this->congregation->id]);
    Membership::create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);
});

test('superadmin can create a room', function () {
    $response = $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => 'Main Hall',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('rooms', [
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Main Hall',
    ]);
});

test('room creation assigns correct sort_order', function () {
    Room::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id, 'sort_order' => 3]);
    Room::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id, 'sort_order' => 7]);

    $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => 'New Room',
        ]);

    $newRoom = Room::where('kingdom_hall_id', $this->kingdomHall->id)->where('name', 'New Room')->first();

    expect($newRoom->sort_order)->toBe(8);
});

test('room creation syncs number_of_rooms', function () {
    Room::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id, 'sort_order' => 1]);
    $this->kingdomHall->update(['number_of_rooms' => 1]);

    $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => 'Second Room',
        ]);

    $this->kingdomHall->refresh();

    expect($this->kingdomHall->number_of_rooms)->toBe(2);
});

test('room creation validates name required', function () {
    $response = $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('room creation validates name max length', function () {
    $response = $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => str_repeat('a', 256),
        ]);

    $response->assertSessionHasErrors('name');
});

test('room creation rejects duplicate name', function () {
    Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Library',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => 'Library',
        ]);

    $response->assertSessionHasErrors('name');
});

test('room name is trimmed before saving', function () {
    $this->actingAs($this->superadmin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => '  Room  ',
        ]);

    $this->assertDatabaseHas('rooms', [
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Room',
    ]);
});

test('superadmin can rename a room', function () {
    $room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Old Name',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->put("/{$this->congregation->slug}/kingdom-hall/rooms/{$room->id}", [
            'name' => 'New Name',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $room->refresh();
    expect($room->name)->toBe('New Name');
});

test('room rename rejects duplicate name', function () {
    Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Existing Room',
        'sort_order' => 1,
    ]);

    $room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Another Room',
        'sort_order' => 2,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->put("/{$this->congregation->slug}/kingdom-hall/rooms/{$room->id}", [
            'name' => 'Existing Room',
        ]);

    $response->assertSessionHasErrors('name');
});

test('superadmin can delete a room', function () {
    $room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'To Delete',
        'sort_order' => 1,
    ]);

    $response = $this->actingAs($this->superadmin)
        ->delete("/{$this->congregation->slug}/kingdom-hall/rooms/{$room->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
});

test('room deletion syncs number_of_rooms', function () {
    $room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Room to Delete',
        'sort_order' => 1,
    ]);
    Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Remaining Room',
        'sort_order' => 2,
    ]);
    $this->kingdomHall->update(['number_of_rooms' => 2]);

    $this->actingAs($this->superadmin)
        ->delete("/{$this->congregation->slug}/kingdom-hall/rooms/{$room->id}");

    $this->kingdomHall->refresh();

    expect($this->kingdomHall->number_of_rooms)->toBe(1);
});

test('non-superadmin cannot create room', function () {
    $admin = User::factory()->create(['current_congregation_id' => $this->congregation->id]);
    Membership::create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->post("/{$this->congregation->slug}/kingdom-hall/rooms", [
            'name' => 'Forbidden Room',
        ]);

    $response->assertForbidden();
});

test('non-superadmin cannot rename room', function () {
    $room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Some Room',
        'sort_order' => 1,
    ]);

    $admin = User::factory()->create(['current_congregation_id' => $this->congregation->id]);
    Membership::create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->put("/{$this->congregation->slug}/kingdom-hall/rooms/{$room->id}", [
            'name' => 'Renamed',
        ]);

    $response->assertForbidden();
});

test('non-superadmin cannot delete room', function () {
    $room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
        'name' => 'Protected Room',
        'sort_order' => 1,
    ]);

    $admin = User::factory()->create(['current_congregation_id' => $this->congregation->id]);
    Membership::create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->delete("/{$this->congregation->slug}/kingdom-hall/rooms/{$room->id}");

    $response->assertForbidden();
});
