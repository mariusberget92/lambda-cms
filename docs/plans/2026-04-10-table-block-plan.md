# Table Block Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `table` block to the block editor supporting both static (manually authored) and dynamic (query-driven) modes with a unified column-first data model, inline canvas editing, and full public rendering.

**Architecture:** Single `table` block type; columns always defined first; static mode stores `rows[]` keyed by column ID; dynamic mode reuses `loopSources.js` + `/api/v1/query` + server-side `resolveBlocks`. Canvas shows editable table in static mode and live-fetched rows in dynamic mode.

**Tech Stack:** Vue 3 SFCs, VueDraggable Plus, Axios (dynamic fetch), `@/lib/loopSources.js` (reused), Laravel `PublicPageController` + `PreviewController` (server resolution).

---

## Task 1: Register the block — scaffolding across 4 files + 3 stubs

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Create (stub): `resources/js/components/BlockEditor/EditorTableBlock.vue`
- Create (stub): `resources/js/components/BlockEditor/blocks/TableSettings.vue`
- Create (stub): `resources/js/Components/Blocks/TableBlock.vue`

**Step 1: Create stub components**

`resources/js/components/BlockEditor/EditorTableBlock.vue`:
```vue
<template>
  <div class="px-3 py-2 text-xs text-white/40">Table (editor coming soon)</div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true }, selectedId: { type: String, default: null } })
defineEmits(['select', 'update'])
</script>
```

`resources/js/components/BlockEditor/blocks/TableSettings.vue`:
```vue
<template>
  <div class="text-xs text-muted-foreground p-2">Table settings coming soon</div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
defineEmits(['update'])
</script>
```

`resources/js/Components/Blocks/TableBlock.vue`:
```vue
<template>
  <div class="text-sm text-muted-foreground">Table coming soon</div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

**Step 2: Add to `BlockTypePanel.vue`**

In the `ALL_TYPES` array, after the `embed` entry add (import `Table2` from lucide-vue-next at top):
```js
{ type: 'table', label: 'Table', icon: Table2, group: 'Interactive' },
```

In the `DEFAULT_DATA` object add:
```js
table: {
  mode: 'static',
  columns: [
    { id: 'col-1', label: 'Column 1', field: '', prefix: '', suffix: '', align: 'left' },
    { id: 'col-2', label: 'Column 2', field: '', prefix: '', suffix: '', align: 'left' },
  ],
  rows: [ { 'col-1': '', 'col-2': '' }, { 'col-1': '', 'col-2': '' } ],
  source: 'posts',
  filters: [],
  filter_logic: 'and',
  sort: { field: 'published_at', direction: 'desc' },
  limit: 10,
  offset: 0,
  striped: true,
  borderStyle: 'full',
  headerStyle: true,
  responsive: 'scroll',
},
```

**Step 3: Add to `BlockCanvas.vue`**

At top of `<script setup>`, add:
```js
import EditorTableBlock from './EditorTableBlock.vue'
import TableBlock from '@/Components/Blocks/TableBlock.vue'
```

In `BLOCK_MAP` add: `table: TableBlock`

In `LABELS` add: `table: 'Table'`

In the canvas template, after the `EditorTabsBlock` block and before `<!-- Spacer -->`, add:
```vue
<EditorTableBlock
  v-else-if="block.type === 'table'"
  :block="block"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update="$emit('update', $event)"
/>
```

Also add `'update'` to the `defineEmits` list in BlockCanvas.

**Step 4: Add to `BlockLayers.vue`**

Add import:
```js
import TableSettings from './blocks/TableSettings.vue'
```

In `COMPONENT_MAP` add: `table: TableSettings`

In `STYLE_BLOCKS` set add: `'table'`

In `LABELS` add: `table: 'Table'`

**Step 5: Add to `BlockRenderer.vue`**

Add import:
```js
import TableBlock from '@/Components/Blocks/TableBlock.vue'
```

In `BLOCK_MAP` add: `table: TableBlock`

**Step 6: Wire `@update` from BlockCanvas up to BlockEditor**

In `resources/js/components/BlockEditor/BlockEditor.vue`, find the `<BlockCanvas>` component usage and add `@update="updateBlock"`:
```vue
<BlockCanvas
  :blocks="localBlocks"
  :selected-id="selectedBlockId"
  @select="selectBlock"
  @reorder="onReorder"
  @update-children="onUpdateChildren"
  @update="updateBlock"
/>
```

**Step 7: Build**

```bash
cd /c/Users/mariu/Herd/lambda-cms && npm run build
```
Expected: `✓ built in` — no errors.

**Step 8: Commit**
```bash
git add resources/js/components/BlockEditor/EditorTableBlock.vue \
        resources/js/components/BlockEditor/blocks/TableSettings.vue \
        resources/js/Components/Blocks/TableBlock.vue \
        resources/js/components/BlockEditor/BlockTypePanel.vue \
        resources/js/components/BlockEditor/BlockCanvas.vue \
        resources/js/components/BlockEditor/BlockLayers.vue \
        resources/js/components/BlockRenderer.vue \
        resources/js/components/BlockEditor/BlockEditor.vue
git commit -m "feat: scaffold table block — register in editor, canvas, layers, renderer"
```

---

## Task 2: `TableSettings.vue` — Content tab

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/TableSettings.vue`

