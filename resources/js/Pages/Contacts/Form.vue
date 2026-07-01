<template>
  <AppLayout :title="isEditing ? 'Edit Contact' : 'New Contact'">
    <Head :title="isEditing ? 'Edit Contact' : 'New Contact'" />

    <form @submit.prevent="submit" class="max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('contacts.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit contact' : 'New contact' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? `${contact.first_name} ${contact.last_name}` : 'Add a new contact to your CRM' }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label for="first_name" class="text-sm font-medium">First name <span class="text-destructive">*</span></label>
            <input
              id="first_name"
              v-model="form.first_name"
              type="text"
              autofocus
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.first_name }"
            />
          </div>
          <div class="space-y-1">
            <label for="last_name" class="text-sm font-medium">Last name <span class="text-destructive">*</span></label>
            <input
              id="last_name"
              v-model="form.last_name"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.last_name }"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label for="email" class="text-sm font-medium">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.email }"
            />
          </div>
          <div class="space-y-1">
            <label for="phone" class="text-sm font-medium">Phone</label>
            <input
              id="phone"
              v-model="form.phone"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.phone }"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label for="position" class="text-sm font-medium">Position</label>
            <input
              id="position"
              v-model="form.position"
              type="text"
              placeholder="e.g. Marketing Director"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Company</label>
            <SelectBox
              v-model="form.company_id"
              :data="companyOptions"
              placeholder="— No company —"
              searchable
            />
          </div>
        </div>

        <div class="space-y-1">
          <label class="text-sm font-medium">Status <span class="text-destructive">*</span></label>
          <SelectBox
            v-model="form.status"
            :data="[
              { value: 'active',   label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
              { value: 'archived', label: 'Archived' },
            ]"
          />
        </div>

        <div class="space-y-1">
          <label for="notes" class="text-sm font-medium">Notes</label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="3"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
          />
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a
          :href="route('contacts.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create contact' }}
        </button>
      </div>
    </form>

    <!-- Activity log (edit mode only) -->
    <div v-if="isEditing" class="max-w-2xl mt-6">
      <ActivityLog
        :activities="activities"
        subject-type="contact"
        :subject-id="contact.id"
        @delete="deleteActivity"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SelectBox from '@/Components/SelectBox.vue'
import ActivityLog from '@/Components/ActivityLog.vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  contact: { type: Object, default: null },
  companies: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
})

const isEditing = computed(() => !!props.contact)

const companyOptions = computed(() =>
  [{ value: null, label: '— No company —' }, ...props.companies.map(c => ({ value: c.id, label: c.name }))]
)

const form = useForm({
  first_name: props.contact?.first_name ?? '',
  last_name: props.contact?.last_name ?? '',
  email: props.contact?.email ?? '',
  phone: props.contact?.phone ?? '',
  position: props.contact?.position ?? '',
  company_id: props.contact?.company_id ?? null,
  status: props.contact?.status ?? 'active',
  notes: props.contact?.notes ?? '',
})

function submit() {
  if (isEditing.value) {
    form.put(route('contacts.update', props.contact.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  } else {
    form.post(route('contacts.store'), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  }
}

function deleteActivity(id) {
  router.delete(route('activities.destroy', id), { preserveScroll: true })
}
</script>
