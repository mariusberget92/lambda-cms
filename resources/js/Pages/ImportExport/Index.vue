<template>
  <AppLayout title="Import / Export">
    <Head title="Import / Export" />

    <PageHeader title="Import / Export" description="Transfer posts, categories and tags between sites" />

    <!-- Tab bar -->
    <div class="flex border-b border-border mb-6">
      <button
        v-for="t in TABS"
        :key="t.id"
        type="button"
        class="px-5 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="tab === t.id
          ? 'border-primary text-primary'
          : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'"
        @click="tab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <!-- ── EXPORT TAB ──────────────────────────────────────────────────────── -->
    <div v-if="tab === 'export'" class="space-y-6">

      <!-- Posts -->
      <div class="rounded-lg border bg-card">
        <div class="flex items-center justify-between px-4 py-3 border-b border-border">
          <div class="flex items-center gap-3">
            <input
              id="all-posts"
              type="checkbox"
              class="h-4 w-4 rounded border-border"
              :checked="allPostsSelected"
              :indeterminate="somePostsSelected && !allPostsSelected"
              @change="toggleAllPosts"
            />
            <label for="all-posts" class="text-sm font-medium cursor-pointer select-none">
              Posts
              <span class="ml-1.5 text-muted-foreground font-normal">
                {{ selectedPosts.size }} / {{ filteredPosts.length }} selected
              </span>
            </label>
          </div>
          <div class="flex items-center gap-2">
            <select
              v-model="postStatusFilter"
              class="text-xs border border-border rounded-md bg-background px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="">All statuses</option>
              <option value="published">Published</option>
              <option value="draft">Draft</option>
              <option value="scheduled">Scheduled</option>
            </select>
            <input
              v-model="postSearch"
              type="text"
              placeholder="Search posts…"
              class="text-xs border border-border rounded-md bg-background px-2 py-1.5 w-40 focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
        </div>

        <div class="divide-y divide-border max-h-72 overflow-y-auto">
          <label
            v-for="post in filteredPosts"
            :key="post.id"
            class="flex items-center gap-3 px-4 py-2.5 hover:bg-muted/40 cursor-pointer transition-colors"
          >
            <input
              type="checkbox"
              class="h-4 w-4 rounded border-border shrink-0"
              :checked="selectedPosts.has(post.id)"
              @change="togglePost(post.id)"
            />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium truncate">{{ post.title }}</span>
                <span :class="statusClass(post.status)" class="shrink-0 text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded-full">
                  {{ post.status }}
                </span>
              </div>
              <div class="text-xs text-muted-foreground font-mono mt-0.5">{{ post.slug }}</div>
            </div>
            <span v-if="post.published_at" class="text-xs text-muted-foreground shrink-0">
              {{ post.published_at }}
            </span>
          </label>
          <div v-if="filteredPosts.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
            No posts match your filter.
          </div>
        </div>
      </div>

      <!-- Categories + Tags side by side -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Categories -->
        <div class="rounded-lg border bg-card">
          <div class="flex items-center gap-3 px-4 py-3 border-b border-border">
            <input
              id="all-cats"
              type="checkbox"
              class="h-4 w-4 rounded border-border"
              :checked="allCatsSelected"
              :indeterminate="someCatsSelected && !allCatsSelected"
              @change="toggleAllCats"
            />
            <label for="all-cats" class="text-sm font-medium cursor-pointer select-none">
              Categories
              <span class="ml-1.5 text-muted-foreground font-normal">
                {{ selectedCategories.size }} / {{ categories.length }}
              </span>
            </label>
          </div>
          <div class="divide-y divide-border max-h-56 overflow-y-auto">
            <label
              v-for="cat in categories"
              :key="cat.id"
              class="flex items-center gap-3 px-4 py-2.5 hover:bg-muted/40 cursor-pointer transition-colors"
            >
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-border shrink-0"
                :checked="selectedCategories.has(cat.id)"
                @change="toggleCategory(cat.id)"
              />
              <span
                v-if="cat.color"
                class="w-2.5 h-2.5 rounded-full shrink-0"
                :style="{ backgroundColor: cat.color }"
              />
              <span class="flex-1 text-sm truncate">{{ cat.name }}</span>
              <span class="text-xs text-muted-foreground shrink-0">{{ cat.posts_count }}</span>
            </label>
            <div v-if="categories.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
              No categories yet.
            </div>
          </div>
        </div>

        <!-- Tags -->
        <div class="rounded-lg border bg-card">
          <div class="flex items-center gap-3 px-4 py-3 border-b border-border">
            <input
              id="all-tags"
              type="checkbox"
              class="h-4 w-4 rounded border-border"
              :checked="allTagsSelected"
              :indeterminate="someTagsSelected && !allTagsSelected"
              @change="toggleAllTags"
            />
            <label for="all-tags" class="text-sm font-medium cursor-pointer select-none">
              Tags
              <span class="ml-1.5 text-muted-foreground font-normal">
                {{ selectedTags.size }} / {{ tags.length }}
              </span>
            </label>
          </div>
          <div class="divide-y divide-border max-h-56 overflow-y-auto">
            <label
              v-for="tag in tags"
              :key="tag.id"
              class="flex items-center gap-3 px-4 py-2.5 hover:bg-muted/40 cursor-pointer transition-colors"
            >
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-border shrink-0"
                :checked="selectedTags.has(tag.id)"
                @change="toggleTag(tag.id)"
              />
              <span class="flex-1 text-sm truncate">{{ tag.name }}</span>
              <span class="text-xs text-muted-foreground shrink-0">{{ tag.posts_count }}</span>
            </label>
            <div v-if="tags.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
              No tags yet.
            </div>
          </div>
        </div>
      </div>

      <!-- Export footer -->
      <div class="flex items-center justify-between rounded-lg border bg-card px-4 py-3">
        <span class="text-sm text-muted-foreground">
          <span class="font-medium text-foreground">{{ totalSelected }}</span> item{{ totalSelected !== 1 ? 's' : '' }} selected
          <span v-if="totalSelected === 0" class="ml-1">(select at least one)</span>
        </span>
        <button
          type="button"
          :disabled="totalSelected === 0 || exporting"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
          @click="doExport"
        >
          <svg v-if="!exporting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          {{ exporting ? 'Exporting…' : 'Export JSON' }}
        </button>
      </div>
    </div>

    <!-- ── IMPORT TAB ──────────────────────────────────────────────────────── -->
    <div v-else class="space-y-6 max-w-2xl">

      <!-- Results banner (shown after a successful import) -->
      <div v-if="importResults" class="rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30 px-4 py-4">
        <p class="text-sm font-semibold text-green-800 dark:text-green-400 mb-3">Import complete</p>
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div>
            <p class="font-medium text-foreground mb-1">Categories</p>
            <p class="text-muted-foreground">{{ importResults.categories.created }} created</p>
            <p v-if="importResults.categories.updated" class="text-muted-foreground">{{ importResults.categories.updated }} updated</p>
            <p v-if="importResults.categories.skipped" class="text-muted-foreground">{{ importResults.categories.skipped }} skipped</p>
          </div>
          <div>
            <p class="font-medium text-foreground mb-1">Tags</p>
            <p class="text-muted-foreground">{{ importResults.tags.created }} created</p>
            <p v-if="importResults.tags.skipped" class="text-muted-foreground">{{ importResults.tags.skipped }} skipped</p>
          </div>
          <div>
            <p class="font-medium text-foreground mb-1">Posts</p>
            <p class="text-muted-foreground">{{ importResults.posts.created }} created</p>
            <p v-if="importResults.posts.updated" class="text-muted-foreground">{{ importResults.posts.updated }} updated</p>
            <p v-if="importResults.posts.skipped" class="text-muted-foreground">{{ importResults.posts.skipped }} skipped</p>
          </div>
        </div>
      </div>

      <!-- File drop zone -->
      <div
        class="rounded-lg border-2 border-dashed transition-colors cursor-pointer"
        :class="dragging
          ? 'border-primary bg-primary/5'
          : 'border-border hover:border-muted-foreground bg-card'"
        @dragover.prevent="dragging = true"
        @dragleave="dragging = false"
        @drop.prevent="onDrop"
        @click="fileInput?.click()"
      >
        <div class="flex flex-col items-center justify-center gap-3 py-10 px-6 text-center pointer-events-none">
          <svg class="w-10 h-10 text-muted-foreground/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <div>
            <p class="text-sm font-medium text-foreground">
              {{ importFile ? importFile.name : 'Drop a .json export file here' }}
            </p>
            <p class="text-xs text-muted-foreground mt-1">
              {{ importFile ? formatFileSize(importFile.size) : 'or click to browse' }}
            </p>
          </div>
          <button
            v-if="importFile"
            type="button"
            class="pointer-events-auto text-xs text-muted-foreground hover:text-foreground transition-colors"
            @click.stop="importFile = null"
          >
            Remove file
          </button>
        </div>
        <input ref="fileInput" type="file" accept=".json,application/json" class="hidden" @change="onFileChange" />
      </div>

      <!-- Duplicate handling -->
      <div class="rounded-lg border bg-card px-4 py-4 space-y-3">
        <p class="text-sm font-medium">When a slug already exists</p>
        <div class="space-y-2">
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="radio" v-model="importMode" value="skip" class="mt-0.5 h-4 w-4 border-border" />
            <div>
              <span class="text-sm font-medium">Skip</span>
              <p class="text-xs text-muted-foreground mt-0.5">Keep existing records unchanged. Only create items that don't exist yet.</p>
            </div>
          </label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="radio" v-model="importMode" value="overwrite" class="mt-0.5 h-4 w-4 border-border" />
            <div>
              <span class="text-sm font-medium">Overwrite</span>
              <p class="text-xs text-muted-foreground mt-0.5">Replace existing records with the imported data. New items are created as usual.</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Errors -->
      <p v-if="importForm.errors.file" class="text-sm text-destructive">{{ importForm.errors.file }}</p>

      <!-- Import note -->
      <p class="text-xs text-muted-foreground">
        Media files are not included in exports. Featured image URLs are preserved as references but will need to be re-uploaded manually if moving to a new server.
      </p>

      <button
        type="button"
        :disabled="!importFile || importForm.processing"
        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
        @click="doImport"
      >
        <svg v-if="!importForm.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        {{ importForm.processing ? 'Importing…' : 'Import' }}
      </button>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
  posts:      { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
})

