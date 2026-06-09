# Requirements Document

## Introduction

Refactor the Kingdom Hall page to replace inline forms with modal-based interactions, add full CRUD capabilities for rooms and congregations, and restrict management controls to superadmin users. The `number_of_rooms` field becomes a derived value from the actual room count rather than a manually editable field.

## Glossary

- **Kingdom_Hall_Page**: The Inertia page component that displays Kingdom Hall details including address, rooms, and congregations
- **Address_Modal**: A dialog component for editing the Kingdom Hall street address, zip code, and city
- **Room_Modal**: A dialog component for creating or renaming a room
- **Congregation_Modal**: A dialog component for adding a new congregation with initial responsible person details
- **Deletion_Confirmation_Dialog**: A dialog requiring explicit user confirmation before destructive actions
- **Superadmin**: A user with the Superadmin role in a congregation belonging to the Kingdom Hall
- **Responsible_Person**: The initial user invited as Admin of a newly created congregation
- **Room**: A bookable space inside a Kingdom Hall identified by name
- **Congregation**: An organization tied to a Kingdom Hall with members and roles
- **Future_Booking**: A booking record associated with a room that has a start time after the current date and time

## Requirements

### Requirement 1: Display Address with Edit Control

**User Story:** As a superadmin, I want to edit the Kingdom Hall address via a modal, so that I can update location details without navigating away from the page.

#### Acceptance Criteria

1. THE Kingdom_Hall_Page SHALL display the street address, zip code, and city in a read-only card with no inline edit affordances visible to any user role
2. WHILE the authenticated user is a Superadmin, THE Kingdom_Hall_Page SHALL display an edit icon button in the address card header
3. WHILE the authenticated user is not a Superadmin, THE Kingdom_Hall_Page SHALL hide all edit, add, and delete controls
4. WHEN the edit icon is clicked, THE Address_Modal SHALL open pre-populated with the current street address, zip code, and city values
5. THE Address_Modal SHALL NOT include a number_of_rooms field
6. THE Address_Modal SHALL enforce the following field constraints: street_address required with a maximum of 255 characters, zip_code required with a maximum of 20 characters, and city required with a maximum of 100 characters
7. THE Address_Modal SHALL prevent form submission while any required field is empty
8. WHEN the Address_Modal form is submitted with valid data, THE System SHALL update the Kingdom Hall record, close the modal, display a success toast notification, and reflect the updated values in the address card without a full page reload
8. IF the Address_Modal form submission fails server-side validation, THEN THE Address_Modal SHALL remain open and display the validation error messages inline next to their respective fields
9. IF the Address_Modal form submission fails due to a network or unexpected server error, THEN THE System SHALL display an error toast notification and the Address_Modal SHALL remain open with the user's input preserved

### Requirement 2: Room Management

**User Story:** As a superadmin, I want to add, rename, and delete rooms via modals, so that I can manage the physical spaces in the Kingdom Hall.

#### Acceptance Criteria

1. THE Kingdom_Hall_Page SHALL display a list of all rooms belonging to the Kingdom Hall ordered by sort_order ascending
2. WHILE the authenticated user is a Superadmin, THE Kingdom_Hall_Page SHALL display a "+" icon button in the rooms card header to add a new room
3. WHEN the "+" icon is clicked, THE Room_Modal SHALL open with an empty room name input field
4. WHEN the Room_Modal form is submitted with a valid room name, THE System SHALL create a new Room record linked to the Kingdom Hall with sort_order set to one greater than the current highest sort_order value among existing rooms in that Kingdom Hall (or 1 if no rooms exist)
5. WHEN a Room is created, THE System SHALL update the Kingdom Hall number_of_rooms field to match the actual room count
6. IF the Room_Modal form submission fails server-side validation, THEN THE Room_Modal SHALL display the validation error messages inline without closing
7. WHILE the authenticated user is a Superadmin, THE Kingdom_Hall_Page SHALL display edit and delete action buttons for each room in the list
8. WHEN the edit action is clicked for a room, THE Room_Modal SHALL open pre-populated with the current room name
9. WHEN the Room_Modal edit form is submitted with a valid name, THE System SHALL update the room name
10. WHEN the delete action is clicked for a room that has future bookings, THE Deletion_Confirmation_Dialog SHALL display a warning indicating the room has future bookings that will be deleted
11. WHEN the delete action is clicked for a room that has no future bookings, THE Deletion_Confirmation_Dialog SHALL ask for confirmation to delete the room
12. WHEN the room deletion is confirmed, THE System SHALL delete the room and all connected booking data
13. WHEN a Room is deleted, THE System SHALL update the Kingdom Hall number_of_rooms field to match the actual room count
14. THE System SHALL consider a room name valid when it contains between 1 and 255 characters after trimming leading and trailing whitespace, and is unique among rooms within the same Kingdom Hall

