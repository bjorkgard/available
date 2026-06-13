<?php

// Feature: full-localization, Property 4: Guest Locale Resolution
// For any request from an unauthenticated user with a session-stored locale,
// the resolved locale SHALL be the session value. For any request without session
// locale but with an Accept-Language header containing a supported locale (or prefix
// match), the resolved locale SHALL be the highest q-value supported match. For any
// request with neither session locale nor a matching Accept-Language entry, the
// resolved locale SHALL be 'sv'.

// **Validates: Requirements 3.1, 3.2, 3.3, 3.6, 7.2**

test('guest locale resolution follows priority: session > Accept-Language > sv', function () {
    $supportedLocales = config('app.supported_locales');
    $scenario = fake()->randomElement(['session', 'accept-language', 'fallback']);

    match ($scenario) {
        'session' => (function () use ($supportedLocales) {
            // When session has a stored locale, it should be used regardless of Accept-Language
            $sessionLocale = fake()->randomElement($supportedLocales);
            $acceptLanguage = fake()->randomElement(['en-US', 'sv-FI', 'de-DE', 'fr-FR', '']);

            $response = $this->withSession(['locale' => $sessionLocale])
                ->withHeaders(['Accept-Language' => $acceptLanguage])
                ->get('/');

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];

            expect($props['locale'])->toBe($sessionLocale);
        })(),

        'accept-language' => (function () use ($supportedLocales) {
            // No session locale, but Accept-Language has a supported match
            $locale = fake()->randomElement($supportedLocales);
            $variants = [
                $locale,
                "{$locale}-".fake()->randomElement(['US', 'GB', 'SE', 'FI', 'AU']),
            ];
            $acceptLanguage = fake()->randomElement($variants);

            // Add an unsupported language with higher q-value sometimes
            $withNoise = fake()->boolean(50);

            if ($withNoise) {
                $acceptLanguage = 'de;q=0.9, '.$acceptLanguage.';q=1.0';
            }

            $response = $this->withHeaders(['Accept-Language' => $acceptLanguage])
                ->get('/');

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];

            expect($props['locale'])->toBe($locale);
        })(),

        'fallback' => (function () {
            // No session locale, no matching Accept-Language → should default to app locale
            $unsupportedHeaders = [
                'de-DE,de;q=0.9,fr;q=0.8',
                'zh-CN,zh;q=0.9',
                'ja;q=1.0,ko;q=0.8',
                '*;q=0.1',
                '',
            ];
            $acceptLanguage = fake()->randomElement($unsupportedHeaders);

            $response = $this->withHeaders(['Accept-Language' => $acceptLanguage])
                ->get('/');

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];

            expect($props['locale'])->toBe(config('app.locale'));
        })(),
    };
})->repeat(30);

// Feature: full-localization, Property 12: Accept-Language Prefix Matching
// For any Accept-Language header value containing a language tag with a prefix
// matching a supported locale (e.g., en-US, en-GB, sv-FI), the locale resolver
// SHALL match it to the corresponding supported locale (en or sv).

// **Validates: Requirements 3.1, 3.2**

test('Accept-Language prefix matching resolves language tags to supported locales', function () {
    $supportedLocales = config('app.supported_locales');
    $suffix = strtoupper(fake()->lexify('??'));

    // Pick a random supported locale and generate a regional variant
    $baseLocale = fake()->randomElement($supportedLocales);
    $languageTag = "{$baseLocale}-{$suffix}";

    $response = $this->withHeaders([
        'Accept-Language' => $languageTag,
    ])->get('/');

    $response->assertOk();

    // The Inertia shared prop 'locale' should match the base locale prefix
    $page = $response->original->getData()['page'];
    $sharedLocale = $page['props']['locale'];

    expect($sharedLocale)->toBe($baseLocale, "Language tag '{$languageTag}' should resolve to '{$baseLocale}'");
})->repeat(30);
