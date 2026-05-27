<template>
  <div :class="responsive === 'scroll' ? 'overflow-x-auto' : ''">
    <table :class="tableClass">
      <thead>
        <tr>
          <th
            v-for="col in columns"
            :key="col.id"
            class="table-th"
            :class="[
              headerStyle ? 'table-th--filled' : 'table-th--plain',
              borderStyle === 'full'  ? 'table-cell-border' : '',
              borderStyle === 'outer' ? 'table-cell-border-b' : '',
            ]"
            :style="{ textAlign: col.align ?? 'left' }"
          >{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, ri) in rows"
          :key="ri"
          :class="striped && ri % 2 === 1 ? 'table-row-stripe' : ''"
          :data-row="ri"
        >
          <td
            v-for="col in columns"
            :key="col.id"
            class="table-td"
            :class="[
              borderStyle === 'full'  ? 'table-cell-border' : '',
              borderStyle === 'outer' ? 'table-cell-border-b' : '',
              responsive === 'stack'  ? 'block before:content-[attr(data-label)] before:font-semibold before:mr-2 sm:table-cell sm:before:content-none' : '',
            ]"
            :data-label="col.label"
            :style="{ textAlign: col.align ?? 'left' }"
          >
            <span v-if="col.prefix" class="table-affix">{{ col.prefix }}</span>
            {{ cellValue(row, col) }}
            <span v-if="col.suffix" class="table-affix">{{ col.suffix }}</span>
          </td>
        </tr>
        <tr v-if="!rows.length">
          <td :colspan="columns.length" class="table-empty">No data available.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const d           = computed(() => props.block.data ?? {})
const columns     = computed(() => d.value.columns ?? [])
const striped     = computed(() => d.value.striped     ?? true)
const headerStyle = computed(() => d.value.headerStyle ?? true)
const borderStyle = computed(() => d.value.borderStyle ?? 'full')
const responsive  = computed(() => d.value.responsive  ?? 'scroll')

const rows = computed(() => {
  if (d.value.mode === 'dynamic') return d.value.resolved?.items ?? []
  return d.value.rows ?? []
})

function cellValue(row, col) {
  if (d.value.mode === 'dynamic') return col.field ? (row[col.field] ?? '') : ''
  return row[col.id] ?? ''
}

const tableClass = computed(() => [
  'w-full border-collapse text-sm table-base',
  borderStyle.value === 'outer' ? 'table-outer-border' : '',
  responsive.value === 'stack'  ? 'sm:table block' : '',
])
</script>

<style scoped>
.table-base { color: var(--ink); }
.table-outer-border { border: 1px solid var(--line-strong); }

.table-th {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
}
.table-th--filled { background: var(--bg); color: var(--ink); }
.table-th--plain  { color: var(--soft); }

.table-td { padding: 0.75rem 1rem; font-size: 0.875rem; color: var(--ink); }

.table-cell-border   { border: 1px solid var(--line-strong); }
.table-cell-border-b { border-bottom: 1px solid var(--line-strong); }

.table-row-stripe { background: var(--bg); }

.table-affix { color: var(--soft); font-size: 0.75rem; margin: 0 0.125rem; }

.table-empty { padding: 2rem 1rem; text-align: center; color: var(--soft); font-size: 0.875rem; }
</style>
