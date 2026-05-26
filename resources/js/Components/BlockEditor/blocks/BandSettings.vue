<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Section label</label>
      <input
        :value="block.data.label"
        type="text"
        placeholder="All Topics"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max tags to show</label>
      <input
        :value="block.data.limit ?? 30"
        type="number"
        min="1" max="100"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { limit: Number($event.target.value) } })"
      />
    </div>
    <div class="flex items-center gap-2">
      <input
        type="checkbox"
        id="band-show-count"
        :checked="block.data.showCount ?? false"
        @change="emit('update', { id: block.id, data: { showCount: $event.target.checked } })"
      />
      <label for="band-show-count" class="text-xs text-muted-foreground">Show post count next to each tag</label>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit  = defineEmits(['update'])
</script>
