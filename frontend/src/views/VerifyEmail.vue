<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>
        <span class="block text-center">Подтверждение email</span>
      </template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <div class="flex flex-col gap-2">
            <label for="code" class="text-sm font-medium">Код</label>
            <InputText
              id="code"
              v-model="code"
              maxlength="6"
              placeholder="000000"
              class="w-full"
              :invalid="submitAttempted && !!errors.code"
              @blur="validateField('code')"
              @input="validateField('code')"
            />
          </div>
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

          <p v-if="resendCooldown > 0" class="text-sm text-slate-600 text-center">
            Запросить новый код через: <strong>{{ resendCooldown }}</strong> сек
          </p>
          <Button
            v-else
            type="button"
            label="Отправить код снова"
            severity="secondary"
            outlined
            class="w-full"
            :loading="resendLoading"
            @click="resend"
          />

          <Button type="submit" label="Подтвердить" :loading="loading" class="w-full" />
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
import Button from 'primevue/button'
import Message from 'primevue/message'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const email = ref(route.query.email || localStorage.getItem('pending_verify_email') || '')
const code = ref('')
const loading = ref(false)
const resendLoading = ref(false)
const error = ref('')
const errors = ref({ code: '' })
const submitAttempted = ref(false)
const RESEND_COOLDOWN_SEC = 60
const resendCooldown = ref(RESEND_COOLDOWN_SEC)
let cooldownTimer = null

function startCooldown(seconds = RESEND_COOLDOWN_SEC) {
  const sec = Math.max(0, Math.floor(Number(seconds)) || 0)
  resendCooldown.value = sec
  if (cooldownTimer) clearInterval(cooldownTimer)
  if (sec <= 0) return
  cooldownTimer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer)
  }, 1000)
}

function validateField(field) {
  if (field === 'code') errors.value.code = !code.value.trim() ? ' ' : (code.value.length !== 6 ? ' ' : '')
}

function validateAll() {
  validateField('code')
  return !errors.value.code
}

onMounted(async () => {
  if (!email.value) {
    router.replace('/register')
    return
  }
  try {
    const { data } = await api.get('/verification-cooldown', {
      params: { email: email.value },
    })
    const seconds = data?.can_resend_after_seconds
    startCooldown(seconds != null ? seconds : RESEND_COOLDOWN_SEC)
  } catch {
    startCooldown(RESEND_COOLDOWN_SEC)
  }
})

onUnmounted(() => {
  if (cooldownTimer) clearInterval(cooldownTimer)
})

async function submit() {
  error.value = ''
  submitAttempted.value = true
  if (!validateAll()) return
  if (!code.value || code.value.length !== 6) {
    error.value = 'Введите 6-значный код'
    return
  }
  loading.value = true
  try {
    const r = await auth.verifyEmail(email.value, code.value)
    if (r.ok) {
      router.push('/photos')
    } else {
      error.value = r.data?.message || 'Не удалось подтвердить'
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Ошибка запроса'
  } finally {
    loading.value = false
  }
}

async function resend() {
  if (resendCooldown.value > 0) return
  resendLoading.value = true
  error.value = ''
  try {
    await auth.resendCode(email.value)
    startCooldown(RESEND_COOLDOWN_SEC)
  } catch (e) {
    error.value = e.response?.data?.message || 'Не удалось отправить код'
    const after = e.response?.data?.can_resend_after
    if (after != null) startCooldown(after)
  } finally {
    resendLoading.value = false
  }
}
</script>
