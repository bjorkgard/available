# Requirements Document

## Introduction

Keyboard shortcuts provide efficient navigation and actions without requiring mouse interaction. This feature introduces two scopes of shortcuts: page-level shortcuts (arrow keys for calendar month navigation) and application-wide shortcuts (the "D" key for toggling appearance between light and dark mode). All shortcuts are suppressed when the user is focused on text-input elements to avoid interfering with normal typing.

## Glossary

- **Shortcut_Handler**: The keyboard event listener logic responsible for detecting keystrokes and dispatching the appropriate action
- **Calendar_Page**: The full-page monthly calendar component rendered at `/{current_congregation}/calendar`
- **Appearance_System**: The existing appearance toggle mechanism (light, dark, system) accessed via the `useAppearance` hook
- **Text_Input_Element**: Any DOM element that accepts text input, including `<input>`, `<textarea>`, `<select>`, and elements with the `contenteditable` attribute set to `"true"`
- **Active_Element**: The DOM element that currently has keyboard focus as reported by `document.activeElement`

## Requirements

### Requirement 1: Calendar Month Navigation via Arrow Keys

**User Story:** As a congregation member, I want to use the left and right arrow keys to step through months on the calendar page so that I can navigate quickly without reaching for the mouse.

#### Acceptance Criteria

1. WHILE the Calendar_Page is displayed, WHEN the user presses the ArrowLeft key and no interactive element (select, input, or button) holds focus, THE Shortcut_Handler SHALL invoke the same month-backward navigation as the backward arrow button
2. WHILE the Calendar_Page is displayed, WHEN the user presses the ArrowRight key and no interactive element (select, input, or button) holds focus, THE Shortcut_Handler SHALL invoke the same month-forward navigation as the forward arrow button
3. WHEN the user navigates away from the Calendar_Page, THE Shortcut_Handler SHALL remove the arrow key event listeners for month navigation
4. IF the displayed month is January of the minimum navigable year (current year minus 10), THEN THE Shortcut_Handler SHALL not navigate further backward when ArrowLeft is pressed
5. IF the displayed month is December of the maximum navigable year (current year plus 10), THEN THE Shortcut_Handler SHALL not navigate further forward when ArrowRight is pressed

### Requirement 2: Application-Wide Appearance Toggle via D Key

**User Story:** As a user, I want to press the "D" key anywhere in the application to quickly switch between light and dark appearance so that I can adjust my viewing preference without navigating to settings.

#### Acceptance Criteria

1. WHEN the user presses the "D" key (case-insensitive) on keydown, THE Shortcut_Handler SHALL toggle the appearance using the Appearance_System according to the mapping defined in criteria 2 and 3
2. IF the current appearance is "light" WHEN the user presses the "D" key, THEN THE Shortcut_Handler SHALL switch the appearance to "dark"
3. IF the current appearance is "dark" or "system" WHEN the user presses the "D" key, THEN THE Shortcut_Handler SHALL switch the appearance to "light"
4. THE "D" key shortcut SHALL function on all authenticated pages of the application, not only the Calendar_Page
5. WHEN the appearance is toggled via the "D" key, THE Shortcut_Handler SHALL persist the new appearance preference so that it survives page navigation and page reload

### Requirement 3: Text Input Suppression

**User Story:** As a user, I want keyboard shortcuts to be inactive while I am typing in a form field so that pressing shortcut keys does not trigger unintended navigation or appearance changes.

#### Acceptance Criteria

1. WHILE the Active_Element is an `<input>` element with a text-accepting type (text, search, url, tel, email, password, number, date, datetime-local, month, week, time), THE Shortcut_Handler SHALL not respond to any registered shortcut keys
2. WHILE the Active_Element is a `<textarea>` element, THE Shortcut_Handler SHALL not respond to any registered shortcut keys
3. WHILE the Active_Element is a `<select>` element, THE Shortcut_Handler SHALL not respond to any registered shortcut keys
4. WHILE the Active_Element has the `contenteditable` attribute set to `"true"`, THE Shortcut_Handler SHALL not respond to any registered shortcut keys
5. IF the Active_Element is not a Text_Input_Element, THEN THE Shortcut_Handler SHALL evaluate the keystroke against registered shortcuts and execute the matching action
6. WHEN a keydown event is received, THE Shortcut_Handler SHALL check whether the Active_Element is a Text_Input_Element before evaluating the keystroke against registered shortcuts

### Requirement 4: Modifier Key Exclusion

**User Story:** As a user, I want keyboard shortcuts to only trigger on plain keystrokes so that browser and OS shortcuts using modifier keys continue to work as expected.

#### Acceptance Criteria

1. WHEN a keyboard event includes the Ctrl modifier, THE Shortcut_Handler SHALL not respond to the event and SHALL allow the browser's default behavior to proceed
2. WHEN a keyboard event includes the Meta modifier (Cmd on macOS), THE Shortcut_Handler SHALL not respond to the event and SHALL allow the browser's default behavior to proceed
3. WHEN a keyboard event includes the Alt modifier, THE Shortcut_Handler SHALL not respond to the event and SHALL allow the browser's default behavior to proceed
4. WHEN a keyboard event includes the Shift modifier, THE Shortcut_Handler SHALL not respond to the event and SHALL allow the browser's default behavior to proceed
5. WHEN a keyboard event has no modifier keys pressed, THE Shortcut_Handler SHALL evaluate the event against registered shortcuts
