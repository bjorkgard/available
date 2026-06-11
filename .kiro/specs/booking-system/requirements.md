# Requirements Document

## Introduction

A room-booking system integrated into the existing calendar application. Members, Administrators, and Super Admins can create, edit, and delete bookings for rooms within their Kingdom Hall. Bookings support recurrence patterns, drag-and-drop rescheduling, congregation color-coding, and automated lifecycle management. The system enforces role-based access and notifies original bookers when their bookings are modified or deleted by higher-privilege users.

## Glossary

- **Booking_System**: The backend and frontend components responsible for creating, storing, displaying, editing, and deleting room bookings.
- **Calendar_UI**: The existing React calendar page component that renders month, week, and day views.
- **Booking_Dialog**: The modal dialog used to create or edit a booking.
- **Context_Menu**: The right-click (desktop) or long-press (mobile) menu displayed on a booking or calendar date.
- **Member**: A user with the `member` CongregationRole who can manage only their own bookings.
- **Administrator**: A user with the `admin` CongregationRole who can manage any booking within their congregation.
- **Super_Admin**: A user with the `superadmin` CongregationRole who can manage bookings across all congregations sharing the Kingdom Hall.
- **Booking**: A scheduled reservation linking a user, congregation, one or more rooms, and a time range.
- **Recurrence_Pattern**: A set of rules (frequency, end condition) defining how a booking repeats over time.
- **Occurrence**: A single instance of a recurring booking on a specific date and time.
- **Congregation**: An organization tied to a Kingdom Hall, identified by a slug in route URLs.
- **Room**: A bookable space within a Kingdom Hall.
- **Kingdom_Hall**: A physical building containing rooms shared by one or more congregations.
- **Notification_Service**: The email subsystem that sends notifications about booking changes.
- **Cleanup_Job**: The scheduled background task responsible for removing expired bookings.
- **Time_Slot**: A 15-minute interval used as the minimum granularity for booking start and end times.
- **Real_Time_Channel**: A private WebSocket channel scoped to a Kingdom_Hall, used to broadcast booking events to all connected users via Laravel Reverb and Laravel Echo.

## Requirements

### Requirement 1: Create a Booking

**User Story:** As a congregation member, I want to create a booking for one or more rooms, so that I can reserve space for meetings and events.

#### Acceptance Criteria

1. THE Calendar_UI SHALL display a "Create Booking" button at the top right of the calendar page.
2. WHEN a user clicks the "Create Booking" button, THE Booking_Dialog SHALL open with fields for booking name, start date and time, end date and time, and room selection.
3. THE Booking_Dialog SHALL enforce 15-minute interval Time_Slots for both start time and end time.
4. THE Booking_Dialog SHALL require a non-empty booking name with a maximum length of 255 characters.
5. THE Booking_Dialog SHALL require at least one Room to be selected.
6. THE Booking_Dialog SHALL allow selection of multiple Rooms simultaneously from the Rooms belonging to the Congregation's KingdomHall.
7. THE Booking_Dialog SHALL require the end date/time to be after the start date/time.
8. THE Booking_System SHALL automatically associate the created Booking with the logged-in user and their current Congregation.
9. WHEN a user right-clicks a date in month view, THE Context_Menu SHALL display a "Create Booking" option with the clicked date pre-filled in the Booking_Dialog.
10. WHEN a user right-clicks a position in week or day view, THE Context_Menu SHALL display a "Create Booking" option with the clicked date and the nearest 15-minute Time_Slot pre-filled based on the vertical click position.
11. WHEN the Booking_Dialog is submitted with valid data and no validation errors exist, THE Booking_System SHALL persist the Booking and display it on the Calendar_UI in all views (month, week, and day) that include the booked date range.
12. IF the Booking_Dialog is submitted with invalid data, THEN THE Booking_System SHALL block persistence and THE Booking_Dialog SHALL remain open and display validation error messages indicating which fields failed validation.
13. IF the Booking_System fails to persist the Booking due to a server error, THEN THE Booking_Dialog SHALL remain open and display an error message indicating the save failed.

### Requirement 2: Recurrence Support

**User Story:** As a congregation member, I want to create repeating bookings, so that I do not have to manually book the same room every week or month.

#### Acceptance Criteria