### Requirement 3: Congregation Management

**User Story:** As a superadmin, I want to add and delete congregations via modals, so that I can manage which congregations share the Kingdom Hall.

#### Acceptance Criteria

1. THE Kingdom_Hall_Page SHALL display a list of all congregations belonging to the Kingdom Hall, showing each congregation's name, congregation number, and assigned color indicator
2. WHILE the authenticated user is a Superadmin, THE Kingdom_Hall_Page SHALL display a "+" icon button in the congregations card header to add a new congregation
3. WHEN the "+" icon is clicked, THE Congregation_Modal SHALL open with input fields for congregation name (max 255 characters), congregation number (max 20 characters, uppercase letters and digits only matching pattern /^[A-Z0-9]+$/), responsible person name (max 255 characters), and responsible person email (valid email format, max 255 characters)
4. WHEN the Congregation_Modal form is submitted with data passing all field constraints from criterion 3 and the congregation number is unique across all congregations, THE System SHALL create a new Congregation record linked to the Kingdom Hall with an auto-generated distinct color
5. WHEN a Congregation is created, THE System SHALL send an invitation email to the responsible person email address with Admin role, expiring after 72 hours
6. WHILE the authenticated user is a Superadmin, THE Kingdom_Hall_Page SHALL display a delete action button for each congregation in the list
7. WHEN the delete action is clicked for a congregation, THE Deletion_Confirmation_Dialog SHALL require the user to type the congregation number to confirm deletion, using case-sensitive matching
8. WHEN the typed congregation number matches the actual congregation number (case-sensitive), THE Deletion_Confirmation_Dialog SHALL enable the confirm button
9. WHEN the congregation deletion is confirmed, THE System SHALL delete the congregation and cascade-delete all connected data including memberships and pending invitations
10. IF the Congregation_Modal form submission fails server-side validation, THEN THE Congregation_Modal SHALL display the validation error messages inline without closing
11. IF the System cannot generate a sufficiently distinct color for the new congregation, THEN THE Congregation_Modal SHALL display a validation error message indicating the color generation failure without closing

### Requirement 4: Authorization Enforcement

**User Story:** As a system administrator, I want management controls restricted to superadmins, so that only authorized users can modify Kingdom Hall configuration.

#### Acceptance Criteria

1. THE System SHALL require Superadmin role membership in a congregation belonging to the Kingdom Hall before allowing any create, update, or delete operation on the Kingdom Hall address, rooms, or congregations
2. IF an authenticated user without Superadmin role in a congregation belonging to the Kingdom Hall submits a create, update, or delete request for address, rooms, or congregations, THEN THE System SHALL return a 403 Forbidden response and SHALL NOT modify any data
3. WHILE the authenticated user does not hold Superadmin role in a congregation belonging to the Kingdom Hall, THE Kingdom_Hall_Page SHALL render without any edit, add, or delete controls present in the DOM
4. WHILE the authenticated user holds Superadmin role in a congregation belonging to the Kingdom Hall, THE Kingdom_Hall_Page SHALL render all edit, add, and delete controls for address, rooms, and congregations
5. IF an unauthenticated user submits a create, update, or delete request for address, rooms, or congregations, THEN THE System SHALL redirect to the login page without modifying any data

### Requirement 5: Inline Form Removal

**User Story:** As a user, I want a cleaner page layout without inline forms, so that the Kingdom Hall page is easier to scan and less cluttered.

#### Acceptance Criteria

1. THE Kingdom_Hall_Page SHALL NOT render the "Edit Kingdom Hall" inline form card regardless of user role
2. THE Kingdom_Hall_Page SHALL NOT render the "Add Congregation" inline form card regardless of user role
3. THE Kingdom_Hall_Page SHALL present all create and edit interactions exclusively through the Address_Modal, Room_Modal, and Congregation_Modal dialog components defined in Requirements 1-3
