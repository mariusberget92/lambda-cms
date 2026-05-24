<script setup>
import { inject, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)
const post     = computed(() => loopItem?.value ?? null)

const AURORA = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#f97316','#ef4444','#a855f7']

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
    :style="{ boxShadow: '0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03)' }"
    @mouseenter="$event.currentTarget.style.boxShadow = `0 20px 40px ${hexToRgba(accentColor, 0.22)}, 0 4px 12px ${hexToRgba(accentColor, 0.10)}`"
    @mouseleave="$event.currentTarget.style.boxShadow = '0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03)'"
  >

    <!-- Bold colorful header for image-less cards -->
    <div
      v-if="!post.featured_image_url"
      class="relative h-28 shrink-0 overflow-hidden"
      :style="{ background: `linear-gradient(135deg, ${accentColor} 0%, ${hexToRgba(accentColor, 0.65)} 100%)` }"
    >
      <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/20" />
      <div class="absolute right-10 -bottom-10 w-20 h-20 rounded-full bg-white/10" />
      <div class="absolute -left-4 -bottom-6 w-16 h-16 rounded-full bg-white/15" />

      <div v-if="post.categories?.length" class="absolute bottom-3.5 left-4 flex flex-wrap gap-1.5">
        <a
          v-for="cat in post.categories"
          :key="cat.slug"
          :href="`/blog/category/${cat.slug}`"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wide text-white transition-all"
          style="background:rgba(255,255,255,0.22);"
          @mouseenter="$event.currentTarget.style.background='rgba(255,255,255,0.38)'"
          @mouseleave="$event.currentTarget.style.background='rgba(255,255,255,0.22)'"
        >{{ cat.name }}</a>
      </div>
    </div>

    <!-- Featured image -->
    <div v-else class="relative overflow-hidden shrink-0">
      <Link :href="post.url" tabindex="-1" aria-hidden="true">
        <img
          :src="post.featured_image_url"
          :alt="post.title"
          class="w-full aspect-video object-cover transition-transform duration-500 group-hover:scale-[1.04]"
          loading="lazy"
        />
      </Link>
      <div class="absolute bottom-0 left-0 right-0 h-[3px]" :style="{ background: accentColor }" />
      <div v-if="post.categories?.length" class="absolute top-3 left-3 flex flex-wrap gap-1.5">
        <a
          v-for="cat in post.categories"
          :key="cat.slug"
          :href="`/blog/category/${cat.slug}`"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-85"
          :style="{ backgroundColor: catColor(cat) }"
        >{{ cat.name }}</a>
      </div>
    </div>

    <!-- Card body -->
    <div class="flex-1 flex flex-col p-5">

      <!-- Title -->
      <h2 class="font-editorial text-xl font-bold leading-snug mb-2.5">
        <Link
          :href="post.url"
          class="transition-colors duration-200"
          style="color:#1e293b;"
          @mouseenter="$event.currentTarget.style.color = accentColor"
          @mouseleave="$event.currentTarget.style.color = '#1e293b'"
        >{{ post.title }}</Link>
      </h2>

      <!-- Excerpt -->
      <p v-if="post.excerpt" class="text-sm leading-relaxed line-clamp-3 mb-4 flex-1" style="color:#64748b;">
        {{ post.excerpt }}
      </p>
      <div v-else class="flex-1" />

      <!-- Meta row -->
      <div class="flex items-center gap-2 text-xs pt-3 mt-auto" style="border-top:1px solid #f1f5f9; color:#94a3b8;">
        <div
          v-if="post.author_avatar_url"
          class="w-6 h-6 rounded-full overflow-hidden shrink-0"
          :style="{ outline: `2px solid ${hexToRgba(accentColor, 0.35)}`, outlineOffset: '1px' }"
        >
          <img :src="post.author_avatar_url" :alt="post.author_name" class="w-full h-full object-cover" />
        </div>
        <div
          v-else
          class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
          :style="{ backgroundColor: accentColor }"
        >{{ post.author_name?.charAt(0)?.toUpperCase() ?? '?' }}</div>

        <span class="font-medium truncate" style="color:#475569;">{{ post.author_name ?? 'Unknown' }}</span>
        <span class="opacity-30 select-none shrink-0">·</span>
        <span class="shrink-0">{{ post.published_at_formatted }}</span>

        <span
          class="ml-auto shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold"
          :style="{ background: hexToRgba(accentColor, 0.10), color: accentColor }"
        >{{ readingTime }} min</span>
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
          @mouseenter="e => { e.currentTarget.style.backgroundColor = AURORA[idx % AURORA.length]; e.currentTarget.style.color = '#fff'; }"
          @mouseleave="e => { e.currentTarget.style.backgroundColor = hexToRgba(AURORA[idx % AURORA.length], 0.12); e.currentTarget.style.color = AURORA[idx % AURORA.length]; }"
        >{{ tag.name }}</a>
      </div>
    </div>
  </article>
</template>
