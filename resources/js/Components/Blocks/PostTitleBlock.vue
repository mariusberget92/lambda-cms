<script setup>
import { inject } from 'vue'

defineProps({ block: Object })
const post = inject('postContext', null)
</script>

<template>
  <div v-if="post">
    <a href="/" class="post-title__back inline-flex items-center gap-1.5 text-sm font-medium mb-6 transition-colors">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      All posts
    </a>
    <h1 class="font-display font-bold leading-tight post-title__heading" style="font-family:'Space Grotesk',sans-serif; font-size:clamp(2rem,5vw,3.25rem); letter-spacing:-0.03em;">
      {{ post.title }}
    </h1>
    <div class="mt-4 h-px w-16 post-title__rule" />
  </div>
  <div v-else class="space-y-3">
    <div class="h-4 w-24 rounded post-title__skel" />
    <div class="h-10 rounded post-title__skel w-3/4" />
    <div class="h-px w-16 post-title__skel" />
  </div>
</template>

<style scoped>
.post-title__back  { color: var(--soft); }
.post-title__back:hover { color: var(--accent); }

.post-title__heading { color: var(--ink); }
.post-title__rule    { background: var(--accent); opacity: 0.6; }
.post-title__skel    { background: var(--line-strong); }
</style>
