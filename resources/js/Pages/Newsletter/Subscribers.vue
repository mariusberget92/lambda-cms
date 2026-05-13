<template>
  <AppLayout title="Newsletter Subscribers">
    <Head title="Newsletter Subscribers" />

    <PageHeader title="Newsletter Subscribers" description="Manage your newsletter subscriber list.">
      <template #actions>
        <a
          :href="route('newsletter.subscribers.export')"
          class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <Download class="w-4 h-4" />
          Export CSV
        </a>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="flex gap-4 mb-6 text-sm">
      <div class="bg-card border rounded-lg px-4 py-3 flex items-center gap-3">
        <span class="text-muted-foreground">Confirmed</span>
        <span class="font-semibold text-lg">{{ totalConfirmed }}</span>
      </div>
      <div class="bg-card border rounded-lg px-4 py-3 flex items-center gap-3">
        <span class="text-muted-foreground">Pending</span>
        <span class="font-semibold text-lg">{{ totalPending }}</span>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-4 border-b">
      <a
        v-for="f in filterOptions"
        :key="f.value"
        :href="route('newsletter.subscribers') + '?filter=' + f.value"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="filter === f.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
      >{{ f.label }}</a>
    </div>

    <!-- Empty state -->
    <div v-if="subscribers.data.length === 0" class="py-16 text-center">
      <Mail class="w-10 h-10 mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground text-sm">No subscribers yet.</p>
    </div>

    <!-- Table -->
    <div v-else class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-muted-foreground">
          <tr>
            <th class="px-4 py-3 w-10">
              <input
                type="checkbox"
                :checked="isAllSelected"
                :indeterminate="selectedIds.length > 0 && !isAllSelected"
                @change="toggleAll"
                class="rounded"
              />
            </th>
            <th class="text-left font-medium px-4 py-3">Email</th>
            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Name</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Status</th>
            <th class="text-left font-medium px-4 py-3 hidden lg:table-cell">Subscribed</th>
            <th class="px-4 py-3 w-10"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr
            v-for="sub in subscribers.data"
            :key="sub.id"
            class="hover:bg-muted/30 transition-colors group"
            :class="{ 'bg-muted/20': selectedIds.includes(sub.id) }"
          >
            <td class="px-4 py-3 w-10">
              <input
                type="checkbox"
                :checked="selectedIds.includes(sub.id)"
                @change="toggleRow(sub.id)"
                class="rounded"
              />
            </td>
            <td class="px-4 py-3 font-medium">{{ sub.email }}</td>
            <td class="px-4 py-3 hidden sm:table-cell text-muted-foreground">{{ sub.name ?? '—' }}</td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="sub.confirmed_at
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                  : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'"
              >{{ sub.confirmed_at ? 'Confirmed' : 'Pending' }}</span>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">{{ sub.created_at }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                  type="button"
                  @click="confirmDelete(sub)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                  title="Delete"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="subscribers.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ subscribers.from }}–{{ subscribers.to }} of {{ subscribers.total }}
      </p>
      <div class="flex gap-1">
        <a
          v-for="link in subscribers.links"
          :key="link.label"
          :href="link.url ?? undefined"
          class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm transition-colors"
          :class="link.active
            ? 'bg-primary text-primary-foreground font-medium'
            : link.url
              ? 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              : 'text-muted-foreground/40 cursor-not-allowed pointer-events-none'"
        >{{ decodeHtmlEntities(link.label) }}</a>
      </div>
    </div>

    <!-- Bulk toolbar -->
    <Transition name="slide-up">
      <div
        v-if="selectedIds.length > 0"
        class="fixed bottom-0 left-0 right-0 z-40 bg-card border-t shadow-lg"
      >
        <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center gap-3">
          <span class="text-sm font-medium text-muted-foreground">{{ selectedIds.length }} selected</span>
          <button
            type="button"
            @click="confirmBulkDelete"
            class="rounded-md border border-destructive/30 px-3 py-1.5 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
          >Remove selected</button>
          <button type="button" @click="selectedIds = []" class="ml-auto text-sm text-muted-foreground hover:text-foreground">✕</button>
        </div>
      </div>
    </Transition>

    <!-- Delete modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Remove subscriber?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.email }}</span>" will be removed.
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="deleteTarget = null" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</button>
            <button type="button" @click="doDelete" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Remove</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Bulk delete modal -->
    <Transition name="fade">
      <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showBulkModal = false" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Remove {{ selectedIds.length }} subscriber(s)?</h3>
          <p class="text-sm text-muted-foreground mb-5">This cannot be undone.</p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="showBulkModal = false" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</button>
            <button type="button" @click="executeBulkDelete" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Remove</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Mail, Download, Trash2 } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'
import { useNotifications } from '@/composables/useNotifications'

const props = defineProps({
  subscribers:    Object,
  filter:         { type: String, default: 'confirmed' },
  totalConfirmed: { type: Number, default: 0 },
  totalPending:   { type: Number, default: 0 },
})

const page = usePage()
const { notify } = useNotifications()

watch(() => page.props.flash, (flash) => {
  if (flash?.status) notify(flash.status, 'success')
})

const filterOptions = [
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Pending',   value: 'pending' },
  { label: 'All',       value: 'all' },
]

const selectedIds  = ref([])
const deleteTarget = ref(null)
const showBulkModal = ref(false)

watch(() => props.subscribers, () => { selectedIds.value = [] })

const isAllSelected = computed(() =>
  props.subscribers.data.length > 0 &&
  props.subscribers.data.every(s => selectedIds.value.includes(s.id))
)

function toggleAll() {
  isAllSelected.value
    ? (selectedIds.value = [])
    : (selectedIds.value = props.subscribers.data.map(s => s.id))
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id)
  idx === -1 ? selectedIds.value.push(id) : selectedIds.value.splice(idx, 1)
}

function confirmDelete(sub) { deleteTarget.value = sub }

function doDelete() {
  if (!deleteTarget.value) return
  router.delete(route('newsletter.subscribers.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}

function confirmBulkDelete() { showBulkModal.value = true }

function executeBulkDelete() {
  showBulkModal.value = false
  router.delete(route('newsletter.subscribers.bulk'), {
    data: { ids: selectedIds.value },
    onSuccess: () => { selectedIds.value = [] },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
