<?php

// Feature: congregation-management, Property 5: Room auto-generation

use App\Actions\Congregations\CreateKingdomHall;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 3.3, 3.6**
test('room auto-generation produces correctly named rooms for random room counts', function () {
    $roomCount = rand(1, 50);

    $user = User::factory()->create();
    $congregation = Congregation::factory()->create();
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $user->id,
        'role' => CongregationRole::Admin,
    ]);

    $action = app(CreateKingdomHall::class);
    $kingdomHall = $action->handle($user, $congregation, [
        'street_address' => fake()->streetAddress(),
        'zip_code' => fake()->postcode(),
        'city' => fake()->city(),
        'number_of_rooms' => $roomCount,
    ]);

    $rooms = $kingdomHall->rooms()->orderBy('sort_order')->get();

    // Verify exactly N rooms exist
    expect($rooms)->toHaveCount($roomCount);

    // Verify rooms are named "Room 1" through "Room N" in order
    for ($i = 1; $i <= $roomCount; $i++) {
        expect($rooms[$i - 1]->name)->toBe("Room {$i}")
            ->and($rooms[$i - 1]->sort_order)->toBe($i);
    }
})->repeat(30);
