<?php

// Feature: full-localization, Property 11: Congregation-Scoped Notification Locale
// For any booking notification sent to a recipient, the notification SHALL be
// rendered using the recipient's User_Locale if set, otherwise the Congregation_Locale,
// otherwise 'sv'.

// **Validates: Requirements 5.3, 7.5**

use App\Actions\Bookings\DeleteBooking;
use App\Enums\CongregationRole;
use App\Enums\DeleteScope;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use App\Notifications\Bookings\BookingDeletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('booking notification locale resolves as: recipient User_Locale > Congregation_Locale > sv', function () {
    Event::fake();
    Notification::fake();

    $supportedLocales = config('app.supported_locales');

    // Randomize the scenario for locale resolution:
    // - user_locale_set: recipient has an explicit locale preference (takes priority)
    // - congregation_fallback: recipient has no locale, falls back to congregation locale
    $scenario = fake()->randomElement(['user_locale_set', 'congregation_fallback']);

    $recipientLocale = match ($scenario) {
        'user_locale_set' => fake()->randomElement($supportedLocales),
        'congregation_fallback' => null,
    };

    // Congregation always has a locale (NOT NULL with default 'sv' in schema)
    $congregationLocale = fake()->randomElement($supportedLocales);

    // Expected resolved locale follows the priority chain:
    // User_Locale > Congregation_Locale > 'sv'
    $expectedLocale = $recipientLocale ?? $congregationLocale;

    // Set up kingdom hall, congregation, rooms
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $kingdomHall->id,
        'locale' => $congregationLocale,
    ]);

    $rooms = Room::factory()->count(fake()->numberBetween(1, 3))->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    // Create the booking owner (recipient of notification)
    $booker = User::factory()->create([
        'email_verified_at' => now(),
        'locale' => $recipientLocale,
    ]);
    $congregation->members()->attach($booker, ['role' => CongregationRole::Member->value]);

    // Create a third-party actor (admin or superadmin) who will delete the booking
    $actorRole = fake()->randomElement([CongregationRole::Admin, CongregationRole::Superadmin]);
    $actor = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($actor, ['role' => $actorRole->value]);

    // Create a booking by the booker
    $hour = fake()->numberBetween(8, 18);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $durationMinutes = fake()->randomElement([30, 45, 60, 90, 120]);

    $baseDate = fake()->dateTimeBetween('+1 day', '+14 days');
    $baseDate->setTime($hour, $minute, 0);

    $startsAt = clone $baseDate;
    $endsAt = (clone $startsAt)->modify("+{$durationMinutes} minutes");

    $booking = Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $booker->id,
        'name' => fake()->words(fake()->numberBetween(2, 4), true),
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
    $booking->rooms()->attach($rooms->pluck('id')->all());

    // Actor deletes the booking — this triggers a notification to the booker
    Notification::fake();

    $deleteAction = app(DeleteBooking::class);
    $deleteAction->handle($actor, $booking, DeleteScope::All);

    // Assert the notification was sent to the booker with the expected locale
    Notification::assertSentTo($booker, BookingDeletedNotification::class, function ($notification, $channels, $notifiable, $locale) use ($expectedLocale) {
        return $locale === $expectedLocale;
    });
})->repeat(30);
