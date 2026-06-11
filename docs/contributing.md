# Contributing

[← Back to documentation](../README.md#documentation)

## Development Workflow

1. Create a feature branch from `main`
2. Make your changes
3. Run the CI checks locally: `composer run ci:check`
4. Push and open a pull request

## Code Style

### PHP

- **Laravel Pint** with the `laravel` preset
- Always run before committing: `vendor/bin/pint --dirty --format agent`
- Use PHP 8 features: constructor promotion, enums, match expressions, named arguments
- Use curly braces for all control structures
- Explicit return types and parameter type hints
- TitleCase for enum keys

### TypeScript/React

- **ESLint 9** + **Prettier 3** with Tailwind plugin
- Run: `npm run lint` (auto-fix) and `npm run format`
- Enforce import ordering and consistent type imports
- 1tbs brace style
- Padding around control statements

### Commit Messages

Use clear, imperative commit messages:
- `Add booking recurrence support`
- `Fix room conflict validation`
- `Refactor calendar header component`

## Architecture Rules

- **Don't add new top-level directories** without approval
- **Don't add dependencies** without approval
- **Controllers stay thin** — delegate to Action classes for business logic
- **Check sibling files** before creating new ones to match conventions
- **Reuse existing components** before creating new ones

## File Creation

Use Artisan `make:` commands for Laravel files:

```bash
php artisan make:model ModelName -mf    # Model + migration + factory
php artisan make:test --pest TestName   # Pest feature test
php artisan make:controller ControllerName
php artisan make:class ClassName        # Generic PHP class
```

## Testing Requirements

- Every code change must be tested
- Run minimum tests needed to verify: `php artisan test --compact --filter=TestName`
- Use factories (check existing states) instead of manual model setup
- Property tests use `->repeat(30)`
- Frontend tests with Vitest: `npx vitest run`

## Key Conventions

| Convention | Rule |
|-----------|------|
| Primary keys | UUID v7 via `HasUuids` trait |
| Route binding | Congregation by `slug`, Invitation by `code`, others by `id` |
| Dates | Always use `APP_LOCALE` (`sv-SE`) constant |
| Timezone | `Europe/Stockholm` |
| Wayfinder | Import from `@/actions/` or `@/routes/`, never hardcode URLs |
| shadcn/ui | Never edit `components/ui/` files directly |
| Types | Define in `resources/js/types/` by domain, re-export from `index.ts` |

## Linting Checklist

Before pushing:

```bash
# PHP
vendor/bin/pint --dirty --format agent

# JS/TS
npm run lint
npm run format

# Types
npm run types:check

# Tests
php artisan test --compact
npx vitest run
```
