<template>
  <div class="flex h-screen max-h-screen overflow-hidden bg-slate-100">
    <aside
      class="flex flex-col w-full md:w-80 shrink-0 border-r bg-white transition-all duration-200"
      :class="{ 'hidden md:!flex': mobileView === 'chat' }"
    >
      <div class="p-3 border-b flex items-center justify-between shrink-0">
        <span class="font-semibold text-base sm:text-lg">Диалоги</span>
        <div class="flex gap-1">
          <Button icon="pi pi-image" text rounded severity="secondary" title="Фото" @click="$router.push('/photos')" />
          <Button icon="pi pi-cog" text rounded severity="secondary" title="Настройки" @click="$router.push('/settings')" />
          <Button icon="pi pi-sign-out" text rounded severity="secondary" title="Выход" @click="logout" />
        </div>
      </div>
      <div class="flex-1 overflow-y-auto">
        <div
          v-for="c in chat.conversations"
          :key="c.id"
          class="p-2.5 sm:p-3 border-b cursor-pointer hover:bg-slate-50 flex items-center gap-2 min-w-0"
          :class="{ 'bg-slate-100': chat.currentConversationId === c.id }"
          @click="openConversation(c)"
        >
          <div
            class="shrink-0 cursor-pointer rounded-full overflow-hidden ring-2 ring-transparent hover:ring-primary/30"
            @click.stop="openGallery(c.id)"
          >
            <Avatar
              :image="c.other_user?.avatar_url || undefined"
              :label="(c.other_user?.avatar_url ? '' : (c.other_user?.name || '?').charAt(0))"
              shape="circle"
              size="normal"
            />
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium truncate">{{ c.other_user?.name }}</div>
            <div class="text-sm text-slate-500 truncate">
              {{ c.last_message?.message || 'Нет сообщений' }}
            </div>
          </div>
          <Badge v-if="c.unread_count" :value="c.unread_count" />
        </div>
        <div v-if="!chat.conversations.length && !chat.loading" class="p-4 text-slate-500 text-sm">
          Нет диалогов. Создайте через API (other_user_id) или сидеры.
        </div>
      </div>
    </aside>
    <main
      class="flex-1 flex flex-col min-w-0 min-h-0 w-full"
      :class="{ 'hidden md:!flex': mobileView === 'list' }"
    >
      <template v-if="chat.currentConversationId">
        <div class="p-3 border-b bg-white flex items-center gap-2 shrink-0">
          <Button
            icon="pi pi-arrow-left"
            text
            rounded
            severity="secondary"
            class="md:hidden shrink-0"
            aria-label="Назад к диалогам"
            @click="mobileView = 'list'"
          />
          <div
            class="shrink-0 cursor-pointer rounded-full overflow-hidden ring-2 ring-transparent hover:ring-primary/30"
            @click="openGallery(chat.currentConversationId)"
          >
            <Avatar
              :image="activeOther?.avatar_url || undefined"
              :label="(activeOther?.avatar_url ? '' : activeOtherName.charAt(0))"
              shape="circle"
              size="normal"
            />
          </div>
          <span class="font-medium truncate flex-1 min-w-0">{{ activeOtherName }}</span>
        </div>
        <div ref="scrollRef" class="flex-1 overflow-y-auto p-3 sm:p-4 flex flex-col gap-2 min-h-0">
          <div
            v-for="m in chat.messages"
            :key="m.id"
            class="flex"
            :class="m.sender?.id === auth.user?.id ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[85%] sm:max-w-[70%] rounded-lg px-3 py-2 break-words"
              :class="m.sender?.id === auth.user?.id ? 'bg-primary text-primary-contrast' : 'bg-white border'"
            >
              <div class="text-sm">{{ m.message }}</div>
              <div class="text-xs opacity-70 mt-1">{{ formatTime(m.created_at) }}</div>
            </div>
          </div>
        </div>
        <div class="p-2 sm:p-3 border-t bg-white flex gap-2 shrink-0">
          <InputText
            v-model="draft"
            class="flex-1 min-w-0"
            placeholder="Сообщение..."
            @keyup.enter="send"
          />
          <Button icon="pi pi-send" @click="send" :disabled="!draft.trim()" class="shrink-0" />
        </div>
      </template>
      <div v-else class="flex-1 flex items-center justify-center text-slate-500 p-4 text-center text-sm sm:text-base">
        Выберите диалог
      </div>
    </main>

    <Dialog
      v-model:visible="galleryVisible"
      modal
      :header="galleryTitle"
      :style="{ width: '95vw', maxWidth: '600px' }"
      :dismissable-mask="true"
      @hide="galleryPhotos = []"
    >
      <div v-if="!galleryPhotos.length" class="py-8 text-center text-slate-500">
        У пользователя пока нет фото
      </div>
      <div v-else class="flex flex-col items-center gap-4">
        <div class="relative w-full flex items-center justify-center bg-slate-100 rounded-lg min-h-[300px]">
          <img
            :src="galleryPhotos[galleryIndex]?.url"
            alt=""
            class="max-w-full max-h-[70vh] object-contain"
          />
        </div>
        <div class="flex items-center gap-4">
          <Button
            icon="pi pi-chevron-left"
            rounded
            text
            :disabled="galleryIndex <= 0"
            @click="galleryIndex = Math.max(0, galleryIndex - 1)"
          />
          <span class="text-sm text-slate-600">{{ galleryIndex + 1 }} / {{ galleryPhotos.length }}</span>
          <Button
            icon="pi pi-chevron-right"
            rounded
            text
            :disabled="galleryIndex >= galleryPhotos.length - 1"
            @click="galleryIndex = Math.min(galleryPhotos.length - 1, galleryIndex + 1)"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import { useRouter } from 'vue-router'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const router = useRouter()

