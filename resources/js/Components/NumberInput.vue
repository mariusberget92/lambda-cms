<script setup>
import { ref } from 'vue'
import { ChevronUp, ChevronDown } from '@lucide/vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: [Number, String], default: 0 },
  min:      { type: Number, default: undefined },
  max:      { type: Number, default: undefined },
  step:     { type: Number, default: 1 },
  disabled: { type: Boolean, default: false },
  error:    { type: Boolean, default: false },
  size:     { type: String,  default: 'md' },  // 'md' | 'sm'
})
const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)

function onInput(e) {
  emit('update:modelValue', e.target.value === '' ? '' : Number(e.target.value))
}

function stepUp() {
  if (!inputRef.value) return
  inputRef.value.stepUp()
  emit('update:modelValue', Number(inputRef.value.value))
}

function stepDown() {
  if (!inputRef.value) return
  inputRef.value.stepDown()
  emit('update:modelValue', Number(inputRef.value.value))
}
</script>

<template>
  <div class="relative inline-flex w-full">
    <input
      ref="inputRef"
      v-bind="$attrs"
      type="number"
      :value="modelValue"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :class="[size === 'sm' ? 'py-1 text-xs pl-2 pr-6' : 'py-1.5 text-sm pl-3 pr-7', error ? 'border-destructive' : 'border-border']"
      class="w-full rounded-md border bg-background focus:outline-none focus:ring-2 focus:ring-ring [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none disabled:opacity-50 disabled:cursor-not-allowed"
      @input="onInput"
    />
    <div class="absolute right-0 inset-y-0 flex flex-col border-l border-border rounded-r-md overflow-hidden">
      <button
        type="button"
        tabindex="-1"
        :disabled="disabled"
        class="flex-1 flex items-center justify-center px-1 text-muted-foreground hover:bg-accent/20 hover:text-foreground transition-colors border-b border-border disabled:opacity-40 disabled:cursor-not-allowed"
        @click="stepUp"
      >
        <ChevronUp class="w-3 h-3" />
      </button>
      <button
        type="button"
        tabindex="-1"
        :disabled="disabled"
        class="flex-1 flex items-center justify-center px-1 text-muted-foreground hover:bg-accent/20 hover:text-foreground transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
        @click="stepDown"
      >
        <ChevronDown class="w-3 h-3" />
      </button>
    </div>
  </div>
</template>
