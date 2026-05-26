<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, PenSquare, LogOut, Menu, X, Search, Command } from '@lucide/vue'
import axios from 'axios'

defineOptions({ layout: null })

const page      = usePage()
const authUser  = computed(() => page.props.auth?.user)
const appName   = computed(() => page.props.appName ?? 'Blog')
const navItems  = computed(() => page.props.navItems ?? [])
const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')
const year      = new Date().getFullYear()

const mobileOpen   = ref(false)
const searchOpen   = ref(false)
const searchQuery  = ref('')
const searchResult = ref([])
const searchLoading = ref(false)

// Theme palette — stored in localStorage
const palette = ref('system')
const PALETTES = ['system', 'terminal', 'synth']

// Blog frontend always renders in light mode (admin dark mode handled via .dark class)
onMounted(() => {
  document.documentElement.classList.remove('dark')
  const saved = localStorage.getItem('lambda-blog-palette')
  if (PALETTES.includes(saved)) palette.value = saved
  document.addEventListener('keydown', onKeyDown)
})
onBeforeUnmount(() => {
  const saved = localStorage.getItem('lambda-cms-theme')
  if (saved === 'dark') document.documentElement.classList.add('dark')
  document.removeEventListener('keydown', onKeyDown)
})

function setPalette(p) {
  palette.value = p
  localStorage.setItem('lambda-blog-palette', p)
}

// ⌘K / Ctrl+K search shortcut
function onKeyDown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    searchOpen.value = !searchOpen.value
    if (searchOpen.value) {
      searchQuery.value = ''
      searchResult.value = []
    }
  }
  if (e.key === 'Escape') {
    searchOpen.value = false
  }
}

let searchTimer = null
function onSearchInput(v) {
  searchQuery.value = v
  clearTimeout(searchTimer)
  if (!v.trim()) { searchResult.value = []; return }
  searchLoading.value = true
  searchTimer = setTimeout(async () => {
    try {
      const { data } = await axios.post('/api/v1/query', {
        source: 'posts',
        filters: [{ field: 'title', op: 'contains', value: v }],
        sort: { field: 'published_at', direction: 'desc' },
        limit: 6,
        offset: 0,
      })
      searchResult.value = data.items ?? []
    } catch (_) {
      searchResult.value = []
    } finally {
      searchLoading.value = false
    }
  }, 200)
}

function closeSearch() {
  searchOpen.value = false
}
</script>

