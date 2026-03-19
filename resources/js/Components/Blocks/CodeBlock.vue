<!-- resources/js/Components/Blocks/CodeBlock.vue -->
<template>
  <div class="my-4 rounded-lg overflow-hidden border border-border">
    <div class="flex items-center justify-between px-3 py-1.5 bg-muted text-xs text-muted-foreground">
      <span>{{ block.data.language || 'code' }}</span>
    </div>
    <pre class="p-4 overflow-x-auto text-sm bg-muted/50"><code>{{ block.data.code }}</code></pre>
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
