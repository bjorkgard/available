# Implementation Plan: Congregation Management

## Overview

This plan rearchitects the Team-based system into a Congregation-based domain model. Implementation proceeds in dependency order: database migrations first, then models/enums, actions/controllers, middleware, frontend pages, and finally tests. Each step builds incrementally so there is no orphaned code.

## Tasks

- [x] 1. Database migrations and schema changes
  - [x] 1.1 Create Kingdom Halls migration
    - Create migration with UUID primary key, `street_address` (string 255), `zip_code` (string 20), `city` (string 100), `number_of_rooms` (integer, min 1 max 50), timestamps
    - Use `$table->uuid('id')->primary()` per project conventions
    - _Requirements: 3.1, 9.1, 9.4_

  - [x] 1.2 Create Rooms migration
    - Create migration with UUID primary key, `kingdom_hall_id` (foreignUuid, cascade delete), `name` (string 255), `sort_order` (integer), timestamps
    - _Requirements: 3.4, 9.1, 9.4_

  - [x] 1.3 Rename and restructure teams table to congregations
    - Rename `teams` → `congregations`
    - Drop `is_personal` column
    - Add `congregation_number` (string 20, unique, not null)
    - Add `kingdom_hall_id` (foreignUuid, nullable, references kingdom_halls)
    - Convert `id` column to UUID primary key
    - Retain soft-delete (`deleted_at`) column
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 9.1, 9.4_

  - [x] 1.4 Rename and restructure team_members to congregation_members
    - Rename `team_members` → `congregation_members`
    - Convert to UUID PK and FKs
    - Add unique constraint on `(congregation_id, user_id)`
    - Update role column values (owner → superadmin)
    - _Requirements: 1.1, 5.5, 9.1, 9.4_

  - [x] 1.5 Rename and restructure team_invitations to congregation_invitations
    - Rename `team_invitations` → `congregation_invitations`
    - Convert to UUID PK and FKs
    - Add `code` (string 64, unique), `name` (string 255), `expires_at` (timestamp nullable), `accepted_at` (timestamp nullable), `invited_by` (foreignUuid)
    - _Requirements: 1.1, 8.1, 8.2, 9.1, 9.4_

  - [x] 1.6 Update users table
    - Rename `current_team_id` → `current_congregation_id`
    - Convert user `id` and `current_congregation_id` to UUID types
    - _Requirements: 1.1, 9.1, 9.4_

- [x] 2. Models, Enums, and Factories
  - [x] 2.1 Create CongregationRole enum
    - Create `app/Enums/CongregationRole.php` with cases: Superadmin, Admin, Member (string-backed)
    - Replace existing TeamRole enum references
    - _Requirements: 5.1_

  - [x] 2.2 Create KingdomHall model with factory
    - Create model with `HasUuids` trait, fillable attributes, relationships (`hasMany` Congregation, `hasMany` Room)
    - Create factory with Faker data for street_address, zip_code, city, number_of_rooms
    - _Requirements: 3.1, 3.2, 9.3_

  - [x] 2.3 Create Room model with factory
    - Create model with `HasUuids` trait, fillable attributes, `belongsTo` KingdomHall relationship
    - Create factory with Faker data
    - _Requirements: 3.4, 9.3_

  - [x] 2.4 Rename Team model to Congregation
    - Rename `app/Models/Team.php` → `app/Models/Congregation.php`
    - Update class name, table name (`congregations`), add `HasUuids` trait, add `SoftDeletes`
    - Add `congregation_number` to fillable, add `belongsTo` KingdomHall, update relationships
    - Update factory: rename TeamFactory → CongregationFactory, add `congregation_number` generation
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 9.3_

  - [x] 2.5 Rename Membership model and update relationships
    - Update `app/Models/Membership.php` table to `congregation_members`, add `HasUuids` trait
    - Update foreign key references from team to congregation
    - Cast role to `CongregationRole` enum
    - _Requirements: 1.1, 5.5, 9.3_

  - [x] 2.6 Create CongregationInvitation model with factory
    - Create model with `HasUuids` trait, `belongsTo` Congregation, `belongsTo` User (inviter)
    - Add `code`, `name`, `email`, `role`, `expires_at`, `accepted_at` attributes
    - Create factory with expiry logic (72 hours from creation)
    - _Requirements: 8.1, 8.2, 9.3_

  - [x] 2.7 Update User model
    - Add `HasUuids` trait, rename `current_team_id` → `current_congregation_id`
    - Update `currentTeam` → `currentCongregation` relationship
    - Update `belongsToMany` to reference Congregation model through `congregation_members`
    - Add helper methods: `congregationRole()`, `isSuperadmin()`, `isAdmin()`, `isMember()`
    - _Requirements: 1.1, 1.4, 5.5, 9.3_

  - [x] 2.8 Update UserFactory for congregation-based registration
    - Remove personal team creation logic
    - Ensure factory works with UUID primary keys
    - _Requirements: 1.4, 9.3_

