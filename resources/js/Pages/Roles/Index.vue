<template>
  <AppLayout title="Roles">
    <Head title="Roles" />

    <PageHeader title="Roles" description="Manage roles and their permissions">
      <template #actions>
        <a
          :href="route('roles.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New role
        </a>
      </template>
    </PageHeader>

    <div class="space-y-3">
      <div
        v-for="role in roles"
        :key="role.id"
        class="rounded-lg border bg-card p-5"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <!-- Name + badges -->
            <div class="flex items-center gap-2 mb-2">
              <h3 class="font-semibold text-sm">
                {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
              </h3>
              <span
                v-if="role.is_system"
                class="inline-flex items-center rounded-full bg-primary/10 text-primary px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
              >System</span>
              <span class="text-xs text-muted-foreground">
                {{ role.users_count }} {{ role.users_count === 1 ? 'user' : 'users' }}
              </span>
            </div>

            <!-- Permission chips -->
            <div v-if="role.permissions.length" class="flex flex-wrap gap-1.5">
              <span
                v-for="perm in role.permissions.slice(0, 12)"
                :key="perm"
                class="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-[11px] text-muted-foreground"
              >{{ perm }}</span>
              <span
                v-if="role.permissions.length > 12"
                class="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-[11px] text-muted-foreground font-medium"
              >+{{ role.permissions.length - 12 }} more</span>
            </div>
            <p v-else class="text-xs text-muted-foreground italic">No permissions assigned</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 shrink-0">
            <a
              v-if="!role.is_system"
              :href="route('roles.edit', role.id)"
              class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
              title="Edit"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </a>
            <button
              v-if="!role.is_system"
              type="button"
              :disabled="role.users_count > 0"
              :title="role.users_count > 0 ? 'Cannot delete a role that has users assigned' : 'Delete'"
              @click="confirmDelete(role)"
              class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-muted-foreground"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete role?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            The <strong>{{ deleteTarget.name }}</strong> role will be permanently removed.
            Users with this role will lose it.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >Cancel</button>
            <button
              type="button"
              @click="deleteRole"
              :disabled="deleting"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 transition-colors"
            >{{ deleting ? 'Deleting...' : 'Delete' }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

defineProps({
  roles: { type: Array, default: () => [] },
})

const deleteTarget = ref(null)
const deleting = ref(false)

function confirmDelete(role) {
  deleteTarget.value = role
}

function deleteRole() {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(route('roles.destroy', deleteTarget.value.id), {
    onFinish: () => {
      deleting.value = false
      deleteTarget.value = null
    },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
