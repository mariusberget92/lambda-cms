<!-- resources/js/Pages/Templates/Index.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { LayoutTemplate, Plus, Pencil, Trash2, ChevronDown } from 'lucide-vue-next'
import { formatDate } from '@/lib/utils.js'

const props = defineProps({
  templates: Object, // grouped by type
})

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
  'partial':        'Partial',
}

const ALL_TYPES = ['blog-index', 'single-post', 'archive', 'search-results', 'partial']

// Flatten all templates into one sorted list
const allTemplates = computed(() => {
  return ALL_TYPES.flatMap(t => props.templates[t] ?? [])
    .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
})

// New Template dropdown
const dropdownOpen = ref(false)
const dropdownRef  = ref(null)

function toggleDropdown() { dropdownOpen.value = !dropdownOpen.value }

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    dropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))

// Delete
const deleteTarget = ref(null)

function confirmDelete(template) {
  deleteTarget.value = template
}

function deleteTemplate() {
  if (!deleteTarget.value) return
  router.delete(route('templates.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<template>
  <AppLayout title="Templates">
    <Head title="Templates" />

    <div class="mb-4 flex items-start justify-between gap-4">
      <div>
        <h2 class="text-lg font-semibold">Templates</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Reusable layout templates and partials</p>
      </div>

      <!-- New Template dropdown -->
      <div ref="dropdownRef" class="relative shrink-0">
        <button
          type="button"
          @click="toggleDropdown"
          class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
        >
          <Plus class="w-3.5 h-3.5" />
          New Template
          <ChevronDown class="w-3.5 h-3.5 ml-0.5 transition-transform" :class="{ 'rotate-180': dropdownOpen }" />
        </button>

        <Transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="dropdownOpen"
            class="absolute right-0 top-full mt-1 z-20 min-w-[160px] rounded-lg border bg-card shadow-md py-1"
          >
            <a
              v-for="type in ALL_TYPES"
              :key="type"
              :href="route('templates.create', { type })"
              class="flex items-center px-3 py-2 text-sm hover:bg-accent transition-colors"
              @click="dropdownOpen = false"
            >
              {{ TYPE_LABELS[type] }}
            </a>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Single table -->
    <div v-if="allTemplates.length > 0" class="rounded-lg border bg-card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="border-b bg-muted/50">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Type</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Updated</th>
            <th class="px-4 py-3 w-10" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="template in allTemplates"
            :key="template.id"
            class="border-b last:border-0 hover:bg-muted/30 transition-colors group"
          >
            <td class="px-4 py-3 font-medium">{{ template.title }}</td>
            <td class="px-4 py-3 text-muted-foreground">{{ TYPE_LABELS[template.type] ?? template.type }}</td>
            <td class="px-4 py-3">
              <StatusBadge :status="template.status" />
            </td>
            <td class="px-4 py-3 text-muted-foreground">{{ formatDate(template.updated_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <a
                  :href="route('templates.edit', template.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                  title="Edit"
                >
                  <Pencil class="w-3.5 h-3.5" />
                </a>
                <button
                  type="button"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                  title="Delete"
                  @click="confirmDelete(template)"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div v-else class="rounded-lg border bg-card px-6 py-12 text-center">
      <LayoutTemplate class="w-10 h-10 mx-auto text-muted-foreground/40 mb-3" />
      <p class="text-sm text-muted-foreground mb-4">No templates yet. Create one using the button above.</p>
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete template?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.title }}</span>" will be permanently deleted.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="deleteTemplate"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
