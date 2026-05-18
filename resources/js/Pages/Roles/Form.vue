<template>
  <AppLayout :title="isEditing ? 'Edit Role' : 'New Role'">
    <Head :title="isEditing ? 'Edit Role' : 'New Role'" />

    <form @submit.prevent="submit" class="max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('roles.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit role' : 'New role' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? role.name : 'Define a name and select permissions' }}
          </p>
        </div>
      </div>

      <!-- System role notice -->
      <div
        v-if="role?.is_system"
        class="rounded-md bg-muted/50 border px-4 py-3 text-sm text-muted-foreground mb-4"
      >
        The <strong>administrator</strong> role is a system role and cannot be modified.
        It always has all permissions.
      </div>

      <!-- Name -->
      <div class="rounded-lg border bg-card p-6 mb-4">
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Role name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="e.g. editor, senior author"
            :disabled="role?.is_system"
            autofocus
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
          <p class="text-xs text-muted-foreground">Lowercase only. Used as the role identifier.</p>
        </div>
      </div>

      <!-- Permissions -->
      <div class="rounded-lg border bg-card p-6 space-y-6">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold">Permissions</h3>
          <button
            v-if="!role?.is_system"
            type="button"
            class="text-xs text-muted-foreground hover:text-foreground transition-colors"
            @click="toggleAll"
          >
            {{ allSelected ? 'Deselect all' : 'Select all' }}
          </button>
        </div>

        <div
          v-for="(perms, group) in permissions"
          :key="group"
          class="space-y-2"
        >
          <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ group }}</p>
            <button
              v-if="!role?.is_system"
              type="button"
              class="text-[11px] text-muted-foreground hover:text-foreground transition-colors"
              @click="toggleGroup(perms)"
            >
              {{ groupAllSelected(perms) ? 'Deselect' : 'Select all' }}
            </button>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
            <label
              v-for="perm in perms"
              :key="perm"
              class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm cursor-pointer transition-colors"
              :class="[
                role?.is_system ? 'opacity-60 cursor-not-allowed' : 'hover:bg-muted/50',
                form.permissions.includes(perm) ? 'bg-primary/5 border border-primary/20' : 'border border-transparent'
              ]"
            >
              <input
                type="checkbox"
                :value="perm"
                v-model="form.permissions"
                :disabled="role?.is_system"
                class="rounded border-muted-foreground/30 accent-primary"
              />
              <span>{{ perm }}</span>
            </label>
          </div>
        </div>
      </div>

      <div v-if="!role?.is_system" class="flex gap-3 mt-4 justify-end">
        <a
          :href="route('roles.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >Cancel</a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create role' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  role:        { type: Object, default: null },
  permissions: { type: Object, default: () => ({}) },
})

const isEditing = computed(() => !!props.role)

const allPermissions = computed(() => Object.values(props.permissions).flat())

const form = useForm({
  name:        props.role?.name ?? '',
  permissions: props.role?.permissions ? [...props.role.permissions] : [],
})

const allSelected = computed(() =>
  allPermissions.value.every(p => form.permissions.includes(p))
)

function groupAllSelected(perms) {
  return perms.every(p => form.permissions.includes(p))
}

function toggleAll() {
  if (allSelected.value) {
    form.permissions = []
  } else {
    form.permissions = [...allPermissions.value]
  }
}

function toggleGroup(perms) {
  if (groupAllSelected(perms)) {
    form.permissions = form.permissions.filter(p => !perms.includes(p))
  } else {
    const toAdd = perms.filter(p => !form.permissions.includes(p))
    form.permissions = [...form.permissions, ...toAdd]
  }
}

function submit() {
  if (isEditing.value) {
    form.put(route('roles.update', props.role.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  } else {
    form.post(route('roles.store'), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  }
}
</script>
