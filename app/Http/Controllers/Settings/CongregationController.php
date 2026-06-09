<?php

namespace App\Http\Controllers\Settings;

use App\Enums\CongregationRole;
use App\Http\Controllers\Controller;
use App\Models\Congregation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CongregationController extends Controller
{
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
    public function edit(Request $request, Congregation $congregation): Response
    {
        $user = $request->user();

        return Inertia::render('congregations/edit', [
            'team' => [
                'id' => $congregation->id,
                'name' => $congregation->name,
                'slug' => $congregation->slug,
                'isPersonal' => false,
            ],
            'members' => $congregation->memberships()->with('user')->get()->map(fn ($membership) => [
                'id' => $membership->user->id,
                'membership_id' => $membership->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'avatar' => $membership->user->avatar ?? null,
                'role' => $membership->role->value,
                'role_label' => $membership->role->label(),
            ]),
            'invitations' => $congregation->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'permissions' => [
                'canUpdateTeam' => $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
                'canDeleteTeam' => $user->congregationRole($congregation) === CongregationRole::Superadmin,
                'canCreateInvitation' => $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
                'canUpdateMember' => $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
                'canRemoveMember' => $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
                'canCancelInvitation' => $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
            ],
            'availableRoles' => CongregationRole::assignable(),
        ]);
    }

    /**
     * Update the congregation.
     */
    public function update(Request $request, Congregation $congregation): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->congregationRole($congregation)?->isAtLeast(CongregationRole::Admin) ?? false,
            403
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $congregation->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Congregation updated.')]);

        return to_route('congregations.edit', ['congregation' => $congregation->slug]);
    }
}
