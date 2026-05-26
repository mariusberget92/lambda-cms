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
      ? { color: 'var(--accent)', background: 'var(--bg)', fontWeight: '600', borderRadius: 'var(--blog-radius, 6px)' }
      : { color: 'var(--soft)', borderRadius: 'var(--blog-radius, 6px)' }"
    @mouseenter="e => { if (!isActive) { e.currentTarget.style.color = 'var(--accent)'; e.currentTarget.style.background = 'var(--bg)'; } }"
    @mouseleave="e => { if (!isActive) { e.currentTarget.style.color = 'var(--soft)'; e.currentTarget.style.background = 'transparent'; } }"
    @click="navigate"
  >
    <span>{{ label || slug }}</span>
    <span class="font-mono-blog text-[11px]" :style="{ color: isActive ? 'var(--accent)' : 'var(--line-strong)' }">›</span>
  </a>

  <!-- Pill variant -->
  <a
    v-else-if="variant === 'pill'"
    :href="filterUrl"
    class="inline-flex items-center font-mono-blog text-[11px] px-3 py-1 rounded-full transition-all duration-150 cursor-pointer"
    :style="isActive
      ? { background: 'var(--accent)', color: 'var(--accent-ink)', border: '1px solid var(--accent)' }
      : { background: 'transparent', color: 'var(--soft)', border: '1px solid var(--line-strong)' }"
    @mouseenter="e => { if (!isActive) { e.currentTarget.style.borderColor = 'var(--accent)'; e.currentTarget.style.color = 'var(--accent)'; } }"
    @mouseleave="e => { if (!isActive) { e.currentTarget.style.borderColor = 'var(--line-strong)'; e.currentTarget.style.color = 'var(--soft)'; } }"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>
