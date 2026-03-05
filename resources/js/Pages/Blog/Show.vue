<script setup>
import { ref } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  post:     Object,
  sidebar:  Object,
  comments: Array,
  authUser: Object,
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const page = usePage()

const form = useForm({
  author_name:  props.authUser?.name ?? '',
  author_email: props.authUser?.email ?? '',
  body:         '',
  website:      '', // honeypot
})

const submitted = ref(false)

function submitComment() {
  form.post(route('comments.store', props.post.slug), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('body')
      submitted.value = true
    },
  })
}
</script>

<template>
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

      <!-- Post body — safe: admin-authored Tiptap HTML -->
      <div
        class="prose prose-sm max-w-none dark:prose-invert"
        v-html="post.body"
      />

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
          {{ comments?.length ? comments.length + ' Comment' + (comments.length !== 1 ? 's' : '') : 'Comments' }}
        </h2>

        <!-- Flash: submitted confirmation -->
        <Transition name="fade">
          <div
            v-if="page.props.flash?.status"
            class="mb-6 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ page.props.flash.status }}
          </div>
        </Transition>

        <!-- Comment list -->
        <div v-if="comments?.length" class="space-y-6 mb-10">
          <div
            v-for="comment in comments"
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
        <p v-else class="text-sm text-muted-foreground mb-10">No comments yet. Be the first!</p>

        <!-- Submission form -->
        <div class="rounded-lg border bg-card p-6">
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
                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
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
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
