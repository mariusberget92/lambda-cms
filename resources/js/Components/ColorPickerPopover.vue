<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: null },
  label:      { type: String, default: 'Color' },
})
const emit = defineEmits(['update:modelValue'])

const NORD_SWATCHES = [
  '#5e81ac', '#81a1c1', '#88c0d0', '#8fbcbb',
  '#a3be8c', '#ebcb8b', '#d08770', '#bf616a',
  '#b48ead', '#4c566a',
]

const open        = ref(false)
const showCustom  = ref(false)

const selected = computed(() => props.modelValue)

function pick(hex) {
  emit('update:modelValue', hex)
  showCustom.value = false
}

function clear() {
  emit('update:modelValue', null)
  showCustom.value = false
}

function onCustomInput(e) {
  emit('update:modelValue', e.target.value)
}

function toggle() {
  open.value = !open.value
  if (!open.value) showCustom.value = false
}

function onKeydown(e) {
  if (e.key === 'Escape' && open.value) {
    open.value = false
    showCustom.value = false
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div class="relative inline-block">
    <!-- Trigger -->
    <button
      type="button"
      @click="toggle"
      :aria-expanded="open"
      class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-accent transition-colors focus:outline-none focus:ring-2 focus:ring-ring"
    >
      <span
        class="w-5 h-5 rounded-full border border-border transition-colors shrink-0"
        :style="selected ? { backgroundColor: selected } : {}"
        :class="!selected ? 'bg-muted' : ''"
      />
      <span class="text-muted-foreground">{{ selected ?? 'None' }}</span>
      <svg class="w-3.5 h-3.5 text-muted-foreground ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Popover panel -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute left-0 top-full mt-1.5 z-50 w-56 rounded-xl border bg-card shadow-xl p-3 space-y-3"
      >
        <!-- Nord swatches grid -->
        <div>
          <p class="text-xs text-muted-foreground mb-2 font-medium">Nord palette</p>
          <div class="grid grid-cols-5 gap-2">
            <button
              v-for="hex in NORD_SWATCHES"
              :key="hex"
              type="button"
              @click="pick(hex)"
              :title="hex"
              class="relative w-8 h-8 rounded-full border-2 transition-all hover:scale-110 focus:outline-none"
              :style="{ backgroundColor: hex }"
              :class="selected === hex ? 'border-foreground shadow-md' : 'border-transparent'"
            >
              <svg
                v-if="selected === hex"
                class="w-3.5 h-3.5 absolute inset-0 m-auto text-white drop-shadow"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Custom color -->
        <div class="border-t pt-2">
          <button
            type="button"
            @click="showCustom = !showCustom"
            class="text-xs text-primary hover:underline"
          >
            {{ showCustom ? 'Hide' : 'Custom color…' }}
          </button>
          <div v-if="showCustom" class="flex items-center gap-2 mt-2">
            <input
              type="color"
              :value="selected ?? '#5e81ac'"
              @input="onCustomInput"
              class="h-8 w-10 cursor-pointer rounded border border-border"
            />
            <span class="text-xs text-muted-foreground font-mono">{{ selected ?? '—' }}</span>
          </div>
        </div>

        <!-- Footer actions -->
        <div class="border-t pt-2 flex items-center justify-between">
          <button
            v-if="selected"
            type="button"
            @click="clear"
            class="text-xs text-muted-foreground hover:text-destructive transition-colors"
          >
            Clear
          </button>
          <button
            type="button"
            @click="open = false"
            class="ml-auto text-xs text-muted-foreground hover:text-foreground transition-colors"
          >
            Done
          </button>
        </div>
      </div>
    </Transition>

    <!-- Click-outside overlay -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
  </div>
</template>
