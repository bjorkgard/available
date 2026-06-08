# Requirements Document

## Introduction

This feature rearchitects the existing Team-based registration and management system into a Congregation-based system tailored for Jehovah's Witnesses Kingdom Hall room-booking. The current "Team" concept is renamed to "Congregation," personal teams are removed, and a new "Kingdom Hall" entity is introduced as the physical building that congregations share. Registration is updated to require a congregation name and number, and a setup wizard enforces Kingdom Hall creation before the app is usable. A three-tier role system (superadmin, admin, member) governs access across congregations sharing the same Kingdom Hall.

## Glossary

- **Congregation**: An organizational unit representing a single Jehovah's Witnesses congregation. Replaces the existing "Team" model. Has a name, a unique congregation number, and belongs to exactly one Kingdom Hall.
- **Congregation_Number**: An official alphanumeric identifier assigned to each Jehovah's Witnesses congregation. Must be unique across the system.
- **Kingdom_Hall**: A physical building where one or more congregations meet. Has a street address, zip code, city, and a specified number of rooms.
- **Room**: A bookable space within a Kingdom Hall. Auto-generated based on the number of rooms specified during Kingdom Hall creation.
- **Superadmin**: The highest-privilege role. Scoped to all congregations connected to their Kingdom Hall. The first user to create a Kingdom Hall receives this role.
- **Admin**: A mid-privilege role scoped to a single congregation. Every congregation must have at least one admin.
- **Member**: The default role with no management permissions, scoped to a single congregation.
- **Setup_Wizard**: A forced onboarding flow that requires a newly registered user to create a Kingdom Hall before accessing the main application.
- **Invitation**: A mechanism to add users to a congregation by sending an email with a link to set a password.
- **System**: The congregation management application as a whole.

## Requirements

### Requirement 1: Rename Team to Congregation

**User Story:** As a developer, I want the existing Team model and all references renamed to Congregation, so that the domain language accurately reflects the Jehovah's Witnesses organizational structure.

#### Acceptance Criteria

1. THE System SHALL use "Congregation" in place of "Team" across all models, database tables, enums, controllers, routes, and UI components.
2. THE Congregation model SHALL have a name attribute (string, required, maximum 255 characters).
3. THE Congregation model SHALL have a congregation_number attribute (string, required, unique across the system, 1 to 20 characters, containing only digits and uppercase Latin letters A–Z).
4. WHEN a user registers, THE System SHALL create the user account without creating a personal congregation and without assigning congregation membership.
5. THE System SHALL retain soft-delete capability on the Congregation model.

### Requirement 2: Registration with Congregation

**User Story:** As a new user, I want to register with my congregation name and number, so that I am immediately associated with my congregation upon account creation.

#### Acceptance Criteria

1. WHEN a user submits the registration form, THE System SHALL require the following fields: congregation name (string, max 255 characters), congregation number (alphanumeric string, max 20 characters), user name (string, max 255 characters), email (valid email format, max 255 characters), password (min 8 characters), and password confirmation.
2. WHEN a valid registration is submitted, THE System SHALL atomically create a new User record and a new Congregation record with the provided name and congregation number within a single database transaction.
3. WHEN a valid registration is submitted, THE System SHALL assign the registering user as admin of the newly created Congregation.
4. IF the congregation number already exists in the system, THEN THE System SHALL reject the registration and display a validation error indicating the number is already in use.
5. IF the email address already exists in the system, THEN THE System SHALL reject the registration and display a validation error indicating the email is already in use.
6. IF any required registration field is missing or invalid, THEN THE System SHALL reject the registration, preserve all previously entered field values except the password fields, and display field-specific validation errors adjacent to each invalid field.
7. IF the congregation number contains non-alphanumeric characters, THEN THE System SHALL reject the registration and display a validation error indicating the number must be alphanumeric.

### Requirement 3: Kingdom Hall Entity

**User Story:** As a congregation admin, I want to define the Kingdom Hall my congregation meets in, so that rooms can be managed and shared across all congregations in the same building.

#### Acceptance Criteria

1. THE Kingdom_Hall model SHALL have a street address (string, required, max 255 characters), zip code (string, required, max 20 characters), city (string, required, max 100 characters), and number of rooms (integer, required, minimum 1, maximum 50).
2. THE System SHALL enforce that every Congregation belongs to exactly one Kingdom Hall.
3. WHEN a Kingdom Hall is created with a specified number of rooms, THE System SHALL auto-generate Room records named "Room 1", "Room 2", through "Room N" where N equals the number of rooms specified.
4. THE Room model SHALL belong to exactly one Kingdom Hall.
5. IF a user attempts to delete a Kingdom Hall that still has one or more associated Congregations, THEN THE System SHALL reject the deletion and return an error message indicating the hall cannot be removed while congregations are assigned to it.
6. WHEN the number of rooms on an existing Kingdom Hall is increased from N to M (where M > N), THE System SHALL auto-generate additional Room records named "Room N+1" through "Room M".
7. IF the number of rooms on an existing Kingdom Hall is decreased to a value lower than the current count of associated Room records, THEN THE System SHALL reject the update and return an error message indicating that rooms must be removed individually before reducing the count.

