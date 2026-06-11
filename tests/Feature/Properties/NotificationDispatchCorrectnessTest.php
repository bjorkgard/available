<?php

// Feature: booking-system, Property 9: Notification dispatch correctness

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\DeleteBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Enums\CongregationRole;
use App\Enums\DeleteScope;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use App\Notifications\Bookings\BookingDeletedNotification;
use App\Notifications\Bookings\BookingModifiedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// **Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5**
test('third-party modification dispatches exactly 1 notification; self-modification dispatches 0', function () {
    Event::fake();
    Notification::fake();

    // Set up kingdom hall, congregation, rooms
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    $rooms = Room::factory()->count(2)->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    // Create the original booker (member)
    $booker = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($booker, ['role' => CongregationRole::Member->value]);

    // Create a third-party modifier (admin or superadmin)
    $modifierRole = fake()->randomElement([CongregationRole::Admin, CongregationRole::Superadmin]);
    $modifier = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($modifier, ['role' => $modifierRole->value]);

    // Create a booking by the booker
    $createAction = app(CreateBooking::class);

    $hour = fake()->numberBetween(8, 18);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $durationMinutes = fake()->randomElement([30, 45, 60, 90, 120]);

    $baseDate = fake()->dateTimeBetween('+1 day', '+14 days');
    $baseDate->setTime($hour, $minute, 0);

    $startsAt = clone $baseDate;
    $endsAt = (clone $startsAt)->modify("+{$durationMinutes} minutes");

    $roomIds = $rooms->pluck('id')->all();

    $bookingData = [
        'name' => fake()->words(fake()->numberBetween(2, 4), true),
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        'room_ids' => $roomIds,
    ];

    $createdBookings = $createAction->handle($booker, $congregation, $bookingData);
    $booking = $createdBookings->first();

    // --- Third-party MODIFICATION: modifier (admin/superadmin) edits the booking ---
    Notification::fake(); // Reset notification state

    $updateAction = app(UpdateBooking::class);
    $newName = fake()->words(3, true);

    $updateAction->handle($modifier, $booking->fresh(), [
        'name' => $newName,
        'scope' => 'this_only',
    ]);

    // Assert exactly 1 notification was sent to the booker
    Notification::assertSentToTimes($booker, BookingModifiedNotification::class, 1);

    // --- Self-MODIFICATION: booker edits their own booking ---
    Notification::fake(); // Reset notification state

    $selfName = fake()->words(3, true);

    $updateAction->handle($booker, $booking->fresh(), [
        'name' => $selfName,
        'scope' => 'this_only',
    ]);

    // Assert 0 notifications were sent
    Notification::assertNotSentTo($booker, BookingModifiedNotification::class);
})->repeat(30);

// **Validates: Requirements 13.2, 13.4, 13.5**
test('third-party deletion dispatches exactly 1 notification; self-deletion dispatches 0', function () {
    Event::fake();
    Notification::fake();

    // Set up kingdom hall, congregation, rooms
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    $rooms = Room::factory()->count(2)->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    // Create the original booker (member)
    $booker = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($booker, ['role' => CongregationRole::Member->value]);

    // Create a third-party deleter (admin or superadmin)
    $deleterRole = fake()->randomElement([CongregationRole::Admin, CongregationRole::Superadmin]);
    $deleter = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($deleter, ['role' => $deleterRole->value]);

    // Create a booking by the booker (for the third-party deletion test)
    $createAction = app(CreateBooking::class);

    $hour = fake()->numberBetween(8, 18);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $durationMinutes = fake()->randomElement([30, 45, 60, 90, 120]);

    $baseDate = fake()->dateTimeBetween('+1 day', '+14 days');
    $baseDate->setTime($hour, $minute, 0);

    $startsAt = clone $baseDate;
    $endsAt = (clone $startsAt)->modify("+{$durationMinutes} minutes");

    $roomIds = $rooms->pluck('id')->all();

    $bookingData = [
        'name' => fake()->words(fake()->numberBetween(2, 4), true),
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        'room_ids' => $roomIds,
    ];

    $createdBookings = $createAction->handle($booker, $congregation, $bookingData);
    $booking = $createdBookings->first();

    // --- Third-party DELETION: deleter (admin/superadmin) deletes the booking ---
    Notification::fake(); // Reset notification state

    $deleteAction = app(DeleteBooking::class);
    $deleteAction->handle($deleter, $booking, DeleteScope::All);

    // Assert exactly 1 notification was sent to the booker
    Notification::assertSentToTimes($booker, BookingDeletedNotification::class, 1);

    // --- Self-DELETION: booker deletes their own booking ---
    // Create another booking by the booker for the self-deletion test
    $baseDate2 = fake()->dateTimeBetween('+15 days', '+30 days');
    $baseDate2->setTime($hour, $minute, 0);
    $startsAt2 = clone $baseDate2;
    $endsAt2 = (clone $startsAt2)->modify("+{$durationMinutes} minutes");

    $bookingData2 = [
        'name' => fake()->words(fake()->numberBetween(2, 4), true),
        'starts_at' => $startsAt2->format('Y-m-d H:i:s'),
        'ends_at' => $endsAt2->format('Y-m-d H:i:s'),
        'room_ids' => $roomIds,
    ];

    $createdBookings2 = $createAction->handle($booker, $congregation, $bookingData2);
    $booking2 = $createdBookings2->first();

    Notification::fake(); // Reset notification state

    $deleteAction->handle($booker, $booking2, DeleteScope::All);

    // Assert 0 notifications were sent
    Notification::assertNotSentTo($booker, BookingDeletedNotification::class);
})->repeat(30);
