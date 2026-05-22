<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  post: { type: Object, required: true },
})

const readingTime = computed(() => {
  const text = props.post?.excerpt || props.post?.body || ''
  const words = text.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 200))
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>

<template>
  <article class="group">
    <!-- Featured image -->
    <div v-if="post.featured_image_url" class="mb-5 overflow-hidden rounded-xl">
      <Link :href="`/blog/${post.slug}`" tabindex="-1" aria-hidden="true">
        <img
          :src="post.featured_image_url"
          :alt="post.title"
          class="w-full aspect-video object-cover transition-transform duration-500 group-hover:scale-[1.03]"
          loading="lazy"
        />
      </Link>
    </div>

    <!-- Categories -->
    <div v-if="post.categories?.length" class="mb-2.5 flex flex-wrap gap-3">
      <Link
        v-for="cat in post.categories"
        :key="cat.slug"
        :href="`/blog/category/${cat.slug}`"
        class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide transition-opacity hover:opacity-70"
        :style="cat.color ? { color: cat.color } : { color: 'var(--primary)' }"
      >
        <span
          class="w-1.5 h-1.5 rounded-full shrink-0"
          :style="{ backgroundColor: cat.color ?? 'var(--primary)' }"
        />
        {{ cat.name }}
      </Link>
    </div>

    <!-- Title -->
    <h2 class="font-editorial text-2xl font-bold leading-snug mb-2.5">
      <Link
        :href="`/blog/${post.slug}`"
        class="text-foreground hover:text-primary transition-colors decoration-primary/30 underline-offset-2 hover:underline"
      >{{ post.title }}</Link>
    </h2>

    <!-- Excerpt -->
    <p v-if="post.excerpt" class="text-[0.9375rem] text-muted-foreground leading-relaxed line-clamp-3 mb-4">
      {{ post.excerpt }}
    </p>

    <!-- Meta row -->
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
      <img
        v-if="post.author?.avatar_url"
        :src="post.author.avatar_url"
        :alt="post.author?.name ?? 'Author'"
        class="w-6 h-6 rounded-full object-cover shrink-0"
      />
      <div v-else class="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-xs font-semibold text-muted-foreground shrink-0">
        {{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}
      </div>
      <span class="font-medium text-foreground/80">{{ post.author?.name ?? 'Unknown' }}</span>
      <span class="text-muted-foreground/30 select-none">·</span>
      <span>{{ formatDate(post.published_at) }}</span>
      <span class="text-muted-foreground/30 select-none">·</span>
      <span>{{ readingTime }} min read</span>
    </div>

    <!-- Tags -->
    <div v-if="post.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
      <Link
        v-for="tag in post.tags"
        :key="tag.slug"
        :href="`/blog/tag/${tag.slug}`"
        class="text-xs border border-border rounded-full px-2.5 py-0.5 text-muted-foreground transition-colors hover:border-primary hover:text-primary hover:bg-primary/5"
      >{{ tag.name }}</Link>
    </div>
  </article>
</template>
