<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>
<template>
  <component :is="block.data?.tag ?? 'h1'" v-if="post" class="font-bold leading-tight">{{ post.title }}</component>
  <div v-else class="h-8 rounded bg-muted/40 animate-pulse w-2/3" />
</template>
