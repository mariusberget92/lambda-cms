<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  disabled:   { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])
function toggle() {
  if (!props.disabled) emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <span
    role="checkbox"
    :aria-checked="modelValue"
    :tabindex="disabled ? -1 : 0"
    class="shrink-0 inline-flex w-3.5 h-3.5 rounded border transition-all cursor-pointer items-center justify-center"
    :class="[
      modelValue
        ? 'bg-primary border-primary'
        : 'border-white/25 hover:border-white/50',
      disabled ? 'opacity-50 cursor-not-allowed' : '',
    ]"
    @click="toggle"
    @keydown.space.prevent="toggle"
    @keydown.enter.prevent="toggle"
  >
    <svg v-if="modelValue" class="w-2 h-2 text-white" viewBox="0 0 8 8" fill="none">
      <path d="M1 4L3 6L7 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </span>
</template>
