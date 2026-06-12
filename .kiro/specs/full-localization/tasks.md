# Implementation Plan: Full Localization

## Overview

Implement full i18n for both Laravel backend and React/Inertia frontend with Swedish (sv) as default and English (en) as secondary language. Uses Laravel `lang/` files as single source of truth, a cacheable JSON API endpoint for frontend consumption via react-i18next, and middleware-based locale resolution.

## Tasks

- [x] 1. Database migrations and model updates
  - [x] 1.1 Create migration to add `locale` column to `users` table
    - Add nullable `string('locale', 5)` column after `email`
    - Add `locale` to User model's fillable array
    - Update UserFactory to optionally generate locale values
    - _Requirements: 4.1, 4.2_

  - [x] 1.2 Create migration to add `locale` column to `congregations` table
    - Add `string('locale', 5)->default('sv')` column after `color`
    - Add `locale` to Congregation model's fillable array
    - Update CongregationFactory to include locale
    - _Requirements: 5.1, 5.2_

  - [x] 1.3 Create migration to add `locale` column to `congregation_invitations` table
    - Add `string('locale', 5)->default('sv')` column after `invited_by`
    - Add `locale` to CongregationInvitation model's fillable array
    - Update CongregationInvitationFactory to include locale
    - _Requirements: 6.6_

- [x] 2. Configuration and translation files
  - [x] 2.1 Update `config/app.php` with locale settings
    - Set `locale` to `'sv'`, `fallback_locale` to `'sv'`
    - Add `'supported_locales' => ['sv', 'en']` configuration key
    - _Requirements: 1.2, 1.3_

  - [x] 2.2 Create Swedish translation files in `lang/sv/`
    - Create `lang/sv/auth.php`, `lang/sv/pagination.php`, `lang/sv/passwords.php`, `lang/sv/validation.php`, `lang/sv/app.php`
    - `app.php` covers notifications, flash messages, and custom validation rules
    - _Requirements: 1.1, 1.5_

  - [x] 2.3 Create English translation files in `lang/en/`
    - Create `lang/en/auth.php`, `lang/en/pagination.php`, `lang/en/passwords.php`, `lang/en/validation.php`, `lang/en/app.php`
    - Mirror all keys from Swedish files with English translations
    - _Requirements: 1.5, 1.7_

  - [x] 2.4 Create frontend JSON translation files
    - Create `lang/sv.json` with all frontend UI string key-value pairs
    - Create `lang/en.json` with English translations for all keys
    - Use key-as-default-text pattern (e.g., `"Kalender": "Kalender"` in sv, `"Kalender": "Calendar"` in en)
    - _Requirements: 1.1, 1.5, 1.7_

  - [x] 2.5 Write property test for translation key symmetry
    - **Property 1: Translation Key Symmetry**
    - **Validates: Requirements 1.5, 1.7**
    - Test in `tests/Feature/Properties/TranslationKeySymmetryTest.php`
    - Iterate all keys across locale files and assert every key exists in all supported locales with non-empty value
    - Use `->repeat(30)`

