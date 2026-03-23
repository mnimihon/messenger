<template>
  <div id="app" class="min-h-screen overflow-x-hidden bg-slate-50" :class="{ 'app-layout': authStore.isAuthenticated }">
    <template v-if="authStore.isAuthenticated">
      <header class="shrink-0 h-[var(--app-header-h,3.5rem)] bg-white border-b px-3 sm:px-4 py-2 flex items-center justify-between">
        <router-link to="/" class="flex items-center gap-2">
          <span class="font-semibold text-2xl tracking-tight uppercase cursor-pointer">messenger</span>
        </router-link>
        <div class="flex items-center gap-1 sm:gap-4">
          <Button
            icon="pi pi-image"
            text
            class="!text-4xl"
            rounded
            severity="secondary"
            title="Фото"
            @click="router.push('/photos')"
          />
          <Button
            icon="pi pi-cog"
            text
            class="!text-4xl"
            rounded
            severity="secondary"
            title="Настройки"
            @click="router.push('/settings')"
          />
          <Button
            icon="pi pi-sign-out"
            text
            class="!text-4xl"
            rounded
            severity="secondary"
            title="Выход"
            @click="logout"
          />
        </div>
      </header>
      <main class="app-main">
        <div
          class="app-main__inner"
          :class="route.path === '/' ? 'app-main__inner--no-scroll' : 'app-main__inner--scroll'"
        >
          <router-view v-slot="{ Component }">
            <template v-if="route.path === '/'">
              <component :is="Component" />
            </template>
            <div v-else class="w-full min-w-0">
              <component :is="Component" />
            </div>
          </router-view>
        </div>
      </main>
    </template>
    <template v-else>
      <router-view />
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

onMounted(() => {
  authStore.init()
})

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>