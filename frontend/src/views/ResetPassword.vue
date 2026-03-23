<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>
        <div class="w-full text-center">Новый пароль</div>
      </template>
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
          <InputText
            v-model="code"
            placeholder="Код из письма"
            class="w-full"
            maxlength="6"
            :invalid="submitAttempted && !!errors.code"
            @blur="validateField('code')"
            @input="validateField('code')"
          />
          <Password
            v-model="password"
            placeholder="Новый пароль"
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

          <p v-if="resendCooldown > 0" class="text-sm text-slate-600">
            Запросить новый код через: <strong>{{ resendCooldown }}</strong> сек
          </p>
          <Button
            v-else
            type="button"
            label="Отправить код"
            severity="secondary"
            outlined
            class="w-full"
            :loading="resendLoading"
            @click="resendCode"
          />

          <Button type="submit" label="Сохранить" :loading="loading" class="w-full" />
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const RESEND_COOLDOWN_SEC = 60
const RESET_RESEND_STORAGE_KEY = 'reset_password_resend_expires_at'
const PENDING_RESET_EMAIL_KEY = 'pending_reset_email'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const code = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref('')
const errors = ref({
  email: '',
  code: '',
  password: '',
  passwordConfirmation: ''
})
const resendCooldown = ref(0)
const resendLoading = ref(false)
const submitAttempted = ref(false)
let cooldownTimer = null

function validateField(name) {
  const v = { email: email.value.trim(), code: code.value.trim(), password: password.value, passwordConfirmation: passwordConfirmation.value }
  if (name === 'email') {
    errors.value.email = !v.email ? 'Введите email' : ''
  } else if (name === 'code') {
    errors.value.code = !v.code ? 'Введите код из письма' : (v.code.length !== 6 ? 'Код должен быть 6 цифр' : '')
  } else if (name === 'password') {
    errors.value.password = !v.password ? 'Введите новый пароль' : ''
  } else if (name === 'passwordConfirmation') {
    errors.value.passwordConfirmation = !v.passwordConfirmation ? 'Повторите пароль' : (v.password !== v.passwordConfirmation ? 'Пароли не совпадают' : '')
  }
}

function validateAll() {
  validateField('email')
  validateField('code')
  validateField('password')
  validateField('passwordConfirmation')
  return !errors.value.email && !errors.value.code && !errors.value.password && !errors.value.passwordConfirmation
}

function startCooldown(seconds = RESEND_COOLDOWN_SEC) {
  const sec = Math.max(0, Math.floor(Number(seconds)) || 0)
  resendCooldown.value = sec
  if (cooldownTimer) clearInterval(cooldownTimer)
  if (sec <= 0) {
    localStorage.removeItem(RESET_RESEND_STORAGE_KEY)
    return
  }
  const expiresAt = Date.now() + sec * 1000
  localStorage.setItem(RESET_RESEND_STORAGE_KEY, String(expiresAt))
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) {
      clearInterval(cooldownTimer)
      localStorage.removeItem(RESET_RESEND_STORAGE_KEY)
    }
  }, 1000)
}

async function resendCode() {
  if (resendCooldown.value > 0) return
  const e = email.value.trim()
  if (!e) {
    errors.value.email = 'Введите email'
    return
  }
  resendLoading.value = true
  error.value = ''
  try {
    const { data } = await api.post('/forgot-password', { email: e })
    if (data.message) {
      startCooldown(data.can_resend_after ?? RESEND_COOLDOWN_SEC)
    }
  } catch (err) {
    const d = err.response?.data
    error.value = d?.message || 'Не удалось отправить код'
    if (d?.can_resend_after != null) startCooldown(d.can_resend_after)
  } finally {
    resendLoading.value = false
  }
}

onMounted(() => {
  const qEmail = typeof route.query.email === 'string' ? route.query.email.trim() : ''
  if (qEmail) {
    localStorage.setItem(PENDING_RESET_EMAIL_KEY, qEmail)
    router.replace({ name: 'reset-password', query: {} })
  }

  const stored = localStorage.getItem(PENDING_RESET_EMAIL_KEY) || ''
  email.value = stored

  const raw = localStorage.getItem(RESET_RESEND_STORAGE_KEY)
  if (raw) {
    const msLeft = Number(raw) - Date.now()
    const secLeft = Math.max(0, Math.ceil(msLeft / 1000))
    if (secLeft > 0) {
      startCooldown(secLeft)
      return
    }
  }

  resendCooldown.value = 0
})

onUnmounted(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})

async function submit() {
  error.value = ''
  submitAttempted.value = true
  if (!validateAll()) return
  if (password.value !== passwordConfirmation.value) {
    errors.value.passwordConfirmation = 'Пароли не совпадают'
    return
  }
  loading.value = true
  try {
    const { data } = await api.post('/reset-password', {
      email: email.value.trim(),
      code: code.value.trim(),
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    if (data.access_token) {
      localStorage.removeItem(PENDING_RESET_EMAIL_KEY)
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
