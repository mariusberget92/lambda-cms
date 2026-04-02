<!-- resources/js/Components/BlockEditor/blocks/AccordionItemSettings.vue -->
<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input
        :value="block.data.title"
        type="text"
        placeholder="Item title..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
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
