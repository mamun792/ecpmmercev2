import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Check which broadcaster to use: reverb (local) or pusher (production)
const broadcaster = import.meta.env.VITE_BROADCAST_DRIVER || 'reverb';

if (broadcaster === 'pusher') {
    // Use Pusher (for production with cloud service)
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
    });
} else {
    // Use Reverb (for local development)
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
