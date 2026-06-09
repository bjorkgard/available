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

The app has full authentication (login, register, passkeys, 2FA), congregation management (create via setup wizard, invite members, switch congregations, edit settings), Kingdom Hall management (address, rooms, multi-congregation sharing), user settings (profile, security, sessions, appearance), and session management (view active sessions, revoke other sessions). The room-booking/scheduling feature itself is not yet implemented — the dashboard is a placeholder.

## Design Principles

- Clarity over density: scannable availability in under two seconds
- Respect the context: works fast mid-conversation or between meetings
- Quiet confidence: no urgency theater, calm certainty
- Congregation-first: shared awareness of who has what and when
- Physical grounding: tangible copy and affordances for real spaces
