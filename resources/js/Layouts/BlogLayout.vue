<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: null })

const appName = computed(() => usePage().props.appName ?? 'Lambda CMS')
const authUser = computed(() => usePage().props.auth?.user)
const year = new Date().getFullYear()
</script>

<template>
  <Head>
    <link rel="alternate" type="application/rss+xml" :title="appName" href="/feed" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
  </Head>
  <div class="min-h-screen flex flex-col bg-background text-foreground">
    <!-- Top nav -->
    <header class="border-b bg-card/80 backdrop-blur-sm sticky top-0 z-10">
      <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
        <Link href="/" class="font-semibold text-base hover:opacity-80 transition-opacity">
          {{ appName }}
        </Link>
        <nav>
          <Link
            v-if="authUser"
            :href="route('dashboard')"
            class="text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            Dashboard
          </Link>
          <Link
            v-else
            :href="route('login')"
            class="text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            Sign in
          </Link>
        </nav>
      </div>
    </header>

    <!-- Hero strip -->
    <div class="bg-primary/5 border-b">
      <div class="max-w-5xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold tracking-tight">{{ appName }}</h1>
        <p class="mt-1 text-muted-foreground text-base">A simple, clean blog powered by Lambda CMS.</p>
      </div>
    </div>

    <!-- Main content -->
    <main class="flex-1 max-w-5xl mx-auto w-full px-4 py-10">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t">
      <div class="max-w-5xl mx-auto px-4 py-5 text-center text-xs text-muted-foreground">
        &copy; {{ year }} {{ appName }}
      </div>
    </footer>
  </div>
</template>