1. THE Booking_Dialog SHALL provide a toggle to switch between a one-time booking and a recurring booking.
2. WHEN the recurrence toggle is enabled, THE Booking_Dialog SHALL display fields for frequency selection (daily, weekly, monthly, yearly) and an end condition (end date or number of occurrences).
3. WHEN a recurring Booking is created, THE Booking_System SHALL generate Occurrences according to the specified Recurrence_Pattern up to and including the end condition.
4. THE Booking_System SHALL store the Recurrence_Pattern as a single parent entity with individual Occurrences derived from it.
5. IF any generated Occurrence conflicts with an existing Booking in the same Room and time range, THEN THE Booking_System SHALL reject the entire recurring Booking creation and display an error listing the conflicting Occurrence dates.
6. THE Booking_System SHALL limit generated Occurrences to a maximum of 365 per Recurrence_Pattern.
7. WHEN a Recurrence_Pattern's end condition is an end date, THE Booking_System SHALL generate only Occurrences that fall within the end date, creating the booking with only valid occurrences.

### Requirement 3: Super Admin Congregation Selection

**User Story:** As a Super Admin, I want to create bookings on behalf of any congregation sharing the Kingdom Hall, so that I can coordinate cross-congregation scheduling.

#### Acceptance Criteria

1. WHILE the logged-in user has the Superadmin role, THE Booking_Dialog SHALL display a congregation selector dropdown listing all congregations that share the same Kingdom_Hall, ordered alphabetically by congregation name (case-insensitive).
2. WHILE the logged-in user has the Superadmin role, THE Booking_Dialog SHALL pre-select the Super_Admin's own Congregation (currentCongregation) in the congregation selector.
3. WHILE the logged-in user does not have the Superadmin role, OR WHILE only one Congregation exists in the Kingdom_Hall, THE Booking_Dialog SHALL hide the congregation selector and default the booking to the user's own Congregation. THE Booking_System SHALL enforce congregation restrictions at both the UI and backend levels, rejecting any API requests that attempt to assign a booking to a congregation the user is not authorized to book for.
4. WHEN a Super_Admin selects a different congregation from the congregation selector, THE Booking_Dialog SHALL associate the booking being created with the selected Congregation.

### Requirement 4: Calendar Display of Bookings

**User Story:** As a congregation member, I want to see all bookings on the calendar, so that I can quickly identify room availability.

#### Acceptance Criteria

1. THE Calendar_UI SHALL display all Bookings for the visible date range in month, week, and day views.
2. THE Calendar_UI SHALL color-code each Booking using the associated Congregation's color attribute.
3. WHILE in week or day view, THE Calendar_UI SHALL visually span each Booking across its actual start and end time range with pixel-accurate positioning based on the 15-minute Time_Slot grid.
4. THE Calendar_UI SHALL display at minimum the booking name and formatted time (HH:mm in sv-SE locale) on each Booking block in all views.
5. THE Calendar_UI SHALL display Bookings from all congregations sharing the same Kingdom_Hall.
6. WHILE in month view, IF more than four Bookings exist on a single date, THE Calendar_UI SHALL display the first three Bookings and a "+N more" indicator showing the remaining count. IF exactly four Bookings exist on a single date, THE Calendar_UI SHALL display all four without the indicator.
7. WHILE in week or day view, IF two or more Bookings overlap in the same time range, THE Calendar_UI SHALL render them side by side within the time column so all remain visible.

### Requirement 5: View Booking Details

**User Story:** As a congregation member, I want to view full details of a booking, so that I can see who booked it, which rooms are reserved, and the recurrence schedule.

#### Acceptance Criteria

1. WHEN a user right-clicks a Booking on desktop, THE Context_Menu SHALL open and display the booking name, time range (start and end formatted in sv-SE locale), list of reserved rooms, booker name, congregation name, and recurrence summary.
2. WHEN a user long-presses a Booking on a mobile device, THE Context_Menu SHALL open and display the same booking detail fields as criterion 1.
3. IF the Booking has no recurrence rule, THEN THE Context_Menu SHALL omit the recurrence summary field from the detail display.
4. IF the viewing user is the booker, OR has the Admin role in the booking's congregation, OR has the Superadmin role in any congregation sharing the Kingdom Hall, THEN THE Context_Menu SHALL display an "Edit" action. IF the system fails to determine permissions, THE Context_Menu SHALL display an error message explaining why Edit/Delete actions are unavailable.
5. IF the viewing user is the booker, OR has the Admin role in the booking's congregation, OR has the Superadmin role in any congregation sharing the Kingdom Hall, THEN THE Context_Menu SHALL display a "Delete" action.
6. WHEN the user clicks or taps outside the Context_Menu, THE Context_Menu SHALL close without performing any action.

