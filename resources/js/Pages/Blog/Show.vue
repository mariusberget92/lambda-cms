<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import SeoHead from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

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

// ── Comment list state ────────────────────────────────────────────────────────
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

// ── Submission form ───────────────────────────────────────────────────────────
const form = useForm({
  author_name:  props.authUser?.name  ?? '',
  author_email: props.authUser?.email ?? '',
  body:         '',
  website:      '', // honeypot
})

function submitComment() {
  form.post(route('comments.store', props.post.slug), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('body')
    },
  })
}
</script>

<template>
  <SeoHead :seo="seo" />
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

      <!-- Category badges -->
      <div v-if="post.categories?.length" class="mb-3 flex flex-wrap gap-1">
        <span
          v-for="cat in post.categories"
          :key="cat.slug"
          class="inline-block text-xs font-medium bg-primary/10 text-primary px-2 py-0.5 rounded-full"
        >
          {{ cat.name }}
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

      <!-- Block editor content -->
      <BlockRenderer v-if="post.use_block_editor && post.blocks" :blocks="post.blocks" />
      <!-- Legacy Tiptap content — keep prose-sm to match the existing rendering -->
      <div v-else class="prose prose-sm max-w-none dark:prose-invert" v-html="post.body" />

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

      <!-- Comments section -->
      <div class="mt-12 pt-8 border-t">
        <h2 class="text-xl font-bold mb-6">
          {{ commentsTotal ? commentsTotal + ' Comment' + (commentsTotal !== 1 ? 's' : '') : 'Comments' }}
        </h2>

        <!-- Comment list -->
        <div v-if="commentList.length" class="space-y-6 mb-6">
          <div
            v-for="comment in commentList"
            :key="comment.id"
            class="flex gap-3"
          >
            <div class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center text-sm font-semibold text-primary shrink-0">
              <img v-if="comment.avatar_url" :src="comment.avatar_url" :alt="comment.author_name" class="w-full h-full rounded-full object-cover" />
              <span v-else>{{ comment.author_name.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline gap-2 mb-1">
                <span class="text-sm font-semibold">{{ comment.author_name }}</span>
                <span class="text-xs text-muted-foreground">{{ comment.created_at }}</span>
              </div>
              <p class="text-sm text-foreground/90 whitespace-pre-line">{{ comment.body }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground mb-6">No comments yet. Be the first!</p>

        <!-- Load more -->
        <div v-if="hasMore || loadError" class="mb-10 text-center">
          <p v-if="loadError" class="text-sm text-destructive mb-2">Failed to load more comments.</p>
          <button
            type="button"
            :disabled="loadingMore"
            @click="loadMore"
            class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors disabled:opacity-50"
          >
            <svg v-if="loadingMore" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            {{ loadingMore ? 'Loading...' : (loadError ? 'Retry' : 'Load more comments') }}
          </button>
        </div>
        <div v-else class="mb-10"></div>

        <!-- Submission form OR disabled notice -->
        <div v-if="commentsEnabled" class="rounded-lg border bg-card p-6">
          <h3 class="text-base font-semibold mb-4">Leave a comment</h3>
          <form @submit.prevent="submitComment" class="space-y-4">
            <!-- Honeypot (hidden) -->
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1.5">Name <span class="text-destructive">*</span></label>
                <input
                  v-model="form.author_name"
                  type="text"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': form.errors.author_name }"
                  placeholder="Your name"
                  required
                />
                <p v-if="form.errors.author_name" class="mt-1 text-xs text-destructive">{{ form.errors.author_name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1.5">Email <span class="text-muted-foreground text-xs font-normal">(optional, not shown)</span></label>
                <input
                  v-model="form.author_email"
                  type="email"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': form.errors.author_email }"
                  placeholder="you@example.com"
                />
                <p v-if="form.errors.author_email" class="mt-1 text-xs text-destructive">{{ form.errors.author_email }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1.5">Comment <span class="text-destructive">*</span></label>
              <textarea
                v-model="form.body"
                rows="4"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-y"
                :class="{ 'border-destructive': form.errors.body }"
                placeholder="Share your thoughts..."
                required
              />
              <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
            </div>

            <div class="flex items-center justify-between">
              <p class="text-xs text-muted-foreground">Comments are moderated before appearing.</p>
              <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-60"
              >
                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Submit comment
              </button>
            </div>
          </form>
        </div>

        <!-- Comments disabled notice -->
        <div v-else class="rounded-lg border bg-muted/30 px-6 py-5 text-sm text-muted-foreground flex items-center gap-2">
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
          Comments are closed.
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>