- [x] 3. Backend locale resolution middleware
  - [x] 3.1 Create `SetLocale` middleware
    - Create `app/Http/Middleware/SetLocale.php`
    - Implement `resolveLocale()` with authenticated and guest resolution paths
    - Authenticated priority: User_Locale > Congregation_Locale > sv
    - Guest priority: session locale > Accept-Language header > sv
    - Implement `parseAcceptLanguage()` with prefix matching (en-US → en)
    - Implement `isSupported()` validation against configured locales
    - _Requirements: 1.4, 3.1, 3.2, 3.3, 3.6, 7.1, 7.2, 7.3_

  - [x] 3.2 Register `SetLocale` middleware globally
    - Add to `bootstrap/app.php` middleware stack before `HandleInertiaRequests`
    - _Requirements: 1.4, 7.1, 7.2_

  - [x] 3.3 Update `HandleInertiaRequests` to share locale props
    - Add `'locale' => fn () => app()->getLocale()` to shared props
    - Add `'supportedLocales' => config('app.supported_locales')` to shared props
    - _Requirements: 4.2_

  - [x] 3.4 Write property test for authenticated locale resolution
    - **Property 2: Authenticated Locale Resolution**
    - **Validates: Requirements 1.4, 4.2, 4.3, 7.1, 7.3**
    - Test in `tests/Feature/Properties/LocaleResolutionTest.php`
    - Randomize user locale, congregation locale combinations and verify priority chain
    - Use `->repeat(30)`

  - [x] 3.5 Write property test for guest locale resolution
    - **Property 4: Guest Locale Resolution**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.6, 7.2**
    - Add to `tests/Feature/Properties/LocaleResolutionTest.php`
    - Randomize session locale, Accept-Language header and verify resolution priority
    - Use `->repeat(30)`

  - [x] 3.6 Write property test for Accept-Language prefix matching
    - **Property 12: Accept-Language Prefix Matching**
    - **Validates: Requirements 3.1, 3.2**
    - Add to `tests/Feature/Properties/LocaleResolutionTest.php`
    - Generate random language tags (en-US, en-GB, sv-FI, etc.) and verify prefix matching
    - Use `->repeat(30)`

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Translation and locale controllers
  - [x] 5.1 Create `TranslationController`
    - Create `app/Http/Controllers/TranslationController.php`
    - Implement `show(string $locale)` method
    - Load all PHP files from `lang/{locale}/` and flatten with dot notation
    - Load `lang/{locale}.json` for JSON translations
    - Merge into single flat key-value map
    - Return 404 for unsupported locales
    - Include ETag header (md5 of response body)
    - Include `Cache-Control: public, max-age=86400, must-revalidate`
    - Handle `If-None-Match` header for 304 responses
    - _Requirements: 2.1, 2.2, 2.3, 8.1, 8.2, 8.3, 8.4, 8.5_

  - [x] 5.2 Register translation API route
    - Add `GET /api/translations/{locale}` route in `routes/web.php` or a new routes file
    - Route should be accessible without authentication
    - _Requirements: 2.1_

  - [x] 5.3 Create `LocaleController` for settings
    - Create `app/Http/Controllers/Settings/LocaleController.php`
    - Implement `update(Request $request)` — PATCH for authenticated users, persists to user record
    - Implement `store(Request $request)` — POST for guests, persists to session
    - Validate locale is in supported locales set
    - _Requirements: 3.5, 4.1, 4.6, 4.7_

  - [x] 5.4 Register locale routes
    - Add `PATCH /settings/locale` route (authenticated, LocaleController@update) in `routes/settings.php`
    - Add `POST /locale` route (guest-accessible, LocaleController@store) in `routes/web.php`
    - _Requirements: 3.5, 4.1_

  - [x] 5.5 Write property test for translation endpoint
    - **Property 3: Translation Endpoint Serves Correct Payload**
    - **Validates: Requirements 2.1, 2.2**
    - Test in `tests/Feature/Properties/TranslationEndpointTest.php`
    - Randomize supported/unsupported locale strings, verify 200 vs 404 responses
    - Use `->repeat(30)`

  - [x] 5.6 Write property test for locale validation
    - **Property 6: Locale Validation Rejects Unsupported Values**
    - **Validates: Requirements 4.7, 6.7**
    - Test in `tests/Feature/Properties/LocaleValidationTest.php`
    - Submit random unsupported locale strings to all locale endpoints and verify 422
    - Use `->repeat(30)`

  - [x] 5.7 Write property test for user locale persistence
    - **Property 5: User Locale Persistence**
    - **Validates: Requirements 4.1**
    - Test in `tests/Feature/Properties/LocaleValidationTest.php`
    - For any supported locale, update user preference and verify persistence + subsequent requests
    - Use `->repeat(30)`

