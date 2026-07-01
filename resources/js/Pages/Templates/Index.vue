<template>
  <AppLayout title="Templates">
    <Head title="Templates" />

    <PageHeader title="Templates" description="Reusable layout templates and partials">
      <template #actions>
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
      </template>
    </PageHeader>

    <!-- Search -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
        </svg>
        <input
          v-model="search"
          type="search"
          placeholder="Search templates..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
    </div>

    <!-- Table -->
    <DataTable :empty="templates.data.length === 0">
      <template #empty>
        <LayoutTemplate class="w-10 h-10 mx-auto text-muted-foreground/40 mb-3" />
        <p class="text-sm text-muted-foreground">No templates yet. Create one using the button above.</p>
      </template>
      <template #headers>
        <th class="w-10">
          <input
            type="checkbox"
            :checked="isAllSelected"
            :indeterminate="selectedIds.length > 0 && !isAllSelected"
            @change="toggleAll"
           
          />
        </th>
        <th class="text-left">Name</th>
        <th class="text-left">Type</th>
        <th class="text-left">Status</th>
        <th class="text-left">Updated</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="template in templates.data"
          :key="template.id"
          class="group"
          :class="{ 'bg-muted/20': selectedIds.includes(template.id) }"
        >
          <td class="w-10">
            <input
              type="checkbox"
              :checked="selectedIds.includes(template.id)"
              :disabled="template.is_system"
              @change="toggleRow(template.id)"
             
            />
          </td>
          <td class="font-medium">{{ template.title }}</td>
          <td class="text-muted-foreground">{{ TYPE_LABELS[template.type] ?? template.type }}</td>
          <td>
            <StatusBadge :status="template.status" />
          </td>
          <td class="text-muted-foreground text-xs">{{ formatDate(template.updated_at) }}</td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('templates.edit', template.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <Pencil class="w-3.5 h-3.5" />
              </a>
              <span
                v-if="template.is_system"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground/40 cursor-default"
                title="System templates cannot be deleted"
              >
                <Lock class="w-3.5 h-3.5" />
              </span>
              <button
                v-else
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
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="templates.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ templates.from }}–{{ templates.to }} of {{ templates.total }}
      </p>
      <div class="flex gap-1">
        <component
          :is="link.url ? 'a' : 'span'"
          v-for="link in templates.links"
          :key="link.label"
          :href="link.url || undefined"
          class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm transition-colors"
          :class="link.active
            ? 'bg-primary text-primary-foreground font-medium'
            : link.url
              ? 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              : 'text-muted-foreground/40 cursor-default'"
        >{{ decodeHtmlEntities(link.label) }}</component>
      </div>
    </div>

    <ConfirmModal
      :open="!!deleteTarget"
      title="Delete template?"
      :description="`&quot;${deleteTarget?.title}&quot; will be permanently deleted.`"
      confirm-label="Delete"
      @close="deleteTarget = null"
      @confirm="deleteTemplate"
    />

    <ConfirmModal
      :open="showBulkDeleteModal"
      :title="`Delete ${selectedIds.length} template${selectedIds.length === 1 ? '' : 's'}?`"
      description="This cannot be undone."
      confirm-label="Delete"
      @close="showBulkDeleteModal = false"
      @confirm="executeBulkDelete"
    />

    <!-- Sticky bulk action toolbar -->
    <Transition name="slide-up">
      <div
        v-if="selectedIds.length > 0"
        class="fixed bottom-0 left-0 right-0 z-40 bg-card border-t shadow-lg"
      >
        <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center gap-3">
          <span class="text-sm font-medium text-muted-foreground">
            {{ selectedIds.length }} selected
          </span>
          <div class="flex items-center gap-2 ml-2">
            <button
              type="button"
              @click="confirmBulkDelete"
              class="rounded-md border border-destructive/30 px-3 py-1.5 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
            >
              Delete
            </button>
          </div>
          <button
            type="button"
            @click="selectedIds = []"
            class="ml-auto text-sm text-muted-foreground hover:text-foreground transition-colors"
            aria-label="Clear selection"
          >
            ✕
          </button>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { LayoutTemplate, Plus, Pencil, Trash2, ChevronDown, Lock } from '@lucide/vue'
import { formatDate, decodeHtmlEntities } from '@/lib/utils.js'
const props = defineProps({
  templates: Object,
  filters: Object,
})

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
  'partial':        'Partial',
  'header':         'Header',
  'footer':         'Footer',
}

const ALL_TYPES = ['blog-index', 'single-post', 'archive', 'search-results', 'partial', 'header', 'footer']

// -- Search --

const search = ref(props.filters?.search ?? '')

let searchTimeout = null
function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(
      route('templates.index'),
      { search: search.value },
      { preserveState: true, replace: true }
    )
  }, 300)
}

// -- Dropdown --

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

// -- Selection --

const selectedIds = ref([])
const showBulkDeleteModal = ref(false)

watch(() => props.templates, () => { selectedIds.value = [] })

const selectableTemplates = computed(() => props.templates.data.filter(t => !t.is_system))

const isAllSelected = computed(() =>
  selectableTemplates.value.length > 0 &&
  selectableTemplates.value.every(t => selectedIds.value.includes(t.id))
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : selectableTemplates.value.map(t => t.id)
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

// -- Bulk delete --

function confirmBulkDelete() { showBulkDeleteModal.value = true }

function executeBulkDelete() {
  showBulkDeleteModal.value = false
  router.post(
    route('templates.bulk'),
    { action: 'delete', ids: selectedIds.value },
    { onSuccess: () => { selectedIds.value = [] } }
  )
}

// -- Single delete --

const deleteTarget = ref(null)

function confirmDelete(template) { deleteTarget.value = template }

function deleteTemplate() {
  if (!deleteTarget.value) return
  router.delete(route('templates.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
