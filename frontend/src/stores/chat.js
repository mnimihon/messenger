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
      const chronological = [...list].reverse()
      messages.value = cursor ? [...chronological, ...messages.value] : chronological
      nextCursor.value = payload.next_cursor
      currentConversationId.value = conversationId
    } finally {
      loading.value = false
    }
  }

  function updateConversationPreview(conversationId, lastMessage) {
    const c = conversations.value.find((x) => x.id === conversationId)
    if (c) {
      c.last_message = lastMessage
        ? {
            message: lastMessage.message,
            created_at: lastMessage.created_at,
            is_read: lastMessage.is_read,
          }
        : null
    }
  }

  function incrementConversationUnread(conversationId) {
    const c = conversations.value.find((x) => x.id === conversationId)
    if (c) {
      c.unread_count = (c.unread_count || 0) + 1
    }
  }

  function clearConversationUnread(conversationId) {
    const c = conversations.value.find((x) => x.id === conversationId)
    if (c) {
      c.unread_count = 0
    }
  }

  async function sendMessage(text) {
    const { data } = await api.post('/messages', {
      conversation_id: currentConversationId.value,
      message: text,
    })
    const msg = data.message
    const exists = messages.value.some((m) => m.id === msg.id)
    if (!exists) {
      messages.value.push(msg)
    }
    updateConversationPreview(currentConversationId.value, msg)
    await loadConversations()
    clearConversationUnread(currentConversationId.value)
    return msg
  }

  async function deleteMessage(messageId) {
    await api.delete(`/messages/${messageId}`)
    messages.value = messages.value.filter((m) => m.id !== messageId)
    await loadConversations()
    const lastMessage = messages.value.length ? messages.value[messages.value.length - 1] : null
    updateConversationPreview(currentConversationId.value, lastMessage)
  }

  async function deleteConversation(conversationId) {
    await api.delete(`/conversations/${conversationId}`)
    conversations.value = conversations.value.filter((c) => c.id !== conversationId)
    if (currentConversationId.value === conversationId) {
      currentConversationId.value = null
      messages.value = []
      nextCursor.value = null
    }
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
    deleteMessage,
    deleteConversation,
    openOrCreateConversation,
    updateConversationPreview,
    incrementConversationUnread,
    clearConversationUnread,
  }
})
