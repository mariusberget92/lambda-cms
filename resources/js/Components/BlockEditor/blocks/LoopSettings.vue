<!-- resources/js/Components/BlockEditor/blocks/LoopSettings.vue -->
<template>
  <div class="space-y-4">

    <!-- Content fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">

      <!-- ── Data Source ─────────────────────────────────────────────── -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Data Source</label>
        <SelectBox size="sm"
          :model-value="block.data.source"
          :data="SOURCES"
          @update:model-value="onSourceChange"
        />
      </div>

      <!-- ── Filters ────────────────────────────────────────────────── -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-1.5">
            <label class="text-xs font-medium text-muted-foreground">Filters</label>
            <template v-if="filters.length >= 2">
              <button
                type="button"
                class="px-1.5 py-0.5 rounded border text-[10px] font-medium transition-colors"
                :class="filterLogic === 'and'
                  ? 'border-primary text-primary bg-primary/10'
                  : 'border-border text-muted-foreground hover:border-primary'"
                @click="emitData({ filter_logic: 'and' })"
              >AND</button>
              <button
                type="button"
                class="px-1.5 py-0.5 rounded border text-[10px] font-medium transition-colors"
                :class="filterLogic === 'or'
                  ? 'border-primary text-primary bg-primary/10'
                  : 'border-border text-muted-foreground hover:border-primary'"
                @click="emitData({ filter_logic: 'or' })"
              >OR</button>
            </template>
          </div>
          <button
            type="button"
            class="text-xs text-primary hover:underline"
            @click="addFilter"
          >+ Add filter</button>
        </div>

        <div
          v-for="(filter, i) in filters"
          :key="i"
          class="mb-2 p-2 rounded-md border bg-muted/30 space-y-1.5"
        >
          <!-- Field -->
          <SelectBox size="sm"
            :model-value="filter.field"
            :data="filterableFields"
            placeholder="Field..."
            @update:model-value="v => updateFilter(i, { field: v })"
          />

          <!-- Operator -->
          <SelectBox size="sm"
            :model-value="filter.op"
            :data="FILTER_OPS"
            @update:model-value="v => updateFilter(i, { op: v })"
          />

          <!-- Value or URL param (hidden when op is not_empty / empty) -->
          <template v-if="filter.op !== 'not_empty' && filter.op !== 'empty'">
            <label class="flex items-center gap-2 text-xs cursor-pointer">
              <EditorCheckbox
                :model-value="!!filter.urlParam"
                @update:model-value="v => toggleUrlParam(i, v)"
              />
              From URL param
            </label>

            <input
              v-if="filter.urlParam"
              :value="filter.urlParam"
              type="text"
              placeholder="param name (e.g. category)"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateFilter(i, { urlParam: $event.target.value })"
            />
            <input
              v-else
              :value="filter.value ?? ''"
              type="text"
              placeholder="Value..."
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateFilter(i, { value: $event.target.value })"
            />
          </template>

          <button
            type="button"
            class="text-xs text-destructive hover:underline"
            @click="removeFilter(i)"
          >Remove</button>
        </div>
      </div>

      <!-- ── Sort ───────────────────────────────────────────────────── -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Sort By</label>
        <div class="flex gap-2">
          <SelectBox size="sm"
            :model-value="block.data.sort?.field"
            :data="sortFieldOptions"
            class="flex-1"
            @update:model-value="v => emitData({ sort: { ...block.data.sort, field: v } })"
          />
          <SelectBox size="sm"
            :model-value="block.data.sort?.direction ?? 'desc'"
            :data="[{ value: 'desc', label: 'Desc' }, { value: 'asc', label: 'Asc' }]"
            class="w-[4.5rem]"
            @update:model-value="v => emitData({ sort: { ...block.data.sort, direction: v } })"
          />
        </div>
      </div>

      <!-- ── Limit + Offset ─────────────────────────────────────────── -->
      <div class="flex gap-2">
        <div class="flex-1">
          <label class="text-xs font-medium text-muted-foreground block mb-1">Limit</label>
          <NumberInput size="sm"
            :model-value="block.data.limit ?? 12"
            :min="1"
            :max="100"
            @update:model-value="emitData({ limit: $event || 12 })"
          />
        </div>
        <div class="flex-1">
          <label class="text-xs font-medium text-muted-foreground block mb-1">Offset</label>
          <NumberInput size="sm"
            :model-value="block.data.offset ?? 0"
            :min="0"
            @update:model-value="emitData({ offset: $event || 0 })"
          />
        </div>
      </div>

    </div>

    <!-- Style fields -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">

      <!-- ── Appearance ─────────────────────────────────────────────── -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Columns per row</label>
        <SelectBox size="sm"
          :model-value="block.data.columns ?? 1"
          :data="[1, 2, 3, 4].map(n => ({ value: n, label: `${n} col${n > 1 ? 's' : ''}` }))"
          @update:model-value="v => emitData({ columns: Number(v) })"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
        <SelectBox size="sm"
          :model-value="block.data.gap ?? 'md'"
          :data="[
            { value: 'sm', label: 'Small' },
            { value: 'md', label: 'Medium' },
            { value: 'lg', label: 'Large' },
            { value: 'xl', label: 'X-Large' },
          ]"
          @update:model-value="v => emitData({ gap: v })"
        />
      </div>

    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'
import NumberInput from '@/Components/NumberInput.vue'
import { SOURCES, SOURCE_FIELDS, SORT_FIELDS, FILTER_OPS } from '@/lib/loopSources.js'
import EditorCheckbox from '../EditorCheckbox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit  = defineEmits(['update'])

const source = computed(() => props.block.data?.source ?? 'posts')
const filters = computed(() => props.block.data?.filters ?? [])
const filterLogic = computed(() => props.block.data?.filter_logic ?? 'and')

const filterableFields = computed(() => SOURCE_FIELDS[source.value] ?? [])

const sortFieldOptions = computed(() =>
  (SORT_FIELDS[source.value] ?? []).map(f => ({ value: f, label: f }))
)

function emitData(patch) {
  emit('update', { id: props.block.id, data: patch })
}

function onSourceChange(v) {
  // Clear filters when source changes — field names differ per source
  emit('update', { id: props.block.id, data: { source: v, filters: [] } })
}

function addFilter() {
  emitData({ filters: [...filters.value, { field: '', op: '=', value: '' }] })
}

function removeFilter(i) {
  const updated = filters.value.filter((_, idx) => idx !== i)
  emitData({ filters: updated })
}

function updateFilter(i, patch) {
  const updated = filters.value.map((f, idx) => idx === i ? { ...f, ...patch } : f)
  emitData({ filters: updated })
}

function toggleUrlParam(i, checked) {
  if (checked) {
    updateFilter(i, { urlParam: '', value: undefined })
  } else {
    const updated = filters.value.map((f, idx) => {
      if (idx !== i) return f
      const { urlParam, ...rest } = f
      return { ...rest, value: '' }
    })
    emitData({ filters: updated })
  }
}
</script>