const TABS = [
  { id: 'export', label: 'Export' },
  { id: 'import', label: 'Import' },
]

const tab = ref('export')

// ── Export state ─────────────────────────────────────────────────────────────

const postSearch      = ref('')
const postStatusFilter = ref('')
const selectedPosts      = ref(new Set())
const selectedCategories = ref(new Set())
const selectedTags       = ref(new Set())
const exporting          = ref(false)

const filteredPosts = computed(() => {
  let list = props.posts
  if (postStatusFilter.value) list = list.filter(p => p.status === postStatusFilter.value)
  if (postSearch.value.trim()) {
    const q = postSearch.value.trim().toLowerCase()
    list = list.filter(p => p.title.toLowerCase().includes(q) || p.slug.includes(q))
  }
  return list
})

const allPostsSelected  = computed(() => filteredPosts.value.length > 0 && filteredPosts.value.every(p => selectedPosts.value.has(p.id)))
const somePostsSelected = computed(() => filteredPosts.value.some(p => selectedPosts.value.has(p.id)))
const allCatsSelected   = computed(() => props.categories.length > 0 && props.categories.every(c => selectedCategories.value.has(c.id)))
const someCatsSelected  = computed(() => props.categories.some(c => selectedCategories.value.has(c.id)))
const allTagsSelected   = computed(() => props.tags.length > 0 && props.tags.every(t => selectedTags.value.has(t.id)))
const someTagsSelected  = computed(() => props.tags.some(t => selectedTags.value.has(t.id)))
const totalSelected     = computed(() => selectedPosts.value.size + selectedCategories.value.size + selectedTags.value.size)

