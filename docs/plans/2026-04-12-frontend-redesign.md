# Frontend Editorial Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace NORD CSS-variable styles on all public-facing blog pages with a clean, light editorial look using plain Tailwind utilities and the Lora serif Google Font.

**Architecture:** All CSS-variable-based Tailwind classes (`bg-background`, `text-foreground`, `bg-card`, `text-muted-foreground`, etc.) are replaced with literal Tailwind classes (`bg-white`, `text-gray-900`, `text-gray-500`, etc.) across 8 frontend files. No CSS variables or `dark:` variants are used anywhere in blog-facing code. A single `.font-editorial` utility is added to `app.css` and Lora is loaded via `<link>` in `BlogLayout`.

**Tech Stack:** Vue 3, Tailwind CSS 4, Inertia.js v2, Google Fonts (Lora)

---

### Task 1: Add `.font-editorial` utility to `app.css`

**Files:**
- Modify: `resources/css/app.css`

**Step 1: Add the utility after the existing `.scrollbar-hidden` block**

In `resources/css/app.css`, after line 8 (the `.scrollbar-hidden::-webkit-scrollbar` rule), add:

```css
/* Editorial serif font for blog headings */
.font-editorial { font-family: 'Lora', Georgia, serif; }
```

**Step 2: Build and verify no errors**

```bash
npm run build
```
Expected: `✓ built in X.XXs` with no errors.

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "style: add font-editorial utility for Lora serif"
```

---

### Task 2: Redesign `BlogLayout.vue`

**Files:**
- Modify: `resources/js/Layouts/BlogLayout.vue`

This is the wrapper for all public pages. Full replacement of the template.

**Step 1: Replace the entire file content**

```vue
<script setup>
import { Head, usePage, Link, router } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'
import { Search, X } from 'lucide-vue-next'

defineOptions({ layout: null })

const appName  = computed(() => usePage().props.appName ?? 'Lambda CMS')
const authUser  = computed(() => usePage().props.auth?.user)
const navItems  = computed(() => usePage().props.navItems ?? [])
const year = new Date().getFullYear()

const searchOpen = ref(false)
const headerQuery = ref('')
const headerSearchInput = ref(null)

function submitHeaderSearch() {
  const q = headerQuery.value.trim()
  if (!q) return
  closeSearch()
  router.get(route('search'), { q })
}

function openSearch() {
  searchOpen.value = true
  nextTick(() => { headerSearchInput.value?.focus() })
}

function closeSearch() {
  searchOpen.value = false
  headerQuery.value = ''
}
</script>

<template>
  <Head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="alternate" type="application/rss+xml" :title="appName" href="/feed" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
  </Head>

  <div class="min-h-screen flex flex-col bg-white text-gray-900">

    <!-- Header -->
    <header class="border-b border-gray-200 bg-white">
      <div class="max-w-5xl mx-auto px-6 h-14 flex items-center justify-between">
        <!-- Site name -->
        <Link href="/" class="font-editorial text-xl font-bold text-gray-900 hover:opacity-70 transition-opacity">
          {{ appName }}
        </Link>

        <!-- Nav -->
        <nav class="flex items-center gap-5">
          <template v-for="item in navItems" :key="item.url + '-' + item.label">
            <a
              v-if="item.url.startsWith('http')"
              :href="item.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
            >{{ item.label }}</a>
            <Link
              v-else
              :href="item.url"
              class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
            >{{ item.label }}</Link>
          </template>

          <!-- Search -->
          <div class="flex items-center gap-1">
            <div
              class="overflow-hidden transition-all duration-300 ease-out flex items-center"
              :class="searchOpen ? 'max-w-[180px] opacity-100' : 'max-w-0 opacity-0 pointer-events-none'"
            >
              <form @submit.prevent="submitHeaderSearch" class="flex items-center gap-1 pl-0.5">
                <input
                  ref="headerSearchInput"
                  v-model="headerQuery"
                  type="search"
                  placeholder="Search…"
                  @keydown.escape="closeSearch"
                  class="h-7 w-36 border-b border-gray-300 bg-transparent px-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900"
                />
                <button type="button" @click="closeSearch" class="text-gray-400 hover:text-gray-900 transition-colors p-1 shrink-0" aria-label="Close search">
                  <X class="w-3.5 h-3.5" />
                </button>
              </form>
            </div>
            <button
              type="button"
              @click="searchOpen ? submitHeaderSearch() : openSearch()"
              class="text-gray-400 hover:text-gray-900 transition-colors p-1 shrink-0"
              aria-label="Search"
            >
              <Search class="w-4 h-4" />
            </button>
          </div>

          <Link
            v-if="authUser"
            :href="route('dashboard')"
            class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
          >Dashboard</Link>
          <Link
            v-else
            :href="route('login')"
            class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
          >Sign in</Link>
        </nav>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 max-w-5xl mx-auto w-full px-6 py-12">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200">
      <div class="max-w-5xl mx-auto px-6 py-6 text-center text-xs text-gray-400">
        &copy; {{ year }} {{ appName }}
      </div>
    </footer>

  </div>
