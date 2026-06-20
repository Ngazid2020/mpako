var CACHE_NAME = 'mpako-v1';

var STATIC_ASSETS = [
    '/offline',
    '/images/icons/icon-72x72.png',
    '/images/icons/icon-96x96.png',
    '/images/icons/icon-128x128.png',
    '/images/icons/icon-144x144.png',
    '/images/icons/icon-152x152.png',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-384x384.png',
    '/images/icons/icon-512x512.png',
];

// Pré-cache les assets statiques à l'installation
self.addEventListener('install', function (event) {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

// Supprime les anciens caches à l'activation
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (key) { return key.startsWith('mpako-') && key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// Stratégie réseau :
//   - Navigation  → network-first, fallback page offline
//   - Icons/splash → cache-first
//   - Tout le reste → network-only (pages Livewire, API)
self.addEventListener('fetch', function (event) {
    var request = event.request;

    // Ignorer les requêtes non-GET et cross-origin
    if (request.method !== 'GET') return;
    try {
        var url = new URL(request.url);
        if (url.origin !== location.origin) return;
    } catch (e) {
        return;
    }

    // Assets statiques (icônes) : cache-first
    if (request.url.includes('/images/icons/')) {
        event.respondWith(
            caches.match(request).then(function (cached) {
                return cached || fetch(request);
            })
        );
        return;
    }

    // Navigation : network-first, page offline en fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(function () {
                return caches.match('/offline');
            })
        );
        return;
    }
});

// ── Push notifications ──
self.addEventListener('push', function (event) {
    if (!event.data) return;

    var data = event.data.json();
    var title = data.title || 'KomorShop';
    var options = {
        body:    data.body    || '',
        icon:    data.icon    || '/images/icons/icon-192x192.png',
        badge:   data.badge   || '/images/icons/icon-72x72.png',
        data:    data.data    || {},
        vibrate: [200, 100, 200],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                if ('focus' in list[i]) return list[i].focus();
            }
            if (clients.openWindow) return clients.openWindow('/commerce');
        })
    );
});
