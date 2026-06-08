<?php

namespace App\Http\Controllers\Congregations;

use App\Http\Controllers\Controller;
use App\Models\CongregationInvitation;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationAcceptController extends Controller
{
    /**
     * Accept a congregation invitation.
     */
    public function accept(Request $request, CongregationInvitation $invitation): RedirectResponse
    {
        if ($invitation->isAccepted()) {
            abort(410, 'This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            abort(410, 'This invitation has expired. Please request a new invitation.');
        }

        if ($invitation->congregation()->withTrashed()->first()?->trashed() || ! $invitation->congregation) {
            abort(404);
        }

        if ($request->user()) {
            $user = $request->user();

            if (strtolower($invitation->email) !== strtolower($user->email)) {
                abort(403, 'This invitation was sent to a different email address.');
            }

            $congregation = $invitation->congregation;

            if (! $user->belongsToCongregation($congregation)) {
                Membership::create([
                    'congregation_id' => $congregation->id,
                    'user_id' => $user->id,
                    'role' => $invitation->role,
                ]);
            }

            $invitation->update(['accepted_at' => now()]);

            $user->switchCongregation($congregation);

            return redirect()->route('dashboard', ['current_congregation' => $congregation->slug]);
        }

        return redirect()->route('register')->with('invitation', $invitation->code);
    }
}
