# Design Document: Session Management

## Overview

This feature adds a session management page to the settings area, allowing users to view all their active sessions and terminate other sessions for security. It follows existing settings page conventions with a new `SessionController`, a user agent parser utility, and an Inertia React page.

The design leverages Laravel's built-in database session driver (already configured) and the existing `sessions` table schema. No new migrations are required — the table already has `id`, `user_id`, `ip_address`, `user_agent`, `payload`, and `last_activity` columns.

## Architecture

```mermaid
graph TD
    A[Browser] -->|GET /settings/sessions| B[SessionController@edit]
    A -->|DELETE /settings/sessions| C[SessionController@destroy]
    B --> D[Query sessions table]
    D --> E[UserAgentParser]
    E --> F[Inertia render sessions page]
    C --> G[Validate password]
    G -->|Valid| H[Delete other sessions]
    G -->|Invalid| I[Return validation error]
    H --> J[Flash success toast + redirect back]
```

**Request flow:**
1. User navigates to `/settings/sessions`
2. `SessionController@edit` queries the `sessions` table for all rows matching the authenticated user's ID
3. Each session's `user_agent` string is parsed by `UserAgentParser` into browser, OS, and device type
4. Data is passed to the Inertia page component for rendering
5. To terminate other sessions, the user submits their password via `DELETE /settings/sessions`
6. The controller validates the password, deletes all sessions except the current one, and returns a success toast

## Components and Interfaces

### Backend

#### `App\Http\Controllers\Settings\SessionController`

```php
class SessionController extends Controller
{
    public function edit(Request $request): Response
    // Returns Inertia page with formatted session data

    public function destroy(Request $request): RedirectResponse
    // Validates password, deletes other sessions, flashes toast
}
```

#### `App\Support\UserAgentParser`

A lightweight utility class (no external dependencies) that extracts browser name, OS name, and device type from a raw user agent string using regex pattern matching.

```php
class UserAgentParser
{
    public function __construct(private ?string $userAgent) {}

    public function browser(): string    // e.g. "Chrome", "Firefox", "Unknown"
    public function os(): string         // e.g. "macOS", "Windows", "Unknown"
    public function deviceType(): string // "desktop" or "mobile"
}
```

**Design decision:** A simple regex-based parser is preferred over a full UA parsing library (like `jenssegers/agent`) because:
- We only need marketing names and device classification, not detailed version info
- It keeps the dependency footprint minimal
- The common browsers/OS patterns are well-known and stable
- Fallback to "Unknown" handles edge cases gracefully

#### `App\Http\Requests\Settings\DestroySessionRequest`

Form request class to validate the password field for session termination.

```php
class DestroySessionRequest extends FormRequest
{
    public function rules(): array
    // ['password' => ['required', 'current_password']]
}
```

### Frontend

#### `resources/js/pages/settings/sessions.tsx`

The main page component receiving session data as Inertia props.

```typescript
type Session = {
    id: string;
    ip_address: string;
    browser: string;
    os: string;
    device_type: 'desktop' | 'mobile';
    last_active: string;       // Human-readable relative time
    is_current_device: boolean;
};

type Props = {
    sessions: Session[];
};
```

#### UI Components Used
- `Heading` (existing) — section header
- `Form` from `@inertiajs/react` — password confirmation form
- `Button`, `Label`, `Input` from shadcn/ui
- `Monitor`, `Smartphone` from lucide-react — device type icons
- `PasswordInput` (existing) — password field with show/hide toggle
- `InputError` (existing) — validation error display
- Sonner toast via existing `useFlashToast` hook

### Route Registration

Added to `routes/settings.php` within the `['auth', 'verified']` middleware group:

```php
Route::get('settings/sessions', [SessionController::class, 'edit'])->name('sessions.edit');
Route::delete('settings/sessions', [SessionController::class, 'destroy'])
    ->middleware('throttle:6,1')
    ->name('sessions.destroy');
```

**Design decision:** The DELETE route uses throttle middleware (6 attempts per minute) to prevent brute-force password attempts through the session termination endpoint.

### Settings Sidebar Integration

The settings layout (`resources/js/layouts/settings/layout.tsx`) will be updated to include a "Sessions" nav item after "Security", importing the route from `@/routes/sessions`.

## Data Models

### Sessions Table (existing — no migration needed)

| Column | Type | Description |
|--------|------|-------------|
| `id` | `string` (PK) | Session identifier (Laravel-generated hash) |
| `user_id` | `foreignUuid` (nullable, indexed) | Owner user UUID |
| `ip_address` | `string(45)` (nullable) | Client IP (IPv4/IPv6) |
| `user_agent` | `text` (nullable) | Raw user agent string |
| `payload` | `longText` | Serialized session payload |
| `last_activity` | `integer` (indexed) | Unix timestamp of last activity |

