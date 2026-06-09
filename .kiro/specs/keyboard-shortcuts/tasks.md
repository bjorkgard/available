# Implementation Plan: Keyboard Shortcuts

## Overview

Implement a reusable `useKeyboardShortcuts` hook with guard-first architecture (modifier key exclusion, text-input suppression), then integrate it into the calendar page (arrow key navigation) and app layout (D key appearance toggle). All logic is frontend-only using React hooks and TypeScript.

## Tasks

- [x] 1. Implement the useKeyboardShortcuts hook with pure guard functions
  - [x] 1.1 Create `resources/js/hooks/use-keyboard-shortcuts.ts` with `isTextInputElement`, `shouldIgnoreEvent`, and `useKeyboardShortcuts`
    - Export `isTextInputElement(element: Element | null): boolean` — returns true for `<input>` with text-accepting types, `<textarea>`, `<select>`, and `contenteditable="true"` elements
    - Export `shouldIgnoreEvent(event: KeyboardEvent): boolean` — returns true if any modifier key is pressed OR the active element is a text-input element
    - Export `useKeyboardShortcuts(shortcuts: Record<string, () => void>): void` — registers a `keydown` listener on `document`, uses a ref to avoid stale closures, normalizes `event.key` to lowercase before lookup, calls `event.preventDefault()` on match
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 2. Property-based tests for pure guard functions
  - [x] 2.1 Write property test: Text-input element suppression (Property 1)
    - **Property 1: Text-input element suppression**
    - Generate random text-input elements (`<input>` with text-accepting types, `<textarea>`, `<select>`, contenteditable elements) × random keys → assert `shouldIgnoreEvent` returns `true`
    - Test file: `resources/js/hooks/__tests__/use-keyboard-shortcuts.property.test.ts`
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**

  - [x] 2.2 Write property test: Non-text-input elements allow shortcuts (Property 2)
    - **Property 2: Non-text-input elements allow shortcuts**
    - Generate random non-text elements (`<div>`, `<span>`, `<body>`, `<button>`, null) × random keys with all modifiers false → assert `shouldIgnoreEvent` returns `false`
    - Test file: `resources/js/hooks/__tests__/use-keyboard-shortcuts.property.test.ts`
    - **Validates: Requirements 3.5**

  - [x] 2.3 Write property test: Modifier key suppression (Property 3)
    - **Property 3: Modifier key suppression**
    - Generate random modifier combinations (at least one of ctrlKey/metaKey/altKey/shiftKey true) × random elements × random keys → assert `shouldIgnoreEvent` returns `true`
    - Test file: `resources/js/hooks/__tests__/use-keyboard-shortcuts.property.test.ts`
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4**

  - [x] 2.4 Write property test: No-modifier events are evaluated (Property 4)
    - **Property 4: No-modifier events are evaluated**
    - Generate random non-text elements × random keys with all modifiers false → assert `shouldIgnoreEvent` returns `false`
    - Test file: `resources/js/hooks/__tests__/use-keyboard-shortcuts.property.test.ts`
    - **Validates: Requirements 4.5**

- [x] 3. Integrate keyboard shortcuts into the calendar page
  - [x] 3.1 Add `useKeyboardShortcuts` to `resources/js/pages/calendar.tsx` for arrow key navigation
    - Import `useKeyboardShortcuts` from `@/hooks/use-keyboard-shortcuts`
    - Call `useKeyboardShortcuts({ arrowleft: onPreviousMonth, arrowright: onNextMonth })` inside the `Calendar` component
    - Existing `onPreviousMonth`/`onNextMonth` already enforce boundary clamping (min/max year ±10)
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 4. Integrate keyboard shortcuts into the app layout
  - [x] 4.1 Add `useKeyboardShortcuts` to `resources/js/layouts/app-layout.tsx` for appearance toggle
    - Import `useKeyboardShortcuts` from `@/hooks/use-keyboard-shortcuts`
    - Import `useAppearance` from `@/hooks/use-appearance`
    - Call `useAppearance()` to get `appearance` and `updateAppearance`
    - Register shortcut: `useKeyboardShortcuts({ d: () => updateAppearance(appearance === 'light' ? 'dark' : 'light') })`
    - This is app-scoped — always mounted on authenticated pages
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 5. Checkpoint - Verify integrations work
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Unit and integration tests
  - [x] 6.1 Write unit tests for `useKeyboardShortcuts` hook behavior
    - Test file: `resources/js/hooks/__tests__/use-keyboard-shortcuts.test.ts`
    - Test ArrowLeft calls `onPreviousMonth` handler
    - Test ArrowRight calls `onNextMonth` handler
    - Test listener is removed on component unmount
    - Test shortcut does not fire when `<input type="text">` is focused
    - Test shortcut does not fire when Ctrl+D is pressed
    - Test "d" key toggles appearance from light → dark
    - Test "d" key toggles appearance from dark → light
    - Test "d" key toggles appearance from system → light
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3, 3.1, 4.1_

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The hook normalizes `event.key` to lowercase before shortcut map lookup — shortcut maps should use lowercase keys
- Boundary clamping for calendar navigation is already handled by `onPreviousMonth`/`onNextMonth`
- Persistence of appearance preference is handled by the existing `useAppearance` hook (localStorage + cookie)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "2.2", "2.3", "2.4", "3.1", "4.1"] },
    { "id": 2, "tasks": ["6.1"] }
  ]
}
```
