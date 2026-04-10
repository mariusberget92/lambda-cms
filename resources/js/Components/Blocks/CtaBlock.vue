<!-- resources/js/Components/Blocks/CtaBlock.vue -->
<template>
  <div class="my-4 rounded-lg border p-6 text-center">
    <h3 v-if="resolvedHeadline" class="text-xl font-bold mb-2">{{ resolvedHeadline }}</h3>
    <p v-if="resolvedText" class="text-muted-foreground mb-4">{{ resolvedText }}</p>
    <a
      v-if="resolvedButtonUrl"
      :href="resolvedButtonUrl"
      class="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
    >
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
