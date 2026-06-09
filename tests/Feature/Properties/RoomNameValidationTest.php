<?php

// Feature: kingdom-hall-page-refactor, Property 5: Room name validation

use App\Actions\Congregations\CreateRoom;
use App\Models\KingdomHall;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.14**
test('valid room names are accepted', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $createRoom = app(CreateRoom::class);

    // Generate a random string that is 1–255 chars after trim
    $length = fake()->numberBetween(1, 255);
    $name = fake()->regexify('[A-Za-z0-9 ]{'.$length.'}');

    // Ensure trimmed length is within valid range
    $trimmed = trim($name);
    if (mb_strlen($trimmed) < 1) {
        $name = fake()->lexify(str_repeat('?', $length));
        $trimmed = trim($name);
    }
    if (mb_strlen($trimmed) > 255) {
        $trimmed = mb_substr($trimmed, 0, 255);
        $name = $trimmed;
    }

    // Ensure uniqueness — the name should not already exist in this KH
    expect(Room::where('kingdom_hall_id', $kingdomHall->id)->where('name', $trimmed)->exists())->toBeFalse();

    $room = $createRoom->handle($kingdomHall, ['name' => $name]);

    expect($room)->toBeInstanceOf(Room::class)
        ->and($room->name)->toBe($trimmed)
        ->and($room->kingdom_hall_id)->toBe($kingdomHall->id);
})->repeat(100);

// **Validates: Requirements 2.14**
test('empty or whitespace-only names are rejected', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $createRoom = app(CreateRoom::class);

    $emptyInputs = ['', '   ', "\t", "\n", "  \t\n  "];
    $name = fake()->randomElement($emptyInputs);

    $createRoom->handle($kingdomHall, ['name' => $name]);
})->throws(ValidationException::class)->repeat(100);

// **Validates: Requirements 2.14**
test('names exceeding 255 chars after trim are rejected', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $createRoom = app(CreateRoom::class);

    // Generate a string that is > 255 chars after trim
    $length = fake()->numberBetween(256, 500);
    $name = fake()->regexify('[A-Za-z0-9]{'.$length.'}');

    expect(mb_strlen(trim($name)))->toBeGreaterThan(255);

    $createRoom->handle($kingdomHall, ['name' => $name]);
})->throws(ValidationException::class)->repeat(100);

// **Validates: Requirements 2.14**
test('duplicate room names within the same Kingdom Hall are rejected', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $createRoom = app(CreateRoom::class);

    // Create a room with a random name first
    $existingName = fake()->regexify('[A-Za-z]{1,50}');
    $createRoom->handle($kingdomHall, ['name' => $existingName]);

    // Attempting to create another room with the same name should fail
    $createRoom->handle($kingdomHall, ['name' => $existingName]);
})->throws(ValidationException::class)->repeat(100);
