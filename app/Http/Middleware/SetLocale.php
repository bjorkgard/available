<?php

namespace App\Http\Middleware;

use App\Models\Congregation;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Set the application locale based on user preference, congregation, or browser.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve the locale for the current request.
     */
    private function resolveLocale(Request $request): string
    {
        if ($user = $request->user()) {
            return $this->resolveAuthenticatedLocale($user, $request);
        }

        return $this->resolveGuestLocale($request);
    }

    /**
     * Resolve locale for an authenticated user.
     *
     * Priority: User_Locale > Congregation_Locale > sv
     */
    private function resolveAuthenticatedLocale(User $user, Request $request): string
    {
        if ($user->locale && $this->isSupported($user->locale)) {
            return $user->locale;
        }

        $congregation = $this->resolveCongregation($request, $user);

        if ($congregation?->locale && $this->isSupported($congregation->locale)) {
            return $congregation->locale;
        }

        return config('app.locale');
    }

    /**
     * Resolve locale for a guest (unauthenticated) user.
     *
     * Priority: session locale > Accept-Language header > sv
     */
    private function resolveGuestLocale(Request $request): string
    {
        if ($request->hasSession()) {
            $sessionLocale = $request->session()->get('locale');

            if ($sessionLocale && $this->isSupported($sessionLocale)) {
                return $sessionLocale;
            }
        }

        return $this->parseAcceptLanguage($request) ?? config('app.locale');
    }

    /**
     * Resolve the current congregation from the route or user relationship.
     */
    private function resolveCongregation(Request $request, User $user): ?Congregation
    {
        // Try route-bound congregation first
        $congregation = $request->route('current_congregation');

        if ($congregation instanceof Congregation) {
            return $congregation;
        }

        // Fall back to the user's current congregation
        return $user->currentCongregation;
    }

    /**
     * Parse the Accept-Language header and return the best supported match.
     *
     * Handles q-values and prefix matching (e.g., en-US → en).
     */
    private function parseAcceptLanguage(Request $request): ?string
    {
        $header = $request->header('Accept-Language');

        if (! $header) {
            return null;
        }

        $locales = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            // Split by semicolon to separate language tag from q-value
            $segments = explode(';', $part);
            $tag = trim($segments[0]);
            $quality = 1.0;

            if (isset($segments[1])) {
                $qPart = trim($segments[1]);

                if (str_starts_with($qPart, 'q=')) {
                    $quality = (float) substr($qPart, 2);
                }
            }

            $locales[] = ['tag' => $tag, 'quality' => $quality];
        }

        // Sort by quality descending
        usort($locales, fn (array $a, array $b) => $b['quality'] <=> $a['quality']);

        foreach ($locales as $locale) {
            $tag = strtolower($locale['tag']);

            // Exact match
            if ($this->isSupported($tag)) {
                return $tag;
            }

            // Prefix match (en-US → en, sv-FI → sv)
            $prefix = strstr($tag, '-', before_needle: true);

            if ($prefix && $this->isSupported($prefix)) {
                return $prefix;
            }
        }

        return null;
    }

    /**
     * Check if a locale is in the supported locales list.
     */
    private function isSupported(string $locale): bool
    {
        return in_array($locale, config('app.supported_locales'), true);
    }
}