- [x] 3. Checkpoint - Models and migrations compile
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Actions (business logic layer)
  - [x] 4.1 Rewrite CreateNewUser action
    - Modify `app/Actions/Fortify/CreateNewUser.php` to accept congregation_name and congregation_number
    - Create User + Congregation atomically in a DB transaction
    - Assign admin role to registering user via Membership
    - Remove personal team creation logic
    - Validate congregation_number format (alphanumeric, 1-20 chars, uppercase)
    - Validate uniqueness of congregation_number and email
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

  - [x] 4.2 Create CreateKingdomHall action
    - Create `app/Actions/Congregations/CreateKingdomHall.php`
    - Validate street_address, zip_code, city, number_of_rooms (1–50)
    - Create KingdomHall record, auto-generate Room records ("Room 1" through "Room N")
    - Link congregation to Kingdom Hall
    - Assign superadmin role to the user
    - Wrap in DB transaction
    - _Requirements: 3.1, 3.3, 4.3, 5.8_

  - [x] 4.3 Create CreateCongregation action
    - Create `app/Actions/Congregations/CreateCongregation.php`
    - Accept congregation name, number, and initial user email/name
    - Create Congregation, link to Kingdom Hall, send invitation to initial user
    - _Requirements: 6.5, 6.6_

  - [x] 4.4 Create SendInvitation action
    - Create `app/Actions/Congregations/SendInvitation.php`
    - Generate unique invitation code, set 72-hour expiry
    - Handle existing users: add directly to congregation with invited role
    - Handle new users: send email with password-setup link
    - Replace duplicate pending invitations for same email+congregation
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7, 8.8_

  - [x] 4.5 Create MoveCongregation action
    - Create `app/Actions/Congregations/MoveCongregation.php`
    - Validate target Kingdom Hall exists and differs from current
    - Update congregation's `kingdom_hall_id`
    - Revoke superadmin role for users whose only congregation in the original KH was the moved one
    - Preserve all memberships and congregation-scoped roles
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

  - [x] 4.6 Create DeleteCongregation action
    - Create `app/Actions/Congregations/DeleteCongregation.php`
    - Soft-delete congregation
    - Remove users who belong exclusively to this congregation
    - For multi-congregation users, switch `current_congregation_id` to another active congregation
    - Cancel pending invitations
    - _Requirements: 11.1, 11.2, 11.4, 11.5, 11.6, 7.4_

  - [x] 4.7 Create DeleteKingdomHall action
    - Create `app/Actions/Congregations/DeleteKingdomHall.php`
    - Delete all congregations connected to the KH (triggering DeleteCongregation for each)
    - Remove exclusive users
    - Delete KH record and associated rooms
    - _Requirements: 11.3, 3.5_

  - [x] 4.8 Create UpdateKingdomHall action
    - Create `app/Actions/Congregations/UpdateKingdomHall.php`
    - Validate room count changes: increase → auto-generate new rooms; decrease → remove highest-numbered rooms if no future bookings
    - Reject decrease if rooms have future bookings
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 3.6, 3.7_

- [x] 5. Checkpoint - Actions compile and unit logic is sound
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Policies and Authorization
  - [x] 6.1 Create CongregationPolicy
    - Create `app/Policies/CongregationPolicy.php`
    - Superadmin: full access to all congregations in their Kingdom Hall
    - Admin: full access to own congregation only
    - Member: view-only access to own congregation
    - _Requirements: 5.2, 5.3, 5.4, 5.7, 7.7_

  - [x] 6.2 Create KingdomHallPolicy
    - Create `app/Policies/KingdomHallPolicy.php`
    - Restrict management (update, delete) to superadmin role
    - _Requirements: 6.1, 6.11, 6.12_

  - [x] 6.3 Create MemberPolicy
    - Create `app/Policies/MemberPolicy.php`
    - Control invite/edit/remove based on role hierarchy
    - Superadmin: manage any member in KH congregations
    - Admin: manage members in own congregation only (member/admin roles only)
    - Enforce last-admin invariant checks
    - _Requirements: 5.6, 6.7, 6.8, 6.9, 6.10, 7.1, 7.2, 7.3, 7.6, 8.5, 8.6_

