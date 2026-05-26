<!-- resources/js/Components/Blocks/FilterLinkBlock.vue -->
<script setup>
import { computed, inject } from 'vue'
import { router } from '@inertiajs/vue3'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem  = inject('loopItem', null)
const variant   = computed(() => props.block.data?.variant ?? 'list')
const paramName = computed(() => props.block.data?.paramName || 'category')
const slug      = computed(() => loopItem?.value?.slug ?? '')
const label     = useFieldBinding(() => props.block, 'label')

// Category hue → OKLCH accent for this item
const hue         = computed(() => loopItem?.value?.hue ?? null)
const itemAccent  = computed(() => hue.value != null ? `oklch(0.62 0.16 ${hue.value})` : 'var(--accent)')

const filterUrl = computed(() => slug.value ? `/?${paramName.value}=${slug.value}` : '/')

const isActive = computed(() => {
  if (!slug.value) return false
  try {
    return new URLSearchParams(window.location.search).get(paramName.value) === slug.value
  } catch {
    return false
  }
})

function navigate(e) {
  e.preventDefault()
  router.get(filterUrl.value, {}, { preserveScroll: true })
}
</script>

<template>
  <!-- List variant -->
  <a
    v-if="variant === 'list'"
    :href="filterUrl"
    class="flex items-center justify-between w-full px-2.5 py-1.5 rounded transition-all duration-150 font-sans text-sm"
    :style="isActive
      ? { color: itemAccent, background: 'var(--bg)', fontWeight: '600', borderRadius: 'var(--blog-radius, 6px)' }
      : { color: 'var(--soft)', borderRadius: 'var(--blog-radius, 6px)' }"
    @mouseenter="e => { if (!isActive) { e.currentTarget.style.color = itemAccent; e.currentTarget.style.background = 'var(--bg)'; } }"
    @mouseleave="e => { if (!isActive) { e.currentTarget.style.color = 'var(--soft)'; e.currentTarget.style.background = 'transparent'; } }"
    @click="navigate"
  >
    <span>{{ label || slug }}</span>
    <span class="font-mono-blog text-[11px]" :style="{ color: isActive ? itemAccent : 'var(--line-strong)' }">›</span>
  </a>

  <!-- Pill variant -->
  <a
    v-else-if="variant === 'pill'"
    :href="filterUrl"
    class="inline-flex items-center font-mono-blog text-[11px] px-3 py-1 rounded-full transition-all duration-150 cursor-pointer"
    :style="isActive
      ? { background: itemAccent, color: 'var(--accent-ink)', border: `1px solid ${itemAccent}` }
      : { background: 'transparent', color: itemAccent, border: `1px solid ${itemAccent}` }"
    @mouseenter="e => { if (!isActive) { e.currentTarget.style.background = itemAccent; e.currentTarget.style.color = 'var(--accent-ink)'; } }"
    @mouseleave="e => { if (!isActive) { e.currentTarget.style.background = 'transparent'; e.currentTarget.style.color = itemAccent; } }"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>
