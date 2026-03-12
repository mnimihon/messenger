<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-4">
    <Card class="w-full max-w-md shadow-md">
      <template #title>Новый пароль</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <InputText v-model="email" type="email" placeholder="Email" class="w-full" />
          <InputText v-model="code" placeholder="Код из письма" class="w-full" />
          <Password v-model="password" placeholder="Новый пароль" class="w-full" toggle-mask :feedback="false" />
          <Password v-model="passwordConfirmation" placeholder="Повтор пароля" class="w-full" toggle-mask :feedback="false" />
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button type="submit" label="Сохранить" :loading="loading" class="w-full" />
          <RouterLink to="/login" class="text-center text-sm text-primary">Вход</RouterLink>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref(route.query.email || '')
const code = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref('')

onMounted(() => {
  if (!email.value && route.query.email) email.value = route.query.email
})

async function submit() {
  error.value = ''
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Пароли не совпадают'
    return
  }
  loading.value = true
  try {
    const { data } = await api.post('/reset-password', {
      email: email.value,
      code: code.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    if (data.access_token) {
      auth.persistToken(data)
      router.push('/')
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Ошибка'
  } finally {
    loading.value = false
  }
}
</script>
