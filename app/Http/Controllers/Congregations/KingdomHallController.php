<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\CreateCongregation;
use App\Actions\Congregations\DeleteKingdomHall;
use App\Actions\Congregations\UpdateKingdomHall;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class KingdomHallController extends Controller
{
    /**
     * Display the Kingdom Hall details.
     */
    public function show(Request $request): Response
    {
        $kingdomHall = $request->user()->currentCongregation->kingdomHall;

        Gate::authorize('view', $kingdomHall);

        $kingdomHall->load(['rooms', 'congregations']);

        return Inertia::render('congregations/kingdom-hall/show', [
            'kingdomHall' => $kingdomHall,
            'canManage' => $request->user()->can('update', $kingdomHall),
        ]);
    }

    /**
     * Update the Kingdom Hall.
     */
    public function update(Request $request, UpdateKingdomHall $updateKingdomHall): RedirectResponse
    {
        $kingdomHall = $request->user()->currentCongregation->kingdomHall;

        Gate::authorize('update', $kingdomHall);

        $updateKingdomHall->handle($kingdomHall, $request->all());

        return back()->with('success', 'Kingdom Hall updated successfully.');
    }

    /**
     * Delete the Kingdom Hall.
     */
    public function destroy(Request $request, DeleteKingdomHall $deleteKingdomHall): RedirectResponse
    {
        $kingdomHall = $request->user()->currentCongregation->kingdomHall;

        Gate::authorize('delete', $kingdomHall);

        $deleteKingdomHall->handle($kingdomHall);

        return redirect()->route('home')->with('success', 'Kingdom Hall deleted successfully.');
    }

    /**
     * Add a congregation to the Kingdom Hall.
     */
    public function addCongregation(Request $request, CreateCongregation $createCongregation): RedirectResponse
    {
        $kingdomHall = $request->user()->currentCongregation->kingdomHall;

        Gate::authorize('addCongregation', $kingdomHall);

        $createCongregation->handle($request->user(), $kingdomHall, $request->only([
            'name',
            'congregation_number',
            'initial_user_name',
            'initial_user_email',
        ]));

        return back()->with('success', 'Congregation added successfully.');
    }
}
