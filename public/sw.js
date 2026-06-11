const CACHE_NAME = 'jwapp-v1';

// Install event — cache shell assets
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate event — clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) =>
            Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name)),
            ),
        ),
    );
    self.clients.claim();
});

// Fetch event — network-first strategy (the app is dynamic, so we don't aggressively cache)
self.addEventListener('fetch', (event) => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    // Skip non-http(s) requests
    if (!event.request.url.startsWith('http')) return;

    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request)),
    );
});
