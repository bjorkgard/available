# Implementation Plan: Kingdom Hall Page Refactor

## Overview

Refactor the Kingdom Hall page from inline forms to modal-driven interactions, add full room CRUD via a dedicated `RoomController`, introduce congregation deletion, and enforce authorization at both backend (policy gates) and frontend (conditional rendering) levels. The implementation proceeds backend-first (policy, actions, controller, routes) then frontend (modals, page refactor), finishing with integration wiring and tests.

## Tasks

- [x] 1. Update policy and refactor UpdateKingdomHall action
  - [x] 1.1 Add `manageRooms` and `deleteCongregation` methods to KingdomHallPolicy
    - Add `manageRooms(User $user, KingdomHall $kingdomHall): bool` method that delegates to `isSuperadminInKingdomHall`
    - Add `deleteCongregation(User $user, KingdomHall $kingdomHall): bool` method that delegates to `isSuperadminInKingdomHall`
    - _Requirements: 4.1, 4.2_

  - [x] 1.2 Refactor UpdateKingdomHall action to address-only
    - Remove `number_of_rooms` from validation rules (keep only `street_address`, `zip_code`, `city`)
    - Remove the room auto-generation logic (the `if ($newRoomCount > $currentRoomCount)` block)
    - Remove the decrease rejection logic
    - Update only `street_address`, `zip_code`, `city` in the `$kingdomHall->update()` call
    - _Requirements: 1.5, 1.6, 1.8_

  - [x] 1.3 Write property test for address update round-trip
    - **Property 1: Address update round-trip**
    - Generate 100+ random valid address combinations (street_address 1–255 chars, zip_code 1–20 chars, city 1–100 chars)
    - Assert persisted values match submitted values exactly
    - **Validates: Requirements 1.8**

- [x] 2. Implement Room CRUD actions and controller
  - [x] 2.1 Create the CreateRoom action
    - Create `app/Actions/Congregations/CreateRoom.php`
    - Validate room name: required, string, max 255, trimmed, unique within the Kingdom Hall (case-sensitive after trim)
    - Set `sort_order` to `max(existing sort_orders) + 1` or `1` if no rooms exist
    - Create the Room record linked to the Kingdom Hall
    - Sync `number_of_rooms` on the Kingdom Hall: `$kingdomHall->update(['number_of_rooms' => $kingdomHall->rooms()->count()])`
    - Wrap in a DB transaction
    - _Requirements: 2.4, 2.5, 2.14_

  - [x] 2.2 Create the RenameRoom action
    - Create `app/Actions/Congregations/RenameRoom.php`
    - Validate new name: required, string, max 255, trimmed, unique within the Kingdom Hall excluding the current room
    - Update the room's `name` field
    - _Requirements: 2.9, 2.14_

  - [x] 2.3 Create the DeleteRoom action
    - Create `app/Actions/Congregations/DeleteRoom.php`
    - Delete the room record (and associated booking data when that feature exists)
    - Sync `number_of_rooms` on the Kingdom Hall after deletion
    - Wrap in a DB transaction
    - _Requirements: 2.12, 2.13_

  - [x] 2.4 Create the RoomController
    - Create `app/Http/Controllers/Congregations/RoomController.php`
    - `store(Request $request, CreateRoom $createRoom)` — authorize via `Gate::authorize('manageRooms', $kingdomHall)`, delegate to action, return `back()->with('success', ...)`
    - `update(Request $request, Room $room, RenameRoom $renameRoom)` — authorize, delegate, return back
    - `destroy(Request $request, Room $room, DeleteRoom $deleteRoom)` — authorize, delegate, return back
    - _Requirements: 2.4, 2.9, 2.12, 4.1_

  - [x] 2.5 Write property test for new room sort_order assignment
    - **Property 3: New room sort_order assignment**
    - For Kingdom Halls with 0–10 randomly-ordered existing rooms, assert new room gets `max(sort_order) + 1` or `1`
    - **Validates: Requirements 2.4**

  - [x] 2.6 Write property test for number_of_rooms invariant
    - **Property 4: number_of_rooms invariant**
    - After random sequences of room creations and deletions, assert `number_of_rooms` equals actual `rooms()->count()`
    - **Validates: Requirements 2.5, 2.13**

  - [x] 2.7 Write property test for room name validation
    - **Property 5: Room name validation**
    - Generate random strings, assert acceptance iff 1–255 chars after trim AND unique within KH
    - **Validates: Requirements 2.14**

