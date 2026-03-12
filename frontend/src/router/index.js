import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('@/views/Login.vue'), meta: { guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/Register.vue'), meta: { guest: true } },
  { path: '/verify-email', name: 'verify-email', component: () => import('@/views/VerifyEmail.vue'), meta: { guest: true } },
  { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/ForgotPassword.vue'), meta: { guest: true } },
  { path: '/reset-password', name: 'reset-password', component: () => import('@/views/ResetPassword.vue'), meta: { guest: true } },
  { path: '/photos', name: 'photos', component: () => import('@/views/Photos.vue'), meta: { requiresAuth: true } },
  { path: '/settings', name: 'settings', component: () => import('@/views/Settings.vue'), meta: { requiresAuth: true } },
  { path: '/', name: 'chat', component: () => import('@/views/Chat.vue'), meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('access_token')
  const isAuthed = !!token

  if (to.meta.requiresAuth && !isAuthed) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }
  if (to.meta.guest && isAuthed && to.name !== 'verify-email') {
    next('/')
    return
  }
  next()
})

export default router
