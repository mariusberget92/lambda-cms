<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  sidebar: {
    type: Object,
    required: true,
  },
})

const maxCount = (items) => Math.max(...items.map((i) => i.posts_count), 1)
</script>

<template>
  <aside class="space-y-8">
    <!-- Categories -->
    <div v-if="sidebar.categories?.length">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Categories</h3>
      <ul class="space-y-1.5">
        <li
          v-for="cat in sidebar.categories"
          :key="cat.slug"
          class="flex items-center justify-between"
        >
          <Link
            :href="`/?category=${cat.slug}`"
            class="text-sm hover:text-primary transition-colors"
          >
            {{ cat.name }}
          </Link>
          <span class="text-xs text-muted-foreground bg-muted px-1.5 py-0.5 rounded-full">
            {{ cat.posts_count }}
          </span>
        </li>
      </ul>
    </div>

    <!-- Tags cloud -->
    <div v-if="sidebar.tags?.length">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Tags</h3>
      <div class="flex flex-wrap gap-1.5">
        <Link
          v-for="tag in sidebar.tags"
          :key="tag.slug"
          :href="`/?tag=${tag.slug}`"
          class="inline-block rounded-full border px-2.5 py-0.5 text-muted-foreground hover:text-foreground hover:border-foreground transition-colors"
          :style="{ fontSize: `${0.65 + (tag.posts_count / maxCount(sidebar.tags)) * 0.35}rem` }"
        >
          {{ tag.name }}
        </Link>
      </div>
    </div>

    <!-- Recent posts -->
    <div v-if="sidebar.recentPosts?.length">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Recent Posts</h3>
      <ul class="space-y-2">
        <li v-for="post in sidebar.recentPosts" :key="post.slug">
          <Link
            :href="`/blog/${post.slug}`"
            class="text-sm hover:text-primary transition-colors line-clamp-2"
          >
            {{ post.title }}
          </Link>
          <p class="text-xs text-muted-foreground mt-0.5">{{ post.published_at }}</p>
        </li>
      </ul>
    </div>
  </aside>
</template>
