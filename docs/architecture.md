# Architecture

[← Back to documentation](../README.md#documentation)

## High-Level Overview

Available is a full-stack monolithic application built with:

```
┌─────────────────────────────────────────────────────────┐
│                      Browser (SPA)                       │
│   React 19 + Inertia.js v3 + Tailwind CSS v4           │
│   shadcn/ui components, Laravel Echo (WebSocket client) │
└────────────────────────┬────────────────────────────────┘
                         │ Inertia Protocol (JSON over HTTP)
                         │ + WebSocket (Reverb)
┌────────────────────────▼────────────────────────────────┐
│                   Laravel 13 Backend                     │
│   Controllers → Actions → Models → Database             │
│   Fortify (auth) · Reverb (WS) · Wayfinder (routes)   │
└────────────────────────┬────────────────────────────────┘
                         │
              ┌──────────▼──────────┐
              │   SQLite Database    │
              └─────────────────────┘
```

## Request Lifecycle

1. **Browser** makes a request (initial page load or Inertia XHR)
2. **Middleware** handles authentication, congregation membership, and Inertia headers
3. **Controller** orchestrates the request, delegates to **Actions** for complex operations
4. **Actions** encapsulate business logic (single-purpose classes)
5. **Models** interact with the database via Eloquent
6. **Inertia** serializes props and renders the React page component
7. **React** hydrates on the client and manages UI state

## Key Architectural Decisions

### Inertia.js (Server-Driven SPA)

No API layer — the backend serves Inertia responses that the React frontend consumes directly. This eliminates the need for separate API routes, serializers, or client-side state management for server data.

### Action Classes

Business logic lives in `app/Actions/`, not controllers. Controllers stay thin — they validate input and delegate to actions. This keeps logic testable, reusable, and decoupled from HTTP concerns.

```
app/Actions/
├── Bookings/         CreateBooking, UpdateBooking, DeleteBooking, RescheduleBooking, TransferBookings
├── Congregations/    CreateCongregation, CreateKingdomHall, DeleteCongregation, etc.
└── Fortify/          CreateNewUser, ResetUserPassword
```

### Congregation-Scoped Routes

Most authenticated routes live under `/{current_congregation}/...`. The `EnsureCongregationMembership` middleware resolves the congregation from the URL slug and verifies the user belongs to it.

### Teams Infrastructure

A generic `Teams` abstraction layer (controllers, middleware, policies, DTOs) provides the underlying infrastructure. The `Congregation` model is the public-facing domain concept built on top of it.

### UUID Primary Keys

All models use UUID v7 (time-ordered) primary keys via the `HasUuids` trait. No auto-incrementing integers are ever exposed.

## Layers

### Controllers (`app/Http/Controllers/`)

Grouped by domain:
- **Congregations/** — BookingController, CongregationController, KingdomHallController, MemberController, SetupWizardController, InvitationAcceptController, RoomController
- **Settings/** — ProfileController, SecurityController, SessionController, CongregationController
- **Teams/** — TeamController, TeamInvitationController, TeamMemberController

### Middleware (`app/Http/Middleware/`)

| Middleware | Purpose |
|-----------|---------|
| `EnsureCongregationMembership` | Verifies user belongs to the URL congregation (supports role param) |
| `EnsureHasKingdomHall` | Ensures the congregation has a Kingdom Hall configured |
| `HandleInertiaRequests` | Shares global Inertia props (auth, congregation, flash) |
| `HandleAppearance` | Passes theme preference cookie to Inertia |
| `SetCongregationUrlDefaults` | Sets URL defaults for congregation-scoped route generation |

### Providers

- **AppServiceProvider** — registers policies, configures immutable dates, password rules, and destructive command protection
- **FortifyServiceProvider** — configures auth actions, Inertia views, and rate limiters

### Events & Broadcasting

Events are broadcast on a private `kingdom-hall.{id}` channel so all users sharing a Kingdom Hall receive real-time updates:

| Event | Trigger |
|-------|---------|
| `BookingCreated` | A new booking (or set of recurring bookings) is created |
| `BookingUpdated` | A booking is modified |
| `BookingDeleted` | A booking is removed |

The frontend listens via Laravel Echo + Pusher.js and updates the calendar in real-time with toast notifications.

## Frontend Architecture

### Page Components

Each page is a React component in `resources/js/pages/` that maps 1:1 to a route. Pages receive typed props from the backend via Inertia.

### Layouts

- **AppLayout** — the main shell with sidebar/header, used for authenticated pages
- **AuthLayout** — centered card layout for login/register flows
- **SettingsLayout** — left nav + content area for settings pages

### Component Organization

```
resources/js/components/
├── ui/              # shadcn/ui primitives (auto-generated, never edit)
├── booking-*.tsx    # Booking domain components
├── calendar-*.tsx   # Calendar domain components
├── congregation-*   # Congregation domain components
└── app-*, nav-*     # Shell/navigation components
```

### Typed Route Generation (Wayfinder)

The `@laravel/vite-plugin-wayfinder` auto-generates TypeScript functions for all Laravel routes. Import from:
- `@/actions/` — controller action functions (POST, PUT, DELETE)
- `@/routes/` — named route URL generators (GET)

### State Management

No Redux or Zustand — state is managed via:
- **Inertia page props** for server data
- **React state/refs** for local UI state
- **Laravel Echo** for real-time updates (merged into page state)
- **Inertia's `useForm`** for form submissions
