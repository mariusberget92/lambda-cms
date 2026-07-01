<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatCard from '@/Components/StatCard.vue'
import ContentCard from '@/Components/ContentCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { StickyNote, Phone as PhoneIcon, Mail, CalendarDays, ListChecks } from '@lucide/vue'

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
  crm: {
    type: Object,
    default: null,
  },
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? { name: '', role: '' })
const permissions = computed(() => page.props.auth?.user?.permissions ?? [])
function userCan(p) { return permissions.value.includes(p) }

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

function formatCurrency(value) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(value)
}

const activityIcons = { note: StickyNote, call: PhoneIcon, email: Mail, meeting: CalendarDays, task: ListChecks }
const activityColors = {
  note: 'bg-blue-500/10 text-blue-500',
  call: 'bg-green-500/10 text-green-500',
  email: 'bg-purple-500/10 text-purple-500',
  meeting: 'bg-orange-500/10 text-orange-500',
  task: 'bg-yellow-500/10 text-yellow-500',
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
          <a :href="route('calendar')" class="text-xs text-primary hover:underline">View calendar</a>
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
              <span>&middot;</span>
              <span>{{ post.author_name }}</span>
            </div>
          </li>
        </ul>
      </ContentCard>

      <ContentCard title="Recent posts">
        <template #actions>
          <a :href="route('posts.index')" class="text-xs text-primary hover:underline">View all</a>
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

    <!-- CRM Section -->
    <template v-if="crm">
      <div class="flex items-center gap-2 mt-8 mb-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">CRM</h2>
        <div class="flex-1 h-px bg-border" />
      </div>

      <!-- CRM stat cards -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-4">
        <StatCard
          v-if="crm.contacts_count !== null"
          label="Contacts"
          :value="crm.contacts_count"
          color="blue"
          :href="route('contacts.index')"
        >
          <template #icon>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </template>
        </StatCard>

        <StatCard
          v-if="crm.companies_count !== null"
          label="Companies"
          :value="crm.companies_count"
          color="purple"
          :href="route('companies.index')"
        >
          <template #icon>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </template>
        </StatCard>

        <StatCard
          v-if="crm.open_deals_count !== null"
          label="Open Pipeline"
          :value="formatCurrency(crm.open_deals_value)"
          color="green"
          :href="route('deals.index')"
        >
          <template #icon>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </template>
        </StatCard>

        <StatCard
          v-if="crm.active_call_lists !== null"
          label="Active Call Lists"
          :value="crm.active_call_lists"
          color="cyan"
          :href="route('call-lists.index')"
        >
          <template #icon>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
          </template>
        </StatCard>
      </div>

      <!-- CRM panels -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        <!-- Recent CRM activity -->
        <ContentCard title="Recent CRM activity">
          <div v-if="!crm.recent_activities?.length" class="text-center py-6">
            <svg class="w-8 h-8 text-muted-foreground/40 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-muted-foreground">No activities logged yet.</p>
          </div>
          <ul v-else class="divide-y divide-border -mx-6 -mb-5">
            <li v-for="a in crm.recent_activities" :key="a.id" class="px-6 py-3 first:pt-0">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" :class="activityColors[a.type] ?? 'bg-muted text-muted-foreground'">
                  <component :is="activityIcons[a.type] ?? StickyNote" class="w-3 h-3" />
                </div>
                <span class="text-sm font-medium line-clamp-1 flex-1 min-w-0">{{ a.description }}</span>
              </div>
              <div class="flex items-center gap-2 mt-1 ml-8 text-xs text-muted-foreground">
                <span class="capitalize">{{ a.type }}</span>
                <span>&middot;</span>
                <span>{{ a.subject_type }}: {{ a.subject_name }}</span>
                <span>&middot;</span>
                <span>{{ timeAgo(a.occurred_at) }}</span>
              </div>
            </li>
          </ul>
        </ContentCard>

        <!-- Upcoming callbacks -->
        <ContentCard title="Pending callbacks">
          <template #actions>
            <a v-if="userCan('manage call lists')" :href="route('call-lists.index')" class="text-xs text-primary hover:underline">Call lists</a>
          </template>
          <div v-if="!crm.upcoming_callbacks?.length" class="text-center py-6">
            <svg class="w-8 h-8 text-muted-foreground/40 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <p class="text-sm text-muted-foreground">No pending callbacks.</p>
          </div>
          <ul v-else class="divide-y divide-border -mx-6 -mb-5">
            <li v-for="(cb, i) in crm.upcoming_callbacks" :key="i" class="px-6 py-3 first:pt-0">
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium line-clamp-1">{{ cb.contact_name }}</p>
                  <div class="flex items-center gap-2 mt-0.5 text-xs text-muted-foreground">
                    <span v-if="cb.phone">{{ cb.phone }}</span>
                    <span v-if="cb.phone">&middot;</span>
                    <span>{{ cb.list_name }}</span>
                  </div>
                  <p v-if="cb.notes" class="text-xs text-muted-foreground mt-1 line-clamp-1 italic">{{ cb.notes }}</p>
                </div>
                <a
                  :href="route('call-lists.work', cb.list_id)"
                  class="shrink-0 inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium hover:bg-accent transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  Call
                </a>
              </div>
            </li>
          </ul>
        </ContentCard>
      </div>
    </template>

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
        <a v-if="userCan('manage contacts')" :href="route('contacts.create')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          New contact
        </a>
        <a v-if="userCan('manage deals')" :href="route('deals.create')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          New deal
        </a>
      </div>
    </ContentCard>
  </AppLayout>
</template>
