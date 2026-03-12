import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/axios'

export const useChatStore = defineStore('chat', () => {
  const conversations = ref([])
  const currentConversationId = ref(null)
  const messages = ref([])
  const nextCursor = ref(null)
  const loading = ref(false)

  async function loadConversations() {
    loading.value = true
    try {
      const { data } = await api.get('/conversations')
      conversations.value = Array.isArray(data.data) ? data.data : []
    } finally {
      loading.value = false
    }
  }

  async function loadMessages(conversationId, cursor = 0) {
    loading.value = true
    try {
      const { data } = await api.get('/messages', {
        params: { conversation_id: conversationId, cursor: cursor || undefined },
      })
      const payload = data.data || {}
      const list = payload.messages || []
      messages.value = cursor ? [...list, ...messages.value] : [...list].reverse()
      nextCursor.value = payload.next_cursor
      currentConversationId.value = conversationId
    } finally {
      loading.value = false
    }
  }

  async function sendMessage(text) {
    const { data } = await api.post('/messages', {
      conversation_id: currentConversationId.value,
      message: text,
    })
    const msg = data.message
    messages.value.push(msg)
    await loadConversations()
    return msg
  }

  async function openOrCreateConversation(otherUserId) {
    const { data, status } = await api.post('/conversations', {
      other_user_id: otherUserId,
    })
    if (status === 201 && data.data) {
      await loadConversations()
      return data.data
    }
    if (data.success && data.message && !data.data) {
      await loadConversations()
      const found = conversations.value.find(
        (c) => c.other_user?.id === otherUserId
      )
      if (found) return found
    }
    await loadConversations()
    return conversations.value[0] || null
  }

  return {
    conversations,
    currentConversationId,
    messages,
    nextCursor,
    loading,
    loadConversations,
    loadMessages,
    sendMessage,
    openOrCreateConversation,
  }
})
