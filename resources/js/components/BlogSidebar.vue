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

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <aside class="space-y-10">

    <!-- Search -->
    <div>
      <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted-foreground/60 mb-4">Search</h3>
      <form @submit.prevent="submitSearch" class="relative">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search posts…"
          class="w-full rounded-lg border border-border bg-white px-3 py-2 pr-10 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
        />
        <button
          type="submit"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-primary transition-colors"
          aria-label="Search"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
          </svg>
        </button>
      </form>
    </div>

    <!-- Categories -->
    <div v-if="sidebar.categories?.length">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted-foreground/60 mb-4">Categories</h3>
      <ul class="space-y-1">
        <li
          v-for="cat in sidebar.categories"
          :key="cat.slug"
          class="flex items-center justify-between gap-2"
        >
          <Link
            :href="`/blog/category/${cat.slug}`"
            class="flex items-center gap-2 text-sm text-foreground/75 hover:text-primary transition-colors py-0.5"
          >
            <span
              class="w-2 h-2 rounded-full shrink-0 ring-1 ring-black/10"
              :style="{ backgroundColor: cat.color ?? 'var(--muted-foreground)' }"
            />
            {{ cat.name }}
          </Link>
          <span class="text-xs text-muted-foreground/50 tabular-nums shrink-0">{{ cat.posts_count }}</span>
        </li>
      </ul>
    </div>

    <!-- Tags -->
    <div v-if="sidebar.tags?.length">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted-foreground/60 mb-4">Tags</h3>
      <div class="flex flex-wrap gap-1.5">
        <Link
          v-for="tag in sidebar.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="inline-block border border-border rounded-full px-2.5 py-0.5 text-muted-foreground transition-colors hover:border-primary hover:text-primary hover:bg-primary/5"
          :style="{ fontSize: `${Math.round((0.65 + (tag.posts_count / maxCount(sidebar.tags)) * 0.3) * 100) / 100}rem` }"
        >{{ tag.name }}</Link>
      </div>
    </div>

    <!-- Recent posts -->
    <div v-if="sidebar.recentPosts?.length">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-muted-foreground/60 mb-4">Recent Posts</h3>
      <ul class="space-y-4">
        <li v-for="post in sidebar.recentPosts" :key="post.slug">
          <Link
            :href="`/blog/${post.slug}`"
            class="block text-sm font-medium text-foreground/80 hover:text-primary transition-colors line-clamp-2 leading-snug"
          >{{ post.title }}</Link>
          <p class="text-xs text-muted-foreground/60 mt-1">{{ formatDate(post.published_at) }}</p>
        </li>
      </ul>
    </div>

  </aside>
</template>