- [x] 7. Middleware and Routing
  - [x] 7.1 Create EnsureHasKingdomHall middleware
    - Create `app/Http/Middleware/EnsureHasKingdomHall.php`
    - Check if authenticated user's current congregation has a linked Kingdom Hall
    - Redirect to `/setup` if no Kingdom Hall exists
    - Exempt routes: `setup.*`, `logout`
    - _Requirements: 4.1, 4.2, 4.4_

  - [x] 7.2 Update route definitions
    - Rename route prefix from `{current_team}` to `{current_congregation}`
    - Add setup wizard routes (`GET /setup`, `POST /setup`) exempt from KH middleware
    - Add congregation management routes (members, kingdom-hall, move, delete)
    - Register EnsureHasKingdomHall middleware in the middleware stack
    - _Requirements: 4.1, 4.2_

- [x] 8. Controllers and Form Requests
  - [x] 8.1 Create SetupWizardController
    - Create `app/Http/Controllers/Congregations/SetupWizardController.php`
    - `show()`: render setup wizard page
    - `store()`: validate and call CreateKingdomHall action
    - Create corresponding Form Request with validation rules
    - _Requirements: 4.1, 4.3, 4.5, 4.6_

  - [x] 8.2 Update registration controller/action for congregation fields
    - Add congregation_name and congregation_number to registration validation
    - Update Fortify registration response to redirect to setup wizard
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

  - [x] 8.3 Create KingdomHallController
    - Create `app/Http/Controllers/Congregations/KingdomHallController.php`
    - `show()`: render KH details page
    - `update()`: validate and call UpdateKingdomHall action
    - `destroy()`: call DeleteKingdomHall action (with confirmation)
    - `addCongregation()`: call CreateCongregation action
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.11, 6.12_

  - [x] 8.4 Create MemberController
    - Create `app/Http/Controllers/Congregations/MemberController.php`
    - `index()`: list members with roles
    - `invite()`: call SendInvitation action
    - `update()`: update member role
    - `destroy()`: remove member from congregation
    - _Requirements: 7.1, 7.2, 7.3, 8.1, 8.5, 8.6_

  - [x] 8.5 Create CongregationController
    - Create `app/Http/Controllers/Congregations/CongregationController.php`
    - `destroy()`: call DeleteCongregation action
    - `move()`: call MoveCongregation action
    - _Requirements: 7.4, 7.5, 10.1, 10.2, 11.1, 11.2_

  - [x] 8.6 Create InvitationAcceptController
    - Create `app/Http/Controllers/Congregations/InvitationAcceptController.php`
    - Handle invitation acceptance: validate code, check expiry, create user or add membership
    - _Requirements: 8.1, 8.3, 8.7_

- [x] 9. Checkpoint - Backend routes and controllers functional
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Frontend - TypeScript types and shared components
  - [x] 10.1 Update TypeScript type definitions
    - Add/update types in `resources/js/types/`: Congregation, KingdomHall, Room, Membership, CongregationInvitation
    - Remove Team-related types, update User type with `current_congregation_id`
    - _Requirements: 1.1, 9.2_

  - [x] 10.2 Create CongregationSwitcher component
    - Create `resources/js/components/congregation-switcher.tsx` replacing TeamSwitcher
    - Show current congregation name, list all user's congregations, switch functionality
    - _Requirements: 1.1_

  - [x] 10.3 Create InviteMemberDialog component
    - Create `resources/js/components/invite-member-dialog.tsx`
    - Form with name, email, role select fields
    - Role options filtered by viewer's role (superadmin sees all, admin sees member/admin)
    - _Requirements: 7.1, 8.1, 8.5, 8.6_

  - [x] 10.4 Create RoleSelect component
    - Create `resources/js/components/role-select.tsx`
    - Dropdown filtered by viewer's own role for role assignment
    - _Requirements: 5.1, 6.8, 6.9_

