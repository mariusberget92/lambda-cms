<script setup>
import { inject } from 'vue'

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
</script>

<template>
  <div
    v-if="post"
    class="rounded-2xl p-5 bg-white"
    style="box-shadow:0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03);"
  >
    <p class="text-[10px] font-bold uppercase tracking-[0.14em] mb-3" style="color:#94a3b8;">Topics</p>
    <div class="flex flex-wrap gap-2">
      <template v-if="props.block.data?.showCategories !== false">
        <a
          v-for="cat in post.categories"
          :key="cat.id"
          :href="`/blog/category/${cat.slug}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-85"
          :style="{ backgroundColor: catColor(cat) }"
        >{{ cat.name }}</a>
      </template>
      <template v-if="props.block.data?.showTags !== false">
        <a
          v-for="(tag, idx) in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-all duration-200"
          :style="{
            backgroundColor: hexToRgba(AURORA[idx % AURORA.length], 0.12),
            color: AURORA[idx % AURORA.length],
          }"
          @mouseenter="e => { e.currentTarget.style.backgroundColor = AURORA[idx % AURORA.length]; e.currentTarget.style.color = '#fff'; }"
          @mouseleave="e => { e.currentTarget.style.backgroundColor = hexToRgba(AURORA[idx % AURORA.length], 0.12); e.currentTarget.style.color = AURORA[idx % AURORA.length]; }"
        >#{{ tag.name }}</a>
      </template>
    </div>
  </div>
  <div v-else class="rounded-2xl p-5 bg-white" style="box-shadow:0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03);">
    <div class="h-3 w-12 rounded bg-muted/40 animate-pulse mb-3" />
    <div class="flex gap-2">
      <div class="h-6 w-16 rounded-full bg-muted/40 animate-pulse" />
      <div class="h-6 w-20 rounded-full bg-muted/40 animate-pulse" />
    </div>
  </div>
</template>
