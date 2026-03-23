<template>
  <div class="relative">
    <input
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :class="inputClasses"
      :placeholder="placeholder"
      :disabled="disabled"
    />
    <i v-if="icon" :class="iconClass"></i>
  </div>
</template>

<script>
export default {
  name: 'PInputText',
  props: {
    modelValue: [String, Number],
    type: {
      type: String,
      default: 'text'
    },
    placeholder: String,
    icon: String,
    disabled: Boolean,
    invalid: Boolean
  },
  emits: ['update:modelValue'],
  computed: {
    inputClasses() {
      return [
        'w-full px-4 py-3 rounded-lg border',
        'focus:outline-none focus:ring-2 focus:ring-blue-500',
        'disabled:bg-gray-100 disabled:cursor-not-allowed',
        this.invalid ? 'border-red-500' : 'border-gray-300',
        this.icon ? 'pl-10' : ''
      ].join(' ')
    },
    iconClass() {
      return `pi pi-${this.icon} absolute left-3 top-3.5 text-gray-400`
    }
  }
}
</script>
