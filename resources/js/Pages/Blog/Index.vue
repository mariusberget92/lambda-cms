<script setup>
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { Link } from '@inertiajs/vue3'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

defineProps({
  posts:   Object,
  sidebar: Object,
  seo:     { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

    <!-- Main: Post list -->
    <div class="lg:col-span-2">

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-gray-400">
        <p class="text-sm">No posts published yet.</p>
      </div>

      <!-- Post list with dividers -->
      <div v-else class="divide-y divide-gray-200">
        <div v-for="post in posts.data" :key="post.id" class="py-10 first:pt-0">
          <PostCard :post="post" />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex items-center justify-center gap-3 mt-12 pt-8 border-t border-gray-200">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="text-sm transition-colors"
            :class="link.active
              ? 'font-semibold text-gray-900 underline underline-offset-2'
              : 'text-gray-500 hover:text-gray-900'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span
            v-else
            class="text-sm text-gray-300 cursor-not-allowed"
          >{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
