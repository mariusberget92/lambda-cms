<script setup>
import { inject, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const post         = inject('postContext', null)
const commentsData = inject('commentsData', null)

const commentList    = ref([])
const hasMore        = ref(false)
const currentPage    = ref(1)
const loadingMore    = ref(false)
const loadError      = ref(false)
const initialLoading = ref(true)

import { onMounted } from 'vue'
onMounted(async () => {
  if (!post?.slug) { initialLoading.value = false; return }
  try {
    const res  = await fetch(`/blog/${post.slug}/comments?page=1`)
    if (!res.ok) throw new Error()
    const json = await res.json()
    commentList.value = json.data
    hasMore.value     = json.has_more
  } catch {
    loadError.value = true
  } finally {
    initialLoading.value = false
  }
})

async function loadMore() {
  loadingMore.value = true
  loadError.value   = false
  try {
    const nextPage = currentPage.value + 1
    const res      = await fetch(`/blog/${post.slug}/comments?page=${nextPage}`)
    if (!res.ok) throw new Error()
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

const form = useForm({ author_name: '', author_email: '', body: '', website: '' })
const submitSuccess = ref(false)
const submitError   = ref('')

function submitComment() {
  submitSuccess.value = false
  submitError.value   = ''
  form.post(route('comments.store', post.slug), {
    preserveScroll: true,
    onSuccess: () => { form.reset('body'); submitSuccess.value = true },
    onError:   () => { submitError.value = 'Please fix the errors below and try again.' },
  })
}
</script>

<template>
  <section v-if="post" class="comments-section">
    <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-1 comments-soft">Discussion</p>
    <h2 class="font-display font-bold mb-8 comments-heading" style="font-family:'Space Grotesk',sans-serif; font-size:1.5rem; letter-spacing:-0.02em;">
      {{ commentsData?.total ? commentsData.total + ' Comment' + (commentsData.total !== 1 ? 's' : '') : 'Comments' }}
    </h2>

    <!-- Loading skeleton -->
    <div v-if="initialLoading" class="space-y-6 mb-10">
      <div v-for="i in 3" :key="i" class="flex gap-4">
        <div class="w-9 h-9 rounded-full comments-skel shrink-0" />
        <div class="flex-1 space-y-2">
          <div class="h-4 w-32 rounded comments-skel" />
          <div class="h-3 w-full rounded comments-skel" />
          <div class="h-3 w-4/5 rounded comments-skel" />
        </div>
      </div>
    </div>

    <!-- Comment list -->
    <div v-else-if="commentList.length" class="space-y-6 mb-10">
      <div v-for="comment in commentList" :key="comment.id" class="flex gap-4">
        <div class="comment-avatar w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0 overflow-hidden">
          <img v-if="comment.avatar_url" :src="comment.avatar_url" :alt="comment.author_name" class="w-full h-full object-cover" />
          <span v-else>{{ comment.author_name?.charAt(0)?.toUpperCase() }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-baseline gap-2 mb-1.5">
            <span class="text-sm font-semibold comments-ink">{{ comment.author_name }}</span>
            <span class="font-mono-blog text-[11px] comments-soft">{{ comment.created_at }}</span>
          </div>
          <p class="text-sm leading-relaxed whitespace-pre-line comments-body">{{ comment.body }}</p>
        </div>
      </div>
    </div>
    <p v-else-if="!initialLoading" class="text-sm mb-8 comments-soft">No comments yet. Be the first!</p>

    <!-- Load more -->
    <div v-if="hasMore || loadError" class="mb-10 text-center">
      <p v-if="loadError" class="text-sm text-destructive mb-2">Failed to load comments.</p>
      <button type="button" :disabled="loadingMore" class="comments-load-more text-sm font-semibold transition-colors disabled:opacity-40" @click="loadMore">
        {{ loadingMore ? 'Loading…' : (loadError ? 'Retry' : 'Load more comments') }}
      </button>
    </div>
    <div v-else class="mb-10" />

    <!-- Comment form -->
    <div v-if="commentsData?.enabled !== false" class="comments-form-wrap">
      <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-1 comments-soft">Leave a reply</p>
      <h3 class="font-display font-semibold mb-5 comments-ink" style="font-family:'Space Grotesk',sans-serif; font-size:1.125rem;">Share your thoughts</h3>

      <div v-if="submitSuccess" class="mb-5 rounded px-4 py-3 text-sm font-medium comments-success">Your comment has been submitted and is awaiting moderation. Thank you!</div>
      <div v-if="submitError"   class="mb-5 rounded px-4 py-3 text-sm font-medium comments-error">{{ submitError }}</div>

      <form @submit.prevent="submitComment" class="space-y-5">
        <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="font-mono-blog text-[10px] uppercase tracking-widest block mb-2 comments-soft">Name <span class="text-destructive">*</span></label>
            <input
              v-model="form.author_name"
              type="text"
              class="comment-field w-full px-3 py-2.5 text-sm focus:outline-none transition-all"
              :class="{ 'comment-field--error': form.errors.author_name }"
              placeholder="Your name"
              required
            />
            <p v-if="form.errors.author_name" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_name }}</p>
          </div>
          <div>
            <label class="font-mono-blog text-[10px] uppercase tracking-widest block mb-2 comments-soft">Email <span class="font-normal normal-case tracking-normal comments-soft text-xs">(optional)</span></label>
            <input
              v-model="form.author_email"
              type="email"
              class="comment-field w-full px-3 py-2.5 text-sm focus:outline-none transition-all"
              :class="{ 'comment-field--error': form.errors.author_email }"
              placeholder="you@example.com"
            />
            <p v-if="form.errors.author_email" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_email }}</p>
          </div>
        </div>

        <div>
          <label class="font-mono-blog text-[10px] uppercase tracking-widest block mb-2 comments-soft">Comment <span class="text-destructive">*</span></label>
          <textarea
            v-model="form.body"
            rows="4"
            class="comment-field w-full px-3 py-2.5 text-sm focus:outline-none transition-all resize-y"
            :class="{ 'comment-field--error': form.errors.body }"
            placeholder="Share your thoughts…"
            required
          />
          <p v-if="form.errors.body" class="mt-1.5 text-xs text-destructive">{{ form.errors.body }}</p>
        </div>

        <div class="flex items-center justify-between gap-4 pt-1">
          <p class="text-xs comments-soft">Comments are moderated before appearing.</p>
          <button type="submit" :disabled="form.processing" class="comment-submit shrink-0 px-6 py-2.5 text-sm font-semibold rounded transition-all duration-150 disabled:opacity-60">
            {{ form.processing ? 'Submitting…' : 'Post comment' }}
          </button>
        </div>
      </form>
    </div>
    <div v-else class="rounded px-6 py-5 text-sm text-center comments-closed">Comments are closed for this post.</div>
  </section>
