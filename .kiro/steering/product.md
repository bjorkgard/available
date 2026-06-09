# Product

A Kingdom Hall room-scheduling app for congregations to coordinate shared spaces. Congregations that share a Kingdom Hall can see room availability at a glance and book slots without conflicts.

## Target Users

Congregation elders (superadmins), ministerial servants (admins), and publishers (members) who share a Kingdom Hall and need to coordinate room usage for meetings, service groups, and events.

## Domain Model

- **Kingdom Hall** — a physical building with an address and multiple rooms
- **Room** — a bookable space inside a Kingdom Hall (e.g., main hall, second school, library)
- **Congregation** — an organization tied to a Kingdom Hall; has members with roles
- **Membership** — a user's role within a congregation (Superadmin, Admin, Member)
- **Invitation** — a pending invite to join a congregation with a specific role
- **Session** — an authenticated browser/device session that users can view and revoke

## Current State

The app has full authentication (login, register, passkeys, 2FA), congregation management (create via setup wizard, invite members, switch congregations, edit settings), Kingdom Hall management (address, rooms, multi-congregation sharing), user settings (profile, security, sessions, appearance), and session management (view active sessions, revoke other sessions).

The calendar is the default landing page with three zoom levels:
- **Month view** — 7-column grid with date numbers top-left, space for booking indicators below. Locale-aware week start (Monday for sv-SE). Today highlighted with a blue border.
- **Week view** — 7 day columns with 2-hour dashed time slot rows. Navigate week-by-week with arrows.
- **Day view** — Full-height view with room columns (from the congregation's Kingdom Hall). 2-hour time slots. Navigate day-by-day with arrows.

View switching: dropdown menu with keyboard shortcuts (⌘0 month, ⌘1 week, ⌘2 day). Responsive defaults: mobile → day, tablet → week, desktop → month. The actual room-booking/scheduling logic (creating, editing, deleting bookings) is not yet implemented.

## Design Principles

- Clarity over density: scannable availability in under two seconds
- Respect the context: works fast mid-conversation or between meetings
- Quiet confidence: no urgency theater, calm certainty
- Congregation-first: shared awareness of who has what and when
- Physical grounding: tangible copy and affordances for real spaces
