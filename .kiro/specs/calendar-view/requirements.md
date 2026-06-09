# Requirements Document

## Introduction

The calendar view replaces the existing placeholder dashboard as the main page after login. It provides a full-page monthly calendar that serves as the primary navigation surface for the room-scheduling app, allowing congregation members to see and navigate dates at a glance.

## Glossary

- **Calendar_View**: The full-page monthly calendar component rendered at the congregation-scoped route, serving as the main page after login
- **Month_Grid**: The grid of date cells displaying one calendar month, including filler dates from adjacent months to complete weeks
- **Navigation_Controls**: The set of UI controls (arrows, month picker, year picker, today button) that allow users to change the displayed month
- **Current_Month**: The month containing today's date according to the user's local timezone
- **Filler_Dates**: Dates from the previous or next month displayed in the grid to complete partial weeks at the start and end of the month
- **Today_Indicator**: A visual mark or highlight applied to the cell representing today's date

## Requirements

### Requirement 1: Page Rename and Route Update

**User Story:** As a developer, I want to rename the dashboard page to calendar so that the codebase reflects the actual purpose of the main authenticated page.

#### Acceptance Criteria

1. THE Calendar_View SHALL be rendered by a page component located at `resources/js/pages/calendar.tsx`
2. WHEN a user navigates to the `/{current_congregation}/calendar` route, THE Calendar_View SHALL be displayed within the authenticated app layout using the route name `calendar`
3. THE Calendar_View SHALL replace the previous dashboard route as the default page after login by updating the Fortify home path from `/dashboard` to `/calendar`
4. WHEN a user navigates to the previous `/{current_congregation}/dashboard` URL, THE System SHALL return a 404 response
5. THE previous `resources/js/pages/dashboard.tsx` page component SHALL be removed from the codebase

### Requirement 2: Monthly Calendar Display

**User Story:** As a congregation member, I want to see a full-page monthly calendar so that I can quickly scan dates for the current month.

#### Acceptance Criteria

1. THE Calendar_View SHALL display exactly one month at a time in a grid layout
2. THE Month_Grid SHALL fill the entire available page area within the app layout
3. THE Month_Grid SHALL display day-of-week column headers using locale-aware abbreviated weekday names (e.g., "Mon", "Tue" or locale equivalent)
4. THE Month_Grid SHALL arrange dates in a fixed 6-row grid representing complete weeks (Sunday through Saturday or Monday through Sunday, matching locale), padding shorter months with Filler_Dates to maintain consistent grid height
5. WHEN the Calendar_View is first loaded, THE Month_Grid SHALL display the Current_Month
6. THE Calendar_View SHALL display a heading indicating the currently displayed month name and year

### Requirement 3: Adjacent Month Filler Dates

**User Story:** As a congregation member, I want to see dates from adjacent months filling in partial weeks so that the calendar grid is visually complete and consistent.

#### Acceptance Criteria

1. WHEN the first day of the displayed month does not fall on the first day of the week, THE Month_Grid SHALL display Filler_Dates from the previous month showing the correct sequential date numbers to complete that week
2. WHEN the last day of the displayed month does not fall on the last day of the week, THE Month_Grid SHALL display Filler_Dates from the next month showing the correct sequential date numbers to complete that week
3. THE Month_Grid SHALL render Filler_Dates with a muted foreground color that has visibly lower contrast than dates belonging to the displayed month
4. WHEN the user activates a Filler_Date cell, THE Month_Grid SHALL navigate to the month containing that date and display it as the current view

### Requirement 4: Month Navigation with Arrows

**User Story:** As a congregation member, I want to step forward or backward one month at a time so that I can browse adjacent months quickly.

#### Acceptance Criteria

1. THE Navigation_Controls SHALL include a backward arrow button and a forward arrow button, each with an accessible label indicating its navigation direction
2. WHEN the user activates the backward arrow, THE Month_Grid SHALL display the month immediately preceding the currently displayed month, rolling the year backward when navigating before January
3. WHEN the user activates the forward arrow, THE Month_Grid SHALL display the month immediately following the currently displayed month, rolling the year forward when navigating past December
4. THE Navigation_Controls SHALL allow navigation to any month within 10 years before and 10 years after the current year

### Requirement 5: Month and Year Picker

**User Story:** As a congregation member, I want to jump directly to a specific month and year so that I can navigate to distant dates without stepping one month at a time.

#### Acceptance Criteria

1. THE Navigation_Controls SHALL include a month picker that lists all 12 months and allows the user to select any one of them
2. THE Navigation_Controls SHALL include a year picker that allows the user to select a year within a range of 5 years before and 5 years after the current year
3. WHEN the user selects a month from the month picker, THE Month_Grid SHALL display the selected month within the currently displayed year
4. WHEN the user selects a year from the year picker, THE Month_Grid SHALL display the currently selected month within the selected year
5. THE month picker SHALL indicate the currently displayed month as the active selection, and THE year picker SHALL indicate the currently displayed year as the active selection

### Requirement 6: Go to Current Month Button

**User Story:** As a congregation member, I want a quick way to return to the current month so that I can always get back to today without multiple clicks.

#### Acceptance Criteria

1. THE Navigation_Controls SHALL include a "Go to current month" button that is identifiable by a visible text label
2. WHEN the user activates the "Go to current month" button, THE Month_Grid SHALL display the Current_Month of the current year as determined by the user's local timezone
3. WHILE the Month_Grid is displaying the Current_Month, THE "Go to current month" button SHALL be rendered as disabled with reduced visual prominence, shall not respond to pointer or keyboard activation, and shall communicate its disabled state to assistive technologies

### Requirement 7: Today's Date Highlight

**User Story:** As a congregation member, I want today's date to be visually distinct so that I can immediately orient myself on the calendar.

#### Acceptance Criteria

1. WHILE the Month_Grid is displaying the Current_Month, THE Calendar_View SHALL apply the Today_Indicator to the cell representing today's date
2. THE Today_Indicator SHALL differentiate today's date from other date cells using at least one non-color visual property (such as font weight, border, or shape) in addition to any color difference
3. WHEN the displayed month does not contain today's date, THE Calendar_View SHALL not display the Today_Indicator on any cell
4. WHEN today's date is visible as a Filler_Date in an adjacent month's grid, THE Calendar_View SHALL NOT apply the Today_Indicator to that filler cell
