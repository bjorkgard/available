<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\SendInvitation;
use App\Enums\CongregationRole;
use App\Http\Controllers\Controller;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MemberController extends Controller
{
    /**
     * Display the members list.
     */
    public function index(Request $request): Response
    {
        $congregation = $this->resolveCongregation($request);

        $members = $congregation->memberships()->with('user')->get();

        $pendingInvitations = $congregation->invitations()
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        return Inertia::render('congregations/members/index', [
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
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

        try {
            $action = new SendInvitation;
            $action->handle($request->user(), $congregation, $request->only(['name', 'email', 'role']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to send invitation. Please try again.',
            ])->back();
        }

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Invitation sent successfully.',
        ])->back();
    }

    /**
     * Update a member's role.
     */
    public function update(Request $request, string $currentCongregation, Membership $member): RedirectResponse
    {
        Gate::authorize('update', $member);

        $validated = $request->validate([
            'role' => ['required', 'string', new Enum(CongregationRole::class)],
        ]);

        $requestedRole = CongregationRole::from($validated['role']);

        Gate::authorize('assignRole', [$member, $requestedRole]);

        $member->update([
            'role' => $requestedRole,
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
     * Cancel a pending invitation.
     */
    public function destroyInvitation(Request $request, string $currentCongregation, CongregationInvitation $invitation): RedirectResponse
    {
        $congregation = $this->resolveCongregation($request);

        abort_unless($invitation->congregation_id === $congregation->id, 404);
        abort_unless($invitation->isPending(), 404);

        $invitation->delete();

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Invitation cancelled.',
        ])->back();
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