- [x] 3. Implement congregation deletion and routes
  - [x] 3.1 Create the DeleteCongregation action
    - Create `app/Actions/Congregations/DeleteCongregation.php` (if not already present)
    - Delete the congregation and cascade-delete memberships and pending invitations
    - Wrap in a DB transaction
    - _Requirements: 3.9_

  - [x] 3.2 Add `destroyCongregation` method to KingdomHallController
    - Authorize via `Gate::authorize('deleteCongregation', $kingdomHall)`
    - Validate that the congregation belongs to the Kingdom Hall
    - Delegate to `DeleteCongregation` action
    - Return `back()->with('success', ...)`
    - _Requirements: 3.9, 4.1_

  - [x] 3.3 Register new routes for room CRUD and congregation deletion
    - Add `POST /{current_congregation}/kingdom-hall/rooms` → `RoomController@store` named `kingdom-hall.rooms.store`
    - Add `PUT /{current_congregation}/kingdom-hall/rooms/{room}` → `RoomController@update` named `kingdom-hall.rooms.update`
    - Add `DELETE /{current_congregation}/kingdom-hall/rooms/{room}` → `RoomController@destroy` named `kingdom-hall.rooms.destroy`
    - Add `DELETE /{current_congregation}/kingdom-hall/congregations/{congregation}` → `KingdomHallController@destroyCongregation` named `kingdom-hall.congregations.destroy`
    - _Requirements: 2.4, 2.9, 2.12, 3.9, 4.1_

  - [x] 3.4 Write property test for authorization enforcement
    - **Property 6: Authorization enforcement**
    - For random non-superadmin users, assert all create/update/delete endpoints return 403 and no data changes
    - **Validates: Requirements 4.1, 4.2**

- [x] 4. Checkpoint - Backend verification
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Update KingdomHallController show method and page props
  - [x] 5.1 Update the `show` method to append `has_future_bookings` flag per room
    - Append `has_future_bookings: false` to each room (placeholder until bookings feature)
    - Ensure rooms are ordered by `sort_order` ascending in the query
    - _Requirements: 2.1, 2.10, 2.11_

  - [x] 5.2 Write property test for rooms returned in sort_order ascending
    - **Property 2: Rooms returned in sort_order ascending**
    - Create Kingdom Halls with rooms having random sort_order values, assert response always returns them sorted ascending
    - **Validates: Requirements 2.1**

- [x] 6. Create frontend modal components
  - [x] 6.1 Create the DeleteConfirmationDialog component
    - Create `resources/js/components/delete-confirmation-dialog.tsx`
    - Props: `open`, `onOpenChange`, `title`, `description`, `action` (URL), `method: 'delete'`, optional `confirmationInput: { label, expectedValue }`, optional `warning`
    - When `confirmationInput` is provided, show text input that must match `expectedValue` (case-sensitive) to enable confirm button
    - When `warning` is provided, display a yellow alert banner
    - Use shadcn AlertDialog primitives and Inertia `router.delete()` for the destructive action
    - Display Sonner success toast on success, error toast on failure
    - _Requirements: 2.10, 2.11, 3.7, 3.8_

  - [x] 6.2 Create the AddressEditModal component
    - Create `resources/js/components/address-edit-modal.tsx`
    - Props: `kingdomHall: KingdomHall`, `open: boolean`, `onOpenChange: (open: boolean) => void`
    - Use Inertia `<Form>` with `method="put"` to the kingdom-hall update route
    - Pre-populate fields with current values; no `number_of_rooms` field
    - Close on success via `onSuccess` callback; show success toast via Sonner
    - Display inline validation errors from server response
    - On network/server error, show error toast and keep modal open with user input preserved
    - Use shadcn Dialog primitives
    - _Requirements: 1.4, 1.5, 1.6, 1.7, 1.8, 1.9_

  - [x] 6.3 Create the RoomModal component
    - Create `resources/js/components/room-modal.tsx`
    - Props: `kingdomHall: KingdomHall`, optional `room?: Room`, `open: boolean`, `onOpenChange: (open: boolean) => void`
    - Create mode (no `room`): POST to rooms store route with empty name input
    - Edit mode (`room` provided): PUT to rooms update route, pre-populated with current name
    - Single input field for room name
    - Close on success; show success toast; display inline validation errors on failure
    - Use shadcn Dialog primitives
    - _Requirements: 2.3, 2.4, 2.6, 2.8, 2.9_

  - [x] 6.4 Create the AddCongregationModal component
    - Create `resources/js/components/add-congregation-modal.tsx`
    - Props: `open: boolean`, `onOpenChange: (open: boolean) => void`
    - Fields: congregation name, congregation number, responsible person name, responsible person email
    - Use Inertia `<Form>` with `method="post"` to the congregation store route
    - Close on success; show success toast; display inline validation errors on failure
    - Use shadcn Dialog primitives
    - _Requirements: 3.3, 3.4, 3.5, 3.10, 3.11_

