<script setup>
import { inject, computed } from 'vue'

const props = defineProps({ block: Object })

const post = inject('postContext', null)

const AURORA = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#f97316','#ef4444','#a855f7']

function catColor(cat) {
  if (cat?.color) return cat.color
  if (!cat?.name) return AURORA[0]
  return AURORA[[...cat.name].reduce((s, c) => s + c.charCodeAt(0), 0) % AURORA.length]
}

function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1,3), 16)
  const g = parseInt(hex.slice(3,5), 16)
  const b = parseInt(hex.slice(5,7), 16)
  return `rgba(${r},${g},${b},${alpha})`
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const accentColor = computed(() => post?.categories?.[0] ? catColor(post.categories[0]) : AURORA[0])

const readingTime = computed(() => {
  if (!post?.body) return null
  const words = post.body.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.ceil(words / 200))
})
</script>

<template>
  <div
    v-if="post"
    class="rounded-2xl p-5 bg-white"
    style="box-shadow:0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03);"
  >
    <p class="text-[10px] font-bold uppercase tracking-[0.14em] mb-3" style="color:#94a3b8;">Written by</p>
    <div class="flex items-center gap-3">
      <div
        class="w-11 h-11 rounded-full overflow-hidden shrink-0"
        :style="{ outline: `3px solid ${hexToRgba(accentColor, 0.3)}`, outlineOffset: '2px' }"
      >
        <img
          v-if="post.author?.avatar_url"
          :src="post.author.avatar_url"
          :alt="post.author.name"
          class="w-full h-full object-cover"
        />
        <div
          v-else
          class="w-full h-full flex items-center justify-center text-sm font-bold text-white"
          :style="{ backgroundColor: accentColor }"
        >{{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}</div>
      </div>

      <div class="flex-1 min-w-0">
        <p class="text-sm font-bold text-foreground">{{ post.author?.name ?? 'Unknown' }}</p>
        <p class="text-xs" style="color:#94a3b8;">{{ formatDate(post.published_at) }}</p>
      </div>

      <span
        v-if="readingTime"
        class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold"
        :style="{ background: hexToRgba(accentColor, 0.10), color: accentColor }"
      >{{ readingTime }} min read</span>
    </div>
  </div>

  <div v-else class="rounded-2xl p-5 bg-white" style="box-shadow:0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03);">
    <div class="h-3 w-16 rounded bg-muted/40 animate-pulse mb-3" />
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-full bg-muted/40 animate-pulse shrink-0" />
      <div class="space-y-1.5">
        <div class="h-4 w-28 rounded bg-muted/40 animate-pulse" />
        <div class="h-3 w-20 rounded bg-muted/40 animate-pulse" />
      </div>
    </div>
  </div>
</template>