<template>
  <Head>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500&family=Inter:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="alternate" type="application/rss+xml" :title="appName" href="/feed" />
    <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
  </Head>

  <div class="lambda-blog-root" :data-palette="palette">

    <!-- Admin bar -->
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

    <!-- BuildStrip — site-level status chrome -->
    <div class="build-strip" style="background:var(--code); border-bottom:1px solid var(--line-strong);">
      <div class="max-w-[1320px] mx-auto px-8 h-7 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <span class="font-mono-blog text-[10px] tracking-widest uppercase" style="color:var(--code-ink); opacity:0.5;">λ cms</span>
          <span style="color:var(--line-strong);">·</span>
          <span class="font-mono-blog text-[10px]" style="color:var(--code-ink); opacity:0.4;">v1.0.0</span>
        </div>
        <div class="flex items-center gap-3">
          <!-- Theme switcher -->
          <div class="flex items-center gap-1">
            <button
              v-for="p in PALETTES"
              :key="p"
              class="font-mono-blog text-[9px] uppercase tracking-widest px-1.5 py-0.5 rounded transition-all"
              :style="palette === p
                ? 'color:var(--accent-ink); background:var(--accent);'
                : 'color:var(--code-ink); opacity:0.4;'"
              @click="setPalette(p)"
            >{{ p }}</button>
          </div>
          <span style="color:var(--line-strong);">·</span>
          <!-- Live status dot -->
          <div class="flex items-center gap-1.5">
            <span class="status-dot w-1.5 h-1.5 rounded-full inline-block" style="background:var(--accent);"></span>
            <span class="font-mono-blog text-[10px]" style="color:var(--code-ink); opacity:0.5;">live</span>
          </div>
        </div>
      </div>
    </div>

    <!-- NavBar — sticky -->
    <header class="sticky top-0 z-40" style="background:var(--panel); border-bottom:1px solid var(--line-strong);">
      <div class="max-w-[1320px] mx-auto px-8 h-14 flex items-center justify-between gap-8">

        <!-- Brand mark -->
        <Link href="/" class="flex items-center gap-2.5 shrink-0 group">
          <div class="w-8 h-8 rounded flex items-center justify-center transition-colors"
               style="border:1px solid var(--line-strong);"
               @mouseenter="$event.currentTarget.style.borderColor='var(--accent)'"
               @mouseleave="$event.currentTarget.style.borderColor='var(--line-strong)'">
            <span class="font-mono-blog text-sm font-medium" style="color:var(--ink);">λ</span>
          </div>
          <span class="font-display font-600 text-sm tracking-tight" style="color:var(--ink); font-family:'Space Grotesk', sans-serif; font-weight:600; letter-spacing:-0.025em;">
            {{ appName }}
          </span>
        </Link>

        <!-- Desktop nav -->
        <nav v-if="navItems.length" class="hidden md:flex items-center gap-6 flex-1">
          <Link
            v-for="item in navItems"
            :key="item.url"
            :href="item.url"
            class="text-sm font-medium transition-colors duration-150"
            style="color:var(--soft); font-family:'Inter', sans-serif;"
            @mouseenter="$event.currentTarget.style.color='var(--ink)'"
            @mouseleave="$event.currentTarget.style.color='var(--soft)'"
          >{{ item.label }}</Link>
        </nav>

        <!-- Search pill + mobile hamburger -->
        <div class="flex items-center gap-3 ml-auto">
          <!-- Search pill (⌘K) -->
          <button
            class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded transition-colors duration-150"
            style="border:1px solid var(--line-strong); color:var(--soft);"
            @mouseenter="$event.currentTarget.style.borderColor='var(--accent)'; $event.currentTarget.style.color='var(--ink)'"
            @mouseleave="$event.currentTarget.style.borderColor='var(--line-strong)'; $event.currentTarget.style.color='var(--soft)'"
            @click="searchOpen = true"
            aria-label="Search"
          >
            <Search class="w-3.5 h-3.5" />
            <span class="font-mono-blog text-xs">Search</span>
            <span
              class="font-mono-blog text-[10px] px-1 py-0.5 rounded"
              style="border:1px solid var(--line-strong); color:var(--soft);"
            >⌘K</span>
          </button>

          <!-- Mobile hamburger -->
          <button
            v-if="navItems.length"
            class="md:hidden p-2 -mr-1 rounded-md transition-colors"
            style="color:var(--soft);"
            :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
            @click="mobileOpen = !mobileOpen"
          >
            <X v-if="mobileOpen" class="w-5 h-5" />
            <Menu v-else class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Mobile nav -->
      <div v-if="mobileOpen && navItems.length" class="md:hidden px-8 pb-4 pt-2 space-y-1" style="border-top:1px solid var(--line-strong);">
        <Link
          v-for="item in navItems"
          :key="item.url"
          :href="item.url"
          class="block py-2 px-2 rounded text-sm font-medium transition-colors"
          style="color:var(--soft);"
          @click="mobileOpen = false"
        >{{ item.label }}</Link>
      </div>
    </header>

    <!-- Main content -->
    <main class="w-full max-w-[1320px] mx-auto flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer style="background:var(--panel); border-top:1px solid var(--line-strong); margin-top:5rem;">
      <div class="max-w-[1320px] mx-auto px-8 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
          <!-- Brand column -->
          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-7 h-7 rounded flex items-center justify-center" style="border:1px solid var(--line-strong);">
                <span class="font-mono-blog text-xs" style="color:var(--ink);">λ</span>
              </div>
              <span class="font-display text-sm font-semibold" style="color:var(--ink); font-family:'Space Grotesk', sans-serif;">{{ appName }}</span>
            </div>
            <p class="text-xs leading-relaxed" style="color:var(--soft);">Engineering notes from a content runtime.</p>
          </div>
          <!-- Links columns -->
          <div>
            <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-3" style="color:var(--soft);">Content</p>
            <div class="space-y-2">
              <a href="/" class="block text-sm transition-colors" style="color:var(--soft);" @mouseenter="$event.currentTarget.style.color='var(--ink)'" @mouseleave="$event.currentTarget.style.color='var(--soft)'">Home</a>
              <a href="/feed" class="block text-sm transition-colors" style="color:var(--soft);" @mouseenter="$event.currentTarget.style.color='var(--ink)'" @mouseleave="$event.currentTarget.style.color='var(--soft)'">RSS Feed</a>
              <a href="/sitemap.xml" class="block text-sm transition-colors" style="color:var(--soft);" @mouseenter="$event.currentTarget.style.color='var(--ink)'" @mouseleave="$event.currentTarget.style.color='var(--soft)'">Sitemap</a>
            </div>
          </div>
        </div>

        <!-- Bottom row -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6" style="border-top:1px solid var(--line);">
          <span class="font-mono-blog text-xs" style="color:var(--soft);">© {{ year }} {{ appName }}</span>
          <div class="flex items-center gap-4">
            <a href="/feed" class="font-mono-blog text-xs transition-colors" style="color:var(--soft);" @mouseenter="$event.currentTarget.style.color='var(--accent)'" @mouseleave="$event.currentTarget.style.color='var(--soft)'">RSS</a>
            <span style="color:var(--line-strong);">·</span>
            <a href="/sitemap.xml" class="font-mono-blog text-xs transition-colors" style="color:var(--soft);" @mouseenter="$event.currentTarget.style.color='var(--accent)'" @mouseleave="$event.currentTarget.style.color='var(--soft)'">Sitemap</a>
          </div>
        </div>
      </div>
    </footer>

    <!-- Search Overlay -->
    <Transition name="search-fade">
      <div
        v-if="searchOpen"
        class="fixed inset-0 z-[100] flex flex-col items-center pt-[12vh] px-4"
        style="background:rgba(0,0,0,0.55); backdrop-filter:blur(4px);"
        @click.self="closeSearch"
      >
        <div class="w-full max-w-xl" style="border-radius:var(--blog-radius); background:var(--panel); border:1px solid var(--line-strong); overflow:hidden;">
          <!-- Search input -->
          <div class="flex items-center gap-3 px-4 py-3" style="border-bottom:1px solid var(--line);">
            <Search class="w-4 h-4 shrink-0" style="color:var(--soft);" />
            <input
              ref="searchInputRef"
              type="text"
              placeholder="Search posts…"
              class="flex-1 bg-transparent outline-none text-sm"
              style="color:var(--ink);"
              :value="searchQuery"
              @input="onSearchInput($event.target.value)"
              autofocus
            />
            <kbd class="font-mono-blog text-[10px] px-1.5 py-0.5 rounded" style="border:1px solid var(--line-strong); color:var(--soft);">Esc</kbd>
          </div>

          <!-- Results -->
          <div class="max-h-80 overflow-y-auto">
            <!-- Loading -->
            <div v-if="searchLoading" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs" style="color:var(--soft);">searching…</span>
            </div>

            <!-- No query -->
            <div v-else-if="!searchQuery.trim()" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs" style="color:var(--soft);">Type to search posts</span>
            </div>

            <!-- No results -->
            <div v-else-if="!searchResult.length" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs" style="color:var(--soft);">No results for "{{ searchQuery }}"</span>
            </div>

            <!-- Result list -->
            <a
              v-for="(item, idx) in searchResult"
              :key="item.slug"
              :href="item.url"
              class="flex items-start gap-3 px-4 py-3 transition-colors duration-100"
              style="border-bottom:1px solid var(--line);"
              :style="{ background: 'transparent' }"
              @mouseenter="$event.currentTarget.style.background='var(--bg)'"
              @mouseleave="$event.currentTarget.style.background='transparent'"
              @click="closeSearch"
            >
              <span class="font-mono-blog text-[10px] shrink-0 mt-0.5 tabular-nums" style="color:var(--soft);">{{ String(item.id).padStart(2, '0') }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate" style="color:var(--ink); font-family:'Space Grotesk', sans-serif;">{{ item.title }}</p>
                <p v-if="item.category_name" class="font-mono-blog text-[10px] mt-0.5" style="color:var(--soft);">{{ item.category_name }}</p>
              </div>
              <span class="font-mono-blog text-[10px] shrink-0" style="color:var(--soft);">{{ item.published_at_formatted }}</span>
            </a>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.search-fade-enter-active { transition: opacity 140ms ease; }
.search-fade-leave-active { transition: opacity 100ms ease; }
.search-fade-enter-from,
.search-fade-leave-to    { opacity: 0; }
</style>
