# Product

A Kingdom Hall room-scheduling app for congregations to coordinate shared spaces. Congregations that share a Kingdom Hall can see room availability at a glance and book slots without conflicts.

## Target Users

Congregation elders (superadmins), ministerial servants (admins), and publishers (members) who share a Kingdom Hall and need to coordinate room usage for meetings, service groups, and events.

## Domain Model

- **Kingdom Hall** — a physical building with an address and multiple rooms
- **Room** — a bookable space inside a Kingdom Hall (e.g., main hall, second school, library)
- **Congregation** — an organization tied to a Kingdom Hall; has members with roles
- **Membership** — a user's role within a congregation (Superadmin, Admin, Member)
- **Booking** — a scheduled reservation linking a user, congregation, one or more rooms, and a time range
- **RecurrencePattern** — rules (frequency, end condition) defining how a booking repeats
- **Invitation** — a pending invite to join a congregation with a specific role
- **Session** — an authenticated browser/device session that users can view and revoke

## Current State

The welcome/landing page is a custom product page with animated hero, calendar view showcase (3D scroll-based), step-by-step onboarding explanation, invitation flow preview, feature grid, and CTA sections. Uses `motion/react` for scroll-linked parallax and reveal animations (respects `prefers-reduced-motion`).

The app has full authentication (login, register, passkeys, 2FA), congregation management (create via setup wizard, invite members, switch congregations, edit settings), Kingdom Hall management (address, rooms, multi-congregation sharing), user settings (profile, security, sessions, appearance), and session management (view active sessions, revoke other sessions).

Registration is a single-step form collecting user account, congregation details, and Kingdom Hall address. If the address matches an existing hall, a warning displays the hall's superadmin contacts so the user can request an invitation instead of creating a duplicate.

The calendar is the default landing page with a fully functional room-booking system:
- **Month view** — 7-column grid showing bookings as color-coded blocks (by congregation). Max 4 per day, "+N more" indicator. Drag-and-drop to move bookings between dates.
- **Week view** — 7 day columns with bookings positioned pixel-accurately on a 15-minute grid. Overlapping bookings render side-by-side. Drag-and-drop with ghost preview.
- **Day view** — Room columns with bookings per room, same grid positioning. Drag-and-drop within rooms.

Booking features:
- Create/edit/delete bookings with recurrence support (daily/weekly/monthly/yearly)
- Unified context menu (right-click) with Create, Edit, Delete actions based on permissions
- Drag-and-drop rescheduling with ghost preview and 15-min grid snapping
- Real-time updates via Laravel Reverb WebSocket (toast notifications for other users' changes)
- Role-based access: Members edit own, Admins edit congregation's, Superadmins edit any in hall
- Superadmins can book on behalf of any congregation in the Kingdom Hall
- Email notifications on third-party modifications/deletions
- Confirmation dialogs for deletes (with recurring scope options)
- Automated cleanup of bookings older than 6 months (daily scheduled command)
- Member removal handles booking transfer/deletion

View switching: dropdown menu with keyboard shortcuts (⌘0 month, ⌘1 week, ⌘2 day). Responsive defaults: mobile → day, tablet → week, desktop → month.

## Design Principles

- Clarity over density: scannable availability in under two seconds
- Respect the context: works fast mid-conversation or between meetings
- Quiet confidence: no urgency theater, calm certainty
- Congregation-first: shared awareness of who has what and when
- Physical grounding: tangible copy and affordances for real spaces
