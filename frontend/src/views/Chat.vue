блять ды все стало только хуже

и вот смотри судя по этому комменту /* Остальные стили оставь как были */ явно же что там были стили которые были нужны и которые нет в твоем файле




вот изначальный Chat.vue. сделай так чтобы туда выдавались и от туда отправлялись данные по нашему API


<template>
  <div class="chat-app" :class="{ 'dialog-open': isMobile && isDialogOpen }">
    <!-- Боковая панель с диалогами (видна всегда на десктопе, скрывается на мобиле при открытом диалоге) -->
    <aside class="chat-sidebar" :class="{ 'hidden': isMobile && isDialogOpen }">
      <!-- Шапка с кнопкой нового диалога -->
      <div class="sidebar-header">
        <h2 class="sidebar-title">Диалоги</h2>
        <button class="new-chat-btn" @click="createNewDialog" title="Новый диалог">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <!-- Список диалогов -->
      <div class="dialogs-list">
        <div
            v-for="dialog in dialogs"
            :key="dialog.id"
            class="dialog-item"
            :class="{ 'active': activeDialogId === dialog.id }"
            @click="openDialog(dialog.id)"
        >
          <div class="dialog-avatar">
            <div class="avatar-default">
              {{ dialog.name.charAt(0).toUpperCase() }}
            </div>
            <div class="dialog-status" :class="{ 'online': dialog.online }"></div>
          </div>

          <div class="dialog-info">
            <div class="dialog-header">
              <h3 class="dialog-name">{{ dialog.name }}</h3>
              <span class="dialog-time">{{ dialog.lastMessage.time }}</span>
            </div>

            <p class="dialog-preview">
              {{ getMessagePreview(dialog.lastMessage.text) }}
            </p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Основная область чата (скрыта на мобиле когда нет открытого диалога) -->
    <main class="chat-main" v-if="!isMobile || isDialogOpen">
      <!-- Шапка текущего диалога с кнопкой назад на мобиле -->
      <div class="chat-header">
        <button v-if="isMobile" class="back-btn" @click="closeDialog">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M15 10H5M5 10L8.33333 6.66667M5 10L8.33333 13.3333"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <div class="chat-header-info">
          <div class="header-avatar">
            <div class="avatar-default">
              {{ activeDialog?.name.charAt(0).toUpperCase() }}
            </div>
            <div class="header-status" :class="{ 'online': activeDialog?.online }"></div>
          </div>

          <div class="header-details">
            <h2 class="header-name">{{ activeDialog?.name }}</h2>
            <p class="header-status-text">
              {{ activeDialog?.online ? 'В сети' : 'Не в сети' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Сообщения -->
      <div class="messages-container" ref="messagesContainer">
        <div class="messages-wrapper">
          <!-- Сообщения -->
          <div
              v-for="message in activeDialogMessages"
              :key="message.id"
              class="message-wrapper"
              :class="{
              'outgoing': message.senderId === currentUser.id,
              'incoming': message.senderId !== currentUser.id
            }"
          >
            <div class="message-bubble">
              <p class="message-text">{{ message.text }}</p>
              <div class="message-meta">
                <span class="message-time">{{ message.time }}</span>
                <span v-if="message.senderId === currentUser.id" class="message-status">
                  <svg v-if="message.status === 'read'" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3 8L6 11L13 4M3 8L6 11L13 4" stroke="#2563EB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3 8L6 11L13 4" stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Поле ввода сообщения -->
      <div class="message-input-container">
        <!-- Поле ввода текста -->
        <input
            v-model="newMessage"
            type="text"
            placeholder="Введите сообщение..."
            class="message-input"
            @keyup.enter="sendMessage"
        />

        <!-- Кнопка отправки -->
        <button
            class="send-btn"
            @click="sendMessage"
            title="Отправить"
        >
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M3.33331 3.33331L16.6666 10L3.33331 16.6666V10V3.33331Z"
                  stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </main>

    <!-- Состояние без выбранного диалога (только для мобильных) -->
    <div v-if="isMobile && !isDialogOpen" class="mobile-empty">
      <div class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
          <path d="M8 10H8.01M12 10H12.01M16 10H16.01M9 16H5C3.89543 16 3 15.1046 3 14V6C3 4.89543 3.89543 4 5 4H19C20.1046 4 21 4.89543 21 6V14C21 15.1046 20.1046 16 19 16H14L9 21V16Z"
                stroke="#94A3B8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <h3>Выберите диалог</h3>
        <p>Нажмите на диалог, чтобы начать общение</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, onUnmounted } from 'vue'
