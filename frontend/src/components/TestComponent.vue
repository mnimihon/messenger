<template>
  <div class="p-8">
    <h1 class="text-3xl font-bold text-blue-600 mb-6">Tailwind + PrimeVue 4 работает!</h1>
    
    <!-- Tailwind кнопки -->
    <div class="space-x-4 mb-8">
      <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Tailwind Button
      </button>
      <button class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
        Secondary
      </button>
    </div>
    
    <!-- PrimeIcons -->
    <div class="flex items-center space-x-4 text-2xl mb-8">
      <i class="pi pi-check text-green-600"></i>
      <i class="pi pi-user text-blue-600"></i>
      <i class="pi pi-send text-purple-600"></i>
    </div>
    
    <!-- Tailwind формы -->
    <div class="max-w-md space-y-4">
      <input type="text" placeholder="Введите текст" 
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      
      <input type="password" placeholder="Пароль"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
  </div>
</template>

<script>
export default {
  name: 'TestComponent'
}
</script>
