<template>
  <AppLayout :title="isEditing ? 'Edit Call List' : 'New Call List'">
    <Head :title="isEditing ? 'Edit Call List' : 'New Call List'" />

    <form @submit.prevent="submit" class="max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('call-lists.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit call list' : 'New call list' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? callList.name : 'Create a new call list' }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            autofocus
            placeholder="e.g. Q3 Follow-ups"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
          />
        </div>

        <div class="space-y-1">
          <label for="description" class="text-sm font-medium">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            placeholder="Optional notes about this call list..."
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
          />
        </div>

        <div class="space-y-1">
          <label class="text-sm font-medium">Status <span class="text-destructive">*</span></label>
          <SelectBox
            v-model="form.status"
            :data="[
              { value: 'active',    label: 'Active' },
              { value: 'completed', label: 'Completed' },
              { value: 'archived',  label: 'Archived' },
            ]"
          />
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a
          :href="route('call-lists.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create call list' }}
        </button>
      </div>
    </form>

    <!-- Contacts section (edit mode only) -->
    <div v-if="isEditing" class="max-w-2xl mt-6">
      <div class="rounded-lg border bg-card p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold">Contacts</h3>
            <p class="text-xs text-muted-foreground mt-0.5">{{ contacts.length }} contact{{ contacts.length !== 1 ? 's' : '' }} in this list</p>
          </div>
        </div>

        <!-- Add contacts -->
        <div class="flex items-center gap-2 mb-4">
          <div class="flex-1">
            <ContactPicker
              :contacts="availableContacts"
              placeholder="Add a contact..."
              @select="addContact"
            />
          </div>
        </div>

        <!-- Contact list -->
        <div v-if="contacts.length > 0" class="border rounded-md divide-y">
          <div
            v-for="contact in contacts"
            :key="contact.id"
            class="flex items-center gap-3 px-3 py-2.5 text-sm"
          >
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ contact.full_name }}</div>
              <div class="text-xs text-muted-foreground truncate">
                {{ [contact.email, contact.company].filter(Boolean).join(' · ') || '—' }}
              </div>
            </div>
            <StatusBadge :status="contact.call_status.replace('_', ' ')" />
            <button
              type="button"
              @click="removeContact(contact.id)"
              class="inline-flex items-center justify-center w-7 h-7 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors shrink-0"
              title="Remove"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
        <div v-else class="text-sm text-muted-foreground text-center py-6">
          No contacts added yet. Use the picker above to add contacts.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SelectBox from '@/Components/SelectBox.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ContactPicker from '@/Components/ContactPicker.vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  callList: { type: Object, default: null },
  contacts: { type: Array, default: () => [] },
  availableContacts: { type: Array, default: () => [] },
})

const isEditing = computed(() => !!props.callList)

const form = useForm({
  name: props.callList?.name ?? '',
  description: props.callList?.description ?? '',
  status: props.callList?.status ?? 'active',
})

function submit() {
  if (isEditing.value) {
    form.put(route('call-lists.update', props.callList.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  } else {
    form.post(route('call-lists.store'), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
  }
}

function addContact(contactId) {
  router.post(route('call-lists.add-contacts', props.callList.id), {
    contact_ids: [contactId],
  }, { preserveScroll: true })
}

function removeContact(contactId) {
  router.post(route('call-lists.remove-contacts', props.callList.id), {
    contact_ids: [contactId],
  }, { preserveScroll: true })
}
</script>
