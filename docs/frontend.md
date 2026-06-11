# Frontend Architecture

[← Back to documentation](../README.md#documentation)

## Tech Stack

| Technology | Purpose |
|-----------|---------|
| React 19 | UI framework |
| TypeScript (strict) | Type safety |
| Inertia.js v3 | Server-driven SPA bridge |
| Tailwind CSS v4 | Utility-first styling |
| shadcn/ui (new-york) | Component library (Radix + Tailwind) |
| Vite 8 | Build tool and dev server |
| React Compiler | Automatic memoization |
| Vitest | Unit/component testing |
| ESLint 9 + Prettier 3 | Linting and formatting |

## Project Structure

```
resources/js/
├── actions/          # Auto-generated Wayfinder controller action functions
├── components/       # Shared components (domain + shell)
│   └── ui/           # shadcn/ui primitives (DO NOT EDIT)
├── hooks/            # Custom React hooks
├── layouts/          # Layout components (app, auth, settings)
├── lib/              # Utility functions (cn, calendar-utils, locale)
├── pages/            # Inertia page components (1:1 with routes)
├── routes/           # Auto-generated Wayfinder named route functions
├── types/            # TypeScript type definitions by domain
└── wayfinder/        # Wayfinder internals (auto-generated)
```

## Pages

Pages are React components in `resources/js/pages/` that receive typed props from the Laravel backend via Inertia.

| Page | Route | Description |
|------|-------|-------------|
| `welcome.tsx` | `/` | Public landing page |
| `calendar.tsx` | `/{congregation}/calendar` | Main calendar view |
| `setup/index.tsx` | `/setup` | First-time setup wizard |
| `auth/login.tsx` | `/login` | Login form |
| `auth/register.tsx` | `/register` | Registration form |
| `settings/profile.tsx` | `/settings/profile` | Profile settings |
| `settings/security.tsx` | `/settings/security` | Password/2FA/passkeys |
| `settings/sessions.tsx` | `/settings/sessions` | Active sessions |
| `settings/appearance.tsx` | `/settings/appearance` | Theme preference |
| `congregations/edit.tsx` | `/{congregation}/congregation` | Congregation settings |
| `congregations/members/index.tsx` | `/{congregation}/members` | Member management |
| `congregations/kingdom-hall/show.tsx` | `/{congregation}/kingdom-hall` | Kingdom Hall settings |

## Layouts

### AppLayout (`layouts/app-layout.tsx`)

The main application shell. Supports two variants:
- **Sidebar** — collapsible sidebar navigation (desktop default)
- **Header** — top navigation bar

### AuthLayout (`layouts/auth-layout.tsx`)

Used for login/register/password reset pages. Supports:
- **Card** — centered card
- **Simple** — minimal centered form
- **Split** — two-column with branding

### SettingsLayout (`layouts/settings/layout.tsx`)

Left navigation + content area for all settings pages.

## Key Components

### Calendar

| Component | Purpose |
|-----------|---------|
| `calendar-header.tsx` | Navigation (prev/next/today), view switcher, date display |
| `month-grid.tsx` | 7-column month view with booking blocks |
| `week-grid.tsx` | 7 day columns, 15-min time grid, pixel-accurate positioning |
| `day-grid.tsx` | Room columns with hourly grid |
| `booking-block.tsx` | Individual booking display (color-coded, truncated text) |
| `booking-dialog.tsx` | Create/edit booking form dialog |
| `booking-context-menu.tsx` | Right-click menu for booking actions |
| `calendar-context-menu.tsx` | Right-click menu on empty calendar space |
| `delete-confirm-dialog.tsx` | Deletion confirmation with scope options |
| `recurrence-edit-prompt.tsx` | Scope picker for editing recurring bookings |

### Navigation

| Component | Purpose |
|-----------|---------|
| `app-sidebar.tsx` | Main navigation sidebar |
| `congregation-switcher.tsx` | Dropdown to switch active congregation |
| `nav-main.tsx` | Main navigation links |
| `nav-user.tsx` | User menu in sidebar |
| `nav-footer.tsx` | Footer links |

### Auth/Security

| Component | Purpose |
|-----------|---------|
| `manage-passkeys.tsx` | Passkey management list |
| `manage-two-factor.tsx` | 2FA setup and management |
| `two-factor-setup-modal.tsx` | QR code setup flow |
| `passkey-register.tsx` | Register a new passkey |

## Custom Hooks

| Hook | Purpose |
|------|---------|
| `use-booking-channel` | Subscribe to WebSocket booking events |
| `use-drag-booking` | Drag-and-drop booking rescheduling |
| `use-keyboard-shortcuts` | Global keyboard shortcuts (⌘0/1/2 for views) |
| `use-responsive-view-mode` | Auto-select calendar view based on screen size |
| `use-flash-toast` | Display flash messages as Sonner toasts |
| `use-appearance` | Theme preference (light/dark/system) |
| `use-mobile` | Mobile breakpoint detection |
| `use-clipboard` | Copy to clipboard utility |
| `use-two-factor-auth` | 2FA state management |
| `use-long-press` | Long press gesture for mobile context menus |

## Routing (Wayfinder)

Instead of hardcoding URLs, import typed functions from Wayfinder:

```tsx
// Controller actions (for forms/mutations)
import { store } from '@/actions/Congregations/BookingController';

// Named routes (for links)
import { calendar } from '@/routes';
```

These are auto-generated by the `@laravel/vite-plugin-wayfinder` Vite plugin whenever routes change.

## Styling Conventions

- Use Tailwind utility classes directly in JSX
- Use `cn()` helper (from `@/lib/utils`) for conditional classes
- Use `cva` (Class Variance Authority) for component variants
- shadcn/ui components use `new-york` style with Radix primitives
- Never edit files in `components/ui/` — they're managed by shadcn CLI

## Locale & SSR

All date/time formatting uses the `APP_LOCALE` constant (`sv-SE`) from `@/lib/locale.ts`. Never use `navigator.language` or browser defaults — this prevents hydration mismatches between SSR and client.

```tsx
import { APP_LOCALE } from '@/lib/locale';

// Correct
new Intl.DateTimeFormat(APP_LOCALE, { ... }).format(date);

// Wrong — will cause hydration mismatch
new Intl.DateTimeFormat(undefined, { ... }).format(date);
```

## Type Definitions

Types are organized by domain in `resources/js/types/`:

| File | Types |
|------|-------|
| `auth.ts` | User, Auth, Passkey, TwoFactorSetupData |
| `bookings.ts` | BookingResource |
| `congregations.ts` | Congregation, KingdomHall, Room, Membership, CongregationInvitation |
| `navigation.ts` | BreadcrumbItem, NavItem |
| `ui.ts` | AppLayoutProps, FlashToast, AuthLayoutProps |
| `index.ts` | Re-exports all types |

## Testing

Frontend tests use **Vitest** with **jsdom** environment and **@testing-library/react**.

```bash
# Run all frontend tests
npx vitest run

# Run specific test
npx vitest run resources/js/hooks/__tests__/use-keyboard-shortcuts.test.ts
```

Test files live alongside their source:
- `resources/js/hooks/__tests__/` — hook tests
- `resources/js/lib/__tests__/` — utility tests
- `resources/js/pages/__tests__/` — page component tests
