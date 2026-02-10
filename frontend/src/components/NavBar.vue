<template>
  <nav class="bg-gray-800 border-b border-gray-700 px-4 py-3">
    <div class="container mx-auto flex items-center justify-between">
      <!-- Логотип -->
      <router-link to="/" class="flex items-center space-x-2">
        <i class="pi pi-comments text-blue-400 text-xl"></i>
        <span class="text-white font-bold text-lg">Messenger</span>
      </router-link>

      <!-- Ссылки -->
      <div class="flex items-center space-x-4">
        <template v-if="!user">
          <router-link
              to="/login"
              class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
              :class="{ 'bg-gray-700 text-white': $route.path === '/login' }"
          >
            Вход
          </router-link>
          <router-link
              to="/register"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          >
            Регистрация
          </router-link>
        </template>
        <template v-else>
          <span class="text-gray-300 text-sm">{{ user.name }}</span>
          <button
              @click="handleLogout"
              class="text-gray-400 hover:text-red-400 p-2"
              title="Выйти"
          >
            <i class="pi pi-sign-out"></i>
          </button>
        </template>
      </div>
    </div>
  </nav>
</template>

<script>
import { useRouter } from 'vue-router'

export default {
  name: 'NavBar',

  props: {
    user: {
      type: Object,
      default: null
    }
  },

  setup() {
    const router = useRouter()

    const handleLogout = async () => {
      try {
        // Временная заглушка
        console.log('Выход')
        router.push('/login')
      } catch (error) {
        console.error('Ошибка выхода:', error)
      }
    }

    return {
      handleLogout
    }
  }
}
</script>