<?php

// Feature: kingdom-hall-page-refactor, Property 1: Address update round-trip

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 1.8**
test('address update round-trip persists exactly the submitted values', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $superadmin = User::factory()->create(['current_congregation_id' => $congregation->id]);
    Membership::create([
        'congregation_id' => $congregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    // Generate random strings without leading/trailing whitespace
    // (Laravel's TrimStrings middleware trims inputs before persistence)
    $streetAddress = trim(fake()->regexify('[A-Za-z0-9 ]{1,255}')) ?: 'A';
    $zipCode = trim(fake()->regexify('[A-Za-z0-9]{1,20}')) ?: '1';
    $city = trim(fake()->regexify('[A-Za-z ]{1,100}')) ?: 'A';

    $response = $this->actingAs($superadmin)
        ->put("/{$congregation->slug}/kingdom-hall", [
            'street_address' => $streetAddress,
            'zip_code' => $zipCode,
            'city' => $city,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $kingdomHall->refresh();

    expect($kingdomHall->street_address)->toBe($streetAddress)
        ->and($kingdomHall->zip_code)->toBe($zipCode)
        ->and($kingdomHall->city)->toBe($city);
})->repeat(30);
