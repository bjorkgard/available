# Implementation Plan: Calendar View

## Overview

Replace the placeholder dashboard with a full-page monthly calendar. Backend changes are limited to renaming a route and updating the Fortify home path. All calendar logic lives in pure TypeScript utilities and React components using shadcn/ui primitives.

## Tasks

- [x] 1. Rename route and update backend configuration
  - [x] 1.1 Replace the `dashboard` route with `calendar` in `routes/web.php` and update `config/fortify.php` home path from `/dashboard` to `/calendar`
    - Change `Route::inertia('dashboard', 'dashboard')->name('dashboard')` to `Route::inertia('calendar', 'calendar')->name('calendar')`
    - Update `'home' => '/dashboard'` to `'home' => '/calendar'` in `config/fortify.php`
    - The `RedirectsToCurrentCongregation` trait prepends the congregation slug to the Fortify home value, so this handles login redirects automatically
    - _Requirements: 1.2, 1.3, 1.4_

  - [x] 1.2 Create `resources/js/pages/calendar.tsx` page component (scaffold) and delete `resources/js/pages/dashboard.tsx`
    - Create a minimal `calendar.tsx` page that renders within `AppLayout` with a "Calendar" breadcrumb using the Wayfinder `calendar` route
    - Delete `resources/js/pages/dashboard.tsx`
    - Update any imports/references to `dashboard` route in existing components if needed
    - _Requirements: 1.1, 1.5_

  - [x] 1.3 Write Pest feature tests for route changes
    - Rename `tests/Feature/DashboardTest.php` to `tests/Feature/CalendarTest.php`
    - Test: authenticated user can visit `/{congregation}/calendar` and receives 200
    - Test: guest is redirected to login
    - Test: `/{congregation}/dashboard` returns 404
    - Test: after login, user is redirected to `/{congregation}/calendar`
    - _Requirements: 1.2, 1.3, 1.4_

- [x] 2. Set up Vitest and create calendar utility functions
  - [x] 2.1 Set up Vitest with fast-check for frontend testing
    - Install `vitest`, `@testing-library/react`, `@testing-library/jest-dom`, `jsdom`, and `fast-check` as dev dependencies
    - Create `vitest.config.ts` with path alias `@/*` → `resources/js/*` and jsdom environment
    - Add `"test": "vitest --run"` script to `package.json`
    - _Requirements: (infrastructure for testing)_

  - [x] 2.2 Implement `resources/js/lib/calendar-utils.ts` with all pure date arithmetic functions
    - `generateMonthGrid(year, month): GridDate[]` — returns exactly 42 entries for 6 complete weeks with leading/trailing filler dates
    - `getPreviousMonth(year, month): { year: number; month: number }` — handles January → December rollover
    - `getNextMonth(year, month): { year: number; month: number }` — handles December → January rollover
    - `getWeekdayNames(locale?: string): string[]` — locale-aware abbreviated weekday names via `Intl.DateTimeFormat`
    - `getFirstDayOfWeek(locale?: string): number` — determines locale's first day of week
    - Export `GridDate` and `DateInfo` interfaces
    - _Requirements: 2.4, 3.1, 3.2, 4.2, 4.3_

  - [x] 2.3 Write property-based tests for `generateMonthGrid`
    - **Property 1: Grid generation produces exactly 42 cells with correct dates**
    - **Validates: Requirements 2.4, 3.1, 3.2**
    - Use fast-check to generate random year (current ± 10) and month (0–11)
    - Assert: array length is always 42
    - Assert: all days of target month appear sequentially
    - Assert: leading filler dates are correct final days of previous month
    - Assert: trailing filler dates start at 1 and increment

  - [x] 2.4 Write property-based tests for filler date correctness
    - **Property 2: Filler date click target correctness**
    - **Validates: Requirements 3.4**
    - For any grid, every `GridDate` with `isCurrentMonth === false` has valid adjacent month/year

  - [x] 2.5 Write property-based tests for month navigation arithmetic
    - **Property 3: Month navigation arithmetic**
    - **Validates: Requirements 4.2, 4.3**
    - Assert: `getPreviousMonth` handles January → December of previous year
    - Assert: `getNextMonth` handles December → January of next year
    - Assert: `getNextMonth(getPreviousMonth(y, m))` returns original `{year, month}`

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Build MonthGrid component
  - [x] 4.1 Create `resources/js/components/month-grid.tsx` component
    - Accept `MonthGridProps` interface: `grid: GridDate[]`, `today: DateInfo | null`, `onFillerDateClick: (year, month) => void`
    - Render 7 weekday column headers using `getWeekdayNames()` with locale-aware abbreviations
    - Render 42 date cells in a CSS Grid (6 rows × 7 columns) filling available space
    - Apply `text-muted-foreground` to filler dates (where `isCurrentMonth === false`)
    - Apply `bg-primary text-primary-foreground font-semibold rounded-md` to today's cell (only when `isToday && isCurrentMonth`)
    - Make filler dates clickable, calling `onFillerDateClick` with the filler date's year and month
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4, 7.1, 7.2, 7.3, 7.4_

  - [x] 4.2 Write property-based test for today indicator exclusivity
    - **Property 6: Today indicator exclusivity**
    - **Validates: Requirements 7.1, 7.3, 7.4**
    - For non-current months, all 42 cells have `isToday === false`
    - For current month, exactly one cell has `isToday === true` and that cell has `isCurrentMonth === true`