</template>
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Layouts/BlogLayout.vue
git commit -m "feat(frontend): clean editorial BlogLayout — remove NORD hero, Lora font"
```

---

### Task 3: Redesign `PostCard.vue`

**Files:**
- Modify: `resources/js/Components/PostCard.vue`

Remove the bordered card box, hover lift, shadow, accent bar, gradient. Clean article entry — title is the link, no CTA button.

**Step 1: Replace the entire file content**

```vue
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
        v-if="post.author.avatar_url"
        :src="post.author.avatar_url"
        :alt="post.author.name"
        class="w-6 h-6 rounded-full object-cover"
      />
      <div v-else class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-500">
        {{ post.author.name.charAt(0).toUpperCase() }}
      </div>
      <span class="text-sm text-gray-500">{{ post.author.name }}</span>
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
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Components/PostCard.vue
git commit -m "feat(frontend): clean editorial PostCard — no card box, Lora title"
```

---

### Task 4: Redesign `BlogSidebar.vue`

**Files:**
- Modify: `resources/js/Components/BlogSidebar.vue`

Remove pill headers, colored accents, CSS-variable classes. Plain typography.

**Step 1: Replace the entire file content**

```vue
<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  sidebar: { type: Object, required: true },
  query:   { type: String, default: '' },
})

const searchQuery = ref(props.query)
watch(() => props.query, (v) => { searchQuery.value = v })

const maxCount = (items) => Math.max(...items.map((i) => i.posts_count), 1)

function submitSearch() {
  const q = searchQuery.value.trim()
  router.get('/search', q ? { q } : {})
}
</script>

<template>
  <aside class="space-y-10">

    <!-- Search -->
    <div>
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Search</h3>
      <form @submit.prevent="submitSearch" class="flex gap-2 items-end">
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search posts…"
          class="flex-1 min-w-0 border-b border-gray-300 bg-transparent py-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900"
        />
        <button
          type="submit"
          class="text-sm text-gray-500 hover:text-gray-900 transition-colors pb-1"
        >Go</button>
      </form>
    </div>

    <!-- Categories -->
    <div v-if="sidebar.categories?.length">
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Categories</h3>
      <ul class="space-y-2">
        <li
          v-for="cat in sidebar.categories"
          :key="cat.slug"
          class="flex items-center justify-between"
        >
          <Link
            :href="`/blog/category/${cat.slug}`"
            class="text-sm text-gray-700 hover:text-gray-900 transition-colors"
          >{{ cat.name }}</Link>
          <span class="text-xs text-gray-400">{{ cat.posts_count }}</span>
        </li>
      </ul>
    </div>

    <!-- Tags -->
    <div v-if="sidebar.tags?.length">
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Tags</h3>
      <div class="flex flex-wrap gap-2">
        <Link
          v-for="tag in sidebar.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="border border-gray-200 rounded-full px-2.5 py-0.5 text-gray-600 transition-colors hover:bg-gray-900 hover:text-white hover:border-gray-900"
          :style="{ fontSize: `${0.65 + (tag.posts_count / maxCount(sidebar.tags)) * 0.35}rem` }"
        >{{ tag.name }}</Link>
      </div>
    </div>

    <!-- Recent posts -->
    <div v-if="sidebar.recentPosts?.length">
      <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Recent Posts</h3>
      <ul class="space-y-3">
        <li v-for="post in sidebar.recentPosts" :key="post.slug">
          <Link
            :href="`/blog/${post.slug}`"
            class="text-sm text-gray-700 hover:text-gray-900 transition-colors line-clamp-2"
          >{{ post.title }}</Link>
          <p class="text-xs text-gray-400 mt-0.5">{{ post.published_at }}</p>
        </li>
      </ul>
    </div>

  </aside>
