<!-- resources/js/Components/Blocks/PostCardBlock.vue -->
<!-- Rich post card for use inside a loop block in the template system. -->
<script setup>
import { inject, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)
const post     = computed(() => loopItem?.value ?? null)

const AURORA = ['#5e81ac','#88c0d0','#a3be8c','#ebcb8b','#d08770','#bf616a','#b48ead']

function catColor(cat) {
  if (cat?.color) return cat.color
  if (!cat?.name) return AURORA[0]
  return AURORA[[...cat.name].reduce((s, c) => s + c.charCodeAt(0), 0) % AURORA.length]
}

function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1, 3), 16)
  const g = parseInt(hex.slice(3, 5), 16)
  const b = parseInt(hex.slice(5, 7), 16)
  return `rgba(${r},${g},${b},${alpha})`
}

const primaryCat  = computed(() => post.value?.categories?.[0] ?? null)
const accentColor = computed(() => catColor(primaryCat.value))

const readingTime = computed(() => {
  const text  = post.value?.excerpt || post.value?.body || ''
  const words = text.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 200))
})
</script>

<template>
  <article
    v-if="post"
    class="group flex flex-col h-full bg-white rounded-2xl overflow-hidden transition-all duration-300 hover:-translate-y-1"
    :style="{
      borderTop: `3px solid ${accentColor}`,
      boxShadow: '0 2px 8px rgba(94,129,172,0.08), 0 1px 3px rgba(94,129,172,0.05)',
    }"
    @mouseenter="$event.currentTarget.style.boxShadow = '0 12px 28px rgba(94,129,172,0.18), 0 4px 8px rgba(94,129,172,0.08)'"
    @mouseleave="$event.currentTarget.style.boxShadow = '0 2px 8px rgba(94,129,172,0.08), 0 1px 3px rgba(94,129,172,0.05)'"
  >
    <!-- Featured image -->
    <div v-if="post.featured_image_url" class="overflow-hidden shrink-0">
      <Link :href="post.url" tabindex="-1" aria-hidden="true">
        <img
          :src="post.featured_image_url"
          :alt="post.title"
          class="w-full aspect-video object-cover transition-transform duration-500 group-hover:scale-[1.04]"
          loading="lazy"
        />
      </Link>
    </div>

    <!-- Card body -->
    <div class="flex-1 flex flex-col p-5">

      <!-- Categories -->
      <div v-if="post.categories?.length" class="mb-3 flex flex-wrap gap-1.5">
        <a
          v-for="cat in post.categories"
          :key="cat.slug"
          :href="`/blog/category/${cat.slug}`"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-85"
          :style="{ backgroundColor: catColor(cat) }"
        >{{ cat.name }}</a>
      </div>

      <!-- Title -->
      <h2 class="font-editorial text-xl font-bold leading-snug mb-2.5">
        <Link
          :href="post.url"
          class="text-foreground hover:underline decoration-primary/40 underline-offset-2 transition-colors"
        >{{ post.title }}</Link>
      </h2>

      <!-- Excerpt -->
      <p v-if="post.excerpt" class="text-sm leading-relaxed line-clamp-3 mb-4 flex-1" style="color:#6b7a96;">
        {{ post.excerpt }}
      </p>
      <div v-else class="flex-1" />

      <!-- Meta row -->
      <div class="flex items-center gap-2 text-xs pt-3 mt-auto" style="border-top:1px solid #eaeffa; color:#8896b0;">
        <div v-if="post.author_avatar_url" class="w-5 h-5 rounded-full overflow-hidden shrink-0">
          <img :src="post.author_avatar_url" :alt="post.author_name" class="w-full h-full object-cover" />
        </div>
        <div
          v-else
          class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
          :style="{ backgroundColor: accentColor }"
        >{{ post.author_name?.charAt(0)?.toUpperCase() ?? '?' }}</div>
        <span class="font-medium truncate" style="color:#4c566a;">{{ post.author_name ?? 'Unknown' }}</span>
        <span class="opacity-30 select-none shrink-0">·</span>
        <span class="shrink-0">{{ post.published_at_formatted }}</span>
        <span class="opacity-30 select-none shrink-0">·</span>
        <span class="shrink-0">{{ readingTime }} min</span>
      </div>

      <!-- Tags -->
      <div v-if="post.tags?.length" class="mt-3 flex flex-wrap gap-1">
        <a
          v-for="(tag, idx) in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="text-[11px] px-2 py-0.5 rounded-full font-medium transition-all duration-200"
          :style="{
            backgroundColor: hexToRgba(AURORA[idx % AURORA.length], 0.12),
            color: AURORA[idx % AURORA.length],
          }"
        >{{ tag.name }}</a>
      </div>
    </div>
  </article>
</template>
