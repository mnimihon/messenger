import axios from 'axios'

// Базовый URL для API (указываем адрес Laravel backend)
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

// Создаем экземпляр axios с настройками
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  withCredentials: true, // Важно для Sanctum cookies
  withXSRFToken: true
})

// Интерцептор для добавления токена
api.interceptors.request.use(
    (config) => {
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => {
      return Promise.reject(error)
    }
)

// Интерцептор для обработки ошибок
api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        // Токен истек или невалиден
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
      }
      return Promise.reject(error)
    }
)

// Функция для получения CSRF cookie (требуется для Laravel Sanctum)
export const getCsrfCookie = async () => {
  try {
    await api.get('/sanctum/csrf-cookie')
    return true
  } catch (error) {
    console.error('CSRF cookie error:', error)
    return false
  }
}

// Auth сервисы
export const authService = {
  // Регистрация
  async register(data) {
    await getCsrfCookie() // Получаем CSRF cookie перед запросом
    return api.post('/register', data)
  },

  // Вход
  async login(data) {
    await getCsrfCookie()
    return api.post('/login', data)
  },

  // Выход
  async logout() {
    return api.post('/logout')
  },

  // Получение данных пользователя
  async getUser() {
    return api.get('/user')
  }
}

export default api