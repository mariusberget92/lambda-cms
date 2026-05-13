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
          v-if="post.author?.avatar_url"
          :src="post.author.avatar_url"
          :alt="post.author?.name ?? 'Author'"
          class="w-9 h-9 rounded-full object-cover"
        />
        <div v-else class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-500">
          {{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ post.author?.name ?? 'Unknown' }}</p>
          <p class="text-xs text-gray-500">
            {{ formatDate(post.published_at) }}
            <span v-if="post.reading_time" class="ml-2 before:content-['·'] before:mr-2">{{ post.reading_time }} min read</span>
          </p>
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
