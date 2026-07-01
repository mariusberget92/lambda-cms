<template>
  <AppLayout title="Users">
    <Head title="Users" />

    <PageHeader title="Users" description="Manage admin users and roles">
      <template #actions>
        <a
          :href="route('users.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Invite user
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
          placeholder="Search users..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
    </div>

    <!-- Table -->
    <DataTable :empty="users.data.length === 0">
      <template #empty>No users found.</template>
      <template #headers>
        <th class="w-10">
          <input
            type="checkbox"
            :checked="isAllSelected"
            :indeterminate="selectedIds.length > 0 && !isAllSelected"
            @change="toggleAll"
           
          />
        </th>
        <th class="text-left">User</th>
        <th class="text-left">Role</th>
        <th class="text-left">Verified</th>
        <th class="text-left">Last seen</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="user in users.data"
          :key="user.id"
          class="group"
          :class="{ 'bg-muted/20': selectedIds.includes(user.id) }"
        >
          <td class="w-10">
            <input
              type="checkbox"
              :checked="selectedIds.includes(user.id)"
              :disabled="user.id === currentUserId || isLastAdmin(user)"
              @change="toggleRow(user.id)"
             
            />
          </td>
          <!-- User -->
          <td>
            <div class="flex items-center gap-3">
              <div class="relative shrink-0">
                <div class="w-8 h-8 rounded-full overflow-hidden bg-muted flex items-center justify-center text-xs font-semibold uppercase">
                  <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover" />
                  <span v-else>{{ initials(user.name) }}</span>
                </div>
                <span
                  v-if="user.is_online"
                  class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-online-dot ring-2 ring-card"
                ></span>
              </div>
              <div>
                <p class="font-medium">{{ user.name }}</p>
                <span
                  v-if="user.is_banned"
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-destructive/10 text-destructive border border-destructive/20 ml-1"
                >
                  {{ user.banned_until ? 'Banned · ' + timeLeft(user.banned_until) : 'Banned · Permanent' }}
                </span>
                <p class="text-xs text-muted-foreground">{{ user.email }}</p>
              </div>
            </div>
          </td>
          <!-- Role -->
          <td>
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="user.role === 'administrator'
                ? 'bg-role-admin-bg text-role-admin-fg'
                : 'bg-role-user-bg text-role-user-fg'"
            >
              {{ user.role === 'administrator' ? 'Administrator' : 'User' }}
            </span>
          </td>
          <!-- Verified -->
          <td>
            <span v-if="user.email_verified" class="text-status-success-fg">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </span>
            <span v-else class="text-muted-foreground text-xs">Pending</span>
          </td>
          <!-- Last seen -->
          <td class="text-muted-foreground text-xs">
            {{ user.last_seen_at ?? 'Never' }}
          </td>
          <!-- Actions -->
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <!-- Unban -->
              <button
                v-if="user.is_banned"
                type="button"
                @click="handleUnban(user)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                :aria-label="'Unban ' + user.name"
                title="Unban"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
              </button>
              <!-- Ban -->
              <button
                v-else-if="user.role !== 'administrator' && user.id !== currentUserId"
                type="button"
                @click="openBanModal(user)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                :aria-label="'Ban ' + user.name"
                title="Ban"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
              </button>
              <a
                :href="route('users.edit', user.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                v-if="user.id !== currentUserId"
                type="button"
                @click="handleDeleteClick(user)"
                :disabled="isLastAdmin(user)"
                :aria-label="isLastAdmin(user) ? 'Cannot delete the only administrator' : 'Delete ' + user.name"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-muted-foreground"
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
    <div v-if="users.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
      </p>
      <div class="flex gap-1">
        <component
          :is="link.url ? 'a' : 'span'"
          v-for="link in users.links"
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
      title="Delete user?"
      confirm-label="Delete"
      processing-label="Deleting..."
      :processing="deleting"
      @close="deleteTarget = null"
      @confirm="deleteUser"
    >
      <strong>{{ deleteTarget?.name }}</strong> ({{ deleteTarget?.email }}) will be permanently removed. This action cannot be undone.
    </ConfirmModal>

    <ConfirmModal
      :open="showBulkDeleteModal"
      :title="`Delete ${selectedIds.length} user${selectedIds.length === 1 ? '' : 's'}?`"
      description="This cannot be undone. Administrators and yourself will be skipped."
      confirm-label="Delete"
      @close="showBulkDeleteModal = false"
      @confirm="executeBulkDelete"
    />

    <!-- Ban modal -->
    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="banModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="banModal.open = false">
        <div class="w-full max-w-sm rounded-lg border bg-card shadow-xl p-6 space-y-4">
          <h3 class="font-semibold">Ban {{ banModal.user?.name }}</h3>

          <div class="space-y-1">
            <label class="text-sm font-medium">Reason <span class="text-destructive">*</span></label>
            <textarea
              v-model="banForm.reason"
              rows="3"
              placeholder="Explain why this user is being banned…"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
              :class="{ 'border-destructive': banForm.errors.reason }"
            />
            <p v-if="banForm.errors.reason" class="text-xs text-destructive mt-1">{{ banForm.errors.reason }}</p>
          </div>

          <div class="space-y-1">
            <label class="text-sm font-medium">Duration <span class="text-destructive">*</span></label>
            <select
              v-model="banForm.duration"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': banForm.errors.duration }"
            >
              <option value="">— Select duration —</option>
              <option value="1h">1 hour</option>
              <option value="6h">6 hours</option>
              <option value="24h">24 hours</option>
              <option value="7d">7 days</option>
              <option value="30d">30 days</option>
              <option value="permanent">Permanent</option>
            </select>
            <p v-if="banForm.errors.duration" class="text-xs text-destructive mt-1">{{ banForm.errors.duration }}</p>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors" @click="banModal.open = false">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors" :disabled="banForm.processing" @click="submitBan">Ban user</button>
          </div>
        </div>
      </div>
    </Transition>

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
import { ref, computed, watch } from "vue";
import { Head, router, usePage, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { useNotifications } from '@/composables/useNotifications'
import { decodeHtmlEntities } from '@/lib/utils.js'

const props = defineProps({
  users:      { type: Object, required: true },
  adminCount: { type: Number, default: 0 },
  filters:    { type: Object, default: () => ({}) },
});

const page          = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const { notify } = useNotifications()

// -- Search --

const search = ref(props.filters?.search ?? '')

let searchTimeout = null
function applyFilters() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(
      route('users.index'),
      { search: search.value },
      { preserveState: true, replace: true }
    )
  }, 300)
}

