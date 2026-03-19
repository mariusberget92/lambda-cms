<!-- resources/js/Components/BlockRenderer.vue -->
<!-- Placeholder — full implementation in Task 15 -->
<template>
  <div class="space-y-4">
    <div
      v-for="block in blocks"
      :key="block.id"
      class="text-sm text-muted-foreground border-l-2 border-border pl-3 py-1"
    >
      [{{ block.type }}]
    </div>
  </div>
</template>

<script setup>
defineProps({ blocks: { type: Array, default: () => [] } })
</script>
