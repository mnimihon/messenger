import { createApp } from 'vue'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import { usePreset, updatePrimaryPalette } from '@primeuix/themes'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'

import 'primeicons/primeicons.css'
import './assets/main.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
const primaryPalette = {
  50: '#e8f2fd',
  100: '#d1e5fb',
  200: '#a3cbf7',
  300: '#75b1f3',
  400: '#4797ef',
  500: '#2285e0',
  600: '#1c6bb4',
  700: '#165187',
  800: '#10385b',
  900: '#0a202e',
  950: '#051017'
}

app.use(PrimeVue, {
  theme: {
    preset: usePreset(Aura, updatePrimaryPalette(primaryPalette))
  }
})

app.mount('#app')
import './echo'