<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-4">
    <Card class="w-full max-w-md shadow-md">
      <template #title>Вход</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <InputText v-model="email" type="email" placeholder="Email" class="w-full" />
          <Password v-model="password" placeholder="Пароль" class="w-full" toggle-mask :feedback="false" />
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button type="submit" label="Войти" :loading="loading" class="w-full" />
          <div class="flex flex-col gap-2 text-sm text-center">
            <RouterLink to="/register" class="text-primary">Регистрация</RouterLink>
            <RouterLink to="/forgot-password" class="text-slate-600">Забыли пароль?</RouterLink>
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const r = await auth.login(email.value, password.value)
    if (r.ok) {
      const redirect = route.query.redirect || '/'
      router.push(redirect)
      return
    }
    error.value = r.data?.message || 'Ошибка входа'
  } catch (e) {
    const d = e.response?.data
    if (e.response?.status === 403 && d?.email) {
      localStorage.setItem('pending_verify_email', d.email || email.value)
      router.push({ name: 'verify-email', query: { email: d.email || email.value } })
      return
    }
    error.value = d?.message || 'Ошибка входа'
  } finally {
    loading.value = false
  }
}
</script>
