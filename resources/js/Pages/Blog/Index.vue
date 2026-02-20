<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'

defineOptions({ layout: BlogLayout })

defineProps({
  posts: Object,
  sidebar: Object,
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>

<template>
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
        <article
          v-for="post in posts.data"
          :key="post.id"
          class="border rounded-xl p-6 bg-card hover:shadow-sm transition-shadow"
        >
          <!-- Category badge -->
          <div v-if="post.category" class="mb-2">
            <span class="inline-block text-xs font-medium bg-primary/10 text-primary px-2 py-0.5 rounded-full">
              {{ post.category.name }}
            </span>
          </div>

          <!-- Title -->
          <h2 class="text-xl font-semibold leading-tight mb-2">
            <Link :href="`/blog/${post.slug}`" class="hover:text-primary transition-colors">
              {{ post.title }}
            </Link>
          </h2>

          <!-- Excerpt -->
          <p v-if="post.excerpt" class="text-sm text-muted-foreground mb-4 line-clamp-3">
            {{ post.excerpt }}
          </p>

          <!-- Meta row -->
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
              <!-- Author avatar -->
              <img
                v-if="post.author.avatar_url"
                :src="post.author.avatar_url"
                :alt="post.author.name"
                class="w-6 h-6 rounded-full object-cover"
              />
              <div v-else class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-semibold text-primary">
                {{ post.author.name.charAt(0).toUpperCase() }}
              </div>
              <span class="text-xs text-muted-foreground">{{ post.author.name }}</span>
              <span class="text-xs text-muted-foreground">·</span>
              <span class="text-xs text-muted-foreground">{{ formatDate(post.published_at) }}</span>
            </div>

            <Link
              :href="`/blog/${post.slug}`"
              class="text-xs font-medium text-primary hover:underline"
            >
              Read more →
            </Link>
          </div>

          <!-- Tags -->
          <div v-if="post.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
            <span
              v-for="tag in post.tags"
              :key="tag.slug"
              class="text-xs border rounded-full px-2 py-0.5 text-muted-foreground"
            >
              {{ tag.name }}
            </span>
          </div>
        </article>
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
