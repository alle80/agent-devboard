// alle80/agent-devboard — Web Push client. Reads window.DEVBOARD_PUSH = { key, subscribeUrl, csrf, sw }
// (printed by <x-devboard::assets />) and exposes window.devboardPush for the settings page (Alpine).
function b64ToUint8(base64) {
  const padding = '='.repeat((4 - (base64.length % 4)) % 4);
  const raw = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
  return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}
const cfg = () => window.DEVBOARD_PUSH || {};
const supported = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

async function registration() {
  return navigator.serviceWorker.register(cfg().sw || '/devboard-sw.js', { scope: '/' });
}
async function current() {
  if (!supported()) return null;
  const reg = await navigator.serviceWorker.getRegistration('/');
  return reg ? reg.pushManager.getSubscription() : null;
}
async function send(method, body) {
  const r = await fetch(cfg().subscribeUrl, {
    method, credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg().csrf, Accept: 'application/json' },
    body: JSON.stringify(body),
  });
  if (!r.ok) throw new Error('HTTP ' + r.status);
}

window.devboardPush = {
  supported,
  configured: () => !!cfg().key,
  /** 'unsupported' | 'nokey' | 'denied' | 'on' | 'off' */
  async status() {
    if (!supported()) return 'unsupported';
    if (!cfg().key) return 'nokey';
    if (Notification.permission === 'denied') return 'denied';
    return (await current()) ? 'on' : 'off';
  },
  async enable() {
    if (!supported() || !cfg().key) return this.status();
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') return this.status();
    const reg = await registration();
    await navigator.serviceWorker.ready;
    let sub = await reg.pushManager.getSubscription();
    if (!sub) sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToUint8(cfg().key) });
    const json = sub.toJSON();
    await send('POST', { endpoint: json.endpoint, keys: json.keys, contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0] });
    return 'on';
  },
  async disable() {
    const sub = await current();
    if (sub) { await send('DELETE', { endpoint: sub.endpoint }); await sub.unsubscribe(); }
    return this.status();
  },
  async test() {
    const r = await fetch(cfg().testUrl, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': cfg().csrf, Accept: 'application/json' } });
    return r.ok;
  },
};
