<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>Восстановление пароля</template>
      <template #subtitle>Укажите email — отправим код</template>
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
          <Message v-if="message" :severity="ok ? 'success' : 'error'" :closable="false">
            {{ message }}
          </Message>
          <Button type="submit" label="Отправить код" :loading="loading" class="w-full" />
          <Button label="Назад к входу" outlined class="w-full" @click="router.push('/login')" />
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Message from 'primevue/message'
import api from '@/api/axios'

const RESET_RESEND_STORAGE_KEY = 'reset_password_resend_expires_at'

const router = useRouter()
const email = ref('')
const loading = ref(false)
const message = ref('')
const ok = ref(false)
const errors = ref({ email: '' })
const submitAttempted = ref(false)

function validateField(field) {
  if (field === 'email') errors.value.email = !email.value.trim() ? ' ' : ''
}

function validateAll() {
  validateField('email')
  return !errors.value.email
}

async function submit() {
  submitAttempted.value = true
  if (!validateAll()) return
  loading.value = true
  message.value = ''
  try {
    await api.post('/forgot-password', { email: email.value })
    const expiresAt = Date.now() + 60 * 1000
    localStorage.setItem(RESET_RESEND_STORAGE_KEY, String(expiresAt))
    ok.value = true
    message.value = ''
    setTimeout(() => router.push({ name: 'reset-password', query: { email: email.value } }), 1500)
  } catch (e) {
    message.value = e.response?.data?.message || 'Ошибка'
    ok.value = false
  } finally {
    loading.value = false
  }
}
</script>
