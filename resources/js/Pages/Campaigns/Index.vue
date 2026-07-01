<template>
  <AppLayout title="Campaigns">
    <Head title="Campaigns" />

    <PageHeader title="Campaigns" description="Create and send email campaigns to your subscribers">
      <template #actions>
        <a
          :href="route('campaigns.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <Plus class="w-4 h-4" />
          New campaign
        </a>
      </template>
    </PageHeader>

    <!-- Status filter -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
        <input
          v-model="search"
          type="search"
          placeholder="Search campaigns..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
      <SelectBox
        :model-value="currentStatus"
        :data="statusOptions"
        @update:model-value="filterByStatus"
      />
    </div>

    <!-- Bulk bar -->
    <Transition name="fade">
      <div v-if="selectedIds.length" class="flex items-center gap-3 rounded-lg border bg-muted/40 px-4 py-2 mb-4">
        <span class="text-sm font-medium">{{ selectedIds.length }} selected</span>
        <button type="button" @click="showBulkDeleteModal = true" class="ml-auto text-xs font-medium text-destructive hover:underline">
          Delete selected
        </button>
      </div>
    </Transition>

    <DataTable :empty="campaigns.data.length === 0">
      <template #empty>
        <Send class="w-8 h-8 mx-auto mb-3 opacity-40" />
        No campaigns yet.
      </template>
      <template #headers>
        <th class="w-10">
          <input type="checkbox" :checked="isAllSelected" :indeterminate="selectedIds.length > 0 && !isAllSelected" @change="toggleAll" />
        </th>
        <th class="text-left">Campaign</th>
        <th class="text-left hidden sm:table-cell w-28">Status</th>
        <th class="text-left hidden md:table-cell w-28">Recipients</th>
        <th class="text-left hidden lg:table-cell w-40">Date</th>
        <th class="w-20"></th>
      </template>
      <template #rows>
        <tr v-for="c in campaigns.data" :key="c.id" class="group" :class="{ 'bg-muted/20': selectedIds.includes(c.id) }">
          <td class="w-10">
            <input type="checkbox" :checked="selectedIds.includes(c.id)" @change="toggleRow(c.id)" />
          </td>
          <td>
            <div class="font-medium text-sm">{{ c.name }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">{{ c.subject }}</div>
          </td>
          <td class="hidden sm:table-cell">
            <StatusBadge :status="c.status" />
          </td>
          <td class="hidden md:table-cell">
            <span class="text-sm text-muted-foreground">{{ c.recipients_count ?? 0 }}</span>
          </td>
          <td class="hidden lg:table-cell">
            <div v-if="c.status === 'scheduled' && c.scheduled_at" class="text-sm text-muted-foreground">
              <Clock class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" />{{ formatDateTime(c.scheduled_at) }}
            </div>
            <span v-else class="text-sm text-muted-foreground">{{ c.sent_at ? formatDate(c.sent_at) : '—' }}</span>
          </td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                v-if="c.status === 'draft' || c.status === 'scheduled'"
                :href="route('campaigns.edit', c.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <Pencil class="w-3.5 h-3.5" />
              </a>
              <a
                v-if="c.status !== 'draft' && c.status !== 'scheduled'"
                :href="route('campaigns.report', c.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Report"
              >
                <BarChart3 class="w-3.5 h-3.5" />
              </a>
              <button
                v-if="c.status !== 'sending'"
                type="button"
                @click="openDeleteModal(c)"
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
    <div v-if="campaigns.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ campaigns.from }}–{{ campaigns.to }} of {{ campaigns.total }}
      </p>
      <div class="flex gap-1">
        <template v-for="link in campaigns.links" :key="link.label">
          <component
            :is="link.url ? 'a' : 'span'"
            :href="link.url"
            v-html="link.label"
            class="px-3 py-1 rounded-md text-sm"
            :class="link.active ? 'bg-primary text-primary-foreground font-medium' : link.url ? 'hover:bg-accent text-muted-foreground' : 'text-muted-foreground/30 cursor-not-allowed'"
          />
        </template>
      </div>
    </div>

    <ConfirmModal
      :open="showDeleteModal"
      title="Delete campaign?"
      :description="`Permanently delete '${deletingCampaign?.name}'.`"
      confirm-label="Delete"
      :processing="deleteProcessing"
      @close="showDeleteModal = false"
      @confirm="confirmDelete"
    />

    <ConfirmModal
      :open="showBulkDeleteModal"
      title="Delete selected campaigns?"
      :description="`This will permanently delete ${selectedIds.length} campaign(s).`"
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
import SelectBox from '@/Components/SelectBox.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { Plus, Search, Send, Pencil, BarChart3, Trash2, Clock } from '@lucide/vue'

const props = defineProps({
  campaigns: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const currentStatus = ref(props.filters.status ?? 'all')
const selectedIds = ref([])
const showDeleteModal = ref(false)
const showBulkDeleteModal = ref(false)
const deletingCampaign = ref(null)
const deleteProcessing = ref(false)
const bulkProcessing = ref(false)

let searchTimeout = null

const statusOptions = [
  { value: 'all',     label: 'All statuses' },
  { value: 'draft',     label: 'Draft' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'sending',   label: 'Sending' },
  { value: 'sent',    label: 'Sent' },
  { value: 'failed',  label: 'Failed' },
]

const isAllSelected = computed(() =>
  props.campaigns.data.length > 0 && selectedIds.value.length === props.campaigns.data.length
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : props.campaigns.data.map(c => c.id)
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id)
  idx === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(idx, 1)
}

function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('campaigns.index'), {
      search: search.value || undefined,
      status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, { preserveState: true, replace: true })
  }, 300)
}

function filterByStatus(status) {
  currentStatus.value = status
  router.get(route('campaigns.index'), {
    search: search.value || undefined,
    status: status !== 'all' ? status : undefined,
  }, { preserveState: true, replace: true })
}

function openDeleteModal(c) {
  deletingCampaign.value = c
  showDeleteModal.value = true
}

function confirmDelete() {
  deleteProcessing.value = true
  router.delete(route('campaigns.destroy', deletingCampaign.value.id), {
    preserveScroll: true,
    onSuccess: () => { showDeleteModal.value = false; deleteProcessing.value = false },
    onError: () => { deleteProcessing.value = false },
  })
}

function confirmBulkDelete() {
  bulkProcessing.value = true
  router.post(route('campaigns.bulk'), { ids: selectedIds.value, action: 'delete' }, {
    preserveScroll: true,
    onSuccess: () => { showBulkDeleteModal.value = false; bulkProcessing.value = false; selectedIds.value = [] },
    onError: () => { bulkProcessing.value = false },
  })
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatDateTime(dateStr) {
  return new Date(dateStr).toLocaleString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
