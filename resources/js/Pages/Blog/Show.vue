<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
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

const SCRIPT_ATTR = 'data-lambda-post-js'

onMounted(() => {
  const code = props.post?.custom_js
  if (!code) return
  const el = document.createElement('script')
  el.setAttribute(SCRIPT_ATTR, '1')
  el.textContent = code
  document.head.appendChild(el)
})

onUnmounted(() => {
  document.querySelectorAll(`[${SCRIPT_ATTR}]`).forEach(el => el.remove())
})

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

const primaryCatColor = props.post.categories?.[0] ? catColor(props.post.categories[0]) : '#5e81ac'
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="px-4 sm:px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">

    <!-- Main article -->
    <div class="lg:col-span-2 min-w-0">

      <!-- Back link -->
      <Link href="/" class="inline-flex items-center gap-1.5 text-sm font-medium mb-8 transition-colors" style="color:#8896b0;"
        @mouseenter="$event.currentTarget.style.color='#5e81ac'"
        @mouseleave="$event.currentTarget.style.color='#8896b0'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        All posts
      </Link>

      <!-- Categories -->
      <div v-if="post.categories?.length" class="mb-4 flex flex-wrap gap-2">
        <Link
          v-for="cat in post.categories"
          :key="cat.slug"
          :href="`/blog/category/${cat.slug}`"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-85"
          :style="{ backgroundColor: catColor(cat) }"
        >{{ cat.name }}</Link>
      </div>

      <!-- Title -->
      <h1 class="font-editorial text-4xl sm:text-5xl font-bold leading-tight text-foreground mb-6">
        {{ post.title }}
      </h1>

      <!-- Author + date -->
      <div class="flex items-center gap-3 mb-8 pb-8" style="border-bottom:2px solid #eef2f9;">
        <div class="w-11 h-11 rounded-full overflow-hidden shrink-0 ring-2" :style="{ '--tw-ring-color': hexToRgba(primaryCatColor, 0.3) }">
          <img
            v-if="post.author?.avatar_url"
            :src="post.author.avatar_url"
            :alt="post.author?.name ?? 'Author'"
            class="w-full h-full object-cover"
          />
          <div
            v-else
            class="w-full h-full flex items-center justify-center text-sm font-bold text-white"
            :style="{ backgroundColor: primaryCatColor }"
          >{{ post.author?.name?.charAt(0)?.toUpperCase() ?? '?' }}</div>
        </div>
        <div>
          <p class="text-sm font-bold text-foreground">{{ post.author?.name ?? 'Unknown' }}</p>
          <p class="text-xs" style="color:#8896b0;">{{ formatDate(post.published_at) }}</p>
        </div>
      </div>

      <!-- Featured image -->
      <div v-if="post.featured_image_url" class="mb-10 overflow-hidden rounded-2xl" style="box-shadow:0 4px 20px rgba(94,129,172,0.12);">
        <img
          :src="post.featured_image_url"
          :alt="post.featured_image_alt ?? post.title"
          class="w-full object-cover max-h-[30rem]"
        />
      </div>

      <!-- Content -->
      <div class="prose prose-lg prose-slate max-w-none">
        <BlockRenderer v-if="post.use_block_editor && post.blocks" :blocks="post.blocks" />
        <div v-else v-html="post.body" />
      </div>

      <!-- Tags -->
      <div v-if="post.tags?.length" class="mt-10 pt-8 flex flex-wrap gap-2" style="border-top:1px solid #dde3ee;">
        <Link
          v-for="(tag, idx) in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="text-xs px-3 py-1 rounded-full font-medium transition-all duration-200"
          :style="{
            backgroundColor: hexToRgba(AURORA[idx % AURORA.length], 0.12),
            color: AURORA[idx % AURORA.length],
          }"
          @mouseenter="e => { e.currentTarget.style.backgroundColor = AURORA[idx % AURORA.length]; e.currentTarget.style.color = '#fff'; }"
          @mouseleave="e => { e.currentTarget.style.backgroundColor = hexToRgba(AURORA[idx % AURORA.length], 0.12); e.currentTarget.style.color = AURORA[idx % AURORA.length]; }"
        >{{ tag.name }}</Link>
      </div>

      <!-- Comments section -->
      <section class="mt-16 pt-10" style="border-top:2px solid #eef2f9;">
        <h2 class="font-editorial text-2xl font-bold text-foreground mb-8">
          {{ commentsTotal ? commentsTotal + ' Comment' + (commentsTotal !== 1 ? 's' : '') : 'Comments' }}
        </h2>

        <!-- Comment list -->
        <div v-if="commentList.length" class="space-y-6 mb-10">
          <div v-for="comment in commentList" :key="comment.id" class="flex gap-4">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0 overflow-hidden" style="background:#5e81ac;">
              <img v-if="comment.avatar_url" :src="comment.avatar_url" :alt="comment.author_name" class="w-full h-full object-cover" />
              <span v-else>{{ comment.author_name.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline gap-2 mb-1.5">
                <span class="text-sm font-bold text-foreground">{{ comment.author_name }}</span>
                <span class="text-xs" style="color:#8896b0;">{{ formatDateTime(comment.created_at) }}</span>
              </div>
              <p class="text-sm leading-relaxed whitespace-pre-line" style="color:#4c566a;">{{ comment.body }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-sm mb-8" style="color:#8896b0;">No comments yet. Be the first!</p>

        <!-- Load more -->
        <div v-if="hasMore || loadError" class="mb-10 text-center">
          <p v-if="loadError" class="text-sm text-destructive mb-2">Failed to load more comments.</p>
          <button
            type="button"
            :disabled="loadingMore"
            class="text-sm font-medium transition-colors disabled:opacity-40"
            style="color:#5e81ac;"
            @click="loadMore"
          >{{ loadingMore ? 'Loading…' : (loadError ? 'Retry' : 'Load more comments') }}</button>
        </div>
        <div v-else class="mb-10" />

        <!-- Comment form -->
        <div v-if="commentsEnabled" class="rounded-2xl p-6 sm:p-8" style="background:linear-gradient(135deg, #f0f4fa 0%, #eef2f9 100%); border:1.5px solid #dde3ee;">
          <h3 class="font-editorial text-lg font-bold text-foreground mb-6">Leave a comment</h3>

          <div v-if="submitSuccess" class="mb-5 rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(163,190,140,0.15); border:1.5px solid rgba(163,190,140,0.4); color:#5a8a3f;">
            Your comment has been submitted and is awaiting moderation. Thank you!
          </div>
          <div v-if="submitError" class="mb-5 rounded-xl px-4 py-3 text-sm font-medium" style="background:rgba(191,97,106,0.1); border:1.5px solid rgba(191,97,106,0.3); color:#bf616a;">
            {{ submitError }}
          </div>

          <form @submit.prevent="submitComment" class="space-y-5">
            <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color:#6b7a96;">
                  Name <span class="text-destructive">*</span>
                </label>
                <input
                  v-model="form.author_name"
                  type="text"
                  class="w-full rounded-xl px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all bg-white"
                  :style="form.errors.author_name ? 'border:1.5px solid #bf616a;' : 'border:1.5px solid #dde3ee;'"
                  placeholder="Your name"
                  required
                  @focus="e => { if (!form.errors.author_name) e.target.style.borderColor='#5e81ac'; e.target.style.boxShadow='0 0 0 3px rgba(94,129,172,0.15)'; }"
                  @blur="e => { e.target.style.borderColor=form.errors.author_name?'#bf616a':'#dde3ee'; e.target.style.boxShadow='none'; }"
                />
                <p v-if="form.errors.author_name" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_name }}</p>
              </div>
              <div>
                <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color:#6b7a96;">
                  Email <span class="text-xs font-normal normal-case tracking-normal" style="color:#8896b0;">(optional)</span>
                </label>
                <input
                  v-model="form.author_email"
                  type="email"
                  class="w-full rounded-xl px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all bg-white"
                  :style="form.errors.author_email ? 'border:1.5px solid #bf616a;' : 'border:1.5px solid #dde3ee;'"
                  placeholder="you@example.com"
                  @focus="e => { if (!form.errors.author_email) e.target.style.borderColor='#5e81ac'; e.target.style.boxShadow='0 0 0 3px rgba(94,129,172,0.15)'; }"
                  @blur="e => { e.target.style.borderColor=form.errors.author_email?'#bf616a':'#dde3ee'; e.target.style.boxShadow='none'; }"
                />
                <p v-if="form.errors.author_email" class="mt-1.5 text-xs text-destructive">{{ form.errors.author_email }}</p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase tracking-wide mb-2" style="color:#6b7a96;">
                Comment <span class="text-destructive">*</span>
              </label>
              <textarea
                v-model="form.body"
                rows="4"
                class="w-full rounded-xl px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all resize-y bg-white"
                :style="form.errors.body ? 'border:1.5px solid #bf616a;' : 'border:1.5px solid #dde3ee;'"
                placeholder="Share your thoughts…"
                required
                @focus="e => { if (!form.errors.body) e.target.style.borderColor='#5e81ac'; e.target.style.boxShadow='0 0 0 3px rgba(94,129,172,0.15)'; }"
                @blur="e => { e.target.style.borderColor=form.errors.body?'#bf616a':'#dde3ee'; e.target.style.boxShadow='none'; }"
              />
              <p v-if="form.errors.body" class="mt-1.5 text-xs text-destructive">{{ form.errors.body }}</p>
            </div>

            <div class="flex items-center justify-between gap-4 pt-1">
              <p class="text-xs" style="color:#8896b0;">Comments are moderated before appearing.</p>
              <button
                type="submit"
                :disabled="form.processing"
                class="shrink-0 text-white px-6 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 disabled:opacity-60"
                style="background:#5e81ac; box-shadow:0 2px 8px rgba(94,129,172,0.35);"
                @mouseenter="e => { e.currentTarget.style.background='#4a6d92'; e.currentTarget.style.boxShadow='0 4px 12px rgba(94,129,172,0.45)'; }"
                @mouseleave="e => { e.currentTarget.style.background='#5e81ac'; e.currentTarget.style.boxShadow='0 2px 8px rgba(94,129,172,0.35)'; }"
              >{{ form.processing ? 'Submitting…' : 'Post comment' }}</button>
            </div>
          </form>
        </div>

        <!-- Comments disabled -->
        <div v-else class="rounded-2xl px-6 py-5 text-sm text-center" style="border:1.5px solid #dde3ee; color:#8896b0;">
          Comments are closed for this post.
        </div>
      </section>
    </div>

    <!-- Sidebar -->
    <div class="lg:pl-2 pt-0">
      <BlogSidebar :sidebar="sidebar" />
    </div>
  </div>
</template>
