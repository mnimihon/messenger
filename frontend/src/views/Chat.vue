<template>
  <div class="flex h-screen bg-slate-100">
    <aside class="w-80 border-r bg-white flex flex-col shrink-0">
      <div class="p-3 border-b flex items-center justify-between">
        <span class="font-semibold">Диалоги</span>
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
          class="p-3 border-b cursor-pointer hover:bg-slate-50 flex items-center gap-2"
          :class="{ 'bg-slate-100': chat.currentConversationId === c.id }"
          @click="openConversation(c)"
        >
          <Avatar :label="(c.other_user?.name || '?').charAt(0)" shape="circle" />
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
    <main class="flex-1 flex flex-col min-w-0">
      <template v-if="chat.currentConversationId">
        <div class="p-3 border-b bg-white flex items-center gap-2">
          <Avatar :label="activeOtherName.charAt(0)" shape="circle" />
          <span class="font-medium">{{ activeOtherName }}</span>
        </div>
        <div ref="scrollRef" class="flex-1 overflow-y-auto p-4 flex flex-col gap-2">
          <div
            v-for="m in chat.messages"
            :key="m.id"
            class="flex"
            :class="m.sender?.id === auth.user?.id ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[70%] rounded-lg px-3 py-2"
              :class="m.sender?.id === auth.user?.id ? 'bg-primary text-primary-contrast' : 'bg-white border'"
            >
              <div class="text-sm">{{ m.message }}</div>
              <div class="text-xs opacity-70 mt-1">{{ formatTime(m.created_at) }}</div>
            </div>
          </div>
        </div>
        <div class="p-3 border-t bg-white flex gap-2">
          <InputText
            v-model="draft"
            class="flex-1"
            placeholder="Сообщение..."
            @keyup.enter="send"
          />
          <Button icon="pi pi-send" @click="send" :disabled="!draft.trim()" />
        </div>
      </template>
      <div v-else class="flex-1 flex items-center justify-center text-slate-500">
        Выберите диалог
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'

const chat = useChatStore()
const auth = useAuthStore()
const draft = ref('')
const scrollRef = ref(null)
let echoChannel = null

const activeOtherName = computed(() => {
  const c = chat.conversations.find((x) => x.id === chat.currentConversationId)
  return c?.other_user?.name || 'Чат'
})

function formatTime(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

async function openConversation(c) {
  if (echoChannel) {
    window.Echo.leave(`private-conversation.${chat.currentConversationId}`)
    echoChannel = null
  }
  await chat.loadMessages(c.id)
  echoChannel = window.Echo.private(`conversation.${c.id}`)
  echoChannel.listen('.message.sent', (e) => {
    const msg = e.message
    if (msg.conversation_id !== c.id) return
    const exists = chat.messages.some((m) => m.id === msg.id)
    if (!exists) chat.messages.push(msg)
    nextTick(scrollBottom)
  })
  nextTick(scrollBottom)
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
  window.location.href = '/login'
}

onMounted(async () => {
  await chat.loadConversations()
})

onUnmounted(() => {
  if (chat.currentConversationId) {
    window.Echo.leave(`private-conversation.${chat.currentConversationId}`)
  }
})
</script>
