<?php

use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use App\Notifications\Bookings\BookingDeletedNotification;
use App\Notifications\Bookings\BookingModifiedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->kingdomHall = KingdomHall::factory()->create();

    $this->congregation = Congregation::factory()->withKingdomHall($this->kingdomHall)->create();

    $this->room = Room::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id]);
    $this->secondRoom = Room::factory()->create(['kingdom_hall_id' => $this->kingdomHall->id]);

    // The original booker (member)
    $this->booker = User::factory()->create();
    $this->congregation->members()->attach($this->booker, ['role' => CongregationRole::Member->value]);
    $this->booker->update(['current_congregation_id' => $this->congregation->id]);

    // An admin who can modify other users' bookings
    $this->admin = User::factory()->create();
    $this->congregation->members()->attach($this->admin, ['role' => CongregationRole::Admin->value]);
    $this->admin->update(['current_congregation_id' => $this->congregation->id]);

    // A superadmin who can modify any booking in the hall
    $this->superadmin = User::factory()->create();
    $this->congregation->members()->attach($this->superadmin, ['role' => CongregationRole::Superadmin->value]);
    $this->superadmin->update(['current_congregation_id' => $this->congregation->id]);
});

// --- Third-party edit notifications ---

test('admin editing another user\'s booking dispatches BookingModifiedNotification', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'Original Booking',
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->admin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'name' => 'Updated Booking',
            'starts_at' => '2025-06-10 10:00:00',
            'ends_at' => '2025-06-10 12:00:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ])
        ->assertOk();

    Notification::assertSentTo($this->booker, BookingModifiedNotification::class);
});

test('superadmin editing another user\'s booking dispatches BookingModifiedNotification', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'Original Booking',
        'starts_at' => '2025-06-10 14:00:00',
        'ends_at' => '2025-06-10 15:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->superadmin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'name' => 'Superadmin Override',
            'starts_at' => '2025-06-10 14:00:00',
            'ends_at' => '2025-06-10 16:00:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ])
        ->assertOk();

    Notification::assertSentTo($this->booker, BookingModifiedNotification::class);
});

// --- Third-party delete notifications ---

test('admin deleting another user\'s booking dispatches BookingDeletedNotification', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'Booking To Delete',
        'starts_at' => '2025-06-10 10:00:00',
        'ends_at' => '2025-06-10 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->admin)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'scope' => 'all',
        ])
        ->assertNoContent();

    Notification::assertSentTo($this->booker, BookingDeletedNotification::class);
});

test('superadmin deleting another user\'s booking dispatches BookingDeletedNotification', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'Booking To Delete',
        'starts_at' => '2025-06-10 16:00:00',
        'ends_at' => '2025-06-10 17:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->superadmin)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'scope' => 'all',
        ])
        ->assertNoContent();

    Notification::assertSentTo($this->booker, BookingDeletedNotification::class);
});

// --- Self-modification: no notifications ---

test('user editing their own booking does NOT dispatch a notification', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'My Booking',
        'starts_at' => '2025-06-11 10:00:00',
        'ends_at' => '2025-06-11 11:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->booker)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'name' => 'My Updated Booking',
            'starts_at' => '2025-06-11 10:00:00',
            'ends_at' => '2025-06-11 12:00:00',
            'room_ids' => [$this->room->id],
            'scope' => 'this_only',
        ])
        ->assertOk();

    Notification::assertNothingSent();
});

test('user deleting their own booking does NOT dispatch a notification', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'My Booking',
        'starts_at' => '2025-06-11 14:00:00',
        'ends_at' => '2025-06-11 15:00:00',
    ]);
    $booking->rooms()->attach($this->room->id);

    $this->actingAs($this->booker)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'scope' => 'all',
        ])
        ->assertNoContent();

    Notification::assertNothingSent();
});

// --- Notification content verification ---

test('BookingModifiedNotification contains correct content fields', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'Weekly Standup',
        'starts_at' => '2025-06-12 09:00:00',
        'ends_at' => '2025-06-12 09:30:00',
    ]);
    $booking->rooms()->attach([$this->room->id, $this->secondRoom->id]);

    $this->actingAs($this->admin)
        ->putJson(route('bookings.update', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'name' => 'Weekly Standup',
            'starts_at' => '2025-06-12 09:00:00',
            'ends_at' => '2025-06-12 10:00:00',
            'room_ids' => [$this->secondRoom->id],
            'scope' => 'this_only',
        ])
        ->assertOk();

    Notification::assertSentTo(
        $this->booker,
        BookingModifiedNotification::class,
        function (BookingModifiedNotification $notification) {
            expect($notification->bookingName)->toBe('Weekly Standup')
                ->and($notification->oldStartsAt->format('Y-m-d H:i'))->toBe('2025-06-12 09:00')
                ->and($notification->oldEndsAt->format('Y-m-d H:i'))->toBe('2025-06-12 09:30')
                ->and($notification->newStartsAt->format('Y-m-d H:i'))->toBe('2025-06-12 09:00')
                ->and($notification->newEndsAt->format('Y-m-d H:i'))->toBe('2025-06-12 10:00')
                ->and($notification->oldRooms)->toContain($this->room->name, $this->secondRoom->name)
                ->and($notification->newRooms)->toBe([$this->secondRoom->name])
                ->and($notification->modifier->id)->toBe($this->admin->id)
                ->and($notification->modifierRole)->toBe('Admin')
                ->and($notification->actionTimestamp)->toBeInstanceOf(Carbon::class);

            return true;
        }
    );
});

test('BookingDeletedNotification contains correct content fields', function () {
    $booking = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->booker->id,
        'name' => 'Important Meeting',
        'starts_at' => '2025-06-12 14:00:00',
        'ends_at' => '2025-06-12 15:30:00',
    ]);
    $booking->rooms()->attach([$this->room->id, $this->secondRoom->id]);

    $this->actingAs($this->admin)
        ->deleteJson(route('bookings.destroy', ['current_congregation' => $this->congregation, 'booking' => $booking]), [
            'scope' => 'all',
        ])
        ->assertNoContent();

    Notification::assertSentTo(
        $this->booker,
        BookingDeletedNotification::class,
        function (BookingDeletedNotification $notification) {
            expect($notification->bookingName)->toBe('Important Meeting')
                ->and($notification->startsAt->format('Y-m-d H:i'))->toBe('2025-06-12 14:00')
                ->and($notification->endsAt->format('Y-m-d H:i'))->toBe('2025-06-12 15:30')
                ->and($notification->roomNames)->toContain($this->room->name, $this->secondRoom->name)
                ->and($notification->deleter->id)->toBe($this->admin->id)
                ->and($notification->deleterRole)->toBe(CongregationRole::Admin)
                ->and($notification->actionTimestamp)->toBeInstanceOf(Carbon::class);

            return true;
        }
    );
});
