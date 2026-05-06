<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3'
import { computed, onMounted, onBeforeUnmount } from 'vue'
import { LayoutDashboard, PenSquare, LogOut } from 'lucide-vue-next'

defineOptions({ layout: null })

const authUser  = computed(() => usePage().props.auth?.user)
const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')

// Public frontend always renders in light mode — strip the admin dark class while here.
onMounted(() => {
  document.documentElement.classList.remove('dark')
})

onBeforeUnmount(() => {
  // Restore the admin theme preference when navigating back to the dashboard.
  const saved = localStorage.getItem('lambda-cms-theme')
  if (saved === 'dark') document.documentElement.classList.add('dark')
})
</script>

<template>
  <Head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet" />
    <link rel="alternate" type="application/rss+xml" :title="appName" href="/feed" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
  </Head>

  <div class="min-h-screen flex flex-col bg-white text-gray-900">

    <!-- Admin bar — only visible when signed in -->
    <div v-if="authUser" data-theme="dark" class="bg-sidebar text-sidebar-foreground border-b border-sidebar-border">
      <div class="max-w-6xl mx-auto px-6 h-9 flex items-center justify-between gap-4">
        <!-- Left: Lambda logo + label -->
        <div class="flex items-center gap-2 shrink-0">
          <div class="w-10 h-10 rounded flex items-center justify-center shrink-0">
            <img :src="'/storage/assets/logo-light.png'" />
          </div>
        </div>

        <!-- Right: quick actions -->
        <nav class="flex items-center gap-1 ml-auto">
          <Link
            :href="route('dashboard')"
            class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors"
          >
            <LayoutDashboard class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Dashboard</span>
          </Link>

          <Link
            :href="route('posts.create')"
            class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors"
          >
            <PenSquare class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">New post</span>
          </Link>

          <div class="w-px h-4 bg-sidebar-border mx-1 shrink-0" />
          <span class="text-xs text-sidebar-foreground/50 hidden sm:inline">{{ authUser.name }}</span>

          <form method="POST" :action="route('logout')" class="inline">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button
              type="submit"
              class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors"
              title="Sign out"
            >
              <LogOut class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">Sign out</span>
            </button>
          </form>
        </nav>
      </div>
    </div>

    <!-- Main content -->
    <main class="flex-1 w-full max-w-6xl mx-auto">
      <slot />
    </main>

  </div>
</template>
