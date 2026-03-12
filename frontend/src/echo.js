import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const apiBase = import.meta.env.VITE_API_URL || '/api'
const wsHost = import.meta.env.VITE_REVERB_HOST || 'localhost'
const wsPort = import.meta.env.VITE_REVERB_PORT || 8080

function authHeaders() {
  const token = localStorage.getItem('access_token')
  return {
    Authorization: token ? `Bearer ${token}` : '',
    Accept: 'application/json',
  }
}

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost,
  wsPort: Number(wsPort),
  wssPort: Number(wsPort),
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
  authEndpoint: `${apiBase.replace(/\/$/, '')}/broadcasting/auth`,
  auth: {
    headers: authHeaders(),
  },
  authTransport: 'ajax',
})

// Обновлять заголовок при смене токена (после логина/verify)
export function refreshEchoAuth() {
  if (window.Echo?.connector?.pusher?.config?.auth?.headers) {
    Object.assign(window.Echo.connector.pusher.config.auth.headers, authHeaders())
  }
}
