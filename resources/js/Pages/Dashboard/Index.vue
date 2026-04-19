<script setup>
import { computed, watch } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatCard from '@/Components/StatCard.vue'
import ContentCard from '@/Components/ContentCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useNotifications } from '@/composables/useNotifications'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({ total: 0, published: 0, scheduled: 0, drafts: 0, pendingCommentsCount: 0 }),
  },
  upcoming_scheduled: {
    type: Array,
    default: () => [],
  },
  recent_posts: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? { name: '', role: '' })

const { notify } = useNotifications()

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.status) notify(flash.status, 'success')
    if (flash?.error)  notify(flash.error,  'error')
  }
)

function formatScheduled(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}
</script>

<template>
  <AppLayout title="Dashboard">
    <Head title="Dashboard" />

    <PageHeader title="Dashboard" description="Overview of your blog." />

    <!-- Stat cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
      <StatCard label="Total Posts" :value="stats.total" color="blue">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard label="Published" :value="stats.published" color="green">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard label="Scheduled" :value="stats.scheduled" color="cyan">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard label="Drafts" :value="stats.drafts" color="yellow">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard
        label="Pending Comments"
        :value="stats.pendingCommentsCount"
        color="red"
        :href="route('comments.index') + '?filter=pending'"
      >
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
          </svg>
        </template>
      </StatCard>
    </div>

    <!-- Two-column panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

      <ContentCard title="Upcoming scheduled posts">
        <template #actions>
          <a :href="route('calendar')" class="text-xs text-primary hover:underline">View calendar →</a>
        </template>
        <div v-if="upcoming_scheduled.length === 0" class="text-center py-6">
          <svg class="w-8 h-8 text-muted-foreground/40 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <p class="text-sm text-muted-foreground">No posts scheduled.</p>
        </div>
        <ul v-else class="divide-y divide-border -mx-6 -mb-5">
          <li v-for="post in upcoming_scheduled" :key="post.id" class="px-6 py-3 first:pt-0">
            <a :href="route('posts.edit', post.id)" class="block font-medium text-sm line-clamp-1 hover:text-primary transition-colors">{{ post.title }}</a>
            <div class="flex items-center gap-2 mt-0.5 text-xs text-muted-foreground">
              <span>{{ formatScheduled(post.published_at) }}</span>
              <span>·</span>
              <span>{{ post.author_name }}</span>
            </div>
          </li>
        </ul>
      </ContentCard>

      <ContentCard title="Recent posts">
        <template #actions>
          <a :href="route('posts.index')" class="text-xs text-primary hover:underline">View all →</a>
        </template>
        <div v-if="recent_posts.length === 0" class="text-center py-6">
          <svg class="w-8 h-8 text-muted-foreground/40 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="text-sm text-muted-foreground">No posts yet.</p>
        </div>
        <ul v-else class="divide-y divide-border -mx-6 -mb-5">
          <li v-for="post in recent_posts" :key="post.id" class="px-6 py-3 first:pt-0 flex items-center justify-between gap-3">
            <a :href="route('posts.edit', post.id)" class="font-medium text-sm line-clamp-1 hover:text-primary transition-colors flex-1 min-w-0">{{ post.title }}</a>
            <div class="flex items-center gap-2 shrink-0">
              <StatusBadge :status="post.status" />
              <span class="text-xs text-muted-foreground">{{ timeAgo(post.updated_at) }}</span>
            </div>
          </li>
        </ul>
      </ContentCard>
    </div>

    <!-- Quick actions -->
    <ContentCard title="Quick actions">
      <div class="flex flex-wrap gap-3">
        <a :href="route('posts.create')" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          New post
        </a>
        <a :href="route('posts.index')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          All posts
        </a>
        <a :href="route('media.index')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Media library
        </a>
        <a v-if="user.role === 'administrator'" :href="route('pages.index')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v8a2 2 0 01-2 2z"/></svg>
          Pages
        </a>
      </div>
    </ContentCard>
  </AppLayout>
</template>
