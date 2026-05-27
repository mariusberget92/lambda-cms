<script setup>
import { inject, computed } from 'vue'

const props = defineProps({ block: Object })
const post = inject('postContext', null)

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const readingTime = computed(() => {
  if (!post?.body) return null
  const words = post.body.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.ceil(words / 200))
})
</script>

<template>
  <div v-if="post" class="post-meta-card">
    <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-3 post-meta__label">Written by</p>
    <div class="flex items-center gap-3">
      <div class="post-meta__avatar w-10 h-10 rounded-full overflow-hidden shrink-0 flex items-center justify-center text-sm font-bold">
        <img
          v-if="post.author?.avatar_url"
          :src="post.author.avatar_url"
          :alt="post.author.name"
          class="w-full h-full object-cover"
        />
        <span v-else>{{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}</span>
      </div>

      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold post-meta__name">{{ post.author?.name ?? 'Unknown' }}</p>
        <p class="font-mono-blog text-[11px] post-meta__date">{{ formatDate(post.published_at) }}</p>
      </div>

      <span v-if="readingTime" class="post-meta__badge shrink-0 font-mono-blog text-[11px] px-3 py-1 rounded-full">
        {{ readingTime }} min read
      </span>
    </div>
  </div>

  <!-- Skeleton -->
  <div v-else class="post-meta-card">
    <div class="h-2.5 w-16 rounded mb-3 post-meta__skel" />
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full post-meta__skel shrink-0" />
      <div class="space-y-1.5">
        <div class="h-3.5 w-28 rounded post-meta__skel" />
        <div class="h-2.5 w-20 rounded post-meta__skel" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.post-meta-card {
  background: var(--panel);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 1.25rem;
}
.post-meta__label  { color: var(--soft); }
.post-meta__avatar {
  background: var(--accent);
  color: var(--accent-ink);
}
.post-meta__name   { color: var(--ink); }
.post-meta__date   { color: var(--soft); }
.post-meta__badge  {
  background: var(--bg);
  color: var(--soft);
  border: 1px solid var(--line-strong);
}
.post-meta__skel   { background: var(--line-strong); }
</style>
