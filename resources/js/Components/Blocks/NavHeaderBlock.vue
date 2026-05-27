<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({ block: { type: Object, required: true } })

const page       = usePage()
const appName    = computed(() => props.block.data?.logoText || page.props.appName || 'Blog')
const navItems   = computed(() => page.props.navItems ?? [])
const showSearch = computed(() => props.block.data?.showSearch !== false)
const isSticky   = computed(() => props.block.data?.sticky !== false)

const mobileOpen    = ref(false)
const searchOpen    = ref(false)
const searchQuery   = ref('')
const searchResult  = ref([])
const searchLoading = ref(false)

onMounted(() => document.addEventListener('keydown', onKeyDown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeyDown))

function onKeyDown(e) {
  if (!showSearch.value) return
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    searchOpen.value = !searchOpen.value
    if (searchOpen.value) { searchQuery.value = ''; searchResult.value = [] }
  }
  if (e.key === 'Escape') searchOpen.value = false
}

let timer = null
function onSearchInput(v) {
  searchQuery.value = v
  clearTimeout(timer)
  if (!v.trim()) { searchResult.value = []; return }
  searchLoading.value = true
  timer = setTimeout(async () => {
    try {
      const { data } = await axios.post('/api/v1/query', {
        source: 'posts',
        filters: [{ field: 'title', op: 'contains', value: v }],
        sort: { field: 'published_at', direction: 'desc' },
        limit: 6, offset: 0,
      })
      searchResult.value = data.items ?? []
    } catch { searchResult.value = [] }
    finally { searchLoading.value = false }
  }, 200)
}
</script>

