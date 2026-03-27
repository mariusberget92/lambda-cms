<template>
  <AppLayout :title="isEditing ? 'Edit User' : 'Invite User'">
    <Head :title="isEditing ? 'Edit User' : 'Invite User'" />

    <form @submit.prevent="submit" class="max-w-xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('users.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit user' : 'Invite user' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? user.name : 'Create a new account and send a welcome email' }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">

        <!-- Info banner -->
        <div class="flex items-start gap-3 rounded-md bg-muted/50 border px-4 py-3 text-sm text-muted-foreground">
          <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span v-if="isEditing">
            The user manages their own password. You can update their name, email and role here.
          </span>
          <span v-else>
            A welcome email with an email verification link and password setup link will be sent automatically.
          </span>
        </div>

        <!-- Name -->
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Jane Doe"
            autofocus
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
          />
        </div>

        <!-- Email -->
        <div class="space-y-1">
          <label for="email" class="text-sm font-medium">Email address <span class="text-destructive">*</span></label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="jane@example.com"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.email }"
          />
        </div>

        <!-- Role -->
        <div class="space-y-1">
          <label for="role" class="text-sm font-medium">Role <span class="text-destructive">*</span></label>
          <SelectBox
            v-model="form.role"
            :data="roleOptions"
            :disabled="isLastAdmin"
            placeholder="— Select a role —"
          />
          <p class="text-xs text-muted-foreground">
            <strong>Administrator</strong> — full access.
            <strong>User</strong> — can manage posts, categories and tags.
          </p>
          <p v-if="isLastAdmin" class="text-xs text-status-warning-fg flex items-center gap-1">
            <svg aria-hidden="true" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Role cannot be changed — this is the only administrator.
          </p>
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a
          :href="route('users.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Send invitation' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import SelectBox from '@/Components/SelectBox.vue'
import { useNotifications } from '@/composables/useNotifications.js'
const { notify } = useNotifications()

const props = defineProps({
  user:       { type: Object, default: null },
  roles:      { type: Array,  default: () => [] },
  adminCount: { type: Number, default: 0 },
});

const isEditing = computed(() => !!props.user);

const isLastAdmin = computed(
  () => props.user?.role === 'administrator' && props.adminCount <= 1
);

const roleOptions = computed(() => props.roles.map(r => ({
  value: r,
  label: r === 'administrator' ? 'Administrator' : 'User',
})))

const form = useForm({
  name:  props.user?.name  ?? "",
  email: props.user?.email ?? "",
  role:  props.user?.role  ?? "",
});

function submit() {
  if (isEditing.value) {
    form.put(route("users.update", props.user.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    });
  } else {
    form.post(route("users.store"), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    });
  }
}
</script>
