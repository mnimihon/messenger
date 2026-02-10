// src/api/axios.js
import axios from 'axios'

const api = axios.create({
    baseURL: 'http://localhost:8001/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
})

// Автоматически добавляем токен к каждому запросу
api.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Обработка ошибок 401
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('access_token')
            localStorage.removeItem('token_expiry')
            localStorage.removeItem('user_data')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

export default api