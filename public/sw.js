/*
 * Envydeo service worker — makes the site installable and resilient.
 *
 * Strategy (deliberately conservative so Livewire, logins and forms behave exactly as before):
 *  - Page navigations: always network first; only when offline show /offline.html.
 *  - Built assets (/build/*), icons, fonts and TMDB images: cache-first with a size cap.
 *  - Everything else (Livewire requests, POSTs, JSON APIs): untouched, straight to the network.
 *  - Push: show notifications sent by the server (used later for invites/matches).
 *
 * Bump VERSION to force clients onto a fresh cache after changing this file.
 */
const VERSION = 'v1';
const SHELL_CACHE = `envydeo-shell-${VERSION}`;
const RUNTIME_CACHE = `envydeo-runtime-${VERSION}`;
const RUNTIME_MAX_ENTRIES = 300;

const SHELL = ['/offline.html', '/favicon.svg', '/icons/icon-192.png', '/icons/icon-512.png', '/manifest.json'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(SHELL_CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('envydeo-') && ![SHELL_CACHE, RUNTIME_CACHE].includes(k)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

const isCacheableAsset = (url) =>
    (url.origin === self.location.origin && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname === '/favicon.svg'))
    || url.hostname === 'image.tmdb.org'
    || url.hostname === 'fonts.bunny.net';

async function trimCache(name, max) {
    const cache = await caches.open(name);
    const keys = await cache.keys();
    await Promise.all(keys.slice(0, Math.max(0, keys.length - max)).map((k) => cache.delete(k)));
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return; // never touch POSTs (Livewire, forms)

    const url = new URL(request.url);

    // Pages: network first, offline fallback.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    // Static assets & images: cache first.
    if (isCacheableAsset(url)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok || response.type === 'opaque') {
                    const copy = response.clone();
                    caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, copy)).then(() => trimCache(RUNTIME_CACHE, RUNTIME_MAX_ENTRIES));
                }
                return response;
            })),
        );
    }
    // Anything else falls through to the network untouched.
});

// ---- Push notifications (server sends JSON: {title, body, url, tag}) --------------------------
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data && event.data.text() }; }

    event.waitUntil(self.registration.showNotification(data.title || 'Envydeo', {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: data.tag,
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            const existing = clients.find((c) => c.url === target);
            return existing ? existing.focus() : self.clients.openWindow(target);
        }),
    );
});
