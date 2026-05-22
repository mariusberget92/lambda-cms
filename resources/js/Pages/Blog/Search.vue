<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

defineProps({
  query:   { type: String, default: '' },
  results: { type: Object, required: true },
  sidebar: { type: Object, default: () => ({}) },
  seo:     { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2">

      <!-- Search heading -->
      <div class="mb-10">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Search</p>
        <h1 class="font-editorial text-3xl font-bold text-gray-900">
          {{ query ? `Results for "${query}"` : 'Search' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ results.total }} {{ results.total === 1 ? 'result' : 'results' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!results.data.length" class="text-center py-20 text-gray-400">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Post list with dividers -->
      <div v-else class="divide-y divide-gray-200">
        <div v-for="post in results.data" :key="post.id" class="py-10 first:pt-0">
          <PostCard :post="post" />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="results.links?.length > 3" class="flex items-center justify-center gap-3 mt-12 pt-8 border-t border-gray-200">
        <template v-for="link in results.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="text-sm transition-colors"
            :class="link.active ? 'font-semibold text-gray-900 underline underline-offset-2' : 'text-gray-500 hover:text-gray-900'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span v-else class="text-sm text-gray-300 cursor-not-allowed">{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </div>
    </div>

    <BlogSidebar :sidebar="sidebar" :query="query" />
  </div>
</template>
