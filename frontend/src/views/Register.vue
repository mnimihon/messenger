<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>Регистрация</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <InputText
            v-model="name"
            placeholder="Имя"
            class="w-full"
            :invalid="submitAttempted && !!errors.name"
            @blur="validateField('name')"
            @input="validateField('name')"
          />
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
          <Password
            v-model="passwordConfirmation"
            placeholder="Повтор пароля"
            class="w-full"
            input-class="w-full"
            :toggle-mask="false"
            :feedback="false"
            :invalid="submitAttempted && !!errors.passwordConfirmation"
            @blur="validateField('passwordConfirmation')"
            @input="validateField('passwordConfirmation')"
          />
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button
            type="submit"
            label="Зарегистрироваться"
            :loading="loading"
            :disabled="password !== passwordConfirmation"
            class="w-full"
          />
          <Button label="Уже есть аккаунт" outlined class="w-full" @click="router.push('/login')" />
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
const errors = ref({ name: '', email: '', password: '', passwordConfirmation: '' })
const submitAttempted = ref(false)

function validateField(fieldName) {
  if (fieldName === 'name') errors.value.name = !name.value.trim() ? ' ' : ''
  if (fieldName === 'email') errors.value.email = !email.value.trim() ? ' ' : ''
  if (fieldName === 'password') errors.value.password = !password.value ? ' ' : ''
  if (fieldName === 'passwordConfirmation') errors.value.passwordConfirmation = !passwordConfirmation.value ? ' ' : (password.value !== passwordConfirmation.value ? ' ' : '')
}

function validateAll() {
  validateField('name')
  validateField('email')
  validateField('password')
  validateField('passwordConfirmation')
  return !errors.value.name && !errors.value.email && !errors.value.password && !errors.value.passwordConfirmation
}

async function submit() {
  submitAttempted.value = true
  if (password.value !== passwordConfirmation.value) {
    errors.value.passwordConfirmation = ' '
    return
  }
  if (!validateAll()) return
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
