<!-- resources/js/Components/Blocks/HtmlBlock.vue -->
<template>
  <div v-html="block.data.content" />
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
