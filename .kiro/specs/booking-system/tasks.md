# Implementation Plan: Booking System

## Overview

Implement a room-booking system integrated into the existing calendar. The plan proceeds from database layer → models/enums → backend logic (actions, policies, events, notifications) → controller/routes → frontend components/hooks → real-time integration → scheduled cleanup. Each task builds incrementally on the previous ones so there is no orphaned code.

## Tasks

- [x] 1. Database migrations and Eloquent models
  - [x] 1.1 Create migration for `recurrence_patterns` table
    - Columns: `id` (uuid PK), `congregation_id` (foreignUuid, cascadeOnDelete), `frequency` (string(10)), `end_date` (date, nullable), `end_count` (integer, nullable), timestamps
    - _Requirements: 2.4_

  - [x] 1.2 Create migration for `bookings` table
    - Columns: `id` (uuid PK), `congregation_id` (foreignUuid, cascadeOnDelete), `user_id` (foreignUuid, nullOnDelete), `name` (string 255, NOT NULL), `starts_at` (datetime), `ends_at` (datetime), `recurrence_pattern_id` (foreignUuid, nullable, nullOnDelete), `is_exception` (boolean, default false), `original_starts_at` (datetime, nullable), timestamps
    - _Requirements: 1.8, 2.4_

  - [x] 1.3 Create migration for `booking_room` pivot table
    - Columns: `id` (uuid PK), `booking_id` (foreignUuid, cascadeOnDelete), `room_id` (foreignUuid, cascadeOnDelete)
    - Unique composite index on `(booking_id, room_id)`
    - _Requirements: 1.6_

  - [x] 1.4 Create `RecurrenceFrequency` enum
    - Create `app/Enums/RecurrenceFrequency.php` with cases: Daily, Weekly, Monthly, Yearly (string-backed)
    - _Requirements: 2.2_

  - [x] 1.5 Create `RecurrencePattern` model with factory
    - Fillable: `congregation_id`, `frequency`, `end_date`, `end_count`
    - Casts: `frequency` → RecurrenceFrequency, `end_date` → date, `end_count` → integer
    - Relations: `bookings()` HasMany, `congregation()` BelongsTo
    - Traits: HasFactory, HasUuids
    - _Requirements: 2.4_

  - [x] 1.6 Create `Booking` model with factory
    - Fillable: `congregation_id`, `user_id`, `name`, `starts_at`, `ends_at`, `recurrence_pattern_id`, `is_exception`, `original_starts_at`
    - Casts: `starts_at` → datetime, `ends_at` → datetime, `original_starts_at` → datetime, `is_exception` → boolean
    - Relations: `congregation()` BelongsTo, `user()` BelongsTo, `recurrencePattern()` BelongsTo, `rooms()` BelongsToMany (via `booking_room`)
    - Traits: HasFactory, HasUuids
    - _Requirements: 1.8, 1.6_

- [x] 2. Authorization and policy
  - [x] 2.1 Create `BookingPolicy`
    - `create(User, Congregation)`: user belongs to congregation
    - `update(User, Booking)`: is owner OR admin in booking's congregation OR superadmin in same Kingdom Hall
    - `delete(User, Booking)`: same logic as update
    - Register policy in `AppServiceProvider` or via model discovery
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4, 7.5_

  - [x] 2.2 Write property test for authorization hierarchy (Property 4)
    - **Property 4: Authorization hierarchy enforcement**
    - Test with randomized users/roles/bookings over 30 iterations
    - Verify: member can only edit own, admin can edit congregation's, superadmin can edit any in hall
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4, 7.5**

