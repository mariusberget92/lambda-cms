<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  posts:   Object,
  sidebar: Object,
  seo:     { type: Object, required: true },
})

const AURORA = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#f97316','#ef4444','#a855f7']

function catColor(cat) {
  if (cat?.color) return cat.color
  if (!cat?.name) return AURORA[0]
  return AURORA[[...cat.name].reduce((s, c) => s + c.charCodeAt(0), 0) % AURORA.length]
}

const heroPost      = computed(() => props.posts?.data?.[0] ?? null)
const remainingPosts = computed(() => props.posts?.data?.slice(1) ?? [])
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="px-4 sm:px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">

    <!-- Main: Post list -->
    <div class="lg:col-span-2">

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="flex flex-col items-center justify-center py-24 text-center gap-3">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mb-2" style="background:rgba(99,102,241,0.1);">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#6366f1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
        </div>
        <p class="text-base font-medium" style="color:#475569;">No posts published yet.</p>
        <p class="text-sm" style="color:#94a3b8;">Check back soon for new content.</p>
      </div>

      <template v-else>
        <!-- Hero post (first post, prominent) -->
        <article
          v-if="heroPost"
          class="group relative mb-8 rounded-2xl overflow-hidden cursor-pointer transition-all duration-300 hover:-translate-y-0.5"
          style="box-shadow:0 4px 24px rgba(0,0,0,0.10), 0 1px 4px rgba(0,0,0,0.06);"
          @mouseenter="$event.currentTarget.style.boxShadow='0 20px 40px rgba(0,0,0,0.16), 0 4px 8px rgba(0,0,0,0.08)'"
          @mouseleave="$event.currentTarget.style.boxShadow='0 4px 24px rgba(0,0,0,0.10), 0 1px 4px rgba(0,0,0,0.06)'"
        >
          <Link :href="`/blog/${heroPost.slug}`" class="block">
            <!-- Image with gradient overlay -->
            <div class="relative overflow-hidden" :class="heroPost.featured_image_url ? 'aspect-[21/9]' : 'aspect-[21/9]'">
              <img
                v-if="heroPost.featured_image_url"
                :src="heroPost.featured_image_url"
                :alt="heroPost.title"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
              />
              <!-- Gradient background fallback -->
              <div
                v-else
                class="w-full h-full"
                :style="{ background: `linear-gradient(135deg, ${catColor(heroPost.categories?.[0])} 0%, #88c0d0 100%)` }"
              />
              <!-- Overlay -->
              <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(20,26,40,0.85) 0%, rgba(20,26,40,0.4) 50%, transparent 100%);" />

              <!-- Text content on top of image -->
              <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
                <!-- Category pills -->
                <div v-if="heroPost.categories?.length" class="mb-3 flex flex-wrap gap-1.5">
                  <span
                    v-for="cat in heroPost.categories"
                    :key="cat.slug"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wide text-white"
                    :style="{ backgroundColor: catColor(cat) }"
                  >{{ cat.name }}</span>
                </div>
                <!-- Title -->
                <h2 class="font-editorial text-2xl sm:text-3xl font-bold text-white leading-snug mb-2">
                  {{ heroPost.title }}
                </h2>
                <!-- Excerpt -->
                <p v-if="heroPost.excerpt" class="text-sm text-white/75 line-clamp-2 mb-4">
                  {{ heroPost.excerpt }}
                </p>
                <!-- Meta -->
                <div class="flex items-center gap-2 text-xs text-white/60">
                  <div
                    class="w-5 h-5 rounded-full overflow-hidden shrink-0 ring-1 ring-white/30"
                  >
                    <img
                      v-if="heroPost.author?.avatar_url"
                      :src="heroPost.author.avatar_url"
                      :alt="heroPost.author.name"
                      class="w-full h-full object-cover"
                    />
                    <div
                      v-else
                      class="w-full h-full flex items-center justify-center text-[10px] font-bold text-white"
                      :style="{ backgroundColor: catColor(heroPost.categories?.[0]) }"
                    >{{ heroPost.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}</div>
                  </div>
                  <span class="font-medium text-white/80">{{ heroPost.author?.name ?? 'Unknown' }}</span>
                  <span class="opacity-40">·</span>
                  <span>{{ heroPost.published_at ? new Date(heroPost.published_at).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' }) : '' }}</span>
                </div>
              </div>
            </div>
          </Link>
        </article>

        <!-- Remaining posts: 2-column card grid -->
        <div v-if="remainingPosts.length" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <PostCard
            v-for="post in remainingPosts"
            :key="post.id"
            :post="post"
          />
        </div>
      </template>

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
