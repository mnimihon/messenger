<template>
  <div class="min-h-full bg-slate-50 p-3 sm:p-4 md:p-6">
    <div class="max-w-lg mx-auto flex flex-col gap-4 w-full min-w-0">
      <Card>
        <template #title>Профиль</template>
        <template #content>
          <form class="flex flex-col gap-3" @submit.prevent="saveName">
            <InputText
              v-model="name"
              placeholder="Имя"
              class="w-full"
              :invalid="nameSubmitAttempted && !!errors.name"
              @blur="validateField('name')"
              @input="validateField('name')"
            />
            <Button type="submit" label="Сохранить имя" :loading="nameLoading" />
          </form>
        </template>
      </Card>
      <Card>
        <template #title>Смена пароля</template>
        <template #content>
          <form class="flex flex-col gap-3" @submit.prevent="savePassword">
            <Password
              v-model="currentPassword"
              placeholder="Текущий пароль"
              class="w-full"
              input-class="w-full"
              :toggle-mask="false"
              :feedback="false"
              :invalid="passwordSubmitAttempted && !!errors.currentPassword"
              @blur="validateField('currentPassword')"
              @input="validateField('currentPassword')"
            />
            <Password
              v-model="newPassword"
              placeholder="Новый пароль"
              class="w-full"
              input-class="w-full"
              :toggle-mask="false"
              :feedback="false"
              :invalid="passwordSubmitAttempted && !!errors.newPassword"
              @blur="validateField('newPassword')"
              @input="validateField('newPassword')"
            />
            <Password
              v-model="newPasswordConfirmation"
              placeholder="Повтор нового"
              class="w-full"
              input-class="w-full"
              :toggle-mask="false"
              :feedback="false"
              :invalid="passwordSubmitAttempted && !!errors.newPasswordConfirmation"
              @blur="validateField('newPasswordConfirmation')"
              @input="validateField('newPasswordConfirmation')"
            />
            <Button type="submit" label="Сменить пароль" :loading="passLoading" />
          </form>
          <Message v-if="passMsg" :severity="passOk ? 'success' : 'error'" class="mt-2" :closable="false">
            {{ passMsg }}
          </Message>
        </template>
      </Card>
      <Card>
        <template #title>Удаление аккаунта</template>
        <template #content>
          <Password
            v-model="deletePassword"
            placeholder="Пароль для подтверждения"
            class="w-full mb-2"
            input-class="w-full"
            :toggle-mask="false"
            :feedback="false"
            :invalid="deleteSubmitAttempted && !!errors.deletePassword"
            @blur="validateField('deletePassword')"
            @input="validateField('deletePassword')"
          />
          <Button label="Удалить аккаунт" severity="danger" class="w-full" :loading="deleteLoading" @click="deleteAccount" />
        </template>
      </Card>
      <Button label="Назад в чат" severity="secondary" outlined class="w-full sm:w-auto" @click="$router.push('/')" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import api from '@/api/axios'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const nameLoading = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const passLoading = ref(false)
const passMsg = ref('')
const passOk = ref(false)
const deletePassword = ref('')
const deleteLoading = ref(false)
const errors = ref({
  name: '',
  currentPassword: '',
  newPassword: '',
  newPasswordConfirmation: '',
  deletePassword: ''
})
const nameSubmitAttempted = ref(false)
const passwordSubmitAttempted = ref(false)
const deleteSubmitAttempted = ref(false)

function validateField(field) {
  if (field === 'name') errors.value.name = !name.value.trim() ? ' ' : ''
  if (field === 'currentPassword') errors.value.currentPassword = !currentPassword.value ? ' ' : ''
  if (field === 'newPassword') errors.value.newPassword = !newPassword.value ? ' ' : ''
  if (field === 'newPasswordConfirmation') errors.value.newPasswordConfirmation = !newPasswordConfirmation.value ? ' ' : (newPassword.value !== newPasswordConfirmation.value ? ' ' : '')
  if (field === 'deletePassword') errors.value.deletePassword = !deletePassword.value ? ' ' : ''
}

onMounted(async () => {
  try {
    const { data } = await api.get('/profile')
    if (data.data?.name) name.value = data.data.name
  } catch {
    const u = auth.user
    if (u?.name) name.value = u.name
  }
})

async function saveName() {
  nameSubmitAttempted.value = true
  validateField('name')
  if (errors.value.name) return
  nameLoading.value = true
  try {
    await api.put('/profile/name', { name: name.value })
  } finally {
    nameLoading.value = false
  }
}

async function savePassword() {
  passMsg.value = ''
  passwordSubmitAttempted.value = true
  validateField('currentPassword')
  validateField('newPassword')
  validateField('newPasswordConfirmation')
  if (errors.value.currentPassword || errors.value.newPassword || errors.value.newPasswordConfirmation) return
  if (newPassword.value !== newPasswordConfirmation.value) return
  passLoading.value = true
  try {
    await api.put('/profile/password', {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirmation.value,
    })
    passOk.value = true
    passMsg.value = 'Пароль изменён'
  } catch (e) {
    passOk.value = false
    passMsg.value = e.response?.data?.message || 'Ошибка'
  } finally {
    passLoading.value = false
  }
}

async function deleteAccount() {
  deleteSubmitAttempted.value = true
  validateField('deletePassword')
  if (errors.value.deletePassword) return
  deleteLoading.value = true
  try {
    await api.delete('/profile/delete_account', { data: { password: deletePassword.value } })
    auth.clearAuth()
    router.push('/login')
  } catch (e) {
    passMsg.value = e.response?.data?.message || 'Ошибка'
    passOk.value = false
  } finally {
    deleteLoading.value = false
  }
}
</script>