- [x] 6. Notification locale integration
  - [x] 6.1 Update `InvitationNotification` to use invitation locale
    - Modify `SendInvitation` action to set `locale` on the invitation record from request input (defaulting to congregation locale)
    - Call `->locale($invitation->locale)` when dispatching notification
    - _Requirements: 5.4, 6.1, 6.2, 6.3, 7.4_

  - [x] 6.2 Update `BookingModifiedNotification` and `BookingDeletedNotification` locale resolution
    - Resolve notification locale: recipient User_Locale > Congregation_Locale > sv
    - Call `->locale(...)` when dispatching each notification
    - _Requirements: 5.3, 7.5_

  - [x] 6.3 Update invitation acceptance flow for locale propagation
    - In `CreateNewUser` action (for new users accepting invitation): set new user's `locale` to the invitation's locale
    - In `InvitationAcceptController` (for existing users): do NOT modify existing user's locale
    - _Requirements: 5.4, 6.4, 6.5_

  - [x] 6.4 Write property test for invitation locale on new user
    - **Property 8: New User Locale From Invitation**
    - **Validates: Requirements 5.4, 6.4**
    - Test in `tests/Feature/Properties/InvitationLocaleTest.php`
    - Random invitation locales, verify new user gets invitation locale
    - Use `->repeat(30)`

  - [x] 6.5 Write property test for existing user locale preserved
    - **Property 9: Existing User Locale Preserved on Invitation Accept**
    - **Validates: Requirements 6.5**
    - Add to `tests/Feature/Properties/InvitationLocaleTest.php`
    - Random existing user locales, verify unchanged after invitation acceptance
    - Use `->repeat(30)`

  - [x] 6.6 Write property test for invitation email locale
    - **Property 10: Invitation Email Locale**
    - **Validates: Requirements 6.3, 7.4**
    - Add to `tests/Feature/Properties/InvitationLocaleTest.php`
    - Random invitation locales, verify notification rendered in that locale
    - Use `->repeat(30)`

  - [x] 6.7 Write property test for congregation-scoped notification locale
    - **Property 11: Congregation-Scoped Notification Locale**
    - **Validates: Requirements 5.3, 7.5**
    - Test in `tests/Feature/Properties/NotificationLocaleTest.php`
    - Random recipient/congregation locale combinations, verify resolution chain
    - Use `->repeat(30)`

  - [x] 6.8 Write property test for congregation locale change isolation
    - **Property 7: Congregation Locale Change Isolation**
    - **Validates: Requirements 5.6**
    - Test in `tests/Feature/Properties/CongregationLocaleIsolationTest.php`
    - Update congregation locale with existing members, verify no User_Locale changes
    - Use `->repeat(30)`

- [x] 7. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Frontend i18n infrastructure
  - [x] 8.1 Install react-i18next dependency
    - Run `npm install react-i18next i18next`
    - _Requirements: 2.4_

  - [x] 8.2 Create i18n configuration with custom backend plugin
    - Create `resources/js/lib/i18n.ts`
    - Implement custom LaravelBackend plugin that fetches from `/api/translations/{locale}`
    - Add in-memory translation cache
    - Configure fallbackLng as `'sv'`, supportedLngs as `['sv', 'en']`
    - _Requirements: 2.4, 2.5, 2.6, 2.7, 2.8_

  - [x] 8.3 Create I18nProvider component
    - Create `resources/js/lib/i18n-provider.tsx`
    - Implement `I18nSync` child that reads Inertia `locale` prop and syncs with i18next
    - Wrap with `I18nextProvider` and `Suspense`
    - _Requirements: 4.5_

  - [x] 8.4 Integrate I18nProvider into app layout
    - Wrap the root app component with `I18nProvider`
    - Ensure it renders above all page components
    - _Requirements: 2.4_

  - [x] 8.5 Update `resources/js/lib/locale.ts` for reactive locale
    - Add `getAppLocale()` function mapping i18n language to BCP 47 tag (sv → sv-SE, en → en-GB)
    - Deprecate static `APP_LOCALE` constant with JSDoc comment
    - Update existing usages of `APP_LOCALE` to use `getAppLocale()`
    - _Requirements: 4.5_

