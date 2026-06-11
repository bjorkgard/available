# Routes & API

[← Back to documentation](../README.md#documentation)

## Route Structure

Most routes are server-rendered via Inertia (returning full page components). However, some routes are **JSON endpoints** consumed by the frontend via XHR — notably the booking CRUD routes, which return `JsonResponse` and are called by the calendar's JavaScript code rather than visited as page navigations. These are marked in the tables below.

Routes are defined in:

- `routes/web.php` — main application routes
- `routes/settings.php` — user settings routes
- `routes/channels.php` — WebSocket channel authorization
- `routes/console.php` — scheduled commands

## Public Routes

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/` | `home` | Inertia (welcome) |
| GET | `/invitations/{invitation}/accept` | `invitations.accept` | InvitationAcceptController@accept |
| POST | `/invitations/{invitation}/accept` | `invitations.accept.store` | InvitationAcceptController@store |

## Auth Routes (Fortify)

These are auto-registered by Laravel Fortify:

| Method | URI | Purpose |
|--------|-----|---------|
| GET/POST | `/login` | Login |
| POST | `/logout` | Logout |
| GET/POST | `/register` | Registration |
| GET/POST | `/forgot-password` | Request reset link |
| GET/POST | `/reset-password` | Reset password |
| GET/POST | `/email/verify` | Email verification |
| GET/POST | `/two-factor-challenge` | 2FA challenge |
| GET/POST | `/user/confirm-password` | Confirm password |
| POST | `/user/two-factor-authentication` | Enable 2FA |
| DELETE | `/user/two-factor-authentication` | Disable 2FA |
| GET | `/user/two-factor-qr-code` | Get QR code |
| GET | `/user/two-factor-recovery-codes` | Get recovery codes |
| POST | `/user/two-factor-recovery-codes` | Regenerate codes |
| GET | `/user/confirmed-two-factor-authentication` | Confirm 2FA |

## Setup Routes (auth required)

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/setup` | `setup.show` | SetupWizardController@show |
| POST | `/setup` | `setup.store` | SetupWizardController@store |

## Congregation-Scoped Routes

All routes below are prefixed with `/{current_congregation}` and require:
- Authentication
- Email verification
- Congregation membership
- Kingdom Hall configured

### Bookings (JSON endpoints)

These routes return `JsonResponse` and are consumed via XHR by the calendar frontend — they are **not** Inertia page visits.

| Method | URI | Name | Controller | Notes |
|--------|-----|------|-----------|-------|
| GET | `/calendar` | `calendar` | Inertia page | Renders the calendar page component |
| GET | `/bookings` | `bookings.index` | BookingController@index | JSON. Query params: `from` (date, required), `to` (date, required) |
| POST | `/bookings` | `bookings.store` | BookingController@store | JSON 201. Body: `name`, `start_date`/`start_time` or `starts_at`, `end_date`/`end_time` or `ends_at`, `room_ids[]`, optional `congregation_id`, optional recurrence fields |
| GET | `/bookings/{booking}` | `bookings.show` | BookingController@show | JSON. Returns single booking resource |
| PUT | `/bookings/{booking}` | `bookings.update` | BookingController@update | JSON. Body: `name`, `starts_at`, `ends_at`, `room_ids[]`, `scope` |
| PATCH | `/bookings/{booking}/reschedule` | `bookings.reschedule` | BookingController@reschedule | JSON. Body: `starts_at` (date, required), `scope` (`this_only` or `this_and_future`) |
| DELETE | `/bookings/{booking}` | `bookings.destroy` | BookingController@destroy | JSON 204. Body: `scope` (`this_only`, `all_future`, or `all`) |

### Members (Inertia pages, admin+ required)

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/members` | `members.index` | MemberController@index |
| POST | `/members/invite` | `members.invite` | MemberController@invite |
| DELETE | `/members/invitations/{invitation}` | `members.invitations.destroy` | MemberController@destroyInvitation |
| PUT | `/members/{member}` | `members.update` | MemberController@update |
| DELETE | `/members/{member}` | `members.destroy` | MemberController@destroy |

### Congregation Settings (Inertia pages, admin+ required)

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/congregation` | `congregation.edit` | CongregationController@edit |
| PATCH | `/congregation` | `congregation.update` | CongregationController@update |
| PATCH | `/congregation/color` | `congregation.update-color` | CongregationController@updateColor |

### Kingdom Hall

| Method | URI | Name | Controller | Access |
|--------|-----|------|-----------|--------|
| GET | `/kingdom-hall` | `kingdom-hall.show` | KingdomHallController@show | Any member |
| PUT | `/kingdom-hall` | `kingdom-hall.update` | KingdomHallController@update | Superadmin |
| DELETE | `/kingdom-hall` | `kingdom-hall.destroy` | KingdomHallController@destroy | Superadmin |
| POST | `/kingdom-hall/congregations` | `kingdom-hall.add-congregation` | KingdomHallController@addCongregation | Superadmin |
| DELETE | `/kingdom-hall/congregations/{congregation}` | `kingdom-hall.congregations.destroy` | KingdomHallController@destroyCongregation | Superadmin |
| POST | `/kingdom-hall/rooms` | `kingdom-hall.rooms.store` | RoomController@store | Superadmin |
| PUT | `/kingdom-hall/rooms/{room}` | `kingdom-hall.rooms.update` | RoomController@update | Superadmin |
| DELETE | `/kingdom-hall/rooms/{room}` | `kingdom-hall.rooms.destroy` | RoomController@destroy | Superadmin |

### Congregation Actions

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| POST | `/move` | `congregation.move` | CongregationController@move |
| DELETE | `/` | `congregation.destroy` | CongregationController@destroy |

## Settings Routes (auth required)

| Method | URI | Name | Controller |
|--------|-----|------|-----------|
| GET | `/settings/profile` | `profile.edit` | ProfileController@edit |
| PATCH | `/settings/profile` | `profile.update` | ProfileController@update |
| DELETE | `/settings/profile` | `profile.destroy` | ProfileController@destroy |
| GET | `/settings/security` | `security.edit` | SecurityController@edit |
| PUT | `/settings/password` | `user-password.update` | SecurityController@update |
| GET | `/settings/appearance` | `appearance.edit` | Inertia (settings/appearance) |
| GET | `/settings/sessions` | `sessions.edit` | SessionController@edit |
| DELETE | `/settings/sessions` | `sessions.destroy` | SessionController@destroy |
| GET | `/settings/congregations` | `congregations.index` | CongregationController@index |

## WebSocket Channels

| Channel | Authorization |
|---------|--------------|
| `private-kingdom-hall.{kingdomHallId}` | User belongs to a congregation in that Kingdom Hall |

## Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `bookings:cleanup` | Daily | Removes bookings older than 6 months |
