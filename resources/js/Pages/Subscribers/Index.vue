<template>
  <AppLayout title="Subscribers">
    <Head title="Subscribers" />

    <PageHeader title="Subscribers" description="Manage newsletter subscribers">
      <template #actions>
        <a
          :href="route('subscribers.export')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <Download class="w-4 h-4" />
          Export CSV
        </a>
        <a
          :href="route('subscribers.import')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <Upload class="w-4 h-4" />
          Import
        </a>
      </template>
    </PageHeader>

    <!-- Status tabs -->
    <div class="flex items-center gap-1 mb-4">
      <button
        v-for="tab in statusTabs"
        :key="tab.value"
        type="button"
        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
        :class="currentStatus === tab.value
          ? 'bg-primary text-primary-foreground'
          : 'text-muted-foreground hover:bg-accent'"
        @click="filterByStatus(tab.value)"
      >
        {{ tab.label }}
        <span class="ml-1 opacity-60">{{ tab.count }}</span>
      </button>
    </div>

    <!-- Search -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <input
          v-model="search"
          type="search"
          placeholder="Search subscribers..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
    </div>

    <!-- Bulk bar -->
    <Transition name="fade">
      <div v-if="selectedIds.length" class="flex items-center gap-3 rounded-lg border bg-muted/40 px-4 py-2 mb-4">
        <span class="text-sm font-medium">{{ selectedIds.length }} selected</span>
        <button
          type="button"
          @click="showBulkDeleteModal = true"
          class="ml-auto text-xs font-medium text-destructive hover:underline"
        >
          Delete selected
        </button>
      </div>
    </Transition>

    <DataTable :empty="subscribers.data.length === 0">
      <template #empty>
        <UserRound class="w-8 h-8 mx-auto mb-3 opacity-40" />
        No subscribers yet.
      </template>
      <template #headers>
        <th class="w-10">
          <input type="checkbox" :checked="isAllSelected" :indeterminate="selectedIds.length > 0 && !isAllSelected" @change="toggleAll" />
        </th>
        <th class="text-left">Email</th>
        <th class="text-left hidden sm:table-cell">Name</th>
        <th class="text-left hidden md:table-cell w-28">Status</th>
        <th class="text-left hidden lg:table-cell w-36">Subscribed</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr v-for="sub in subscribers.data" :key="sub.id" class="group" :class="{ 'bg-muted/20': selectedIds.includes(sub.id) }">
          <td class="w-10">
            <input type="checkbox" :checked="selectedIds.includes(sub.id)" @change="toggleRow(sub.id)" />
          </td>
          <td>
            <span class="text-sm font-medium">{{ sub.email }}</span>
          </td>
          <td class="hidden sm:table-cell">
            <span class="text-sm text-muted-foreground">{{ sub.name || '—' }}</span>
          </td>
          <td class="hidden md:table-cell">
            <StatusBadge :status="sub.status" />
          </td>
          <td class="hidden lg:table-cell">
            <span class="text-sm text-muted-foreground">{{ formatDate(sub.subscribed_at) }}</span>
          </td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                type="button"
                @click="openDeleteModal(sub)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                title="Delete"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="subscribers.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ subscribers.from }}–{{ subscribers.to }} of {{ subscribers.total }}
      </p>
      <div class="flex gap-1">
        <template v-for="link in subscribers.links" :key="link.label">
          <component
            :is="link.url ? 'a' : 'span'"
            :href="link.url"
            v-html="link.label"
            class="px-3 py-1 rounded-md text-sm"
            :class="link.active
              ? 'bg-primary text-primary-foreground font-medium'
              : link.url
                ? 'hover:bg-accent text-muted-foreground'
                : 'text-muted-foreground/30 cursor-not-allowed'"
          />
        </template>
      </div>
    </div>

    <ConfirmModal
      :open="showDeleteModal"
      title="Delete subscriber?"
      :description="`Remove ${deletingSubscriber?.email} from the subscriber list.`"
      confirm-label="Delete"
      :processing="deleteProcessing"
      @close="showDeleteModal = false"
      @confirm="confirmDelete"
    />

    <ConfirmModal
      :open="showBulkDeleteModal"
      title="Delete selected subscribers?"
      :description="`This will permanently delete ${selectedIds.length} subscriber(s).`"
      confirm-label="Delete all"
      :processing="bulkProcessing"
      @close="showBulkDeleteModal = false"
      @confirm="confirmBulkDelete"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { Search, Download, Upload, UserRound, Trash2 } from '@lucide/vue'

const props = defineProps({
  subscribers: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  counts: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const currentStatus = ref(props.filters.status ?? 'all')
const selectedIds = ref([])
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const deletingSubscriber = ref(null)
const deleteProcessing = ref(false)
const bulkProcessing = ref(false)

let searchTimeout = null

const statusTabs = computed(() => [
  { value: 'all', label: 'All', count: props.counts.all ?? 0 },
  { value: 'active', label: 'Active', count: props.counts.active ?? 0 },
  { value: 'unsubscribed', label: 'Unsubscribed', count: props.counts.unsubscribed ?? 0 },
])

const isAllSelected = computed(() =>
  props.subscribers.data.length > 0 && selectedIds.value.length === props.subscribers.data.length
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : props.subscribers.data.map(s => s.id)
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id)
  idx === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(idx, 1)
}

function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('subscribers.index'), {
      search: search.value || undefined,
      status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, { preserveState: true, replace: true })
  }, 300)
}

function filterByStatus(status) {
  currentStatus.value = status
  router.get(route('subscribers.index'), {
    search: search.value || undefined,
    status: status !== 'all' ? status : undefined,
  }, { preserveState: true, replace: true })
}

function openDeleteModal(sub) {
  deletingSubscriber.value = sub
  showDeleteModal.value = true
}

function confirmDelete() {
  deleteProcessing.value = true
  router.delete(route('subscribers.destroy', deletingSubscriber.value.id), {
    preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false; deleteProcessing.value = false },
    onError: () => { deleteProcessing.value = false },
  })
}

function confirmBulkDelete() {
  bulkProcessing.value = true
  router.post(route('subscribers.bulk'), { ids: selectedIds.value, action: 'delete' }, {
    preserveScroll: true,
    onSuccess: () => {
      showBulkDeleteModal.value = false
      bulkProcessing.value = false
      selectedIds.value = []
    },
    onError: () => { bulkProcessing.value = false },
  })
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
