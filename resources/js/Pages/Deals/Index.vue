<template>
  <AppLayout title="Deals">
    <Head title="Deals" />

    <PageHeader title="Deals" description="Track your sales pipeline">
      <template #actions>
        <div class="flex items-center gap-2">
          <!-- View toggle -->
          <div class="inline-flex rounded-md border overflow-hidden">
            <button
              type="button"
              class="px-3 py-2 text-sm font-medium transition-colors"
              :class="view === 'board' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
              @click="view = 'board'"
            >
              <Columns3 class="w-4 h-4" />
            </button>
            <button
              type="button"
              class="px-3 py-2 text-sm font-medium transition-colors border-l"
              :class="view === 'table' ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
              @click="view = 'table'"
            >
              <List class="w-4 h-4" />
            </button>
          </div>
          <a
            :href="route('deals.create')"
            class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New deal
          </a>
        </div>
      </template>
    </PageHeader>

    <!-- Filters -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
        </svg>
        <input
          v-model="search"
          type="search"
          placeholder="Search deals..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
    </div>

    <!-- Board View -->
    <div v-if="view === 'board'" class="flex gap-4 overflow-x-auto pb-4">
      <div
        v-for="stage in stages"
        :key="stage"
        class="flex-shrink-0 w-72 rounded-lg border bg-muted/20"
      >
        <!-- Column header -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" :class="stageColor(stage)" />
            <span class="text-sm font-semibold capitalize">{{ stage }}</span>
          </div>
          <span class="text-xs text-muted-foreground">
            {{ dealsByStage(stage).length }}
          </span>
        </div>

        <!-- Cards -->
        <div class="p-2 space-y-2 min-h-[120px]">
          <div
            v-for="deal in dealsByStage(stage)"
            :key="deal.id"
            class="rounded-md border bg-card p-3 hover:shadow-sm transition-shadow cursor-pointer"
            @click="navigateTo(route('deals.edit', deal.id))"
          >
            <div class="font-medium text-sm mb-1">{{ deal.name }}</div>
            <div v-if="deal.value" class="text-sm font-semibold text-primary mb-1">
              {{ formatCurrency(deal.value) }}
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <span v-if="deal.contact" class="text-xs text-muted-foreground">{{ deal.contact.full_name }}</span>
              <span v-if="deal.company" class="text-xs text-muted-foreground">· {{ deal.company.name }}</span>
            </div>
            <div v-if="deal.expected_close_date" class="text-xs text-muted-foreground mt-1.5">
              Close: {{ deal.expected_close_date }}
            </div>
          </div>
          <div v-if="dealsByStage(stage).length === 0" class="py-6 text-center text-xs text-muted-foreground">
            No deals
          </div>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <template v-if="view === 'table'">
      <DataTable :empty="!deals.data.length">
        <template #empty>
          No deals yet. <a :href="route('deals.create')" class="text-primary hover:underline">Create one.</a>
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
          <th class="text-left">Deal</th>
          <th class="text-left hidden sm:table-cell">Value</th>
          <th class="text-left hidden sm:table-cell">Stage</th>
          <th class="text-left hidden md:table-cell">Contact</th>
          <th class="text-left hidden md:table-cell">Close date</th>
          <th class="w-10"></th>
        </template>
        <template #rows>
          <tr
            v-for="deal in deals.data"
            :key="deal.id"
            class="group"
            :class="{ 'bg-muted/20': selectedIds.includes(deal.id) }"
          >
            <td class="w-10">
              <input
                type="checkbox"
                :checked="selectedIds.includes(deal.id)"
                @change="toggleRow(deal.id)"
               
              />
            </td>
            <td>
              <div class="font-medium">{{ deal.name }}</div>
              <div v-if="deal.company" class="text-xs text-muted-foreground mt-0.5">{{ deal.company.name }}</div>
            </td>
            <td class="hidden sm:table-cell text-sm">
              {{ deal.value ? formatCurrency(deal.value) : '—' }}
            </td>
            <td class="hidden sm:table-cell">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                :class="stageBadge(deal.stage)"
              >{{ deal.stage }}</span>
            </td>
            <td class="hidden md:table-cell text-sm text-muted-foreground">
              {{ deal.contact?.full_name ?? '—' }}
            </td>
            <td class="hidden md:table-cell text-sm text-muted-foreground">
              {{ deal.expected_close_date ?? '—' }}
            </td>
            <td>
              <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <a
                  :href="route('deals.edit', deal.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                  title="Edit"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </a>
                <button
                  type="button"
                  @click="confirmDelete(deal)"
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
      <div v-if="deals.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
        <p class="text-muted-foreground">
          Showing {{ deals.from }}–{{ deals.to }} of {{ deals.total }}
        </p>
        <div class="flex gap-1">
          <component
            :is="link.url ? 'a' : 'span'"
            v-for="link in deals.links"
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
    </template>

    <ConfirmModal
      :open="!!deleteTarget"
      title="Delete deal?"
      :description="`&quot;${deleteTarget?.name}&quot; will be permanently deleted.`"
      confirm-label="Delete"
      @close="deleteTarget = null"
      @confirm="doDelete"
    />

    <ConfirmModal
      :open="showBulkDeleteModal"
      :title="`Delete ${selectedIds.length} deal${selectedIds.length === 1 ? '' : 's'}?`"
      description="This cannot be undone."
      confirm-label="Delete"
      @close="showBulkDeleteModal = false"
      @confirm="executeBulkDelete"
    />

    <!-- Sticky bulk action toolbar (table view only) -->
    <Transition name="slide-up">
      <div
        v-if="selectedIds.length > 0 && view === 'table'"
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
import { Columns3, List } from '@lucide/vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

