<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Value</label>
      <input
        :value="block.data.value"
        type="text"
        placeholder="2.4K"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { value: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
      <input
        :value="block.data.label"
        type="text"
        placeholder="articles"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Trend (optional)</label>
      <input
        :value="block.data.trend"
        type="text"
        placeholder="↑ +12%"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { trend: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Trend tone</label>
      <SelectBox size="sm"
        :model-value="block.data.trendTone ?? 'neutral'"
        :data="[
          { value: 'neutral',  label: 'Neutral' },
          { value: 'positive', label: 'Positive (green)' },
          { value: 'negative', label: 'Negative (red)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { trendTone: v } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit  = defineEmits(['update'])
</script>
