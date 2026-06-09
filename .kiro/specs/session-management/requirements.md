# Requirements Document

## Introduction

Session management allows authenticated users to view and manage their active sessions from the settings area. Users can see which devices and browsers are logged in, identify the current session, and terminate all other sessions for security purposes. Session data is stored in the database using Laravel's built-in database session driver.

## Glossary

- **Session_Manager**: The backend component responsible for retrieving, formatting, and invalidating user sessions from the database
- **Sessions_Page**: The frontend settings page that displays active sessions and provides session management controls
- **User_Agent_Parser**: The component that extracts browser name, operating system, and device type from a raw user agent string
- **Active_Session**: A session record in the database that belongs to the authenticated user and has not expired
- **Current_Session**: The session associated with the request currently being made by the authenticated user
- **Device_Type**: A classification of the session's device as either "desktop" or "mobile" based on the parsed user agent

## Requirements

### Requirement 1: Display Active Sessions

**User Story:** As a user, I want to see a list of all my active sessions under settings, so that I can monitor which devices are logged into my account.

#### Acceptance Criteria

1. WHEN the user navigates to the sessions settings page, THE Sessions_Page SHALL display a list of all Active_Sessions belonging to the authenticated user, ordered with the Current_Session first, followed by remaining sessions sorted by last activity time descending
2. THE Sessions_Page SHALL display the browser name and operating system for each Active_Session
3. THE Sessions_Page SHALL display the IP address for each Active_Session
4. THE Sessions_Page SHALL display the last active time for each Active_Session as a human-readable relative timestamp (e.g., "2 hours ago", "3 days ago")
5. THE Sessions_Page SHALL visually indicate which session is the Current_Session with a distinct "This device" label

### Requirement 2: Device Type Identification

**User Story:** As a user, I want to see an icon indicating whether a session is on a desktop computer or a handheld device, so that I can quickly identify my devices.

#### Acceptance Criteria

1. IF the Device_Type is "desktop", THEN THE Sessions_Page SHALL display a desktop icon for that session entry
2. IF the Device_Type is "mobile", THEN THE Sessions_Page SHALL display a mobile device icon for that session entry
3. IF the User_Agent_Parser cannot determine the Device_Type or returns a value other than "desktop" or "mobile", THEN THE Sessions_Page SHALL default to displaying the desktop icon

### Requirement 3: Parse User Agent Information

**User Story:** As a developer, I want user agent strings parsed into structured device information, so that session details are readable for users.

#### Acceptance Criteria

1. WHEN a session record is retrieved, THE User_Agent_Parser SHALL extract the browser marketing name (e.g., "Chrome", "Firefox", "Safari") without version number from the user agent string
2. WHEN a session record is retrieved, THE User_Agent_Parser SHALL extract the operating system marketing name (e.g., "Windows", "macOS", "Linux", "iOS", "Android") without version number from the user agent string
3. WHEN a session record is retrieved, THE User_Agent_Parser SHALL classify the Device_Type as "mobile" if the user agent string contains a mobile-device indicator (e.g., "Mobile", "iPhone", "Android" with "Mobile"), and as "desktop" otherwise
4. IF the user agent string is empty or does not match any known browser or operating system pattern, THEN THE User_Agent_Parser SHALL return "Unknown" for the browser name, "Unknown" for the operating system, and "desktop" for the Device_Type

### Requirement 4: Terminate Other Sessions

**User Story:** As a user, I want to log out all other sessions except my current one, so that I can secure my account if I suspect unauthorized access.

#### Acceptance Criteria

1. WHEN the user confirms the terminate-other-sessions action, THE Session_Manager SHALL delete all Active_Sessions belonging to the authenticated user except the Current_Session
2. WHEN the user initiates the terminate-other-sessions action, THE Sessions_Page SHALL prompt the user to enter their current password before submitting the request
3. IF the provided password is incorrect, THEN THE Session_Manager SHALL reject the request, display a validation error indicating the password is incorrect, and clear the password input field
4. WHEN other sessions are successfully terminated, THE Sessions_Page SHALL display a success toast notification and refresh the session list to reflect the updated state
5. THE Sessions_Page SHALL disable the terminate-other-sessions action when only the Current_Session exists
6. IF the session termination request fails due to a server error, THEN THE Sessions_Page SHALL display an error notification indicating the operation could not be completed and leave existing sessions unchanged

### Requirement 5: Session Data Storage

**User Story:** As a developer, I want sessions stored in the database, so that session data can be queried and managed programmatically.

#### Acceptance Criteria

1. THE Session_Manager SHALL use the database session driver to store session data in the "sessions" table
2. THE Session_Manager SHALL associate each session record with the authenticated user's identifier, enabling retrieval of all sessions belonging to a specific user
3. THE Session_Manager SHALL store the user agent string for each session, retaining a null value if the user agent is not available in the request
4. THE Session_Manager SHALL store the IP address for each session, supporting both IPv4 and IPv6 formats up to 45 characters
5. WHEN a request is processed for an authenticated user, THE Session_Manager SHALL update the last activity timestamp of the corresponding session record to the current time as a Unix timestamp

### Requirement 6: Sessions Settings Page Routing

**User Story:** As a user, I want to access session management from my settings, so that it is discoverable alongside other account settings.

#### Acceptance Criteria

1. THE Sessions_Page SHALL be accessible at the URL path "settings/sessions"
2. IF the user is not authenticated, THEN THE Sessions_Page SHALL redirect to the login page
3. IF the user is authenticated but has not verified their email, THEN THE Sessions_Page SHALL redirect to the email verification page
4. THE Sessions_Page SHALL appear as a navigation item labeled "Sessions" in the settings sidebar alongside Profile, Security, and Appearance
5. THE Sessions_Page SHALL use the shared settings layout consistent with other settings pages
