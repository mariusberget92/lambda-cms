<!-- resources/js/Components/Blocks/TableBlock.vue -->
<template>
  <div :class="responsive === 'scroll' ? 'overflow-x-auto' : ''">
    <table :class="tableClass">
      <thead>
        <tr>
          <th
            v-for="col in columns"
            :key="col.id"
            :class="[
              'px-4 py-3 text-sm font-semibold',
              headerStyle ? 'bg-muted text-foreground' : 'text-muted-foreground',
              borderStyle === 'full'  ? 'border border-border' : '',
              borderStyle === 'outer' ? 'border-b border-border' : '',
            ]"
            :style="{ textAlign: col.align ?? 'left' }"
          >{{ col.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, ri) in rows"
          :key="ri"
          :class="striped && ri % 2 === 1 ? 'bg-muted/40' : ''"
          :data-row="ri"
        >
          <td
            v-for="col in columns"
            :key="col.id"
            :class="[
              'px-4 py-3 text-sm',
              borderStyle === 'full'  ? 'border border-border' : '',
              borderStyle === 'outer' ? 'border-b border-border' : '',
              responsive === 'stack'  ? 'block before:content-[attr(data-label)] before:font-semibold before:mr-2 sm:table-cell sm:before:content-none' : '',
            ]"
            :data-label="col.label"
            :style="{ textAlign: col.align ?? 'left' }"
          >
            <span v-if="col.prefix" class="text-muted-foreground text-xs mr-0.5">{{ col.prefix }}</span>
            {{ cellValue(row, col) }}
            <span v-if="col.suffix" class="text-muted-foreground text-xs ml-0.5">{{ col.suffix }}</span>
          </td>
        </tr>
        <tr v-if="!rows.length">
          <td :colspan="columns.length" class="px-4 py-8 text-center text-muted-foreground text-sm">
            No data available.
          </td>
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

// Static: rows from block.data.rows. Dynamic: from block.data.resolved.items
const rows = computed(() => {
  if (d.value.mode === 'dynamic') {
    return d.value.resolved?.items ?? []
  }
  return d.value.rows ?? []
})

// For dynamic rows, cell value comes from the item field. For static, from the column ID key.
function cellValue(row, col) {
  if (d.value.mode === 'dynamic') {
    return col.field ? (row[col.field] ?? '') : ''
  }
  return row[col.id] ?? ''
}

const tableClass = computed(() => [
  'w-full border-collapse text-sm',
  borderStyle.value === 'outer' ? 'border border-border' : '',
  responsive.value === 'stack'  ? 'sm:table block' : '',
])
</script>
