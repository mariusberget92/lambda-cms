<template>
  <AppLayout :title="isEditing ? 'Edit Deal' : 'New Deal'">
    <Head :title="isEditing ? 'Edit Deal' : 'New Deal'" />

    <form @submit.prevent="submit" class="max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('deals.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit deal' : 'New deal' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? deal.name : 'Add a new deal to your pipeline' }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Deal name <span class="text-destructive">*</span></label>
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
            <label for="value" class="text-sm font-medium">Value</label>
            <input
              id="value"
              v-model="form.value"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Stage <span class="text-destructive">*</span></label>
            <SelectBox
              v-model="form.stage"
              :data="stageOptions"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-sm font-medium">Contact</label>
            <SelectBox
              v-model="form.contact_id"
              :data="contactOptions"
              placeholder="— No contact —"
              searchable
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
          <label for="expected_close_date" class="text-sm font-medium">Expected close date</label>
          <input
            id="expected_close_date"
            v-model="form.expected_close_date"
            type="date"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
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
          :href="route('deals.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create deal' }}
        </button>
      </div>
    </form>

    <!-- Activity log (edit mode only) -->
    <div v-if="isEditing" class="max-w-2xl mt-6">
      <ActivityLog
        :activities="activities"
        subject-type="deal"
        :subject-id="deal.id"
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
  deal: { type: Object, default: null },
  contacts: { type: Array, default: () => [] },
  companies: { type: Array, default: () => [] },
  stages: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
})

const isEditing = computed(() => !!props.deal)

const stageOptions = computed(() =>
  props.stages.map(s => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1) }))
)

const contactOptions = computed(() =>
  [{ value: null, label: '— No contact —' }, ...props.contacts.map(c => ({ value: c.id, label: `${c.first_name} ${c.last_name}` }))]
)

const companyOptions = computed(() =>
  [{ value: null, label: '— No company —' }, ...props.companies.map(c => ({ value: c.id, label: c.name }))]
)

const form = useForm({
  name: props.deal?.name ?? '',
  value: props.deal?.value ?? '',
  stage: props.deal?.stage ?? 'lead',
  contact_id: props.deal?.contact_id ?? null,
  company_id: props.deal?.company_id ?? null,
  expected_close_date: props.deal?.expected_close_date ?? '',
  notes: props.deal?.notes ?? '',
})

function submit() {
  if (isEditing.value) {
    form.put(route('deals.update', props.deal.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  } else {
    form.post(route('deals.store'), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  }
}

function deleteActivity(id) {
  router.delete(route('activities.destroy', id), { preserveScroll: true })
}
</script>
