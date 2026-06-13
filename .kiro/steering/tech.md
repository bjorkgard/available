# Tech Stack

## Backend

- PHP 8.5, Laravel 13
- Inertia.js v3 (server-side adapter: `inertiajs/inertia-laravel`)
- Laravel Fortify (authentication with passkeys and 2FA)
- Laravel Reverb (WebSocket server for real-time broadcasting)
- Laravel Wayfinder (typed route generation)
- Laravel Chisel (AI tooling)
- SQLite (default database)
- Pest v4 (testing framework)
- Laravel Pint (code formatter, `laravel` preset)

## Frontend

- React 19 with TypeScript (strict mode)
- Inertia.js v3 React client (`@inertiajs/react`) + `@inertiajs/vite` (SSR plugin)
- Tailwind CSS v4 (via `@tailwindcss/vite` plugin)
- shadcn/ui (new-york style, Radix primitives, lucide icons)
- Vite 8 with `laravel-vite-plugin`, `@inertiajs/vite`, `@vitejs/plugin-react`
- React Compiler (`babel-plugin-react-compiler`)
- Class Variance Authority + clsx + tailwind-merge for styling utilities
- Sonner (toast notifications)
- Laravel Echo + Pusher.js (WebSocket client for real-time updates)
- `date-fns` (date formatting with sv locale for shadcn Calendar)
- `@laravel/passkeys` (WebAuthn/passkey support)
- `input-otp` (OTP input for 2FA)
- Motion (`motion/react`) for scroll-linked and reveal animations (respects `prefers-reduced-motion`)
- `tw-animate-css` (Tailwind animation utilities)
- ESLint 9 + Prettier 3
- Vitest (frontend unit/component testing)

## Common Commands

```bash
# Development server (runs Laravel, queue, pail logs, Vite, and Reverb concurrently)
composer run dev

# Build frontend assets
npm run build

# Run all backend tests
php artisan test --compact

# Run specific backend test
php artisan test --compact --filter=TestName

# Run all frontend tests
npx vitest run

# Run specific frontend test
npx vitest run resources/js/path/to/test.ts

# Lint PHP (auto-fix)
vendor/bin/pint --dirty --format agent

# Lint JS/TS (auto-fix)
npm run lint

# Format JS/TS
npm run format

# Type check frontend
npm run types:check

# Generate Wayfinder routes
# (automatic via vite plugin, or manually via artisan)
php artisan wayfinder:generate

# Create a new test
php artisan make:test --pest TestName

# Create a new model (with factory, migration, etc.)
php artisan make:model ModelName --help
```

## Database Conventions

- All models use UUID v7 (time-ordered) as primary keys — never auto-incrementing integers.
- Use Laravel's `HasUuids` trait on every Eloquent model.
- Migrations use `$table->uuid('id')->primary()` for primary keys and `$table->foreignUuid(...)` for foreign keys.
- Never expose numeric/sequential IDs in URLs, API responses, or frontend code.
- Route model binding varies by model:
  - `Congregation` binds by `slug`
  - `CongregationInvitation` binds by `code`
  - Other models bind by UUID `id`

## Locale & SSR

- Application locale is `sv-SE` — all date/time formatting uses `getAppLocale()` from `resources/js/lib/locale.ts`, which reactively maps the current i18n language to a BCP 47 tag (`sv-SE`, `en-GB`).
- The legacy `APP_LOCALE` constant is deprecated; prefer `getAppLocale()` in new code.
- Never use `navigator.language` or `Intl.DateTimeFormat(undefined, ...)` directly — always import and use `getAppLocale()` to ensure SSR and client produce identical output (avoids hydration mismatches).
- Timezone is `Europe/Stockholm` (configured in `config/app.php`).

## Key Configuration

- Vite config: `vite.config.ts` (includes `laravel-vite-plugin` with Bunny font loader, `@inertiajs/vite`, `@vitejs/plugin-react` with React Compiler, `@tailwindcss/vite`, `@laravel/vite-plugin-wayfinder` with `formVariants: true`)
- Vitest config: `vitest.config.ts` (frontend test runner, jsdom environment)
- TypeScript: `tsconfig.json` (path alias `@/*` → `resources/js/*`)
- ESLint: `eslint.config.js` (enforces import ordering, consistent type imports, 1tbs brace style, padding around control statements)
- Prettier: `.prettierrc` (with tailwindcss plugin)
- Pint: `pint.json` (laravel preset)
- shadcn: `components.json` (new-york style, aliases configured)

## Testing Conventions

- Property-based tests use `->repeat(30)` — not 100. This keeps CI memory usage manageable while still providing meaningful coverage.
- Property tests live in `tests/Feature/Properties/` and use randomized inputs to validate invariants.
- Feature tests live in `tests/Feature/` grouped by domain (e.g., `KingdomHalls/`, `Auth/`, `Congregations/`).