import { useChatStore } from '@/stores/chat'
import { useAuthStore } from '@/stores/auth'

const chatStore = useChatStore()
const authStore = useAuthStore()

// Текущий пользователь с защитой от null
const currentUser = computed(() => {
  return authStore.user || { id: null, name: 'Загрузка...' }
})

// Адаптивность
const isMobile = ref(window.innerWidth <= 768)
const isDialogOpen = ref(false)

// Выбранный диалог
const activeDialogId = ref(null) // изменил с '1' на null
const newMessage = ref('')

// Используем данные из store вместо моковых
const dialogs = computed(() => {
  return chatStore.conversations.map(conv => ({
    id: conv.id,
    name: conv.other_user?.name || 'Пользователь',
    online: false, // TODO: добавить онлайн статус
    lastMessage: {
      text: conv.last_message?.message || 'Нет сообщений',
      time: formatTime(conv.last_message?.created_at) || ''
    }
  }))
})

const activeDialogMessages = computed(() => {
  return chatStore.messages.map(msg => ({
    id: msg.id,
    text: msg.message,
    time: formatTime(msg.created_at),
    senderId: msg.sender?.id,
    status: msg.is_read ? 'read' : 'sent'
  }))
})

// Refs
const messagesContainer = ref(null)

// Computed
const activeDialog = computed(() => {
  return dialogs.value.find(dialog => dialog.id === activeDialogId.value)
})

// Methods
async function openDialog(dialogId) {
  // Отписываемся от старого канала
  if (echoChannel) {
    echoChannel.stopListening('.message.sent')
  }

  activeDialogId.value = dialogId
  if (isMobile.value) {
    isDialogOpen.value = true
  }

  // Загружаем сообщения
  await chatStore.loadMessages(dialogId)
  scrollToBottom()

  // Подписываемся на новый канал
  echoChannel = window.Echo.private(`conversation.${dialogId}`)

  echoChannel.listen('.message.sent', (e) => {
    // Добавляем новое сообщение (только если от другого пользователя)
    if (e.message.sender.id !== currentUser.value.id) {
      chatStore.messages.push(e.message)
      scrollToBottom()

      // Обновляем последнее сообщение в диалоге
      chatStore.updateConversationLastMessage(e.message)
    }
  })
}

const closeDialog = () => {
  if (isMobile.value) {
    isDialogOpen.value = false
  }
}

