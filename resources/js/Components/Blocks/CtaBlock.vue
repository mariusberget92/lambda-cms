<!-- resources/js/Components/Blocks/CtaBlock.vue -->
<template>
  <div class="my-4 rounded-lg border bg-card p-6 text-center">
    <h3 v-if="resolvedHeadline" class="text-xl font-bold mb-2">{{ resolvedHeadline }}</h3>
    <p v-if="block.data.text" class="text-muted-foreground mb-4">{{ block.data.text }}</p>
    <a
      v-if="resolvedButtonUrl"
      :href="resolvedButtonUrl"
      class="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
    >
      {{ block.data.button_label || 'Learn more' }}
    </a>
  </div>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedHeadline  = useFieldBinding(() => props.block, 'headline')
const resolvedButtonUrl = useFieldBinding(() => props.block, 'button_url')
</script>
