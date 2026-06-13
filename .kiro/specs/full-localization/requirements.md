# Requirements Document

## Introduction

Full application localization (i18n) for both the Laravel backend and React/Inertia frontend. The system uses Laravel's language files (`lang/` directory) as the single source of truth for translations. The frontend uses react-i18next, loading translations at runtime from a cacheable Laravel API endpoint that serves language files as JSON per locale. Swedish (sv) is the application default language, with English (en) as a supported secondary language. Only UI strings and system messages are localized; user-generated content remains unlocalized.

## Glossary

- **Locale_Manager**: The backend component responsible for resolving, applying, and persisting the active locale for a request
- **Translation_Endpoint**: The Laravel API endpoint (`/api/translations/{locale}`) that serves language files as a JSON payload for the frontend
- **Language_Selector**: A UI control that allows users to switch between supported languages
- **Frontend_i18n**: The react-i18next integration that loads translations from the Translation_Endpoint and provides translation functions to React components
- **Supported_Locales**: The set of available languages in the application — Swedish (sv) and English (en)
- **User_Locale**: The locale preference persisted to the authenticated user's database record
- **Congregation_Locale**: The default locale assigned to a Congregation, used for system communications and as the default for new members
- **Invitation_Locale**: The locale assigned to a specific invitation, determining the language of the invitation email and the invited user's initial locale
- **Browser_Locale**: The preferred language detected from the browser's `Accept-Language` header for unauthenticated visitors

## Requirements

### Requirement 1: Backend Localization Infrastructure

**User Story:** As a developer, I want all backend strings managed through Laravel's localization system, so that translations are centralized and maintainable.

#### Acceptance Criteria

1. THE Locale_Manager SHALL use Laravel's `lang/` directory with PHP and JSON files as the single source of truth for all translatable strings
2. THE Locale_Manager SHALL support Swedish (sv) and English (en) as Supported_Locales
3. THE Locale_Manager SHALL set Swedish (sv) as both the application default locale and fallback locale
4. WHEN a request is processed, THE Locale_Manager SHALL resolve the active locale from the authenticated user's preferred locale setting and apply it to all backend-generated strings including validation messages, flash messages, and notification content
5. THE Locale_Manager SHALL provide translation files for both Supported_Locales covering all existing backend strings including validation messages, authentication messages, pagination labels, password reset messages, and application-specific strings used in notifications, flash messages, and custom validation rules
6. IF a translation key is missing for the active locale, THEN THE Locale_Manager SHALL fall back to the Swedish (sv) translation for that key
7. THE Locale_Manager SHALL ensure every translation key present in one Supported_Locale file has a corresponding entry in all other Supported_Locale files

### Requirement 2: Frontend Translation Loading

**User Story:** As a developer, I want translations loaded at runtime from a Laravel endpoint, so that the frontend stays in sync with the single source of truth without build-time bundling.

#### Acceptance Criteria

1. THE Translation_Endpoint SHALL serve all translation keys for a given locale as a single JSON response at `/api/translations/{locale}`
2. IF a locale not present in the application's configured locale list is requested, THEN THE Translation_Endpoint SHALL return a 404 response
3. THE Translation_Endpoint SHALL set a `Cache-Control` header with a `max-age` of at least 60 seconds on translation responses to enable client-side and CDN caching
4. THE Frontend_i18n SHALL initialize react-i18next with translations fetched from the Translation_Endpoint using a custom i18next backend plugin
5. THE Frontend_i18n SHALL cache fetched translations in memory for the lifetime of the page session so that subsequent Inertia navigations do not trigger additional network requests for the same locale
6. IF a translation key is missing for the active locale, THEN THE Frontend_i18n SHALL display the Swedish (sv) value for that key as a fallback
7. WHEN the Translation_Endpoint request fails, THE Frontend_i18n SHALL render the UI using previously cached Swedish (sv) translations without delaying page display
8. THE Frontend_i18n SHALL eagerly load Swedish (sv) translations at initialization so that fallback content is always available regardless of network failures

### Requirement 3: Guest Language Detection and Selection

**User Story:** As an unauthenticated visitor, I want the application to detect my browser language and let me switch languages, so that I can use the app in my preferred language before logging in.

#### Acceptance Criteria

