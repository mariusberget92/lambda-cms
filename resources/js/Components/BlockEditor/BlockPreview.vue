<!-- resources/js/Components/BlockEditor/BlockPreview.vue -->
<template>
  <div class="flex-1 overflow-y-auto border-r bg-background">
    <div class="p-4 prose prose-sm max-w-none dark:prose-invert">
      <p v-if="!blocks.length" class="text-muted-foreground text-sm text-center py-8">
        No blocks yet — add one from the left panel.
      </p>
      <BlockRenderer v-else :blocks="blocks" />
    </div>
  </div>
</template>

<script setup>
import BlockRenderer from '@/Components/BlockRenderer.vue'

defineProps({
  blocks: { type: Array, default: () => [] },
})
</script>
