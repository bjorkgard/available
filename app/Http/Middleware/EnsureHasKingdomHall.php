<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasKingdomHall
{
    /**
     * Handle an incoming request.
     *
     * Redirects to the setup wizard if the authenticated user's current
     * congregation does not have a linked Kingdom Hall.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('setup.*', 'logout')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $congregation = $user->currentCongregation;

        if (! $congregation || ! $congregation->kingdom_hall_id) {
            return redirect()->route('setup.show');
        }

        return $next($request);
    }
}
