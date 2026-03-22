<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const archive = inject('archiveContext', null)
</script>
<template>
  <component :is="block.data?.tag ?? 'h1'" v-if="archive" class="font-bold leading-tight">
    {{ archive.type === 'category' ? 'Category: ' : 'Tag: ' }}{{ archive.name }}
  </component>
  <div v-else class="h-8 rounded bg-muted/40 animate-pulse w-1/2" />
</template>
