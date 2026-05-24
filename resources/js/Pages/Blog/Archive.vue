<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

defineProps({
  posts:   Object,
  sidebar: Object,
  seo:     { type: Object, required: true },
  heading: { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="px-4 sm:px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2">

      <!-- Archive heading card -->
      <div class="rounded-2xl p-7 mb-8 overflow-hidden relative" style="background:linear-gradient(135deg,#6366f1 0%,#818cf8 50%,#0ea5e9 100%); box-shadow:0 6px 24px rgba(99,102,241,0.35);">
        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/60 mb-1.5">
          {{ heading.type === 'category' ? 'Category' : 'Tag' }}
        </p>
        <h1 class="font-editorial text-3xl font-bold text-white mb-1">{{ heading.name }}</h1>
        <p class="text-sm text-white/70">
          {{ heading.postsCount }} {{ heading.postsCount === 1 ? 'post' : 'posts' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20" style="color:#94a3b8;">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Card grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
      </div>

      <!-- Pagination -->
      <nav v-if="posts.links?.length > 3" class="flex items-center justify-center gap-1 mt-10 pt-8" style="border-top:1px solid #e2e8f0;">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="min-w-9 h-9 flex items-center justify-center rounded-full text-sm font-medium transition-all duration-200"
            :style="link.active
              ? 'background:#6366f1; color:#fff; box-shadow:0 2px 8px rgba(99,102,241,0.4);'
              : 'color:#64748b; background:white;'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span
            v-else
            class="min-w-9 h-9 flex items-center justify-center rounded-full text-sm cursor-not-allowed"
            style="color:#cbd5e1;"
          >{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </nav>
    </div>

    <!-- Sidebar -->
    <div class="lg:pl-2 pt-0">
      <BlogSidebar :sidebar="sidebar" />
    </div>
  </div>
</template>
