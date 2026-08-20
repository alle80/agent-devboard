<?php

namespace Alle80\Griglia\Http\Controllers;

use Illuminate\Http\Response;

/** Serves the Web Push service worker from the site root (scope «/»). */
class ServiceWorkerController
{
    public function __invoke(): Response
    {
        $js = <<<'JS'
// Griglia — Web Push service worker
self.addEventListener('push', (event) => {
  let payload = {};
  try { payload = event.data ? event.data.json() : {}; } catch (e) { payload = { title: event.data ? event.data.text() : '' }; }
  const title = payload.title || 'Griglia';
  const options = payload.options || payload;
  event.waitUntil((async () => {
    await self.registration.showNotification(title, {
      body: options.body || '',
      tag: options.tag,
      icon: options.icon,
      badge: options.badge,
      data: options.data || {},
      renotify: !!options.tag,
    });
    // tell open pages (diagnostics in /settings) that a push reached this device
    const list = await clients.matchAll({ type: 'window', includeUncontrolled: true });
    list.forEach((c) => c.postMessage({ type: 'griglia-push', title, body: options.body || '' }));
  })());
});
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
    for (const c of list) { if ('focus' in c) { c.navigate(url); return c.focus(); } }
    return clients.openWindow(url);
  }));
});
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(clients.claim()));
JS;

        return response($js, 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Cache-Control' => 'no-cache', 'Service-Worker-Allowed' => '/']);
    }
}
