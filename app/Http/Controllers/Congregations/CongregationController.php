<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\DeleteCongregation;
use App\Actions\Congregations\MoveCongregation;
use App\Actions\Congregations\UpdateCongregationColor;
use App\Enums\CongregationRole;
use App\Http\Controllers\Controller;
use App\Models\Congregation;
use App\Models\KingdomHall;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CongregationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the user's congregations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('congregations/index', [
            'teams' => $user->toUserCongregations(includeCurrent: true),
        ]);
    }

    /**
     * Show the congregation edit page.
     */
    public function edit(Request $request): Response
    {
        $congregation = $request->route('current_congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->firstOrFail();
        }

        $user = $request->user();

        return Inertia::render('congregations/edit', [
            'team' => [
                'id' => $congregation->id,
                'name' => $congregation->name,
                'slug' => $congregation->slug,
                'congregation_number' => $congregation->congregation_number,
                'color' => $congregation->color,
                'locale' => $congregation->locale ?? 'sv',
                'isPersonal' => false,
            ],
            'permissions' => [
                'canUpdateTeam' => $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
                'canDeleteTeam' => $user->congregationRole($congregation) === CongregationRole::Superadmin,
            ],
        ]);
    }

    /**
     * Update the congregation.
     */
    public function update(Request $request): RedirectResponse
    {
        $congregation = $request->route('current_congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->firstOrFail();
        }

        $user = $request->user();

        abort_unless(
            $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
            403
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'congregation_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                'unique:congregations,congregation_number,'.$congregation->id,
            ],
            'locale' => ['sometimes', 'string', Rule::in(config('app.supported_locales'))],
        ], [
            'congregation_number.regex' => __('The congregation number must contain only digits and uppercase letters (A–Z).'),
            'congregation_number.unique' => __('This congregation number is already in use.'),
        ]);

        $congregation->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Congregation updated.')]);

        return to_route('congregation.edit', ['current_congregation' => $congregation->slug]);
    }

    /**
     * Update the congregation's color.
     */
    public function updateColor(Request $request, UpdateCongregationColor $action): RedirectResponse
    {
        $congregation = $request->route('current_congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->firstOrFail();
        }

        $user = $request->user();

        abort_unless(
            $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
            403
        );

        $validated = $request->validate([
            'color' => ['required', 'string'],
        ]);

        $action->handle($congregation, $validated['color']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Congregation color updated.')]);

        return to_route('congregation.edit', ['current_congregation' => $congregation->slug]);
    }

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

        return redirect()->back()->with('success', __('Congregation moved successfully.'));
    }
}
