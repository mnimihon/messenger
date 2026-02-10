import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const accessToken = ref(localStorage.getItem('access_token'))
    const tokenExpiry = ref(localStorage.getItem('token_expiry'))

    const isTokenValid = computed(() => {
        if (!tokenExpiry.value) return false
        return new Date(tokenExpiry.value) > new Date()
    })

    const isAuthenticated = computed(() => {
        return !!accessToken.value && isTokenValid.value
    })

    async function fetchCurrentUser() {
        try {
            const response = await api.get('/user')
            user.value = response.data
            localStorage.setItem('user_data', JSON.stringify(user.value))
            return user.value
        } catch (error) {
            console.error('Ошибка загрузки пользователя:', error)
            clearAuthData()
            return null
        }
    }

    function clearAuthData() {
        user.value = null
        accessToken.value = null
        tokenExpiry.value = null
        localStorage.removeItem('access_token')
        localStorage.removeItem('token_expiry')
        localStorage.removeItem('user_data')
        delete api.defaults.headers.common.Authorization
    }

    async function init() {
        if (isTokenValid.value && accessToken.value) {
            api.defaults.headers.common.Authorization = `Bearer ${accessToken.value}`
            return await fetchCurrentUser()
        }
        return null
    }

    return {
        user,
        accessToken,
        isAuthenticated,
        fetchCurrentUser,
        clearAuthData,
        init
    }
})