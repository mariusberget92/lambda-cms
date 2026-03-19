<!-- resources/js/Components/Blocks/DividerBlock.vue -->
<template>
  <div class="my-6 flex items-center justify-center">
    <hr v-if="block.data.style === 'line'" class="w-full border-border" />
    <div v-else-if="block.data.style === 'dots'" class="flex gap-2 text-muted-foreground">
      <span>·</span><span>·</span><span>·</span>
    </div>
    <div v-else class="h-8" />
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
