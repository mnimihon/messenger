<template>
  <div class="min-h-screen bg-slate-50 p-3 sm:p-4 md:p-6">
    <div class="max-w-2xl mx-auto w-full">
      <Card>
        <template #title>Фотографии профиля</template>
        <template #subtitle>Загрузите несколько фото (до 10 за раз). Главное фото отображается в аватарке.</template>
        <template #content>
          <div class="flex flex-wrap gap-3 sm:gap-4 mb-4">
            <div
              v-for="p in photos"
              :key="p.id"
              class="relative w-20 h-20 sm:w-28 sm:h-28 rounded-lg overflow-hidden border bg-slate-100 flex flex-col shrink-0"
            >
              <img :src="p.url" alt="" class="w-full h-14 sm:h-20 object-cover" />
              <div class="flex-1 flex items-center justify-center gap-1 p-0.5 sm:p-1 bg-white min-h-0">
                <Badge v-if="p.is_main" value="Главное" severity="success" class="text-xs" />
                <Button
                  v-else
                  icon="pi pi-star"
                  size="small"
                  text
                  rounded
                  severity="secondary"
                  title="Сделать главным"
                  @click="setMain(p.id)"
                />
              </div>
            </div>
          </div>
          <FileUpload
            mode="basic"
            accept="image/jpeg,image/png,image/jpg"
            :max-file-size="2000000"
            :auto="false"
            :multiple="true"
            choose-label="Выбрать одно или несколько фото"
            @select="onSelect"
          />
          <p class="text-xs sm:text-sm text-slate-500 mt-1">Максимум 10 фото за раз, до 2 МБ каждое</p>
          <Message v-if="msg" :severity="msgOk ? 'success' : 'error'" class="mt-4" :closable="false">
            {{ msg }}
          </Message>
          <div class="flex flex-wrap gap-2 mt-6">
            <Button label="В чат" icon="pi pi-comments" @click="goChat" class="flex-1 sm:flex-initial min-w-0" />
            <Button label="Настройки" severity="secondary" outlined class="flex-1 sm:flex-initial min-w-0" @click="$router.push('/settings')" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import Message from 'primevue/message'
import Badge from 'primevue/badge'
import api from '@/api/axios'

const router = useRouter()
const photos = ref([])
const msg = ref('')
const msgOk = ref(false)

async function load() {
  const { data } = await api.get('/photos')
  photos.value = data.data || []
}

onMounted(load)

async function onSelect(event) {
  const files = event.files
  if (!files?.length) return
  msg.value = ''
  const form = new FormData()
  const limit = Math.min(files.length, 10)
  for (let i = 0; i < limit; i++) {
    form.append('photos[]', files[i])
  }
  try {
    await api.post('/photos', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    msgOk.value = true
    msg.value = files.length > 1 ? `Загружено фото: ${limit}` : 'Фото загружено'
    await load()
  } catch (e) {
    msgOk.value = false
    msg.value = e.response?.data?.message || 'Ошибка загрузки'
  }
}

async function setMain(photoId) {
  try {
    await api.post(`/photos/${photoId}/set-main`)
    msgOk.value = true
    msg.value = 'Главное фото обновлено'
    await load()
  } catch (e) {
    msgOk.value = false
    msg.value = e.response?.data?.message || 'Ошибка'
  }
}

function goChat() {
  router.push('/')
}
</script>
