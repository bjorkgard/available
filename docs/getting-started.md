# Getting Started

[← Back to documentation](../README.md#documentation)

## Prerequisites

- **PHP 8.4+** (via [Laravel Herd](https://herd.laravel.com) or manual install)
- **Node.js 20+** and npm
- **Composer 2**
- **SQLite** (default, no extra setup needed)

## Installation

```bash
# Clone the repository
git clone <repository-url> available
cd available

# Run the setup script (installs deps, creates .env, generates key, migrates, builds assets)
composer run setup
```

The `composer run setup` script handles:
1. `composer install`
2. Copies `.env.example` → `.env` (if not present)
3. Generates app key
4. Runs database migrations
5. `npm install`
6. `npm run build`

## Environment Configuration

Copy `.env.example` to `.env` and configure:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_NAME` | `Laravel` | Application display name |
| `APP_URL` | `http://localhost` | Base URL of the app |
| `DB_CONNECTION` | `sqlite` | Database driver |
| `BROADCAST_CONNECTION` | `log` | Set to `reverb` for real-time features |
| `QUEUE_CONNECTION` | `database` | Queue driver |
| `MAIL_MAILER` | `log` | Email driver (use `smtp` in production) |

For real-time features (WebSocket broadcasting), configure Reverb in `.env`.

## Running the Application

### With Laravel Herd (recommended)

If you have Herd installed, the app is automatically available at:

```
https://available.test
```

Start the supporting services:

```bash
composer run dev
```

This starts concurrently:
- Laravel development server
- Queue worker
- Pail log viewer
- Vite dev server (hot reload)
- Reverb WebSocket server

### Without Herd

```bash
composer run dev
```

The app will be available at `http://localhost:8000`.

## First-Time Setup

1. Visit the app and register an account
2. You'll be redirected to the **Setup Wizard**
3. Create your first congregation (name + congregation number)
4. Add your Kingdom Hall (address + number of rooms)
5. Rooms are auto-generated based on the count
6. You'll be redirected to the calendar — ready to book

## Development Commands

```bash
# Start dev environment
composer run dev

# Build frontend assets (production)
npm run build

# Run backend tests
php artisan test --compact

# Run frontend tests
npx vitest run

# Lint PHP (auto-fix)
vendor/bin/pint --dirty --format agent

# Lint JS/TS (auto-fix)
npm run lint

# Format JS/TS
npm run format

# Type check frontend
npm run types:check

# Full CI check
composer run ci:check
```

## Useful Artisan Commands

```bash
# List all routes
php artisan route:list

# Generate Wayfinder typed routes
php artisan wayfinder:generate

# Create a new test
php artisan make:test --pest TestName

# Create a new model with factory and migration
php artisan make:model ModelName -mf

# Run a specific test
php artisan test --compact --filter=TestName
```