const createNewDialog = async () => {
  // TODO: Реализовать выбор пользователя для нового диалога
  // Сейчас просто тест с пользователем ID: 2
  const otherUserId = 2
  try {
    const conversation = await chatStore.getOrCreateConversation(otherUserId)
    await openDialog(conversation.id)
  } catch (error) {
    console.error('Ошибка создания диалога:', error)
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || !activeDialogId.value) return

  try {
    await chatStore.sendMessage(newMessage.value)
    newMessage.value = ''
    scrollToBottom()
  } catch (error) {
    console.error('Ошибка отправки сообщения:', error)
  }
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const getMessagePreview = (text) => {
  const maxLength = 35
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

const handleResize = () => {
  isMobile.value = window.innerWidth <= 768
  if (!isMobile.value) {
    isDialogOpen.value = false
  }
}

let echoChannel = null
// Lifecycle
onMounted(async () => {


  window.Echo.private('conversation.7')
      .listen('.message.sent', (event) => {
        console.log('Новое сообщение:', event.message)
      })

  // Сначала ждем загрузки пользователя
  if (!authStore.user) {
    await authStore.fetchCurrentUser()
  }

  console.log('User loaded:', authStore.user?.id) // Проверь

  // Теперь инициализируем WebSocket
  await initWebSockets()
  await chatStore.loadConversations()

  if (chatStore.conversations.length > 0) {
    await openDialog(chatStore.conversations[0].id)
  }

  window.addEventListener('resize', handleResize)
  handleResize()
})

async function initWebSockets() {
  try {
    // Проверяем что Echo доступен
    if (!window.Echo) {
      console.log('Echo not available')
      return
    }

    // Подключаемся к приватному каналу пользователя
    window.Echo.channel(`user.${currentUser.value.id}`)
        .listen('.conversation.updated', (e) => {
          // Обновляем список диалогов
          chatStore.loadConversations()
        })

    console.log('WebSocket connected for user:', currentUser.value.id)
  } catch (error) {
    console.log('WebSocket init error:', error)
  }
}

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)

  if (window.Echo) {
    window.Echo.leaveAllChannels()
  }

  if (echoChannel) {
    echoChannel.stopListening('.message.sent')
  }
})
</script>

<style scoped>
.chat-app {
  display: flex;
  height: 100vh;
  background: #f8fafc;
  position: relative;
}

/* Сайдбар */
.chat-sidebar {
  width: 320px;
  background: white;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease;
}

/* Шапка с кнопкой нового диалога */
.sidebar-header {
  padding: 1.25rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sidebar-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.new-chat-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.new-chat-btn:hover {
  background: #2563eb;
  color: white;
}

/* Список диалогов */
.dialogs-list {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem;
}

.dialog-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  margin: 0.125rem 0;
}

.dialog-item:hover {
  background: #f8fafc;
}

.dialog-item.active {
  background: #eff6ff;
}

.dialog-avatar {
  position: relative;
  flex-shrink: 0;
}

.avatar-default {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #2563eb;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  font-weight: 600;
}

.dialog-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  background: #94a3b8;
  border: 2px solid white;
  border-radius: 50%;
}

.dialog-status.online {
  background: #10b981;
}

.dialog-info {
  flex: 1;
  min-width: 0;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.25rem;
}

.dialog-name {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dialog-time {
  font-size: 0.75rem;
  color: #94a3b8;
  white-space: nowrap;
}

.dialog-preview {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Основная область чата */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
  position: relative;
}

/* Кнопка назад на мобиле */
.back-btn {
  display: none;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  margin-right: 0.5rem;
}

/* Шапка чата */
.chat-header {
  background: white;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
}

.chat-header-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.header-avatar {
  position: relative;
}

.header-avatar .avatar-default {
  width: 40px;
  height: 40px;
  font-size: 1rem;
}

.header-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  background: #94a3b8;
  border: 2px solid white;
  border-radius: 50%;
}

.header-status.online {
  background: #10b981;
}