function togglePost(id) {
  const s = new Set(selectedPosts.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedPosts.value = s
}

function toggleAllPosts() {
  if (allPostsSelected.value) {
    const s = new Set(selectedPosts.value)
    filteredPosts.value.forEach(p => s.delete(p.id))
    selectedPosts.value = s
  } else {
    const s = new Set(selectedPosts.value)
    filteredPosts.value.forEach(p => s.add(p.id))
    selectedPosts.value = s
  }
}

function toggleCategory(id) {
  const s = new Set(selectedCategories.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedCategories.value = s
}

function toggleAllCats() {
  selectedCategories.value = allCatsSelected.value
    ? new Set()
    : new Set(props.categories.map(c => c.id))
}

function toggleTag(id) {
  const s = new Set(selectedTags.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selectedTags.value = s
}

function toggleAllTags() {
  selectedTags.value = allTagsSelected.value
    ? new Set()
    : new Set(props.tags.map(t => t.id))
}

function statusClass(status) {
  if (status === 'published') return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
  if (status === 'scheduled') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
  return 'bg-muted text-muted-foreground'
}

async function doExport() {
  exporting.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
    const res = await fetch(route('import-export.export'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({
        post_ids:     [...selectedPosts.value],
        category_ids: [...selectedCategories.value],
        tag_ids:      [...selectedTags.value],
      }),
    })
    if (!res.ok) throw new Error('Export failed')
    const blob = await res.blob()
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href     = url
    a.download = `lambda-cms-export-${new Date().toISOString().slice(0, 10)}.json`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    alert('Export failed. Please try again.')
  } finally {
    exporting.value = false
  }
}

// ── Import state ──────────────────────────────────────────────────────────────

const fileInput   = ref(null)
const importFile  = ref(null)
const importMode  = ref('skip')
const dragging    = ref(false)
const importForm  = useForm({ file: null, mode: 'skip' })

const page         = usePage()
const importResults = computed(() => page.props.import_results ?? null)

function onFileChange(e) {
  importFile.value = e.target.files[0] ?? null
}

function onDrop(e) {
  dragging.value   = false
  const file = e.dataTransfer.files[0]
  if (file && (file.type === 'application/json' || file.name.endsWith('.json'))) {
    importFile.value = file
  }
}

function formatFileSize(bytes) {
  if (bytes < 1024)        return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function doImport() {
  if (!importFile.value) return
  importForm.file = importFile.value
  importForm.mode = importMode.value
  importForm.post(route('import-export.import'), {
    forceFormData: true,
    onSuccess: () => { importFile.value = null },
  })
}
</script>
