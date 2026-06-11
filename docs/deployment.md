# Deployment

[← Back to documentation](../README.md#documentation)

## Production Checklist

Before deploying, ensure:

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_KEY` is set (generated via `php artisan key:generate`)
- [ ] `APP_URL` is set to the production domain
- [ ] Database is configured and migrated
- [ ] `BROADCAST_CONNECTION=reverb` (if using real-time features)
- [ ] Mail driver configured (SMTP or service)
- [ ] Queue worker is running
- [ ] Frontend assets are built (`npm run build`)
- [ ] Reverb is running (if using WebSocket features)

## Environment Variables (Production)

```env
APP_NAME=Available
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=sqlite
# Or use MySQL/PostgreSQL for production

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@your-domain.com

REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

## Build Process

```bash
# Install PHP dependencies (no dev)
composer install --no-dev --optimize-autoloader

# Install Node dependencies and build
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Required Services

| Service | Purpose | Command |
|---------|---------|---------|
| Web server | Serve HTTP | nginx/Apache + PHP-FPM |
| Queue worker | Process jobs | `php artisan queue:work` |
| Scheduler | Run scheduled tasks | Cron: `* * * * * php artisan schedule:run` |
| Reverb | WebSocket server | `php artisan reverb:start` |

## Scheduled Tasks

The application has one scheduled command:

| Command | Schedule | Description |
|---------|----------|-------------|
| `bookings:cleanup` | Daily | Removes bookings older than 6 months |

Set up the Laravel scheduler in cron:

```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Laravel Cloud

The recommended deployment platform is [Laravel Cloud](https://cloud.laravel.com/), which provides:
- Automatic scaling
- Zero-downtime deployments
- Built-in queue and scheduler management
- Managed Reverb WebSocket hosting

## Security Notes

- Password requirements in production: 12+ chars, mixed case, letters, numbers, symbols, haveibeenpwned check
- Destructive database commands are blocked in production (`DB::prohibitDestructiveCommands`)
- Session encryption available via `SESSION_ENCRYPT=true`
- Rate limiting on login (5/min), 2FA (5/min), passkeys (10/min), password changes (6/min)
