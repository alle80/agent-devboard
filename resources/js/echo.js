import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Reverb / Pusher-protocol WebSocket client for the live updates. Configuration comes from
// window.DEVBOARD_ECHO (runtime, printed by <x-devboard::assets />) with a fallback on the Vite env
// of the host build. Livewire finds window.Echo and wires the #[On('echo-private:...')] listeners.
const cfg = window.DEVBOARD_ECHO ?? {};
const key = cfg.key ?? import.meta.env.VITE_REVERB_APP_KEY;
const host = cfg.host ?? import.meta.env.VITE_REVERB_HOST;
const port = Number(cfg.port ?? import.meta.env.VITE_REVERB_PORT ?? 443);
const scheme = cfg.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? 'https';

// No broadcaster configured → do nothing (no WebSocket, no error).
if (key) {

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Resync: while the page is in background (phone) or the socket is down, missed events are not
// replayed. When back in the foreground and on every reconnection we ask the components to re-render.
const resync = () => window.Livewire?.dispatch('live-resync');

let wasConnected = false;
window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
    if (current === 'connected') {
        if (wasConnected) resync();
        wasConnected = true;
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') resync();
});
window.addEventListener('pageshow', (e) => { if (e.persisted) resync(); });

} // if (key)
