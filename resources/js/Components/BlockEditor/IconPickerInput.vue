<!-- resources/js/components/BlockEditor/IconPickerInput.vue -->
<template>
  <div>
    <!-- Trigger -->
    <button
      type="button"
      class="w-full flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 text-sm text-left hover:border-muted-foreground transition-colors"
      @click="open = true"
    >
      <Icon v-if="modelValue" :icon="modelValue" class="w-4 h-4 shrink-0" />
      <span class="flex-1 truncate" :class="modelValue ? 'text-foreground' : 'text-muted-foreground'">
        {{ modelValue ? modelValue.split(':')[1] : 'Select icon…' }}
      </span>
      <ChevronDown class="w-3 h-3 text-muted-foreground shrink-0" />
    </button>

    <button
      v-if="modelValue"
      type="button"
      class="mt-1 text-[10px] text-muted-foreground hover:text-foreground transition-colors"
      @click="$emit('update:modelValue', null)"
    >Clear</button>

    <!-- Dialog -->
    <Teleport to="body">
      <div
        v-if="open"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60"
        @click.self="open = false"
      >
        <div class="bg-background border border-border rounded-xl shadow-2xl w-[680px] max-h-[85vh] flex flex-col p-4 gap-3">

          <!-- Header -->
          <div class="flex items-center justify-between shrink-0">
            <h3 class="text-sm font-semibold">Select Icon</h3>
            <button type="button" class="text-muted-foreground hover:text-foreground" @click="open = false">
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Set tabs -->
          <div class="flex gap-1 flex-wrap shrink-0">
            <button
              v-for="s in SETS"
              :key="s.id"
              type="button"
              class="px-2 py-0.5 text-xs rounded border transition-colors"
              :class="activeSet === s.id
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-background border-border hover:border-muted-foreground'"
              @click="activeSet = s.id; page = 0"
            >
              {{ s.label }}
              <span class="opacity-60 ml-0.5">({{ s.id === 'all' ? FLAT_ALL.length : (ALL_ICONS[s.id]?.length ?? 0) }})</span>
            </button>
          </div>

          <!-- Search -->
          <input
            v-model="search"
            type="text"
            placeholder="Search icons…"
            class="shrink-0 w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="page = 0"
          />

          <!-- Grid -->
          <div class="flex-1 overflow-y-auto min-h-0">
            <div class="grid grid-cols-8 gap-1">
              <button
                v-for="ic in pagedIcons"
                :key="ic"
                type="button"
                class="flex flex-col items-center gap-0.5 p-1.5 rounded hover:bg-muted transition-colors"
                :class="ic === modelValue ? 'bg-primary/15 ring-1 ring-primary rounded' : ''"
                :title="ic"
                @click="select(ic)"
              >
                <Icon :icon="ic" class="w-6 h-6 shrink-0" />
                <span class="text-[9px] text-muted-foreground truncate w-full text-center leading-none">
                  {{ ic.split(':')[1] }}
                </span>
              </button>
            </div>

            <p v-if="filteredIcons.length === 0" class="text-center text-sm text-muted-foreground py-8">
              No icons found for "{{ search }}"
            </p>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-between text-xs text-muted-foreground shrink-0 pt-1 border-t border-border">
            <button
              type="button"
              class="px-2 py-1 rounded border hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              :disabled="page === 0"
              @click="page--"
            >← Prev</button>
            <span>{{ page + 1 }} / {{ totalPages }}</span>
            <button
              type="button"
              class="px-2 py-1 rounded border hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              :disabled="page >= totalPages - 1"
              @click="page++"
            >Next →</button>
          </div>

        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Icon, addCollection } from '@iconify/vue'
import { ChevronDown, X } from 'lucide-vue-next'

import fa6SolidData   from '@iconify-json/fa6-solid/icons.json'
import fa6RegularData from '@iconify-json/fa6-regular/icons.json'
import fa6BrandsData  from '@iconify-json/fa6-brands/icons.json'
import lucideData     from '@iconify-json/lucide/icons.json'
import tablerData     from '@iconify-json/tabler/icons.json'

// Register all sets — icons render offline in the editor; public pages use Iconify API fallback
addCollection(fa6SolidData)
addCollection(fa6RegularData)
addCollection(fa6BrandsData)
addCollection(lucideData)
addCollection(tablerData)

function buildList(data) {
  return Object.keys(data.icons).map(n => `${data.prefix}:${n}`)
}

const ALL_ICONS = {
  'fa6-solid':   buildList(fa6SolidData),
  'fa6-regular': buildList(fa6RegularData),
  'fa6-brands':  buildList(fa6BrandsData),
  'lucide':      buildList(lucideData),
  'tabler':      buildList(tablerData),
}

const FLAT_ALL = Object.values(ALL_ICONS).flat()

const SETS = [
  { id: 'all',         label: 'All' },
  { id: 'fa6-solid',   label: 'FA Solid' },
  { id: 'fa6-regular', label: 'FA Regular' },
  { id: 'fa6-brands',  label: 'FA Brands' },
  { id: 'lucide',      label: 'Lucide' },
  { id: 'tabler',      label: 'Tabler' },
]

const PAGE_SIZE = 96  // 8 columns × 12 rows

const props = defineProps({ modelValue: { type: String, default: null } })
const emit  = defineEmits(['update:modelValue'])

const open      = ref(false)
const activeSet = ref('all')
const search    = ref('')
const page      = ref(0)

const sourceIcons = computed(() =>
  activeSet.value === 'all' ? FLAT_ALL : (ALL_ICONS[activeSet.value] ?? [])
)

const filteredIcons = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return sourceIcons.value
  return sourceIcons.value.filter(ic => ic.split(':')[1].includes(q))
})

const totalPages  = computed(() => Math.max(1, Math.ceil(filteredIcons.value.length / PAGE_SIZE)))
const pagedIcons  = computed(() => {
  const start = page.value * PAGE_SIZE
  return filteredIcons.value.slice(start, start + PAGE_SIZE)
})

function select(ic) {
  emit('update:modelValue', ic)
  open.value = false
}
</script>
