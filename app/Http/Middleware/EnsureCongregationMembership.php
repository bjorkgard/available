<?php

namespace App\Http\Middleware;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCongregationMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $minimumRole = null): Response
    {
        [$user, $congregation] = [$request->user(), $this->congregation($request)];

        abort_if(! $user || ! $congregation || ! $this->canAccessCongregation($user, $congregation), 403);

        $this->ensureMemberHasRequiredRole($user, $congregation, $minimumRole);

        if ($request->route('current_congregation') && ! $user->isCurrentCongregation($congregation)) {
            $user->switchCongregation($congregation);
        }

        return $next($request);
    }

    /**
     * Determine if the user can access the given congregation.
     *
     * Access is granted to direct members OR superadmins in the same Kingdom Hall.
     */
    protected function canAccessCongregation(User $user, Congregation $congregation): bool
    {
        return $user->belongsToCongregation($congregation)
            || $user->isSuperadminInSameKingdomHall($congregation);
    }

    /**
     * Ensure the given user has at least the given role, if applicable.
     */
    protected function ensureMemberHasRequiredRole(User $user, Congregation $congregation, ?string $minimumRole): void
    {
        if ($minimumRole === null) {
            return;
        }

        $role = $user->congregationRole($congregation);

        $requiredRole = CongregationRole::tryFrom($minimumRole);

        abort_if(
            $requiredRole === null ||
            $role === null ||
            ! $role->isAtLeast($requiredRole),
            403,
        );
    }

    /**
     * Get the congregation associated with the request.
     */
    protected function congregation(Request $request): ?Congregation
    {
        $congregation = $request->route('current_congregation') ?? $request->route('congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->first();
        }

        return $congregation;
    }
}
