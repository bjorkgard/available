# Real-Time Features

[← Back to documentation](../README.md#documentation)

## Overview

The application provides real-time updates to the calendar using **Laravel Reverb** (WebSocket server) on the backend and **Laravel Echo** with **Pusher.js** on the frontend.

When a user creates, updates, or deletes a booking, all other users viewing the same Kingdom Hall's calendar see the change instantly — with a toast notification describing what happened.

## Architecture

```
┌─────────────────────┐        ┌─────────────────────┐
│   User A (browser)  │        │   User B (browser)  │
│   Laravel Echo      │        │   Laravel Echo      │
└─────────┬───────────┘        └───────────▲─────────┘
          │ HTTP (creates booking)          │ WebSocket push
          ▼                                 │
┌─────────────────────┐        ┌────────────┴────────┐
│   Laravel Backend   │──────►│   Laravel Reverb     │
│   Fires event       │ Queue  │   (WebSocket server) │
└─────────────────────┘        └─────────────────────┘
```

## Backend Events

Events are broadcast on a private channel scoped to the Kingdom Hall:

```php
new PrivateChannel('kingdom-hall.' . $kingdomHallId)
```

### BookingCreated

- **Trigger:** `CreateBooking` action completes
- **Broadcast name:** `booking.created`
- **Payload:** Array of bookings with id, name, times, congregation info, rooms
- **Excludes:** The user who created the booking (`dontBroadcastToCurrentUser()`)

### BookingUpdated

- **Trigger:** `UpdateBooking` action completes
- **Broadcast name:** `booking.updated`
- **Payload:** Updated booking data
- **Excludes:** The user who made the update

### BookingDeleted

- **Trigger:** `DeleteBooking` action completes
- **Broadcast name:** `booking.deleted`
- **Payload:** Deleted booking ID(s)
- **Excludes:** The user who deleted

## Channel Authorization

Defined in `routes/channels.php`:

```php
Broadcast::channel('kingdom-hall.{kingdomHallId}', function (User $user, string $kingdomHallId) {
    return $user->currentCongregation?->kingdom_hall_id === $kingdomHallId
        || $user->congregations()->where('kingdom_hall_id', $kingdomHallId)->exists();
});
```

A user can subscribe to the channel if they belong to **any** congregation in that Kingdom Hall.

## Frontend Integration

### `use-booking-channel` Hook

The `use-booking-channel` custom hook manages the Echo subscription:

1. Subscribes to the private `kingdom-hall.{id}` channel on mount
2. Listens for `booking.created`, `booking.updated`, `booking.deleted` events
3. Merges incoming data into the calendar's local state
4. Shows a Sonner toast notification describing the change
5. Unsubscribes on unmount

### Configuration

The frontend Echo instance is configured in `resources/js/bootstrap.ts` (or equivalent) using:
- **Pusher.js** as the broadcaster client
- Reverb's host/port/key from Vite environment variables

## Development Setup

To enable real-time features locally:

1. Ensure `BROADCAST_CONNECTION=reverb` in `.env`
2. Run `composer run dev` — this starts Reverb alongside other services
3. Configure Reverb environment variables (auto-generated on install):
   ```
   REVERB_APP_ID=...
   REVERB_APP_KEY=...
   REVERB_APP_SECRET=...
   REVERB_HOST=localhost
   REVERB_PORT=8080
   ```

The `composer run dev` script starts Reverb automatically via:
```
php artisan reverb:start
```

## Notifications

In addition to real-time WebSocket events, the app sends **email notifications** when a booking is modified or deleted by someone other than the booking owner:

| Notification | Trigger |
|-------------|---------|
| `BookingModifiedNotification` | Another user edits your booking |
| `BookingDeletedNotification` | Another user deletes your booking |

These are queued and processed by the queue worker (also started by `composer run dev`).