**Step 1: Write the full Content tab**

Replace stub with:

```vue
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
        <input type="checkbox" :checked="d.striped ?? true" class="accent-nord-green rounded" @change="update('striped', $event.target.checked)" />
        <span class="text-xs font-medium text-muted-foreground">Striped rows</span>
      </label>

      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" :checked="d.headerStyle ?? true" class="accent-nord-green rounded" @change="update('headerStyle', $event.target.checked)" />
        <span class="text-xs font-medium text-muted-foreground">Styled header row</span>
      </label>

    </div>

  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, X } from 'lucide-vue-next'
import SelectBox  from '@/Components/SelectBox.vue'
import NumberInput from '@/Components/NumberInput.vue'
import { SOURCES, SOURCE_FIELDS, SORT_FIELDS, FILTER_OPS } from '@/lib/loopSources.js'

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
```

**Step 2: Build**
```bash
npm run build
```
Expected: `✓ built` — no errors.

**Step 3: Commit**
```bash
git add resources/js/components/BlockEditor/blocks/TableSettings.vue
git commit -m "feat: TableSettings content + style tabs"
```

---

## Task 3: `EditorTableBlock.vue` — canvas with inline editing and dynamic fetch

**Files:**
- Modify: `resources/js/components/BlockEditor/EditorTableBlock.vue`

**Step 1: Write the canvas component**

```vue
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
```

**Step 2: Build**
```bash
npm run build
```
Expected: `✓ built` — no errors.

**Step 3: Commit**
```bash
git add resources/js/components/BlockEditor/EditorTableBlock.vue
git commit -m "feat: EditorTableBlock — inline static editing and dynamic data fetch"
```

---

## Task 4: `TableBlock.vue` — public renderer

**Files:**
- Modify: `resources/js/Components/Blocks/TableBlock.vue`

**Step 1: Write the public renderer**

```vue
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
```

**Step 2: Build**
```bash
npm run build
```
Expected: `✓ built` — no errors.

**Step 3: Commit**
```bash
git add resources/js/Components/Blocks/TableBlock.vue
git commit -m "feat: TableBlock public renderer — static, dynamic, responsive, styled"
```

---

## Task 5: Server-side resolution for dynamic table

**Files:**
- Modify: `app/Http/Controllers/PublicPageController.php`
- Modify: `app/Http/Controllers/PreviewController.php`

**Step 1: Add table resolution to `PublicPageController.php`**

Find the `resolveBlocks` method. After the `if (($block['type'] ?? '') === 'loop')` block, add:

```php
// Resolve dynamic table block
if (($block['type'] ?? '') === 'table' && ($block['data']['mode'] ?? '') === 'dynamic') {
    $result = $this->queryBuilder->resolve($block['data'] ?? [], $urlParams);
    $block['data']['resolved'] = $result;
    return $block;
}
```

Also update the recursive children check at the top of `resolveBlocks` to include `'loop'` children (already handled) — verify `accordion` and `table` are NOT in the recursion list since they don't have child blocks that need resolving. The existing check is for `container` and `section` only — leave it as is.

**Step 2: Apply the same change to `PreviewController.php`**

Find its `resolveBlocks` method and add the identical `table` block after the `loop` block handler:

```php
if (($block['type'] ?? '') === 'table' && ($block['data']['mode'] ?? '') === 'dynamic') {
    $result = $this->queryBuilder->resolve($block['data'] ?? [], $urlParams);
    $block['data']['resolved'] = $result;
    return $block;
}
```

**Step 3: Build**
```bash
npm run build
```
Expected: `✓ built` — no errors.

**Step 4: Commit**
```bash
git add app/Http/Controllers/PublicPageController.php \
        app/Http/Controllers/PreviewController.php
git commit -m "feat: resolve dynamic table block server-side in public and preview controllers"
```

---

## Task 6: Manual verification checklist

**Step 1: Start dev server**
```bash
php artisan serve
npm run dev
```

**Step 2: Open the block editor on any page, drag in a Table block. Verify:**
- [ ] "Table" appears in the Interactive group of the block palette
- [ ] Dragging it to canvas shows the EditorTableBlock with "Static" mode
- [ ] Layers panel shows "TABLE" entry; clicking it opens TableSettings
- [ ] Content tab shows mode toggle, 2 default columns, 2 empty rows
- [ ] Clicking a cell in the canvas makes it editable (contenteditable)
- [ ] Editing a cell and tabbing away updates the row data (check via Advanced → Custom CSS round-trip or settings panel rows)
- [ ] "Add column" adds a column card; "Add row" adds a row
- [ ] Switching to "Dynamic" mode shows source/filters/sort/limit controls; canvas fetches and renders live rows
- [ ] Style tab: toggling striped/header/border/responsive updates the canvas preview
- [ ] Advanced tab works (custom CSS, bg gradient, etc.) same as all blocks

**Step 3: On a published page with a Table block in dynamic mode, verify:**
- [ ] `block.data.resolved.items` is populated server-side (no client fetch on public page)
- [ ] Prefix/suffix render correctly
- [ ] `overflow-x-auto` wraps the table when responsive = scroll

**Step 4: Commit any fixes, then final build**
```bash
npm run build
git add -A
git commit -m "feat: table block complete — static/dynamic, inline editing, public renderer"
```
