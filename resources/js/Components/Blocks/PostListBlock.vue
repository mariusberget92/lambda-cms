<!-- resources/js/Components/Blocks/PostListBlock.vue -->
<template>
  <div>
    <div v-if="!posts.length" class="text-sm text-muted-foreground py-4 text-center">
      No posts found.
    </div>

    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <a
        v-for="post in posts"
        :key="post.id"
        :href="`/blog/${post.slug}`"
        class="group flex flex-col rounded-lg border bg-card overflow-hidden hover:border-primary transition-colors"
      >
        <div v-if="post.featured_image_url" class="aspect-video overflow-hidden bg-muted">
          <img
            :src="post.featured_image_url"
            :alt="post.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>

        <div class="flex flex-col flex-1 p-4">
          <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary transition-colors mb-2">
            {{ post.title }}
          </h3>
          <p v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-3 flex-1">
            {{ post.excerpt }}
          </p>
          <div class="flex items-center gap-2 mt-3 text-xs text-muted-foreground">
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
