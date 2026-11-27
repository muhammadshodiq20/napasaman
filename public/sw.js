const CACHE_NAME = 'napasaman-pwa-v2';
const OFFLINE_ASSETS = [
  '/',
  '/rute-bersih',
  '/kalkulator-paparan',
  '/bot-wa',
  '/manifest.json',
  '/images/napasaman-logo.png',
  '/images/icon-192.png',
  '/images/icon-512.png'
];

// Never cache authenticated, admin, API, or CSRF-bearing form routes
const NEVER_CACHE_PREFIXES = ['/admin', '/login', '/logout', '/api', '/lapor-asap'];

function isCacheablePath(pathname) {
  return !NEVER_CACHE_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(prefix + '/'));
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(OFFLINE_ASSETS).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME)
          .map((key) => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return;
  if (!isCacheablePath(url.pathname)) return;

  // Network-first strategy for fresh AQI data, fallback to cache when offline
  event.respondWith(
    fetch(event.request)
      .then((response) => {
        const cacheControl = (response.headers.get('Cache-Control') || '').toLowerCase();
        const isPrivate = cacheControl.includes('no-store') || cacheControl.includes('private');
        if (response && response.status === 200 && response.type === 'basic' && !isPrivate) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return response;
      })
      .catch(() => caches.match(event.request).then((cached) => cached || caches.match('/')))
  );
});
