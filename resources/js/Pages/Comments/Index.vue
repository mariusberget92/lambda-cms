<template>
  <AppLayout title="Comments">
    <Head title="Comments" />

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Comments</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Moderate reader comments</p>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-4 border-b">
      <a
        v-for="tab in tabs"
        :key="tab.value"
        :href="route('comments.index') + (tab.value !== 'pending' ? '?filter=' + tab.value : '')"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="filter === tab.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
      >
        {{ tab.label }}
        <span v-if="tab.value === 'pending' && pendingCount" class="ml-1.5 rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-semibold text-destructive-foreground">{{ pendingCount }}</span>
      </a>
    </div>

    <!-- Bulk actions bar -->
    <Transition name="fade">
      <div v-if="selected.length" class="flex items-center gap-3 mb-4 rounded-md border bg-muted/50 px-4 py-2.5 text-sm">
        <span class="text-muted-foreground">{{ selected.length }} selected</span>
        <div class="flex gap-2 ml-auto">
          <button type="button" @click="bulkAction('approve')" class="rounded-md bg-status-success-bg px-3 py-1.5 text-xs font-medium text-status-success-fg hover:opacity-80 transition-opacity">Approve</button>
          <button type="button" @click="bulkAction('reject')" class="rounded-md bg-status-warning-bg px-3 py-1.5 text-xs font-medium text-status-warning-fg hover:opacity-80 transition-opacity">Reject</button>
          <button type="button" @click="bulkAction('delete')" class="rounded-md bg-destructive/10 px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors">Delete</button>
        </div>
      </div>
    </Transition>

    <!-- Empty state -->
    <div v-if="comments.data.length === 0" class="py-16 text-center">
      <MessageSquare class="w-10 h-10 mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground text-sm">No comments in this category.</p>
    </div>

    <!-- Comment cards -->
    <div v-else class="space-y-3">
      <div
        v-for="comment in comments.data"
        :key="comment.id"
        class="rounded-lg border bg-card"
      >
        <!-- Card header -->
        <div class="flex items-start gap-3 p-4">
          <!-- Checkbox -->
          <input
            type="checkbox"
            :value="comment.id"
            v-model="selected"
            class="mt-1 rounded border-border accent-nord-green"
          />

          <!-- Avatar -->
          <div
            class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold text-white"
            :style="{ backgroundColor: avatarColor(comment.author_name) }"
          >
            {{ initials(comment.author_name) }}
          </div>

          <!-- Author + meta -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-semibold">{{ comment.author_name }}</span>
              <span v-if="comment.author_email" class="text-xs text-muted-foreground">{{ comment.author_email }}</span>
              <span class="text-xs text-muted-foreground">· {{ comment.created_at }}</span>
            </div>
            <a
              :href="`/blog/${comment.post.slug}`"
              target="_blank"
              rel="noopener"
              class="text-xs text-primary hover:underline mt-0.5 inline-block"
            >
              {{ comment.post.title }}
            </a>
          </div>

          <!-- Status badge -->
          <StatusBadge :status="comment.status" class="shrink-0" />
        </div>

        <!-- Body -->
        <div class="px-4 pb-3 pl-16">
          <p class="text-sm text-foreground whitespace-pre-wrap leading-relaxed">
            {{ expanded[comment.id] ? comment.body : truncate(comment.body) }}
          </p>
          <button
            v-if="comment.body.length > 300"
            type="button"
            class="mt-1 text-xs text-primary hover:underline"
            @click="toggleExpanded(comment.id)"
          >
            {{ expanded[comment.id] ? 'Show less' : 'Show more' }}
          </button>
        </div>

        <!-- Action footer -->
        <div class="flex items-center gap-2 px-4 py-2.5 border-t pl-16 flex-wrap">
          <button
            v-if="comment.status !== 'approved'"
            type="button"
            @click="router.patch(route('comments.approve', comment.id))"
            class="rounded-md bg-status-success-bg px-3 py-1 text-xs font-medium text-status-success-fg hover:opacity-80 transition-opacity"
          >Approve</button>
          <button
            v-if="comment.status !== 'rejected'"
            type="button"
            @click="router.patch(route('comments.reject', comment.id))"
            class="rounded-md bg-status-warning-bg px-3 py-1 text-xs font-medium text-status-warning-fg hover:opacity-80 transition-opacity"
          >Reject</button>
          <button
            type="button"
            @click="confirmDelete(comment.id)"
            class="rounded-md bg-destructive/10 px-3 py-1 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors"
          >Delete</button>

          <!-- Reply button -->
          <button
            type="button"
            class="ml-auto rounded-md border px-3 py-1 text-xs font-medium hover:bg-accent transition-colors flex items-center gap-1"
            @click="toggleReply(comment.id)"
          >
            <MessageSquare class="w-3 h-3" />
            {{ comment.replies?.length ? `Reply (${comment.replies.length})` : 'Reply' }}
          </button>
        </div>

        <!-- Existing replies -->
        <div v-if="comment.replies?.length" class="border-t divide-y divide-border ml-16">
          <div v-for="reply in comment.replies" :key="reply.id" class="px-4 py-3 bg-muted/20">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-semibold">{{ reply.author_name }}</span>
              <span class="text-xs text-muted-foreground">· {{ reply.created_at }}</span>
              <span class="text-[10px] bg-primary/10 text-primary px-1.5 py-0.5 rounded-full font-medium">Admin reply</span>
            </div>
            <p class="text-sm text-foreground whitespace-pre-wrap">{{ reply.body }}</p>
          </div>
        </div>

        <!-- Inline reply form -->
        <div v-if="replyingTo === comment.id" class="border-t px-4 py-3 ml-16 bg-muted/10">
          <textarea
            v-model="replyBody"
            rows="3"
            placeholder="Write a reply…"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <div class="flex gap-2 mt-2">
            <button
              type="button"
              class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50"
              :disabled="!replyBody.trim()"
              @click="sendReply(comment.id)"
            >Send reply</button>
            <button
              type="button"
              class="rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent"
              @click="cancelReply"
            >Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="comments.last_page > 1" class="mt-6 flex justify-center gap-2">
      <a
        v-for="page in comments.links"
        :key="page.label"
        :href="page.url"
        class="rounded-md border px-3 py-1.5 text-sm transition-colors"
        :class="page.active
          ? 'bg-primary text-primary-foreground border-primary'
          : page.url ? 'hover:bg-accent' : 'opacity-40 cursor-default pointer-events-none'"
      >{{ decodeHtmlEntities(page.label) }}</a>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { MessageSquare } from 'lucide-vue-next'

