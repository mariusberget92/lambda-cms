<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  sidebar: { type: Object, required: true },
  query:   { type: String, default: '' },
})

const searchQuery = ref(props.query)
watch(() => props.query, (v) => { searchQuery.value = v })

const maxCount = (items) => Math.max(...items.map((i) => i.posts_count), 1)

function submitSearch() {
  const q = searchQuery.value.trim()
  router.get('/search', q ? { q } : {})
}
</script>

<template>
  <aside class="space-y-10">

    <!-- Search -->
    <div>
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Search</h3>
      <form @submit.prevent="submitSearch" class="flex gap-2 items-end">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search posts…"
          class="flex-1 min-w-0 border-b border-gray-300 bg-transparent py-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900"
        />
        <button
          type="submit"
          class="text-sm text-gray-500 hover:text-gray-900 transition-colors pb-1"
        >Go</button>
      </form>
    </div>

    <!-- Categories -->
    <div v-if="sidebar.categories?.length">
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Categories</h3>
      <ul class="space-y-2">
        <li
          v-for="cat in sidebar.categories"
          :key="cat.slug"
          class="flex items-center justify-between"
        >
          <Link
            :href="`/blog/category/${cat.slug}`"
            class="text-sm text-gray-700 hover:text-gray-900 transition-colors"
          >{{ cat.name }}</Link>
          <span class="text-xs text-gray-400">{{ cat.posts_count }}</span>
        </li>
      </ul>
    </div>

    <!-- Tags -->
    <div v-if="sidebar.tags?.length">
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Tags</h3>
      <div class="flex flex-wrap gap-2">
        <Link
          v-for="tag in sidebar.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="border border-gray-200 rounded-full px-2.5 py-0.5 text-gray-600 transition-colors hover:bg-gray-900 hover:text-white hover:border-gray-900"
          :style="{ fontSize: `${0.65 + (tag.posts_count / maxCount(sidebar.tags)) * 0.35}rem` }"
        >{{ tag.name }}</Link>
      </div>
    </div>

    <!-- Recent posts -->
    <div v-if="sidebar.recentPosts?.length">
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Recent Posts</h3>
      <ul class="space-y-3">
        <li v-for="post in sidebar.recentPosts" :key="post.slug">
          <Link
            :href="`/blog/${post.slug}`"
            class="text-sm text-gray-700 hover:text-gray-900 transition-colors line-clamp-2"
          >{{ post.title }}</Link>
          <p class="text-xs text-gray-400 mt-0.5">{{ post.published_at }}</p>
        </li>
      </ul>
    </div>

  </aside>
</template>
