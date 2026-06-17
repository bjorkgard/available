# Calendar & Booking System

[← Back to documentation](../README.md#documentation)

## Overview

The calendar is the primary interface of the application — the default landing page after login. It provides a visual room-scheduling system where congregations sharing a Kingdom Hall can view and manage bookings.

## Views

### Month View (M)

- 7-column grid (Mon–Sun)
- Bookings shown as color-coded blocks (by congregation color)
- Maximum 4 bookings visible per day cell
- "+N more" indicator when day has more than 4 bookings
- Drag-and-drop to move bookings between dates

### Week View (W)

- 7 day columns with a 15-minute time grid
- Bookings positioned pixel-accurately based on start/end times
- Overlapping bookings render side-by-side (column subdivision)
- Drag-and-drop with ghost preview and 15-min snapping
- Default view on tablet screens

### Day View (D)

- One column per room in the Kingdom Hall
- Same 15-minute grid positioning as week view
- Drag-and-drop within and between rooms
- Default view on mobile screens

## View Switching

- **Dropdown menu** in the calendar header
- **Keyboard shortcuts:** M (month), W (week), D (day)
- **Additional shortcuts:** N (new booking), T (go to today), ? (keyboard shortcuts help)
- **Responsive defaults:** mobile → day, tablet → week, desktop → month
- The `use-responsive-view-mode` hook manages automatic switching

## Booking CRUD

### Creating a Booking

1. Right-click empty space → "New booking" (context menu)
2. Fill in the booking dialog: name, start/end time, room(s), optional recurrence
3. Superadmins can select which congregation to book for
4. Submit → `POST /{congregation}/bookings`

### Editing a Booking

1. Right-click a booking → "Edit" (or double-click)
2. If recurring: choose scope (this only / all future / all)
3. Modify details in the booking dialog
4. Submit → `PUT /{congregation}/bookings/{booking}`

### Deleting a Booking

1. Right-click a booking → "Delete"
2. Confirmation dialog appears
3. If recurring: choose delete scope (this only / all future / all)
4. Confirm → `DELETE /{congregation}/bookings/{booking}`

### Rescheduling (Drag-and-Drop)

1. Drag a booking to a new time/date/room
2. Ghost preview shows the new position
3. Drop snaps to 15-minute grid
4. Sends → `PATCH /{congregation}/bookings/{booking}/reschedule`

## Recurrence

Bookings can repeat with the following frequencies:
- **Daily** — every day
- **Weekly** — same day each week
- **Monthly** — same date each month
- **Yearly** — same date each year

End conditions:
- **By date** — stop repeating after a specific date
- **By count** — stop after N occurrences

### Recurrence Exceptions

When editing a single occurrence of a recurring booking:
- A new booking is created with `is_exception = true`
- `original_starts_at` stores the original time
- The booking retains its link to the `RecurrencePattern`

### Delete Scopes for Recurring Bookings

| Scope | Behavior |
|-------|----------|
| `this_only` | Deletes only the selected occurrence |
| `all_future` | Deletes this and all future occurrences |
| `all` | Deletes the entire series (all occurrences) |

## Room Conflict Prevention

- A room cannot have overlapping bookings
- The backend validates time ranges against existing bookings for the same room
- Exclusive constraint enforced in the `RoomConflictExclusivity` property test

## Color Coding

Each congregation has an optional `color` field (hex value). Bookings are displayed with:
- Background color matching the congregation's color
- Visible contrast for text readability
- Helps users quickly identify which congregation owns a booking

## Permissions

| Role | Can Do |
|------|--------|
| Member | Create own bookings, edit/delete own bookings |
| Admin | All of Member + edit/delete any booking in their congregation |
| Superadmin | All of Admin + edit/delete any booking in the Kingdom Hall, book on behalf of other congregations |

## Real-Time Updates

When another user modifies the calendar, changes appear instantly via WebSocket:
- New bookings slide into view
- Updated bookings reflect changes
- Deleted bookings disappear
- A toast notification describes what changed

See [Real-Time Features](./real-time.md) for implementation details.

## Automatic Cleanup

The `bookings:cleanup` command runs daily and removes bookings older than 6 months. This keeps the database lean and the calendar focused on relevant data.
