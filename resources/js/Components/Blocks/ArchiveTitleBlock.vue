<script setup>
import { inject, computed } from 'vue'

defineProps({ block: Object })

const archive = inject('archiveContext', null)

const AURORA = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#f97316','#ef4444','#a855f7']

function nameToColor(name) {
  if (!name) return AURORA[0]
  return AURORA[[...name].reduce((s, c) => s + c.charCodeAt(0), 0) % AURORA.length]
}

function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1,3), 16)
  const g = parseInt(hex.slice(3,5), 16)
  const b = parseInt(hex.slice(5,7), 16)
  return `rgba(${r},${g},${b},${alpha})`
}

const accentColor = computed(() => nameToColor(archive?.name))
</script>

<template>
  <div
    v-if="archive"
    class="relative overflow-hidden rounded-2xl p-7 mb-2"
    :style="{
      background: `linear-gradient(135deg, ${accentColor} 0%, ${hexToRgba(accentColor, 0.65)} 100%)`,
      boxShadow: `0 6px 24px ${hexToRgba(accentColor, 0.35)}`,
    }"
  >
    <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-white/15" />
    <div class="absolute right-20 -bottom-12 w-24 h-24 rounded-full bg-white/10" />
    <div class="absolute -left-6 -bottom-8 w-20 h-20 rounded-full bg-white/10" />

    <p class="relative text-[11px] font-bold uppercase tracking-[0.14em] text-white/60 mb-2">
      {{ archive.type === 'category' ? 'Category' : 'Tag' }}
    </p>
    <h1 class="relative font-editorial text-3xl font-bold text-white leading-tight mb-1">
      {{ archive.name }}
    </h1>
    <p class="relative text-sm text-white/70">
      {{ archive.postsCount }} {{ archive.postsCount === 1 ? 'post' : 'posts' }}
    </p>
  </div>
  <div v-else class="h-32 rounded-2xl bg-muted/40 animate-pulse" />
</template>
