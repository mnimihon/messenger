<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>Регистрация</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <InputText v-model="name" placeholder="Имя" class="w-full" />
          <InputText v-model="email" type="email" placeholder="Email" class="w-full" />
          <Password v-model="password" placeholder="Пароль" class="w-full" toggle-mask />
          <Password v-model="passwordConfirmation" placeholder="Повтор пароля" class="w-full" toggle-mask :feedback="false" />
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button
            type="submit"
            label="Зарегистрироваться"
            :loading="loading"
            :disabled="password !== passwordConfirmation"
            class="w-full"
          />
          <RouterLink to="/login" class="text-center text-sm text-primary">Уже есть аккаунт</RouterLink>
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
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  if (password.value !== passwordConfirmation.value) return
  error.value = ''
  loading.value = true
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    router.push({ name: 'verify-email', query: { email: email.value } })
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors
      ? JSON.stringify(e.response.data.errors)
      : 'Ошибка регистрации'
  } finally {
    loading.value = false
  }
}
</script>
