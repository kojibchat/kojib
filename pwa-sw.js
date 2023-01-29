importScripts('https://storage.googleapis.com/workbox-cdn/releases/5.1.2/workbox-sw.js');

const CACHE = "pwa-cache";
const offlineFallbackPage = "offline/";
const filesToCache = [
  'offline/',
  'assets/files/backgrounds/error_page_bg.jpg',
  'assets/files/backgrounds/offline_error_expression_text_bg.jpg',
  'assets/thirdparty/bootstrap/bootstrap.min.css',
  'assets/css/error_page/error_page.css',
  'assets/files/defaults/favicon.png',
  'assets/fonts/montserrat/montserrat-bold.woff',
  'assets/fonts/montserrat/montserrat-medium.woff',
  'assets/fonts/montserrat/montserrat-semibold.woff',
  'assets/fonts/montserrat/font.css',
  'assets/thirdparty/bootstrap/bootstrap.bundle.min.js',
];
self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});

self.addEventListener('install', async (event) => {
  event.waitUntil(
    caches.open(CACHE)
    .then((cache) => cache.addAll(filesToCache))
  );
});

if (workbox.navigationPreload.isSupported()) {
  workbox.navigationPreload.enable();
}


self.addEventListener('fetch', (event) => {
    if (event.request.url.endsWith('api_request/')) {
      return;
    }
    event.respondWith((async () => {
      try {
        const preloadResp = await event.preloadResponse;

        if (preloadResp) {
          return preloadResp;
        }

        const networkResp = await fetch(event.request);
        return networkResp;
      } catch (error) {

        const cache = await caches.open(CACHE);
        const cachedResp = await cache.match(offlineFallbackPage);
        return cachedResp;
      }
    })());
});
