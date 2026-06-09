# Implementation Plan: Congregation Color

## Overview

Implement a persistent color attribute on congregations with CIEDE2000 perceptual distance validation, auto-generation on creation/move, admin manual change, and UI display in the congregation switcher and edit page. The backend uses a dedicated `ColorService` for all color math, and existing actions are extended to call it at the right moments.

## Tasks

- [x] 1. Database and model foundation
  - [x] 1.1 Create migration to add `color` column to `congregations` table
    - Add `$table->char('color', 7)->nullable()->after('kingdom_hall_id')`
    - _Requirements: 1.3, 5.1_
  - [x] 1.2 Update Congregation model and factory
    - Add `'color'` to `$fillable` array on the Congregation model
    - Update `CongregationFactory` to generate a random valid hex color in `#RRGGBB` format
    - _Requirements: 1.3, 5.3_

- [x] 2. Implement ColorService core
  - [x] 2.1 Create `ColorService` with hex validation and color space conversion
    - Create `app/Services/ColorService.php`
    - Implement `isValidHex(string $hex): bool` using regex `/^#[0-9A-Fa-f]{6}$/`
    - Implement `hexToLab(string $hex): array{L: float, a: float, b: float}` with RGB→XYZ (D65/2°)→Lab conversion
    - _Requirements: 4.1, 4.5, 3.3_
  - [x] 2.2 Implement CIEDE2000 distance calculation in `ColorService`
    - Implement `ciede2000Distance(string $hex1, string $hex2): float`
    - Throw `\InvalidArgumentException` for invalid hex inputs
    - Round result to 4 decimal places
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_
  - [x] 2.3 Implement color generation and distance validation methods
    - Implement `generateDistinctColor(array $siblingColors): string` with 100-attempt budget
    - Implement `isDistinctFromAll(string $color, array $siblingColors): bool` using MIN_DISTANCE of 25
    - Create `app/Exceptions/ColorGenerationException.php` thrown when 100 attempts exhausted
    - _Requirements: 1.1, 1.4, 2.1, 2.2_
  - [x] 2.4 Write property tests for CIEDE2000 distance (Properties 5, 6, 7, 8)
    - **Property 5: CIEDE2000 distance is non-negative**
    - **Property 6: CIEDE2000 distance is symmetric**
    - **Property 7: CIEDE2000 distance identity**
    - **Property 8: Invalid hex input throws validation error**
    - Create `tests/Feature/Properties/ColorDistancePropertyTest.php`
    - Run 100 iterations each with randomly generated hex colors
    - **Validates: Requirements 4.2, 4.3, 4.4, 4.5**
  - [x] 2.5 Write property tests for color generation (Properties 1, 2, 3)
    - **Property 1: Generated colors maintain minimum distance from siblings**
    - **Property 2: All generated colors have valid hex format**
    - **Property 3: Colors below minimum distance are rejected**
    - Create `tests/Feature/Properties/ColorDistanceGenerationTest.php`
    - Run 100 iterations each with randomly generated sibling sets (0–10 siblings)
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.5, 2.1, 2.2, 3.1, 3.5**
  - [x] 2.6 Write property test for hex format validation (Property 4)
    - **Property 4: Hex format validation accepts valid colors and rejects invalid strings**
    - Create `tests/Feature/Properties/ColorValidationPropertyTest.php`
    - Run 100 iterations with random valid and invalid strings
    - **Validates: Requirements 3.3, 3.4**
  - [x] 2.7 Write unit tests for ColorService
    - Create `tests/Unit/ColorServiceTest.php`
    - Test `hexToLab` against known reference colors (e.g., pure red, green, blue, white, black)
    - Test `ciede2000Distance` against published reference pairs
    - Test generation with no siblings produces valid color
    - Test generation throws `ColorGenerationException` after 100 failed attempts
    - _Requirements: 4.1, 4.2, 1.4_

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Integrate color into existing actions
  - [x] 4.1 Update `CreateCongregation` action to assign color on creation
    - Inject `ColorService` into the action
    - Query sibling congregation colors from the kingdom hall (if present)
    - Call `generateDistinctColor()` and set color before `Congregation::create()`
    - If no kingdom hall, generate a random color without distance check
    - Catch `ColorGenerationException` and convert to `ValidationException`
    - _Requirements: 1.1, 1.2, 1.4_
  - [x] 4.2 Update `MoveCongregation` action to validate/regenerate color on move
    - After updating `kingdom_hall_id`, query sibling colors in destination hall
    - Validate current color with `isDistinctFromAll()`; regenerate if needed
    - Catch `ColorGenerationException` and reject move with `ValidationException`
    - _Requirements: 2.3, 2.4, 2.5, 2.6_
  - [x] 4.3 Update `CreateKingdomHall` action to validate/regenerate color on hall association
    - After associating congregation with hall, validate current color against siblings
    - Regenerate if current color conflicts
    - _Requirements: 1.5, 2.1_
  - [x] 4.4 Write feature tests for color assignment and validation in actions
    - Create `tests/Feature/Congregations/CongregationColorTest.php`
    - Test color assigned on congregation creation (with and without kingdom hall)
    - Test color validated/regenerated on move to hall with conflicting colors
    - Test color validated/regenerated on kingdom hall setup
    - Test self-exclusion: congregation's own color not compared against itself
    - _Requirements: 1.1, 1.2, 1.5, 2.3, 2.4, 2.6_

