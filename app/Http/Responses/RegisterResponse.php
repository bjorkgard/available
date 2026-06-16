<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 201);
        }

        $user = $request->user();
        $congregation = $user->currentCongregation;

        if ($congregation?->kingdom_hall_id) {
            return redirect()->route('calendar', ['current_congregation' => $congregation->slug]);
        }

        return redirect()->route('setup.show');
    }
}
