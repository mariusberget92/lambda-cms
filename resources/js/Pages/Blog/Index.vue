<script setup>
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { Link } from '@inertiajs/vue3'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

defineProps({
  posts:  Object,
  sidebar: Object,
  seo:    { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <!-- Main: Post list -->
    <div class="lg:col-span-2">
      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-muted-foreground">
        <svg class="mx-auto mb-4 w-10 h-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">No posts published yet.</p>
      </div>

      <!-- Post cards -->
      <div v-else class="space-y-8">
        <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
      </div>

      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex items-center justify-center gap-1 mt-10">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-3 py-1.5 text-sm rounded-md border transition-colors"
            :class="link.active
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'"
          >
            {{ decodeHtmlEntities(link.label) }}
          </Link>
          <span
            v-else
            class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed"
          >
            {{ decodeHtmlEntities(link.label) }}
          </span>
        </template>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
