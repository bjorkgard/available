# Authentication & Authorization

[← Back to documentation](../README.md#documentation)

## Authentication (Laravel Fortify)

The app uses **Laravel Fortify** as the authentication backend with custom Inertia.js views rendered as React pages.

### Supported Auth Methods

| Method | Description |
|--------|-------------|
| Email/Password | Standard login with bcrypt hashing (12 rounds) |
| Passkeys (WebAuthn) | Passwordless login via biometrics/hardware keys |
| Two-Factor (TOTP) | Time-based one-time passwords with recovery codes |

### Auth Pages

| Route | Page Component | Purpose |
|-------|---------------|---------|
| `/login` | `auth/login` | Email/password + passkey login |
| `/register` | `auth/register` | New account registration |
| `/forgot-password` | `auth/forgot-password` | Request password reset link |
| `/reset-password/{token}` | `auth/reset-password` | Set new password |
| `/email/verify` | `auth/verify-email` | Email verification notice |
| `/two-factor-challenge` | `auth/two-factor-challenge` | 2FA code entry |
| `/confirm-password` | `auth/confirm-password` | Re-confirm password for sensitive actions |

### Post-Login Flow

Custom response classes redirect users after authentication:

1. **LoginResponse / PasskeyLoginResponse / TwoFactorLoginResponse** — redirects to `/{current_congregation}/calendar` if the user has a congregation, otherwise to `/setup`
2. **RegisterResponse** — redirects to `/setup` (new users always go through the wizard)
3. **VerifyEmailResponse** — redirects to the intended URL or calendar

### Rate Limiting

| Endpoint | Limit | Key |
|----------|-------|-----|
| Login | 5/min | `email|ip` |
| Two-Factor | 5/min | `session login.id` |
| Passkeys | 10/min | `credential.id|ip` |
| Password change | 6/min | Throttle middleware |

### Password Requirements (Production)

- Minimum 12 characters
- Mixed case
- Letters, numbers, and symbols
- Not compromised (checked against haveibeenpwned)

## Authorization

### Role Hierarchy

The app uses a three-tier role system within each congregation:

```
Superadmin (level 3) — Full control over Kingdom Hall, rooms, and all bookings
     │
     ▼
  Admin (level 2) — Manage congregation members, edit congregation bookings
     │
     ▼
  Member (level 1) — Create and manage own bookings
```

Roles are scoped per congregation — a user can be Superadmin in one congregation and Member in another.

### Booking Policy

| Action | Who Can Do It |
|--------|--------------|
| **View** | Any user in a congregation sharing the same Kingdom Hall |
| **Create** | Any member of the target congregation, OR a Superadmin in any congregation sharing the same Kingdom Hall |
| **Update** | Booking owner, Admin in the booking's congregation, OR Superadmin in the same Kingdom Hall |
| **Delete** | Same as Update |

### Route-Level Authorization

Middleware enforces access at the route level:

```php
// Any authenticated member of the congregation
EnsureCongregationMembership::class

// At least Admin role required
EnsureCongregationMembership::class.':admin'

// Superadmin only
EnsureCongregationMembership::class.':superadmin'
```

### Policies

| Policy | Model | Key Rules |
|--------|-------|-----------|
| `BookingPolicy` | Booking | Owner/Admin/Superadmin hierarchy |
| `CongregationPolicy` | Congregation | Role-based management |
| `KingdomHallPolicy` | KingdomHall | Superadmin only |
| `MemberPolicy` | Membership | Cannot demote last superadmin |
| `TeamPolicy` | Team (infra) | Underlying team permissions |

## Security Features

- **Password confirmation** required for accessing security settings (via `RequirePassword` middleware)
- **Session management** — users can view all active sessions and revoke others
- **CSRF protection** on all state-changing requests
- **Immutable dates** (CarbonImmutable) to prevent accidental mutation
- **Destructive command protection** in production
