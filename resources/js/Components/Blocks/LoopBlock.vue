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
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import LoopItemProvider from './LoopItemProvider.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const page      = usePage()
const items     = ref(props.block.data?.resolved?.items ?? [])
const isLoading = ref(false)

// CSS grid wrapper — columns and gap driven by block.data
const GAP_CLASS = { sm: 'gap-2', md: 'gap-4', lg: 'gap-6', xl: 'gap-8' }
const wrapperClass = computed(() => {
  const cols = props.block.data?.columns ?? 1
  const gap  = GAP_CLASS[props.block.data?.gap ?? 'md'] ?? 'gap-4'
  return `grid grid-cols-${cols} ${gap}`
})

// Does this loop have any filters that depend on URL params?
const hasUrlParamFilters = computed(() =>
  (props.block.data?.filters ?? []).some(f => f.urlParam)
)

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
    const { data } = await axios.post('/api/v1/query', {
      source:     props.block.data?.source ?? 'posts',
      filters:    props.block.data?.filters ?? [],
      sort:       props.block.data?.sort    ?? { field: 'published_at', direction: 'desc' },
      limit:      props.block.data?.limit   ?? 12,
      offset:     props.block.data?.offset  ?? 0,
      url_params: getUrlParams(),
    })
    items.value = data.items ?? []
  } catch (err) {
    if (import.meta.env.DEV) console.error('[LoopBlock] fetch error', err)
  } finally {
    isLoading.value = false
  }
}

// Watch for Inertia URL changes (client-side navigation / URL param changes)
// Only set up the watcher when we actually have urlParam filters — avoids unnecessary overhead
if (hasUrlParamFilters.value) {
  watch(
    () => page.url,
    (newUrl, oldUrl) => { if (newUrl !== oldUrl) fetchItems() }
  )
}
</script>
