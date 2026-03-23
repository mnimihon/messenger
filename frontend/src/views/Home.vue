<template>
  <Chat v-if="auth.isAuthenticated" />
  <Login v-else />
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import Chat from './Chat.vue'
import Login from './Login.vue'

const auth = useAuthStore()
</script>
