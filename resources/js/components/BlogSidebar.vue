<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  sidebar: {
    type: Object,
    required: true,
  },
  query: {
    type: String,
    default: '',
  },
})

const searchQuery = ref(props.query)

const maxCount = (items) => Math.max(...items.map((i) => i.posts_count), 1)

function submitSearch() {
  const q = searchQuery.value.trim()
  router.get('/search', q ? { q } : {})
}
</script>

<template>
  <aside class="space-y-8">
    <!-- Search -->
    <div>
      <h3 class="text-xs font-semibold uppercase tracking-wider text-primary mb-3 flex items-center gap-2">
        <span class="w-1 h-3.5 bg-primary rounded-full flex-shrink-0"></span>
        <span class="bg-primary/10 px-2 py-0.5 rounded-md">Search</span>
      </h3>
      <form @submit.prevent="submitSearch" class="flex gap-2">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search posts…"
          class="flex-1 min-w-0 h-8 rounded-md border bg-background px-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary"
        />
        <button
          type="submit"
          class="h-8 px-3 rounded-md bg-primary text-primary-foreground text-sm hover:bg-primary/90 transition-colors"
        >Go</button>
      </form>
    </div>
    <!-- Categories -->
    <div v-if="sidebar.categories?.length">
      <h3 class="text-xs font-semibold uppercase tracking-wider text-primary mb-3 flex items-center gap-2">
        <span class="w-1 h-3.5 bg-primary rounded-full flex-shrink-0"></span>
        <span class="bg-primary/10 px-2 py-0.5 rounded-md">Categories</span>
      </h3>
      <ul class="space-y-1.5">
        <li
          v-for="cat in sidebar.categories"
          :key="cat.slug"
          class="flex items-center justify-between rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-primary/5"
        >
          <Link
            :href="`/blog/category/${cat.slug}`"
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
      <h3 class="text-xs font-semibold uppercase tracking-wider text-primary mb-3 flex items-center gap-2">
        <span class="w-1 h-3.5 bg-primary rounded-full flex-shrink-0"></span>
        <span class="bg-primary/10 px-2 py-0.5 rounded-md">Tags</span>
      </h3>
      <div class="flex flex-wrap gap-1.5">
        <Link
          v-for="tag in sidebar.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="inline-block rounded-full border px-2.5 py-0.5 text-muted-foreground transition-colors duration-200 hover:bg-primary hover:text-primary-foreground hover:border-primary"
          :style="{ fontSize: `${0.65 + (tag.posts_count / maxCount(sidebar.tags)) * 0.35}rem` }"
        >
          {{ tag.name }}
        </Link>
      </div>
    </div>

    <!-- Recent posts -->
    <div v-if="sidebar.recentPosts?.length">
      <h3 class="text-xs font-semibold uppercase tracking-wider text-primary mb-3 flex items-center gap-2">
        <span class="w-1 h-3.5 bg-primary rounded-full flex-shrink-0"></span>
        <span class="bg-primary/10 px-2 py-0.5 rounded-md">Recent Posts</span>
      </h3>
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
