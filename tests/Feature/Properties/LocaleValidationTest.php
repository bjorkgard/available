<?php

// Feature: full-localization, Property 6: Locale Validation Rejects Unsupported Values
// For any string that is not a member of the supported locales set, submitting it as
// a locale value SHALL result in a validation error response.

// **Validates: Requirements 4.7, 6.7**

use App\Models\User;

test('locale validation rejects unsupported values on all locale endpoints', function () {
    $supportedLocales = config('app.supported_locales');

    // Generate a random unsupported locale string
    $unsupported = fake()->randomElement([
        fake()->randomElement(['fr', 'de', 'es', 'it', 'pt', 'nl', 'ja', 'zh', 'ko', 'ar']),
        fake()->lexify('??'),
        fake()->lexify('???'),
        fake()->bothify('??-??'),
        fake()->regexify('[a-z]{1,8}'),
        'invalid',
        'xx',
        'EN',
        'SV',
        'en-US',
        'sv-SE',
    ]);

    // Ensure the generated value is actually not in the supported locales
    if (in_array($unsupported, $supportedLocales, true)) {
        $unsupported = 'xx_unsupported_'.fake()->lexify('???');
    }

    // Test PATCH /settings/locale (authenticated endpoint)
    $user = User::factory()->create();

    $authenticatedResponse = $this->actingAs($user)
        ->patch('/settings/locale', ['locale' => $unsupported]);

    $authenticatedResponse->assertSessionHasErrors('locale');
    expect($authenticatedResponse->status())->toBe(302);

    // Verify user locale was NOT changed
    $user->refresh();
    expect($user->locale)->not->toBe($unsupported);

    // Test POST /locale (guest endpoint)
    $guestResponse = $this->post('/locale', ['locale' => $unsupported]);

    $guestResponse->assertSessionHasErrors('locale');
    expect($guestResponse->status())->toBe(302);
})->repeat(30);

// Feature: full-localization, Property 5: User Locale Persistence
// For any authenticated user and any locale value in the supported locales set,
// updating the user's locale preference SHALL persist that value to the user's
// database record and subsequent requests SHALL reflect the new locale.

// **Validates: Requirements 4.1**

use App\Models\Congregation;

test('user locale persistence: updating locale persists to database and reflects in subsequent requests', function () {
    $supportedLocales = config('app.supported_locales');

    // Pick a random supported locale to set
    $newLocale = fake()->randomElement($supportedLocales);

    // Create a user with a random initial locale (or null)
    $initialLocale = fake()->randomElement([null, ...array_diff($supportedLocales, [$newLocale])]);
    $congregation = Congregation::factory()->withKingdomHall()->create();
    $user = User::factory()->withCongregation($congregation)->create([
        'locale' => $initialLocale,
    ]);

    // Submit PATCH to update locale
    $response = $this->actingAs($user)
        ->patch('/settings/locale', ['locale' => $newLocale]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    // Verify the user's database record was updated
    $user->refresh();
    expect($user->locale)->toBe($newLocale);

    // Make a subsequent request and verify the Inertia shared `locale` prop matches
    $subsequentResponse = $this->actingAs($user)
        ->get(route('calendar', ['current_congregation' => $congregation->slug]));

    $subsequentResponse->assertOk();

    $props = $subsequentResponse->original->getData()['page']['props'];
    expect($props['locale'])->toBe($newLocale);
})->repeat(30);
