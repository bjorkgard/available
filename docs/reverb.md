# Laravel Reverb — WebSocket Server

Laravel Reverb provides real-time WebSocket communication for the booking system's live calendar updates. It broadcasts booking events (created, updated, deleted) to all connected users sharing the same Kingdom Hall.

## Architecture

```
Browser (Laravel Echo + Pusher.js) ⇄ Reverb WebSocket Server ⇄ Laravel App (broadcasts events)
```

Events are dispatched by the PHP app via the Reverb API, and Reverb pushes them to connected browser clients over WebSocket connections on private channels (`private-kingdom-hall.{id}`).

---

## Development Setup

### Prerequisites

- PHP 8.5+
- Node.js 20+
- The project's dependencies installed (`composer install && npm install`)

### Environment Variables

The installer adds these to `.env` automatically:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=548377
REVERB_APP_KEY=dwvyst8edisj4pqngpcl
REVERB_APP_SECRET=srbuz1fu06wm4mrdbdgv
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Starting in Development

Run all services with the dev command (includes Reverb automatically):

```bash
composer run dev
```

Or start Reverb independently:

```bash
php artisan reverb:start --debug
```

The `--debug` flag outputs all WebSocket connections and messages to the terminal for debugging.

### Verifying It Works

1. Open the calendar in two browser tabs (same or different users in the same Kingdom Hall)
2. Create a booking in one tab
3. The booking should appear in the other tab without a page refresh
4. Check the Reverb terminal for connection and event logs

### Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| "Offline" indicator stays visible | Reverb not running | Run `php artisan reverb:start` |
| Events not received | Missing `broadcastAs()` | Verify events return custom names (`booking.created` etc.) |
| 401 on channel auth | User not in Kingdom Hall | Check channel authorization in `routes/channels.php` |
| CORS errors | Wrong `REVERB_HOST` | Ensure it matches the browser origin |

---

## Production Setup (Laravel Forge)

### 1. Install Reverb on the Server

Reverb is included in the Composer dependencies — no additional installation needed on deploy.

### 2. Environment Variables

Add these to the Forge environment (`.env`):

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-unique-app-id
REVERB_APP_KEY=your-unique-app-key
REVERB_APP_SECRET=your-unique-app-secret
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="your-domain.com"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

> **Important:** `REVERB_HOST` should be `0.0.0.0` (listen on all interfaces). `VITE_REVERB_HOST` should be your public domain. `VITE_REVERB_PORT` should be `443` (the Nginx proxy port).

Generate unique credentials:

```bash
php artisan reverb:install
# Or generate manually — any random strings work for APP_ID, APP_KEY, APP_SECRET
```

### 3. Create Reverb Daemon in Forge

In Forge, go to your server → **Daemons** → **Create Daemon**:

| Field | Value |
|-------|-------|
| Command | `php artisan reverb:start` |
| Directory | `/home/forge/your-domain.com` |
| User | `forge` |
| Processes | `1` |
| Start Seconds | `1` |
| Stop Seconds | `10` |
| Stop Signal | `SIGTERM` |

Click **Create**. Forge uses Supervisor to keep Reverb running and restart it on crash.

### 4. Configure Nginx Reverse Proxy

Forge configures this automatically when you create a WebSocket site. If you need to do it manually, add to your Nginx site config:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";

    proxy_pass http://0.0.0.0:8080;
}

location /apps {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;

    proxy_pass http://0.0.0.0:8080;
}
```

Reverb listens on `/app` (WebSocket connections) and `/apps` (API requests from the Laravel app).

### 5. SSL/TLS

If your site uses HTTPS (it should), the WebSocket connection upgrades via `wss://` through Nginx's proxy. The browser connects to `wss://your-domain.com/app` which Nginx forwards to Reverb on port 8080 internally.

### 6. Scaling (Optional)

For high-traffic deployments, increase Nginx worker connections:

```nginx
worker_rlimit_nofile 10000;

events {
    worker_connections 10000;
    multi_accept on;
}
```

### 7. Restarting Reverb After Deploys

Add to your Forge deploy script:

```bash
sudo supervisorctl restart reverb
```

Or if the daemon is named differently, check `sudo supervisorctl status` and use that name.

---

## Queue Worker

Broadcast events are dispatched through the queue. Ensure a queue worker is running:

```bash
# Development
php artisan queue:work

# Production (Forge creates this as a daemon automatically)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Without the queue worker, broadcast events will not reach Reverb.
