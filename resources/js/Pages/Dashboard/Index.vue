<template>
  <AppLayout title="Dashboard">
    <Head title="Dashboard" />

    <!-- Error flash (e.g. unauthorised access redirect) -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.error"
        class="flex items-center gap-2 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 mb-6"
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

    <!-- Stats -->
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
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

      <div class="rounded-lg border bg-card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Published</p>
          <div class="w-8 h-8 rounded-md bg-green-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.published }}</p>
      </div>

      <div class="rounded-lg border bg-card p-5">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-medium text-muted-foreground">Drafts</p>
          <div class="w-8 h-8 rounded-md bg-amber-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </div>
        </div>
        <p class="text-3xl font-bold">{{ stats.drafts }}</p>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="rounded-lg border bg-card p-5">
      <h3 class="text-sm font-semibold mb-3">Quick actions</h3>
      <div class="flex flex-wrap gap-3">
        <a
          :href="route('posts.create')"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
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

defineProps({
  stats: {
    type: Object,
    default: () => ({ total: 0, published: 0, drafts: 0 }),
  },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? { name: "" });
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
