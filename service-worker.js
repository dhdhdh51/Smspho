/**
 * Private SMS Dashboard – Service Worker
 * Strategy: Cache-first for static assets, Network-first for API/pages
 */

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE  = `sms-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `sms-dynamic-${CACHE_VERSION}`;

const STATIC_ASSETS = [
  '/offline.html',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  'https://cdn.tailwindcss.com',
];

const NEVER_CACHE = [
  '/api/',
  '/auth/',
  '/logout.php',
];

// ── Install ──────────────────────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ── Activate ─────────────────────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => k !== STATIC_CACHE && k !== DYNAMIC_CACHE)
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch ────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET and never-cache paths
  if (event.request.method !== 'GET') return;
  if (NEVER_CACHE.some(p => url.pathname.startsWith(p))) return;
  if (!['http:', 'https:'].includes(url.protocol)) return;

  // Static assets – cache first
  if (STATIC_ASSETS.includes(url.href) || url.pathname.startsWith('/icons/')) {
    event.respondWith(cacheFirst(event.request));
    return;
  }

  // Pages – network first with offline fallback
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then(res => {
          if (res.ok) cacheDynamic(event.request, res.clone());
          return res;
        })
        .catch(() => caches.match('/offline.html'))
    );
    return;
  }

  // Everything else – stale-while-revalidate
  event.respondWith(staleWhileRevalidate(event.request));
});

// ── Push Notifications ───────────────────────────────────
self.addEventListener('push', event => {
  let data = { title: 'New SMS', body: 'You have a new message', icon: '/icons/icon-192.png' };
  try { data = { ...data, ...event.data.json() }; } catch (_) {}

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body:    data.body,
      icon:    data.icon || '/icons/icon-192.png',
      badge:   '/icons/icon-72.png',
      tag:     'sms-notification',
      renotify: true,
      vibrate: [200, 100, 200],
      data:    { url: data.url || '/dashboard.php' },
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = event.notification.data?.url || '/dashboard.php';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
      const match = list.find(c => c.url.includes('/dashboard'));
      if (match) { match.focus(); return; }
      return clients.openWindow(url);
    })
  );
});

// ── Background Sync ──────────────────────────────────────
self.addEventListener('sync', event => {
  if (event.tag === 'sync-messages') {
    event.waitUntil(syncMessages());
  }
});
async function syncMessages() {
  // Notify all open windows to refresh
  const all = await clients.matchAll({ type: 'window' });
  all.forEach(c => c.postMessage({ type: 'SYNC_MESSAGES' }));
}

// ── Cache helpers ────────────────────────────────────────
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  cacheDynamic(request, response.clone());
  return response;
}

async function staleWhileRevalidate(request) {
  const cache    = await caches.open(DYNAMIC_CACHE);
  const cached   = await cache.match(request);
  const fetching = fetch(request).then(res => {
    if (res.ok) cache.put(request, res.clone());
    return res;
  });
  return cached || fetching;
}

async function cacheDynamic(request, response) {
  if (!response || !response.ok) return;
  const cache = await caches.open(DYNAMIC_CACHE);
  cache.put(request, response);
}
