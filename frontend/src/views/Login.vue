<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>Вход</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <InputText
            v-model="email"
            type="email"
            placeholder="Email"
            class="w-full"
            :invalid="submitAttempted && !!errors.email"
            @blur="validateField('email')"
            @input="validateField('email')"
          />
          <Password
            v-model="password"
            placeholder="Пароль"
            class="w-full"
            input-class="w-full"
            :toggle-mask="false"
            :feedback="false"
            :invalid="submitAttempted && !!errors.password"
            @blur="validateField('password')"
            @input="validateField('password')"
          />
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button type="submit" label="Войти" :loading="loading" class="w-full" />
          <div class="flex flex-col gap-2">
            <Button label="Регистрация" outlined class="w-full" @click="router.push('/register')" />
            <Button label="Забыли пароль?" severity="secondary" outlined class="w-full" @click="router.push('/forgot-password')" />
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
const errors = ref({ email: '', password: '' })
const submitAttempted = ref(false)

function validateField(name) {
  if (name === 'email') errors.value.email = !email.value.trim() ? ' ' : ''
  if (name === 'password') errors.value.password = !password.value ? ' ' : ''
}

function validateAll() {
  validateField('email')
  validateField('password')
  return !errors.value.email && !errors.value.password
}

async function submit() {
  submitAttempted.value = true
  if (!validateAll()) return
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
      router.push({ name: 'verify-email', query: { email: d.email || email.value, autoResend: '1' } })
      return
    }
    error.value = d?.message || 'Ошибка входа'
  } finally {
    loading.value = false
  }
}
</script>
