<?php

// Feature: session-management, Property 7: Terminate removes all sessions except current

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// **Validates: Requirements 4.1**
test('terminating other sessions leaves exactly 1 session — the current session', function () {
    $user = User::factory()->create();

    // Generate a random number of sessions (1-20)
    $sessionCount = rand(1, 20);

    // Create session records with random timestamps
    $sessions = collect();
    for ($i = 0; $i < $sessionCount; $i++) {
        $sessions->push([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'payload' => '',
            'last_activity' => rand(time() - 86400 * 30, time()),
        ]);
    }

    // Insert all sessions into the database
    foreach ($sessions as $session) {
        DB::table('sessions')->insert($session);
    }

    // Pick one session to be the "current" session
    $currentSession = $sessions->random();

    // Execute the terminate-other-sessions action with a valid password
    $response = $this->actingAs($user)
        ->withCookies([config('session.cookie') => $currentSession['id']])
        ->delete(route('sessions.destroy'), [
            'password' => 'password',
        ]);

    $response->assertRedirect();

    // Property assertion: Exactly 1 session remains for this user
    $remainingSessions = DB::table('sessions')
        ->where('user_id', $user->id)
        ->get();

    expect($remainingSessions)->toHaveCount(1,
        "Expected exactly 1 session to remain after termination, but found {$remainingSessions->count()}. "
        ."Started with {$sessionCount} sessions."
    );

    // Property assertion: The remaining session is the current session
    expect($remainingSessions->first()->id)->toBe($currentSession['id'],
        'The remaining session must be the current session.'
    );
})->repeat(100);
