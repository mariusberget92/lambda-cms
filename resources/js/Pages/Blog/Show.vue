<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'

defineOptions({ layout: BlogLayout })

defineProps({
  post: Object,
  sidebar: Object,
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <!-- Main: Post content -->
    <div class="lg:col-span-2">
      <!-- Back link -->
      <Link href="/" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back to posts
      </Link>

      <!-- Category badge -->
      <div v-if="post.category" class="mb-3">
        <span class="inline-block text-xs font-medium bg-primary/10 text-primary px-2 py-0.5 rounded-full">
          {{ post.category.name }}
        </span>
      </div>

      <!-- Title -->
      <h1 class="text-3xl font-bold tracking-tight leading-tight mb-4">{{ post.title }}</h1>

      <!-- Featured image hero -->
      <div v-if="post.featured_image_url" class="mb-8">
        <img
          :src="post.featured_image_url"
          :alt="post.featured_image_alt ?? post.title"
          class="w-full rounded-xl object-cover max-h-96"
        />
      </div>

      <!-- Author + date row -->
      <div class="flex items-center gap-3 mb-8 pb-8 border-b">
        <img
          v-if="post.author.avatar_url"
          :src="post.author.avatar_url"
          :alt="post.author.name"
          class="w-9 h-9 rounded-full object-cover"
        />
        <div v-else class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center text-sm font-semibold text-primary">
          {{ post.author.name.charAt(0).toUpperCase() }}
        </div>
        <div>
          <p class="text-sm font-medium">{{ post.author.name }}</p>
          <p class="text-xs text-muted-foreground">{{ formatDate(post.published_at) }}</p>
        </div>
      </div>

      <!-- Post body — safe: admin-authored Tiptap HTML -->
      <div
        class="prose prose-sm max-w-none dark:prose-invert"
        v-html="post.body"
      />

      <!-- Tags -->
      <div v-if="post.tags?.length" class="mt-8 pt-6 border-t flex flex-wrap gap-2">
        <span
          v-for="tag in post.tags"
          :key="tag.slug"
          class="text-xs border rounded-full px-2.5 py-0.5 text-muted-foreground"
        >
          {{ tag.name }}
        </span>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