- [x] 5. Build CalendarHeader component
  - [x] 5.1 Create `resources/js/components/calendar-header.tsx` component
    - Accept `CalendarHeaderProps` interface with `displayedYear`, `displayedMonth`, callback props, and `isCurrentMonth`
    - Render previous/next month `Button` (variant: `outline`, size: `icon`) with `ChevronLeft`/`ChevronRight` icons and accessible `aria-label`
    - Render month `Select` (shadcn) with all 12 months using locale-aware month names, showing current selection
    - Render year `Select` (shadcn) with range of current year ± 5, showing current selection
    - Render "Today" `Button` (variant: `outline`) that is `disabled` when `isCurrentMonth` is true, with `aria-disabled` for assistive tech
    - _Requirements: 4.1, 4.4, 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.2, 6.3_

  - [x] 5.2 Write property-based test for today button disabled state
    - **Property 5: Today button disabled state**
    - **Validates: Requirements 6.3**
    - `isCurrentMonth` is `true` if and only if displayed year equals current year AND displayed month equals current month

  - [x] 5.3 Write property-based test for picker selection state consistency
    - **Property 4: Picker selection state consistency**
    - **Validates: Requirements 5.3, 5.4**
    - For any month selection M applied to state with year Y, result is `{year: Y, month: M}`
    - For any year selection Y applied to state with month M, result is `{year: Y, month: M}`

- [x] 6. Assemble calendar page with state management
  - [x] 6.1 Complete `resources/js/pages/calendar.tsx` with full calendar implementation
    - Initialize `displayedYear` and `displayedMonth` from `new Date()` (current month)
    - Compute `grid` via `generateMonthGrid(displayedYear, displayedMonth)` on each render
    - Compute `today` as `DateInfo` when displaying current month, `null` otherwise
    - Compute `isCurrentMonth` flag for header
    - Implement `onPreviousMonth`/`onNextMonth` using `getPreviousMonth`/`getNextMonth` with ±10 year boundary clamping
    - Implement `onSelectMonth` and `onSelectYear` state setters
    - Implement `onGoToToday` to reset to current month/year
    - Implement `onFillerDateClick` to navigate to the filler date's month
    - Wire `CalendarHeader` and `MonthGrid` with all props
    - Display month/year heading (from `CalendarHeader`)
    - _Requirements: 2.1, 2.5, 2.6, 3.4, 4.2, 4.3, 4.4, 5.3, 5.4, 6.2_

  - [x] 6.2 Write unit tests for calendar page state logic
    - Test: initial state is current month/year
    - Test: navigation beyond ±10 year bounds is clamped
    - Test: filler date click navigates to correct month
    - Test: "Today" resets to current month
    - _Requirements: 2.5, 4.4, 6.2_

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The `calendar-utils.ts` module is intentionally pure (no React, no side effects) to maximize testability
- No new database tables or API endpoints are needed — this feature is entirely frontend + route config

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1"] },
    { "id": 1, "tasks": ["1.2", "2.2"] },
    { "id": 2, "tasks": ["1.3", "2.3", "2.4", "2.5"] },
    { "id": 3, "tasks": ["4.1", "5.1"] },
    { "id": 4, "tasks": ["4.2", "5.2", "5.3"] },
    { "id": 5, "tasks": ["6.1"] },
    { "id": 6, "tasks": ["6.2"] }
  ]
}
```
