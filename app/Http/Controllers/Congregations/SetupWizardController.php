<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\CreateKingdomHall;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKingdomHallRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupWizardController extends Controller
{
    /**
     * Show the setup wizard form.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('setup/index');
    }

    /**
     * Handle the setup wizard submission.
     */
    public function store(StoreKingdomHallRequest $request, CreateKingdomHall $action): RedirectResponse
    {
        $user = $request->user();
        $congregation = $user->currentCongregation;

        $action->handle($user, $congregation, $request->validated());

        return to_route('dashboard', ['current_congregation' => $congregation->slug]);
    }
}
