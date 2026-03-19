<!-- resources/js/Components/Blocks/ParagraphBlock.vue -->
<template>
  <div class="prose prose-sm max-w-none dark:prose-invert" v-html="block.data.content" />
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
