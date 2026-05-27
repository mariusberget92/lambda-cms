<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({ block: { type: Object, required: true } })

const page = usePage()

const searchParams = computed(() => {
  try { return new URLSearchParams(new URL(page.url, window.location.origin).search) }
  catch { return new URLSearchParams() }
})

const category   = computed(() => searchParams.value.get('category') ?? '')
const tag        = computed(() => searchParams.value.get('tag') ?? '')
const isFiltered = computed(() => !!category.value || !!tag.value)

const filterType = computed(() => category.value ? 'Category' : 'Tag')
const filterSlug = computed(() => category.value || tag.value)
const filterLabel = computed(() =>
  filterSlug.value.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
)

const defaultTitle = computed(() => props.block.data?.defaultTitle ?? 'Latest Posts')

function clearFilter() {
  router.get('/', {}, { preserveScroll: false })
}
</script>

<template>
  <!-- Filtered state: show active filter context with clear link -->
  <div v-if="isFiltered" class="afb-filtered">
    <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-1.5 afb-type">
      {{ filterType }}
    </p>
    <div class="flex items-center gap-3 flex-wrap">
      <h2 class="font-bold leading-tight afb-heading">{{ filterLabel }}</h2>
      <button
        type="button"
        class="afb-clear font-mono-blog text-[11px] inline-flex items-center gap-1.5 transition-colors"
        @click="clearFilter"
      >
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        All posts
      </button>
    </div>
    <div class="mt-3 h-px w-10 afb-rule" />
  </div>

  <!-- Default state: static title -->
  <h2 v-else class="font-bold leading-tight afb-heading">
    {{ defaultTitle }}
  </h2>
</template>

<style scoped>
.afb-type    { color: var(--soft); }
.afb-heading { color: var(--ink); font-size: clamp(1.25rem, 3vw, 1.75rem); letter-spacing: -0.02em; }
.afb-rule    { background: var(--accent); opacity: 0.6; }

.afb-clear {
  color: var(--soft);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 0.25rem 0.625rem;
  background: var(--panel);
}
.afb-clear:hover {
  color: var(--accent);
  border-color: var(--accent);
}
</style>
