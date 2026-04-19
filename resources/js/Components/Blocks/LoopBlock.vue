<!-- resources/js/Components/Blocks/LoopBlock.vue -->
<template>
  <div :class="wrapperClass">
    <!-- Items: each wrapped in a LoopItemProvider that scopes the item via provide/inject -->
    <template v-if="items.length">
      <LoopItemProvider
        v-for="item in items"
        :key="item.id ?? item.slug ?? item.name"
        :item="item"
      >
        <BlockRenderer :blocks="block.children ?? []" />
      </LoopItemProvider>
    </template>

    <!-- Loading skeleton -->
    <template v-else-if="isLoading">
      <div
        v-for="i in (block.data.limit ?? 6)"
        :key="i"
        class="h-40 rounded-lg bg-muted animate-pulse"
      />
    </template>

    <!-- Empty state -->
    <p v-else class="col-span-full text-muted-foreground text-sm text-center py-8">
      No items found.
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import LoopItemProvider from './LoopItemProvider.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
import { useLoopPagination } from '@/composables/useLoopPagination.js'

const props = defineProps({ block: { type: Object, required: true } })

const page      = usePage()
const items     = ref(props.block.data?.resolved?.items ?? [])
const isLoading = ref(false)

const { setPagination } = useLoopPagination()

// CSS grid/flex wrapper — columns and gap driven by block.data
const GAP_CLASS = { sm: 'gap-2', md: 'gap-4', lg: 'gap-6', xl: 'gap-8' }
const wrapperClass = computed(() => {
  const cols = props.block.data?.columns ?? 1
  const gap  = GAP_CLASS[props.block.data?.gap ?? 'md'] ?? 'gap-4'
  if (cols === 'flex' || props.block.data?.flexWrap) {
    return `flex flex-wrap ${gap}`
  }
  return `grid grid-cols-${cols} ${gap}`
})

// Does this loop have any filters that depend on URL params?
const hasUrlParamFilters = computed(() =>
  (props.block.data?.filters ?? []).some(f => f.urlParam)
)

// The URL param name used for page-based pagination (optional).
const pageParam = computed(() => props.block.data?.pageParam?.trim() || null)

// Read current page number from the URL for this loop's pageParam.
function getCurrentPage() {
  if (!pageParam.value) return 1
  const n = parseInt(new URL(window.location.href).searchParams.get(pageParam.value))
  return Number.isFinite(n) && n > 0 ? n : 1
}

// Extract relevant URL param values from the current window URL
function getUrlParams() {
  if (!hasUrlParamFilters.value) return {}
  const keys   = (props.block.data?.filters ?? []).filter(f => f.urlParam).map(f => f.urlParam)
  const search = new URL(window.location.href).searchParams
  return Object.fromEntries(keys.filter(k => search.has(k)).map(k => [k, search.get(k)]))
}

async function fetchItems() {
  isLoading.value = true
  try {
    const limit = props.block.data?.limit ?? 12

    // When pageParam is set, derive offset from the current page number.
    // Otherwise fall back to the static offset field.
    const offset = pageParam.value
      ? (getCurrentPage() - 1) * limit
      : (props.block.data?.offset ?? 0)

    const { data } = await axios.post('/api/v1/query', {
      source:     props.block.data?.source ?? 'posts',
      filters:    props.block.data?.filters ?? [],
      sort:       props.block.data?.sort    ?? { field: 'published_at', direction: 'desc' },
      limit,
      offset,
      url_params: getUrlParams(),
    })

    items.value = data.items ?? []

    // Publish total + perPage so PaginationBlock can compute page count.
    if (pageParam.value) {
      setPagination(pageParam.value, data.total ?? 0, limit)
    }
  } catch (err) {
    if (import.meta.env.DEV) console.error('[LoopBlock] fetch error', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => fetchItems())

// Watch for Inertia URL changes — covers both filter params and page params.
const shouldWatch = computed(() => hasUrlParamFilters.value || !!pageParam.value)
watch(
  () => page.url,
  (newUrl, oldUrl) => { if (newUrl !== oldUrl && shouldWatch.value) fetchItems() }
)
</script>
