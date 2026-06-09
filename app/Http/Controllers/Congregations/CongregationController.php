<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\DeleteCongregation;
use App\Actions\Congregations\MoveCongregation;
use App\Http\Controllers\Controller;
use App\Models\Congregation;
use App\Models\KingdomHall;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CongregationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Delete the congregation.
     */
    public function destroy(Request $request, DeleteCongregation $deleteCongregation): RedirectResponse
    {
        $congregation = $request->route('current_congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->firstOrFail();
        }

        $this->authorize('delete', $congregation);

        $deleteCongregation->handle($congregation);

        return redirect()->route('home');
    }

    /**
     * Move the congregation to a different Kingdom Hall.
     */
    public function move(Request $request, MoveCongregation $moveCongregation): RedirectResponse
    {
        $congregation = $request->route('current_congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->firstOrFail();
        }

        $this->authorize('move', $congregation);

        $validated = $request->validate([
            'target_kingdom_hall_id' => ['required', 'uuid', 'exists:kingdom_halls,id'],
        ]);

        $targetKingdomHall = KingdomHall::findOrFail($validated['target_kingdom_hall_id']);

        $moveCongregation->handle($congregation, $targetKingdomHall);

        return redirect()->back()->with('success', 'Congregation moved successfully.');
    }
}
