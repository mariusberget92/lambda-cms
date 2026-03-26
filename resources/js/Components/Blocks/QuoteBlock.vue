<!-- resources/js/Components/Blocks/QuoteBlock.vue -->
<template>
  <blockquote class="border-l-4 border-primary pl-4 my-4 italic">
    <p class="text-lg">{{ resolvedText }}</p>
    <cite v-if="resolvedAttribution" class="block mt-2 text-sm text-muted-foreground not-italic">
      — {{ resolvedAttribution }}
    </cite>
  </blockquote>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedText        = useFieldBinding(() => props.block, 'text')
const resolvedAttribution = useFieldBinding(() => props.block, 'attribution')
</script>
