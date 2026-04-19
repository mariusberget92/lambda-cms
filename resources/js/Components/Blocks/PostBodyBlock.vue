<script setup>
import { inject } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
const post = inject('postContext', null)
</script>
<template>
  <div v-if="post">
    <BlockRenderer v-if="post.use_block_editor && post.blocks?.length" :blocks="post.blocks" />
    <!-- Content sanitized server-side via the post/page model before storage -->
    <div v-else class="prose dark:prose-invert max-w-none" v-html="post.body" />
  </div>
  <div v-else class="space-y-2">
    <div class="h-4 rounded bg-muted/40 animate-pulse w-full" />
    <div class="h-4 rounded bg-muted/40 animate-pulse w-5/6" />
    <div class="h-4 rounded bg-muted/40 animate-pulse w-4/6" />
  </div>
</template>
