<template>
  <div class="min-h-screen bg-slate-50 p-4">
    <div class="max-w-2xl mx-auto">
      <Card>
        <template #title>Фотографии профиля</template>
        <template #subtitle>Загрузите хотя бы одно фото — затем можно перейти в чат</template>
        <template #content>
          <div class="flex flex-wrap gap-4 mb-4">
            <div
              v-for="p in photos"
              :key="p.id"
              class="relative w-24 h-24 rounded-lg overflow-hidden border bg-slate-100"
            >
              <img :src="p.url" alt="" class="w-full h-full object-cover" />
              <Badge v-if="p.is_main" value="Главное" class="absolute bottom-0 left-0 text-xs" />
            </div>
          </div>
          <FileUpload
            mode="basic"
            accept="image/jpeg,image/png,image/jpg"
            :max-file-size="2000000"
            :auto="false"
            choose-label="Выбрать фото"
            @select="onSelect"
          />
          <Message v-if="msg" :severity="msgOk ? 'success' : 'error'" class="mt-4" :closable="false">
            {{ msg }}
          </Message>
          <div class="flex gap-2 mt-6">
            <Button label="В чат" icon="pi pi-comments" @click="goChat" />
            <Button label="Настройки" severity="secondary" outlined @click="$router.push('/settings')" />
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
  for (let i = 0; i < files.length; i++) {
    form.append('photos[]', files[i])
  }
  try {
    await api.post('/photos', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    msgOk.value = true
    msg.value = 'Фото загружены'
    await load()
  } catch (e) {
    msgOk.value = false
    msg.value = e.response?.data?.message || 'Ошибка загрузки'
  }
}

function goChat() {
  router.push('/')
}
</script>
