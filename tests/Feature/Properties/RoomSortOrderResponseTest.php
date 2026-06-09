<?php

// Feature: kingdom-hall-page-refactor, Property 2: Rooms returned in sort_order ascending

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.1**
test('rooms are returned in sort_order ascending in the Inertia response', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $roomCount = rand(2, 10);
    $usedSortOrders = [];

    for ($i = 0; $i < $roomCount; $i++) {
        do {
            $sortOrder = rand(1, 200);
        } while (in_array($sortOrder, $usedSortOrders));

        $usedSortOrders[] = $sortOrder;

        Room::factory()->create([
            'kingdom_hall_id' => $kingdomHall->id,
            'sort_order' => $sortOrder,
        ]);
    }

    $response = $this->actingAs($superadmin)
        ->get("/{$congregation->slug}/kingdom-hall");

    $response->assertOk();

    $rooms = $response->original->getData()['page']['props']['kingdomHall']['rooms'];

    $sortOrders = array_column($rooms, 'sort_order');

    expect(count($sortOrders))->toBe($roomCount);

    for ($i = 1; $i < count($sortOrders); $i++) {
        expect($sortOrders[$i])->toBeGreaterThanOrEqual($sortOrders[$i - 1]);
    }
})->repeat(30);