</template>

<style scoped>
.comments-section {
  background: var(--panel);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 1.5rem 2rem;
}
.comments-soft    { color: var(--soft); }
.comments-ink     { color: var(--ink); }
.comments-body    { color: var(--ink); opacity: 0.8; }
.comments-heading { color: var(--ink); }
.comments-skel    { background: var(--line-strong); }
.comments-load-more { color: var(--accent); }
.comments-load-more:hover { opacity: 0.75; }

.comment-avatar {
  background: var(--accent);
  color: var(--accent-ink);
}

.comment-field {
  background: var(--bg);
  color: var(--ink);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
}
.comment-field:focus { border-color: var(--accent); }
.comment-field--error { border-color: var(--destructive, #ef4444); }

.comments-form-wrap {
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: var(--blog-radius);
  padding: 1.25rem 1.5rem;
}

.comments-success {
  background: rgba(0,0,0,0.04);
  border: 1px solid var(--line-strong);
  color: var(--ink);
}
.comments-error {
  background: rgba(239,68,68,0.06);
  border: 1px solid rgba(239,68,68,0.25);
  color: #ef4444;
}

.comment-submit {
  background: var(--accent);
  color: var(--accent-ink);
  border: 1px solid var(--accent);
}
.comment-submit:hover:not(:disabled) { opacity: 0.88; }

.comments-closed {
  border: 1px solid var(--line);
  color: var(--soft);
}
</style>