### Requirement 4: Setup Wizard

**User Story:** As a newly registered user, I want to be guided through Kingdom Hall creation immediately after registration, so that my congregation has a physical location configured before I can use the application.

#### Acceptance Criteria

1. WHEN a user completes registration and their Congregation has no linked Kingdom Hall, THE System SHALL redirect the user to the Setup Wizard.
2. WHILE a user's Congregation has no linked Kingdom Hall, THE System SHALL prevent access to all application pages except the Setup Wizard and logout.
3. WHEN the user submits the Setup Wizard with a valid street address, zip code, city, and number of rooms (integer between 1 and 50), THE System SHALL create the Kingdom Hall, auto-generate Room records named "Room 1" through "Room N", link the Congregation to the Kingdom Hall, and assign the superadmin role to the user.
4. IF the user attempts to navigate away from the Setup Wizard before completion, THEN THE System SHALL redirect back to the Setup Wizard.
5. IF the user submits the Setup Wizard with missing or invalid fields, THEN THE System SHALL reject the submission, display field-specific validation errors, and retain the previously entered values.
6. IF Kingdom Hall creation fails after submission, THEN THE System SHALL not link the Congregation or assign the superadmin role, and SHALL display an error message indicating the operation failed.

### Requirement 5: Role Hierarchy and Scope

**User Story:** As a system administrator, I want clearly defined roles with appropriate scopes, so that users can only perform actions within their authority.

#### Acceptance Criteria

1. THE System SHALL support exactly three roles: superadmin, admin, and member.
2. THE Superadmin role SHALL be scoped to all congregations connected to the same Kingdom Hall as the superadmin's congregation.
3. THE Admin role SHALL be scoped to only the admin's own congregation.
4. THE Member role SHALL have no congregation management permissions (inviting users, editing user details, removing users, deleting the congregation, or moving the congregation) and SHALL only be able to view congregation information and book rooms.
5. THE System SHALL store a user's role as part of their membership in each congregation, allowing different roles in different congregations.
6. THE System SHALL enforce that every Congregation has at least one user with the admin role at all times by preventing any operation that would remove the last admin, including role demotion, user removal, or the admin leaving the congregation.
7. IF a user attempts an action that exceeds their role's scope, THEN THE System SHALL deny the action and display an error indicating insufficient permissions.
8. WHEN the first user creates a Kingdom Hall via the Setup Wizard, THE System SHALL assign the superadmin role to that user.

### Requirement 6: Superadmin Permissions

**User Story:** As a superadmin, I want to manage all congregations in my Kingdom Hall, so that I can oversee the entire building's organizational structure.

#### Acceptance Criteria

1. THE Superadmin SHALL be able to update the Kingdom Hall details (street address, zip code, city, and number of rooms).
2. WHEN the superadmin increases the number of rooms, THE System SHALL auto-generate additional Room records (named sequentially following existing rooms) to match the new total.
3. WHEN the superadmin decreases the number of rooms, THE System SHALL remove the highest-numbered Room records that exceed the new total, provided those rooms have no future bookings.
4. IF the superadmin attempts to decrease the number of rooms and one or more of the rooms to be removed has future bookings, THEN THE System SHALL reject the update and display an error indicating which rooms still have bookings.
5. THE Superadmin SHALL be able to add new congregations to their Kingdom Hall by providing a congregation name, congregation number, and an initial user's name and email.
6. WHEN the superadmin adds a new congregation, THE System SHALL send an invitation email to the specified user to set a password and become admin of that congregation.
7. THE Superadmin SHALL be able to invite users to any congregation connected to their Kingdom Hall.
8. THE Superadmin SHALL be able to assign the superadmin role to any user who belongs to a congregation connected to the same Kingdom Hall.
9. THE Superadmin SHALL be able to assign the admin role to any user who belongs to a congregation connected to the same Kingdom Hall.
10. IF the superadmin attempts to revoke their own superadmin role and no other superadmin exists for the Kingdom Hall, THEN THE System SHALL prevent the action and display an error indicating another superadmin must be assigned first.
11. THE Superadmin SHALL be able to delete any congregation connected to their Kingdom Hall.
12. THE Superadmin SHALL be able to delete the Kingdom Hall along with all its connected congregations.
13. THE Superadmin SHALL have all admin permissions (invite users, edit user details, remove users) within their own congregation.

### Requirement 7: Admin Permissions

**User Story:** As a congregation admin, I want to manage my congregation's members and settings, so that I can maintain my congregation's participation in the system.

#### Acceptance Criteria

