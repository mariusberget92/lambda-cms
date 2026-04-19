<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component :is="'h' + block.data.level" class="font-bold leading-tight">
    {{ resolvedText }}
  </component>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedText = useFieldBinding(() => props.block, 'text')
</script>