.header-details {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.header-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.header-status-text {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

/* Сообщения */
.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
}

.messages-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.message-wrapper {
  display: flex;
}

.message-wrapper.outgoing {
  justify-content: flex-end;
}

.message-wrapper.incoming {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 70%;
  padding: 0.625rem 0.875rem;
  border-radius: 14px;
  position: relative;
}

.message-wrapper.outgoing .message-bubble {
  background: #2563eb;
  border-bottom-right-radius: 4px;
}

.message-wrapper.incoming .message-bubble {
  background: white;
  border: 1px solid #e2e8f0;
  border-bottom-left-radius: 4px;
}

.message-text {
  margin: 0 0 0.375rem 0;
  font-size: 0.9375rem;
  line-height: 1.4;
}

.message-wrapper.outgoing .message-text {
  color: white;
}

.message-wrapper.incoming .message-text {
  color: #1e293b;
}

.message-meta {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.25rem;
}

.message-time {
  font-size: 0.75rem;
  opacity: 0.8;
}

.message-wrapper.outgoing .message-time {
  color: rgba(255, 255, 255, 0.9);
}

.message-wrapper.incoming .message-time {
  color: #94a3b8;
}

/* Поле ввода */
.message-input-container {
  background: white;
  padding: 0.75rem 1.25rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.file-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.file-btn:hover {
  background: #e2e8f0;
  color: #475569;
}

.message-input {
  flex: 1;
  height: 40px;
  padding: 0 1rem;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 20px;
  font-size: 0.9375rem;
  color: #1e293b;
  outline: none;
  transition: all 0.2s;
}

.message-input:focus {
  background: white;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.send-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #2563eb;
  border: none;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.send-btn:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

/* Состояние без выбранного диалога на мобиле */
.mobile-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  text-align: center;
  padding: 2rem;
}

.empty-state {
  color: #64748b;
  max-width: 300px;
}

.empty-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem;
  color: #1e293b;
}

.empty-state p {
  font-size: 0.9375rem;
  margin: 0;
  line-height: 1.5;
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .chat-app {
    background: #0f172a;
  }

  .chat-sidebar,
  .chat-header,
  .message-input-container {
    background: #1e293b;
    border-color: #334155;
  }

  .sidebar-title,
  .dialog-name,
  .header-name {
    color: #f1f5f9;
  }

  .dialog-preview,
  .header-status-text {
    color: #94a3b8;
  }

  .new-chat-btn {
    background: #334155;
    color: #cbd5e1;
  }

  .new-chat-btn:hover {
    background: #2563eb;
    color: white;
  }

  .dialog-item:hover {
    background: #334155;
  }

  .dialog-item.active {
    background: rgba(37, 99, 235, 0.1);
  }

  .avatar-default {
    background: #2563eb;
  }

  .file-btn {
    background: #334155;
    color: #cbd5e1;
  }

  .file-btn:hover {
    background: #475569;
  }

  .message-input {
    background: #0f172a;
    border-color: #475569;
    color: #f1f5f9;
  }

  .message-input:focus {
    background: #1e293b;
    border-color: #60a5fa;
  }

  .message-wrapper.incoming .message-bubble {
    background: #334155;
    border-color: #475569;
  }

  .message-wrapper.incoming .message-text {
    color: #f1f5f9;
  }

  .send-btn:disabled {
    background: #475569;
  }

  .mobile-empty {
    background: #0f172a;
  }

  .empty-state h3 {
    color: #f1f5f9;
  }
}

/* Мобильная адаптивность */
@media (max-width: 768px) {
  .chat-app {
    flex-direction: row;
    overflow: hidden;
  }

  .chat-sidebar {
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 10;
    transform: translateX(0);
    transition: transform 0.3s ease;
  }

  .chat-sidebar.hidden {
    transform: translateX(-100%);
  }

  .chat-main {
    width: 100%;
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    transform: translateX(100%);
    transition: transform 0.3s ease;
  }

  .chat-app.dialog-open .chat-main {
    transform: translateX(0);
  }

  .back-btn {
    display: flex;
  }

  .mobile-empty {
    display: flex;
  }

  .chat-app:not(.dialog-open) .chat-main {
    display: none;
  }
}

/* Планшетная адаптивность */
@media (min-width: 769px) and (max-width: 1024px) {
  .chat-sidebar {
    width: 280px;
  }

  .dialog-preview {
    max-width: 150px;
  }
}

/* Десктоп (всегда показываем оба блока) */
@media (min-width: 769px) {
  .back-btn {
    display: none;
  }

  .mobile-empty {
    display: none;
  }

  .chat-main {
    display: flex !important;
    transform: none !important;
    position: static;
  }
}
</style>