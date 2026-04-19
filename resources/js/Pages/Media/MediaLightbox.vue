<template>
  <Teleport to="body">
    <Transition name="lightbox">
      <div
        v-if="modelValue !== null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
        @click.self="close"
      >
        <!-- Close button -->
        <button
          type="button"
          class="absolute top-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
          @click="close"
          aria-label="Close"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <!-- Prev arrow -->
        <button
          v-if="images.length > 1"
          type="button"
          class="absolute left-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
          @click="prev"
          aria-label="Previous image"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>

        <!-- Image -->
        <img
          :src="currentImage.url"
          :alt="currentImage.alt ?? currentImage.original_filename"
          class="max-h-[90vh] max-w-[90vw] object-contain select-none"
          draggable="false"
        />

        <!-- Next arrow -->
        <button
          v-if="images.length > 1"
          type="button"
          class="absolute right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
          @click="next"
          aria-label="Next image"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>

        <!-- Counter + filename -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-center pointer-events-none">
          <p class="text-white/70 text-xs">{{ currentImage.original_filename }}</p>
          <p v-if="images.length > 1" class="text-white/50 text-xs mt-0.5">{{ modelValue + 1 }} / {{ images.length }}</p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: Number, default: null },
  images:     { type: Array,  default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const currentImage = computed(() => props.images[props.modelValue] ?? {})

function close() { emit('update:modelValue', null) }
function prev()  { emit('update:modelValue', (props.modelValue - 1 + props.images.length) % props.images.length) }
function next()  { emit('update:modelValue', (props.modelValue + 1) % props.images.length) }

function onKeydown(e) {
  if (props.modelValue === null) return
  if (e.key === 'Escape')     close()
  if (e.key === 'ArrowLeft')  prev()
  if (e.key === 'ArrowRight') next()
}

onMounted(()   => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.lightbox-enter-active, .lightbox-leave-active { transition: opacity 0.2s; }
.lightbox-enter-from, .lightbox-leave-to { opacity: 0; }
</style>
