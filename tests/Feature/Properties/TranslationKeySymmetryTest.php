<?php

// Feature: full-localization, Property 1: Translation Key Symmetry
// For any translation key present in any supported locale's translation files,
// that key SHALL exist in all other supported locale files with a non-empty value.

// **Validates: Requirements 1.5, 1.7**

/**
 * Recursively flatten a nested array with dot notation.
 *
 * @param  array<string, mixed>  $array
 * @return array<string, string>
 */
function flattenTranslationArray(array $array, string $prefix = ''): array
{
    $results = [];

    foreach ($array as $key => $value) {
        $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : $key;

        if (is_array($value)) {
            $results = array_merge($results, flattenTranslationArray($value, $fullKey));
        } else {
            $results[$fullKey] = $value;
        }
    }

    return $results;
}

/**
 * Load all translation keys for a given locale from PHP and JSON files.
 *
 * @return array<string, string>
 */
function loadLocaleTranslations(string $locale): array
{
    $translations = [];

    // Load PHP translation files
    $phpPath = lang_path($locale);

    if (is_dir($phpPath)) {
        foreach (glob("{$phpPath}/*.php") as $file) {
            $group = basename($file, '.php');
            $content = require $file;

            if (is_array($content)) {
                $flattened = flattenTranslationArray($content, $group);
                $translations = array_merge($translations, $flattened);
            }
        }
    }

    // Load JSON translation file
    $jsonPath = lang_path("{$locale}.json");

    if (file_exists($jsonPath)) {
        $content = json_decode(file_get_contents($jsonPath), true);

        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $translations["__json__.{$key}"] = $value;
            }
        }
    }

    return $translations;
}

test('every translation key exists in all supported locales with a non-empty value', function () {
    $supportedLocales = config('app.supported_locales');

    expect($supportedLocales)->toBeArray()->not->toBeEmpty();

    // Load translations for all locales
    $allTranslations = [];
    $allKeys = [];

    foreach ($supportedLocales as $locale) {
        $allTranslations[$locale] = loadLocaleTranslations($locale);
        $allKeys = array_merge($allKeys, array_keys($allTranslations[$locale]));
    }

    $allKeys = array_unique($allKeys);

    expect($allKeys)->not->toBeEmpty();

    // Randomly sample a subset of keys for this repetition
    $sampleSize = min(50, count($allKeys));
    $sampledKeys = fake()->randomElements($allKeys, $sampleSize);

    foreach ($sampledKeys as $key) {
        foreach ($supportedLocales as $locale) {
            expect(array_key_exists($key, $allTranslations[$locale]))
                ->toBeTrue("Key '{$key}' is missing in locale '{$locale}'");

            expect($allTranslations[$locale][$key])
                ->not->toBeEmpty("Key '{$key}' in locale '{$locale}' has an empty value");
        }
    }
})->repeat(30);