const props = defineProps({
  comments:     Object,
  filter:       { type: String, default: 'pending' },
  pendingCount: { type: Number, default: 0 },
})

const tabs = [
  { value: 'pending',  label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'all',      label: 'All' },
]

// Selection
const selected = ref([])

function bulkAction(action) {
  router.post(route('comments.bulk'), { action, ids: selected.value }, {
    onSuccess: () => { selected.value = [] },
  })
}

// Show more / less
const expanded = reactive({})
function truncate(body) {
  return body.length > 300 ? body.slice(0, 300) + '…' : body
}
function toggleExpanded(id) {
  expanded[id] = !expanded[id]
}

// Reply
const replyingTo = ref(null)
const replyBody  = ref('')

function toggleReply(id) {
  replyingTo.value = replyingTo.value === id ? null : id
  replyBody.value  = ''
}

function cancelReply() {
  replyingTo.value = null
  replyBody.value  = ''
}

function sendReply(commentId) {
  router.post(route('comments.reply', commentId), { body: replyBody.value }, {
    onSuccess: () => cancelReply(),
  })
}

// Avatar helpers — Nord accent color palette
const AVATAR_COLORS = [
  '#5e81ac', '#88c0d0', '#a3be8c', '#ebcb8b', '#d08770', '#b48ead',
]

function avatarColor(name) {
  const code = (name ?? 'A').charCodeAt(0)
  return AVATAR_COLORS[code % AVATAR_COLORS.length]
}

function initials(name) {
  return (name ?? '?')
    .split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

function confirmDelete(id) {
  if (!window.confirm('Delete this comment? This cannot be undone.')) return
  router.delete(route('comments.destroy', id))
}

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea')
  txt.innerHTML = str
  return txt.value
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
