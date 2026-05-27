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
  <a
    v-if="variant === 'list'"
    :href="filterUrl"
    class="filter-list-link flex items-center justify-between w-full px-2.5 py-1.5 rounded transition-all duration-150 font-sans text-sm"
    :class="{ 'filter-list-link--active': isActive }"
    @click="navigate"
  >
    <span>{{ label || slug }}</span>
    <span class="font-mono-blog text-[11px] filter-list-link__arrow">›</span>
  </a>

  <a
    v-else-if="variant === 'pill'"
    :href="filterUrl"
    class="filter-pill inline-flex items-center font-mono-blog text-[11px] px-3 py-1 rounded-full transition-all duration-150 cursor-pointer"
    :class="{ 'filter-pill--active': isActive }"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>

<style scoped>
.filter-list-link {
  color: var(--soft);
  border-radius: var(--blog-radius);
}
.filter-list-link:hover,
.filter-list-link--active {
  color: var(--accent);
  background: var(--bg);
  font-weight: 600;
}
.filter-list-link__arrow { color: var(--line-strong); }
.filter-list-link:hover .filter-list-link__arrow,
.filter-list-link--active .filter-list-link__arrow {
  color: var(--accent);
}

.filter-pill {
  color: var(--soft);
  border: 1px solid var(--line-strong);
  background: transparent;
}
.filter-pill:hover,
.filter-pill--active {
  background: var(--accent);
  color: var(--accent-ink);
  border-color: var(--accent);
}
</style>
