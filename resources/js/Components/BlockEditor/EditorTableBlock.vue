<!-- resources/js/components/BlockEditor/EditorTableBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg overflow-hidden">
    <!-- Header -->
    <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center justify-between">
      <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
        {{ block.blockName || 'Table' }} · {{ d.mode === 'dynamic' ? 'Dynamic' : 'Static' }}
      </span>
      <span v-if="d.mode === 'dynamic' && isLoading" class="text-[10px] text-white/30 animate-pulse">Loading…</span>
    </div>

    <!-- Table preview -->
    <div class="overflow-x-auto p-2">
      <table class="w-full text-xs border-collapse">
        <!-- Header row -->
        <thead>
          <tr>
            <th
              v-for="col in columns"
              :key="col.id"
              class="px-2 py-1.5 text-left font-semibold text-white/70 border border-white/10 bg-white/5"
              :style="{ textAlign: col.align ?? 'left' }"
            >
              {{ col.label || '—' }}
              <span v-if="col.prefix || col.suffix" class="text-white/30 font-normal ml-1">
                {{ [col.prefix, col.suffix].filter(Boolean).join('…') }}
              </span>
            </th>
          </tr>
        </thead>

        <!-- Static: editable rows -->
        <tbody v-if="d.mode === 'static'">
          <tr v-for="(row, ri) in d.rows ?? []" :key="ri" :class="(d.striped ?? true) && ri % 2 === 1 ? 'bg-white/3' : ''">
            <td
              v-for="col in columns"
              :key="col.id"
              class="border border-white/8 px-0 py-0"
              :style="{ textAlign: col.align ?? 'left' }"
            >
              <div
                contenteditable="true"
                class="px-2 py-1.5 min-w-[60px] outline-none focus:bg-primary/10 focus:ring-1 focus:ring-primary text-white/70"
                :data-row="ri"
                :data-col="col.id"
                @blur="onCellBlur($event, ri, col.id)"
                @keydown.enter.prevent="$event.target.blur()"
              >{{ row[col.id] ?? '' }}</div>
            </td>
          </tr>
          <tr v-if="!(d.rows ?? []).length">
            <td :colspan="columns.length || 1" class="px-3 py-4 text-center text-white/20 italic">
              No rows — add rows in the Content tab
            </td>
          </tr>
        </tbody>

        <!-- Dynamic: skeleton or live rows -->
        <tbody v-else>
          <!-- Loading skeleton -->
          <template v-if="isLoading">
            <tr v-for="i in Math.min(d.limit ?? 5, 5)" :key="i">
              <td v-for="col in columns" :key="col.id" class="border border-white/8 px-2 py-1.5">
                <div class="h-3 bg-white/10 rounded animate-pulse" />
              </td>
            </tr>
          </template>
          <!-- Live rows -->
          <template v-else-if="dynamicRows.length">
            <tr v-for="(row, ri) in dynamicRows" :key="ri" :class="(d.striped ?? true) && ri % 2 === 1 ? 'bg-white/3' : ''">
              <td
                v-for="col in columns"
                :key="col.id"
                class="border border-white/8 px-2 py-1.5 text-white/60"
                :style="{ textAlign: col.align ?? 'left' }"
              >
                <span v-if="col.prefix" class="text-white/30 mr-0.5">{{ col.prefix }}</span>
                {{ col.field ? (row[col.field] ?? '—') : '—' }}
                <span v-if="col.suffix" class="text-white/30 ml-0.5">{{ col.suffix }}</span>
              </td>
            </tr>
          </template>
          <template v-else>
            <tr>
              <td :colspan="columns.length || 1" class="px-3 py-4 text-center text-white/20 italic">
                No results found
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Column count hint when no columns defined -->
    <div v-if="!columns.length" class="px-3 py-2 text-[10px] text-white/20 text-center">
      No columns defined — add columns in the Content tab
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})
const emit = defineEmits(['select', 'update'])

const d = computed(() => props.block.data ?? {})
const columns = computed(() => d.value.columns ?? [])

// ── Dynamic mode: fetch from /api/v1/query ─────────────────────────────────
const dynamicRows = ref([])
const isLoading   = ref(false)

async function fetchRows() {
  if (d.value.mode !== 'dynamic') return
  isLoading.value = true
  try {
    const { data } = await axios.post('/api/v1/query', {
      source:  d.value.source ?? 'posts',
      filters: d.value.filters ?? [],
      sort:    d.value.sort    ?? { field: 'published_at', direction: 'desc' },
      limit:   d.value.limit   ?? 10,
      offset:  d.value.offset  ?? 0,
    })
    dynamicRows.value = data.items ?? []
  } catch (e) {
    if (import.meta.env.DEV) console.error('[EditorTableBlock] fetch error', e)
    dynamicRows.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => fetchRows())

watch(
  () => [d.value.mode, d.value.source, d.value.filters, d.value.sort, d.value.limit, d.value.offset],
  () => fetchRows(),
  { deep: true }
)

// ── Static inline editing ──────────────────────────────────────────────────
function onCellBlur(event, ri, colId) {
  const value = event.target.innerText
  const rows = (d.value.rows ?? []).map((row, i) =>
    i === ri ? { ...row, [colId]: value } : row
  )
  emit('update', { id: props.block.id, data: { rows } })
}
</script>
