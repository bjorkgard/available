<?php

namespace App\Http\Controllers\Congregations;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Models\CongregationInvitation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationAcceptController extends Controller
{
    use PasswordValidationRules;

    /**
     * Show the invitation accept page or process it for authenticated users.
     */
    public function accept(Request $request, CongregationInvitation $invitation): Response|RedirectResponse
    {
        $this->validateInvitation($invitation);

        // Authenticated user: accept immediately
        if ($request->user()) {
            return $this->acceptForAuthenticatedUser($request->user(), $invitation);
        }

        // Check if user already exists but isn't logged in
        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Store the invitation URL as the intended destination so redirect()->intended() returns here after login
            redirect()->setIntendedUrl($request->url());

            return redirect()->route('login')->with('status', __('Please log in to accept this invitation.'));
        }

        // New user: show the registration form
        return Inertia::render('auth/accept-invitation', [
            'invitation' => [
                'code' => $invitation->code,
                'name' => $invitation->name,
                'email' => $invitation->email,
                'congregation_name' => $invitation->congregation->name,
                'role' => $invitation->role->value,
            ],
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Create a new user account and accept the invitation.
     */
    public function store(Request $request, CongregationInvitation $invitation): RedirectResponse
    {
        $this->validateInvitation($invitation);

        // If already authenticated, just accept
        if ($request->user()) {
            return $this->acceptForAuthenticatedUser($request->user(), $invitation);
        }

        // Ensure no existing user with this email
        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            return redirect()->route('login')->with('status', __('An account with this email already exists. Please log in.'));
        }

        $request->validate([
            'password' => $this->passwordRules(),
        ]);

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $invitation->name,
                'email' => $invitation->email,
                'password' => $request->input('password'),
                'locale' => $invitation->locale,
            ]);

            $congregation = $invitation->congregation;

            Membership::create([
                'congregation_id' => $congregation->id,
                'user_id' => $user->id,
                'role' => $invitation->role,
            ]);

            $invitation->update(['accepted_at' => now()]);

            $user->switchCongregation($congregation);

            return $user;
        });

        Auth::login($user);

        $congregation = $invitation->congregation;

        return redirect()->route('calendar', ['current_congregation' => $congregation->slug]);
    }

    /**
     * Accept the invitation for an already authenticated user.
     */
    private function acceptForAuthenticatedUser(User $user, CongregationInvitation $invitation): RedirectResponse
    {
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

        return redirect()->route('calendar', ['current_congregation' => $congregation->slug]);
    }

    /**
     * Validate that the invitation is still valid.
     */
    private function validateInvitation(CongregationInvitation $invitation): void
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
    }
}
