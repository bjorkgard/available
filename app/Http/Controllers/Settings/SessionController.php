<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DestroySessionRequest;
use App\Support\UserAgentParser;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Show the user's session management page.
     */
    public function edit(Request $request): Response
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                $parser = new UserAgentParser($session->user_agent);

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'browser' => $parser->browser(),
                    'os' => $parser->os(),
                    'device_type' => $parser->deviceType(),
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'is_current_device' => $session->id === $currentSessionId,
                ];
            });

        // Current session first, then remaining (already sorted by last_activity desc from query)
        $currentSession = $sessions->where('is_current_device', true)->values();
        $otherSessions = $sessions->where('is_current_device', false)->values();

        $sortedSessions = $currentSession->merge($otherSessions)->values()->all();

        return Inertia::render('settings/sessions', [
            'sessions' => $sortedSessions,
        ]);
    }

    /**
     * Terminate all other sessions for the authenticated user.
     */
    public function destroy(DestroySessionRequest $request): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Other sessions terminated.')]);

        return back();
    }
}
