<!-- resources/js/Components/Blocks/SliderBlock.vue -->
<template>
  <div class="relative overflow-hidden rounded-lg select-none" :style="{ background: block.data?.bgColor || 'transparent' }">
    <div class="relative" :style="{ height: block.data?.height || '320px' }">
      <!-- Slides -->
      <template v-for="(slide, i) in slides" :key="i">
        <Transition name="slider-fade">
          <div v-show="i === current" class="absolute inset-0">
            <img v-if="slide.image" :src="slide.image" :alt="slide.alt || ''" class="w-full h-full object-cover" />
            <div v-if="!slide.image" class="w-full h-full bg-black/20 flex items-center justify-center">
              <span class="text-white/30 text-sm">No image</span>
            </div>
            <div v-if="block.data?.overlay" class="absolute inset-0" :style="{ background: block.data.overlayColor || 'rgba(0,0,0,0.4)' }" />
            <div v-if="slide.title || slide.description" class="absolute inset-0 flex items-center justify-center px-8">
              <div class="text-center max-w-xl">
                <h3 v-if="slide.title" class="text-2xl font-bold mb-2 drop-shadow" style="color: #fff">{{ slide.title }}</h3>
                <p v-if="slide.description" class="text-sm drop-shadow" style="color: rgba(255,255,255,0.85)">{{ slide.description }}</p>
              </div>
            </div>
          </div>
        </Transition>
      </template>

      <div v-if="slides.length === 0" class="absolute inset-0 flex items-center justify-center bg-black/10">
        <span class="text-muted-foreground text-sm">No slides added yet</span>
      </div>

      <!-- Arrows -->
      <template v-if="block.data?.showArrows !== false && slides.length > 1">
        <button
          type="button"
          class="absolute left-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-black/40 text-white flex items-center justify-center hover:bg-black/65 transition-colors text-xl leading-none"
          @click="prev"
        >‹</button>
        <button
          type="button"
          class="absolute right-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-black/40 text-white flex items-center justify-center hover:bg-black/65 transition-colors text-xl leading-none"
          @click="next"
        >›</button>
      </template>
    </div>

    <!-- Dots -->
    <div v-if="block.data?.showDots !== false && slides.length > 1" class="flex justify-center gap-2 py-3">
      <button
        v-for="(_, i) in slides"
        :key="i"
        type="button"
        class="w-2 h-2 rounded-full transition-all duration-200"
        :style="i === current
          ? { background: block.data?.dotColor || 'var(--primary)', transform: 'scale(1.35)' }
          : { background: 'rgba(100,100,100,0.35)' }"
        @click="current = i"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const slides = computed(() => props.block.data?.slides || [])
const current = ref(0)

function next() { current.value = (current.value + 1) % Math.max(slides.value.length, 1) }
function prev() { current.value = (current.value - 1 + Math.max(slides.value.length, 1)) % Math.max(slides.value.length, 1) }

let timer = null
onMounted(() => {
  if (props.block.data?.autoplay) {
    const delay = (props.block.data?.autoplayDelay || 4) * 1000
    timer = setInterval(next, delay)
  }
})
onUnmounted(() => { if (timer) clearInterval(timer) })
</script>

<style scoped>
.slider-fade-enter-active, .slider-fade-leave-active { transition: opacity 0.4s ease; }
.slider-fade-enter-from, .slider-fade-leave-to { opacity: 0; }
</style>
