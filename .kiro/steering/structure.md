# Project Structure

## Backend (`app/`)

```
app/
├── Actions/              # Single-purpose action classes
│   ├── Congregations/    # Congregation actions (CreateCongregation, CreateKingdomHall, DeleteCongregation, DeleteKingdomHall, MoveCongregation, SendInvitation, UpdateKingdomHall)
│   └── Fortify/          # Auth actions (CreateNewUser, ResetUserPassword)
├── Concerns/             # Reusable traits (GeneratesUniqueSlugs, GeneratesUniqueTeamSlugs, HasCongregations, HasTeams, PasswordValidationRules, ProfileValidationRules)
├── Console/Commands/     # Artisan commands
├── Data/                 # Data transfer objects (TeamPermissions, UserTeam)
├── Enums/                # PHP enums (CongregationRole, TeamPermission, TeamRole)
├── Http/
│   ├── Controllers/
│   │   ├── Congregations/ # Congregation domain (CongregationController, InvitationAcceptController, KingdomHallController, MemberController, SetupWizardController)
│   │   ├── Settings/      # Settings controllers (CongregationController, ProfileController, SecurityController, SessionController)
│   │   └── Teams/         # Teams domain (TeamController, TeamInvitationController, TeamMemberController)
│   ├── Middleware/        # EnsureCongregationMembership, EnsureHasKingdomHall, EnsureTeamMembership, HandleAppearance, HandleInertiaRequests, SetCongregationUrlDefaults, SetTeamUrlDefaults
│   ├── Requests/
│   │   ├── Settings/      # DestroySessionRequest, PasswordUpdateRequest, ProfileDeleteRequest, ProfileUpdateRequest, TwoFactorAuthenticationRequest
│   │   ├── Teams/         # AcceptTeamInvitationRequest, CreateTeamInvitationRequest, DeleteTeamRequest, SaveTeamRequest, UpdateTeamMemberRequest
│   │   └── StoreKingdomHallRequest.php
│   └── Responses/
│       ├── Concerns/      # RedirectsToCurrentCongregation, RedirectsToCurrentTeam
│       └── LoginResponse, PasskeyLoginResponse, RegisterResponse, TwoFactorLoginResponse, VerifyEmailResponse
├── Models/               # Eloquent models (Congregation, CongregationInvitation, KingdomHall, Membership, Room, User)
├── Notifications/
│   ├── Congregations/    # InvitationNotification
│   └── Teams/            # TeamInvitation
├── Policies/             # Authorization policies (CongregationPolicy, KingdomHallPolicy, MemberPolicy, TeamPolicy)
├── Providers/            # Service providers (AppServiceProvider, FortifyServiceProvider)
├── Rules/                # Custom validation rules (TeamName, UniqueTeamInvitation, ValidTeamInvitation)
└── Support/              # Utility classes (UserAgentParser)
```

## Frontend (`resources/js/`)

```
resources/js/
├── actions/              # Wayfinder-generated controller action functions (auto-generated)
├── components/           # Shared React components
│   │                     # alert-error, app-content, app-header, app-logo-icon, app-logo, app-shell,
│   │                     # app-sidebar-header, app-sidebar, appearance-tabs, breadcrumbs,
│   │                     # calendar-header, congregation-switcher, day-grid, delete-user,
│   │                     # heading, input-error, invite-member-dialog, manage-passkeys,
│   │                     # manage-two-factor, month-grid, nav-footer, nav-main, nav-user,
│   │                     # passkey-item, passkey-register, passkey-verify, password-input,
│   │                     # role-select, text-link, two-factor-recovery-codes,
│   │                     # two-factor-setup-modal, user-info, user-menu-content, week-grid
│   └── ui/               # shadcn/ui primitives (auto-generated, do not edit)
├── hooks/                # Custom React hooks
│   │                     # use-appearance, use-clipboard, use-current-url, use-flash-toast,
│   │                     # use-initials, use-keyboard-shortcuts, use-mobile-navigation,
│   │                     # use-mobile, use-responsive-view-mode, use-two-factor-auth
│   └── __tests__/        # Hook unit tests (vitest)
├── layouts/              # Layout components
│   ├── app/              # App shell layout parts (app-header-layout, app-sidebar-layout)
│   ├── auth/             # Auth layout parts (auth-card-layout, auth-simple-layout, auth-split-layout)
│   ├── settings/         # Settings layout (layout.tsx)
│   └── app-layout.tsx, auth-layout.tsx
├── lib/                  # Utility functions
│   │                     # utils.ts (cn() helper), calendar-utils.ts (date arithmetic),
│   │                     # locale.ts (APP_LOCALE constant)
│   └── __tests__/        # Library unit tests (vitest)
├── pages/                # Inertia page components (maps to routes)
│   ├── __tests__/        # Page component tests (vitest)
│   ├── auth/             # Auth pages (accept-invitation, confirm-password, forgot-password, login, register, reset-password, two-factor-challenge, verify-email)
│   ├── congregations/    # Congregation pages (edit, index, kingdom-hall/show, members/index)
│   ├── settings/         # Settings pages (appearance, profile, security, sessions)
│   ├── setup/            # Setup wizard (index)
│   └── calendar.tsx, welcome.tsx
├── routes/               # Wayfinder-generated named route functions (auto-generated)
├── types/                # TypeScript type definitions (auth, congregations, navigation, ui, global.d.ts, vite-env.d.ts)
│   └── index.ts          # Re-exports all types from domain files
└── wayfinder/            # Wayfinder internals (auto-generated)
```

