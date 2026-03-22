<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'

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
    <!-- Main -->
    <div class="lg:col-span-2">

      <!-- Search heading -->
      <div class="mb-8 pb-6 border-b">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">Search</p>
        <h1 class="text-3xl font-bold tracking-tight">
          {{ query ? `Search results for "${query}"` : 'Search' }}
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
          {{ results.total }} {{ results.total === 1 ? 'result' : 'results' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!results.data.length" class="text-center py-20 text-muted-foreground">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Post cards -->
      <div v-else class="space-y-8">
        <PostCard v-for="post in results.data" :key="post.id" :post="post" />
      </div>

      <!-- Pagination -->
      <div v-if="results.links?.length > 3" class="flex items-center justify-center gap-1 mt-10">
        <template v-for="link in results.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-3 py-1.5 text-sm rounded-md border transition-colors"
            :class="link.active
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'"
          >
            {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
          </Link>
          <span
            v-else
            class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed"
          >
            {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
          </span>
        </template>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
