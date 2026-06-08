<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentCongregation
{
    protected function redirectPathForCurrentCongregation($request, string $redirect): string
    {
        $congregation = $this->currentCongregation($request);

        URL::defaults(['current_congregation' => $congregation->slug]);

        return "/{$congregation->slug}{$redirect}";
    }

    protected function currentCongregation($request)
    {
        $user = $request->user();
        $congregation = $user?->currentCongregation;

        if (! $congregation) {
            abort(403);
        }

        return $congregation;
    }
}
