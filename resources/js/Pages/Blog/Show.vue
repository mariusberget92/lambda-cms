<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/components/BlogSidebar.vue'
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
    onError:   () => { submitError.value = 'Please fix the errors below and try again.' },
  })
}
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">

    <!-- Main article -->
    <div class="lg:col-span-2 min-w-0">

      <!-- Back link -->
      <Link href="/" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-primary transition-colors mb-8">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        All posts
      </Link>

      <!-- Categories -->
      <div v-if="post.categories?.length" class="mb-3 flex flex-wrap gap-3">
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
      <h1 class="font-editorial text-4xl sm:text-5xl font-bold leading-tight text-foreground mb-5">
        {{ post.title }}
      </h1>

      <!-- Author + date -->
      <div class="flex items-center gap-3 mb-8 pb-8 border-b border-border">
        <img
          v-if="post.author?.avatar_url"
          :src="post.author.avatar_url"
          :alt="post.author?.name ?? 'Author'"
          class="w-10 h-10 rounded-full object-cover shrink-0"
        />
        <div v-else class="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-sm font-semibold text-muted-foreground shrink-0">
          {{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}
        </div>
        <div>
          <p class="text-sm font-semibold text-foreground">{{ post.author?.name ?? 'Unknown' }}</p>
          <p class="text-xs text-muted-foreground">{{ formatDate(post.published_at) }}</p>
        </div>
      </div>

      <!-- Featured image -->
      <div v-if="post.featured_image_url" class="mb-10 overflow-hidden rounded-xl">
        <img
          :src="post.featured_image_url"
          :alt="post.featured_image_alt ?? post.title"
          class="w-full object-cover max-h-[28rem]"
        />
      </div>

      <!-- Content -->
      <div class="prose prose-lg prose-slate max-w-none">
        <BlockRenderer v-if="post.use_block_editor && post.blocks" :blocks="post.blocks" />
        <div v-else v-html="post.body" />
      </div>

      <!-- Tags -->
      <div v-if="post.tags?.length" class="mt-10 pt-8 border-t border-border flex flex-wrap gap-2">
        <Link
          v-for="tag in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="text-xs border border-border rounded-full px-3 py-1 text-muted-foreground transition-colors hover:border-primary hover:text-primary hover:bg-primary/5"
        >{{ tag.name }}</Link>
      </div>

      <!-- Comments section -->
      <section class="mt-16 pt-10 border-t border-border">
        <h2 class="font-editorial text-2xl font-bold text-foreground mb-8">
          {{ commentsTotal ? commentsTotal + ' Comment' + (commentsTotal !== 1 ? 's' : '') : 'Comments' }}
        </h2>

        <!-- Comment list -->
        <div v-if="commentList.length" class="space-y-8 mb-10">
          <div v-for="comment in commentList" :key="comment.id" class="flex gap-4">
            <div class="w-9 h-9 rounded-full bg-muted flex items-center justify-center text-sm font-semibold text-muted-foreground shrink-0 overflow-hidden">
              <img v-if="comment.avatar_url" :src="comment.avatar_url" :alt="comment.author_name" class="w-full h-full object-cover" />
              <span v-else>{{ comment.author_name.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline gap-2 mb-1.5">
                <span class="text-sm font-semibold text-foreground">{{ comment.author_name }}</span>
                <span class="text-xs text-muted-foreground">{{ formatDateTime(comment.created_at) }}</span>
              </div>
              <p class="text-sm text-foreground/80 leading-relaxed whitespace-pre-line">{{ comment.body }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground mb-8">No comments yet. Be the first!</p>

        <!-- Load more -->
        <div v-if="hasMore || loadError" class="mb-10 text-center">
          <p v-if="loadError" class="text-sm text-destructive mb-2">Failed to load more comments.</p>
          <button
            type="button"
            :disabled="loadingMore"
            class="text-sm text-muted-foreground hover:text-primary transition-colors disabled:opacity-40"
            @click="loadMore"
          >{{ loadingMore ? 'Loading…' : (loadError ? 'Retry' : 'Load more comments') }}</button>
        </div>
        <div v-else class="mb-10" />

        <!-- Comment form -->
        <div v-if="commentsEnabled" class="rounded-xl border border-border bg-muted/20 p-6 sm:p-8">
          <h3 class="font-editorial text-lg font-bold text-foreground mb-6">Leave a comment</h3>

          <div v-if="submitSuccess" class="mb-5 rounded-lg bg-[var(--color-success-bg)] border border-[var(--color-success-border)] px-4 py-3 text-sm text-[var(--color-success-fg)]">
            Your comment has been submitted and is awaiting moderation. Thank you!
          </div>
          <div v-if="submitError" class="mb-5 rounded-lg bg-[var(--color-error-bg)] border border-[var(--color-error-border)] px-4 py-3 text-sm text-[var(--color-error-fg)]">
            {{ submitError }}
          </div>

          <form @submit.prevent="submitComment" class="space-y-5">
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wide mb-2">
                  Name <span class="text-destructive">*</span>
                </label>
                <input
                  v-model="form.author_name"
                  type="text"
                  class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :class="form.errors.author_name ? 'border-destructive' : 'border-border'"
                  placeholder="Your name"
                  required
                />
                <p v-if="form.errors.author_name" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_name }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wide mb-2">
                  Email <span class="text-muted-foreground/50 font-normal normal-case tracking-normal">(optional)</span>
                </label>
                <input
                  v-model="form.author_email"
                  type="email"
                  class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                  :class="form.errors.author_email ? 'border-destructive' : 'border-border'"
                  placeholder="you@example.com"
                />
                <p v-if="form.errors.author_email" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_email }}</p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-foreground/70 uppercase tracking-wide mb-2">
                Comment <span class="text-destructive">*</span>
              </label>
              <textarea
                v-model="form.body"
                rows="4"
                class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-y"
                :class="form.errors.body ? 'border-destructive' : 'border-border'"
                placeholder="Share your thoughts…"
                required
              />
              <p v-if="form.errors.body" class="mt-1.5 text-xs text-destructive">{{ form.errors.body }}</p>
            </div>

            <div class="flex items-center justify-between gap-4 pt-1">
              <p class="text-xs text-muted-foreground">Comments are moderated before appearing.</p>
              <button
                type="submit"
                :disabled="form.processing"
                class="shrink-0 bg-primary text-primary-foreground px-5 py-2 text-sm font-medium rounded-lg hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-60"
              >{{ form.processing ? 'Submitting…' : 'Post comment' }}</button>
            </div>
          </form>
        </div>

        <!-- Comments disabled -->
        <div v-else class="rounded-xl border border-border px-6 py-5 text-sm text-muted-foreground text-center">
          Comments are closed for this post.
        </div>
      </section>
    </div>

    <!-- Sidebar -->
    <div class="lg:border-l lg:border-border lg:pl-12 pt-4 lg:pt-0">
      <BlogSidebar :sidebar="sidebar" />
    </div>
  </div>
</template>
