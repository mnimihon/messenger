import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/axios'

export const useChatStore = defineStore('chat', () => {
    const conversations = ref([])
    const currentConversation = ref(null)
    const messages = ref([])
    const loading = ref(false)

    // Загрузить диалоги
    async function loadConversations() {
        loading.value = true
        try {
            const response = await api.get('/conversations')
            conversations.value = response.data
        } finally {
            loading.value = false
        }
    }

    // Загрузить сообщения диалога
    async function loadMessages(conversationId) {
        loading.value = true
        try {
            const response = await api.get(`/messages?conversation_id=${conversationId}`)
            messages.value = response.data.messages.reverse() // новые внизу
            currentConversation.value = conversationId
        } finally {
            loading.value = false
        }
    }

    // Отправить сообщение
    async function sendMessage(message) {
        try {
            const response = await api.post('/messages', {
                conversation_id: currentConversation.value,
                message: message
            })

            // Добавляем в список
            messages.value.push(response.data.message)

            // Обновляем последнее сообщение в диалоге
            updateConversationLastMessage(response.data.message)

            return response.data.message
        } catch (error) {
            console.error('Ошибка отправки:', error)
            throw error
        }
    }

    // Создать/получить диалог
    async function getOrCreateConversation(otherUserId) {
        try {
            const response = await api.post('/conversations', {
                other_user_id: otherUserId
            })

            // Добавляем/обновляем в списке
            const existingIndex = conversations.value.findIndex(c => c.id === response.data.id)
            if (existingIndex >= 0) {
                conversations.value[existingIndex] = response.data
            } else {
                conversations.value.unshift(response.data)
            }

            return response.data
        } catch (error) {
            console.error('Ошибка создания диалога:', error)
            throw error
        }
    }

    // Обновить последнее сообщение в диалоге
    function updateConversationLastMessage(message) {
        const convIndex = conversations.value.findIndex(c => c.id === message.conversation_id)
        if (convIndex >= 0) {
            conversations.value[convIndex].last_message = {
                message: message.message,
                created_at: message.created_at,
                is_read: message.is_read
            }
            conversations.value[convIndex].updated_at = new Date().toISOString()

            // Перемещаем в начало
            const conversation = conversations.value.splice(convIndex, 1)[0]
            conversations.value.unshift(conversation)
        }
    }

    return {
        conversations,
        currentConversation,
        messages,
        loading,
        loadConversations,
        loadMessages,
        sendMessage,
        getOrCreateConversation
    }
})