1. THE Admin SHALL be able to invite users to their own congregation only.
2. THE Admin SHALL be able to edit the name and role of users within their own congregation.
3. THE Admin SHALL be able to remove users from their own congregation.
4. WHEN an Admin deletes their own congregation, THE System SHALL remove all member associations and cancel all pending invitations for that congregation.
5. THE Admin SHALL be able to move their congregation to a different Kingdom Hall.
6. IF an admin is the last admin of a congregation and attempts to leave or be removed, THEN THE System SHALL prevent the action and display an error indicating another admin must be assigned first.
7. IF an Admin attempts to manage members or settings of a congregation they do not administer, THEN THE System SHALL reject the request and return an authorization error.

### Requirement 8: Invitation System

**User Story:** As a superadmin or admin, I want to invite users to congregations via email, so that new members can join the system with minimal friction.

#### Acceptance Criteria

1. WHEN an invitation is sent, THE System SHALL email the invited user with a link to set a password and activate their account, where the link expires after 72 hours.
2. THE System SHALL store pending invitations with the congregation, invitee name (maximum 255 characters), invitee email (maximum 255 characters), and invited role.
3. IF an invitation is sent to an email that already exists in the system, THEN THE System SHALL add the existing user to the congregation with the invited role without requiring a new password.
4. WHEN a superadmin creates a new congregation, THE System SHALL send an invitation to the specified initial user as part of the congregation creation flow.
5. IF an admin attempts to send an invitation for a role other than member or admin, or to a congregation they do not belong to, THEN THE System SHALL reject the invitation with an error message indicating the action is not permitted.
6. THE Superadmin SHALL be able to send invitations for any role to any congregation in their Kingdom Hall.
7. IF a user attempts to accept an invitation after the link has expired, THEN THE System SHALL reject the activation and display an error message indicating the invitation has expired.
8. IF an invitation is sent to an email that already has a pending invitation for the same congregation, THEN THE System SHALL replace the previous pending invitation with the new one.

### Requirement 9: UUID Primary Keys

**User Story:** As a system architect, I want all models to use UUIDs as primary keys instead of auto-incrementing integers, so that IDs cannot be guessed or enumerated by external actors.

#### Acceptance Criteria

1. THE System SHALL use UUID v7 (ordered UUIDs) as the primary key for all models including User, Congregation, Kingdom Hall, Room, Membership, and Invitation.
2. THE System SHALL NOT expose auto-incrementing integer IDs in URLs, API responses, or frontend code.
3. THE System SHALL use the `HasUuids` trait (or equivalent) on all Eloquent models to auto-generate UUIDs on creation.
4. THE System SHALL define all primary key columns as `uuid` type in database migrations and all foreign key columns as `foreignUuid` type.
5. WHEN a new model is created in the future, THE System SHALL use UUID as the primary key by default.
6. THE System SHALL use UUID v7 (time-ordered) to maintain index performance on the primary key column.

### Requirement 10: Congregation Movement Between Kingdom Halls

**User Story:** As a superadmin or admin, I want to move a congregation to a different Kingdom Hall, so that organizational changes in the physical world are reflected in the system.

#### Acceptance Criteria

1. THE Admin of a congregation SHALL be able to move their congregation to a different Kingdom Hall by specifying the target Kingdom Hall.
2. THE Superadmin SHALL be able to move any congregation in their Kingdom Hall to a different Kingdom Hall.
3. WHEN a congregation is moved to a different Kingdom Hall, THE System SHALL update the congregation's Kingdom Hall association and retain all members and their congregation-scoped roles (admin, member).
4. IF the target Kingdom Hall does not exist or is the same as the congregation's current Kingdom Hall, THEN THE System SHALL reject the move and display a validation error indicating the reason.
5. WHEN a congregation is moved out of a Kingdom Hall, THE System SHALL revoke the superadmin role in the original Kingdom Hall for any user whose only congregation in that Kingdom Hall was the moved congregation.
6. IF the last congregation is moved out of a Kingdom Hall, THEN THE System SHALL retain the Kingdom Hall record with zero congregations.

### Requirement 11: Congregation Deletion

**User Story:** As an admin or superadmin, I want to delete a congregation, so that I can remove organizational units that are no longer needed.

#### Acceptance Criteria

1. WHEN an admin deletes their congregation, THE System SHALL remove all users who belong exclusively to that congregation from the system.
2. WHEN a superadmin deletes a congregation, THE System SHALL remove all users who belong exclusively to that congregation from the system.
3. WHEN a superadmin deletes the Kingdom Hall, THE System SHALL delete all congregations connected to that Kingdom Hall and remove all users who belong exclusively to those congregations.
4. IF a user belongs to multiple congregations and one is deleted, THEN THE System SHALL retain the user and switch their current congregation to another active congregation.
5. THE System SHALL require confirmation before executing any congregation or Kingdom Hall deletion.
6. THE System SHALL soft-delete congregations to preserve audit history.
