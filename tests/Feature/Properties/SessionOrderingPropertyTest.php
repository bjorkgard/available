<?php

// Feature: session-management, Property 1: Session ordering — current first, then by recency

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// **Validates: Requirements 1.1**
test('current session is always first and remaining sessions are sorted by last_activity descending', function () {
    $user = User::factory()->create();

    // Generate a random number of sessions (1-20)
    $sessionCount = rand(1, 20);
    $lifetimeSeconds = config('session.lifetime') * 60;

    // Create session records with random timestamps within the session lifetime window
    $sessions = collect();
    for ($i = 0; $i < $sessionCount; $i++) {
        $sessions->push([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'payload' => '',
            'last_activity' => rand(time() - $lifetimeSeconds + 60, time()),
        ]);
    }

    // Insert all sessions into the database
    foreach ($sessions as $session) {
        DB::table('sessions')->insert($session);
    }

    // Pick one session to be the "current" session
    $currentSession = $sessions->random();

    // Make the request with a session cookie matching our chosen "current" session
    $response = $this->actingAs($user)
        ->withCookies([config('session.cookie') => $currentSession['id']])
        ->get(route('sessions.edit'));

    $response->assertOk();

    $responseSessions = $response->original->getData()['page']['props']['sessions'];

    // Property assertion 1: Current session is always at index 0
    expect($responseSessions[0]['is_current_device'])->toBeTrue(
        'The current session must always be at index 0'
    );

    // Property assertion 2: Remaining sessions are sorted by last_activity descending
    if (count($responseSessions) > 1) {
        $otherSessions = array_slice($responseSessions, 1);

        // Map response session IDs to their original last_activity timestamps
        $sessionTimestamps = $sessions->keyBy('id');

        for ($i = 0; $i < count($otherSessions) - 1; $i++) {
            $currentTimestamp = $sessionTimestamps[$otherSessions[$i]['id']]['last_activity'];
            $nextTimestamp = $sessionTimestamps[$otherSessions[$i + 1]['id']]['last_activity'];

            expect($currentTimestamp)->toBeGreaterThanOrEqual(
                $nextTimestamp,
                'Sessions after index 0 must be sorted by last_activity descending. '
                .'Session at position '.($i + 1)." (timestamp: {$currentTimestamp}) should be >= "
                .'session at position '.($i + 2)." (timestamp: {$nextTimestamp})"
            );
        }
    }
})->repeat(20);
