<script setup>
import { inject, ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'

const post         = inject('postContext', null)
const commentsData = inject('commentsData', null)

const AURORA = ['#6366f1','#0ea5e9','#22c55e','#f59e0b','#f97316','#ef4444','#a855f7']

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

const accentColor = computed(() => post?.categories?.[0] ? catColor(post.categories[0]) : AURORA[0])

const commentList    = ref([])
const hasMore        = ref(false)
const currentPage    = ref(1)
const loadingMore    = ref(false)
const loadError      = ref(false)
const initialLoading = ref(true)

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

const form = useForm({
  author_name:  '',
  author_email: '',
  body:         '',
  website:      '',
})

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
  <section
    v-if="post"
    class="rounded-2xl p-6 sm:p-8 bg-white"
    style="box-shadow:0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03);"
  >
    <p class="text-[10px] font-bold uppercase tracking-[0.14em] mb-1" style="color:#94a3b8;">Discussion</p>
    <h2 class="font-editorial text-2xl font-bold text-foreground mb-8">
      {{ commentsData?.total ? commentsData.total + ' Comment' + (commentsData.total !== 1 ? 's' : '') : 'Comments' }}
    </h2>

    <!-- Loading skeleton -->
    <div v-if="initialLoading" class="space-y-6 mb-10">
      <div v-for="i in 3" :key="i" class="flex gap-4">
        <div class="w-9 h-9 rounded-full bg-muted/40 animate-pulse shrink-0" />
        <div class="flex-1 space-y-2">
          <div class="h-4 w-32 rounded bg-muted/40 animate-pulse" />
          <div class="h-3 w-full rounded bg-muted/40 animate-pulse" />
          <div class="h-3 w-4/5 rounded bg-muted/40 animate-pulse" />
        </div>
      </div>
    </div>

    <!-- Comment list -->
    <div v-else-if="commentList.length" class="space-y-6 mb-10">
      <div v-for="comment in commentList" :key="comment.id" class="flex gap-4">
        <div
          class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0 overflow-hidden"
          :style="{ backgroundColor: accentColor }"
        >
          <img
            v-if="comment.avatar_url"
            :src="comment.avatar_url"
            :alt="comment.author_name"
            class="w-full h-full object-cover"
          />
          <span v-else>{{ comment.author_name?.charAt(0)?.toUpperCase() }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-baseline gap-2 mb-1.5">
            <span class="text-sm font-bold text-foreground">{{ comment.author_name }}</span>
            <span class="text-xs" style="color:#94a3b8;">{{ comment.created_at }}</span>
          </div>
          <p class="text-sm leading-relaxed whitespace-pre-line" style="color:#475569;">{{ comment.body }}</p>
        </div>
      </div>
    </div>
    <p v-else-if="!initialLoading" class="text-sm mb-8" style="color:#94a3b8;">
      No comments yet. Be the first!
    </p>

    <!-- Load more -->
    <div v-if="hasMore || loadError" class="mb-10 text-center">
      <p v-if="loadError" class="text-sm text-destructive mb-2">Failed to load comments.</p>
      <button
        type="button"
        :disabled="loadingMore"
        class="text-sm font-semibold transition-colors disabled:opacity-40"
        :style="{ color: accentColor }"
        @click="loadMore"
      >{{ loadingMore ? 'Loading…' : (loadError ? 'Retry' : 'Load more comments') }}</button>
    </div>
    <div v-else class="mb-10" />

    <!-- Comment form -->
    <div
      v-if="commentsData?.enabled !== false"
      class="rounded-2xl p-5 sm:p-6"
      style="background:#f8f9ff; border:1.5px solid #e8eaff;"
    >
      <p class="text-[10px] font-bold uppercase tracking-[0.14em] mb-1" style="color:#94a3b8;">Leave a reply</p>
      <h3 class="font-editorial text-lg font-bold text-foreground mb-5">Share your thoughts</h3>

      <div v-if="submitSuccess" class="mb-5 rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(34,197,94,0.12); border:1.5px solid rgba(34,197,94,0.3); color:#16a34a;">
        Your comment has been submitted and is awaiting moderation. Thank you!
      </div>
      <div v-if="submitError" class="mb-5 rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(239,68,68,0.10); border:1.5px solid rgba(239,68,68,0.25); color:#ef4444;">
        {{ submitError }}
      </div>

      <form @submit.prevent="submitComment" class="space-y-5">
        <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color:#64748b;">
              Name <span class="text-destructive">*</span>
            </label>
            <input
              v-model="form.author_name"
              type="text"
              class="w-full rounded-xl px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all bg-white"
              :style="form.errors.author_name ? 'border:1.5px solid #ef4444;' : 'border:1.5px solid #e2e8f0;'"
              placeholder="Your name"
              required
              @focus="e => { if (!form.errors.author_name) e.target.style.borderColor='#6366f1'; e.target.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'; }"
              @blur="e => { e.target.style.borderColor=form.errors.author_name?'#ef4444':'#e2e8f0'; e.target.style.boxShadow='none'; }"
            />
            <p v-if="form.errors.author_name" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_name }}</p>
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color:#64748b;">
              Email <span class="text-xs font-normal normal-case tracking-normal" style="color:#94a3b8;">(optional)</span>
            </label>
            <input
              v-model="form.author_email"
              type="email"
              class="w-full rounded-xl px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all bg-white"
              :style="form.errors.author_email ? 'border:1.5px solid #ef4444;' : 'border:1.5px solid #e2e8f0;'"
              placeholder="you@example.com"
              @focus="e => { if (!form.errors.author_email) e.target.style.borderColor='#6366f1'; e.target.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'; }"
              @blur="e => { e.target.style.borderColor=form.errors.author_email?'#ef4444':'#e2e8f0'; e.target.style.boxShadow='none'; }"
            />
            <p v-if="form.errors.author_email" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_email }}</p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color:#64748b;">
            Comment <span class="text-destructive">*</span>
          </label>
          <textarea
            v-model="form.body"
            rows="4"
            class="w-full rounded-xl px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all resize-y bg-white"
            :style="form.errors.body ? 'border:1.5px solid #ef4444;' : 'border:1.5px solid #e2e8f0;'"
            placeholder="Share your thoughts…"
            required
            @focus="e => { if (!form.errors.body) e.target.style.borderColor='#6366f1'; e.target.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'; }"
            @blur="e => { e.target.style.borderColor=form.errors.body?'#ef4444':'#e2e8f0'; e.target.style.boxShadow='none'; }"
          />
          <p v-if="form.errors.body" class="mt-1.5 text-xs text-destructive">{{ form.errors.body }}</p>
        </div>

        <div class="flex items-center justify-between gap-4 pt-1">
          <p class="text-xs" style="color:#94a3b8;">Comments are moderated before appearing.</p>
          <button
            type="submit"
            :disabled="form.processing"
            class="shrink-0 text-white px-6 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 disabled:opacity-60"
            style="background:#6366f1; box-shadow:0 2px 8px rgba(99,102,241,0.35);"
            @mouseenter="e => { e.currentTarget.style.background='#4f46e5'; e.currentTarget.style.boxShadow='0 4px 12px rgba(99,102,241,0.45)'; }"
            @mouseleave="e => { e.currentTarget.style.background='#6366f1'; e.currentTarget.style.boxShadow='0 2px 8px rgba(99,102,241,0.35)'; }"
          >{{ form.processing ? 'Submitting…' : 'Post comment' }}</button>
        </div>
      </form>
    </div>

    <div v-else class="rounded-2xl px-6 py-5 text-sm text-center" style="border:1.5px solid #e8eaff; color:#94a3b8;">
      Comments are closed for this post.
    </div>
  </section>
</template>
