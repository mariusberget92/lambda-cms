<template>
  <AppLayout :title="isEditing ? 'Edit Company' : 'New Company'">
    <Head :title="isEditing ? 'Edit Company' : 'New Company'" />

    <form @submit.prevent="submit" class="max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('companies.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit company' : 'New company' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? company.name : 'Add a new company to your CRM' }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Company name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            autofocus
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label for="domain" class="text-sm font-medium">Website / Domain</label>
            <input
              id="domain"
              v-model="form.domain"
              type="text"
              placeholder="example.com"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <div class="space-y-1">
            <label for="phone" class="text-sm font-medium">Phone</label>
            <input
              id="phone"
              v-model="form.phone"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
        </div>

        <div class="space-y-1">
          <label for="address" class="text-sm font-medium">Address</label>
          <textarea
            id="address"
            v-model="form.address"
            rows="2"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
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
          :href="route('companies.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create company' }}
        </button>
      </div>
    </form>

    <!-- Activity log (edit mode only) -->
    <div v-if="isEditing" class="max-w-2xl mt-6">
      <ActivityLog
        :activities="activities"
        subject-type="company"
        :subject-id="company.id"
        @delete="deleteActivity"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ActivityLog from '@/Components/ActivityLog.vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  company: { type: Object, default: null },
  activities: { type: Array, default: () => [] },
})

const isEditing = computed(() => !!props.company)

const form = useForm({
  name: props.company?.name ?? '',
  domain: props.company?.domain ?? '',
  phone: props.company?.phone ?? '',
  address: props.company?.address ?? '',
  notes: props.company?.notes ?? '',
})

function submit() {
  if (isEditing.value) {
    form.put(route('companies.update', props.company.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  } else {
    form.post(route('companies.store'), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  }
}

function deleteActivity(id) {
  router.delete(route('activities.destroy', id), { preserveScroll: true })
}
</script>