const chat = useChatStore()
const auth = useAuthStore()
const draft = ref('')
const scrollRef = ref(null)
const echoChannels = ref({})
const galleryVisible = ref(false)
const galleryPhotos = ref([])
const galleryIndex = ref(0)
const galleryUserName = ref('')
const mobileView = ref('list')

const activeOther = computed(() => {
  const c = chat.conversations.find((x) => x.id === chat.currentConversationId)
  return c?.other_user
})

const activeOtherName = computed(() => {
  return activeOther.value?.name || 'Чат'
})

const galleryTitle = computed(() => {
  return galleryUserName.value ? `Фото: ${galleryUserName.value}` : 'Галерея'
})

function formatTime(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

function handleNewMessage(msg) {
  const conversationId = msg.conversation_id
  const isCurrentConversation = conversationId === chat.currentConversationId
  const myId = auth.user?.id
  const senderId = msg.sender?.id
  const isFromOther =
    myId != null &&
    senderId != null &&
    Number(senderId) !== Number(myId)

  chat.updateConversationPreview(conversationId, msg)

  if (isCurrentConversation) {
    const exists = chat.messages.some((m) => m.id === msg.id)
    if (!exists) chat.messages.push(msg)
    nextTick(scrollBottom)
  } else if (isFromOther) {
    chat.incrementConversationUnread(conversationId)
  }
}

function setupConversationChannels() {
  const ids = (chat.conversations || []).map((c) => c.id)
  const current = Object.keys(echoChannels.value)

  current.forEach((id) => {
    if (!ids.includes(Number(id))) {
      window.Echo.leave(`conversation.${id}`)
      delete echoChannels.value[id]
    }
  })

  ids.forEach((id) => {
    if (echoChannels.value[id]) return
    const ch = window.Echo.private(`conversation.${id}`)
    ch.listen('.message.sent', (e) => handleNewMessage(e.message))
    echoChannels.value[id] = ch
  })
}

async function openConversation(c) {
  await chat.loadMessages(c.id)
  chat.clearConversationUnread(c.id)
  mobileView.value = 'chat'
  nextTick(scrollBottom)
}

async function openGallery(conversationId) {
  if (!conversationId) return
  const conv = chat.conversations.find((x) => x.id === conversationId)
  galleryUserName.value = conv?.other_user?.name || 'Пользователь'
  try {
    const { data } = await api.get(`/conversations/${conversationId}/other-user-photos`)
    galleryPhotos.value = data.data || []
    galleryIndex.value = 0
  } catch {
    galleryPhotos.value = []
  }
  galleryVisible.value = true
}

function scrollBottom() {
  const el = scrollRef.value
  if (el) el.scrollTop = el.scrollHeight
}

async function send() {
  const t = draft.value.trim()
  if (!t) return
  draft.value = ''
  await chat.sendMessage(t)
  nextTick(scrollBottom)
}

async function logout() {
  await auth.logout()
  router.push('/')
}

watch(
  () => chat.conversations,
  () => setupConversationChannels(),
  { immediate: true }
)

onMounted(async () => {
  await chat.loadConversations()
})

onUnmounted(() => {
  Object.keys(echoChannels.value).forEach((id) => {
    window.Echo.leave(`conversation.${id}`)
  })
  echoChannels.value = {}
})
</script>
