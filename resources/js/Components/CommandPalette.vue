<template>
  <Teleport to="body">
    <Transition name="cmd-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[200] flex items-start justify-center pt-[15vh] px-4"
        @mousedown.self="close"
      >
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />
        <div
          class="relative w-full max-w-lg bg-card border rounded-xl shadow-2xl overflow-hidden"
          role="dialog"
          aria-modal="true"
          aria-label="Global search"
        >
          <!-- Input row -->
          <div class="flex items-center gap-3 px-4 py-3 border-b">
            <Search class="w-4 h-4 text-muted-foreground shrink-0" />
            <input
              ref="inputRef"
              v-model="query"
              type="text"
              placeholder="Search posts, pages, media, users…"
              class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
              @keydown.down.prevent="moveDown"
              @keydown.up.prevent="moveUp"
              @keydown.enter.prevent="go"
              @keydown.escape="close"
            />
            <kbd class="hidden sm:inline-flex h-5 items-center rounded border bg-muted px-1.5 text-[10px] font-mono text-muted-foreground">ESC</kbd>
          </div>

          <!-- Results -->
          <div class="max-h-80 overflow-y-auto py-1">
            <!-- Loading -->
            <div v-if="loading" class="px-4 py-6 text-center text-sm text-muted-foreground">Searching…</div>

            <!-- No results -->
            <div v-else-if="query.length >= 2 && flatResults.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
              No results for "{{ query }}"
            </div>

            <!-- Empty state -->
            <div v-else-if="query.length < 2" class="px-4 py-4 text-center text-sm text-muted-foreground">
              Type to search…
            </div>

            <!-- Groups -->
            <template v-for="group in resultGroups" :key="group.label">
              <div v-if="group.items.length" class="px-3 pt-2 pb-0.5">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground/60">{{ group.label }}</p>
              </div>
              <a
                v-for="item in group.items"
                :key="item.type + item.id"
                :href="item.url"
                class="flex items-center gap-3 px-4 py-2 text-sm transition-colors cursor-pointer"
                :class="activeItem === item ? 'bg-primary text-primary-foreground' : 'hover:bg-muted/60'"
                @mouseenter="activeItem = item"
                @click="close"
              >
                <component :is="group.icon" class="w-4 h-4 shrink-0 opacity-70" />
                <span class="flex-1 truncate">{{ item.label }}</span>
                <span v-if="item.meta" class="text-xs opacity-60 shrink-0">{{ item.meta }}</span>
              </a>
            </template>
          </div>

          <!-- Footer hint -->
          <div v-if="flatResults.length > 0" class="border-t px-4 py-2 flex items-center gap-3 text-[11px] text-muted-foreground/60">
            <span><kbd class="font-mono">↑↓</kbd> navigate</span>
            <span><kbd class="font-mono">↵</kbd> open</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { Search, FileText, File, Image, User } from 'lucide-vue-next'

const open  = ref(false)
const query = ref('')
const results = ref({ posts: [], pages: [], media: [], users: [] })
const loading = ref(false)
const activeItem = ref(null)
const inputRef = ref(null)

let debounceTimer = null

const resultGroups = computed(() => [
  { label: 'Posts',  icon: FileText, items: results.value.posts ?? [] },
  { label: 'Pages',  icon: File,     items: results.value.pages ?? [] },
  { label: 'Media',  icon: Image,    items: results.value.media ?? [] },
  { label: 'Users',  icon: User,     items: results.value.users ?? [] },
])

const flatResults = computed(() => resultGroups.value.flatMap(g => g.items))

watch(query, (val) => {
  clearTimeout(debounceTimer)
  if (val.length < 2) {
    results.value = { posts: [], pages: [], media: [], users: [] }
    activeItem.value = null
    return
  }
  loading.value = true
  debounceTimer = setTimeout(() => search(val), 250)
})

async function search(q) {
  try {
    const res = await fetch(route('admin.search') + '?q=' + encodeURIComponent(q), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
    results.value = await res.json()
    activeItem.value = flatResults.value[0] ?? null
  } finally {
    loading.value = false
  }
}

function moveDown() {
  const flat = flatResults.value
  if (!flat.length) return
  const idx = flat.indexOf(activeItem.value)
  activeItem.value = flat[(idx + 1) % flat.length]
}

function moveUp() {
  const flat = flatResults.value
  if (!flat.length) return
  const idx = flat.indexOf(activeItem.value)
  activeItem.value = flat[(idx - 1 + flat.length) % flat.length]
}

function go() {
  if (activeItem.value?.url) {
    window.location.href = activeItem.value.url
    close()
  }
}

function openPalette() {
  open.value = true
  query.value = ''
  results.value = { posts: [], pages: [], media: [], users: [] }
  activeItem.value = null
  nextTick(() => inputRef.value?.focus())
}

function close() {
  open.value = false
}

function handleKeydown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    open.value ? close() : openPalette()
  }
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<style scoped>
.cmd-fade-enter-active, .cmd-fade-leave-active { transition: opacity 0.15s; }
.cmd-fade-enter-from, .cmd-fade-leave-to { opacity: 0; }
</style>
