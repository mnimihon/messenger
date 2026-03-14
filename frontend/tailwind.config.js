/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
    "./node_modules/primevue/**/*.{vue,js,ts,jsx,tsx}"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2285e0',
          contrast: '#ffffff',
          600: '#1c6bb4',
          700: '#165187',
        },
      },
    },
  },
  plugins: [],
}