### Requirement 6: Role-Based Edit Permissions

**User Story:** As an administrator, I want to edit any booking in my congregation, so that I can manage scheduling conflicts without relying on the original booker.

#### Acceptance Criteria

1. WHILE the logged-in user has the Member role in a Congregation, THE Booking_System SHALL allow editing only Bookings where that user is the creator.
2. WHILE the logged-in user has the Admin role in a Congregation, THE Booking_System SHALL allow editing any Booking created by a member of that same Congregation.
3. WHILE the logged-in user has the Superadmin role in any Congregation within a Kingdom_Hall, THE Booking_System SHALL allow editing any Booking associated with any Congregation in that Kingdom_Hall.
4. IF a user without edit permission for a Booking attempts to modify it, THEN THE Booking_System SHALL reject the request with a forbidden error indication and leave the Booking unchanged.
5. IF an edit to a Booking would result in a time overlap with another Booking for the same Room, THEN THE Booking_System SHALL reject the edit with an error indication describing the conflict and leave the original Booking unchanged.

### Requirement 7: Role-Based Delete Permissions

**User Story:** As an administrator, I want to delete bookings in my congregation, so that I can remove obsolete or erroneous reservations.

#### Acceptance Criteria

1. WHILE the logged-in user is a Member, WHEN the user requests deletion of a Booking they created, THE Booking_System SHALL remove the Booking from the system and confirm successful deletion to the user.
2. WHILE the logged-in user is a Member, IF the user attempts to delete a Booking created by another user, THEN THE Booking_System SHALL reject the request with an authorization error.
3. WHILE the logged-in user is an Administrator, WHEN the user requests deletion of any Booking belonging to the Administrator's Congregation, THE Booking_System SHALL remove the Booking from the system and confirm successful deletion to the user.
4. WHILE the logged-in user is a Superadmin, WHEN the user requests deletion of any Booking belonging to any Congregation in the same Kingdom_Hall, THE Booking_System SHALL remove the Booking from the system and confirm successful deletion to the user.
5. IF a user without delete permission for the target Booking attempts to delete it, THEN THE Booking_System SHALL reject the request with an authorization error and leave the Booking unchanged.
6. IF a user attempts to delete a Booking that does not exist, THEN THE Booking_System SHALL respond with a not-found error.

### Requirement 8: Editing Recurring Bookings

**User Story:** As a congregation member, I want to choose whether my edit applies to a single occurrence or all future occurrences, so that I have control over the recurrence series.

#### Acceptance Criteria

1. WHEN a user submits changes to a recurring Booking's name, time, room assignment, or Recurrence_Pattern, THE Booking_System SHALL immediately prompt the user to choose between "this occurrence only" and "this and all future occurrences" before persisting any changes.
2. WHEN the user selects "this occurrence only," THE Booking_System SHALL create an exception override for the selected Occurrence with the edited values and leave the parent Recurrence_Pattern and all other Occurrences unchanged.
3. WHEN the user selects "this and all future occurrences," THE Booking_System SHALL split the Recurrence_Pattern at the selected Occurrence, end the original pattern before the selected date, and create a new Recurrence_Pattern starting from the selected Occurrence with the updated values, recalculating all subsequent Occurrences accordingly.
4. IF the user selects "this and all future occurrences" and previously-edited single-occurrence exceptions exist on future dates, THEN THE Booking_System SHALL discard those exceptions and regenerate those Occurrences from the new Recurrence_Pattern.
5. IF the edited Occurrence or regenerated Occurrences conflict with an existing Booking in the same Room and time, THEN THE Booking_System SHALL reject the edit, retain the original state, and display an error message indicating which Occurrence(s) have scheduling conflicts.
6. IF the user dismisses the edit-scope prompt without selecting an option, THEN THE Booking_System SHALL discard the pending changes and leave the Booking unchanged.

### Requirement 9: Drag-and-Drop Rescheduling

**User Story:** As a congregation member, I want to reschedule bookings by dragging them on the calendar, so that I can quickly adjust timing without opening the edit dialog.

#### Acceptance Criteria

