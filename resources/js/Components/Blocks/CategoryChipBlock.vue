<script setup>
import { inject, computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)

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
const href     = computed(() => slug.value ? `/blog/category/${slug.value}` : null)
const isActive = computed(() => props.block.data?.active ?? false)
</script>

<template>
  <component
    :is="href ? 'a' : 'span'"
    :href="href || undefined"
    class="inline-flex items-center font-mono-blog text-[11px] px-3 py-1 rounded-full transition-all duration-150"
    :style="isActive
      ? { background: 'var(--accent)', color: 'var(--accent-ink)', border: '1px solid var(--accent)' }
      : { background: 'transparent', color: 'var(--soft)', border: '1px solid var(--line-strong)' }"
    @mouseenter="e => { if (!isActive) { e.currentTarget.style.borderColor = 'var(--accent)'; e.currentTarget.style.color = 'var(--accent)'; } }"
    @mouseleave="e => { if (!isActive) { e.currentTarget.style.borderColor = 'var(--line-strong)'; e.currentTarget.style.color = 'var(--soft)'; } }"
  >{{ name }}</component>
</template>
