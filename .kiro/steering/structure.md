# Project Structure

## Backend (`app/`)

```
app/
├── Actions/          # Single-purpose action classes (Fortify auth, Teams)
│   ├── Fortify/      # Auth actions (CreateNewUser, ResetUserPassword, etc.)
│   └── Teams/        # Team actions (CreateTeam, etc.)
├── Concerns/         # Reusable traits (HasTeams, PasswordValidationRules)
├── Console/Commands/ # Artisan commands
├── Data/             # Data transfer objects (TeamPermissions, UserTeam)
├── Enums/            # PHP enums (TeamPermission, TeamRole)
├── Http/
│   ├── Controllers/  # Grouped by domain (Settings/, Teams/)
│   ├── Middleware/
│   ├── Requests/     # Form request validation classes
│   └── Responses/    # Custom response classes
├── Models/           # Eloquent models (User, Team, Membership, TeamInvitation)
├── Notifications/    # Notification classes grouped by domain
├── Policies/         # Authorization policies
├── Providers/        # Service providers (App, Fortify)
└── Rules/            # Custom validation rules
```

## Frontend (`resources/js/`)

```
resources/js/
├── actions/          # Wayfinder-generated controller action functions (auto-generated)
├── components/       # Shared React components
│   └── ui/           # shadcn/ui primitives (auto-generated, do not edit)
├── hooks/            # Custom React hooks
├── layouts/          # Layout components
│   ├── app/          # App shell layout parts (sidebar, header)
│   ├── auth/         # Auth layout parts
│   └── settings/     # Settings layout parts
├── lib/              # Utility functions (utils.ts with cn() helper)
├── pages/            # Inertia page components (maps to routes)
│   ├── auth/         # Auth pages (login, register, forgot-password, etc.)
│   ├── settings/     # Settings pages (profile, security, appearance)
│   └── teams/        # Team management pages
├── routes/           # Wayfinder-generated named route functions (auto-generated)
├── types/            # TypeScript type definitions
│   └── index.ts      # Re-exports all types from domain files
└── wayfinder/        # Wayfinder internals (auto-generated)
```

## Routes

```
routes/
├── web.php           # Main web routes (team-scoped under {current_team} prefix)
├── settings.php      # Settings routes (profile, security)
└── console.php       # Console/scheduled commands
```

## Database

```
database/
├── factories/        # Model factories (User, Team, TeamInvitation)
├── migrations/       # Chronological migrations
└── seeders/          # Database seeders
```

## Tests

```
tests/
├── Feature/          # Feature/integration tests (grouped by domain)
│   ├── Auth/
│   ├── Settings/
│   └── Teams/
├── Unit/             # Unit tests
├── Pest.php          # Pest configuration
└── TestCase.php      # Base test case
```

## Conventions

- Controllers are grouped by domain in subdirectories (e.g., `Http/Controllers/Teams/`)
- Pages mirror the route structure (e.g., `pages/teams/edit.tsx` for the team edit route)
- Routes are team-scoped: most authenticated routes live under `/{current_team}/...`
- Wayfinder generates typed route/action helpers — import from `@/actions/` or `@/routes/`
- Types are split by domain in `resources/js/types/` and re-exported from `index.ts`
- Use `Inertia::render('folder/page', [...])` to render pages (lowercase, slash-separated)