- [x] 7. Refactor KingdomHallShow page to use modals
  - [x] 7.1 Refactor the KingdomHallShow page component
    - Remove the inline "Edit Kingdom Hall" form card entirely
    - Remove the inline "Add Congregation" form card entirely
    - Add edit icon button in the address card header (visible only when `canManage`)
    - Add "+" icon button in the rooms card header (visible only when `canManage`)
    - Add edit and delete action buttons per room (visible only when `canManage`)
    - Add "+" icon button in the congregations card header (visible only when `canManage`)
    - Add delete action button per congregation (visible only when `canManage`)
    - Wire all buttons to open their respective modals via React state
    - Import and render `AddressEditModal`, `RoomModal`, `AddCongregationModal`, `DeleteConfirmationDialog`
    - Update the Props type to include `has_future_bookings` on rooms
    - When deleting a room with `has_future_bookings: true`, pass a warning string to `DeleteConfirmationDialog`
    - When deleting a congregation, pass `confirmationInput` with `expectedValue` set to the congregation number
    - Hide all management controls from DOM when `canManage` is false
    - _Requirements: 1.1, 1.2, 1.3, 2.2, 2.7, 3.1, 3.2, 3.6, 4.3, 4.4, 5.1, 5.2, 5.3_

- [x] 8. Checkpoint - Full integration verification
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Write feature tests for all new endpoints
  - [x] 9.1 Write feature tests for address update endpoint
    - Test successful address update by superadmin
    - Test validation failures (missing fields, exceeded max length)
    - Test 403 for non-superadmin users
    - _Requirements: 1.6, 1.8, 4.1, 4.2_

  - [x] 9.2 Write feature tests for room management endpoints
    - Test room creation (happy path, validation, sort_order, number_of_rooms sync)
    - Test room rename (happy path, uniqueness validation)
    - Test room deletion (happy path, number_of_rooms sync)
    - Test 403 for non-superadmin users on all room endpoints
    - _Requirements: 2.4, 2.5, 2.9, 2.12, 2.13, 2.14, 4.1, 4.2_

  - [x] 9.3 Write feature tests for congregation deletion endpoint
    - Test successful deletion with cascade (memberships, invitations removed)
    - Test 403 for non-superadmin users
    - Test that congregation must belong to the Kingdom Hall
    - _Requirements: 3.9, 4.1, 4.2_

  - [x] 9.4 Write feature tests for KingdomHallShow page rendering
    - Test that superadmin sees management controls in rendered page
    - Test that non-superadmin does NOT see management controls
    - Test no inline form cards present regardless of role
    - Test rooms returned in sort_order ascending
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 4.3, 4.4, 5.1, 5.2_

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Feature tests cover endpoint behavior, authorization, and page rendering
- The `has_future_bookings` flag is a placeholder (always `false`) until the booking feature is implemented
- Wayfinder will auto-generate typed route functions after routes are registered — use them in frontend components
- Run `vendor/bin/pint --dirty --format agent` after PHP changes and `npm run lint` after TS changes

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "2.1", "2.2", "2.3", "3.1"] },
    { "id": 2, "tasks": ["2.4", "3.2", "3.3"] },
    { "id": 3, "tasks": ["2.5", "2.6", "2.7", "3.4", "5.1"] },
    { "id": 4, "tasks": ["5.2", "6.1"] },
    { "id": 5, "tasks": ["6.2", "6.3", "6.4"] },
    { "id": 6, "tasks": ["7.1"] },
    { "id": 7, "tasks": ["9.1", "9.2", "9.3", "9.4"] }
  ]
}
```
