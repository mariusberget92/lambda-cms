<!-- resources/js/Components/BlockEditor/blocks/HeadingSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Level</label>
      <select
        :value="block.data.level"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { level: Number($event.target.value) } })"
      >
        <option v-for="n in 6" :key="n" :value="n">H{{ n }}</option>
      </select>
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text</label>
      <input
        :value="block.data.text"
        type="text"
        placeholder="Heading text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
