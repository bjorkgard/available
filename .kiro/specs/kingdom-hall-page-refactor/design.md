# Design Document: Kingdom Hall Page Refactor

## Overview

This design refactors the Kingdom Hall page from inline-form-based editing to a modal-driven UI, adds full room CRUD, and introduces congregation deletion with confirmation. The refactor keeps the existing read-only display cards but replaces the inline "Edit Kingdom Hall" and "Add Congregation" form cards with modal dialogs triggered from icon buttons in card headers. Authorization is enforced both server-side (policy gates returning 403) and client-side (conditionally rendering controls based on `canManage`).

Key architectural changes:
- **New `RoomController`** — dedicated controller for room CRUD (store, update, destroy)
- **New policy methods** — `manageRooms` and `deleteCongregation` on `KingdomHallPolicy`
- **Refactored `UpdateKingdomHall` action** — removes `number_of_rooms` from validation; address-only updates
- **New frontend modals** — `address-edit-modal`, `room-modal`, `add-congregation-modal`, `delete-confirmation-dialog`
- **Derived `number_of_rooms`** — updated via model events on Room creation/deletion rather than manual input

## Architecture

```mermaid
graph TD
    subgraph Frontend [React / Inertia]
        Page[KingdomHallShow Page]
        AddressModal[AddressEditModal]
        RoomModal[RoomModal]
        CongModal[AddCongregationModal]
        DeleteDialog[DeleteConfirmationDialog]
    end

    subgraph Backend [Laravel]
        KHCtrl[KingdomHallController]
        RoomCtrl[RoomController]
        Policy[KingdomHallPolicy]
        UpdateKH[UpdateKingdomHall Action]
        CreateRoom[CreateRoom Action]
        RenameRoom[RenameRoom Action]
        DeleteRoom[DeleteRoom Action]
        CreateCong[CreateCongregation Action]
        DeleteCong[DeleteCongregation Action]
    end

    Page --> AddressModal
    Page --> RoomModal
    Page --> CongModal
    Page --> DeleteDialog

    AddressModal -->|PUT /kingdom-hall| KHCtrl
    RoomModal -->|POST /kingdom-hall/rooms| RoomCtrl
    RoomModal -->|PUT /kingdom-hall/rooms/{room}| RoomCtrl
    DeleteDialog -->|DELETE /kingdom-hall/rooms/{room}| RoomCtrl
    CongModal -->|POST /kingdom-hall/congregations| KHCtrl
    DeleteDialog -->|DELETE /kingdom-hall/congregations/{congregation}| KHCtrl

    KHCtrl --> Policy
    RoomCtrl --> Policy
    KHCtrl --> UpdateKH
    KHCtrl --> CreateCong
    KHCtrl --> DeleteCong
    RoomCtrl --> CreateRoom
    RoomCtrl --> RenameRoom
    RoomCtrl --> DeleteRoom
```

### Request Flow

1. User clicks an action button (edit address, add/edit/delete room, add/delete congregation)
2. Modal opens with appropriate form or confirmation prompt
3. On submit, Inertia `<Form>` or `router` sends request to the appropriate route
4. Controller authorizes via `Gate::authorize()` using `KingdomHallPolicy`
5. Action class validates and performs the operation in a DB transaction
6. Controller returns `back()` with a flash toast
7. Inertia re-renders the page with updated props; modal closes on success

## Components and Interfaces

### Backend Components

#### RoomController

New controller at `app/Http/Controllers/Congregations/RoomController.php`:

```php
class RoomController extends Controller
{
    public function store(Request $request, CreateRoom $createRoom): RedirectResponse
    public function update(Request $request, Room $room, RenameRoom $renameRoom): RedirectResponse
    public function destroy(Request $request, Room $room, DeleteRoom $deleteRoom): RedirectResponse
}
```

#### Action Classes

| Action | Location | Responsibility |
|--------|----------|----------------|
| `UpdateKingdomHall` | `app/Actions/Congregations/` | Validates & updates address fields only (no `number_of_rooms`) |
| `CreateRoom` | `app/Actions/Congregations/` | Validates room name uniqueness within KH, creates room, syncs `number_of_rooms` |
| `RenameRoom` | `app/Actions/Congregations/` | Validates new name uniqueness within KH, updates room name |
| `DeleteRoom` | `app/Actions/Congregations/` | Deletes room (and future bookings when that feature exists), syncs `number_of_rooms` |
| `DeleteCongregation` | `app/Actions/Congregations/` | Already exists — soft-deletes congregation with cascade cleanup |

#### Updated KingdomHallPolicy

New methods added:

