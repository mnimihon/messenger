<template>
  <div class="min-h-screen bg-slate-50 p-3 sm:p-4 md:p-6">
    <div class="max-w-2xl mx-auto w-full">
      <Card>
        <template #title>Фотографии профиля</template>
        <template #subtitle>Загрузите фотографии (не более 10)</template>
        <template #content>
          <div class="flex flex-wrap gap-3 sm:gap-4 mb-4">
            <div
              v-for="p in photos"
              :key="p.id"
              class="relative w-20 h-20 sm:w-28 sm:h-28 rounded-lg overflow-hidden border bg-slate-100 flex flex-col shrink-0"
            >
              <img :src="p.url" alt="" class="w-full h-14 sm:h-20 object-cover" />
              <div class="flex-1 flex items-center justify-center gap-1 p-0.5 sm:p-1 bg-white min-h-0">
                <Button
                  :icon="p.is_main ? 'pi pi-star-fill' : 'pi pi-star'"
                  size="small"
                  text
                  rounded
                  :class="p.is_main ? 'text-primary' : 'text-slate-400 hover:text-primary'"
                  :title="p.is_main ? 'Главное фото' : 'Сделать главным'"
                  @click="setMain(p.id)"
                />
                <Button
                  icon="pi pi-times"
                  size="small"
                  text
                  rounded
                  class="text-red-500 hover:text-red-600"
                  title="Удалить"
                  @click="removePhoto(p.id)"
                />
              </div>
            </div>
          </div>
          <div class="file-upload-wrap">
            <FileUpload
              mode="basic"
              accept="image/jpeg,image/png,image/jpg"
              :max-file-size="2000000"
              :auto="false"
              :multiple="true"
              choose-label="Выбрать одно или несколько фото"
              @select="onSelect"
            />
          </div>
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
  photos.value = photos.value.map((p) => ({
    ...p,
    is_main: p.id === photoId,
  }))
  try {
    await api.post(`/photos/${photoId}/set-main`)
    msgOk.value = true
    msg.value = 'Главное фото обновлено'
    await load()
  } catch (e) {
    msgOk.value = false
    msg.value = e.response?.data?.message || 'Ошибка'
    await load()
  }
}

async function removePhoto(photoId) {
  try {
    await api.delete(`/photos/${photoId}`)
    msgOk.value = true
    msg.value = 'Фото удалено'
    await load()
  } catch (e) {
    msgOk.value = false
    msg.value = e.response?.data?.message || 'Ошибка удаления'
  }
}

function goChat() {
  router.push('/')
}
</script>

<style scoped>
/* Скрыть только надпись "No file chosen", кнопка остаётся */
.file-upload-wrap :deep(.p-fileupload-content) {
  display: none;
}
</style>
