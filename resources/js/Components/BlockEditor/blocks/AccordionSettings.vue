<!-- resources/js/Components/BlockEditor/blocks/AccordionSettings.vue -->
<template>
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Default state</label>
      <SelectBox
        :model-value="block.data.defaultState ?? 'first-open'"
        :data="[
          { value: 'first-open',   label: 'First item open' },
          { value: 'all-collapsed', label: 'All collapsed' },
          { value: 'all-open',     label: 'All open' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { defaultState: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border style</label>
      <SelectBox
        :model-value="block.data.borderStyle ?? 'bordered'"
        :data="[
          { value: 'bordered',   label: 'Bordered (dividers)' },
          { value: 'separated',  label: 'Separated (cards)' },
          { value: 'borderless', label: 'Borderless' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { borderStyle: v } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