```php
public function manageRooms(User $user, KingdomHall $kingdomHall): bool
{
    return $this->isSuperadminInKingdomHall($user, $kingdomHall);
}

public function deleteCongregation(User $user, KingdomHall $kingdomHall): bool
{
    return $this->isSuperadminInKingdomHall($user, $kingdomHall);
}
```

#### Routes (added to the superadmin middleware group)

```php
Route::post('kingdom-hall/rooms', [RoomController::class, 'store'])->name('kingdom-hall.rooms.store');
Route::put('kingdom-hall/rooms/{room}', [RoomController::class, 'update'])->name('kingdom-hall.rooms.update');
Route::delete('kingdom-hall/rooms/{room}', [RoomController::class, 'destroy'])->name('kingdom-hall.rooms.destroy');
Route::delete('kingdom-hall/congregations/{congregation}', [KingdomHallController::class, 'destroyCongregation'])->name('kingdom-hall.congregations.destroy');
```

#### KingdomHallController Changes

- `show()` — adds `hasFutureBookings` flag per room (placeholder: always `false` until bookings are implemented)
- `update()` — unchanged in signature; delegates to refactored `UpdateKingdomHall` (address-only)
- New `destroyCongregation()` method — authorizes, validates congregation belongs to KH, delegates to `DeleteCongregation`

### Frontend Components

#### Page Props Update

```typescript
type Props = {
    kingdomHall: KingdomHall & {
        rooms: (Room & { has_future_bookings: boolean })[];
        congregations: Congregation[];
    };
    canManage: boolean;
};
```

#### AddressEditModal

- Location: `resources/js/components/address-edit-modal.tsx`
- Props: `{ kingdomHall: KingdomHall; open: boolean; onOpenChange: (open: boolean) => void }`
- Uses `<Form>` from `@inertiajs/react` with `method="put"` to `/{slug}/kingdom-hall`
- Pre-populates fields; closes on success via `onSuccess` callback
- Displays inline validation errors from server response

#### RoomModal

- Location: `resources/js/components/room-modal.tsx`
- Props: `{ kingdomHall: KingdomHall; room?: Room; open: boolean; onOpenChange: (open: boolean) => void }`
- When `room` is provided: edit mode (PUT to `/{slug}/kingdom-hall/rooms/{room}`)
- When `room` is undefined: create mode (POST to `/{slug}/kingdom-hall/rooms`)
- Single input field for room name
- Displays inline validation errors

#### AddCongregationModal

- Location: `resources/js/components/add-congregation-modal.tsx`
- Props: `{ open: boolean; onOpenChange: (open: boolean) => void }`
- Fields: congregation name, congregation number, responsible person name, responsible person email
- Uses `<Form>` with `method="post"` to `/{slug}/kingdom-hall/congregations`
- Displays inline validation errors

#### DeleteConfirmationDialog

- Location: `resources/js/components/delete-confirmation-dialog.tsx`
- Props: `{ open: boolean; onOpenChange: (open: boolean) => void; title: string; description: string; action: string; method: 'delete'; confirmationInput?: { label: string; expectedValue: string }; warning?: string }`
- Generic reusable component for all delete confirmations
- When `confirmationInput` is provided, shows a text input that must match `expectedValue` (case-sensitive) before the confirm button enables
- When `warning` is provided, displays a yellow alert banner (e.g., future bookings warning)
- Uses `router.delete()` or `<Form method="delete">` for the destructive action

### Component Hierarchy

```
KingdomHallShow
├── AddressCard (read-only display + edit button if canManage)
│   └── AddressEditModal (controlled by open state)
├── RoomsCard (room list + add button if canManage)
│   ├── RoomModal (add/edit)
│   └── DeleteConfirmationDialog (delete room)
└── CongregationsCard (congregation list + add button if canManage)
    ├── AddCongregationModal (add)
    └── DeleteConfirmationDialog (delete congregation with number confirmation)
```

## Data Models

### Existing Models (unchanged schema)

#### KingdomHall
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| street_address | string(255) | required |
| zip_code | string(20) | required |
| city | string(100) | required |
| number_of_rooms | integer | now derived/synced from room count |

#### Room
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| kingdom_hall_id | uuid (FK) | |
| name | string(255) | unique within KH |
| sort_order | integer | sequential |

#### Congregation
| Column | Type | Notes |
|--------|------|-------|
| id | uuid (PK) | |
| name | string(255) | |
| slug | string | auto-generated |
| congregation_number | string(20) | uppercase alphanumeric, globally unique |
| kingdom_hall_id | uuid (FK) | |
| color | string | auto-generated distinct color |

### Data Flow for number_of_rooms Sync

The `number_of_rooms` field is kept in sync by the `CreateRoom` and `DeleteRoom` actions:

