/* Only the offline document and explicitly listed public files may be cached. */
const CACHE = 'vayuma-public-v7';
const ROOT = new URL('./', self.registration.scope);
const OFFLINE = new URL('offline.html', ROOT).href;
const PUBLIC_FILES = new Map([
  ['assets/css/dashboard-layout.css', 'text/css'],
  ['frontend/css/agriculture-tools.css', 'text/css'],
  ['frontend/css/extension-workspace.css', 'text/css'],
  ['frontend/css/profile_forms.css', 'text/css'],
  ['frontend/css/pwa-install.css', 'text/css'],
  ['frontend/css/dashboard-shell.css', 'text/css'],
  ['frontend/css/dashboard-shell-transitions.css', 'text/css'],
  ['frontend/css/user-menu.css', 'text/css'],
  ['frontend/css/profile-hub.css', 'text/css'],
  ['frontend/css/tokens.css', 'text/css'],
  ['frontend/css/components.css', 'text/css'],
  ['frontend/css/components_addition.css', 'text/css'],
  ['frontend/css/layout.css', 'text/css'],
  ['frontend/css/pages.css', 'text/css'],
  ['assets/css/style.css', 'text/css'],
  ['assets/js/vayuma-language.js', 'javascript'],
  ['frontend/js/climate-guidance.js', 'javascript'],
  ['frontend/js/pwa.js', 'javascript'],
  ['frontend/js/user-menu.js', 'javascript'],
  ['frontend/js/profile-photo.js', 'javascript'],
  ['frontend/js/vayuma-ui.js', 'javascript'],
  ['frontend/images/icons.svg', 'image/svg+xml'],
  ['assets/images/vayuma-logo.jpg', 'image/jpeg'],
  ['frontend/images/vayuma-logo.jpg', 'image/jpeg'],
  ['frontend/images/pwa/icon-192.png', 'image/png'],
  ['frontend/images/pwa/icon-512.png', 'image/png'],
  ['frontend/images/pwa/maskable-192.png', 'image/png'],
  ['frontend/images/pwa/maskable-512.png', 'image/png'],
  ['frontend/images/pwa/apple-touch-icon.png', 'image/png']
].map(([path, type]) => [new URL(path, ROOT).pathname, type]));

self.addEventListener('install', event => {
  event.waitUntil((async () => {
    const response = await fetch(OFFLINE, {cache: 'reload', credentials: 'omit'});
    if (!response.ok || response.redirected || response.type !== 'basic' ||
        !(response.headers.get('content-type') || '').toLowerCase().includes('text/html')) {
      throw new Error('Offline document unavailable');
    }
    await (await caches.open(CACHE)).put(OFFLINE, response);
  })());
});
self.addEventListener('activate', event => {
  event.waitUntil((async () => {
    for (const name of await caches.keys()) {
      if (name.startsWith('vayuma-public-') && name !== CACHE) await caches.delete(name);
    }
    await self.clients.claim();
  })());
});
self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== ROOT.origin) return;
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(async () => (await caches.open(CACHE)).match(OFFLINE) || Response.error()));
    return;
  }
  const type = PUBLIC_FILES.get(url.pathname);
  // Do not store arbitrary query variants, credentials-bearing URLs or partial responses.
  if (!type || (url.search && !/^\?v=[a-zA-Z0-9._-]+$/.test(url.search)) || request.headers.has('range')) return;
  event.respondWith((async () => {
    const cache = await caches.open(CACHE);
    try {
      const response = await fetch(request);
      const contentType = (response.headers.get('content-type') || '').toLowerCase();
      const cacheControl = (response.headers.get('cache-control') || '').toLowerCase();
      if (response.ok && !response.redirected && response.type === 'basic' && contentType.includes(type) && !/\b(?:private|no-store|no-cache)\b/.test(cacheControl)) {
        await cache.put(request, response.clone());
      }
      return response;
    } catch (_) {
      return (await cache.match(request)) || Response.error();
    }
  })());
});
