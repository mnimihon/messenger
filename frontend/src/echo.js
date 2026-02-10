import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: import.meta.env.VITE_API_BASE_URL + '/broadcasting/auth',
    auth: {
        headers: {
            Authorization: 'Bearer ' + localStorage.getItem('access_token'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    },
    authTransport: 'ajax',
    withCredentials: false,
});