```php
// After room creation/deletion:
$kingdomHall->update([
    'number_of_rooms' => $kingdomHall->rooms()->count(),
]);
```

This avoids model events that could fire unexpectedly and keeps the sync explicit within the action transaction.

### Room has_future_bookings Flag

Until the booking feature is implemented, the controller will append `has_future_bookings: false` to each room. When bookings exist, this will query:

```php
$room->bookings()->where('starts_at', '>', now())->exists()
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Address update round-trip

*For any* valid address data (street_address 1–255 chars, zip_code 1–20 chars, city 1–100 chars), when submitted to the UpdateKingdomHall action, the persisted KingdomHall record SHALL contain exactly the submitted values.

**Validates: Requirements 1.8**

### Property 2: Rooms returned in sort_order ascending

*For any* Kingdom Hall with rooms having arbitrary sort_order values, the controller's show response SHALL always return rooms ordered by sort_order ascending.

**Validates: Requirements 2.1**

### Property 3: New room sort_order assignment

*For any* Kingdom Hall with zero or more existing rooms, when a new room is created, its sort_order SHALL equal the maximum sort_order among existing rooms plus one (or 1 if no rooms exist).

**Validates: Requirements 2.4**

### Property 4: number_of_rooms invariant

*For any* Kingdom Hall, after any room creation or deletion, the `number_of_rooms` field SHALL equal the actual count of rooms belonging to that Kingdom Hall.

**Validates: Requirements 2.5, 2.13**

### Property 5: Room name validation

*For any* string, the room name validation SHALL accept it if and only if it contains between 1 and 255 characters after trimming leading and trailing whitespace AND no other room in the same Kingdom Hall has the same trimmed name.

**Validates: Requirements 2.14**

### Property 6: Authorization enforcement

*For any* authenticated user who does not hold Superadmin role in a congregation belonging to a Kingdom Hall, all create, update, and delete requests targeting that Kingdom Hall's address, rooms, or congregations SHALL return a 403 response and SHALL NOT modify any database records.

**Validates: Requirements 4.1, 4.2**

## Error Handling

### Server-Side Errors

| Scenario | Response | Client Behavior |
|----------|----------|-----------------|
| Validation failure (422) | `back()->withErrors(...)` | Modal stays open, inline errors displayed next to fields |
| Authorization failure (403) | `abort(403)` | Inertia error page rendered |
| Color generation failure | `ValidationException` with `color` key | Modal stays open, error message shown |
| Unauthenticated (401) | Redirect to login | Standard Fortify redirect |
| Room deletion with future bookings | Normal flow — warning shown pre-confirmation | Dialog shows warning; user can still confirm |
| Unexpected server error (500) | Standard error response | Sonner error toast via `onError` callback; modal stays open with user input preserved |

### Client-Side Validation

- Required fields: submit button disabled via HTML `required` attribute and Form component's built-in handling
- No client-side max-length enforcement beyond HTML `maxLength` attribute — server is source of truth
- Network errors caught by Inertia's `onError` event handler, triggering error toast

### Optimistic Behavior

No optimistic updates. All mutations wait for server confirmation before closing modals. This keeps the UI consistent with the database state and avoids complex rollback logic for a low-frequency admin interface.

## Testing Strategy

### Backend Testing (Pest)

**Feature Tests:**
- `KingdomHallUpdateTest` — address update success/failure, validation rules
- `RoomManagementTest` — room create, rename, delete, sort_order logic, number_of_rooms sync
- `CongregationDeletionTest` — cascade deletion, confirmation logic
- `KingdomHallAuthorizationTest` — 403 for non-superadmin on all endpoints

**Property-Based Tests (Pest with `pest-plugin-faker` for data generation):**
- Each correctness property implemented as a dedicated test with 100+ iterations
- Use model factories with randomized attributes
- Tag format: `Feature: kingdom-hall-page-refactor, Property {N}: {title}`
- Property tests live in `tests/Feature/Properties/` directory

**Property test library:** Pest's built-in `with()` datasets combined with `fake()` for generating random valid/invalid inputs across 100+ cases. For properties requiring broader randomization, use PHP generators with `yield` in dataset providers.

### Frontend Testing (Vitest)

**Component Tests:**
- `KingdomHallShow` renders correctly for superadmin vs non-superadmin
- No inline form cards present in any role
- Modal open/close behavior
- Delete confirmation dialog enable/disable logic
- Conditional rendering of action buttons

### Test Configuration

- Property tests: minimum 100 iterations per property
- Each property test tagged with: `Feature: kingdom-hall-page-refactor, Property {number}: {property_text}`
- Backend tests run via `php artisan test --compact`
- Frontend tests run via `npx vitest run`
