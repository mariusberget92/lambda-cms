<template>
  <AppLayout title="Categories">
    <Head title="Categories" />

    <PageHeader title="Categories" description="Organise posts by category">
      <template #actions>
        <a
          :href="route('categories.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New category
        </a>
      </template>
    </PageHeader>

    <!-- Category bubble map -->
    <div class="rounded-lg border bg-card p-6 mb-6">
      <p class="text-xs font-medium text-muted-foreground mb-4">Category cloud</p>
      <div v-if="allCategories.length === 0" class="text-sm text-muted-foreground text-center py-4">
        No categories yet.
      </div>
      <div v-else class="flex flex-wrap gap-2 items-center justify-center">
        <a
          v-for="(cat, i) in allCategories"
          :key="cat.id"
          :href="route('categories.edit', cat.id)"
          :title="`${cat.name} — ${cat.posts_count} post${cat.posts_count !== 1 ? 's' : ''}`"
          :style="bubbleStyle(cat, i)"
          class="rounded-full border font-medium transition-all duration-150 hover:scale-105 hover:opacity-90 cursor-pointer leading-none"
        >
          {{ cat.name }}
        </a>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
        </svg>
        <input
          v-model="search"
          type="search"
          placeholder="Search categories..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
    </div>

    <DataTable :empty="categories.data.length === 0">
      <template #empty>
        <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
        </svg>
        No categories yet.
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
        <th class="text-left hidden md:table-cell">Description</th>
        <th class="text-left hidden sm:table-cell w-24">Posts</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="cat in categories.data"
          :key="cat.id"
          class="group"
          :class="{ 'bg-muted/20': selectedIds.includes(cat.id) }"
        >
          <td class="w-10">
            <input
              type="checkbox"
              :checked="selectedIds.includes(cat.id)"
              @change="toggleRow(cat.id)"
             
            />
          </td>
          <td>
            <div class="font-medium flex items-center gap-1.5">
              <span
                v-if="cat.color"
                class="inline-block w-2.5 h-2.5 rounded-full shrink-0"
                :style="{ backgroundColor: cat.color }"
              />
              {{ cat.name }}
            </div>
            <div class="text-xs text-muted-foreground font-mono mt-0.5">{{ cat.slug }}</div>
          </td>
          <td class="hidden md:table-cell text-muted-foreground text-sm">
            {{ cat.description ?? '—' }}
          </td>
          <td class="hidden sm:table-cell">
            <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
              {{ cat.posts_count }}
            </span>
          </td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('categories.edit', cat.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                type="button"
                @click="openDeleteModal(cat)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                title="Delete"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="categories.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ categories.from }}–{{ categories.to }} of {{ categories.total }}
      </p>
      <div class="flex gap-1">
        <component
          :is="link.url ? 'a' : 'span'"
          v-for="link in categories.links"
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
      title="Delete category?"
      confirm-label="Delete"
      @close="deleteTarget = null"
      @confirm="deleteCategory"
    >
      "<span class="font-medium text-foreground">{{ deleteTarget?.name }}</span>"
      <span v-if="deleteTarget?.posts_count > 0"> is used by {{ deleteTarget.posts_count }} post{{ deleteTarget.posts_count !== 1 ? 's' : '' }}. Posts will not be deleted.</span>
      <span v-else> will be permanently deleted.</span>
    </ConfirmModal>

    <ConfirmModal
      :open="showBulkDeleteModal"
      :title="`Delete ${selectedIds.length} ${selectedIds.length === 1 ? 'category' : 'categories'}?`"
      description="Posts will not be deleted."
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
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'
import { bubbleStyle as _bubbleStyle } from '@/lib/chartColors.js'
const props = defineProps({
  categories: Object,
  allCategories: Array,
  filters: Object,
})

const maxCount = computed(() => Math.max(...(props.allCategories || []).map(c => c.posts_count), 1))

function bubbleStyle(cat, index) {
  return _bubbleStyle(cat, index, maxCount.value)
}

// -- Search --

const search = ref(props.filters?.search ?? '')

let searchTimeout = null
function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(
      route('categories.index'),
      { search: search.value },
      { preserveState: true, replace: true }
    )
  }, 300)
}

// -- Selection --

const selectedIds = ref([])
const showBulkDeleteModal = ref(false)

watch(() => props.categories, () => { selectedIds.value = [] })

const isAllSelected = computed(() =>
  props.categories.data.length > 0 &&
  props.categories.data.every(c => selectedIds.value.includes(c.id))
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : props.categories.data.map(c => c.id)
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

// -- Bulk actions --

function confirmBulkDelete() { showBulkDeleteModal.value = true }

function executeBulkDelete() {
  showBulkDeleteModal.value = false
  router.post(
    route('categories.bulk'),
    { action: 'delete', ids: selectedIds.value },
    { onSuccess: () => { selectedIds.value = [] } }
  )
}

// -- Single delete --

const deleteTarget = ref(null)

function openDeleteModal(category) { deleteTarget.value = category }

function deleteCategory() {
  if (!deleteTarget.value) return
  router.delete(route('categories.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