const props = defineProps({
  deals: Object,
  filters: Object,
  stages: Array,
})

const view = ref('board')
const search = ref(props.filters?.search ?? '')

let searchTimeout = null
function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(
      route('deals.index'),
      { search: search.value },
      { preserveState: true, replace: true }
    )
  }, 300)
}

function dealsByStage(stage) {
  return props.deals.data.filter(d => d.stage === stage)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)
}

function navigateTo(url) {
  router.visit(url)
}

function stageColor(stage) {
  const map = {
    lead: 'bg-blue-500',
    qualified: 'bg-yellow-500',
    proposal: 'bg-purple-500',
    won: 'bg-green-500',
    lost: 'bg-red-500',
  }
  return map[stage] || 'bg-muted-foreground'
}

function stageBadge(stage) {
  const map = {
    lead: 'bg-blue-500/10 text-blue-600',
    qualified: 'bg-yellow-500/10 text-yellow-600',
    proposal: 'bg-purple-500/10 text-purple-600',
    won: 'bg-green-500/10 text-green-600',
    lost: 'bg-red-500/10 text-red-600',
  }
  return map[stage] || 'bg-muted text-muted-foreground'
}

// -- Selection (table view) --

const selectedIds = ref([])
const showBulkDeleteModal = ref(false)

watch(() => props.deals, () => { selectedIds.value = [] })

const isAllSelected = computed(() =>
  props.deals.data.length > 0 &&
  props.deals.data.every(d => selectedIds.value.includes(d.id))
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : props.deals.data.map(d => d.id)
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

function confirmBulkDelete() { showBulkDeleteModal.value = true }

function executeBulkDelete() {
  showBulkDeleteModal.value = false
  router.post(
    route('deals.bulk'),
    { action: 'delete', ids: selectedIds.value },
    { onSuccess: () => { selectedIds.value = [] } }
  )
}

// -- Single delete --

const deleteTarget = ref(null)

function confirmDelete(deal) { deleteTarget.value = deal }

function doDelete() {
  if (!deleteTarget.value) return
  router.delete(route('deals.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
