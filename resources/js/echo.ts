import { http } from '@inertiajs/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    // Attach the X-Socket-ID header to all Inertia requests so that
    // dontBroadcastToCurrentUser() can exclude this connection server-side.
    http.onRequest((config) => {
        if (window.Echo?.socketId()) {
            config.headers = config.headers ?? {};
            config.headers['X-Socket-ID'] = window.Echo.socketId();
            console.log(
                '[Echo] Attaching X-Socket-ID:',
                window.Echo.socketId(),
            );
        } else {
            console.log('[Echo] No socket ID available for request');
        }

        return config;
    });
}
