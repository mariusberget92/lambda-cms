<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  post: { type: Object, required: true },
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function categoryStyle(cat) {
  return cat.color ? { backgroundColor: cat.color + '20', color: cat.color } : {}
}
function categoryClass(cat) {
  return cat.color ? '' : 'bg-primary/10 text-primary'
}
</script>

<template>
  <article class="border rounded-xl overflow-hidden bg-card hover:shadow-sm transition-shadow">
    <!-- Featured image -->
    <div v-if="post.featured_image_url" class="w-full h-48 overflow-hidden">
      <img
        :src="post.featured_image_url"
        :alt="post.title"
        class="w-full h-full object-cover"
        loading="lazy"
      />
    </div>

    <div class="p-6">
      <!-- Category badges -->
      <div v-if="post.categories?.length" class="mb-2 flex flex-wrap gap-1">
        <span
          v-for="cat in post.categories"
          :key="cat.slug"
          :class="['inline-block text-xs font-medium px-2 py-0.5 rounded-full', categoryClass(cat)]"
          :style="categoryStyle(cat)"
        >
          {{ cat.name }}
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
          <img
            v-if="post.author.avatar_url"
            :src="post.author.avatar_url"
            :alt="post.author.name"
            class="w-6 h-6 rounded-full object-cover"
          />
          <div
            v-else
            class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-semibold text-primary"
          >
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
    </div>
  </article>
</template>
