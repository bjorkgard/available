# Available

A Kingdom Hall room-scheduling app for congregations to coordinate shared spaces. Congregations that share a Kingdom Hall can see room availability at a glance and book slots without conflicts.

## Core Functions

### Authentication

Full authentication system with email/password login, registration, email verification, password reset, two-factor authentication (TOTP with recovery codes), and passkey/WebAuthn support.

### Congregation Management

- **Setup wizard** — new users create or join a congregation on first login
- **Member invitations** — admins invite users by email with a specific role (Superadmin, Admin, Member)
- **Role-based access** — actions are scoped by membership role within each congregation
- **Congregation switching** — users who belong to multiple congregations can switch between them
- **Congregation settings** — admins can edit congregation details; members can leave or move to another congregation

### Kingdom Hall Management

- **Address & rooms** — superadmins manage the physical building's address and define bookable rooms
- **Multi-congregation sharing** — multiple congregations can be linked to the same Kingdom Hall
- **Room generation** — rooms are automatically created when a Kingdom Hall is set up

### User Settings

- **Profile** — update name, email, and delete account
- **Security** — change password, manage passkeys, configure two-factor authentication
- **Sessions** — view active browser sessions (device, browser, IP, last activity) and revoke others
- **Appearance** — light/dark/system theme preference
- **Congregations** — view and manage congregation memberships

### Room Scheduling (Planned)

The dashboard currently serves as a placeholder. The scheduling feature — viewing room availability, booking time slots, and resolving conflicts — is the next major milestone.

## Tech Stack

- **Backend:** PHP 8.5, Laravel 13, SQLite
- **Frontend:** React 19, TypeScript, Inertia.js v3, Tailwind CSS v4, shadcn/ui
- **Auth:** Laravel Fortify, passkeys (`@laravel/passkeys`), TOTP 2FA
- **Tooling:** Vite 8, Pest v4, Laravel Pint, ESLint, Prettier

## Getting Started

```bash
# Install dependencies and set up the database
composer run setup

# Start the development server (Laravel, queue, logs, and Vite)
composer run dev
```

The app will be available at `https://available.test` (via Laravel Herd) or `http://localhost:8000`.

## Development

```bash
# Run tests
php artisan test --compact

# Lint PHP
vendor/bin/pint --dirty --format agent

# Lint & format JS/TS
npm run lint
npm run format

# Type check frontend
npm run types:check
```

## License

MIT
