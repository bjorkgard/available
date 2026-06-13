# Localization

## Rules

- All user-facing strings in PHP must use `__()` or `trans()`.
- All user-facing strings in React/TypeScript must use the `t()` function from `react-i18next` (via `useTranslation()`).
- New translation keys must be added to both `lang/sv.json` and `lang/en.json` (frontend) or both `lang/sv/app.php` and `lang/en/app.php` (backend).
- Swedish (`sv`) is the default and fallback locale. English (`en`) is the secondary supported locale.

## What Counts as a User-Facing String

A string is "user-facing" if it is rendered to the user in any of these contexts:

- HTML/JSX text content (headings, paragraphs, labels, buttons)
- Component props that render text (`label`, `placeholder`, `title`, `description`, `aria-label`)
- Toast/flash messages (Sonner, `Inertia::flash()`)
- Notification content (mail subject, body, action text)
- Validation error messages (custom `$fail()` messages, custom rule messages)

## What Is NOT a User-Facing String

These are excluded from localization requirements:

- Log messages (`Log::info(...)`, `logger(...)`)
- Internal exception messages (`throw new \Exception(...)`)
- Class names, route names, configuration keys
- Database column names and enum values
- Test assertions and test data
- Code comments and PHPDoc blocks
- CSS class names and HTML attributes that are not rendered as text

## Backend Pattern

```php
// Correct
__('app.booking_created')
trans('app.invitation_sent')

// Incorrect — raw string in flash/notification
session()->flash('success', 'Booking created');
```

## Frontend Pattern

```tsx
// Correct
const { t } = useTranslation();
<Button>{t('Spara')}</Button>
<Input placeholder={t('Sök...')} />
toast.success(t('Bokning skapad'));

// Incorrect — raw string in JSX
<Button>Spara</Button>
<Input placeholder="Sök..." />
toast.success('Bokning skapad');
```

## Translation File Locations

| Context | Files |
|---------|-------|
| Backend (PHP) | `lang/sv/auth.php`, `lang/sv/app.php`, etc. + `lang/en/` mirrors |
| Frontend (JSON) | `lang/sv.json`, `lang/en.json` |

The JSON files use key-as-default-text pattern: keys are the Swedish text, values are the translation for that locale.
