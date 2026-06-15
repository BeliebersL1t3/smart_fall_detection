import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb', // Ganti menjadi 'pusher' jika Anda menggunakan Laravel Websockets (BeyondCode)
    key: import.meta.env.VITE_REVERB_APP_KEY ?? import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: true, // Wajib TRUE agar otomatis menggunakan HTTPS aman di Railway
    enabledTransports: ['ws', 'wss'],
});