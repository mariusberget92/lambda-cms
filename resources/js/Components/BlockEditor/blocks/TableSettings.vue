<!-- resources/js/components/BlockEditor/blocks/TableSettings.vue -->
<template>
  <div class="space-y-4">

    <!-- Content tab -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">

      <!-- Mode toggle -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Mode</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button type="button"
            :class="d.mode === 'static' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            class="flex-1 py-1.5 transition-colors"
            @click="update('mode', 'static')">Static</button>
          <button type="button"
            :class="d.mode === 'dynamic' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            class="flex-1 py-1.5 transition-colors"
            @click="update('mode', 'dynamic')">Dynamic</button>
        </div>
      </div>

      <!-- Columns -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-muted-foreground">Columns</label>
          <button type="button" class="text-xs text-primary hover:underline" @click="addColumn">+ Add column</button>
        </div>

        <VueDraggable v-model="localColumns" tag="div" class="space-y-2" handle=".col-handle" :animation="150" @end="onColumnsReorder">
          <div v-for="col in localColumns" :key="col.id" class="rounded-md border bg-muted/20 p-2 space-y-1.5">

            <!-- Header row: drag handle + label + remove -->
            <div class="flex items-center gap-1.5">
              <span class="col-handle cursor-grab text-muted-foreground"><GripVertical class="w-3 h-3" /></span>
              <input
                :value="col.label"
                type="text"
                placeholder="Header label"
                class="flex-1 rounded border bg-background px-2 py-1 text-xs"
                @input="updateCol(col.id, 'label', $event.target.value)"
              />
              <button type="button" class="text-destructive/60 hover:text-destructive" @click="removeColumn(col.id)">
                <X class="w-3 h-3" />
              </button>
            </div>

            <!-- Field (dynamic only) -->
            <div v-if="d.mode === 'dynamic'">
              <SelectBox size="sm"
                :model-value="col.field"
                :data="[{ value: '', label: 'Select field...' }, ...sourceFields]"
                placeholder="Select field..."
                @update:model-value="v => updateCol(col.id, 'field', v)"
              />
            </div>

            <!-- Prefix / Suffix -->
            <div class="flex gap-2">
              <input
                :value="col.prefix"
                type="text"
                placeholder="Prefix (e.g. £)"
                class="w-1/2 rounded border bg-background px-2 py-1 text-xs"
                @input="updateCol(col.id, 'prefix', $event.target.value)"
              />
              <input
                :value="col.suffix"
                type="text"
                placeholder="Suffix (e.g. %)"
                class="w-1/2 rounded border bg-background px-2 py-1 text-xs"
                @input="updateCol(col.id, 'suffix', $event.target.value)"
              />
            </div>

            <!-- Alignment -->
            <div class="flex rounded-md border overflow-hidden text-[10px]">
              <button v-for="a in ['left','center','right']" :key="a" type="button"
                :class="col.align === a ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                class="flex-1 py-1 transition-colors capitalize"
                @click="updateCol(col.id, 'align', a)">
                {{ a.slice(0,1).toUpperCase() + a.slice(1, 4) }}
              </button>
            </div>

          </div>
        </VueDraggable>
      </div>

      <!-- Static: rows editor -->
      <div v-if="d.mode === 'static'">
        <div class="flex items-center justify-between mb-2">
          <label class="text-xs font-medium text-muted-foreground">Rows</label>
          <button type="button" class="text-xs text-primary hover:underline" @click="addRow">+ Add row</button>
        </div>
        <div class="space-y-1">
          <div v-for="(row, ri) in d.rows ?? []" :key="ri" class="flex gap-1 items-center">
            <div class="flex-1 flex gap-1">
              <input
                v-for="col in d.columns ?? []"
                :key="col.id"
                :value="row[col.id] ?? ''"
                type="text"
                :placeholder="col.label || 'Cell'"
                class="flex-1 min-w-0 rounded border bg-background px-2 py-1 text-xs"
                @input="updateCell(ri, col.id, $event.target.value)"
              />
            </div>
            <button type="button" class="text-destructive/60 hover:text-destructive shrink-0" @click="removeRow(ri)">
              <X class="w-3 h-3" />
            </button>
          </div>
        </div>
      </div>

      <!-- Dynamic: query section -->
      <div v-if="d.mode === 'dynamic'" class="space-y-3 pt-2 border-t">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Data Source</label>
          <SelectBox size="sm" :model-value="d.source" :data="SOURCES" @update:model-value="onSourceChange" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-medium text-muted-foreground">Filters</label>
            <button type="button" class="text-xs text-primary hover:underline" @click="addFilter">+ Add filter</button>
          </div>
          <div v-for="(filter, i) in d.filters ?? []" :key="i" class="mb-2 p-2 rounded border bg-muted/30 space-y-1.5">
            <SelectBox size="sm" :model-value="filter.field" :data="filterableFields" placeholder="Field..." @update:model-value="v => updateFilter(i, { field: v })" />
            <SelectBox size="sm" :model-value="filter.op" :data="FILTER_OPS" @update:model-value="v => updateFilter(i, { op: v })" />
            <input v-if="filter.op !== 'not_empty' && filter.op !== 'empty'"
              :value="filter.value ?? ''"
              type="text" placeholder="Value..."
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateFilter(i, { value: $event.target.value })" />
            <button type="button" class="text-xs text-destructive hover:underline" @click="removeFilter(i)">Remove</button>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Sort By</label>
          <div class="flex gap-2">
            <SelectBox size="sm" :model-value="d.sort?.field" :data="sortFieldOptions" class="flex-1" @update:model-value="v => update('sort', { ...d.sort, field: v })" />
            <SelectBox size="sm" :model-value="d.sort?.direction ?? 'desc'" :data="[{ value:'desc',label:'Desc'},{ value:'asc',label:'Asc'}]" class="w-[4.5rem]" @update:model-value="v => update('sort', { ...d.sort, direction: v })" />
          </div>
        </div>

        <div class="flex gap-2">
          <div class="flex-1">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Limit</label>
            <NumberInput size="sm" :model-value="d.limit ?? 10" :min="1" :max="200" @update:model-value="v => update('limit', v || 10)" />
          </div>
          <div class="flex-1">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Offset</label>
            <NumberInput size="sm" :model-value="d.offset ?? 0" :min="0" @update:model-value="v => update('offset', v || 0)" />
          </div>
        </div>
      </div>

    </div>

    <!-- Style tab -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Border style</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button v-for="opt in [{ value:'full',label:'Full'},{ value:'outer',label:'Outer'},{ value:'none',label:'None'}]" :key="opt.value" type="button"
            :class="(d.borderStyle ?? 'full') === opt.value ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            class="flex-1 py-1.5 transition-colors"
            @click="update('borderStyle', opt.value)">{{ opt.label }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Responsive</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button type="button" :class="(d.responsive ?? 'scroll') === 'scroll' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'" class="flex-1 py-1.5 transition-colors" @click="update('responsive','scroll')">Scroll</button>
          <button type="button" :class="(d.responsive ?? 'scroll') === 'stack' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'" class="flex-1 py-1.5 transition-colors" @click="update('responsive','stack')">Stack</button>
        </div>
      </div>

      <label class="flex items-center gap-2 cursor-pointer">
        <EditorCheckbox :model-value="d.striped ?? true" @update:model-value="v => update('striped', v)" />
        <span class="text-xs font-medium text-muted-foreground">Striped rows</span>
      </label>

      <label class="flex items-center gap-2 cursor-pointer">
        <EditorCheckbox :model-value="d.headerStyle ?? true" @update:model-value="v => update('headerStyle', v)" />
        <span class="text-xs font-medium text-muted-foreground">Styled header row</span>
      </label>

    </div>

  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, X } from '@lucide/vue'
