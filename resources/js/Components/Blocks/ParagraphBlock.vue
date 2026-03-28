<!-- resources/js/Components/Blocks/ParagraphBlock.vue -->
<template>
  <!-- Content sanitized server-side via the post/page model before storage -->
  <div class="prose prose-sm max-w-none dark:prose-invert" v-html="resolvedContent" />
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedContent = useFieldBinding(() => props.block, 'content')
</script>
