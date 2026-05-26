<script setup>
import { inject, computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)

const hue  = computed(() => {
  const h = loopItem?.value?.hue ?? loopItem?.value?.categories?.[0]?.hue ?? props.block.data?.hue ?? 220
  return h
})
const name = computed(() => {
  return loopItem?.value?.name
    ?? loopItem?.value?.categories?.[0]?.name
    ?? props.block.data?.label
    ?? 'Category'
})
const slug = computed(() => {
  return loopItem?.value?.slug
    ?? loopItem?.value?.categories?.[0]?.slug
    ?? props.block.data?.slug
    ?? null
})
const href = computed(() => slug.value ? `/blog/category/${slug.value}` : null)

const chipColor  = computed(() => `oklch(0.62 0.16 ${hue.value})`)
const isActive   = computed(() => props.block.data?.active ?? false)
</script>

<template>
  <component
    :is="href ? 'a' : 'span'"
    :href="href || undefined"
    class="inline-flex items-center font-mono-blog text-[11px] px-3 py-1 rounded-full transition-all duration-150"
    :style="isActive
      ? { background: chipColor, color: 'var(--accent-ink)', border: `1px solid ${chipColor}` }
      : { background: 'transparent', color: chipColor, border: `1px solid ${chipColor}` }"
    @mouseenter="e => { if (!isActive) { e.currentTarget.style.background = chipColor; e.currentTarget.style.color = 'var(--accent-ink)'; } }"
    @mouseleave="e => { if (!isActive) { e.currentTarget.style.background = 'transparent'; e.currentTarget.style.color = chipColor; } }"
  >{{ name }}</component>
</template>
