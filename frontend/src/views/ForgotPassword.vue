<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-4">
    <Card class="w-full max-w-md shadow-md">
      <template #title>Восстановление пароля</template>
      <template #subtitle>Укажите email — отправим код</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <InputText v-model="email" type="email" placeholder="Email" class="w-full" />
          <Message v-if="message" :severity="ok ? 'success' : 'error'" :closable="false">
            {{ message }}
          </Message>
          <Button type="submit" label="Отправить код" :loading="loading" class="w-full" />
          <RouterLink to="/login" class="text-center text-sm text-primary">Назад к входу</RouterLink>
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

const router = useRouter()
const email = ref('')
const loading = ref(false)
const message = ref('')
const ok = ref(false)

async function submit() {
  loading.value = true
  message.value = ''
  try {
    await api.post('/forgot-password', { email: email.value })
    ok.value = true
    message.value = 'Если аккаунт существует, код отправлен на почту.'
    setTimeout(() => router.push({ name: 'reset-password', query: { email: email.value } }), 1500)
  } catch (e) {
    message.value = e.response?.data?.message || 'Ошибка'
    ok.value = false
  } finally {
    loading.value = false
  }
}
</script>
