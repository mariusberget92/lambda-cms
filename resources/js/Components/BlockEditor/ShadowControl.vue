<!-- resources/js/Components/BlockEditor/ShadowControl.vue -->
<template>
  <div class="space-y-2">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Shadow</label>

    <!-- Preset pills -->
    <div class="flex gap-1 flex-wrap">
      <button
        v-for="preset in PRESETS"
        :key="preset.label"
        type="button"
        class="px-2 py-1 text-xs rounded border transition-colors"
        :class="isActive(preset) ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
        @click="select(preset)"
      >{{ preset.label }}</button>
    </div>

    <!-- Custom value input -->
    <div v-if="isCustom">
      <input
        type="text"
        :value="modelValue"
        placeholder="0px 4px 6px rgba(0,0,0,0.1)"
        class="w-full rounded border border-border bg-background px-2 py-1 text-xs"
        @input="emit('update:modelValue', $event.target.value)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const PRESETS = [
  { label: 'None',   value: '' },
  { label: 'SM',     value: '0 1px 2px rgba(0,0,0,0.05)' },
  { label: 'MD',     value: '0 4px 6px rgba(0,0,0,0.1)' },
  { label: 'LG',     value: '0 10px 15px rgba(0,0,0,0.15)' },
  { label: 'XL',     value: '0 20px 25px rgba(0,0,0,0.2)' },
  { label: 'Custom', value: '__custom__' },
]

const props = defineProps({
  modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const isCustom = computed(() => {
  if (!props.modelValue) return false
  return !PRESETS.slice(0, -1).some(p => p.value === props.modelValue)
})

function isActive(preset) {
  if (preset.value === '__custom__') return isCustom.value
  return props.modelValue === preset.value
}

function select(preset) {
  if (preset.value === '__custom__') {
    // Just shows the custom input — don't clear the existing value
    return
  }
  emit('update:modelValue', preset.value)
}
</script>
