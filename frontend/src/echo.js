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

let echoInstance = null

function createEcho() {
  return new Echo({
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
}

// Вызывать после логина / успешной верификации, когда уже есть токен
export function ensureEchoInitialized() {
  const token = localStorage.getItem('access_token')
  if (!token) return
  if (!echoInstance) {
    echoInstance = createEcho()
    window.Echo = echoInstance
  } else {
    refreshEchoAuth()
  }
}

// Обновлять заголовок при смене токена (после логина/verify)
export function refreshEchoAuth() {
  if (echoInstance?.connector?.pusher?.config?.auth?.headers) {
    Object.assign(echoInstance.connector.pusher.config.auth.headers, authHeaders())
  }
}

// Отключать сокеты при логауте
export function shutdownEcho() {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
    if (window.Echo) {
      delete window.Echo
    }
  }
}
