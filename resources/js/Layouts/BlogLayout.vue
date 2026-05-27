<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { LayoutDashboard, PenSquare, LogOut, Menu, X, Search } from '@lucide/vue'
import axios from 'axios'

defineOptions({ layout: null })

const page      = usePage()
const authUser  = computed(() => page.props.auth?.user)
const appName   = computed(() => page.props.appName ?? 'Blog')
const navItems  = computed(() => page.props.navItems ?? [])
const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.content ?? '')
const year      = new Date().getFullYear()

const mobileOpen    = ref(false)
const searchOpen    = ref(false)
const searchQuery   = ref('')
const searchResult  = ref([])
const searchLoading = ref(false)

onMounted(() => {
  document.documentElement.classList.remove('dark')
  document.addEventListener('keydown', onKeyDown)
})
onBeforeUnmount(() => {
  const saved = localStorage.getItem('lambda-cms-theme')
  if (saved === 'dark') document.documentElement.classList.add('dark')
  document.removeEventListener('keydown', onKeyDown)
})

function onKeyDown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    searchOpen.value = !searchOpen.value
    if (searchOpen.value) {
      searchQuery.value = ''
      searchResult.value = []
    }
  }
  if (e.key === 'Escape') searchOpen.value = false
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

  <div class="lambda-blog-root">

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

    <!-- NavBar — sticky -->
    <header class="blog-navbar sticky top-0 z-40">
      <div class="max-w-[1320px] mx-auto px-8 h-14 flex items-center justify-between gap-8">

        <!-- Brand mark -->
        <Link href="/" class="blog-brand flex items-center gap-2.5 shrink-0">
          <div class="blog-brand__mark w-8 h-8 rounded flex items-center justify-center">
            <span class="font-mono-blog text-sm font-medium">λ</span>
          </div>
          <span class="font-display font-semibold text-sm" style="font-family:'Space Grotesk', sans-serif; letter-spacing:-0.025em;">
            {{ appName }}
          </span>
        </Link>

        <!-- Desktop nav -->
        <nav v-if="navItems.length" class="hidden md:flex items-center gap-6 flex-1">
          <Link
            v-for="item in navItems"
            :key="item.url"
            :href="item.url"
            class="blog-nav-link text-sm font-medium transition-colors duration-150"
          >{{ item.label }}</Link>
        </nav>

        <!-- Search pill + mobile hamburger -->
        <div class="flex items-center gap-3 ml-auto">
          <button
            class="blog-search-pill hidden sm:flex items-center gap-2 px-3 py-1.5 rounded transition-colors duration-150"
            @click="searchOpen = true"
            aria-label="Search (Ctrl+K)"
          >
            <Search class="w-3.5 h-3.5" />
            <span class="font-mono-blog text-xs">Search</span>
            <span class="font-mono-blog text-[10px] px-1 py-0.5 rounded blog-kbd">⌘K</span>
          </button>

          <button
            v-if="navItems.length"
            class="blog-icon-btn md:hidden p-2 -mr-1 rounded-md transition-colors"
            :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
            @click="mobileOpen = !mobileOpen"
          >
            <X v-if="mobileOpen" class="w-5 h-5" />
            <Menu v-else class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Mobile nav -->
      <div v-if="mobileOpen && navItems.length" class="md:hidden px-8 pb-4 pt-2 space-y-1 blog-mobile-nav">
        <Link
          v-for="item in navItems"
          :key="item.url"
          :href="item.url"
          class="blog-nav-link block py-2 px-2 rounded text-sm font-medium transition-colors"
          @click="mobileOpen = false"
        >{{ item.label }}</Link>
      </div>
    </header>

    <!-- Main content -->
    <main class="w-full max-w-[1320px] mx-auto flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="blog-footer" style="margin-top:5rem;">
      <div class="max-w-[1320px] mx-auto px-8 py-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-8">
          <div class="col-span-2 md:col-span-1">
            <div class="flex items-center gap-2 mb-3">
              <div class="blog-brand__mark w-7 h-7 rounded flex items-center justify-center">
                <span class="font-mono-blog text-xs">λ</span>
              </div>
              <span class="font-display text-sm font-semibold" style="font-family:'Space Grotesk', sans-serif;">{{ appName }}</span>
            </div>
            <p class="text-xs leading-relaxed blog-soft">A content runtime.</p>
          </div>
          <div>
            <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-3 blog-soft">Content</p>
            <div class="space-y-2">
              <a href="/" class="blog-footer-link block text-sm transition-colors">Home</a>
              <a href="/feed" class="blog-footer-link block text-sm transition-colors">RSS Feed</a>
              <a href="/sitemap.xml" class="blog-footer-link block text-sm transition-colors">Sitemap</a>
            </div>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 blog-footer-bottom">
          <span class="font-mono-blog text-xs blog-soft">© {{ year }} {{ appName }}</span>
          <div class="flex items-center gap-4">
            <a href="/feed" class="blog-footer-link font-mono-blog text-xs transition-colors">RSS</a>
            <span class="blog-rule-char">·</span>
            <a href="/sitemap.xml" class="blog-footer-link font-mono-blog text-xs transition-colors">Sitemap</a>
          </div>
        </div>
      </div>
    </footer>

    <!-- Search Overlay -->
    <Transition name="search-fade">
      <div
        v-if="searchOpen"
        class="fixed inset-0 z-[100] flex flex-col items-center pt-[12vh] px-4 blog-search-backdrop"
        @click.self="closeSearch"
      >
        <div class="w-full max-w-xl blog-search-panel">
          <div class="flex items-center gap-3 px-4 py-3 blog-search-input-row">
            <Search class="w-4 h-4 shrink-0 blog-soft" />
            <input
              type="text"
              placeholder="Search posts…"
              class="flex-1 bg-transparent outline-none text-sm blog-ink"
              :value="searchQuery"
              @input="onSearchInput($event.target.value)"
              autofocus
            />
            <kbd class="font-mono-blog text-[10px] px-1.5 py-0.5 rounded blog-kbd">Esc</kbd>
          </div>
          <div class="max-h-80 overflow-y-auto">
            <div v-if="searchLoading" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs blog-soft">searching…</span>
            </div>
            <div v-else-if="!searchQuery.trim()" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs blog-soft">Type to search posts</span>
            </div>
            <div v-else-if="!searchResult.length" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs blog-soft">No results for "{{ searchQuery }}"</span>
            </div>
            <a
              v-for="item in searchResult"
              :key="item.slug"
              :href="item.url"
              class="blog-search-result flex items-start gap-3 px-4 py-3 transition-colors duration-100"
              @click="closeSearch"
            >
              <span class="font-mono-blog text-[10px] shrink-0 mt-0.5 tabular-nums blog-soft">{{ String(item.id).padStart(2, '0') }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate blog-ink" style="font-family:'Space Grotesk', sans-serif;">{{ item.title }}</p>
                <p v-if="item.category_name" class="font-mono-blog text-[10px] mt-0.5 blog-soft">{{ item.category_name }}</p>
              </div>
              <span class="font-mono-blog text-[10px] shrink-0 blog-soft">{{ item.published_at_formatted }}</span>
            </a>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* All blog layout styles reference CSS custom properties from .lambda-blog-root */

.blog-navbar {
  background: var(--panel);
  border-bottom: 1px solid var(--line-strong);
}

.blog-brand__mark {
  border: 1px solid var(--line-strong);
  color: var(--ink);
  transition: border-color 150ms;
}
.blog-brand:hover .blog-brand__mark {
  border-color: var(--accent);
}

.blog-nav-link {
  color: var(--soft);
  font-family: 'Inter', sans-serif;
}
.blog-nav-link:hover { color: var(--ink); }

.blog-search-pill {
  border: 1px solid var(--line-strong);
  color: var(--soft);
}
.blog-search-pill:hover {
  border-color: var(--accent);
  color: var(--ink);
}

.blog-icon-btn { color: var(--soft); }
.blog-icon-btn:hover { color: var(--ink); }

.blog-kbd {
  border: 1px solid var(--line-strong);
  color: var(--soft);
}

.blog-mobile-nav { border-top: 1px solid var(--line-strong); }

.blog-footer {
  background: var(--panel);
  border-top: 1px solid var(--line-strong);
}
.blog-footer-bottom { border-top: 1px solid var(--line); }
.blog-footer-link   { color: var(--soft); }
.blog-footer-link:hover { color: var(--ink); }
.blog-soft      { color: var(--soft); }
.blog-ink       { color: var(--ink); }
.blog-rule-char { color: var(--line-strong); }

.blog-search-backdrop { background: rgba(0,0,0,0.45); backdrop-filter: blur(4px); }

.blog-search-panel {
  border-radius: var(--blog-radius);
  background: var(--panel);
  border: 1px solid var(--line-strong);
  overflow: hidden;
}
.blog-search-input-row { border-bottom: 1px solid var(--line); }

.blog-search-result { border-bottom: 1px solid var(--line); }
.blog-search-result:hover { background: var(--bg); }

.search-fade-enter-active { transition: opacity 140ms ease; }
.search-fade-leave-active { transition: opacity 100ms ease; }
.search-fade-enter-from,
.search-fade-leave-to    { opacity: 0; }
</style>
