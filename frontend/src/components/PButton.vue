<template>
  <button
    :class="buttonClasses"
    @click="$emit('click', $event)"
    :disabled="disabled"
  >
    <i v-if="icon" :class="iconClass"></i>
    <span v-if="$slots.default || label">
      <slot>{{ label }}</slot>
    </span>
  </button>
</template>

<script>
export default {
  name: 'PButton',
  props: {
    label: String,
    icon: String,
    severity: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'success', 'danger'].includes(value)
    },
    disabled: Boolean
  },
  computed: {
    buttonClasses() {
      const base = [
        'px-4 py-2 rounded-lg font-medium',
        'transition-all duration-200',
        'flex items-center gap-2',
        'disabled:opacity-50 disabled:cursor-not-allowed'
      ]
      
      const severityClasses = {
        primary: 'bg-blue-600 hover:bg-blue-700 text-white',
        secondary: 'bg-gray-200 hover:bg-gray-300 text-gray-800',
        success: 'bg-green-600 hover:bg-green-700 text-white',
        danger: 'bg-red-600 hover:bg-red-700 text-white'
      }
      
      return [...base, severityClasses[this.severity]].join(' ')
    },
    iconClass() {
      return `pi pi-${this.icon}`
    }
  }
}
</script>
