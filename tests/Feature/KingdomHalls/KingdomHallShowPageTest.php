<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin can view kingdom hall page', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('congregations/kingdom-hall/show')
    );
});

test('page displays canManage true for superadmin', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertInertia(fn ($page) => $page
        ->component('congregations/kingdom-hall/show')
        ->where('canManage', true)
    );
});

test('page displays canManage false for non-superadmin', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $admin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('congregations/kingdom-hall/show')
        ->where('canManage', false)
    );
});

test('page does not contain inline edit form', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertOk();
    $response->assertDontSee('Edit Kingdom Hall');
});

test('page does not contain inline add congregation form', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertOk();
    $response->assertDontSee('Add Congregation');
});

test('rooms are returned in sort_order ascending', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room C', 'sort_order' => 3]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room A', 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'name' => 'Room B', 'sort_order' => 2]);

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertInertia(fn ($page) => $page
        ->component('congregations/kingdom-hall/show')
        ->has('kingdomHall.rooms', 3)
        ->where('kingdomHall.rooms.0.name', 'Room A')
        ->where('kingdomHall.rooms.1.name', 'Room B')
        ->where('kingdomHall.rooms.2.name', 'Room C')
    );
});

test('rooms include has_future_bookings flag', function () {
    $kh = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'sort_order' => 1]);
    Room::factory()->create(['kingdom_hall_id' => $kh->id, 'sort_order' => 2]);

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertInertia(fn ($page) => $page
        ->component('congregations/kingdom-hall/show')
        ->has('kingdomHall.rooms', 2)
        ->where('kingdomHall.rooms.0.has_future_bookings', false)
        ->where('kingdomHall.rooms.1.has_future_bookings', false)
    );
});
