<!-- resources/js/Components/BlockEditor/blocks/QuoteSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Quote text</label>
      <textarea
        :value="block.data.text"
        rows="4"
        placeholder="The quote..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Attribution (optional)</label>
      <input
        :value="block.data.attribution"
        type="text"
        placeholder="— Author name"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { attribution: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
