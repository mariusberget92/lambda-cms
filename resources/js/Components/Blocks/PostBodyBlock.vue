<script setup>
import { inject } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
const post = inject('postContext', null)
</script>

<template>
  <div v-if="post" class="post-body-card">
    <BlockRenderer v-if="post.use_block_editor && post.blocks?.length" :blocks="post.blocks" />
    <!-- Content sanitized server-side via the post/page model before storage -->
    <div v-else class="prose prose-neutral max-w-none post-body-prose" v-html="post.body" />
  </div>
  <div v-else class="post-body-card space-y-3">
    <div class="h-4 rounded post-body__skel w-full" />
    <div class="h-4 rounded post-body__skel w-5/6" />
    <div class="h-4 rounded post-body__skel w-4/6" />
    <div class="h-4 rounded post-body__skel w-full" />
    <div class="h-4 rounded post-body__skel w-3/4" />
  </div>
</template>

<style scoped>
.post-body-card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: var(--blog-radius);
  padding: 2rem 2.5rem;
}
.post-body__skel {
  background: var(--line-strong);
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Override prose link colors to use design tokens */
.post-body-prose :deep(a) {
  color: var(--accent);
}
.post-body-prose :deep(a:hover) {
  opacity: 0.75;
}
.post-body-prose :deep(code) {
  background: var(--bg);
  color: var(--ink);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 0.15em 0.4em;
  font-size: 0.875em;
}
.post-body-prose :deep(pre) {
  background: var(--code);
  color: var(--code-ink);
  border-radius: var(--blog-radius);
}
.post-body-prose :deep(blockquote) {
  border-left-color: var(--accent);
  color: var(--soft);
}
</style>