1. WHEN an unauthenticated user visits the application and no session-stored locale is present, THE Locale_Manager SHALL detect the Browser_Locale by parsing the `Accept-Language` header and selecting the highest-priority language tag (by q-value) that matches a Supported_Locale, using language-prefix matching (e.g., `en-US` or `en-GB` matches `en`)
2. IF the Browser_Locale matches a Supported_Locale (Swedish `sv` or English `en`) via exact match or language-prefix match, THEN THE Locale_Manager SHALL use that locale for the session
3. IF the Browser_Locale does not match any Supported_Locale, THEN THE Locale_Manager SHALL use Swedish (sv) as the default
4. THE Language_Selector SHALL be displayed on the welcome page for unauthenticated visitors and SHALL show all Supported_Locales (Swedish and English) as selectable options
5. WHEN a guest selects a language via the Language_Selector, THE Locale_Manager SHALL apply the selected locale to the current session and persist it in a session cookie
6. WHEN an unauthenticated user makes a subsequent request and a session-stored locale is present, THE Locale_Manager SHALL use the session-stored locale and skip browser language detection

### Requirement 4: Authenticated User Language Preference

**User Story:** As a logged-in user, I want to save my language preference, so that my chosen language persists across sessions and devices.

#### Acceptance Criteria

1. WHEN an authenticated user selects a locale from the Language_Selector, THE Locale_Manager SHALL persist the selected locale value to the User_Locale field on the user's database record within 2 seconds of the selection
2. WHEN an authenticated user makes a page request, THE Locale_Manager SHALL resolve the active locale by reading the User_Locale field from the user's database record and providing it as a shared Inertia prop
3. IF the User_Locale field is null, THEN THE Locale_Manager SHALL fall back to the Congregation_Locale of the user's current congregation; IF the Congregation_Locale is also null, THEN THE Locale_Manager SHALL fall back to the application default locale defined in the app configuration
4. THE Language_Selector SHALL display the list of application-supported locales and be accessible to authenticated users from the app sidebar navigation area
5. WHEN the User_Locale is updated, THE Frontend_i18n SHALL switch the active locale without requiring a full page reload, applying the new locale to all visible date formatting and UI text within 1 second
6. IF the persist operation for User_Locale fails, THEN THE Locale_Manager SHALL retain the previously active locale on the client, display an error notification indicating the preference was not saved, and not update the User_Locale field on the database record
7. THE Locale_Manager SHALL only accept locale values that exist in the application's configured set of supported locales; IF an unsupported locale value is submitted, THEN THE Locale_Manager SHALL reject the request with a validation error

### Requirement 5: Congregation Default Language

**User Story:** As a congregation administrator, I want to set my congregation's default language, so that system communications and new member defaults use the appropriate language.

#### Acceptance Criteria

1. WHEN creating a congregation during the setup wizard, THE Setup_Wizard SHALL display a language selector populated with all supported application locales (at minimum: sv, en) for choosing the Congregation_Locale
2. IF no Congregation_Locale selection is made during setup, THEN THE Setup_Wizard SHALL set the Congregation_Locale to Swedish (sv)
3. WHEN sending system emails or notifications (BookingModifiedNotification, BookingDeletedNotification, InvitationNotification) on behalf of a congregation, THE Locale_Manager SHALL render the notification content in the language identified by the Congregation_Locale of the sending congregation
4. WHEN a new member joins a congregation via an invitation, THE Locale_Manager SHALL set the new member's initial User_Locale to the Congregation_Locale of the congregation at the time the invitation was created
5. THE Congregation_Locale SHALL be editable by users with the Admin or Superadmin role in the congregation settings, and the selector SHALL only accept values from the supported application locales list
6. IF an administrator changes the Congregation_Locale, THEN THE Locale_Manager SHALL apply the new locale only to notifications sent after the change, without modifying existing members' User_Locale values

### Requirement 6: Invitation Language

**User Story:** As a congregation administrator, I want to control the language of invitation emails, so that invited users receive communications in an appropriate language.

#### Acceptance Criteria

