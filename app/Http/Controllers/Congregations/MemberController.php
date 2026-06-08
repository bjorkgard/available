<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\SendInvitation;
use App\Http\Controllers\Controller;
use App\Models\Congregation;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display the members list.
     */
    public function index(Request $request): Response
    {
        $congregation = $this->resolveCongregation($request);

        $members = $congregation->memberships()->with('user')->get();

        return Inertia::render('congregations/members/index', [
            'members' => $members,
            'congregation' => $congregation,
            'viewerRole' => $request->user()->congregationRole($congregation)?->value,
        ]);
    }

    /**
     * Send an invitation to a new member.
     */
    public function invite(Request $request): RedirectResponse
    {
        $congregation = $this->resolveCongregation($request);

        Gate::authorize('invite', [Membership::class, $congregation]);

        $action = new SendInvitation;
        $action->handle($request->user(), $congregation, $request->only(['name', 'email', 'role']));

        return back()->with('success', 'Invitation sent successfully.');
    }

    /**
     * Update a member's role.
     */
    public function update(Request $request, string $currentCongregation, Membership $member): RedirectResponse
    {
        Gate::authorize('update', $member);

        $request->validate([
            'role' => ['required', 'string'],
        ]);

        $member->update([
            'role' => $request->input('role'),
        ]);

        return back()->with('success', 'Member role updated.');
    }

    /**
     * Remove a member from the congregation.
     */
    public function destroy(Request $request, string $currentCongregation, Membership $member): RedirectResponse
    {
        Gate::authorize('delete', $member);

        $member->delete();

        return back()->with('success', 'Member removed from congregation.');
    }

    /**
     * Resolve the congregation from the route parameter.
     */
    protected function resolveCongregation(Request $request): Congregation
    {
        $congregation = $request->route('current_congregation');

        if (is_string($congregation)) {
            $congregation = Congregation::where('slug', $congregation)->firstOrFail();
        }

        return $congregation;
    }
}