- [x] 11. Frontend - Pages
  - [x] 11.1 Update Register page
    - Modify `resources/js/pages/auth/register.tsx`
    - Add congregation_name and congregation_number fields to form
    - Add validation error display for new fields
    - _Requirements: 2.1, 2.6_

  - [x] 11.2 Create Setup Wizard page
    - Create `resources/js/pages/setup/index.tsx`
    - Form with street_address, zip_code, city, number_of_rooms fields
    - Validation error display, field value preservation on error
    - _Requirements: 4.1, 4.3, 4.5_

  - [x] 11.3 Create Members management page
    - Create `resources/js/pages/congregations/members/index.tsx`
    - List members with roles, invite button, edit role, remove member actions
    - Role-based action visibility (members see no management controls)
    - _Requirements: 7.1, 7.2, 7.3, 5.4_

  - [x] 11.4 Create Kingdom Hall details page
    - Create `resources/js/pages/congregations/kingdom-hall/show.tsx`
    - Display KH info, rooms list, edit form (superadmin only)
    - Add/remove congregation controls (superadmin only)
    - _Requirements: 6.1, 6.2, 6.5_

  - [x] 11.5 Update app layout and sidebar
    - Replace TeamSwitcher with CongregationSwitcher in sidebar
    - Update navigation links from team-based to congregation-based routes
    - _Requirements: 1.1_

  - [x] 11.6 Rename pages/teams directory to pages/congregations
    - Move and rename existing team pages to congregation namespace
    - Update all Inertia render paths
    - _Requirements: 1.1_

- [x] 12. Checkpoint - Full stack compiles and renders
  - Ensure all tests pass, ask the user if questions arise.

- [x] 13. Notification and Email
  - [x] 13.1 Create InvitationNotification
    - Create `app/Notifications/Congregations/InvitationNotification.php`
    - Email with invitation link containing code, congregation name, role info
    - Link expires after 72 hours
    - _Requirements: 8.1, 8.4_

- [x] 14. Feature tests
  - [x] 14.1 Write registration feature tests
    - Test valid registration creates User + Congregation + admin membership
    - Test duplicate congregation_number rejection
    - Test duplicate email rejection
    - Test invalid congregation_number format rejection
    - Test missing field validation
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

  - [x] 14.2 Write Setup Wizard feature tests
    - Test wizard renders for user without KH
    - Test valid submission creates KH + rooms + assigns superadmin
    - Test middleware redirects to wizard when no KH
    - Test invalid submission returns errors
    - _Requirements: 4.1, 4.2, 4.3, 4.5, 4.6_

  - [x] 14.3 Write congregation management feature tests
    - Test congregation deletion soft-deletes and removes exclusive users
    - Test multi-congregation user retained on deletion
    - Test congregation movement updates KH association
    - Test move revokes superadmin for exclusive KH users
    - Test invalid move target rejected
    - _Requirements: 10.1, 10.3, 10.4, 10.5, 11.1, 11.4, 11.6_

  - [x] 14.4 Write invitation feature tests
    - Test invitation sends email with valid link
    - Test existing user added directly without password setup
    - Test expired invitation rejected
    - Test duplicate invitation replaced
    - Test admin cannot invite to other congregations
    - Test superadmin can invite to any KH congregation
    - _Requirements: 8.1, 8.2, 8.3, 8.5, 8.6, 8.7, 8.8_

  - [x] 14.5 Write role and authorization feature tests
    - Test superadmin access to all KH congregations
    - Test admin restricted to own congregation
    - Test member cannot perform management actions
    - Test last admin removal prevented
    - Test last superadmin demotion prevented
    - _Requirements: 5.2, 5.3, 5.4, 5.6, 6.10, 7.6, 7.7_

  - [x] 14.6 Write Kingdom Hall management feature tests
    - Test room increase generates new rooms
    - Test room decrease removes highest-numbered rooms
    - Test room decrease rejected when rooms have bookings
    - Test KH deletion rejected when congregations exist (via policy)
    - Test KH deletion cascades when superadmin confirms
    - _Requirements: 3.3, 3.5, 3.6, 3.7, 6.2, 6.3, 6.4, 11.3_