- [x] 3. Core action classes
  - [x] 3.1 Create `CreateBooking` action
    - Validate: name (required, max 255), starts_at/ends_at (15-min aligned, ends_at > starts_at), room_ids (required, at least one, must belong to congregation's Kingdom Hall), recurrence (optional: frequency, end_date or end_count)
    - Conflict detection: query overlapping bookings in same rooms
    - Recurrence: generate occurrences up to 365 max, reject entire creation if any occurrence conflicts
    - Persist booking(s) + pivot rows in transaction
    - Dispatch BookingCreated event
    - _Requirements: 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.11, 2.1, 2.2, 2.3, 2.5, 2.6, 2.7, 3.4_

  - [x] 3.2 Write property test for booking time constraint (Property 1)
    - **Property 1: Booking time constraint invariant**
    - For any created booking, ends_at > starts_at and both aligned to 15-min boundaries
    - **Validates: Requirements 1.3, 1.7**

  - [x] 3.3 Write property test for room conflict exclusivity (Property 2)
    - **Property 2: Room conflict exclusivity**
    - For any two bookings sharing a room, time ranges do not overlap
    - **Validates: Requirements 2.5, 6.5, 8.5, 9.7**

  - [x] 3.4 Write property test for recurrence occurrence count limit (Property 3)
    - **Property 3: Recurrence occurrence count limit**
    - For any recurrence pattern, generated bookings ≤ 365
    - **Validates: Requirements 2.6**

  - [x] 3.5 Create `UpdateBooking` action
    - Accept edit scope: `this_only` or `this_and_future`
    - `this_only`: set `is_exception = true`, `original_starts_at`, update fields on that booking only
    - `this_and_future`: end original pattern before edit point, create new pattern from edit point, regenerate future occurrences, discard future exceptions
    - Conflict detection on edited/regenerated occurrences
    - Dispatch BookingUpdated event
    - Dispatch notification if modifier ≠ booker
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 6.5_

  - [x] 3.6 Write property test for edit scope isolation (Property 5)
    - **Property 5: Edit scope isolation — "this occurrence only"**
    - Editing single occurrence leaves parent pattern and other occurrences unchanged
    - **Validates: Requirements 8.2**

  - [x] 3.7 Write property test for edit scope split (Property 6)
    - **Property 6: Edit scope split — "this and all future"**
    - Editing "this and future" ends original pattern, creates new pattern, regenerates future only
    - **Validates: Requirements 8.3, 8.4**

  - [x] 3.8 Create `DeleteBooking` action
    - Accept delete scope: `this_only`, `all_future`, or `all` (for non-recurring)
    - `this_only`: delete single occurrence
    - `all_future`: delete selected + all subsequent occurrences; if none remain, delete pattern
    - `all`: delete the standalone booking
    - Dispatch BookingDeleted event
    - Dispatch notification if deleter ≠ booker
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

  - [x] 3.9 Create `RescheduleBooking` action
    - Accept: booking, new `starts_at`, scope (this_only / this_and_future)
    - Compute new `ends_at` preserving original duration
    - Snap to 15-min grid
    - Conflict detection
    - For `this_and_future`: adjust subsequent occurrence dates relative to delta
    - Dispatch BookingUpdated event + notification if third-party
    - _Requirements: 9.2, 9.3, 9.4, 9.5, 9.7_

  - [x] 3.10 Write property test for drag-and-drop duration preservation (Property 7)
    - **Property 7: Drag-and-drop duration preservation**
    - For any reschedule, duration (ends_at - starts_at) remains identical
    - **Validates: Requirements 9.2**

  - [x] 3.11 Create `TransferBookings` action
    - Accept: source user, target user, congregation
    - Update `user_id` on all future bookings (starts_at >= now) from source to target in that congregation
    - Return count of transferred bookings
    - _Requirements: 12.3_

- [x] 4. Checkpoint - Ensure all backend action tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Events, notifications, and broadcasting
  - [x] 5.1 Create broadcast events (BookingCreated, BookingUpdated, BookingDeleted)
    - Implement `ShouldBroadcast` interface
    - Broadcast on `private-kingdom-hall.{kingdomHallId}` channel
    - Use `$this->dontBroadcastToCurrentUser()`
    - BookingCreated includes all occurrence data for recurring bookings
    - _Requirements: 15.1, 15.2, 15.8, 15.9_

  - [x] 5.2 Register channel authorization in `routes/channels.php`
    - Authorize `kingdom-hall.{kingdomHallId}`: user must belong to a congregation in that Kingdom Hall
    - _Requirements: 15.2_

  - [x] 5.3 Create `BookingModifiedNotification`
    - Queued mail to original booker on third-party edit
    - Content: booking name, old/new time range, old/new rooms, modifier name/role, timestamp (sv-SE, Europe/Stockholm)
    - `$tries = 3`, `$backoff = [10, 60, 300]`
    - _Requirements: 13.1, 13.3, 13.6_

  - [x] 5.4 Create `BookingDeletedNotification`
    - Queued mail to original booker on third-party delete
    - Content: deleted booking name, time, rooms, deleter name/role, timestamp (sv-SE)
    - `$tries = 3`, `$backoff = [10, 60, 300]`
    - _Requirements: 13.2, 13.4, 13.6_

  - [x] 5.5 Write property test for notification dispatch correctness (Property 9)
    - **Property 9: Notification dispatch correctness**
    - Third-party modification → exactly 1 notification; self-modification → 0 notifications
    - **Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5**

- [x] 6. Controller and routes
  - [x] 6.1 Create `BookingController` with CRUD + reschedule methods
    - `index`: return bookings for Kingdom Hall within date range (query params: `from`, `to`), include computed `can_edit`/`can_delete` via policy
    - `store`: validate input, authorize via policy, delegate to CreateBooking action
    - `show`: return single booking with details
    - `update`: validate input + scope, authorize, delegate to UpdateBooking action
    - `reschedule`: validate new start + scope, authorize, delegate to RescheduleBooking action
    - `destroy`: validate scope, authorize, delegate to DeleteBooking action
    - _Requirements: 1.11, 1.12, 1.13, 4.1, 4.5, 5.1, 6.4, 7.5, 7.6_

  - [x] 6.2 Register booking routes in `routes/web.php`
    - Under `{current_congregation}` prefix with existing middleware stack
    - `POST /bookings` → store
    - `GET /bookings` → index (JSON)
    - `GET /bookings/{booking}` → show
    - `PUT /bookings/{booking}` → update
    - `PATCH /bookings/{booking}/reschedule` → reschedule
    - `DELETE /bookings/{booking}` → destroy
    - _Requirements: 1.11, 6.4, 7.5_

  - [x] 6.3 Update `DeleteCongregation` action to cascade-delete bookings and recurrence patterns
    - Within existing transaction, delete all bookings and recurrence_patterns for the congregation
    - _Requirements: 11.1, 11.2, 11.3_

  - [x] 6.4 Write property test for cascade deletion completeness (Property 8)
    - **Property 8: Cascade deletion completeness**
    - After congregation deletion, zero bookings/patterns referencing that congregation exist
    - **Validates: Requirements 11.1, 11.2, 11.3**

  - [x] 6.5 Update `MemberController@destroy` to handle booking transfer/delete flow
    - Before removing member: check for future bookings
    - Accept `booking_action` (transfer/delete) and optional `transfer_to` user_id
    - Delegate to TransferBookings or DeleteBooking action
    - If no future bookings exist, proceed directly
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

  - [x] 6.6 Run `php artisan wayfinder:generate` to generate typed route helpers
    - Ensures frontend has access to new booking routes via `@/actions/` and `@/routes/`
    - _Requirements: 1.11_

- [x] 7. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Frontend components — Booking dialog and context menu
  - [x] 8.1 Create `BookingDialog` component
    - shadcn Dialog with form fields: name (input, required, max 255), date/time pickers (15-min intervals), room multi-select (checkboxes from Kingdom Hall rooms)
    - Recurrence toggle → frequency select + end condition (end date or count)
    - Congregation selector (visible only for superadmins with multiple congregations)
    - Form submission via Inertia `useForm` to store/update endpoints
    - Inline validation errors on 422 response
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.12, 1.13, 2.1, 2.2, 3.1, 3.2, 3.3, 3.4_

  - [x] 8.2 Create `BookingContextMenu` component
    - Radix ContextMenu for desktop right-click, custom long-press hook for mobile
    - Display: booking name, time range (sv-SE format), rooms, booker name, congregation name, recurrence summary (if applicable)
    - Conditionally show Edit/Delete actions based on `can_edit`/`can_delete` props
    - Close on outside click
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [x] 8.3 Create `BookingBlock` component
    - Visual booking chip rendered in all calendar views
    - Color-coded by congregation color
    - Displays booking name and time (HH:mm sv-SE)
    - In week/day view: pixel-accurate positioning based on 15-min grid
    - `draggable` prop controlled by `can_edit`
    - _Requirements: 4.2, 4.3, 4.4_

  - [x] 8.4 Create `RecurrenceEditPrompt` component
    - Dialog with options: "This occurrence only" / "This and all future occurrences"
    - Dismiss without selection cancels the operation
    - _Requirements: 8.1, 8.6, 9.3_

  - [x] 8.5 Create `DeleteConfirmDialog` component
    - Non-recurring: shows booking name, offers Cancel / Delete
    - Recurring: shows booking name, offers "Delete only this occurrence" / "Delete all future occurrences" / Cancel
    - _Requirements: 10.1, 10.2_

  - [x] 8.6 Create `MemberRemovalDialog` component
    - Presented when admin removes a member with future bookings
    - Options: transfer (select target member) or delete future bookings
    - Cancel dismisses without removal
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.6_

- [x] 9. Frontend components — Calendar integration and drag-and-drop
  - [x] 9.1 Integrate bookings into calendar page
    - Fetch bookings via BookingController index endpoint (date range params)
    - Render BookingBlock in month/week/day views
    - Month view: show max 4 bookings, "+N more" indicator if > 4
    - Week/day view: side-by-side rendering for overlapping bookings
    - "Create Booking" button at top right of calendar
    - Context menu on right-click/long-press on dates (pre-fill date/time in BookingDialog)
    - _Requirements: 1.1, 1.9, 1.10, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 9.2 Create `useDragBooking` hook
    - Manage drag state with native HTML Drag API
    - Ghost preview on target slot, placeholder on original
    - Snap to 15-minute grid boundaries
    - Disable drag for bookings where `can_edit` is false
    - On drop: call reschedule endpoint, show RecurrenceEditPrompt if recurring
    - Revert on conflict (422) or invalid drop target
    - _Requirements: 9.1, 9.2, 9.5, 9.6, 9.7, 9.8_

  - [x] 9.3 Create `useBookingChannel` hook
    - Subscribe to `private-kingdom-hall.{kingdomHallId}` via Laravel Echo
    - Listen for `.booking.created`, `.booking.updated`, `.booking.deleted`
    - Merge/update/remove bookings from local React state without Inertia reload
    - Handle connection loss: exponential backoff reconnection + subtle indicator
    - _Requirements: 15.3, 15.4, 15.5, 15.6, 15.7_

  - [x] 9.4 Wire real-time channel into calendar page
    - Consume `useBookingChannel` in calendar page with handlers that update booking state
    - Ensure bookings from all congregations in same Kingdom Hall are displayed
    - _Requirements: 15.3, 15.4, 15.5, 15.6_

- [x] 10. Scheduled cleanup command
  - [x] 10.1 Create `CleanupExpiredBookings` artisan command
    - Delete bookings with `ends_at` older than 6 months (Europe/Stockholm timezone)
    - Delete orphaned recurrence patterns (zero remaining bookings)
    - Log count of deleted bookings and patterns
    - Idempotent: subsequent same-day runs delete nothing additional
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6_

  - [x] 10.2 Register cleanup command in scheduler (`routes/console.php`)
    - `Schedule::command('bookings:cleanup')->daily()`
    - _Requirements: 16.1_

  - [x] 10.3 Write property test for cleanup idempotence (Property 10)
    - **Property 10: Cleanup idempotence**
    - Running cleanup twice on same day produces same final state
    - **Validates: Requirements 16.6**

  - [x] 10.4 Write property test for recurrence pattern orphan cleanup (Property 11)
    - **Property 11: Recurrence pattern orphan cleanup**
    - When all bookings of a pattern are deleted, the pattern is also deleted
    - **Validates: Requirements 16.4, 10.4**

- [x] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Feature tests and integration wiring
  - [x] 12.1 Write feature tests for booking CRUD with different roles
    - Test create/update/delete as Member, Admin, Superadmin
    - Test forbidden scenarios (member editing another's booking)
    - _Requirements: 1.11, 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4_

  - [x] 12.2 Write feature tests for recurrence generation and editing
    - Test daily/weekly/monthly/yearly occurrence generation
    - Test "this only" and "this and future" edit scopes
    - Test conflict rejection for recurring bookings
    - _Requirements: 2.3, 2.5, 2.6, 2.7, 8.2, 8.3, 8.4, 8.5_

  - [x] 12.3 Write feature tests for drag-and-drop reschedule
    - Test time shift preserves duration
    - Test conflict detection rejects drop
    - Test scope prompt for recurring bookings
    - _Requirements: 9.2, 9.3, 9.4, 9.7_

  - [x] 12.4 Write feature tests for notification dispatch
    - Assert queued notifications on third-party edit/delete
    - Assert no notification on self-edit
    - Assert notification content (booking name, time, rooms, modifier, timestamp)
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

  - [x] 12.5 Write feature tests for member removal booking transfer/delete
    - Test transfer reassigns future bookings
    - Test delete removes future bookings
    - Test no-future-bookings skips dialog
    - Test no-other-members cancels removal
    - _Requirements: 12.1, 12.3, 12.4, 12.5, 12.6_

  - [x] 12.6 Write property test for booking creation round-trip (Property 12)
    - **Property 12: Booking creation round-trip**
    - Create booking then fetch: returned data matches input
    - **Validates: Requirements 1.8, 1.11**

- [x] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document (Properties 1–12)
- Unit tests validate specific examples and edge cases
- Backend tests use Pest v4 with `->repeat(30)` for property tests
- Frontend tests use Vitest
- All date/time formatting uses `sv-SE` locale and `Europe/Stockholm` timezone
- Wayfinder generates typed route helpers after routes are registered

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.4"] },
    { "id": 1, "tasks": ["1.2", "1.5"] },
    { "id": 2, "tasks": ["1.3", "1.6"] },
    { "id": 3, "tasks": ["2.1"] },
    { "id": 4, "tasks": ["2.2", "3.1"] },
    { "id": 5, "tasks": ["3.2", "3.3", "3.4", "3.5", "3.8", "3.11"] },
    { "id": 6, "tasks": ["3.6", "3.7", "3.9"] },
    { "id": 7, "tasks": ["3.10", "5.1", "5.2", "5.3", "5.4"] },
    { "id": 8, "tasks": ["5.5", "6.1"] },
    { "id": 9, "tasks": ["6.2", "6.3", "6.5"] },
    { "id": 10, "tasks": ["6.4", "6.6"] },
    { "id": 11, "tasks": ["8.1", "8.2", "8.3", "8.4", "8.5", "8.6"] },
    { "id": 12, "tasks": ["9.1", "9.2", "9.3"] },
    { "id": 13, "tasks": ["9.4", "10.1"] },
    { "id": 14, "tasks": ["10.2", "10.3", "10.4"] },
    { "id": 15, "tasks": ["12.1", "12.2", "12.3", "12.4", "12.5", "12.6"] }
  ]
}
```
