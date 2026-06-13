<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TranslationController extends Controller
{
    public function show(Request $request, string $locale): SymfonyResponse
    {
        if (! in_array($locale, config('app.supported_locales'), true)) {
            abort(404);
        }

        $translations = $this->loadTranslations($locale);
        $body = json_encode($translations, JSON_THROW_ON_ERROR);
        $etag = '"'.md5($body).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=86400, must-revalidate',
            ]);
        }

        return response($body, 200, [
            'Content-Type' => 'application/json',
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
        ]);
    }

    /**
     * Load and flatten all translations for a given locale.
     *
     * @return array<string, string>
     */
    private function loadTranslations(string $locale): array
    {
        $translations = [];

        // Load all PHP files from lang/{locale}/ and flatten with dot notation
        $phpPath = lang_path($locale);

        if (is_dir($phpPath)) {
            foreach (glob($phpPath.'/*.php') as $file) {
                $group = basename($file, '.php');
                $entries = require $file;

                if (is_array($entries)) {
                    $flattened = Arr::dot($entries);

                    foreach ($flattened as $key => $value) {
                        $translations[$group.'.'.$key] = $value;
                    }
                }
            }
        }

        // Load lang/{locale}.json for JSON translations
        $jsonPath = lang_path($locale.'.json');

        if (file_exists($jsonPath)) {
            $jsonTranslations = json_decode(file_get_contents($jsonPath), true);

            if (is_array($jsonTranslations)) {
                $translations = array_merge($translations, $jsonTranslations);
            }
        }

        return $translations;
    }
}
