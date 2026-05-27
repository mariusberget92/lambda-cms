<script setup>
import { inject, computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)

const name = computed(() =>
  loopItem?.value?.name
  ?? loopItem?.value?.categories?.[0]?.name
  ?? props.block.data?.label
  ?? 'Category'
)
const slug = computed(() =>
  loopItem?.value?.slug
  ?? loopItem?.value?.categories?.[0]?.slug
  ?? props.block.data?.slug
  ?? null
)
const href     = computed(() => slug.value ? `/blog/category/${slug.value}` : null)
const isActive = computed(() => props.block.data?.active ?? false)
</script>

<template>
  <component
    :is="href ? 'a' : 'span'"
    :href="href || undefined"
    class="cat-chip inline-flex items-center font-mono-blog text-[11px] px-3 py-1 rounded-full transition-all duration-150"
    :class="{ 'cat-chip--active': isActive }"
  >{{ name }}</component>
</template>

<style scoped>
.cat-chip {
  border: 1px solid var(--line-strong);
  color: var(--soft);
  background: transparent;
}
.cat-chip:hover,
.cat-chip--active {
  background: var(--accent);
  color: var(--accent-ink);
  border-color: var(--accent);
}
</style>
