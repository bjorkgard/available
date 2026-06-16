# Domain Model

[← Back to documentation](../README.md#documentation)

## Core Concepts

### Kingdom Hall

A physical building where congregations meet. Has an address and contains one or more bookable rooms.

- One Kingdom Hall can be shared by multiple congregations
- Rooms are auto-generated when a Kingdom Hall is first created
- Only Superadmins can manage Kingdom Hall settings

### Congregation

An organization (a religious congregation) tied to a Kingdom Hall. Users become members with assigned roles.

- Identified by a unique `slug` (auto-generated from name)
- Has a unique `congregation_number`
- Can have a `color` for visual distinction on the calendar
- Supports soft deletes

### Room

A bookable space inside a Kingdom Hall (e.g., "Main Hall", "Second School", "Library").

- Ordered by `sort_order` for consistent display
- Cascade-deleted when the Kingdom Hall is removed

### Booking

A scheduled reservation for one or more rooms within a time range.

- Belongs to a congregation and optionally a user
- Can span multiple rooms (many-to-many via `booking_room`)
- Supports recurrence (linked to a `RecurrencePattern`)
- Color-coded by congregation on the calendar
- Automatically cleaned up after 6 months (daily scheduled command)

### Recurrence Pattern

Defines how a booking repeats over time.

- Frequency: daily, weekly, monthly, yearly
- End conditions: by date or by occurrence count
- One pattern can have many booking instances
- Exceptions are bookings with `is_exception = true` and `original_starts_at` set

### Membership

The relationship between a user and a congregation, with a role.

- Roles: `superadmin`, `admin`, `member`
- A user can belong to multiple congregations
- The `current_congregation_id` on User tracks which congregation is active

### Invitation

A pending invite to join a congregation.

- Sent by an admin with a specified role
- Identified by a unique random `code` (64 chars)
- Has an expiry date
- Can be accepted once (sets `accepted_at`)

## Enums

### CongregationRole

```php
case Superadmin = 'superadmin';  // level 3
case Admin = 'admin';            // level 2
case Member = 'member';          // level 1
```

### RecurrenceFrequency

```php
case Daily = 'daily';
case Weekly = 'weekly';
case Monthly = 'monthly';
case Yearly = 'yearly';
```

### DeleteScope

Used when deleting recurring bookings to specify scope:

```php
case ThisOnly = 'this_only';    // Delete just this occurrence
case AllFuture = 'all_future';  // Delete this and all future occurrences
case All = 'all';               // Delete all occurrences in the series
```

## Model Relationships

```
User
├── congregations()         → BelongsToMany (via Membership)
├── currentCongregation()   → BelongsTo Congregation
└── bookings()              → HasMany (implicit via user_id)

Congregation
├── kingdomHall()           → BelongsTo KingdomHall
├── members()              → BelongsToMany User (via Membership)
├── memberships()          → HasMany Membership
├── invitations()          → HasMany CongregationInvitation
└── (bookings via user)

KingdomHall
├── congregations()        → HasMany Congregation
└── rooms()                → HasMany Room

Room
└── kingdomHall()          → BelongsTo KingdomHall

Booking
├── congregation()         → BelongsTo Congregation
├── user()                 → BelongsTo User
├── recurrencePattern()    → BelongsTo RecurrencePattern
└── rooms()                → BelongsToMany Room (via BookingRoom)

RecurrencePattern
├── congregation()         → BelongsTo Congregation
└── bookings()             → HasMany Booking

CongregationInvitation
├── congregation()         → BelongsTo Congregation
└── inviter()              → BelongsTo User
```

## Business Rules

1. A user must belong to at least one congregation to access the app (enforced by the setup wizard)
2. Congregations must have a Kingdom Hall to access the calendar (enforced by `EnsureHasKingdomHall` middleware)
3. Room conflicts are exclusive — a room cannot be double-booked for overlapping times
4. The last superadmin in a congregation cannot be demoted or removed
5. Bookings older than 6 months are automatically deleted (daily `bookings:cleanup` command)
6. Invitation codes expire and cannot be reused after acceptance
7. Congregation slugs are auto-generated and updated when the name changes
8. A Kingdom Hall address (street, zip code, city, country) must be unique — duplicates are prevented at the database level
