# Project Structure

## Backend (`app/`)

```
app/
├── Actions/              # Single-purpose action classes
│   ├── Congregations/    # Congregation actions (CreateCongregation, CreateKingdomHall, DeleteCongregation, DeleteKingdomHall, MoveCongregation, SendInvitation, UpdateKingdomHall)
│   └── Fortify/          # Auth actions (CreateNewUser, ResetUserPassword)
├── Concerns/             # Reusable traits (HasCongregations, GeneratesUniqueSlugs, PasswordValidationRules, ProfileValidationRules)
├── Console/Commands/     # Artisan commands
├── Enums/                # PHP enums (CongregationRole)
├── Http/
│   ├── Controllers/
│   │   ├── Congregations/ # Congregation domain (CongregationController, InvitationAcceptController, KingdomHallController, MemberController, SetupWizardController)
│   │   └── Settings/      # Settings controllers (ProfileController, SecurityController, CongregationController)
│   ├── Middleware/        # EnsureCongregationMembership, EnsureHasKingdomHall, SetCongregationUrlDefaults, HandleInertiaRequests, HandleAppearance
│   ├── Requests/          # Form request validation classes
│   └── Responses/         # Custom response classes
├── Models/               # Eloquent models (User, Congregation, CongregationInvitation, KingdomHall, Membership, Room)
├── Notifications/
│   └── Congregations/    # Congregation notification classes
├── Policies/             # Authorization policies (CongregationPolicy, KingdomHallPolicy, MemberPolicy)
├── Providers/            # Service providers (App, Fortify)
└── Rules/                # Custom validation rules
```

## Frontend (`resources/js/`)

```
resources/js/
├── actions/              # Wayfinder-generated controller action functions (auto-generated)
├── components/           # Shared React components
│   │                     # congregation-switcher, invite-member-dialog, role-select,
│   │                     # manage-passkeys, manage-two-factor, password-input, etc.
│   └── ui/               # shadcn/ui primitives (auto-generated, do not edit)
├── hooks/                # Custom React hooks (use-appearance, use-clipboard, use-current-url, use-flash-toast, use-initials, use-mobile, use-two-factor-auth)
├── layouts/              # Layout components
│   ├── app/              # App shell layout parts (app-header-layout, app-sidebar-layout)
│   ├── auth/             # Auth layout parts (auth-card-layout, auth-simple-layout, auth-split-layout)
│   └── settings/         # Settings layout
├── lib/                  # Utility functions (utils.ts with cn() helper)
├── pages/                # Inertia page components (maps to routes)
│   ├── auth/             # Auth pages (login, register, forgot-password, reset-password, confirm-password, verify-email, two-factor-challenge)
│   ├── congregations/    # Congregation pages (index, edit, kingdom-hall/show, members/index)
│   ├── settings/         # Settings pages (profile, security, appearance)
│   └── setup/            # Setup wizard (index)
├── routes/               # Wayfinder-generated named route functions (auto-generated)
├── types/                # TypeScript type definitions (auth, congregations, navigation, ui)
│   └── index.ts          # Re-exports all types from domain files
└── wayfinder/            # Wayfinder internals (auto-generated)
```

## Routes

```
routes/
├── web.php               # Main web routes (congregation-scoped under {current_congregation} prefix)
├── settings.php          # Settings routes (profile, security, appearance, congregations)
└── console.php           # Console/scheduled commands
```

## Database

```
database/
├── factories/            # Model factories (UserFactory, CongregationFactory, CongregationInvitationFactory, KingdomHallFactory, RoomFactory)
├── migrations/           # Chronological migrations
└── seeders/              # Database seeders
```

## Tests

```
tests/
├── Feature/              # Feature/integration tests (grouped by domain)
│   ├── Auth/             # Authentication tests
│   ├── Congregations/    # Congregation management tests
│   ├── KingdomHalls/     # Kingdom Hall management tests
│   ├── Properties/       # Property/invariant tests (validation, cascades, uniqueness)
│   └── Settings/         # Settings tests (profile, security)
├── Unit/                 # Unit tests
├── Pest.php              # Pest configuration
└── TestCase.php          # Base test case
```

## Conventions

- Controllers are grouped by domain in subdirectories (e.g., `Http/Controllers/Congregations/`)
- Pages mirror the route structure (e.g., `pages/congregations/edit.tsx` for the congregation edit route)
- Routes are congregation-scoped: most authenticated routes live under `/{current_congregation}/...`
- Congregation model uses `slug` as route key (`getRouteKeyName()` returns `'slug'`)
- CongregationInvitation uses `code` as route key
- Wayfinder generates typed route/action helpers — import from `@/actions/` or `@/routes/`
- Types are split by domain in `resources/js/types/` and re-exported from `index.ts`
- Use `Inertia::render('folder/page', [...])` to render pages (lowercase, slash-separated)
