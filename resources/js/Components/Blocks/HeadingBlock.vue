<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component :is="'h' + block.data.level" class="font-bold leading-tight">
    {{ block.data.text }}
  </component>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
