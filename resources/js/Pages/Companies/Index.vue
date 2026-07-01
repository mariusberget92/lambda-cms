<template>
  <AppLayout title="Companies">
    <Head title="Companies" />

    <PageHeader title="Companies" description="Manage your CRM companies">
      <template #actions>
        <a
          :href="route('companies.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New company
        </a>
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
          placeholder="Search companies..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
    </div>

    <DataTable :empty="!companies.data.length">
      <template #empty>
        No companies yet. <a :href="route('companies.create')" class="text-primary hover:underline">Create one.</a>
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
        <th class="text-left">Company</th>
        <th class="text-left hidden sm:table-cell">Domain</th>
        <th class="text-left hidden sm:table-cell w-24">Contacts</th>
        <th class="text-left hidden md:table-cell w-24">Deals</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="company in companies.data"
          :key="company.id"
          class="group"
          :class="{ 'bg-muted/20': selectedIds.includes(company.id) }"
        >
          <td class="w-10">
            <input
              type="checkbox"
              :checked="selectedIds.includes(company.id)"
              @change="toggleRow(company.id)"
             
            />
          </td>
          <td>
            <div class="font-medium">{{ company.name }}</div>
            <div v-if="company.phone" class="text-xs text-muted-foreground mt-0.5">{{ company.phone }}</div>
          </td>
          <td class="hidden sm:table-cell text-muted-foreground text-sm">{{ company.domain ?? '—' }}</td>
          <td class="hidden sm:table-cell">
            <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
              {{ company.contacts_count }}
            </span>
          </td>
          <td class="hidden md:table-cell">
            <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
              {{ company.deals_count }}
            </span>
          </td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('companies.edit', company.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                type="button"
                @click="confirmDelete(company)"
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
    <div v-if="companies.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ companies.from }}–{{ companies.to }} of {{ companies.total }}
      </p>
      <div class="flex gap-1">
        <component
          :is="link.url ? 'a' : 'span'"
          v-for="link in companies.links"
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
      title="Delete company?"
      confirm-label="Delete"
      @close="deleteTarget = null"
      @confirm="doDelete"
    >
      "<span class="font-medium text-foreground">{{ deleteTarget?.name }}</span>"
      <span v-if="deleteTarget?.contacts_count > 0"> has {{ deleteTarget.contacts_count }} contact{{ deleteTarget.contacts_count !== 1 ? 's' : '' }}. Contacts will be unlinked, not deleted.</span>
      <span v-else> will be permanently deleted.</span>
    </ConfirmModal>

    <ConfirmModal
      :open="showBulkDeleteModal"
      :title="`Delete ${selectedIds.length} ${selectedIds.length === 1 ? 'company' : 'companies'}?`"
      description="Contacts will be unlinked, not deleted."
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

const props = defineProps({
  companies: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')

let searchTimeout = null
function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(
      route('companies.index'),
      { search: search.value },
      { preserveState: true, replace: true }
    )
  }, 300)
}

// -- Selection --

const selectedIds = ref([])
const showBulkDeleteModal = ref(false)

watch(() => props.companies, () => { selectedIds.value = [] })

const isAllSelected = computed(() =>
  props.companies.data.length > 0 &&
  props.companies.data.every(c => selectedIds.value.includes(c.id))
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : props.companies.data.map(c => c.id)
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
    route('companies.bulk'),
    { action: 'delete', ids: selectedIds.value },
    { onSuccess: () => { selectedIds.value = [] } }
  )
}

// -- Single delete --

const deleteTarget = ref(null)

function confirmDelete(company) { deleteTarget.value = company }

function doDelete() {
  if (!deleteTarget.value) return
  router.delete(route('companies.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
