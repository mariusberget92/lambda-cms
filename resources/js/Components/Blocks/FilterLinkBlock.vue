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
    const params = new URLSearchParams(window.location.search)
    return params.get(paramName.value) === slug.value
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
  <!-- List variant: compact row, full-width, subtle hover -->
  <a
    v-if="variant === 'list'"
    :href="filterUrl"
    class="flex items-center justify-between w-full px-2 py-1.5 rounded-md text-sm transition-colors"
    :style="isActive
      ? 'color:#4f46e5; background:#ede9fe; font-weight:600;'
      : 'color:#4b5563;'"
    style_hover="background:#f3f4f6"
    @mouseenter="e => !isActive && (e.target.style.background='#f3f4f6')"
    @mouseleave="e => !isActive && (e.target.style.background='')"
    @click="navigate"
  >
    <span>{{ label || slug }}</span>
    <span style="color:#9ca3af; font-size:10px;">›</span>
  </a>

  <!-- Pill variant: compact rounded badge -->
  <a
    v-else-if="variant === 'pill'"
    :href="filterUrl"
    class="inline-flex items-center rounded-full text-xs font-medium transition-colors cursor-pointer"
    :style="isActive
      ? 'padding:3px 10px; background:#ede9fe; color:#4f46e5; border:1px solid #c4b5fd;'
      : 'padding:3px 10px; background:#f9fafb; color:#374151; border:1px solid #e5e7eb;'"
    @mouseenter="e => !isActive && (e.target.style.background='#f3f4f6')"
    @mouseleave="e => !isActive && (e.target.style.background= isActive ? '#ede9fe' : '#f9fafb')"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>