- [x] 15. Property-based tests
  - [x] 15.1 Write property test for congregation number validation
    - **Property 1: Congregation number validation**
    - Generate random strings, verify only uppercase alphanumeric 1–20 char strings pass validation
    - Use `repeat(100)` with Faker-generated data
    - **Validates: Requirements 1.3, 2.7**

  - [x] 15.2 Write property test for registration validation rejects incomplete submissions
    - **Property 2: Registration validation rejects incomplete or invalid submissions**
    - Generate random incomplete/invalid form data, verify rejection with correct errors
    - Use `repeat(100)`
    - **Validates: Requirements 2.1, 2.6**

  - [x] 15.3 Write property test for registration uniqueness constraints
    - **Property 3: Registration uniqueness constraints**
    - Generate duplicate congregation numbers and emails, verify rejection
    - Use `repeat(100)`
    - **Validates: Requirements 2.4, 2.5**

  - [x] 15.4 Write property test for room auto-generation
    - **Property 5: Room auto-generation produces correctly named rooms**
    - Generate random room counts (1–50), verify correct room names and count
    - Use `repeat(100)`
    - **Validates: Requirements 3.3, 3.6**

  - [x] 15.5 Write property test for setup wizard gate
    - **Property 7: Setup wizard gate blocks all protected routes**
    - Generate random route paths, verify middleware redirects without KH
    - Use `repeat(100)`
    - **Validates: Requirements 4.2, 4.4**

  - [x] 15.6 Write property test for role scope enforcement
    - **Property 9: Role scope enforcement**
    - Generate random role+action combinations, verify scope enforcement
    - Use `repeat(100)`
    - **Validates: Requirements 5.2, 5.3, 5.4, 7.1, 7.7**

  - [x] 15.7 Write property test for last privileged role invariant
    - **Property 10: Last privileged role invariant**
    - Generate last-admin/superadmin removal scenarios, verify prevention
    - Use `repeat(100)`
    - **Validates: Requirements 5.6, 6.10, 7.6**

  - [x] 15.8 Write property test for invitation expiry enforcement
    - **Property 13: Invitation expiry enforcement**
    - Generate invitations with random timestamps, verify expiry enforcement
    - Use `repeat(100)`
    - **Validates: Requirements 8.1, 8.7**

  - [x] 15.9 Write property test for duplicate invitation replacement
    - **Property 15: Duplicate invitation replacement**
    - Generate duplicate invitations, verify exactly one pending per email-congregation pair
    - Use `repeat(100)`
    - **Validates: Requirements 8.8**

  - [x] 15.10 Write property test for congregation move preserves membership
    - **Property 16: Congregation move preserves membership and roles**
    - Generate random congregation memberships, move congregation, verify preservation
    - Use `repeat(100)`
    - **Validates: Requirements 10.3**

  - [x] 15.11 Write property test for exclusive user removal on deletion
    - **Property 19: Exclusive user removal on entity deletion**
    - Generate random user membership graphs, delete congregation, verify exclusive removal
    - Use `repeat(100)`
    - **Validates: Requirements 11.1, 11.2, 11.3**

- [x] 16. Final checkpoint - Full test suite passes
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests use Pest's `repeat(100)` with Faker-generated data for correctness validation
- Unit/feature tests validate specific examples and edge cases
- Migrations must run before any model or action work begins
- The existing Team model, factories, and tests must be renamed/refactored rather than duplicated

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "1.6"] },
    { "id": 2, "tasks": ["1.4", "1.5"] },
    { "id": 3, "tasks": ["2.1", "2.2", "2.3"] },
    { "id": 4, "tasks": ["2.4", "2.5", "2.6", "2.7", "2.8"] },
    { "id": 5, "tasks": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7", "4.8"] },
    { "id": 6, "tasks": ["6.1", "6.2", "6.3"] },
    { "id": 7, "tasks": ["7.1", "7.2"] },
    { "id": 8, "tasks": ["8.1", "8.2", "8.3", "8.4", "8.5", "8.6"] },
    { "id": 9, "tasks": ["10.1", "13.1"] },
    { "id": 10, "tasks": ["10.2", "10.3", "10.4"] },
    { "id": 11, "tasks": ["11.1", "11.2", "11.3", "11.4", "11.5", "11.6"] },
    { "id": 12, "tasks": ["14.1", "14.2", "14.3", "14.4", "14.5", "14.6"] },
    { "id": 13, "tasks": ["15.1", "15.2", "15.3", "15.4", "15.5", "15.6", "15.7", "15.8", "15.9", "15.10", "15.11"] }
  ]
}
```
