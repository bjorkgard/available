<?php

// Feature: full-localization, Property 7: Congregation Locale Change Isolation
// For any congregation with existing members, updating the Congregation_Locale
// SHALL NOT modify any existing member's User_Locale value.

// **Validates: Requirements 5.6**

use App\Models\Congregation;
use App\Models\User;

test('congregation locale change does not modify existing members User_Locale values', function () {
    $supportedLocales = config('app.supported_locales');

    // Create a congregation with a random locale
    $initialLocale = fake()->randomElement($supportedLocales);
    $congregation = Congregation::factory()->withKingdomHall()->create([
        'locale' => $initialLocale,
    ]);

    // Create multiple members with various locales (some null, some 'sv', some 'en')
    $memberLocales = [];
    $memberCount = fake()->numberBetween(3, 6);

    for ($i = 0; $i < $memberCount; $i++) {
        $locale = fake()->randomElement([null, ...config('app.supported_locales')]);
        $user = User::factory()->create(['locale' => $locale]);
        $congregation->members()->attach($user, ['role' => fake()->randomElement(['admin', 'member', 'superadmin'])]);
        $memberLocales[$user->id] = $locale;
    }

    // Record each member's locale value before the change
    $membersBefore = $congregation->members()->get()->mapWithKeys(fn (User $user) => [
        $user->id => $user->locale,
    ])->toArray();

    // Ensure we recorded all members
    expect($membersBefore)->toHaveCount($memberCount);

    // Update the congregation's locale to a different value
    $newLocale = fake()->randomElement(array_diff($supportedLocales, [$initialLocale]));
    $congregation->update(['locale' => $newLocale]);

    // Verify the congregation locale was actually changed
    $congregation->refresh();
    expect($congregation->locale)->toBe($newLocale);

    // Refresh all members from database and assert each member's locale is unchanged
    $membersAfter = $congregation->members()->get()->mapWithKeys(fn (User $user) => [
        $user->id => $user->locale,
    ])->toArray();

    expect($membersAfter)->toHaveCount($memberCount);

    foreach ($membersBefore as $userId => $localeBefore) {
        expect($membersAfter[$userId])->toBe($localeBefore,
            "Member {$userId} locale changed from '{$localeBefore}' to '{$membersAfter[$userId]}' after congregation locale update"
        );
    }
})->repeat(30);
