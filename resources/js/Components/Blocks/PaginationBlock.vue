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
      :class="[btnBase, currentPage <= 1 ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
      :style="currentPage <= 1 ? inactiveStyle : hoverReadyStyle"
      :disabled="currentPage <= 1"
      @mouseenter="e => currentPage > 1 && applyHover(e)"
      @mouseleave="e => currentPage > 1 && removeHover(e)"
      @click="go(currentPage - 1)"
    >{{ prevLabel }}</button>

    <!-- Numbered pages -->
    <template v-if="style === 'numbered'">
      <template v-for="p in visiblePages" :key="p">
        <span v-if="p === '...'" class="px-1 text-sm select-none" style="color:#94a3b8;">…</span>
        <button
          v-else
          :class="[btnBase, 'cursor-pointer']"
          :style="p === currentPage ? activeStyle : hoverReadyStyle"
          @mouseenter="e => p !== currentPage && applyHover(e)"
          @mouseleave="e => p !== currentPage && removeHover(e)"
          @click="go(p)"
        >{{ p }}</button>
      </template>
    </template>

    <!-- Next -->
    <button
      :class="[btnBase, currentPage >= lastPage ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
      :style="currentPage >= lastPage ? inactiveStyle : hoverReadyStyle"
      :disabled="currentPage >= lastPage"
      @mouseenter="e => currentPage < lastPage && applyHover(e)"
      @mouseleave="e => currentPage < lastPage && removeHover(e)"
      @click="go(currentPage + 1)"
    >{{ nextLabel }}</button>
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

const btnBase = 'px-3 py-1.5 rounded-xl text-sm font-medium transition-all min-w-[2.25rem] text-center'

const activeStyle    = 'background:#6366f1; color:white; box-shadow:0 2px 8px rgba(99,102,241,0.35);'
const inactiveStyle  = 'background:white; color:#94a3b8; border:1.5px solid #e2e8f0;'
const hoverReadyStyle = 'background:white; color:#374151; border:1.5px solid #e2e8f0;'

function applyHover(e) {
  e.currentTarget.style.cssText = 'background:#eef2ff; color:#6366f1; border:1.5px solid #c7d2fe;'
}
function removeHover(e) {
  e.currentTarget.style.cssText = hoverReadyStyle
}
</script>
