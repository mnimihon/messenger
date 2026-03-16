import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { setAuthToken } from '@/api/axios'
import { refreshEchoAuth } from '@/echo'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const accessToken = ref(localStorage.getItem('access_token'))

  const isAuthenticated = computed(() => !!accessToken.value)

  function persistToken(payload) {
    const token = payload.access_token
    const expiresAt = payload.expires_at
    if (token) {
      accessToken.value = token
      localStorage.setItem('access_token', token)
      if (expiresAt) localStorage.setItem('token_expiry', expiresAt)
      setAuthToken(token)
      refreshEchoAuth()
    }
    if (payload.user) {
      user.value = payload.user
      localStorage.setItem('user_data', JSON.stringify(payload.user))
    }
  }

  function clearAuth() {
    user.value = null
    accessToken.value = null
    localStorage.removeItem('access_token')
    localStorage.removeItem('token_expiry')
    localStorage.removeItem('user_data')
    localStorage.removeItem('pending_verify_email')
    setAuthToken(null)
    refreshEchoAuth()
  }

  async function fetchUser() {
    const { data } = await api.get('/user')
    user.value = data
    localStorage.setItem('user_data', JSON.stringify(data))
    return data
  }

  async function init() {
    if (accessToken.value) {
      setAuthToken(accessToken.value)
      try {
        await fetchUser()
      } catch {
        clearAuth()
      }
    }
    const raw = localStorage.getItem('user_data')
    if (raw && !user.value) {
      try {
        user.value = JSON.parse(raw)
      } catch {
        /* ignore */
      }
    }
  }

  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    if (data.success && data.access_token) {
      persistToken(data)
      return { ok: true }
    }
    return { ok: false, data }
  }

  async function register(payload) {
    const { data } = await api.post('/register', payload)
    if (!data.success) {
      const error = new Error(data.message || 'Ошибка регистрации')
      error.response = { data }
      throw error
    }
    localStorage.setItem('pending_verify_email', payload.email)
    return true
  }

  async function verifyEmail(email, code) {
    const { data } = await api.post('/verify-email', { email, code })
    if (data.success && data.access_token) {
      persistToken(data)
      localStorage.removeItem('pending_verify_email')
      return { ok: true }
    }
    return { ok: false, data }
  }

  async function resendCode(email) {
    return api.post('/resend-code', { email })
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      clearAuth()
    }
  }

  return {
    user,
    accessToken,
    isAuthenticated,
    init,
    fetchUser,
    login,
    register,
    verifyEmail,
    resendCode,
    logout,
    clearAuth,
    persistToken,
  }
})
