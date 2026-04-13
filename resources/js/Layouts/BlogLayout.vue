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
  nextTick(() => { headerSearchInput.value?.focus() })
}

function closeSearch() {
  searchOpen.value = false
  headerQuery.value = ''
}
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

    <!-- Header -->
    <header class="border-b border-gray-200 bg-white">
      <div class="max-w-5xl mx-auto px-6 h-14 flex items-center justify-between">
        <!-- Site name -->
        <Link href="/" class="font-editorial text-xl font-bold text-gray-900 hover:opacity-70 transition-opacity">
          {{ appName }}
        </Link>

        <!-- Nav -->
        <nav class="flex items-center gap-5">
          <template v-for="item in navItems" :key="item.url + '-' + item.label">
            <a
              v-if="item.url.startsWith('http')"
              :href="item.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
            >{{ item.label }}</a>
            <Link
              v-else
              :href="item.url"
              class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
            >{{ item.label }}</Link>
          </template>

          <!-- Search -->
          <div class="flex items-center gap-1">
            <div
              class="overflow-hidden transition-all duration-300 ease-out flex items-center"
              :class="searchOpen ? 'max-w-[180px] opacity-100' : 'max-w-0 opacity-0 pointer-events-none'"
            >
              <form @submit.prevent="submitHeaderSearch" class="flex items-center gap-1 pl-0.5">
                <input
                  ref="headerSearchInput"
                  v-model="headerQuery"
                  type="search"
                  placeholder="Search…"
                  @keydown.escape="closeSearch"
                  class="h-7 w-36 border-b border-gray-300 bg-transparent px-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:border-gray-900"
                />
                <button type="button" @click="closeSearch" class="text-gray-400 hover:text-gray-900 transition-colors p-1 shrink-0" aria-label="Close search">
                  <X class="w-3.5 h-3.5" />
                </button>
              </form>
            </div>
            <button
              type="button"
              @click="searchOpen ? submitHeaderSearch() : openSearch()"
              class="text-gray-400 hover:text-gray-900 transition-colors p-1 shrink-0"
              aria-label="Search"
            >
              <Search class="w-4 h-4" />
            </button>
          </div>

          <Link
            v-if="authUser"
            :href="route('dashboard')"
            class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
          >Dashboard</Link>
          <Link
            v-else
            :href="route('login')"
            class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
          >Sign in</Link>
        </nav>
      </div>
    </header>

    <!-- Main content -->
    <main class="flex-1 max-w-5xl mx-auto w-full px-6 py-12">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200">
      <div class="max-w-5xl mx-auto px-6 py-6 text-center text-xs text-gray-400">
        &copy; {{ year }} {{ appName }}
      </div>
    </footer>

  </div>
</template>
