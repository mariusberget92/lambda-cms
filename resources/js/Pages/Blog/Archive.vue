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
  <div class="py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
    <div class="lg:col-span-2">

      <!-- Archive heading -->
      <div class="mb-10 pb-8 border-b border-border">
        <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-primary/70 mb-2">
          {{ heading.type === 'category' ? 'Category' : 'Tag' }}
        </p>
        <h1 class="font-editorial text-4xl font-bold text-foreground">{{ heading.name }}</h1>
        <p class="text-sm text-muted-foreground mt-2">
          {{ heading.postsCount }} {{ heading.postsCount === 1 ? 'post' : 'posts' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-muted-foreground">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Post list -->
      <div v-else class="divide-y divide-border">
        <div v-for="post in posts.data" :key="post.id" class="py-10 first:pt-0">
          <PostCard :post="post" />
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="posts.links?.length > 3" class="flex items-center justify-center gap-1 mt-12 pt-8 border-t border-border">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="min-w-9 h-9 flex items-center justify-center rounded-md text-sm transition-colors"
            :class="link.active
              ? 'bg-primary text-primary-foreground font-semibold'
              : 'text-muted-foreground hover:text-foreground hover:bg-muted'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span
            v-else
            class="min-w-9 h-9 flex items-center justify-center rounded-md text-sm text-muted-foreground/30 cursor-not-allowed"
          >{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </nav>
    </div>

    <!-- Sidebar -->
    <div class="lg:border-l lg:border-border lg:pl-12 pt-4 lg:pt-0">
      <BlogSidebar :sidebar="sidebar" />
    </div>
  </div>
</template>
