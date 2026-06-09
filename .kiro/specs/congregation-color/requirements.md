# Requirements Document

## Introduction

Each congregation in a Kingdom Hall is assigned a unique color to visually distinguish it in scheduling views, calendars, and other shared interfaces. Colors are auto-generated at creation time and validated on change to ensure sufficient perceptual distance between all congregations sharing the same Kingdom Hall.

## Glossary

- **Congregation**: An organization tied to a Kingdom Hall; has members with roles
- **Kingdom_Hall**: A physical building with multiple rooms and multiple congregations
- **Color**: A hexadecimal RGB color value (e.g., `#3B82F6`) stored on a Congregation
- **Color_Distance**: The perceptual difference between two colors measured using the CIEDE2000 algorithm in the CIELAB color space
- **Minimum_Distance_Threshold**: The minimum acceptable Color_Distance value between any two congregation colors in the same Kingdom Hall (value: 25)
- **Color_Service**: The backend service responsible for generating random colors and validating color distance
- **Admin**: A user with the Admin or Superadmin role within a congregation
- **Superadmin**: A user with the Superadmin role within a congregation

## Requirements

### Requirement 1: Assign Random Color on Congregation Creation

**User Story:** As a user creating a congregation, I want a color to be automatically assigned, so that the congregation is visually distinguishable from day one without manual setup.

#### Acceptance Criteria

1. WHEN a Congregation is created within a Kingdom_Hall that already has other Congregations, THE Color_Service SHALL generate a random Color that has a CIEDE2000 Color_Distance of at least 25 from every existing Congregation color in that Kingdom_Hall and assign it to the Congregation before the creation transaction completes
2. WHEN a Congregation is created without a Kingdom_Hall association, THE Color_Service SHALL assign a random Color in valid `#RRGGBB` format without performing any distance check
3. THE Color_Service SHALL store the Color as a 7-character hexadecimal string in the format `#RRGGBB` where each R, G, and B component is a two-digit hexadecimal value (00–FF)
4. IF the Color_Service cannot generate a Color meeting the Minimum_Distance_Threshold after a maximum of 100 attempts, THEN THE Color_Service SHALL reject the Congregation creation with an error message indicating that no sufficiently distinct color could be generated
5. WHEN a Congregation is associated with a Kingdom_Hall after initial creation (e.g., via the setup wizard), THE Color_Service SHALL validate the existing Color against all sibling Congregations in that Kingdom_Hall and generate a new compliant Color if the current Color has a Color_Distance below 25 from any sibling

### Requirement 2: Enforce Color Uniqueness Within a Kingdom Hall

**User Story:** As a congregation member, I want all congregations in my Kingdom Hall to have visually distinct colors, so that I can quickly identify which congregation owns a booking or event.

#### Acceptance Criteria

1. THE Color_Service SHALL ensure that no two Congregations within the same Kingdom_Hall have a Color_Distance below the Minimum_Distance_Threshold of 25 as measured by CIEDE2000
2. WHEN a color change is requested that would result in a Color_Distance below Minimum_Distance_Threshold with any other Congregation in the same Kingdom_Hall, THE Color_Service SHALL reject the change with a validation error indicating which Congregation(s) conflict and that the chosen color is too similar
3. WHEN a Congregation is moved to a different Kingdom_Hall via the MoveCongregation action, THE Color_Service SHALL validate that the Congregation's current Color has a Color_Distance of at least Minimum_Distance_Threshold against every existing Congregation color in the destination Kingdom_Hall
4. IF a Congregation is moved and its current color has a Color_Distance below Minimum_Distance_Threshold with any Congregation in the destination Kingdom_Hall, THEN THE Color_Service SHALL automatically generate a new Color that meets the Minimum_Distance_Threshold against all Congregations in the destination Kingdom_Hall before completing the move
5. IF a Congregation is moved and the Color_Service cannot generate a valid Color within 100 attempts that meets the Minimum_Distance_Threshold against all Congregations in the destination Kingdom_Hall, THEN THE Color_Service SHALL reject the move with a validation error indicating that no sufficiently distinct color could be generated
6. WHEN validating Color_Distance for a Congregation, THE Color_Service SHALL compare the Congregation's Color against every other Congregation in the same Kingdom_Hall, excluding itself

### Requirement 3: Admin Color Change

**User Story:** As an admin or superadmin, I want to change my congregation's color, so that I can pick a color that better represents our congregation's identity.

#### Acceptance Criteria

1. WHEN an Admin requests a color change for their own Congregation, THE System SHALL persist the new Color if it has a Color_Distance of at least Minimum_Distance_Threshold from every other Congregation color in the same Kingdom_Hall
2. IF a user with the Member role requests a color change, THEN THE System SHALL reject the request and return an authorization error without modifying the Congregation's Color
3. THE System SHALL validate that the submitted color is a valid 7-character hexadecimal string matching the format `#RRGGBB` (case-insensitive input accepted, stored as uppercase) where each pair RR, GG, BB is a value from 00 to FF
4. IF the submitted color fails format validation, THEN THE System SHALL reject the request and display an inline validation error indicating the expected format
5. IF the new color has a Color_Distance below the Minimum_Distance_Threshold from any sibling Congregation's color in the same Kingdom_Hall, THEN THE System SHALL reject the change and display an inline validation error indicating the color is too similar to an existing congregation's color
6. WHEN a valid color change is saved, THE System SHALL display a Sonner toast notification indicating the color was updated successfully

### Requirement 4: Color Distance Calculation

**User Story:** As a developer, I want color distance to be calculated using a perceptually uniform algorithm, so that colors that "look different" to humans are treated as different by the system.

#### Acceptance Criteria

1. THE Color_Service SHALL convert hexadecimal RGB colors to the CIELAB color space using the D65 illuminant and 2° standard observer for the intermediate XYZ conversion before calculating distance
2. THE Color_Service SHALL use the CIEDE2000 formula to calculate Color_Distance between two CIELAB color values, producing a non-negative floating-point result rounded to 4 decimal places
3. WHEN Color_Distance is calculated from Color A to Color B, THE Color_Service SHALL produce a result identical to calculating from Color B to Color A within a tolerance of ±0.0001 (symmetry property)
4. WHEN Color_Distance is calculated from any Color A to itself, THE Color_Service SHALL produce a result of 0.0000 (identity property)
5. IF either input to the Color_Distance calculation is not a valid 7-character hexadecimal string in the format `#RRGGBB`, THEN THE Color_Service SHALL throw a validation error indicating the invalid color value

### Requirement 5: Color Display

**User Story:** As a congregation member, I want to see the congregation's color used consistently in the UI, so that I can identify my congregation at a glance.

#### Acceptance Criteria

1. THE System SHALL include a `color` property containing the Congregation's Color as a 7-character `#RRGGBB` hex string on each Congregation object within the shared Inertia page props (`currentCongregation` and `congregations`) for all authenticated pages
2. WHEN displaying a Congregation in the congregation-switcher component, THE System SHALL render a color swatch of at least 12×12 CSS pixels with `background-color` set to the Congregation's Color
3. IF a Congregation's Color value is null or missing, THEN THE System SHALL render the swatch using a fallback neutral color and not throw a runtime error
4. WHEN displaying a Congregation in any list that shows multiple Congregations, THE System SHALL render a color swatch adjacent to the Congregation name using the Congregation's Color
