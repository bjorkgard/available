# Database Schema

[← Back to documentation](../README.md#documentation)

## Overview

The application uses **SQLite** as the default database. All primary keys are **UUID v7** (time-ordered), and every model uses the `HasUuids` Eloquent trait.

## Entity Relationship Diagram

```
┌──────────────┐       ┌──────────────────┐       ┌──────────────┐
│     User     │◄──────┤   Membership     ├──────►│ Congregation │
│              │  M:N   │  (pivot table)   │       │              │
│ id (uuid)    │       │ congregation_id   │       │ id (uuid)    │
│ name         │       │ user_id           │       │ name         │
│ email        │       │ role (enum)       │       │ slug (unique)│
│ password     │       └──────────────────┘       │ cong_number  │
│ current_     │                                   │ kingdom_     │
│  congregation│                                   │  hall_id (FK)│
│  _id (FK)    │                                   │ color        │
└──────────────┘                                   └───────┬──────┘
                                                           │ N:1
                                                   ┌───────▼──────┐
                                                   │ KingdomHall  │
                                                   │              │
                                                   │ id (uuid)    │
                                                   │ street_address│
                                                   │ zip_code     │
                                                   │ city         │
                                                   │ country      │
                                                   │ number_of_   │
                                                   │  rooms       │
                                                   └───────┬──────┘
                                                           │ 1:N
                                                   ┌───────▼──────┐
                                                   │     Room     │
                                                   │              │
                                                   │ id (uuid)    │
                                                   │ kingdom_     │
                                                   │  hall_id (FK)│
                                                   │ name         │
                                                   │ sort_order   │
                                                   └──────────────┘
```

```
┌──────────────┐       ┌──────────────────┐       ┌──────────────┐
│   Booking    │◄──────┤   BookingRoom    ├──────►│     Room     │
│              │  M:N   │  (pivot table)   │       │              │
│ id (uuid)    │       │ booking_id (FK)   │       │              │
│ congregation_│       │ room_id (FK)      │       │              │
│  id (FK)     │       └──────────────────┘       └──────────────┘
│ user_id (FK) │
│ name         │       ┌──────────────────┐
│ starts_at    │──────►│RecurrencePattern │
│ ends_at      │  N:1  │                  │
│ recurrence_  │       │ id (uuid)        │
│  pattern_id  │       │ congregation_id  │
│ is_exception │       │ frequency (enum) │
│ original_    │       │ end_date         │
│  starts_at   │       │ end_count        │
└──────────────┘       └──────────────────┘
```

## Tables

### `users`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `name` | string | |
| `email` | string | Unique |
| `email_verified_at` | timestamp | Nullable |
| `password` | string | Hashed |
| `current_congregation_id` | uuid | FK → congregations, nullable |
| `two_factor_secret` | text | Encrypted, nullable |
| `two_factor_recovery_codes` | text | Encrypted, nullable |
| `two_factor_confirmed_at` | timestamp | Nullable |
| `remember_token` | string | |
| `timestamps` | | created_at, updated_at |

### `congregations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `name` | string | |
| `slug` | string | Unique, auto-generated, used for route binding |
| `congregation_number` | string(20) | Unique |
| `kingdom_hall_id` | uuid | FK → kingdom_halls, nullable, nullOnDelete |
| `color` | string | Nullable, hex color for calendar display |
| `timestamps` | | |
| `deleted_at` | timestamp | Soft deletes |

### `congregation_members` (Membership pivot)

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `congregation_id` | uuid | FK → congregations, cascadeOnDelete |
| `user_id` | uuid | FK → users, cascadeOnDelete |
| `role` | string | Enum: superadmin, admin, member |
| `timestamps` | | |
| | | Unique constraint on [congregation_id, user_id] |

### `congregation_invitations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `code` | string(64) | Unique, auto-generated, used for route binding |
| `congregation_id` | uuid | FK → congregations, cascadeOnDelete |
| `name` | string(255) | Invitee's name |
| `email` | string(255) | Invitee's email |
| `role` | string | Enum: superadmin, admin, member |
| `invited_by` | uuid | FK → users, cascadeOnDelete |
| `expires_at` | timestamp | Nullable |
| `accepted_at` | timestamp | Nullable |
| `timestamps` | | |

### `kingdom_halls`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `street_address` | string(255) | |
| `zip_code` | string(20) | |
| `city` | string(100) | |
| `country` | string(100) | Default: 'Sverige' |
| `number_of_rooms` | integer | |
| `timestamps` | | |
| | | Unique constraint on [street_address, zip_code, city, country] |

### `rooms`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `kingdom_hall_id` | uuid | FK → kingdom_halls, cascadeOnDelete |
| `name` | string(255) | |
| `sort_order` | integer | Display ordering |
| `timestamps` | | |

### `bookings`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `congregation_id` | uuid | FK → congregations, cascadeOnDelete |
| `user_id` | uuid | FK → users, nullable, nullOnDelete |
| `name` | string(255) | Booking title |
| `starts_at` | datetime | Start time |
| `ends_at` | datetime | End time |
| `recurrence_pattern_id` | uuid | FK → recurrence_patterns, nullable, nullOnDelete |
| `is_exception` | boolean | Whether this is an exception to a recurrence |
| `original_starts_at` | datetime | Nullable, original time before rescheduling |
| `timestamps` | | |

### `booking_room` (pivot)

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `booking_id` | uuid | FK → bookings, cascadeOnDelete |
| `room_id` | uuid | FK → rooms, cascadeOnDelete |
| | | Unique constraint on [booking_id, room_id] |

### `recurrence_patterns`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid | Primary key |
| `congregation_id` | uuid | FK → congregations, cascadeOnDelete |
| `frequency` | string(10) | Enum: daily, weekly, monthly, yearly |
| `end_date` | date | Nullable, end by date |
| `end_count` | integer | Nullable, end after N occurrences |
| `timestamps` | | |

## Route Model Binding

| Model | Route Key | Example |
|-------|-----------|---------|
| `Congregation` | `slug` | `/my-congregation/calendar` |
| `CongregationInvitation` | `code` | `/invitations/abc123.../accept` |
| All others | `id` (uuid) | `/bookings/019...` |

## Conventions

- Soft deletes on `Congregation` only (preserves history)
- Cascade deletes on most foreign keys (removing a congregation removes its bookings, memberships, invitations)
- `nullOnDelete` on user references (bookings survive user deletion)
- No auto-incrementing integers anywhere
- All timestamps use `CarbonImmutable` (configured in AppServiceProvider)
