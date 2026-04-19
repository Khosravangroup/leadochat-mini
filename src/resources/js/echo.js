import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;

if (reverbKey && reverbHost) {
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 443);
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: [reverbScheme === 'https' ? 'wss' : 'ws'],
    });
}
