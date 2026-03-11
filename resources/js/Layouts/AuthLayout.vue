<script setup>
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme.js'

const { isDark, toggleTheme } = useTheme()
</script>

<template>
  <div class="min-h-screen bg-background flex items-center justify-center">
    <!-- Dark mode toggle — fixed top-right -->
    <button
      @click="toggleTheme"
      class="fixed top-4 right-4 inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
      <Sun v-if="isDark" class="w-4 h-4" />
      <Moon v-else class="w-4 h-4" />
    </button>

    <div class="w-full max-w-sm">
      <slot />
    </div>
  </div>
</template>