1. WHEN creating an invitation, THE invitation form SHALL pre-select the Congregation_Locale as the default Invitation_Locale
2. THE invitation form SHALL allow the sender to override the Invitation_Locale by selecting a different Supported_Locale
3. WHEN sending an invitation email, THE notification system SHALL render the email subject line and body in the Invitation_Locale
4. WHEN an invited user accepts the invitation and creates a new account, THE Locale_Manager SHALL set the new user's User_Locale to the Invitation_Locale from the accepted invitation
5. IF the invited user already has an account, THEN THE Locale_Manager SHALL NOT overwrite the existing user's User_Locale
6. THE Invitation_Locale SHALL be stored on the invitation record in the database
7. IF the submitted Invitation_Locale is not a Supported_Locale, THEN THE invitation form SHALL reject the submission with a validation error indicating the locale is invalid

### Requirement 7: Locale Resolution Priority

**User Story:** As a developer, I want a clear locale resolution order, so that the correct language is applied in every context without ambiguity.

#### Acceptance Criteria

1. WHEN resolving the locale for an authenticated user request, THE Locale_Manager SHALL apply the following priority order: User_Locale (if set), then Congregation_Locale of the active congregation (the congregation identified by the `{current_congregation}` route parameter or session), then Swedish (sv) as the application default
2. WHEN resolving the locale for an unauthenticated user request, THE Locale_Manager SHALL apply the following priority order: session-stored locale (if present), then Browser_Locale (if it matches a Supported_Locale), then Swedish (sv) as the application default
3. WHEN resolving the locale for an authenticated user on a route without congregation context, THE Locale_Manager SHALL apply the following priority order: User_Locale (if set), then Swedish (sv) as the application default
4. WHEN sending an invitation notification, THE Locale_Manager SHALL use the Invitation_Locale stored on the invitation record, regardless of the recipient's User_Locale or Congregation_Locale
5. WHEN sending a congregation-scoped notification (BookingModifiedNotification, BookingDeletedNotification), THE Locale_Manager SHALL resolve the locale using the recipient's User_Locale (if set), then the Congregation_Locale, then Swedish (sv) as the application default

### Requirement 8: Translation Endpoint Caching

**User Story:** As a developer, I want translation responses cached effectively, so that frontend performance is not degraded by repeated translation fetches.

#### Acceptance Criteria

1. THE Translation_Endpoint SHALL include an `ETag` header derived from a hash of the translation response body
2. WHEN a request includes an `If-None-Match` header matching the current ETag, THE Translation_Endpoint SHALL return a 304 Not Modified response with no body
3. THE Translation_Endpoint SHALL include a `Cache-Control: public, max-age=86400, must-revalidate` header to enable CDN and browser caching with a 24-hour freshness lifetime
4. WHEN translation files are updated during deployment, THE Translation_Endpoint SHALL produce a new ETag value, causing clients to fetch updated translations on their next request after revalidation
5. WHEN a request includes an `If-None-Match` header that does not match the current ETag, THE Translation_Endpoint SHALL return a 200 response with the full translation payload and the current ETag header

### Requirement 9: Localization Enforcement in Development

**User Story:** As a developer, I want automated checks that catch unlocalized strings, so that new features are always properly translated.

#### Acceptance Criteria

1. THE steering files SHALL include a rule stating that all user-facing strings in PHP files must use the `__()` or `trans()` localization functions, and all user-facing strings in React/TypeScript files must use the project's designated translation function
2. THE steering files SHALL define "user-facing strings" as any string literal rendered in HTML output, flash messages, notification content, validation error messages, or UI labels — excluding log messages, internal exception messages, class names, route names, and configuration keys
3. WHEN a `.php`, `.tsx`, or `.ts` file is created or edited, THE Kiro hook SHALL use the `askAgent` action to review the file and report each instance of a user-facing string that does not use the localization system
4. WHILE analyzing PHP files, THE hook SHALL identify string literals passed directly to Blade output, `Inertia::flash()` messages, notification content, and validation `$fail()` messages that are not wrapped in `__()` or `trans()`
5. WHILE analyzing React/TypeScript files, THE hook SHALL identify string literals rendered as JSX text content, component label props, toast messages, and placeholder attributes that are not wrapped in the project's designated translation function
6. IF the hook detects one or more unlocalized user-facing strings, THEN THE hook SHALL produce a warning listing the file path, line number, and the offending string for each violation
