<!-- resources/js/Components/BlockEditor/blocks/TabItemSettings.vue -->
<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
      <input
        :value="block.data.label"
        type="text"
        placeholder="Tab label..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
