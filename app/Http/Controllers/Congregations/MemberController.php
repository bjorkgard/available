<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Bookings\TransferBookings;
use App\Actions\Congregations\SendInvitation;
use App\Enums\CongregationRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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

        $lastActivityByUser = DB::table('sessions')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $members = $congregation->memberships()
            ->with('user')
            ->leftJoinSub($lastActivityByUser, 'latest_session', function ($join) {
                $join->on('congregation_members.user_id', '=', 'latest_session.user_id');
            })
            ->select('congregation_members.*', 'latest_session.last_activity')
            ->get()
            ->map(function ($membership) {
                $membership->last_active_at = $membership->last_activity
                    ? Carbon::createFromTimestamp($membership->last_activity)->toIso8601String()
                    : null;
                unset($membership->last_activity);

                return $membership;
            })
            ->sortBy(fn ($membership) => $membership->user->name)
            ->values();

        $pendingInvitations = $congregation->invitations()
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->oldest('updated_at')
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
                'message' => __('Failed to send invitation. Please try again.'),
            ])->back();
        }

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Invitation sent successfully.'),
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

        return back()->with('success', __('Member role updated.'));
    }

    /**
     * Remove a member from the congregation.
     *
     * If the member has future bookings, the request must include a `booking_action`
     * (transfer or delete) and optionally a `transfer_to` user ID.
     */
    public function destroy(Request $request, string $currentCongregation, Membership $member): RedirectResponse
    {
        Gate::authorize('delete', $member);

        $congregation = $member->congregation;
        $userId = $member->user_id;

        $hasFutureBookings = Booking::query()
            ->where('congregation_id', $congregation->id)
            ->where('user_id', $userId)
            ->where('starts_at', '>=', now())
            ->exists();

        if ($hasFutureBookings) {
            // Ensure at least one other active member exists in the congregation
            $otherMemberExists = $congregation->memberships()
                ->where('user_id', '!=', $userId)
                ->exists();

            if (! $otherMemberExists) {
                throw ValidationException::withMessages([
                    'member' => __('This member cannot be removed because no other active members exist to receive their bookings. Remove their future bookings first or add another member.'),
                ]);
            }

            $validated = $request->validate([
                'booking_action' => ['required', Rule::in(['transfer', 'delete'])],
                'transfer_to' => [
                    'nullable',
                    'required_if:booking_action,transfer',
                    'uuid',
                    Rule::exists('congregation_members', 'user_id')
                        ->where('congregation_id', $congregation->id)
                        ->whereNot('user_id', $userId),
                ],
            ]);

            if ($validated['booking_action'] === 'transfer') {
                $targetUser = User::findOrFail($validated['transfer_to']);

                (new TransferBookings)->handle(
                    source: $member->user,
                    target: $targetUser,
                    congregation: $congregation,
                );
            } else {
                Booking::query()
                    ->where('congregation_id', $congregation->id)
                    ->where('user_id', $userId)
                    ->where('starts_at', '>=', now())
                    ->delete();
            }
        }

        $member->delete();

        return back()->with('success', __('Member removed from congregation.'));
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
            'message' => __('Invitation cancelled.'),
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
