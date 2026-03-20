<template>
  <AppLayout title="Users">
    <Head title="Users" />

    <div class="mb-6">
      <h2 class="text-lg font-semibold">Users</h2>
      <p class="text-sm text-muted-foreground mt-0.5">Manage who has access to Lambda CMS.</p>
      <a
        :href="route('users.create')"
        class="mt-3 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Invite user
      </a>
    </div>

    <!-- Flash -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg mb-4"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Table -->
    <DataTable :loading="false" :empty="users.data.length === 0">
      <template #empty>No users found.</template>
      <template #headers>
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
          class="hover:bg-muted/20 transition-colors"
        >
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
            <div class="flex items-center justify-end gap-2">
              <a
                :href="route('users.edit', user.id)"
                class="rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                v-if="user.id !== currentUserId"
                type="button"
                @click="handleDeleteClick(user)"
                :disabled="isLastAdmin(user)"
                :aria-label="isLastAdmin(user) ? 'Cannot delete the only administrator' : 'Delete ' + user.name"
                class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-muted-foreground"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="users.last_page > 1" class="flex justify-end gap-1 mt-4">
      <a
        v-for="link in users.links"
        :key="link.label"
        :href="link.url ?? '#'"
        class="px-3 py-1.5 rounded-md text-sm border transition-colors"
        :class="link.active
          ? 'bg-primary text-primary-foreground border-primary'
          : link.url
            ? 'hover:bg-accent text-foreground border-border'
            : 'text-muted-foreground border-border cursor-default pointer-events-none'"
      >{{ decodeHtmlEntities(link.label) }}</a>
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-card rounded-lg border shadow-lg max-w-sm w-full p-6 space-y-4">
          <h3 class="text-sm font-semibold">Delete user?</h3>
          <p class="text-sm text-muted-foreground">
            <strong>{{ deleteTarget.name }}</strong> ({{ deleteTarget.email }}) will be permanently removed. This action cannot be undone.
          </p>
          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="deleteUser"
              :disabled="deleting"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 transition-colors"
            >
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from '@/Components/DataTable.vue'

const props = defineProps({
  users:      { type: Object, required: true },
  adminCount: { type: Number, default: 0 },
});

const page          = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const deleteTarget = ref(null);
const deleting     = ref(false);

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea');
  txt.innerHTML = str;
  return txt.value;
}

function initials(name) {
  return name.split(" ").map(n => n[0]).slice(0, 2).join("").toUpperCase();
}

function isLastAdmin(user) {
  return user.role === 'administrator' && props.adminCount <= 1;
}

function handleDeleteClick(user) {
  if (isLastAdmin(user)) return;
  confirmDelete(user);
}

function confirmDelete(user) {
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
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
