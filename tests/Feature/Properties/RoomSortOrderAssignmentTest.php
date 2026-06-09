<?php

// Feature: kingdom-hall-page-refactor, Property 3: New room sort_order assignment

use App\Actions\Congregations\CreateRoom;
use App\Models\KingdomHall;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.4**
test('new room sort_order equals max existing sort_order + 1 or 1 if no rooms exist', function () {
    $kingdomHall = KingdomHall::factory()->create();

    $roomCount = rand(0, 10);
    $existingSortOrders = [];

    for ($i = 0; $i < $roomCount; $i++) {
        $sortOrder = rand(1, 100);
        $existingSortOrders[] = $sortOrder;

        Room::factory()->create([
            'kingdom_hall_id' => $kingdomHall->id,
            'sort_order' => $sortOrder,
        ]);
    }

    $expectedSortOrder = empty($existingSortOrders) ? 1 : max($existingSortOrders) + 1;

    $action = app(CreateRoom::class);
    $newRoom = $action->handle($kingdomHall, ['name' => 'Room '.fake()->unique()->uuid()]);

    expect($newRoom->sort_order)->toBe($expectedSortOrder);
})->repeat(100);
