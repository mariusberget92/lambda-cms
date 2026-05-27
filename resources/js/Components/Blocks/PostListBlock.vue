<template>
  <div>
    <p v-if="!posts.length" class="post-list-empty">No posts found.</p>

    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <a
        v-for="post in posts"
        :key="post.id"
        :href="`/blog/${post.slug}`"
        class="post-list-card group flex flex-col"
      >
        <div v-if="post.featured_image_url" class="post-list-card__image">
          <img
            :src="post.featured_image_url"
            :alt="post.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>

        <div class="flex flex-col flex-1 p-4">
          <h3 class="post-list-card__title font-semibold text-sm line-clamp-2 mb-2">
            {{ post.title }}
          </h3>
          <p v-if="post.excerpt" class="post-list-card__excerpt text-xs line-clamp-3 flex-1">
            {{ post.excerpt }}
          </p>
          <div class="post-list-card__meta flex items-center gap-2 mt-3 text-xs">
            <span>{{ post.author_name }}</span>
            <span>·</span>
            <span>{{ formatDate(post.published_at) }}</span>
          </div>
        </div>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  block: { type: Object, required: true },
})

const posts = computed(() => props.block.data?.resolved?.posts ?? [])

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('en-GB', {
    day: 'numeric', month: 'short', year: 'numeric',
  })
}
</script>

<style scoped>
.post-list-empty { font-size: 0.875rem; color: var(--soft); padding: 1rem 0; text-align: center; }

.post-list-card {
  border-radius: var(--blog-radius);
  border: 1px solid var(--line-strong);
  background: var(--panel);
  overflow: hidden;
  transition: border-color 150ms;
  text-decoration: none;
}
.post-list-card:hover { border-color: var(--accent); }

.post-list-card__image {
  aspect-ratio: 16/9;
  overflow: hidden;
  background: var(--bg);
}

.post-list-card__title { color: var(--ink); transition: color 150ms; }
.post-list-card:hover .post-list-card__title { color: var(--accent); }

.post-list-card__excerpt { color: var(--soft); }
.post-list-card__meta { color: var(--soft); border-top: 1px solid var(--line); padding-top: 0.75rem; }
</style>
