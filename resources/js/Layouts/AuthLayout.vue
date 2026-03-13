<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme.js'
import { useParticleCanvas } from '@/composables/useParticleCanvas.js'

const { isDark, toggleTheme } = useTheme()

const canvasRef = ref(null)
const { init, cleanup } = useParticleCanvas(canvasRef)

onMounted(init)
onUnmounted(cleanup)
</script>

<template>
  <!-- Full-viewport backdrop -->
  <div class="min-h-screen bg-background flex items-center justify-center p-8">

    <!-- 80% centered box with rounded corners -->
    <div class="w-4/5 flex rounded-2xl overflow-hidden shadow-2xl min-h-[480px]">

      <!-- Left panel: particle canvas (desktop only) -->
      <div class="hidden md:flex w-1/2 bg-[oklch(14%_0.01_260)]" aria-hidden="true">
        <canvas ref="canvasRef" class="w-full h-full" />
      </div>

      <!-- Right panel: auth form -->
      <div class="w-full md:w-1/2 bg-background flex flex-col items-center justify-center p-8">
        <div class="w-full max-w-sm">
          <slot />
        </div>
      </div>

    </div>

    <!-- Dark mode toggle (sits above both panels) -->
    <button
      type="button"
      @click="toggleTheme"
      class="fixed top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
      <Sun v-if="isDark" class="w-4 h-4" />
      <Moon v-else class="w-4 h-4" />
    </button>

  </div>
</template>
