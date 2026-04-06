<script setup>
import { Head, usePage, Link, router } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'
import { Search, X } from 'lucide-vue-next'

defineOptions({ layout: null })

const appName  = computed(() => usePage().props.appName ?? 'Lambda CMS')
const authUser  = computed(() => usePage().props.auth?.user)
const navItems  = computed(() => usePage().props.navItems ?? [])
const year = new Date().getFullYear()

const searchOpen = ref(false)
const headerQuery = ref('')
const headerSearchInput = ref(null)

function submitHeaderSearch() {
  const q = headerQuery.value.trim()
  if (!q) return
  closeSearch()
  router.get(route('search'), { q })
}

function openSearch() {
  searchOpen.value = true
  nextTick(() => {
    headerSearchInput.value?.focus()
  })
}

function closeSearch() {
  searchOpen.value = false
  headerQuery.value = ''
}
</script>

<template>
  <Head>
    <link rel="alternate" type="application/rss+xml" :title="appName" href="/feed" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
  </Head>
  <div class="min-h-screen flex flex-col bg-background text-foreground">
    <!-- Top nav -->
    <header class="border-b bg-gradient-to-b from-card/95 to-card/80 backdrop-blur-md sticky top-0 z-10 shadow-sm">
      <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
        <Link href="/" class="font-semibold text-base hover:opacity-80 transition-opacity">
          {{ appName }}
        </Link>
        <nav class="flex items-center gap-4">
          <template v-for="item in navItems" :key="item.url + '-' + item.label">
            <a
              v-if="item.url.startsWith('http')"
              :href="item.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-sm text-muted-foreground hover:text-foreground hover:border-b-2 hover:border-primary transition-colors"
            >{{ item.label }}</a>
            <Link
              v-else
              :href="item.url"
              class="text-sm text-muted-foreground hover:text-foreground hover:border-b-2 hover:border-primary transition-colors"
            >{{ item.label }}</Link>
          </template>

          <!-- Search -->
          <div class="flex items-center">
            <form
              v-if="searchOpen"
              @submit.prevent="submitHeaderSearch"
              class="flex items-center gap-1"
            >
              <input
                ref="headerSearchInput"
                v-model="headerQuery"
                type="search"
                placeholder="Search…"
                @keydown.escape="closeSearch"
                class="h-7 w-40 rounded-md border bg-background px-2.5 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary"
              />
              <button type="submit" class="text-muted-foreground hover:text-foreground transition-colors p-1">
                <Search class="w-4 h-4" />
              </button>
              <button type="button" @click="closeSearch" class="text-muted-foreground hover:text-foreground transition-colors p-1" aria-label="Close search">
                <X class="w-3.5 h-3.5" />
              </button>
            </form>
            <button
              v-else
              type="button"
              @click="openSearch"
              class="text-muted-foreground hover:text-foreground transition-colors p-1"
              aria-label="Search"
            >
              <Search class="w-4 h-4" />
            </button>
          </div>

          <Link
            v-if="authUser"
            :href="route('dashboard')"
            class="text-sm text-muted-foreground hover:text-foreground hover:border-b-2 hover:border-primary transition-colors"
          >Dashboard</Link>
          <Link
            v-else
            :href="route('login')"
            class="text-sm text-muted-foreground hover:text-foreground hover:border-b-2 hover:border-primary transition-colors"
          >Sign in</Link>
        </nav>
      </div>
    </header>

    <!-- Hero strip -->
    <div class="relative overflow-hidden bg-gradient-to-br from-[#2e3440] to-[#3b4252]">
      <!-- Radial glow blob -->
      <div class="absolute inset-0 pointer-events-none"
           style="background: radial-gradient(ellipse 60% 80% at 70% 50%, rgba(94,129,172,0.28) 0%, transparent 70%)" />
      <!-- Bottom fade into page -->
      <div class="absolute bottom-0 left-0 right-0 h-10 pointer-events-none bg-gradient-to-b from-transparent to-background" />
      <div class="relative max-w-5xl mx-auto px-4 py-14">
        <div class="border-l-4 border-primary pl-5"
             style="box-shadow: -3px 0 18px rgba(94,129,172,0.4)">
          <h1 class="text-4xl font-bold tracking-tight text-white">{{ appName }}</h1>
          <p class="mt-2 text-base" style="color: rgba(216,222,233,0.72)">
            A simple, clean blog powered by Lambda CMS.
          </p>
        </div>
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
