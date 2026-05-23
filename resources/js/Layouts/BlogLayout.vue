<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, PenSquare, LogOut, Menu, X } from '@lucide/vue'

defineOptions({ layout: null })

const page      = usePage()
const authUser  = computed(() => page.props.auth?.user)
const appName   = computed(() => page.props.appName ?? 'Blog')
const navItems  = computed(() => page.props.navItems ?? [])
const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')
const year      = new Date().getFullYear()

const mobileOpen = ref(false)

// Public frontend always renders in light mode
onMounted(() => {
  document.documentElement.classList.remove('dark')
})
onBeforeUnmount(() => {
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

  <div class="min-h-screen flex flex-col text-foreground" style="background:#eef2f9;">

    <!-- Admin bar -->
    <div v-if="authUser" data-theme="dark" class="bg-sidebar text-sidebar-foreground border-b border-sidebar-border shrink-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-9 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 shrink-0">
          <div class="w-5 h-5 rounded flex items-center justify-center shrink-0 bg-sidebar-primary">
            <span class="text-sidebar-primary-foreground font-bold text-xs leading-none select-none">Λ</span>
          </div>
          <span class="text-xs text-sidebar-foreground/60 hidden sm:inline">Admin</span>
        </div>
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
            <button type="submit" class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors" title="Sign out">
              <LogOut class="w-3.5 h-3.5" />
              <span class="hidden sm:inline">Sign out</span>
            </button>
          </form>
        </nav>
      </div>
    </div>

    <!-- Site header -->
    <header class="sticky top-0 z-40 shrink-0 shadow-sm" style="background:rgba(255,255,255,0.97); backdrop-filter:blur(8px);">
      <!-- Colorful gradient accent strip -->
      <div class="h-[3px]" style="background:linear-gradient(90deg,#5e81ac,#88c0d0,#a3be8c,#ebcb8b,#d08770);" />

      <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-15">
        <!-- Site name / logo -->
        <Link href="/" class="font-editorial text-xl font-bold shrink-0 hover:opacity-80 transition-opacity" style="color:#5e81ac;">
          {{ appName }}
        </Link>

        <!-- Desktop nav -->
        <nav v-if="navItems.length" class="hidden md:flex items-center gap-7 ml-10">
          <Link
            v-for="item in navItems"
            :key="item.url"
            :href="item.url"
            class="text-sm font-medium transition-colors relative group"
            style="color:#4c566a;"
          >
            {{ item.label }}
            <span class="absolute -bottom-0.5 left-0 right-0 h-[2px] rounded-full scale-x-0 group-hover:scale-x-100 transition-transform origin-left" style="background:#5e81ac;" />
          </Link>
        </nav>

        <!-- Mobile hamburger -->
        <button
          v-if="navItems.length"
          class="md:hidden p-2 -mr-1 rounded-md transition-colors"
          style="color:#4c566a;"
          :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
          @click="mobileOpen = !mobileOpen"
        >
          <X v-if="mobileOpen" class="w-5 h-5" />
          <Menu v-else class="w-5 h-5" />
        </button>
      </div>

      <!-- Mobile nav dropdown -->
      <div v-if="mobileOpen && navItems.length" class="md:hidden border-t border-border bg-white px-4 pb-3 pt-2 space-y-0.5">
        <Link
          v-for="item in navItems"
          :key="item.url"
          :href="item.url"
          class="block py-2 px-2 rounded-md text-sm font-medium text-foreground/80 hover:text-primary hover:bg-primary/5 transition-colors"
          @click="mobileOpen = false"
        >{{ item.label }}</Link>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 w-full max-w-6xl mx-auto">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="shrink-0 mt-16" style="background:rgba(255,255,255,0.7); border-top:1px solid #dde3ee;">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <span class="font-editorial font-bold text-base" style="color:#5e81ac;">{{ appName }}</span>
        <div class="flex items-center gap-1 text-sm" style="color:#6b7a96;">
          <span>© {{ year }}</span>
          <span class="mx-2 opacity-40">·</span>
          <a href="/feed" class="hover:text-primary transition-colors">RSS</a>
          <span class="mx-2 opacity-40">·</span>
          <a href="/sitemap.xml" class="hover:text-primary transition-colors">Sitemap</a>
        </div>
      </div>
    </footer>

  </div>
</template>
