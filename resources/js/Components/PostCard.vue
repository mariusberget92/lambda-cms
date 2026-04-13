<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  post: { type: Object, required: true },
})

const readingTime = computed(() => {
  const text = props.post?.excerpt || props.post?.body || ''
  const words = text.trim().split(/\s+/).length
  return Math.ceil(words / 200)
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>

<template>
  <article>
    <!-- Featured image -->
    <div v-if="post.featured_image_url" class="mb-5">
      <Link :href="`/blog/${post.slug}`">
        <img
          :src="post.featured_image_url"
          :alt="post.title"
          class="w-full rounded-lg object-cover aspect-video"
          loading="lazy"
        />
      </Link>
    </div>

    <!-- Category -->
    <div v-if="post.categories?.length" class="mb-2 flex flex-wrap gap-2">
      <span
        v-for="cat in post.categories"
        :key="cat.slug"
        class="text-xs text-gray-500 uppercase tracking-wide"
      >{{ cat.name }}</span>
    </div>

    <!-- Title -->
    <h2 class="font-editorial text-2xl font-bold leading-snug mb-2">
      <Link :href="`/blog/${post.slug}`" class="text-gray-900 hover:underline decoration-gray-300 underline-offset-2 transition-colors">
        {{ post.title }}
      </Link>
    </h2>

    <!-- Excerpt -->
    <p v-if="post.excerpt" class="text-base text-gray-600 leading-relaxed line-clamp-3 mb-4">
      {{ post.excerpt }}
    </p>

    <!-- Meta row -->
    <div class="flex items-center gap-2">
      <img
        v-if="post.author?.avatar_url"
        :src="post.author.avatar_url"
        :alt="post.author?.name ?? 'Author'"
        class="w-6 h-6 rounded-full object-cover"
      />
      <div v-else class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-500">
        {{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}
      </div>
      <span class="text-sm text-gray-500">{{ post.author?.name ?? 'Unknown' }}</span>
      <span class="text-sm text-gray-300">·</span>
      <span class="text-sm text-gray-500">{{ formatDate(post.published_at) }}</span>
      <span class="text-sm text-gray-300">·</span>
      <span class="text-sm text-gray-500">{{ readingTime }} min read</span>
    </div>

    <!-- Tags -->
    <div v-if="post.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
      <span
        v-for="tag in post.tags"
        :key="tag.slug"
        class="text-xs border border-gray-200 rounded-full px-2.5 py-0.5 text-gray-500"
      >{{ tag.name }}</span>
    </div>
  </article>
</template>
