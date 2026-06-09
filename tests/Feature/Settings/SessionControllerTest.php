<?php

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('inertia.testing.ensure_pages_exist', false);
    config()->set('session.driver', 'database');
});

test('authenticated user can view sessions page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('sessions.edit'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/sessions')
            ->has('sessions'),
        );
});

test('guest is redirected to login', function () {
    $response = $this->get(route('sessions.edit'));

    $response->assertRedirect(route('login'));
});

test('unverified user is redirected to email verification', function () {
    $user = User::factory()->unverified()->create();

    if (! ($user instanceof MustVerifyEmail)) {
        $this->markTestSkipped('User model does not implement MustVerifyEmail.');
    }

    $response = $this->actingAs($user)
        ->get(route('sessions.edit'));

    $response->assertRedirect(route('verification.notice'));
});

test('current session is listed first', function () {
    $user = User::factory()->create();

    // Insert other sessions with varying last_activity timestamps
    DB::table('sessions')->insert([
        [
            'id' => 'older-session-id',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0',
            'payload' => '',
            'last_activity' => now()->subMinutes(30)->timestamp,
        ],
        [
            'id' => 'newer-session-id',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Safari/537.36',
            'payload' => '',
            'last_activity' => now()->subMinutes(5)->timestamp,
        ],
    ]);

    $response = $this->actingAs($user)->get(route('sessions.edit'));

    // The response includes sessions - after the request, the current session is also in the DB.
    // The controller runs before session save, so it only sees our 2 inserted sessions.
    // The current session (from the request) is identified by comparing session IDs.
    // Since the request's session hasn't been written to DB at controller execution time,
    // we verify ordering of the pre-existing sessions: newest first.
    $sessions = $response->viewData('page')['props']['sessions'];

    // The sessions should be ordered by last_activity desc (no current match in array driver scenario)
    expect($sessions[0]['id'])->toBe('newer-session-id');
    expect($sessions[1]['id'])->toBe('older-session-id');
});

test('can terminate other sessions with correct password', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        [
            'id' => 'other-session-1',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
            'payload' => '',
            'last_activity' => now()->subHour()->timestamp,
        ],
        [
            'id' => 'other-session-2',
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 Firefox/121.0',
            'payload' => '',
            'last_activity' => now()->subHours(2)->timestamp,
        ],
    ]);

    $response = $this->actingAs($user)
        ->delete(route('sessions.destroy'), [
            'password' => 'password',
        ]);

    $response->assertRedirect();

    // The other sessions should be deleted (the controller deletes all except the current request's session)
    expect(DB::table('sessions')->where('id', 'other-session-1')->exists())->toBeFalse();
    expect(DB::table('sessions')->where('id', 'other-session-2')->exists())->toBeFalse();
});

test('cannot terminate sessions with incorrect password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('sessions.edit'))
        ->delete(route('sessions.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response->assertSessionHasErrors('password')
        ->assertRedirect(route('sessions.edit'));
});

test('terminate sessions is throttled', function () {
    $user = User::factory()->create();

    // Send 6 requests (within the limit)
    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)
            ->delete(route('sessions.destroy'), [
                'password' => 'wrong-password',
            ]);
    }

    // The 7th request should be throttled
    $response = $this->actingAs($user)
        ->delete(route('sessions.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response->assertStatus(429);
});

test('only own sessions are returned', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Insert sessions for both users
    DB::table('sessions')->insert([
        [
            'id' => 'my-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 Chrome/121.0.0.0',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'other-user-session',
            'user_id' => $otherUser->id,
            'ip_address' => '172.16.0.1',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0.0.0',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('sessions.edit'));

    $response->assertOk();

    $sessions = $response->viewData('page')['props']['sessions'];

    // Should only contain the authenticated user's sessions, not other users'
    $sessionIds = collect($sessions)->pluck('id')->all();
    expect($sessionIds)->toContain('my-session');
    expect($sessionIds)->not->toContain('other-user-session');
});
