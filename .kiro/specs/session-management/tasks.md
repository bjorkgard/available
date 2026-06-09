# Implementation Plan: Session Management

## Overview

Add a session management page to the settings area, allowing users to view all their active sessions (with parsed device info) and terminate other sessions by confirming their password. The implementation builds on the existing database session driver and sessions table, following established settings page conventions.

## Tasks

- [x] 1. Create UserAgentParser utility and form request
  - [x] 1.1 Create `App\Support\UserAgentParser` class
    - Implement constructor accepting a nullable user agent string
    - Implement `browser(): string` method using regex to extract marketing browser name (Chrome, Firefox, Safari, Edge, Opera, etc.) without version number
    - Implement `os(): string` method using regex to extract marketing OS name (Windows, macOS, Linux, iOS, Android) without version number
    - Implement `deviceType(): string` method returning "mobile" if user agent contains mobile indicators (Mobile, iPhone, Android with Mobile), "desktop" otherwise
    - Return "Unknown" for browser/OS and "desktop" for device type when input is null, empty, or unrecognizable
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 1.2 Write unit tests for UserAgentParser
    - Test known browser strings (Chrome, Firefox, Safari, Edge, Opera) produce correct marketing names
    - Test known OS strings (Windows, macOS, Linux, iOS, Android) produce correct names
    - Test mobile indicators correctly classify device type as "mobile"
    - Test desktop user agents classify as "desktop"
    - Test null, empty, and gibberish input returns "Unknown"/"Unknown"/"desktop"
    - Test version numbers are stripped from browser and OS names
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 1.3 Write property tests for UserAgentParser
    - **Property 4: Browser name extraction without version**
    - **Property 5: Operating system extraction without version**
    - **Property 6: Device type classification**
    - **Validates: Requirements 3.1, 3.2, 3.3**
    - Generate randomized user agent strings with known browser/OS patterns and version suffixes
    - Verify version numbers are never present in output
    - Generate strings with/without mobile indicators and verify classification

  - [x] 1.4 Create `App\Http\Requests\Settings\DestroySessionRequest` form request
    - Define rules: `['password' => ['required', 'current_password']]`
    - _Requirements: 4.2, 4.3_

- [x] 2. Implement SessionController backend
  - [x] 2.1 Create `App\Http\Controllers\Settings\SessionController` with `edit` method
    - Query sessions table for all rows matching authenticated user's ID
    - Parse each session's user agent using `UserAgentParser`
    - Format last_activity as human-readable relative timestamp using `Carbon::createFromTimestamp()->diffForHumans()`
    - Mark current session by comparing session ID with `$request->session()->getId()`
    - Order results: current session first, then remaining sorted by last_activity descending
    - Return Inertia response rendering `settings/sessions` with formatted session data
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 5.1, 5.2_

  - [x] 2.2 Add `destroy` method to SessionController
    - Validate password using `DestroySessionRequest`
    - Delete all session records for authenticated user except the current session ID
    - Flash success toast message
    - Redirect back
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 2.3 Register routes in `routes/settings.php`
    - Add `GET settings/sessions` route mapped to `SessionController@edit` with name `sessions.edit`
    - Add `DELETE settings/sessions` route mapped to `SessionController@destroy` with name `sessions.destroy` and `throttle:6,1` middleware
    - Place within existing `['auth', 'verified']` middleware group
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 2.4 Write feature tests for SessionController
    - Test GET returns 200 with session data for authenticated user
    - Test GET redirects to login for guests
    - Test GET redirects to verification for unverified users
    - Test session list ordering: current session first, then by last_activity desc
    - Test DELETE with correct password removes other sessions and keeps current
    - Test DELETE with incorrect password returns 422 validation error
    - Test DELETE is throttled after 6 attempts
    - Test only sessions belonging to authenticated user are returned (not other users' sessions)
    - _Requirements: 1.1, 4.1, 4.2, 4.3, 4.4, 6.1, 6.2, 6.3_

  - [x] 2.5 Write property test for session ordering
    - **Property 1: Session ordering — current first, then by recency**
    - **Validates: Requirements 1.1**
    - Generate random sets of 1-20 sessions with random timestamps
    - Verify current session is always at index 0
    - Verify remaining sessions are sorted by last_activity descending

  - [x] 2.6 Write property test for session termination
    - **Property 7: Terminate removes all sessions except current**
    - **Validates: Requirements 4.1**
    - Generate users with 1-20 sessions
    - Execute terminate action with valid password
    - Verify exactly 1 session remains (the current session)

- [x] 3. Checkpoint - Ensure all backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Build the sessions settings page frontend
  - [x] 4.1 Create `resources/js/pages/settings/sessions.tsx` page component
    - Define TypeScript `Session` type with id, ip_address, browser, os, device_type, last_active, is_current_device fields
    - Render session list with `Monitor` (desktop) or `Smartphone` (mobile) icon from lucide-react based on device_type
    - Display browser name, OS, IP address, and relative last_active time for each session
    - Show "This device" label for the current session entry
    - Use shared settings layout via `Sessions.layout` static property with breadcrumbs
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 6.5_

  - [x] 4.2 Add terminate other sessions form to the page
    - Use `Form` from `@inertiajs/react` with Wayfinder-generated `SessionController.destroy.form()`
    - Include `PasswordInput` component for password confirmation
    - Show `InputError` for validation errors on the password field
    - Clear password field on error using `resetOnError`
    - Disable the terminate button when only the current session exists (sessions length <= 1)
    - Disable submit button while processing
    - Display success toast via existing `useFlashToast` hook on successful termination
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_

  - [x] 4.3 Add "Sessions" nav item to settings sidebar
    - Update `resources/js/layouts/settings/layout.tsx` to import the sessions route from `@/routes/sessions`
    - Add nav item `{ title: 'Sessions', href: editSessions(), icon: null }` after "Security"
    - _Requirements: 6.4, 6.5_

- [x] 5. Checkpoint - Verify frontend integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Ensure SESSION_DRIVER is set to database
  - [x] 6.1 Verify `.env` has `SESSION_DRIVER=database`
    - Check `.env` file and update if needed to set `SESSION_DRIVER=database`
    - Verify `config/session.php` defaults to the database driver
    - _Requirements: 5.1_

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The sessions table already exists — no migration is needed
- Wayfinder will auto-generate typed route functions after adding routes (via Vite plugin)
- The `useFlashToast` hook handles flash-based toast notifications from redirects

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.4"] },
    { "id": 1, "tasks": ["1.2", "1.3", "2.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "2.5"] },
    { "id": 3, "tasks": ["2.4", "2.6"] },
    { "id": 4, "tasks": ["4.1", "6.1"] },
    { "id": 5, "tasks": ["4.2", "4.3"] }
  ]
}
```
