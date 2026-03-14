<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-3 sm:p-4 md:p-6">
    <Card class="w-full max-w-md shadow-md mx-2 sm:mx-0">
      <template #title>Подтверждение email</template>
      <template #subtitle>
        Введите код из письма на {{ email }}
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
            />
          </div>
          <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
          <Button type="submit" label="Подтвердить" :loading="loading" class="w-full" />
          <Button
            type="button"
            label="Отправить код снова"
            severity="secondary"
            outlined
            class="w-full"
            :loading="resendLoading"
            @click="resend"
          />
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
import Button from 'primevue/button'
import Message from 'primevue/message'
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

function validateField(field) {
  if (field === 'code') errors.value.code = !code.value.trim() ? ' ' : (code.value.length !== 6 ? ' ' : '')
}

function validateAll() {
  validateField('code')
  return !errors.value.code
}

onMounted(() => {
  if (!email.value) {
    router.replace('/register')
  }
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
  resendLoading.value = true
  error.value = ''
  try {
    await auth.resendCode(email.value)
  } catch (e) {
    error.value = e.response?.data?.message || 'Не удалось отправить код'
  } finally {
    resendLoading.value = false
  }
}
</script>
