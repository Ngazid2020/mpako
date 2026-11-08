var CACHE_NAME = 'beez-v2';

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
                    .filter(function (key) { return key.startsWith('beez-') && key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// ─────────────────────────────────────────────────────────────
// Mode hors ligne de la plateforme
// ─────────────────────────────────────────────────────────────
// /commerce/{slug}/hors-ligne est une application autonome (Alpine + IndexedDB).
// On la garde en cache avec ses assets Vite : quand le réseau tombe, toute
// navigation vers une page du commerce affiche cette application.
var OFFLINE_APP_RE = /^\/commerce\/([^/]+)\/hors-ligne\/?$/;
var PANEL_PAGE_RE = /^\/commerce\/([^/]+)(\/|$)/;
var RESERVED_SLUGS = ['login', 'register', 'pending', 'logout', 'password-reset', 'email-verification'];

function offlineAppPath(slug) {
    return '/commerce/' + slug + '/hors-ligne';
}

// Met en cache la page de l'application hors ligne + ses assets /build/assets/*
function cacheOfflineApp(path, response) {
    if (!response || !response.ok || response.redirected || response.type !== 'basic') {
        return Promise.resolve();
    }
    return caches.open(CACHE_NAME).then(function (cache) {
        return response.clone().text().then(function (html) {
            var assets = html.match(/\/build\/assets\/[^"'\s)<>]+/g) || [];
            var unique = assets.filter(function (a, i) { return assets.indexOf(a) === i; });
            return Promise.all([
                cache.put(path, response.clone()),
                Promise.all(unique.map(function (asset) {
                    return cache.match(asset).then(function (hit) {
                        return hit || fetch(asset).then(function (r) { if (r.ok) return cache.put(asset, r); }).catch(function () {});
                    });
                })),
            ]);
        });
    });
}

// Préchargement demandé par le panel Filament (renderHook « offline-bridge »)
self.addEventListener('message', function (event) {
    var data = event.data || {};
    if (data.type !== 'warm-offline' || !data.url) return;
    var path = new URL(data.url, location.origin).pathname.replace(/\/$/, '');
    if (!OFFLINE_APP_RE.test(path)) return;
    event.waitUntil(
        fetch(path, { credentials: 'include' })
            .then(function (response) { return cacheOfflineApp(path, response); })
            .catch(function () {})
    );
});

// Stratégie réseau :
//   - Assets Vite (/build/assets, noms hachés) → cache-first
//   - Icônes → cache-first
//   - Application hors ligne → network-first, mise en cache, fallback cache
//   - Autres pages du commerce → network-first, fallback application hors ligne
//   - Tout le reste → network-only (Livewire, API)
self.addEventListener('fetch', function (event) {
    var request = event.request;

    // Ignorer les requêtes non-GET et cross-origin
    if (request.method !== 'GET') return;
    var url;
    try {
        url = new URL(request.url);
        if (url.origin !== location.origin) return;
    } catch (e) {
        return;
    }

    // Assets compilés (hash dans le nom) et icônes : cache-first
    if (url.pathname.indexOf('/build/assets/') === 0 || url.pathname.indexOf('/images/icons/') === 0) {
        event.respondWith(
            caches.match(request).then(function (cached) {
                if (cached) return cached;
                return fetch(request).then(function (response) {
                    if (response.ok && url.pathname.indexOf('/build/assets/') === 0) {
                        var copy = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) { cache.put(url.pathname, copy); });
                    }
                    return response;
                });
            })
        );
        return;
    }

    if (request.mode !== 'navigate') return;

    var path = url.pathname.replace(/\/$/, '');

    // Application hors ligne elle-même
    if (OFFLINE_APP_RE.test(path)) {
        event.respondWith(
            fetch(request).then(function (response) {
                event.waitUntil(cacheOfflineApp(path, response));
                return response;
            }).catch(function () {
                return caches.match(path).then(function (cached) {
                    return cached || caches.match('/offline');
                });
            })
        );
        return;
    }

    // Autres navigations : réseau, sinon application hors ligne du commerce visité
    event.respondWith(
        fetch(request).catch(function () {
            var match = path.match(PANEL_PAGE_RE);
            var slug = match && RESERVED_SLUGS.indexOf(match[1]) === -1 ? match[1] : null;
            var fallback = slug ? caches.match(offlineAppPath(slug)) : Promise.resolve(null);
            return fallback.then(function (cached) {
                return cached || caches.match('/offline');
            });
        })
    );
});

// ── Push notifications ──
self.addEventListener('push', function (event) {
    if (!event.data) return;

    try { var data = event.data.json(); } catch (e) { var data = { title: 'BeeZ', body: '' }; }
    var title = data.title || 'BeeZ';
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
