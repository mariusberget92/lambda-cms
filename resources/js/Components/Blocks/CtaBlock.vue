<template>
  <div class="cta-block">
    <h3 v-if="resolvedHeadline" class="cta-headline">{{ resolvedHeadline }}</h3>
    <p v-if="resolvedText" class="cta-text">{{ resolvedText }}</p>
    <a v-if="resolvedButtonUrl" :href="resolvedButtonUrl" class="cta-btn">
      {{ resolvedButtonLabel || 'Learn more' }}
    </a>
  </div>
</template>

<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedHeadline    = useFieldBinding(() => props.block, 'headline')
const resolvedText        = useFieldBinding(() => props.block, 'text')
const resolvedButtonUrl   = useFieldBinding(() => props.block, 'button_url')
const resolvedButtonLabel = useFieldBinding(() => props.block, 'button_label')
</script>

<style scoped>
.cta-block {
  margin: 1rem 0;
  border-radius: var(--blog-radius);
  border: 1px solid var(--line-strong);
  padding: 1.5rem;
  text-align: center;
  background: var(--panel);
}
.cta-headline { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--ink); }
.cta-text { color: var(--soft); margin-bottom: 1rem; }
.cta-btn {
  display: inline-flex;
  align-items: center;
  border-radius: var(--blog-radius);
  background: var(--accent);
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--accent-ink);
  transition: opacity 150ms;
  text-decoration: none;
}
.cta-btn:hover { opacity: 0.85; }
</style>
