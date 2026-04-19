<!-- resources/js/Components/BlockEditor/ColorPicker.vue -->
<template>
  <div ref="containerRef" class="relative inline-block">
    <div class="flex items-center gap-2">
      <!-- Circle swatch trigger -->
      <button
        type="button"
        class="w-6 h-6 rounded-full flex-shrink-0 cursor-pointer shadow-sm ring-1 ring-black/20 hover:scale-110 transition-transform"
        :style="{ backgroundColor: displayColor }"
        @click.stop="toggle"
      />

      <!-- Hex value display -->
      <span
        v-if="showValue"
        class="text-xs text-muted-foreground font-mono flex-1 cursor-pointer"
        @click.stop="toggle"
      >{{ displayColor }}</span>

      <!-- Reset button -->
      <button
        v-if="modelValue && showReset"
        type="button"
        class="text-xs text-muted-foreground hover:text-foreground transition-colors"
        @click.stop="onReset"
      >Reset</button>
    </div>

    <!-- Dark picker dropdown -->
    <Transition
      enter-active-class="transition-all duration-150 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-100 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="open"
        class="absolute left-0 top-8 z-50 w-52 rounded-lg border border-[#4c566a] bg-[#2e3440] p-3 shadow-2xl"
      >
        <!-- Large native color picker -->
        <input
          type="color"
          :value="displayColor"
          class="mb-2 block h-20 w-full cursor-pointer rounded-md border-0 bg-transparent"
          @input="onNativeInput"
        />

        <!-- Hex text input -->
        <input
          type="text"
          :value="hexText"
          maxlength="7"
          placeholder="#000000"
          spellcheck="false"
          class="w-full rounded-md border border-[#4c566a] bg-[#3b4252] px-2 py-1.5 text-xs font-mono text-[#eceff4] placeholder-[#616e88] focus:outline-none focus:ring-1 focus:ring-[#88c0d0]"
          @input.stop
          @change="onHexChange"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: null },
  default:    { type: String, default: '#ffffff' },
  showValue:  { type: Boolean, default: true },
  showReset:  { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const containerRef = ref(null)
const open         = ref(false)
const hexText      = ref('')

const displayColor = computed(() => props.modelValue ?? props.default)

watch(displayColor, val => { hexText.value = val }, { immediate: true })

function toggle() {
  open.value = !open.value
  if (open.value) hexText.value = displayColor.value
}

function onNativeInput(e) {
  const val = e.target.value
  hexText.value = val
  emit('update:modelValue', val)
}

function onHexChange(e) {
  const val = e.target.value.trim()
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    emit('update:modelValue', val)
  }
}

function onReset() {
  open.value = false
  emit('update:modelValue', null)
}

function handleClickOutside(e) {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside, true))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside, true))
</script>
