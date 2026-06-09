<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam($request, string $redirect): string
    {
        $congregation = $this->currentTeam($request);

        URL::defaults(['current_team' => $congregation->slug]);

        return "/{$congregation->slug}{$redirect}";
    }

    protected function currentTeam($request)
    {
        $user = $request->user();
        $congregation = $user?->currentCongregation;

        if (! $congregation) {
            abort(403);
        }

        return $congregation;
    }
}