1. WHEN a user drags a Booking to a new date or time slot, THE Calendar_UI SHALL display a placeholder on the original slot and a ghost preview on the target slot during the drag operation.
2. WHEN a user drops a Booking on a valid target slot, THE Booking_System SHALL update the Booking's start and end time to the new position while preserving the booking duration.
3. WHEN a user drags a recurring Booking, THE Booking_System SHALL prompt the user to choose between "this occurrence only" and "this and all future occurrences" before applying the change.
4. WHEN the user selects "this and all future occurrences" after a drag, THE Booking_System SHALL adjust subsequent Occurrence dates relative to the new date according to the Recurrence_Pattern.
5. THE Calendar_UI SHALL snap dragged Bookings to the nearest 15-minute Time_Slot boundary.
6. IF the user does not have edit permission for the Booking, THE Calendar_UI SHALL prevent drag initiation on that Booking and suppress all drag-related visual feedback (hover states, cursor changes) for that Booking.
7. IF the rescheduled time conflicts with an existing Booking in the same Room, THE Booking_System SHALL reject the drop, revert the Booking to its original position, and display an error message indicating the conflict.
8. IF the user releases the drag outside a valid drop target, THE Calendar_UI SHALL cancel the drag and return the Booking to its original position.

### Requirement 10: Deleting Bookings

**User Story:** As a congregation member, I want to confirm before deleting a booking, so that I do not accidentally remove important reservations.

#### Acceptance Criteria

1. WHEN a user initiates deletion of a non-recurring Booking, THE Booking_System SHALL display a confirmation dialog showing the booking name and offering "Cancel" and "Delete" options.
2. WHEN a user initiates deletion of a recurring Booking, THE Booking_System SHALL display a dialog showing the booking name and offering three options: "Delete only this occurrence," "Delete all future occurrences," and "Cancel."
3. WHEN the user selects "Delete only this occurrence," THE Booking_System SHALL remove only the selected Occurrence and leave remaining Occurrences unchanged.
4. WHEN the user selects "Delete all future occurrences," THE Booking_System SHALL remove the selected Occurrence and all subsequent Occurrences in the series. IF no Occurrences remain in the series, THE Booking_System SHALL also delete the parent Recurrence_Pattern.
5. WHEN a deletion completes successfully, THE Calendar_UI SHALL immediately remove the deleted Booking(s) from the visible calendar.
6. IF deletion fails due to a server error, network timeout, or any other failure, THE Booking_System SHALL display an error message indicating the failure. THE Calendar_UI MAY refresh to reflect the current server state.

### Requirement 11: Cascade Deletion on Congregation Removal

**User Story:** As a super admin, I want all bookings for a congregation to be removed when that congregation is deleted, so that orphaned data does not pollute the calendar.

#### Acceptance Criteria

1. WHEN a Congregation is deleted, THE Booking_System SHALL delete all Bookings associated with that Congregation regardless of their temporal status (past, current, or future) within the same database transaction.
2. WHEN a Congregation is deleted, THE Booking_System SHALL delete all Recurrence_Patterns associated with Bookings of that Congregation within the same database transaction.
3. WHEN the cascade deletion completes, THE Booking_System SHALL retain zero Bookings and zero Recurrence_Patterns referencing the deleted Congregation's id.

### Requirement 12: User Deletion Booking Transfer

**User Story:** As an administrator, I want to decide what happens to a deleted user's bookings, so that important reservations are not lost.

#### Acceptance Criteria

1. WHEN an administrator initiates removal of a Member from a Congregation, THE Booking_System SHALL present a dialog requiring the administrator to choose between deleting the Member's future Bookings or transferring them to another active Member of the same Congregation before the removal is completed.
2. IF the administrator dismisses the dialog without making a choice, THEN THE Booking_System SHALL cancel the member removal and preserve all existing Bookings unchanged.
3. WHEN the administrator selects "transfer," THE Booking_System SHALL display a list of all other active Members of the same Congregation and reassign all future Bookings from the removed Member to the administrator-selected target Member.
4. WHEN the administrator selects "delete," THE Booking_System SHALL remove all future Bookings created by the removed Member and complete the member removal.
5. IF the removed Member has no future Bookings at the time of removal, THEN THE Booking_System SHALL proceed with the member removal without presenting the transfer-or-delete dialog.
6. IF the Congregation has no other active Members eligible as transfer targets, THEN THE Booking_System SHALL cancel the member removal process entirely and inform the administrator that the member cannot be removed until another active member exists to receive their bookings, or the member's future bookings are deleted separately first.