<template>
  <header class="nav-header" :class="{ 'nav-header--sticky': isSticky }">
    <div class="nav-header__inner max-w-[1320px] mx-auto px-8 h-14 flex items-center justify-between gap-8">

      <!-- Brand -->
      <a href="/" class="nav-brand flex items-center gap-2.5 shrink-0">
        <div class="nav-brand__mark w-8 h-8 rounded flex items-center justify-center">
          <span class="font-mono-blog text-sm font-medium">λ</span>
        </div>
        <span class="font-semibold text-sm nav-brand__name" style="font-family:'Space Grotesk',sans-serif; letter-spacing:-0.025em;">
          {{ appName }}
        </span>
      </a>

      <!-- Desktop nav links -->
      <nav v-if="navItems.length" class="hidden md:flex items-center gap-6 flex-1">
        <a
          v-for="item in navItems"
          :key="item.url"
          :href="item.url"
          class="nav-link text-sm font-medium transition-colors duration-150"
        >{{ item.label }}</a>
      </nav>

      <!-- Right side -->
      <div class="flex items-center gap-3 ml-auto">
        <!-- Search pill -->
        <button
          v-if="showSearch"
          class="nav-search-pill hidden sm:flex items-center gap-2 px-3 py-1.5 rounded transition-colors duration-150"
          @click="searchOpen = true"
          aria-label="Search (Ctrl+K)"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
          </svg>
          <span class="font-mono-blog text-xs">Search</span>
          <span class="font-mono-blog text-[10px] px-1 py-0.5 rounded nav-kbd">⌘K</span>
        </button>

        <!-- Mobile hamburger -->
        <button
          v-if="navItems.length"
          class="nav-icon-btn md:hidden p-2 -mr-1 rounded-md transition-colors"
          :aria-label="mobileOpen ? 'Close menu' : 'Open menu'"
          @click="mobileOpen = !mobileOpen"
        >
          <svg v-if="mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="mobileOpen && navItems.length" class="md:hidden px-8 pb-4 pt-2 space-y-1 nav-mobile">
      <a
        v-for="item in navItems"
        :key="item.url"
        :href="item.url"
        class="nav-link block py-2 px-2 rounded text-sm font-medium transition-colors"
        @click="mobileOpen = false"
      >{{ item.label }}</a>
    </div>
  </header>

  <!-- Search overlay — teleported to body so it escapes any overflow:hidden containers -->
  <Teleport to="body">
    <Transition name="search-fade">
      <div
        v-if="searchOpen"
        class="fixed inset-0 z-[200] flex flex-col items-center pt-[12vh] px-4 nav-search-backdrop"
        @click.self="searchOpen = false"
      >
        <div class="w-full max-w-xl nav-search-panel">
          <div class="flex items-center gap-3 px-4 py-3 nav-search-row">
            <svg class="w-4 h-4 shrink-0 nav-soft" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input
              type="text"
              placeholder="Search posts…"
              class="flex-1 bg-transparent outline-none text-sm nav-ink"
              :value="searchQuery"
              @input="onSearchInput($event.target.value)"
              autofocus
            />
            <kbd class="font-mono-blog text-[10px] px-1.5 py-0.5 rounded nav-kbd">Esc</kbd>
          </div>
          <div class="max-h-80 overflow-y-auto">
            <div v-if="searchLoading" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs nav-soft">searching…</span>
            </div>
            <div v-else-if="!searchQuery.trim()" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs nav-soft">Type to search posts</span>
            </div>
            <div v-else-if="!searchResult.length" class="px-4 py-6 text-center">
              <span class="font-mono-blog text-xs nav-soft">No results for "{{ searchQuery }}"</span>
            </div>
            <a
              v-for="item in searchResult"
              :key="item.slug"
              :href="item.url"
              class="nav-search-result flex items-start gap-3 px-4 py-3 transition-colors duration-100"
              @click="searchOpen = false"
            >
              <span class="font-mono-blog text-[10px] shrink-0 mt-0.5 tabular-nums nav-soft">{{ String(item.id).padStart(2,'0') }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate nav-ink" style="font-family:'Space Grotesk',sans-serif;">{{ item.title }}</p>
                <p v-if="item.category_name" class="font-mono-blog text-[10px] mt-0.5 nav-soft">{{ item.category_name }}</p>
              </div>
              <span class="font-mono-blog text-[10px] shrink-0 nav-soft">{{ item.published_at_formatted }}</span>
            </a>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.nav-header {
  background: var(--panel);
  border-bottom: 1px solid var(--line-strong);
  z-index: 40;
}
.nav-header--sticky { position: sticky; top: 0; }

.nav-brand__mark {
  border: 1px solid var(--line-strong);
  color: var(--ink);
  transition: border-color 150ms;
}
.nav-brand:hover .nav-brand__mark { border-color: var(--accent); }
.nav-brand__name { color: var(--ink); }

.nav-link { color: var(--soft); font-family: 'Inter', sans-serif; }
.nav-link:hover { color: var(--ink); }

.nav-search-pill {
  border: 1px solid var(--line-strong);
  color: var(--soft);
}
.nav-search-pill:hover { border-color: var(--accent); color: var(--ink); }

.nav-icon-btn { color: var(--soft); }
.nav-icon-btn:hover { color: var(--ink); }

.nav-kbd {
  border: 1px solid var(--line-strong);
  color: var(--soft);
}
.nav-mobile { border-top: 1px solid var(--line-strong); }

.nav-soft { color: var(--soft); }
.nav-ink  { color: var(--ink); }

.nav-search-backdrop { background: rgba(0,0,0,0.45); backdrop-filter: blur(4px); }
.nav-search-panel {
  border-radius: var(--blog-radius);
  background: var(--panel);
  border: 1px solid var(--line-strong);
  overflow: hidden;
}
.nav-search-row { border-bottom: 1px solid var(--line); }
.nav-search-result { border-bottom: 1px solid var(--line); }
.nav-search-result:hover { background: var(--bg); }

.search-fade-enter-active { transition: opacity 140ms ease; }
.search-fade-leave-active { transition: opacity 100ms ease; }
.search-fade-enter-from,
.search-fade-leave-to    { opacity: 0; }
</style>