- [x] 5. Admin color change endpoint
  - [x] 5.1 Create `UpdateCongregationColor` action
    - Create `app/Actions/Congregations/UpdateCongregationColor.php`
    - Inject `ColorService`
    - Validate hex format (regex, case-insensitive input, store uppercase)
    - Validate distance from siblings via `isDistinctFromAll()`
    - Throw `ValidationException` with field-specific messages on failure
    - Persist the new color on the congregation
    - _Requirements: 3.1, 3.3, 3.4, 3.5_
  - [x] 5.2 Add color update route and controller method
    - Add `color` to validated fields in `CongregationController::update()` (or add a dedicated method)
    - Delegate to `UpdateCongregationColor` action
    - Authorize via existing `CongregationPolicy::update()` (admin/superadmin only)
    - Return with Sonner toast flash on success
    - _Requirements: 3.1, 3.2, 3.6_
  - [x] 5.3 Write feature tests for admin color change
    - Test admin can update congregation color successfully (toast returned)
    - Test member cannot update color (403)
    - Test invalid hex format returns validation error
    - Test too-similar color returns validation error
    - Test shared props include color on congregation objects
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 5.1_

- [x] 6. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Frontend: type updates and congregation switcher
  - [x] 7.1 Update TypeScript types for congregation color
    - Add `color: string | null` to the `Congregation` type in `resources/js/types/congregations.ts`
    - _Requirements: 5.1_
  - [x] 7.2 Add color swatch to congregation-switcher component
    - Render a 12×12px swatch with inline `background-color` set to the congregation's color
    - Use a fallback neutral color (e.g., `#94A3B8`) if color is null
    - Display swatch adjacent to congregation name in the switcher list
    - _Requirements: 5.2, 5.3, 5.4_

- [x] 8. Frontend: color picker on congregation edit page
  - [x] 8.1 Add color picker input to congregation edit page
    - Add a hex text input field with color preview swatch to `resources/js/pages/congregations/edit.tsx`
    - Only show for admins/superadmins
    - Display inline validation errors from the backend
    - Show Sonner toast on successful color update
    - _Requirements: 3.1, 3.4, 3.5, 3.6, 5.2_

- [x] 9. Data migration for existing congregations
  - [x] 9.1 Create artisan command or seeder to backfill colors for existing congregations
    - Iterate existing congregations grouped by kingdom hall
    - Use `ColorService::generateDistinctColor()` to assign colors respecting distance constraints
    - Handle congregations without a kingdom hall (assign random color)
    - _Requirements: 1.1, 1.2, 2.1_

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The `ColorService` is the central piece — get it right and tested before integrating into actions
- Frontend uses inline `background-color` style (Tailwind JIT won't work with dynamic hex values)
- The existing `CongregationPolicy::update()` handles authorization — no new policy method needed

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["2.1"] },
    { "id": 2, "tasks": ["2.2"] },
    { "id": 3, "tasks": ["2.3"] },
    { "id": 4, "tasks": ["2.4", "2.5", "2.6", "2.7"] },
    { "id": 5, "tasks": ["4.1", "4.2", "4.3", "5.1"] },
    { "id": 6, "tasks": ["4.4", "5.2"] },
    { "id": 7, "tasks": ["5.3", "7.1"] },
    { "id": 8, "tasks": ["7.2", "8.1"] },
    { "id": 9, "tasks": ["9.1"] }
  ]
}
```