### Requirement 13: Email Notifications for Third-Party Modifications

**User Story:** As a member, I want to receive an email when an administrator or super admin changes or deletes my booking, so that I am informed of changes I did not make.

#### Acceptance Criteria

1. WHEN an Administrator edits a Booking created by a different user in the same Congregation, THE Notification_Service SHALL dispatch a queued email to the original booker containing: the booking name, old and new time range, old and new room list, the name and role of the person who made the change, and the timestamp of the action formatted in sv-SE locale (Europe/Stockholm timezone).
2. WHEN an Administrator deletes a Booking created by a different user in the same Congregation, THE Notification_Service SHALL dispatch a queued email to the original booker containing: the deleted booking name, time range, room list, the name and role of the person who performed the deletion, and the timestamp of the action formatted in sv-SE locale (Europe/Stockholm timezone).
3. WHEN a Super_Admin edits a Booking created by a different user, THE Notification_Service SHALL dispatch a queued email to the original booker with the same content fields as criterion 1.
4. WHEN a Super_Admin deletes a Booking created by a different user, THE Notification_Service SHALL dispatch a queued email to the original booker with the same content fields as criterion 2.
5. WHEN a user modifies or deletes their own Booking, THE Notification_Service SHALL NOT send a notification email.
6. IF email delivery fails, THE Notification_Service SHALL retry delivery up to three times with exponential backoff (status restricted to QUEUED during retries) before marking the notification as FAILED.

### Requirement 15: Real-Time Calendar Updates

**User Story:** As a congregation member, I want the calendar to update in real time when other users create, edit, or delete bookings, so that I always see current availability without refreshing the page.

#### Acceptance Criteria

1. THE Booking_System SHALL broadcast a WebSocket event via Laravel Reverb whenever a Booking is created, updated, or deleted.
2. THE Booking_System SHALL broadcast booking events on a private channel scoped to the Kingdom_Hall (e.g., `private-kingdom-hall.{kingdomHallId}`), ensuring all users sharing the same Kingdom_Hall receive updates regardless of their congregation.
3. WHEN the Calendar_UI receives a booking-created event, THE Calendar_UI SHALL add the new Booking to the visible calendar without a full page reload, provided the booking falls within the currently displayed date range.
4. WHEN the Calendar_UI receives a booking-updated event, THE Calendar_UI SHALL update the affected Booking's position, name, time, or color on the visible calendar without a full page reload.
5. WHEN the Calendar_UI receives a booking-deleted event, THE Calendar_UI SHALL remove the affected Booking from the visible calendar without a full page reload.
6. THE Calendar_UI SHALL establish a WebSocket connection to the Kingdom_Hall channel upon page load using Laravel Echo and maintain it for the duration of the session.
7. IF the WebSocket connection is lost, THE Calendar_UI SHALL attempt automatic reconnection with exponential backoff and display a subtle connectivity indicator to the user while disconnected.
8. THE Booking_System SHALL NOT broadcast events back to the user who initiated the action (exclude the sender) to prevent redundant UI updates.
9. WHEN a recurring Booking is created, THE Booking_System SHALL broadcast a single event containing all generated Occurrences rather than one event per Occurrence.

### Requirement 16: Automated Booking Cleanup

**User Story:** As a system administrator, I want expired bookings to be automatically removed, so that the database stays clean and the calendar remains relevant.

#### Acceptance Criteria

1. THE Cleanup_Job SHALL be registered in the Laravel scheduler to run once daily.
2. WHEN the Cleanup_Job runs, THE Booking_System SHALL delete all Bookings (and standalone Occurrences) with an end time older than 6 months from the current date, using the Europe/Stockholm timezone for comparison.
3. WHEN the Cleanup_Job deletes an Occurrence that is part of a recurring series, THE Booking_System SHALL remove only the expired Occurrence and preserve the parent Recurrence_Pattern and any future Occurrences.
4. IF all Occurrences of a Recurrence_Pattern have been deleted (none remain with an end time within or after the 6-month threshold), THEN THE Cleanup_Job SHALL also delete the parent Recurrence_Pattern.
5. THE Cleanup_Job SHALL log the total number of deleted Bookings, Occurrences, and Recurrence_Patterns after each run.
6. THE Cleanup_Job SHALL be idempotent — running it multiple times on the same day SHALL produce no additional deletions beyond the first run.
