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
  <div class="px-4 sm:px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2">

      <!-- Search heading card -->
      <div class="rounded-2xl p-7 mb-8 relative overflow-hidden" style="background:linear-gradient(135deg,#b48ead 0%,#88c0d0 100%); box-shadow:0 6px 20px rgba(136,192,208,0.3);">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/60 mb-1.5">Search results</p>
        <h1 class="font-editorial text-3xl font-bold text-white mb-1">
          {{ query ? `"${query}"` : 'Search' }}
        </h1>
        <p class="text-sm text-white/70">
          {{ results.total }} {{ results.total === 1 ? 'result' : 'results' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!results.data.length" class="text-center py-20" style="color:#8896b0;">
        <p class="text-sm">No posts found for your search.</p>
      </div>

      <!-- Card grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <PostCard v-for="post in results.data" :key="post.id" :post="post" />
      </div>

      <!-- Pagination -->
      <nav v-if="results.links?.length > 3" class="flex items-center justify-center gap-1 mt-10 pt-8" style="border-top:1px solid #dde3ee;">
        <template v-for="link in results.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="min-w-9 h-9 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-200"
            :style="link.active
              ? 'background:#5e81ac; color:#fff; box-shadow:0 2px 8px rgba(94,129,172,0.4);'
              : 'color:#6b7a96; background:white;'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span
            v-else
            class="min-w-9 h-9 flex items-center justify-center rounded-full text-sm cursor-not-allowed"
            style="color:#b8c4d8;"
          >{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </nav>
    </div>

    <!-- Sidebar -->
    <div class="lg:pl-2 pt-0">
      <BlogSidebar :sidebar="sidebar" :query="query" />
    </div>
  </div>
</template>