- [x] 9. Frontend components and integration
  - [x] 9.1 Create LanguageSelector component
    - Create `resources/js/components/language-selector.tsx`
    - Use shadcn DropdownMenu with Globe icon
    - Handle both authenticated (PATCH /settings/locale) and guest (POST /locale) flows
    - Use Inertia `useForm` for submission with `preserveScroll`
    - _Requirements: 3.4, 4.4_

  - [x] 9.2 Add LanguageSelector to app sidebar
    - Import and render LanguageSelector in the sidebar navigation area (nav-footer or similar)
    - Visible to all authenticated users
    - _Requirements: 4.4_

  - [x] 9.3 Add LanguageSelector to welcome page
    - Import and render LanguageSelector on the welcome/landing page for guests
    - _Requirements: 3.4_

  - [x] 9.4 Add locale field to congregation settings page
    - Update congregation edit page to include a locale selector
    - Pre-populate with current Congregation_Locale
    - Restrict to Admin/Superadmin roles
    - _Requirements: 5.1, 5.5_

  - [x] 9.5 Add locale field to invitation form
    - Update invite-member-dialog to include locale selector
    - Pre-select Congregation_Locale as default
    - Allow override to any supported locale
    - _Requirements: 6.1, 6.2_

  - [x] 9.6 Add locale selector to setup wizard
    - Update setup wizard page to include congregation locale selection
    - Default to sv if not selected
    - _Requirements: 5.1, 5.2_

  - [x] 9.7 Write frontend unit tests for i18n
    - Test custom backend plugin caching behavior in `resources/js/lib/__tests__/i18n.test.ts`
    - Test fallback behavior and error handling
    - Test LanguageSelector rendering and endpoint calls
    - Test I18nProvider sync with Inertia locale prop
    - _Requirements: 2.4, 2.5, 2.6, 2.7_

- [x] 10. Replace hardcoded strings with translation calls
  - [x] 10.1 Replace hardcoded strings in PHP backend files
    - Wrap all user-facing strings in `__()` or `trans()` calls
    - Cover: notification content, flash messages, validation messages, custom rules
    - Add new keys to `lang/sv/app.php` and `lang/en/app.php` as needed
    - _Requirements: 1.4, 1.5, 9.1_

  - [x] 10.2 Replace hardcoded strings in React components with `useTranslation()`
    - Import `useTranslation` from `react-i18next` in all page and component files
    - Replace string literals in JSX (text content, labels, placeholders, toasts) with `t('key')` calls
    - Add corresponding keys to `lang/sv.json` and `lang/en.json`
    - _Requirements: 1.5, 9.1_

- [x] 11. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Localization enforcement tooling
  - [x] 12.1 Create localization steering file
    - Create or update a steering file documenting the localization rules
    - Specify that all user-facing strings in PHP must use `__()` or `trans()`
    - Specify that all user-facing strings in React/TypeScript must use the `t()` function from react-i18next
    - Define what constitutes "user-facing strings" per Requirement 9.2
    - _Requirements: 9.1, 9.2_

  - [x] 12.2 Create Kiro hook for localization enforcement
    - Create a `fileEdited` hook on `.php`, `.tsx`, `.ts` patterns
    - Use `askAgent` action to review edited files for unlocalized user-facing strings
    - Output warnings with file path, line number, and offending string for violations
    - _Requirements: 9.3, 9.4, 9.5, 9.6_

- [x] 13. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document using `->repeat(30)`
- Unit tests validate specific examples and edge cases
- Backend uses PHP, frontend uses TypeScript/React throughout
- Translation files in `lang/` serve as single source of truth for both backend and frontend
- The `SetLocale` middleware must be registered before `HandleInertiaRequests` to ensure the shared `locale` prop is correct

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "2.1"] },
    { "id": 1, "tasks": ["2.2", "2.3", "2.4"] },
    { "id": 2, "tasks": ["2.5", "3.1"] },
    { "id": 3, "tasks": ["3.2", "3.3"] },
    { "id": 4, "tasks": ["3.4", "3.5", "3.6", "5.1"] },
    { "id": 5, "tasks": ["5.2", "5.3"] },
    { "id": 6, "tasks": ["5.4", "5.5", "5.6", "5.7"] },
    { "id": 7, "tasks": ["6.1", "6.2", "6.3"] },
    { "id": 8, "tasks": ["6.4", "6.5", "6.6", "6.7", "6.8"] },
    { "id": 9, "tasks": ["8.1"] },
    { "id": 10, "tasks": ["8.2"] },
    { "id": 11, "tasks": ["8.3", "8.5"] },
    { "id": 12, "tasks": ["8.4"] },
    { "id": 13, "tasks": ["9.1"] },
    { "id": 14, "tasks": ["9.2", "9.3", "9.4", "9.5", "9.6"] },
    { "id": 15, "tasks": ["9.7", "10.1", "10.2"] },
    { "id": 16, "tasks": ["12.1", "12.2"] }
  ]
}
```
