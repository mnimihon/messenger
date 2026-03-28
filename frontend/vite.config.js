import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 3000,
    host: '0.0.0.0',
    hmr: false,
    // Docker + bind mount (особенно Windows/WSL): нативный inotify часто ломается,
    // Vite перестаёт отвечать на :3000 → вкладка уходит в chrome-error. Polling стабильнее.
    watch: process.env.CHOKIDAR_USEPOLLING === 'true'
      ? { usePolling: true, interval: 1000 }
      : undefined,
    proxy: {
      '/api': {
        target: 'http://localhost:8001',
        changeOrigin: true,
        secure: false,
      },
    },
  },
})
