<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => fn () => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales'),
            'translations' => fn () => $this->loadTranslations(app()->getLocale()),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentCongregation' => fn () => $user?->currentCongregation?->load('kingdomHall.rooms'),
            'currentCongregationRole' => function () use ($user) {
                $congregation = $user?->currentCongregation;

                if (! $congregation) {
                    return null;
                }

                return $user->congregationRole($congregation)?->value;
            },
            'congregations' => function () use ($user) {
                if (! $user) {
                    return [];
                }

                $currentCongregation = $user->currentCongregation;

                // Superadmins can see all congregations in the same kingdom hall
                if ($currentCongregation && $user->isSuperadmin($currentCongregation)) {
                    return $currentCongregation->kingdomHall
                        ?->congregations()
                        ->orderByRaw('LOWER(name)')
                        ->get() ?? $user->congregations;
                }

                return $user->congregations;
            },
        ];
    }

    /**
     * Load and flatten all translations for a given locale.
     *
     * @return array<string, string>
     */
    private function loadTranslations(string $locale): array
    {
        $translations = [];

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
