<!-- resources/js/components/Blocks/PaginationBlock.vue -->
<template>
  <nav
    v-if="lastPage > 1"
    class="my-6 flex flex-wrap items-center gap-2"
    :class="{
      'justify-start':  alignment === 'left',
      'justify-center': alignment === 'center',
      'justify-end':    alignment === 'right',
    }"
    aria-label="Pagination"
  >
    <!-- Prev -->
    <button
      :class="[btnClass, currentPage <= 1 ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
      :disabled="currentPage <= 1"
      @click="go(currentPage - 1)"
    >
      {{ prevLabel }}
    </button>

    <!-- Numbered pages -->
    <template v-if="style === 'numbered'">
      <template v-for="p in visiblePages" :key="p">
        <span v-if="p === '...'" class="px-1 text-sm text-muted-foreground select-none">…</span>
        <button
          v-else
          :class="[btnClass, p === currentPage ? activeClass : '', 'cursor-pointer']"
          @click="go(p)"
        >
          {{ p }}
        </button>
      </template>
    </template>

    <!-- Next -->
    <button
      :class="[btnClass, currentPage >= lastPage ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
      :disabled="currentPage >= lastPage"
      @click="go(currentPage + 1)"
    >
      {{ nextLabel }}
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useLoopPagination } from '@/composables/useLoopPagination.js'

const props = defineProps({ block: { type: Object, required: true } })

const { getPagination } = useLoopPagination()

const pageParam   = computed(() => props.block.data?.pageParam ?? 'page')
const style       = computed(() => props.block.data?.style ?? 'prev-next')
const alignment   = computed(() => props.block.data?.alignment ?? 'center')
const buttonStyle = computed(() => props.block.data?.buttonStyle ?? 'outline')
const prevLabel   = computed(() => props.block.data?.prevLabel ?? '← Previous')
const nextLabel   = computed(() => props.block.data?.nextLabel ?? 'Next →')

const pagination  = computed(() => getPagination(pageParam.value))
const total       = computed(() => pagination.value.total)
const perPage     = computed(() => pagination.value.perPage || 1)
const lastPage    = computed(() => Math.ceil(total.value / perPage.value))

const currentPage = computed(() => {
  const n = parseInt(new URL(window.location.href).searchParams.get(pageParam.value))
  return Number.isFinite(n) && n > 0 ? n : 1
})

function pageUrl(p) {
  const url = new URL(window.location.href)
  url.searchParams.set(pageParam.value, p)
  return url.pathname + url.search
}

function go(p) {
  if (p < 1 || p > lastPage.value) return
  router.get(pageUrl(p), {}, { preserveScroll: true })
}

// Visible page numbers with ellipsis — always show first, last, and ±2 around current
const visiblePages = computed(() => {
  const total = lastPage.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const cur = currentPage.value
  const pages = new Set([1, total, cur - 1, cur, cur + 1, cur - 2, cur + 2].filter(p => p >= 1 && p <= total))
  const sorted = [...pages].sort((a, b) => a - b)
  const result = []
  let prev = 0
  for (const p of sorted) {
    if (p - prev > 1) result.push('...')
    result.push(p)
    prev = p
  }
  return result
})

const btnClass = computed(() => {
  const base = 'px-3 py-1.5 rounded text-sm font-medium transition-colors min-w-[2rem] text-center'
  if (buttonStyle.value === 'solid')   return `${base} bg-primary text-primary-foreground hover:opacity-90`
  if (buttonStyle.value === 'ghost')   return `${base} text-foreground hover:bg-muted`
  return `${base} border border-border bg-background text-foreground hover:bg-muted`
})

const activeClass = computed(() => {
  if (buttonStyle.value === 'solid')  return 'ring-2 ring-offset-1 ring-primary'
  if (buttonStyle.value === 'ghost')  return 'bg-muted font-bold'
  return 'border-primary text-primary font-bold'
})
</script>
