<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Section number</label>
      <input
        :value="block.data.number"
        type="text"
        placeholder="01"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
        @input="emit('update', { id: block.id, data: { number: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input
        :value="block.data.title"
        type="text"
        placeholder="Section title"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Meta text (right of rule)</label>
      <input
        :value="block.data.meta"
        type="text"
        placeholder="Latest posts"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
        @input="emit('update', { id: block.id, data: { meta: $event.target.value } })"
      />
    </div>
    <div class="flex items-center gap-2">
      <input
        type="checkbox"
        id="sh-show-rule"
        :checked="block.data.showRule ?? false"
        @change="emit('update', { id: block.id, data: { showRule: $event.target.checked } })"
      />
      <label for="sh-show-rule" class="text-xs text-muted-foreground">Show accent rule below title</label>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit  = defineEmits(['update'])
</script>
