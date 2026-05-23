<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  sidebar: { type: Object, required: true },
  query:   { type: String, default: '' },
})

const searchQuery = ref(props.query)
watch(() => props.query, (v) => { searchQuery.value = v })

const AURORA = ['#5e81ac','#88c0d0','#a3be8c','#ebcb8b','#d08770','#bf616a','#b48ead']

function catColor(cat) {
  if (cat?.color) return cat.color
  if (!cat?.name) return AURORA[0]
  return AURORA[[...cat.name].reduce((s, c) => s + c.charCodeAt(0), 0) % AURORA.length]
}

function hexToRgba(hex, alpha) {
  const r = parseInt(hex.slice(1,3), 16)
  const g = parseInt(hex.slice(3,5), 16)
  const b = parseInt(hex.slice(5,7), 16)
  return `rgba(${r},${g},${b},${alpha})`
}

const maxCount = (items) => Math.max(...items.map((i) => i.posts_count), 1)

function submitSearch() {
  const q = searchQuery.value.trim()
  router.get('/search', q ? { q } : {})
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <aside class="space-y-5">

    <!-- Search card -->
    <div class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 8px rgba(94,129,172,0.08), 0 1px 3px rgba(94,129,172,0.05);">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.14em] mb-3" style="color:#8896b0;">Search</h3>
      <form @submit.prevent="submitSearch" class="relative">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search posts…"
          class="w-full rounded-xl px-4 py-2.5 pr-10 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all"
          style="background:#f0f4fa; border:1.5px solid #dde3ee;"
          @focus="$event.target.style.borderColor='#5e81ac'; $event.target.style.boxShadow='0 0 0 3px rgba(94,129,172,0.15)'"
          @blur="$event.target.style.borderColor='#dde3ee'; $event.target.style.boxShadow='none'"
        />
        <button
          type="submit"
          class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors"
          style="color:#8896b0;"
          aria-label="Search"
          @mouseenter="$event.currentTarget.style.color='#5e81ac'"
          @mouseleave="$event.currentTarget.style.color='#8896b0'"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
          </svg>
        </button>
      </form>
    </div>

    <!-- Categories card -->
    <div v-if="sidebar.categories?.length" class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 8px rgba(94,129,172,0.08), 0 1px 3px rgba(94,129,172,0.05);">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.14em] mb-4" style="color:#8896b0;">Categories</h3>
      <ul class="space-y-1.5">
        <li
          v-for="cat in sidebar.categories"
          :key="cat.slug"
          class="flex items-center justify-between gap-2"
        >
          <Link
            :href="`/blog/category/${cat.slug}`"
            class="flex items-center gap-2.5 text-sm font-medium py-0.5 flex-1 min-w-0 truncate transition-colors"
            style="color:#4c566a;"
            @mouseenter="$event.currentTarget.style.color=catColor(cat)"
            @mouseleave="$event.currentTarget.style.color='#4c566a'"
          >
            <span
              class="w-2.5 h-2.5 rounded-full shrink-0"
              :style="{ backgroundColor: catColor(cat) }"
            />
            <span class="truncate">{{ cat.name }}</span>
          </Link>
          <span
            class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold text-white"
            :style="{ backgroundColor: catColor(cat) }"
          >{{ cat.posts_count > 99 ? '99+' : cat.posts_count }}</span>
        </li>
      </ul>
    </div>

    <!-- Tags card -->
    <div v-if="sidebar.tags?.length" class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 8px rgba(94,129,172,0.08), 0 1px 3px rgba(94,129,172,0.05);">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.14em] mb-4" style="color:#8896b0;">Tags</h3>
      <div class="flex flex-wrap gap-1.5">
        <Link
          v-for="(tag, idx) in sidebar.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="inline-block rounded-full px-3 py-1 font-medium transition-all duration-200"
          :style="{
            fontSize: `${Math.round((0.65 + (tag.posts_count / maxCount(sidebar.tags)) * 0.3) * 100) / 100}rem`,
            backgroundColor: hexToRgba(AURORA[idx % AURORA.length], 0.12),
            color: AURORA[idx % AURORA.length],
          }"
          @mouseenter="e => { e.currentTarget.style.backgroundColor = AURORA[idx % AURORA.length]; e.currentTarget.style.color = '#fff'; }"
          @mouseleave="e => { e.currentTarget.style.backgroundColor = hexToRgba(AURORA[idx % AURORA.length], 0.12); e.currentTarget.style.color = AURORA[idx % AURORA.length]; }"
        >{{ tag.name }}</Link>
      </div>
    </div>

    <!-- Recent posts card -->
    <div v-if="sidebar.recentPosts?.length" class="bg-white rounded-2xl p-5" style="box-shadow:0 2px 8px rgba(94,129,172,0.08), 0 1px 3px rgba(94,129,172,0.05);">
      <h3 class="text-[10px] font-bold uppercase tracking-[0.14em] mb-4" style="color:#8896b0;">Recent Posts</h3>
      <ul class="space-y-4">
        <li v-for="post in sidebar.recentPosts" :key="post.slug">
          <Link
            :href="`/blog/${post.slug}`"
            class="block text-sm font-medium leading-snug line-clamp-2 transition-colors"
            style="color:#2e3440;"
            @mouseenter="$event.currentTarget.style.color='#5e81ac'"
            @mouseleave="$event.currentTarget.style.color='#2e3440'"
          >{{ post.title }}</Link>
          <p class="text-xs mt-1" style="color:#8896b0;">{{ formatDate(post.published_at) }}</p>
        </li>
      </ul>
    </div>

  </aside>
</template>
