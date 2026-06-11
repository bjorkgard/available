# Testing

[← Back to documentation](../README.md#documentation)

## Overview

The project uses two testing frameworks:
- **Pest v4** for backend PHP tests
- **Vitest** for frontend TypeScript/React tests

## Backend (Pest)

### Running Tests

```bash
# Run all tests
php artisan test --compact

# Run specific test file
php artisan test --compact --filter=BookingCrudTest

# Run specific test method
php artisan test --compact --filter="can create a booking"

# Run with coverage (requires Xdebug or PCOV)
php artisan test --coverage
```

### Test Structure

```
tests/
├── Feature/              # Integration tests (HTTP requests, database)
│   ├── Auth/             # Authentication flow tests
│   ├── Bookings/         # Booking CRUD, recurrence, notifications
│   ├── Congregations/    # Congregation management tests
│   ├── KingdomHalls/     # Kingdom Hall management tests
│   ├── Properties/       # Property-based/invariant tests
│   └── Settings/         # User settings tests
├── Unit/                 # Isolated unit tests
├── Pest.php              # Pest configuration & helpers
└── TestCase.php          # Base test case
```

### Conventions

- Feature tests use `RefreshDatabase` trait (via Pest config)
- Always use **factories** to create models in tests — check factory states before manual setup
- Property-based tests use `->repeat(30)` (not 100) for manageable CI memory
- Property tests validate invariants with randomized inputs
- Group tests by domain (Bookings/, Auth/, Congregations/)

### Creating Tests

```bash
# Create a feature test
php artisan make:test --pest BookingRescheduleTest

# Create a unit test
php artisan make:test --pest --unit SomeUtilityTest
```

### Factory States

Check existing factories in `database/factories/` for available states before manually configuring models. Common factories:

| Factory | States |
|---------|--------|
| `UserFactory` | default (verified email) |
| `CongregationFactory` | default (with slug, number) |
| `KingdomHallFactory` | default (with address) |
| `RoomFactory` | default (with name, sort_order) |
| `BookingFactory` | default (with times, name) |
| `RecurrencePatternFactory` | weekly, daily, monthly |
| `CongregationInvitationFactory` | pending, expired, accepted |

### Property-Based Tests

Located in `tests/Feature/Properties/`, these tests use randomized inputs to verify system invariants:

| Test | Invariant |
|------|-----------|
| `AuthorizationHierarchyTest` | Role hierarchy is always respected |
| `BookingCreationRoundTripTest` | Created bookings can always be retrieved |
| `BookingTimeConstraintTest` | Bookings always have valid time ranges |
| `RoomConflictExclusivityTest` | Rooms cannot be double-booked |
| `RecurrenceOccurrenceCountLimitTest` | Recurrence respects occurrence limits |
| `CascadeDeletionBookingsTest` | Deleting congregation cascades to bookings |
| `InvitationExpiryTest` | Expired invitations cannot be accepted |
| `LastPrivilegedRoleTest` | Last superadmin cannot be removed |
| `RegistrationUniquenessTest` | Duplicate emails are rejected |
| `SetupWizardGateTest` | Setup is only accessible without congregation |

## Frontend (Vitest)

### Running Tests

```bash
# Run all frontend tests
npx vitest run

# Run specific test file
npx vitest run resources/js/hooks/__tests__/use-keyboard-shortcuts.test.ts

# Run with coverage
npx vitest run --coverage
```

### Test Structure

```
resources/js/
├── hooks/__tests__/      # Hook unit tests
├── lib/__tests__/        # Utility function tests
└── pages/__tests__/      # Page component tests
```

### Configuration

- **Config file:** `vitest.config.ts`
- **Environment:** jsdom
- **Libraries:** @testing-library/react, @testing-library/jest-dom
- **Path aliases:** Same as tsconfig (`@/*` → `resources/js/*`)

### Writing Frontend Tests

```tsx
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';

describe('MyComponent', () => {
    it('renders correctly', () => {
        render(<MyComponent />);
        expect(screen.getByText('Hello')).toBeInTheDocument();
    });
});
```

### Property-Based Frontend Tests

The project uses **fast-check** for property-based testing in the frontend (e.g., `calendar-utils` tests).

## CI Pipeline

The full CI check runs:

```bash
composer run ci:check
```

Which executes:
1. `npm run lint:check` — ESLint (no auto-fix)
2. `npm run format:check` — Prettier check
3. `npm run types:check` — TypeScript type checking
4. `vendor/bin/pint --test` — PHP code style check
5. `php artisan test` — All backend tests