// -- Selection --

const selectedIds = ref([])
const showBulkDeleteModal = ref(false)

watch(() => props.users, () => { selectedIds.value = [] })

const selectableUsers = computed(() =>
  props.users.data.filter(u => u.id !== currentUserId.value && !isLastAdmin(u))
)

const isAllSelected = computed(() =>
  selectableUsers.value.length > 0 &&
  selectableUsers.value.every(u => selectedIds.value.includes(u.id))
)

function toggleAll() {
  selectedIds.value = isAllSelected.value ? [] : selectableUsers.value.map(u => u.id)
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
    route('users.bulk'),
    { action: 'delete', ids: selectedIds.value },
    { onSuccess: () => { selectedIds.value = [] } }
  )
}

// -- Single delete --

const deleteTarget = ref(null);
const deleting     = ref(false);

function isLastAdmin(user) {
  return user.role === 'administrator' && props.adminCount <= 1;
}

function handleDeleteClick(user) {
  if (isLastAdmin(user)) return;
  deleteTarget.value = user;
}

function deleteUser() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  router.delete(route("users.destroy", deleteTarget.value.id), {
    onFinish: () => {
      deleting.value = false;
      deleteTarget.value = null;
    },
  });
}

// -- Ban --

const banModal = ref({ open: false, user: null })
const banForm  = useForm({ reason: '', duration: '' })

function openBanModal(user) {
  banModal.value = { open: true, user }
  banForm.reset()
}

function submitBan() {
  banForm.post(route('users.ban', banModal.value.user.id), {
    onSuccess: () => { banModal.value.open = false },
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

function handleUnban(user) {
  useForm({}).delete(route('users.unban', user.id), {
    onError: () => notify('Failed to unban user.', 'error'),
  })
}

function timeLeft(isoString) {
  const until = new Date(isoString)
  const diffMs = until - Date.now()
  if (diffMs <= 0) return 'expired'
  const mins  = Math.floor(diffMs / 60000)
  const hours = Math.floor(mins / 60)
  const days  = Math.floor(hours / 24)
  if (days > 0)  return `${days}d left`
  if (hours > 0) return `${hours}h left`
  return `${mins}m left`
}

function initials(name) {
  return name.split(" ").map(n => n[0]).slice(0, 2).join("").toUpperCase();
}
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
