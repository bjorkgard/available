<?php

// Feature: kingdom-hall-page-refactor, Property 4: number_of_rooms invariant

use App\Actions\Congregations\CreateRoom;
use App\Actions\Congregations\DeleteRoom;
use App\Models\KingdomHall;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.5, 2.13**
test('number_of_rooms equals actual rooms count after random create and delete operations', function () {
    $kingdomHall = KingdomHall::factory()->create(['number_of_rooms' => 0]);

    $createRoom = app(CreateRoom::class);
    $deleteRoom = app(DeleteRoom::class);

    $operationCount = rand(5, 15);
    $roomCounter = 0;

    for ($i = 0; $i < $operationCount; $i++) {
        $existingRooms = $kingdomHall->rooms()->get();
        $canDelete = $existingRooms->isNotEmpty();

        // Randomly choose create or delete (bias toward create if nothing to delete)
        $shouldCreate = ! $canDelete || (bool) rand(0, 1);

        if ($shouldCreate) {
            $roomCounter++;
            $createRoom->handle($kingdomHall, ['name' => "Room {$roomCounter}-".uniqid()]);
        } else {
            $roomToDelete = $existingRooms->random();
            $deleteRoom->handle($roomToDelete);
        }
    }

    $kingdomHall->refresh();

    expect($kingdomHall->number_of_rooms)->toBe($kingdomHall->rooms()->count());
})->repeat(100);
