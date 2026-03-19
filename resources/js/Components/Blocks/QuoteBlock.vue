<!-- resources/js/Components/Blocks/QuoteBlock.vue -->
<template>
  <blockquote class="border-l-4 border-primary pl-4 my-4 italic">
    <p class="text-lg">{{ block.data.text }}</p>
    <cite v-if="block.data.attribution" class="block mt-2 text-sm text-muted-foreground not-italic">
      — {{ block.data.attribution }}
    </cite>
  </blockquote>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