**Note:** The `sessions` table uses a string `id` (not UUID) because Laravel's database session driver generates its own session IDs. This is the one table that doesn't follow the UUID v7 convention.

### Session DTO (controller → frontend)

The controller transforms raw database rows into a structured array for the frontend:

```php
[
    'id' => $session->id,
    'ip_address' => $session->ip_address,
    'browser' => $parser->browser(),
    'os' => $parser->os(),
    'device_type' => $parser->deviceType(),
    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
    'is_current_device' => $session->id === $request->session()->getId(),
]
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Session ordering — current first, then by recency

*For any* set of sessions belonging to a user, the returned list SHALL have the current session at index 0, and all subsequent sessions sorted by `last_activity` in descending order.

**Validates: Requirements 1.1**

### Property 2: Session display contains all required fields

*For any* session with a non-null user agent and IP address, the formatted session object SHALL contain a non-empty browser name, a non-empty OS name, and the IP address value.

**Validates: Requirements 1.2, 1.3**

### Property 3: Relative timestamp formatting

*For any* Unix timestamp representing a past time, the relative time formatter SHALL produce a non-empty string matching the pattern of a human-readable relative duration (e.g., containing "ago").

**Validates: Requirements 1.4**

### Property 4: Browser name extraction without version

*For any* user agent string containing a known browser identifier followed by a version number, the parser SHALL return only the marketing name without any numeric version component.

**Validates: Requirements 3.1**

### Property 5: Operating system extraction without version

*For any* user agent string containing a known OS identifier followed by a version number, the parser SHALL return only the marketing OS name without any numeric version component.

**Validates: Requirements 3.2**

### Property 6: Device type classification

*For any* user agent string, if it contains a mobile indicator (e.g., "Mobile", "iPhone", "Android" with "Mobile"), the parser SHALL classify it as "mobile"; otherwise it SHALL classify it as "desktop".

**Validates: Requirements 3.3**

### Property 7: Terminate removes all sessions except current

*For any* user with N sessions (N ≥ 1), after executing the terminate-other-sessions action with a valid password, exactly 1 session SHALL remain — the current session — and N-1 sessions SHALL be deleted.

**Validates: Requirements 4.1**

## Error Handling

| Scenario | Handling |
|----------|----------|
| Invalid password on terminate | Return 422 with `password` validation error; clear password field on frontend |
| Server error during termination | Return 500; frontend shows error toast via Sonner; sessions remain unchanged |
| Null/empty user agent | Parser returns "Unknown" for browser and OS, "desktop" for device type |
| Null IP address | Display "Unknown" in the UI |
| User not authenticated | Redirect to login (auth middleware) |
| User email not verified | Redirect to verification page (verified middleware) |
| Rate limit exceeded on destroy | Return 429 Too Many Requests |

## Testing Strategy

### Unit Tests (Pest)

Test the `UserAgentParser` class in isolation:
- Known browser strings produce correct marketing names
- Known OS strings produce correct marketing names
- Mobile indicators correctly classify device type
- Empty/null/gibberish input returns "Unknown"/"Unknown"/"desktop" defaults
- Edge cases: bots, unusual agents, very long strings

### Feature Tests (Pest)

- `GET /settings/sessions` returns 200 with session data for authenticated user
- `GET /settings/sessions` redirects to login for guests
- `GET /settings/sessions` redirects to verification for unverified users
- `DELETE /settings/sessions` with correct password removes other sessions
- `DELETE /settings/sessions` with incorrect password returns validation error
- `DELETE /settings/sessions` is throttled after 6 attempts
- Session list ordering: current session first, then by last_activity desc

### Property-Based Tests (Pest with `pestphp/pest-plugin-stressless` or custom generators)

Each property test runs a minimum of 100 iterations with randomized input.

- **Property 1:** Generate random session sets (1-20 sessions), assign random timestamps, verify ordering invariant
- **Property 4:** Generate user agent strings with known browser patterns + version suffixes, verify version is stripped
- **Property 5:** Generate user agent strings with known OS patterns + version suffixes, verify version is stripped
- **Property 6:** Generate user agent strings with/without mobile indicators, verify classification
- **Property 7:** Generate users with 1-20 sessions, execute terminate, verify exactly 1 remains

**Library:** Property-based tests will use a custom Pest dataset generator approach — generating randomized inputs via PHP's `random_int`, `str_repeat`, and combinatorial string builders within Pest's `dataset()` feature, running each property assertion 100+ times.

**Tag format:** Each property test includes a comment:
```php
// Feature: session-management, Property {N}: {property_text}
```
