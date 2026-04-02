<!-- resources/js/Components/BlockEditor/blocks/TabsSettings.vue -->
<template>
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Tab style</label>
      <SelectBox
        :model-value="block.data.tabStyle ?? 'underline'"
        :data="[
          { value: 'underline', label: 'Underline' },
          { value: 'pills',     label: 'Pills' },
          { value: 'buttons',   label: 'Buttons' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { tabStyle: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex gap-1">
        <button
          v-for="al in ['left', 'center', 'right']"
          :key="al"
          type="button"
          class="flex-1 px-2 py-1 text-xs rounded border capitalize transition-colors"
          :class="(block.data.alignment ?? 'left') === al
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background border-border hover:border-muted-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: al } })"
        >{{ al }}</button>
      </div>
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
