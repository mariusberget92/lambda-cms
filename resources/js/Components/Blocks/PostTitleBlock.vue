<script setup>
import { inject, computed } from 'vue'

defineProps({ block: Object })

const post = inject('postContext', null)

const AURORA = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#f97316','#ef4444','#a855f7']

function catColor(cat) {
  if (cat?.color) return cat.color
  if (!cat?.name) return AURORA[0]
  return AURORA[[...cat.name].reduce((s, c) => s + c.charCodeAt(0), 0) % AURORA.length]
}

const accentColor = computed(() => post?.categories?.[0] ? catColor(post.categories[0]) : AURORA[0])
</script>

<template>
  <div v-if="post">
    <a
      href="/"
      class="inline-flex items-center gap-1.5 text-sm font-medium mb-6 transition-colors"
      style="color:#94a3b8;"
      @mouseenter="$event.currentTarget.style.color='#6366f1'"
      @mouseleave="$event.currentTarget.style.color='#94a3b8'"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      All posts
    </a>
    <h1 class="font-editorial text-4xl sm:text-5xl font-bold leading-tight text-foreground">
      {{ post.title }}
    </h1>
    <div class="mt-4 h-1 w-16 rounded-full" :style="{ background: accentColor }" />
  </div>
  <div v-else class="space-y-3">
    <div class="h-4 w-24 rounded bg-muted/40 animate-pulse" />
    <div class="h-10 rounded bg-muted/40 animate-pulse w-3/4" />
    <div class="h-1 w-16 rounded-full bg-muted/40 animate-pulse" />
  </div>
</template>
