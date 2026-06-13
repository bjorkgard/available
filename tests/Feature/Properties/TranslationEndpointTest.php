<?php

// Feature: full-localization, Property 3: Translation Endpoint Serves Correct Payload
// For any supported locale, the Translation_Endpoint SHALL return a 200 JSON response
// containing every translation key defined in that locale's files. For any string not
// in the supported locales list, the endpoint SHALL return 404.

// **Validates: Requirements 2.1, 2.2**

test('translation endpoint returns correct payload for supported locales and 404 for unsupported', function () {
    $supportedLocales = config('app.supported_locales');
    $testSupported = fake()->boolean(50);

    if ($testSupported) {
        // Test a supported locale — should return 200 with correct JSON and headers
        $locale = fake()->randomElement($supportedLocales);

        $response = $this->get("/api/translations/{$locale}");

        $response->assertOk();
        $response->assertHeader('ETag');

        $cacheControl = $response->headers->get('Cache-Control');
        expect($cacheControl)->toContain('public')
            ->toContain('max-age=86400')
            ->toContain('must-revalidate');

        $translations = $response->json();

        expect($translations)->toBeArray()->not->toBeEmpty();

        // Verify the response contains keys from the locale's PHP translation files
        $phpPath = lang_path($locale);

        if (is_dir($phpPath)) {
            $phpFiles = glob($phpPath.'/*.php');

            foreach (fake()->randomElements($phpFiles, min(2, count($phpFiles))) as $file) {
                $group = basename($file, '.php');

                // At least one key from this group should be present
                $groupKeys = array_filter(
                    array_keys($translations),
                    fn ($key) => str_starts_with($key, $group.'.')
                );

                expect($groupKeys)->not->toBeEmpty(
                    "Expected keys from group '{$group}' in locale '{$locale}' response"
                );
            }
        }

        // Verify JSON file keys are included if the file exists
        $jsonPath = lang_path("{$locale}.json");

        if (file_exists($jsonPath)) {
            $jsonKeys = array_keys(json_decode(file_get_contents($jsonPath), true));
            $sampledKey = fake()->randomElement($jsonKeys);

            expect($translations)->toHaveKey($sampledKey);
        }
    } else {
        // Test an unsupported locale — should return 404
        $unsupportedLocales = ['fr', 'de', 'xx', 'ja', 'zh', 'pt', 'it', 'nl', 'ko', 'ar'];
        $randomStrings = [fake()->lexify('??'), fake()->lexify('???'), fake()->numerify('##')];
        $allUnsupported = array_merge($unsupportedLocales, $randomStrings);

        // Filter out any that happen to be supported
        $allUnsupported = array_filter(
            $allUnsupported,
            fn ($l) => ! in_array($l, $supportedLocales, true)
        );

        $locale = fake()->randomElement(array_values($allUnsupported));

        $response = $this->get("/api/translations/{$locale}");

        $response->assertNotFound();
    }
})->repeat(30);