</template>
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Components/BlogSidebar.vue
git commit -m "feat(frontend): clean editorial BlogSidebar"
```

---

### Task 5: Update `Blog/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Blog/Index.vue`

Posts separated by `border-t border-gray-200` dividers. Plain pagination.

**Step 1: Replace the entire file content**

```vue
<script setup>
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { Link } from '@inertiajs/vue3'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

defineProps({
  posts:   Object,
  sidebar: Object,
  seo:     { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

    <!-- Main: Post list -->
    <div class="lg:col-span-2">

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-gray-400">
        <p class="text-sm">No posts published yet.</p>
      </div>

      <!-- Post list with dividers -->
      <div v-else class="divide-y divide-gray-200">
        <div v-for="post in posts.data" :key="post.id" class="py-10 first:pt-0">
          <PostCard :post="post" />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex items-center justify-center gap-3 mt-12 pt-8 border-t border-gray-200">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="text-sm transition-colors"
            :class="link.active
              ? 'font-semibold text-gray-900 underline underline-offset-2'
              : 'text-gray-500 hover:text-gray-900'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span
            v-else
            class="text-sm text-gray-300 cursor-not-allowed"
          >{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Index.vue
git commit -m "feat(frontend): editorial blog index — divider list, plain pagination"
```

---

### Task 6: Update `Blog/Show.vue`

**Files:**
- Modify: `resources/js/Pages/Blog/Show.vue`

Article typography, prose content, clean comments section.

**Step 1: Replace the entire file content**

```vue
<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import SeoHead from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
import { formatDateTime } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  post:            Object,
  sidebar:         Object,
  comments:        { type: Array,   default: () => [] },
  commentsTotal:   { type: Number,  default: 0 },
  commentsHasMore: { type: Boolean, default: false },
  commentsPerPage: { type: Number,  default: 10 },
  commentsEnabled: { type: Boolean, default: true },
  authUser:        { type: Object,  default: null },
  seo:             { type: Object,  required: true },
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

// Comment list
const commentList = ref([...props.comments])
const hasMore     = ref(props.commentsHasMore)
const currentPage = ref(1)
const loadingMore = ref(false)
const loadError   = ref(false)

async function loadMore() {
  loadingMore.value = true
  loadError.value   = false
  try {
    const nextPage = currentPage.value + 1
    const res      = await fetch(`/blog/${props.post.slug}/comments?page=${nextPage}`)
    if (!res.ok) throw new Error('Server error')
    const json = await res.json()
    commentList.value.push(...json.data)
    hasMore.value     = json.has_more
    currentPage.value = nextPage
  } catch {
    loadError.value = true
  } finally {
    loadingMore.value = false
  }
}

// Comment form
const form = useForm({
  author_name:  props.authUser?.name  ?? '',
  author_email: props.authUser?.email ?? '',
  body:         '',
  website:      '',
})

const submitSuccess = ref(false)
const submitError   = ref('')

function submitComment() {
  submitSuccess.value = false
  submitError.value   = ''
  form.post(route('comments.store', props.post.slug), {
    preserveScroll: true,
    onSuccess: () => { form.reset('body'); submitSuccess.value = true },
    onError: () => { submitError.value = 'Please fix the errors below and try again.' },
  })
}
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

    <!-- Main article -->
    <div class="lg:col-span-2">

      <!-- Back link -->
      <Link href="/" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors mb-8">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        All posts
      </Link>

      <!-- Category -->
      <div v-if="post.categories?.length" class="mb-3 flex flex-wrap gap-3">
        <span
          v-for="cat in post.categories"
          :key="cat.slug"
          class="text-xs text-gray-500 uppercase tracking-wide"
        >{{ cat.name }}</span>
      </div>

      <!-- Title -->
      <h1 class="font-editorial text-4xl font-bold leading-tight text-gray-900 mb-4">{{ post.title }}</h1>

      <!-- Author + date -->
      <div class="flex items-center gap-3 mb-8 pb-8 border-b border-gray-200">
        <img
          v-if="post.author.avatar_url"
          :src="post.author.avatar_url"
          :alt="post.author.name"
          class="w-9 h-9 rounded-full object-cover"
        />
        <div v-else class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-500">
          {{ post.author.name.charAt(0).toUpperCase() }}
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ post.author.name }}</p>
          <p class="text-xs text-gray-500">{{ formatDate(post.published_at) }}</p>
        </div>
      </div>

      <!-- Featured image -->
      <div v-if="post.featured_image_url" class="mb-10">
        <img
          :src="post.featured_image_url"
          :alt="post.featured_image_alt ?? post.title"
          class="w-full rounded-lg object-cover max-h-96"
        />
      </div>

      <!-- Content -->
      <BlockRenderer v-if="post.use_block_editor && post.blocks" :blocks="post.blocks" />
      <div v-else class="prose prose-gray max-w-none" v-html="post.body" />

      <!-- Tags -->
      <div v-if="post.tags?.length" class="mt-10 pt-8 border-t border-gray-200 flex flex-wrap gap-2">
        <span
          v-for="tag in post.tags"
          :key="tag.slug"
          class="text-xs border border-gray-200 rounded-full px-2.5 py-0.5 text-gray-500"
        >{{ tag.name }}</span>
      </div>

      <!-- Comments -->
      <div class="mt-16 pt-10 border-t border-gray-200">
        <h2 class="font-editorial text-2xl font-bold text-gray-900 mb-8">
          {{ commentsTotal ? commentsTotal + ' Comment' + (commentsTotal !== 1 ? 's' : '') : 'Comments' }}
        </h2>

        <!-- Comment list -->
        <div v-if="commentList.length" class="space-y-8 mb-10">
          <div v-for="comment in commentList" :key="comment.id" class="flex gap-4">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-500 shrink-0">
              <img v-if="comment.avatar_url" :src="comment.avatar_url" :alt="comment.author_name" class="w-full h-full rounded-full object-cover" />
              <span v-else>{{ comment.author_name.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline gap-2 mb-1">
                <span class="text-sm font-semibold text-gray-900">{{ comment.author_name }}</span>
                <span class="text-xs text-gray-400">{{ formatDateTime(comment.created_at) }}</span>
              </div>
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ comment.body }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-500 mb-8">No comments yet. Be the first!</p>

        <!-- Load more -->
        <div v-if="hasMore || loadError" class="mb-10 text-center">
          <p v-if="loadError" class="text-sm text-red-500 mb-2">Failed to load more comments.</p>
          <button
            type="button"
            :disabled="loadingMore"
            @click="loadMore"
            class="text-sm text-gray-500 hover:text-gray-900 transition-colors disabled:opacity-50"
          >{{ loadingMore ? 'Loading…' : (loadError ? 'Retry' : 'Load more comments') }}</button>
        </div>
        <div v-else class="mb-10" />

        <!-- Comment form -->
        <div v-if="commentsEnabled" class="bg-gray-50 rounded-lg p-6">
          <h3 class="font-editorial text-lg font-bold text-gray-900 mb-5">Leave a comment</h3>

          <div v-if="submitSuccess" class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
            Your comment has been submitted and is awaiting moderation. Thank you!
          </div>
          <div v-if="submitError" class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-600">
            {{ submitError }}
          </div>

          <form @submit.prevent="submitComment" class="space-y-4">
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                <input
                  v-model="form.author_name"
                  type="text"
                  class="w-full border-b border-gray-300 bg-transparent py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900"
                  :class="{ 'border-red-400': form.errors.author_name }"
                  placeholder="Your name"
                  required
                />
                <p v-if="form.errors.author_name" class="mt-1 text-xs text-red-500">{{ form.errors.author_name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                <input
                  v-model="form.author_email"
                  type="email"
                  class="w-full border-b border-gray-300 bg-transparent py-1.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900"
                  :class="{ 'border-red-400': form.errors.author_email }"
                  placeholder="you@example.com"
                />
                <p v-if="form.errors.author_email" class="mt-1 text-xs text-red-500">{{ form.errors.author_email }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Comment <span class="text-red-500">*</span></label>
              <textarea
                v-model="form.body"
                rows="4"
                class="w-full border border-gray-200 bg-white rounded-md px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900 resize-y"
                :class="{ 'border-red-400': form.errors.body }"
                placeholder="Share your thoughts…"
                required
              />
              <p v-if="form.errors.body" class="mt-1 text-xs text-red-500">{{ form.errors.body }}</p>
            </div>

            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-400">Comments are moderated before appearing.</p>
              <button
                type="submit"
                :disabled="form.processing"
                class="bg-gray-900 text-white px-5 py-2 text-sm font-medium rounded-md hover:bg-gray-700 transition-colors disabled:opacity-60"
              >{{ form.processing ? 'Submitting…' : 'Submit comment' }}</button>
            </div>
          </form>
        </div>

        <!-- Comments disabled -->
        <div v-else class="rounded-lg border border-gray-200 px-6 py-5 text-sm text-gray-500">
          Comments are closed.
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Show.vue
git commit -m "feat(frontend): editorial single post — Lora title, prose body, clean comments"
```

---

### Task 7: Update `Blog/Archive.vue`

**Files:**
- Modify: `resources/js/Pages/Blog/Archive.vue`

Replace CSS-variable classes with plain Tailwind. Same divider post list as Index.

**Step 1: Replace the entire file content**

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
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
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2">

      <!-- Archive heading -->
      <div class="mb-10">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">
          {{ heading.type === 'category' ? 'Category' : 'Tag' }}
        </p>
        <h1 class="font-editorial text-3xl font-bold text-gray-900">{{ heading.name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ heading.postsCount }} {{ heading.postsCount === 1 ? 'post' : 'posts' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-gray-400">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Post list with dividers -->
      <div v-else class="divide-y divide-gray-200">
        <div v-for="post in posts.data" :key="post.id" class="py-10 first:pt-0">
          <PostCard :post="post" />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex items-center justify-center gap-3 mt-12 pt-8 border-t border-gray-200">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="text-sm transition-colors"
            :class="link.active ? 'font-semibold text-gray-900 underline underline-offset-2' : 'text-gray-500 hover:text-gray-900'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span v-else class="text-sm text-gray-300 cursor-not-allowed">{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </div>
    </div>

    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Archive.vue
git commit -m "feat(frontend): editorial archive page"
```

---

### Task 8: Update `Blog/Search.vue`

**Files:**
- Modify: `resources/js/Pages/Blog/Search.vue`

Replace CSS-variable classes. Same post list pattern.

**Step 1: Replace the entire file content**

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineOptions({ layout: BlogLayout })

defineProps({
  query:   { type: String, default: '' },
  results: { type: Object, required: true },
  sidebar: { type: Object, default: () => ({}) },
  seo:     { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <div class="lg:col-span-2">

      <!-- Search heading -->
      <div class="mb-10">
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-2">Search</p>
        <h1 class="font-editorial text-3xl font-bold text-gray-900">
          {{ query ? `Results for "${query}"` : 'Search' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ results.total }} {{ results.total === 1 ? 'result' : 'results' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!results.data.length" class="text-center py-20 text-gray-400">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Post list with dividers -->
      <div v-else class="divide-y divide-gray-200">
        <div v-for="post in results.data" :key="post.id" class="py-10 first:pt-0">
          <PostCard :post="post" />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="results.links?.length > 3" class="flex items-center justify-center gap-3 mt-12 pt-8 border-t border-gray-200">
        <template v-for="link in results.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="text-sm transition-colors"
            :class="link.active ? 'font-semibold text-gray-900 underline underline-offset-2' : 'text-gray-500 hover:text-gray-900'"
          >{{ decodeHtmlEntities(link.label) }}</Link>
          <span v-else class="text-sm text-gray-300 cursor-not-allowed">{{ decodeHtmlEntities(link.label) }}</span>
        </template>
      </div>
    </div>

    <BlogSidebar :sidebar="sidebar" :query="query" />
  </div>
</template>
```

**Step 2: Build and verify**

```bash
npm run build
```
Expected: `✓ built in X.XXs`

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Search.vue
git commit -m "feat(frontend): editorial search results page"
```

---

### Task 9: Final build & smoke check

**Step 1: Full build**

```bash
npm run build
```
Expected: `✓ built in X.XXs` with no errors.

**Step 2: Start the dev server and visually verify**

```bash
php artisan serve
```

Visit:
- `/` — blog index: white background, Lora titles, divider between posts, no NORD colours
- `/blog/{any-slug}` — single post: serif h1, prose body, clean comments
- `/blog/category/{slug}` — archive: taxonomy label + Lora heading
- `/search?q=test` — search results page

**Step 3: Confirm dark mode has no effect on frontend**
In browser DevTools, add `class="dark"` to `<html>`. Frontend should remain white — no colour change.

**Step 4: Commit**

No code change needed — this is a verification step only.