import SelectBox  from '@/Components/SelectBox.vue'
import NumberInput from '@/Components/NumberInput.vue'
import { SOURCES, SOURCE_FIELDS, SORT_FIELDS, FILTER_OPS } from '@/lib/loopSources.js'
import EditorCheckbox from '../EditorCheckbox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const d = computed(() => props.block.data ?? {})

// Columns — local ref for VueDraggable
const _cols = ref([...(props.block.data?.columns ?? [])])
watch(() => props.block.data?.columns, v => { _cols.value = [...(v ?? [])] })
const localColumns = computed({
  get: () => _cols.value,
  set: (val) => { _cols.value = val },
})

const sourceFields    = computed(() => SOURCE_FIELDS[d.value.source ?? 'posts'] ?? [])
const filterableFields = computed(() => sourceFields.value)
const sortFieldOptions = computed(() => (SORT_FIELDS[d.value.source ?? 'posts'] ?? []).map(f => ({ value: f, label: f })))

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}

function onColumnsReorder() {
  emit('update', { id: props.block.id, data: { columns: _cols.value } })
}

function updateCol(colId, key, value) {
  const cols = (d.value.columns ?? []).map(c => c.id === colId ? { ...c, [key]: value } : c)
  emit('update', { id: props.block.id, data: { columns: cols } })
}

function addColumn() {
  const id = crypto.randomUUID()
  const cols = [...(d.value.columns ?? []), { id, label: `Column ${(d.value.columns?.length ?? 0) + 1}`, field: '', prefix: '', suffix: '', align: 'left' }]
  emit('update', { id: props.block.id, data: { columns: cols } })
}

function removeColumn(colId) {
  const cols = (d.value.columns ?? []).filter(c => c.id !== colId)
  // Also strip that column from all rows
  const rows = (d.value.rows ?? []).map(row => { const r = { ...row }; delete r[colId]; return r })
  emit('update', { id: props.block.id, data: { columns: cols, rows } })
}

function addRow() {
  const row = Object.fromEntries((d.value.columns ?? []).map(c => [c.id, '']))
  emit('update', { id: props.block.id, data: { rows: [...(d.value.rows ?? []), row] } })
}

function removeRow(ri) {
  const rows = (d.value.rows ?? []).filter((_, i) => i !== ri)
  emit('update', { id: props.block.id, data: { rows } })
}

function updateCell(ri, colId, value) {
  const rows = (d.value.rows ?? []).map((row, i) => i === ri ? { ...row, [colId]: value } : row)
  emit('update', { id: props.block.id, data: { rows } })
}

function onSourceChange(v) {
  emit('update', { id: props.block.id, data: { source: v, filters: [] } })
}

function addFilter() {
  update('filters', [...(d.value.filters ?? []), { field: '', op: '=', value: '' }])
}

function removeFilter(i) {
  update('filters', (d.value.filters ?? []).filter((_, idx) => idx !== i))
}

function updateFilter(i, patch) {
  update('filters', (d.value.filters ?? []).map((f, idx) => idx === i ? { ...f, ...patch } : f))
}
</script>
