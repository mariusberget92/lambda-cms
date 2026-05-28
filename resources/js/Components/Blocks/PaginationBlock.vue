<!-- resources/js/components/Blocks/PaginationBlock.vue -->
<template>
  <nav
    v-if="lastPage > 1"
    class="my-6 flex flex-wrap items-center gap-2 w-full"
    :class="{
      'justify-start':  alignment === 'left',
      'justify-center': alignment === 'center',
      'justify-end':    alignment === 'right',
    }"
    aria-label="Pagination"
  >
    <button
      :class="['pg-btn', currentPage <= 1 ? 'pg-btn--disabled' : '']"
      :disabled="currentPage <= 1"
      @click="go(currentPage - 1)"
    >{{ prevLabel }}</button>

    <template v-if="style === 'numbered'">
      <template v-for="p in visiblePages" :key="p">
        <span v-if="p === '...'" class="px-1 text-sm select-none pg-ellipsis">…</span>
        <button
          v-else
          :class="['pg-btn', p === currentPage ? 'pg-btn--active' : '']"
          @click="go(p)"
        >{{ p }}</button>
      </template>
    </template>

    <button
      :class="['pg-btn', currentPage >= lastPage ? 'pg-btn--disabled' : '']"
      :disabled="currentPage >= lastPage"
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
</script>

<style scoped>
.pg-btn {
  padding: 0.375rem 0.75rem;
  border-radius: var(--blog-radius);
  font-size: 0.875rem;
  font-weight: 500;
  min-width: 2.25rem;
  text-align: center;
  transition: all 150ms;
  cursor: pointer;
  background: var(--panel);
  color: var(--ink);
  border: 1px solid var(--line-strong);
}
.pg-btn:hover:not(:disabled) {
  background: var(--bg);
  border-color: var(--accent);
  color: var(--accent);
}
.pg-btn--active {
  background: var(--accent);
  color: var(--accent-ink);
  border-color: var(--accent);
}
.pg-btn--active:hover { opacity: 0.88; }
.pg-btn--disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.pg-ellipsis { color: var(--soft); }
</style>
