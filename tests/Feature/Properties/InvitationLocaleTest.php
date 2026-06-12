<?php

// Feature: full-localization, Property 8: New User Locale From Invitation
// For any invitation with an Invitation_Locale set, when a new user creates an
// account by accepting that invitation, the new user's User_Locale SHALL equal
// the invitation's locale value.

// **Validates: Requirements 5.4, 6.4**

use App\Actions\Congregations\SendInvitation;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\User;
use App\Notifications\Congregations\InvitationNotification;
use Illuminate\Support\Facades\Notification;

test('new user gets locale from invitation on acceptance', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $locale = fake()->randomElement(config('app.supported_locales'));

    $invitation = CongregationInvitation::factory()->create([
        'congregation_id' => $congregation->id,
        'locale' => $locale,
    ]);

    $response = $this->post(route('invitations.accept.store', $invitation), [
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $newUser = User::where('email', $invitation->email)->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->locale)->toBe($locale);
})->repeat(30);

// Feature: full-localization, Property 10: Invitation Email Locale
// For any invitation with an Invitation_Locale set, the invitation email
// notification SHALL be rendered in the Invitation_Locale, regardless of any
// other locale context.

// **Validates: Requirements 6.3, 7.4**

test('invitation email is rendered in the invitation locale', function () {
    Notification::fake();

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $inviter = User::factory()->create();
    $congregation->memberships()->create([
        'user_id' => $inviter->id,
        'role' => CongregationRole::Admin,
    ]);

    $locale = fake()->randomElement(config('app.supported_locales'));

    $action = new SendInvitation;
    $invitation = $action->handle($inviter, $congregation, [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'role' => CongregationRole::Member->value,
        'locale' => $locale,
    ]);

    Notification::assertSentOnDemand(
        InvitationNotification::class,
        function (InvitationNotification $notification, array $channels, object $notifiable) use ($locale, $invitation) {
            expect($notification->locale)->toBe($locale);

            // Render the mail message in the invitation locale and verify translated content
            $mail = app()->call(function () use ($notification, $notifiable, $locale) {
                app()->setLocale($locale);

                return $notification->toMail($notifiable);
            });

            $expectedSubject = trans(
                "You've been invited to join :congregationName",
                ['congregationName' => $invitation->congregation->name],
                $locale,
            );

            expect($mail->subject)->toBe($expectedSubject);

            return true;
        },
    );
})->repeat(30);

// Feature: full-localization, Property 9: Existing User Locale Preserved on Invitation Accept
// For any existing user with a User_Locale set (or null), accepting a congregation
// invitation SHALL NOT modify that user's User_Locale value.

// **Validates: Requirements 6.5**

test('existing user locale is not changed when accepting an invitation', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $userLocale = fake()->randomElement([...config('app.supported_locales'), null]);
    $invitationLocale = fake()->randomElement(config('app.supported_locales'));

    $user = User::factory()->create([
        'locale' => $userLocale,
    ]);

    $invitation = CongregationInvitation::factory()->create([
        'congregation_id' => $congregation->id,
        'email' => $user->email,
        'locale' => $invitationLocale,
    ]);

    $this->actingAs($user)
        ->post(route('invitations.accept.store', $invitation));

    $user->refresh();

    expect($user->locale)->toBe($userLocale);
})->repeat(30);
