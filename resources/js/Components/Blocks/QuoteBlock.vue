<template>
  <blockquote class="quote-block">
    <p class="quote-text">{{ resolvedText }}</p>
    <cite v-if="resolvedAttribution" class="quote-cite">— {{ resolvedAttribution }}</cite>
  </blockquote>
</template>

<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedText        = useFieldBinding(() => props.block, 'text')
const resolvedAttribution = useFieldBinding(() => props.block, 'attribution')
</script>

<style scoped>
.quote-block {
  border-left: 4px solid var(--accent);
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
}
.quote-text { font-size: 1.125rem; color: var(--ink); }
.quote-cite { display: block; margin-top: 0.5rem; font-size: 0.875rem; color: var(--soft); font-style: normal; }
</style>
