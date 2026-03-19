<!-- resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue -->
<template>
  <div>
    <label class="text-xs font-medium text-muted-foreground block mb-1">Content</label>
    <TiptapEditor
      :model-value="block.data.content"
      @update:model-value="emit('update', { id: block.id, data: { content: $event } })"
    />
  </div>
</template>

<script setup>
import TiptapEditor from '@/Components/TiptapEditor.vue'
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