## Routes

```
routes/
├── web.php               # Main web routes (welcome, setup, congregation-scoped under {current_congregation} prefix with calendar as default, invitation acceptance)
├── settings.php          # Settings routes (profile, security, password, appearance, sessions, congregations)
└── console.php           # Console/scheduled commands
```

## Database

```
database/
├── factories/            # Model factories (CongregationFactory, CongregationInvitationFactory, KingdomHallFactory, RoomFactory, UserFactory)
├── migrations/           # Chronological migrations
└── seeders/              # Database seeders
```

## Tests

```
tests/
├── Feature/              # Feature/integration tests (grouped by domain)
│   ├── Auth/             # Authentication tests (Authentication, EmailVerification, PasswordConfirmation, PasswordReset, Registration, TwoFactorChallenge, VerificationNotification)
│   ├── Congregations/    # Congregation management tests (CongregationManagement, CongregationColor, Invitation, RoleAuthorization, SetupWizard)
│   ├── KingdomHalls/     # Kingdom Hall management tests
│   ├── Properties/       # Property/invariant tests (CongregationNumberValidation, DeletionCascade, DuplicateInvitation, InvitationExpiry, LastPrivilegedRole, MovePreservation, RegistrationUniqueness, RegistrationValidation, RoleScopeEnforcement, RoomGeneration, SessionOrderingProperty, SessionTerminationProperty, SetupWizardGate)
│   ├── Settings/         # Settings tests (ProfileUpdate, Security, SessionController)
│   └── (root)           # CalendarTest, MemberPolicyTest, MoveCongregationTest, SendInvitationTest
├── Unit/                 # Unit tests (UserAgentParserTest, UserAgentParserPropertyTest)
├── Pest.php              # Pest configuration
└── TestCase.php          # Base test case
```

## Frontend Tests (Vitest)

```
resources/js/
├── hooks/__tests__/      # use-keyboard-shortcuts tests (unit + property-based)
├── lib/__tests__/        # calendar-utils property tests
└── pages/__tests__/      # Calendar page component tests
```

## Conventions

- Controllers are grouped by domain in subdirectories (e.g., `Http/Controllers/Congregations/`)
- Pages mirror the route structure (e.g., `pages/congregations/edit.tsx` for the congregation edit route)
- Routes are congregation-scoped: most authenticated routes live under `/{current_congregation}/...`
- The calendar page is the default landing page after login (replaces the old dashboard)
- Congregation model uses `slug` as route key (`getRouteKeyName()` returns `'slug'`)
- CongregationInvitation uses `code` as route key
- Wayfinder generates typed route/action helpers — import from `@/actions/` or `@/routes/`
- Types are split by domain in `resources/js/types/` and re-exported from `index.ts`
- Use `Inertia::render('folder/page', [...])` to render pages (lowercase, slash-separated)
- A `Teams` abstraction layer exists as the underlying infrastructure for the congregation system (controllers, middleware, policies, rules, DTOs)
- The shared `currentCongregation` Inertia prop eager-loads `kingdomHall.rooms`
