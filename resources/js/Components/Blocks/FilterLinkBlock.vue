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

const listStyle = computed(() => isActive.value
  ? { color: '#6366f1', background: '#eef2ff', fontWeight: '600' }
  : { color: '#4b5563' }
)

const pillStyle = computed(() => isActive.value
  ? { padding: '4px 14px', background: '#6366f1', color: 'white', borderRadius: '20px', boxShadow: '0 2px 8px rgba(99,102,241,0.35)' }
  : { padding: '4px 14px', background: '#eef2ff', color: '#6366f1', borderRadius: '20px' }
)

function onListEnter(e) {
  if (isActive.value) return
  e.currentTarget.style.background = '#f5f3ff'
  e.currentTarget.style.color = '#6366f1'
}
function onListLeave(e) {
  if (isActive.value) return
  e.currentTarget.style.background = ''
  e.currentTarget.style.color = '#4b5563'
}
function onPillEnter(e) {
  if (isActive.value) return
  e.currentTarget.style.background = '#6366f1'
  e.currentTarget.style.color = 'white'
  e.currentTarget.style.boxShadow = '0 2px 8px rgba(99,102,241,0.35)'
}
function onPillLeave(e) {
  if (isActive.value) return
  e.currentTarget.style.background = '#eef2ff'
  e.currentTarget.style.color = '#6366f1'
  e.currentTarget.style.boxShadow = ''
}
</script>

<template>
  <!-- List variant: compact row, full-width -->
  <a
    v-if="variant === 'list'"
    :href="filterUrl"
    class="flex items-center justify-between w-full px-2.5 py-1.5 rounded-xl text-sm transition-all duration-150"
    :style="listStyle"
    @mouseenter="onListEnter"
    @mouseleave="onListLeave"
    @click="navigate"
  >
    <span>{{ label || slug }}</span>
    <span class="text-[11px] font-bold" style="color:#c4b5fd;">›</span>
  </a>

  <!-- Pill variant: rounded badge -->
  <a
    v-else-if="variant === 'pill'"
    :href="filterUrl"
    class="inline-flex items-center rounded-full text-xs font-semibold transition-all duration-150 cursor-pointer"
    :style="pillStyle"
    @mouseenter="onPillEnter"
    @mouseleave="onPillLeave"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>
