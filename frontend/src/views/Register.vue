<template>
  <div class="auth-page">
    <main class="auth-container">
      <div class="auth-card">

        <!-- Форма -->
        <form class="auth-form" @submit.prevent="handleRegister">
          <!-- Имя пользователя -->
          <div class="input-group">
            <div class="input-wrapper">
              <input
                  id="username"
                  v-model="username"
                  type="text"
                  required
                  placeholder="Имя пользователя"
                  class="text-input"
                  @focus="focusedField = 'username'"
                  @blur="focusedField = null"
              />
              <div class="input-border"></div>
            </div>
          </div>

          <!-- Email -->
          <div class="input-group">
            <div class="input-wrapper">
              <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  placeholder="Электронная почта"
                  class="text-input"
                  @focus="focusedField = 'email'"
                  @blur="focusedField = null"
              />
              <div class="input-border"></div>
            </div>
          </div>

          <!-- Пароль -->
          <div class="input-group">
            <div class="input-wrapper">
              <input
                  id="password"
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Пароль"
                  class="text-input"
                  @focus="focusedField = 'password'"
                  @blur="focusedField = null"
              />
              <div class="input-border"></div>
              <button
                  type="button"
                  class="password-toggle"
                  @click="togglePassword"
                  @mousedown.prevent
              >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path v-if="showPassword" d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                        fill="#64748B"/>
                  <path v-if="showPassword" d="M10 15C13.3333 15 16.1111 12.7778 17.5 10C16.1111 7.22222 13.3333 5 10 5C6.66667 5 3.88889 7.22222 2.5 10C3.88889 12.7778 6.66667 15 10 15ZM10 6.66667C12.7778 6.66667 15 8.88889 15 10C15 11.1111 12.7778 13.3333 10 13.3333C7.22222 13.3333 5 11.1111 5 10C5 8.88889 7.22222 6.66667 10 6.66667Z"
                        fill="#64748B"/>
                  <path v-if="!showPassword" d="M2.5 2.5L17.5 17.5M8.33333 8.33333C7.8039 8.86377 7.5 9.59564 7.5 10.4167C7.5 12.0896 8.91038 13.5 10.5833 13.5C11.4044 13.5 12.1362 13.1961 12.6667 12.6667M15 12.0833C15.555 11.2772 16 10.325 16 9.58333C16 6.5 13.5 4 10.4167 4C9.675 4 8.72278 4.445 7.91667 5"
                        stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Подтверждение пароля -->
          <div class="input-group">
            <div class="input-wrapper">
              <input
                  id="confirmPassword"
                  v-model="confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  required
                  placeholder="Подтвердите пароль"
                  class="text-input"
                  @focus="focusedField = 'confirmPassword'"
                  @blur="focusedField = null"
              />
              <div class="input-border"></div>
              <button
                  type="button"
                  class="password-toggle"
                  @click="toggleConfirmPassword"
                  @mousedown.prevent
              >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path v-if="showConfirmPassword" d="M10 12.5C11.3807 12.5 12.5 11.3807 12.5 10C12.5 8.61929 11.3807 7.5 10 7.5C8.61929 7.5 7.5 8.61929 7.5 10C7.5 11.3807 8.61929 12.5 10 12.5Z"
                        fill="#64748B"/>
                  <path v-if="showConfirmPassword" d="M10 15C13.3333 15 16.1111 12.7778 17.5 10C16.1111 7.22222 13.3333 5 10 5C6.66667 5 3.88889 7.22222 2.5 10C3.88889 12.7778 6.66667 15 10 15ZM10 6.66667C12.7778 6.66667 15 8.88889 15 10C15 11.1111 12.7778 13.3333 10 13.3333C7.22222 13.3333 5 11.1111 5 10C5 8.88889 7.22222 6.66667 10 6.66667Z"
                        fill="#64748B"/>
                  <path v-if="!showConfirmPassword" d="M2.5 2.5L17.5 17.5M8.33333 8.33333C7.8039 8.86377 7.5 9.59564 7.5 10.4167C7.5 12.0896 8.91038 13.5 10.5833 13.5C11.4044 13.5 12.1362 13.1961 12.6667 12.6667M15 12.0833C15.555 11.2772 16 10.325 16 9.58333C16 6.5 13.5 4 10.4167 4C9.675 4 8.72278 4.445 7.91667 5"
                        stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Кнопка регистрации -->
          <button
              type="submit"
              class="submit-button"
              :disabled="isLoading || !passwordsMatch"
          >
            <span class="button-text">Зарегистрироваться</span>
          </button>

          <!-- Сообщение о несоответствии паролей -->
          <div v-if="!passwordsMatch && confirmPassword" class="error-message">
            Пароли не совпадают
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import api from '@/api/axios'

const router = useRouter()
const username = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)
const focusedField = ref(null)

const passwordsMatch = computed(() => {
  return password.value === confirmPassword.value
})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

const handleRegister = async () => {
  isLoading.value = true

  try {
    const response = await api.post('/register', {
      name: username.value,
      email: email.value,
      password: password.value,
      password_confirmation: confirmPassword.value
    })

    router.push('/login')
  } catch (error) {
    console.error('Ошибка:', error.response?.data || error.message)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background-color: #f8fafc;
}

.auth-container {
  width: 100%;
  max-width: 400px;
}

.auth-card {
  background: white;
  border-radius: 16px;
  padding: 3rem 2rem;
  box-shadow:
      0 10px 25px -5px rgba(0, 0, 0, 0.05),
      0 8px 10px -6px rgba(0, 0, 0, 0.01);
  border: 1px solid rgba(226, 232, 240, 0.6);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.input-wrapper {
  position: relative;
}

.text-input {
  width: 100%;
  height: 52px;
  padding: 0 1rem;
  font-size: 1rem;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  transition: all 0.2s ease;
  color: #1e293b;
  outline: none;
}

.text-input::placeholder {
  color: #94a3b8;
}

.text-input:focus {
  background: white;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.text-input:focus ~ .input-border {
  transform: scaleX(1);
}

.input-border {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: #2563eb;
  transform: scaleX(0);
  transition: transform 0.3s ease;
  border-radius: 0 0 2px 2px;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.password-toggle:hover {
  background-color: #f1f5f9;
}

.submit-button {
  width: 100%;
  height: 52px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 0.5rem;
}

.submit-button:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.submit-button:active:not(:disabled) {
  transform: translateY(0) scale(0.98);
}

.submit-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  color: #dc2626;
  font-size: 0.875rem;
  text-align: center;
  margin-top: 0.5rem;
}

/* Dark theme */
@media (prefers-color-scheme: dark) {
  .auth-page {
    background-color: #0f172a;
  }

  .auth-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow:
        0 10px 25px -5px rgba(0, 0, 0, 0.3),
        0 8px 10px -6px rgba(0, 0, 0, 0.2);
  }

  .text-input {
    background: #0f172a;
    border-color: #475569;
    color: #f1f5f9;
  }

  .text-input:focus {
    background: #1e293b;
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
  }

  .password-toggle:hover {
    background-color: #334155;
  }

  .submit-button {
    background: #2563eb;
  }

  .submit-button:hover:not(:disabled) {
    background: #1d4ed8;
  }

  .error-message {
    color: #f87171;
  }
}

/* Mobile */
@media (max-width: 640px) {
  .auth-card {
    padding: 2.5rem 1.5rem;
    border-radius: 12px;
  }

  .auth-form {
    gap: 1rem;
  }

  .text-input {
    height: 48px;
  }

  .submit-button {
    height: 48px;
  }
}
</style>