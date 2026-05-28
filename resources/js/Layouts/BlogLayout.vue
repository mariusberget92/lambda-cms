<script setup>
import { computed, watchEffect } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, PenSquare, LogOut } from '@lucide/vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

defineOptions({ layout: null })

const page      = usePage()
const authUser  = computed(() => page.props.auth?.user)
const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')

const headerBlocks = computed(() => page.props.headerBlocks ?? [])
const footerBlocks = computed(() => page.props.footerBlocks ?? [])

watchEffect(() => {
  const color = page.props.accentColor
  if (color && /^#[0-9a-fA-F]{6}$/.test(color)) {
    document.documentElement.style.setProperty('--accent', color)
    document.documentElement.style.setProperty('--accent-ink', '#ffffff')
  }
})
</script>

<template>
  <Head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="alternate" type="application/rss+xml" :title="page.props.appName" href="/feed" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
  </Head>

  <div class="lambda-blog-root">

    <!-- Admin bar — hardcoded, auth-gated, uses admin design tokens -->
    <div v-if="authUser" data-theme="dark" class="bg-sidebar text-sidebar-foreground border-b border-sidebar-border shrink-0 relative z-50">
      <div class="max-w-[1320px] mx-auto px-8 h-9 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 shrink-0">
          <div class="w-5 h-5 rounded flex items-center justify-center shrink-0 bg-sidebar-primary">
            <span class="text-sidebar-primary-foreground font-bold text-xs leading-none select-none">Λ</span>
          </div>
          <span class="text-xs text-sidebar-foreground/60 hidden sm:inline">Admin</span>
        </div>
        <nav class="flex items-center gap-1 ml-auto">
          <Link :href="route('dashboard')" class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors">
            <LayoutDashboard class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Dashboard</span>
          </Link>
          <Link :href="route('posts.create')" class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors">
            <PenSquare class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">New post</span>
          </Link>
          <div class="w-px h-4 bg-sidebar-border mx-1 shrink-0" />
          <span class="text-xs text-sidebar-foreground/50 hidden sm:inline">{{ authUser.name }}</span>
          <form method="POST" :action="route('logout')" class="inline">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button type="submit" class="flex items-center gap-1.5 px-2 py-1 rounded text-xs text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent transition-colors" title="Sign out">
              <LogOut class="w-3.5 h-3.5" />
            </button>
          </form>
        </nav>
      </div>
    </div>

    <!-- Header — rendered from block editor template (type: 'header') -->
    <BlockRenderer :blocks="headerBlocks" wrapper-class="contents" />

    <!-- Page content -->
    <main class="w-full max-w-[1320px] mx-auto flex-1">
      <slot />
    </main>

    <!-- Footer — rendered from block editor template (type: 'footer') -->
    <BlockRenderer :blocks="footerBlocks" wrapper-class="contents" />

  </div>
</template>
