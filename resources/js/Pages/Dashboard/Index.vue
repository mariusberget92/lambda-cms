<template>
  <AppLayout title="Dashboard">
    <Head title="Dashboard" />

    <!-- Error flash -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.error"
        class="flex items-center gap-2 rounded-md bg-status-error-bg border border-status-error-border px-4 py-3 text-sm text-status-error-fg mb-6"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        {{ $page.props.flash.error }}
      </div>
    </Transition>

    <!-- Welcome -->
    <div class="mb-6">
      <h2 class="text-lg font-semibold">Good to see you, {{ user.name }}</h2>
      <p class="text-sm text-muted-foreground mt-0.5">Here's what's happening with your blog.</p>
    </div>

    <!-- Stats (5 cards) -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">

      <!-- Total Posts -->
      <div class="rounded-lg border bg-card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Total Posts</p>
          <div class="w-8 h-8 rounded-md bg-muted flex items-center justify-center">
            <svg class="w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.total }}</p>
      </div>

      <!-- Published -->
      <div class="rounded-lg border bg-card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Published</p>
          <div class="w-8 h-8 rounded-md bg-status-success-bg flex items-center justify-center">
            <svg class="w-4 h-4 text-status-success-fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.published }}</p>
      </div>

      <!-- Scheduled -->
      <div class="rounded-lg border bg-card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Scheduled</p>
          <div class="w-8 h-8 rounded-md bg-indigo-50 flex items-center justify-center">
            <svg class="w-4 h-4 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.scheduled }}</p>
      </div>

      <!-- Drafts -->
      <div class="rounded-lg border bg-card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Drafts</p>
          <div class="w-8 h-8 rounded-md bg-status-warning-bg flex items-center justify-center">
            <svg class="w-4 h-4 text-status-warning-fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.drafts }}</p>
      </div>

      <!-- Pending Comments -->
      <a :href="route('comments.index') + '?filter=pending'" class="rounded-lg border bg-card p-5 hover:bg-accent transition-colors">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Pending Comments</p>
          <div class="w-8 h-8 rounded-md bg-muted flex items-center justify-center">
            <svg class="w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.pendingCommentsCount }}</p>
      </a>
    </div>

    <!-- Two-column panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

      <!-- Upcoming Scheduled Posts -->
      <div class="rounded-lg border bg-card p-5">
        <h3 class="text-sm font-semibold mb-4">Upcoming scheduled posts</h3>
        <div v-if="upcoming_scheduled.length === 0" class="text-sm text-muted-foreground text-center py-6">
          No posts scheduled.
        </div>
        <ul v-else class="divide-y divide-border">
          <li v-for="post in upcoming_scheduled" :key="post.id" class="py-3 first:pt-0 last:pb-0">
            <a
              :href="route('posts.edit', post.id)"
              class="block font-medium text-sm line-clamp-1 hover:text-primary transition-colors"
            >{{ post.title }}</a>
            <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
              <span>{{ formatScheduled(post.published_at) }}</span>
              <span>·</span>
              <span>{{ post.author_name }}</span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Recent Posts -->
      <div class="rounded-lg border bg-card p-5">
        <h3 class="text-sm font-semibold mb-4">Recent posts</h3>
        <div v-if="recent_posts.length === 0" class="text-sm text-muted-foreground text-center py-6">
          No posts yet.
        </div>
        <ul v-else class="divide-y divide-border">
          <li
            v-for="post in recent_posts"
            :key="post.id"
            class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3"
          >
            <a
              :href="route('posts.edit', post.id)"
              class="font-medium text-sm line-clamp-1 hover:text-primary transition-colors flex-1 min-w-0"
            >{{ post.title }}</a>
            <div class="flex items-center gap-2 shrink-0">
              <StatusBadge :status="post.status" />
              <span class="text-xs text-muted-foreground">{{ timeAgo(post.updated_at) }}</span>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="rounded-lg border bg-card p-5">
      <h3 class="text-sm font-semibold mb-3">Quick actions</h3>
      <div class="flex flex-wrap gap-3">
        <a
          :href="route('posts.create')"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New post
        </a>
        <a
          :href="route('posts.index')"
          class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          View all posts
        </a>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

defineProps({
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
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? { name: "" });

// Format an ISO 8601 date string as "14 Mar 2026, 09:00"
function formatScheduled(isoString) {
  return new Date(isoString).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

// Return relative time string from an ISO 8601 date string
function timeAgo(isoString) {
  const diff = Date.now() - new Date(isoString).getTime();
  const minutes = Math.floor(diff / 60000);
  if (minutes < 1) return "just now";
  if (minutes < 60) return minutes + "m ago";
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return hours + "h ago";
  const days = Math.floor(hours / 24);
  return days + "d ago";
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
