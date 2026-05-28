<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>

<template>
  <div v-if="post" class="flex items-center gap-3">
    <img v-if="props.block.data?.showAvatar !== false && post.author?.avatar_url"
      :src="post.author.avatar_url" :alt="post.author.name"
      class="author-avatar rounded-full object-cover" />
    <div v-else-if="props.block.data?.showAvatar !== false"
      class="author-avatar author-avatar--initials rounded-full flex items-center justify-center text-sm font-semibold">
      {{ post.author?.name?.[0] ?? '?' }}
    </div>
    <p class="author-name font-medium text-sm">{{ post.author?.name }}</p>
  </div>
  <div v-else class="flex items-center gap-3">
    <div class="author-skel author-skel--avatar rounded-full animate-pulse" />
    <div class="author-skel author-skel--name rounded animate-pulse" />
  </div>
</template>

<style scoped>
.author-avatar { width: 2.5rem; height: 2.5rem; }
.author-avatar--initials { background: var(--bg); color: var(--soft); border: 1px solid var(--line-strong); }
.author-name { color: var(--ink); }
.author-skel { background: var(--line-strong); }
.author-skel--avatar { width: 2.5rem; height: 2.5rem; }
.author-skel--name { height: 1rem; width: 8rem; }
</style>
