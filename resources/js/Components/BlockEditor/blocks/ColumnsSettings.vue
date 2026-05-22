<!-- resources/js/Components/BlockEditor/blocks/ColumnsSettings.vue -->
<!-- Columns is a pre-configured ContainerBlock with column count and gap controls -->
<template>
  <div v-show="!tab || tab === 'content'" class="space-y-4">
    <!-- Column count -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Columns</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="n in [2, 3, 4]"
          :key="n"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="columnCount === n ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="setColumns(n)"
        >{{ n }}</button>
      </div>
    </div>

    <!-- Gap -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
      <SelectBox size="sm"
        :model-value="block.data.gap ?? '1rem'"
        :data="[
          { value: '0',      label: 'None' },
          { value: '0.5rem', label: 'XS (0.5rem)' },
          { value: '1rem',   label: 'SM (1rem)' },
          { value: '1.5rem', label: 'MD (1.5rem)' },
          { value: '2rem',   label: 'LG (2rem)' },
          { value: '3rem',   label: 'XL (3rem)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { gap: v } })"
      />
    </div>

    <!-- Vertical align -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Vertical align</label>
      <SelectBox size="sm"
        :model-value="block.data.align ?? 'start'"
        :data="[
          { value: 'start',   label: 'Top' },
          { value: 'center',  label: 'Center' },
          { value: 'end',     label: 'Bottom' },
          { value: 'stretch', label: 'Stretch' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { align: v } })"
      />
    </div>

    <!-- Stack on mobile -->
    <div class="flex items-center gap-2">
      <EditorCheckbox
        :model-value="block.data.wrap ?? true"
        @update:model-value="v => emit('update', { id: block.id, data: { wrap: v } })"
      />
      <label class="text-xs text-muted-foreground">Stack on small screens</label>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox      from '@/Components/SelectBox.vue'
import EditorCheckbox from '../EditorCheckbox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const columnCount = computed(() => {
  const cols = props.block.data?.columns
  if (cols) return parseInt(cols)
  // Derive from existing children if columns field not set
  return props.block.children?.length ?? 2
})

function setColumns(n) {
  emit('update', { id: props.block.id, data: { columns: String(n), direction: 'row' } })
}
</script>
