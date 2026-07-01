<template>
  <AppLayout :title="`Working: ${callList.name}`">
    <Head :title="`Working: ${callList.name}`" />

    <div class="flex items-center gap-3 mb-6">
      <a
        :href="route('call-lists.index')"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div class="flex-1">
        <h2 class="text-lg font-semibold">{{ callList.name }}</h2>
        <div class="flex items-center gap-3 mt-1">
          <div class="flex items-center gap-2">
            <div class="h-1.5 w-24 rounded-full bg-muted overflow-hidden">
              <div
                class="h-full bg-primary rounded-full transition-all"
                :style="{ width: progressPercent + '%' }"
              />
            </div>
            <span class="text-xs text-muted-foreground tabular-nums">
              {{ completedCount }}/{{ contacts.length }} completed
            </span>
          </div>
          <StatusBadge :status="callList.status" />
        </div>
      </div>
    </div>

    <div v-if="contacts.length === 0" class="rounded-lg border bg-card p-12 text-center">
      <p class="text-sm text-muted-foreground">No contacts in this list.</p>
      <a :href="route('call-lists.edit', callList.id)" class="text-sm text-primary hover:underline mt-2 inline-block">
        Add contacts to get started.
      </a>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">
      <!-- Sidebar: contact list -->
      <div class="rounded-lg border bg-card overflow-hidden">
        <div class="p-3 border-b">
          <p class="text-xs font-medium text-muted-foreground">Contact list</p>
        </div>
        <div class="max-h-[60vh] overflow-y-auto divide-y">
          <button
            v-for="contact in contacts"
            :key="contact.id"
            type="button"
            class="w-full text-left px-3 py-2.5 text-sm transition-colors"
            :class="contact.id === currentContactId ? 'bg-accent' : 'hover:bg-accent/50'"
            @click="selectContact(contact.id)"
          >
            <div class="flex items-center gap-2">
              <span
                class="w-2 h-2 rounded-full shrink-0"
                :class="statusDot(contact.call_status)"
              />
              <span class="truncate font-medium">{{ contact.full_name }}</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Main: current contact -->
      <div v-if="current" class="space-y-4">
        <!-- Contact card -->
        <div class="rounded-lg border bg-card p-6">
          <h3 class="text-base font-semibold mb-3">{{ current.full_name }}</h3>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div v-if="current.email">
              <span class="text-xs text-muted-foreground">Email</span>
              <p class="font-medium">{{ current.email }}</p>
            </div>
            <div v-if="current.phone">
              <span class="text-xs text-muted-foreground">Phone</span>
              <p class="font-medium">{{ current.phone }}</p>
            </div>
            <div v-if="current.position">
              <span class="text-xs text-muted-foreground">Position</span>
              <p class="font-medium">{{ current.position }}</p>
            </div>
            <div v-if="current.company">
              <span class="text-xs text-muted-foreground">Company</span>
              <p class="font-medium">{{ current.company }}</p>
            </div>
          </div>
        </div>

        <!-- Status + Notes -->
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div class="space-y-1">
            <label class="text-sm font-medium">Call status</label>
            <SelectBox
              v-model="statusForm.call_status"
              :data="statusOptions"
            />
          </div>

          <div class="space-y-1">
            <label for="call-notes" class="text-sm font-medium">Notes</label>
            <textarea
              id="call-notes"
              v-model="statusForm.notes"
              rows="3"
              placeholder="Add notes about this call..."
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
            />
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="saveStatus"
              :disabled="saving"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
            <button
              type="button"
              @click="saveAndNext"
              :disabled="saving"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors disabled:opacity-50"
            >
              Save & Next
            </button>
          </div>
        </div>

        <!-- Navigation -->
        <div class="flex items-center justify-between">
          <button
            type="button"
            :disabled="currentIndex <= 0"
            @click="navigate(-1)"
            class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-sm hover:bg-accent transition-colors disabled:opacity-40 disabled:cursor-default"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Previous
          </button>
          <span class="text-xs text-muted-foreground tabular-nums">
            {{ currentIndex + 1 }} of {{ contacts.length }}
          </span>
          <button
            type="button"
            :disabled="currentIndex >= contacts.length - 1"
            @click="navigate(1)"
            class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-sm hover:bg-accent transition-colors disabled:opacity-40 disabled:cursor-default"
          >
            Next
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import SelectBox from '@/Components/SelectBox.vue'
const props = defineProps({
  callList: Object,
  contacts: Array,
  currentContactId: Number,
})

const saving = ref(false)

const statusOptions = [
  { value: 'not_called', label: 'Not Called' },
  { value: 'called',     label: 'Called' },
  { value: 'no_answer',  label: 'No Answer' },
  { value: 'callback',   label: 'Callback' },
  { value: 'completed',  label: 'Completed' },
]

const currentIndex = computed(() =>
  props.contacts.findIndex(c => c.id === props.currentContactId)
)

const current = computed(() =>
  props.contacts.find(c => c.id === props.currentContactId) ?? null
)

const completedCount = computed(() =>
  props.contacts.filter(c => c.call_status === 'completed').length
)

const progressPercent = computed(() => {
  if (!props.contacts.length) return 0
  return Math.round((completedCount.value / props.contacts.length) * 100)
})

const statusForm = ref({
  call_status: current.value?.call_status ?? 'not_called',
  notes: current.value?.notes ?? '',
})

watch(current, (c) => {
  if (c) {
    statusForm.value.call_status = c.call_status
    statusForm.value.notes = c.notes ?? ''
  }
})

function statusDot(status) {
  const map = {
    not_called: 'bg-muted-foreground/40',
    called: 'bg-blue-500',
    no_answer: 'bg-amber-500',
    callback: 'bg-purple-500',
    completed: 'bg-green-500',
  }
  return map[status] ?? 'bg-muted-foreground/40'
}

function selectContact(id) {
  router.get(route('call-lists.work', props.callList.id), { contact_id: id }, {
    preserveState: true,
    replace: true,
  })
}

function navigate(direction) {
  const nextIndex = currentIndex.value + direction
  if (nextIndex < 0 || nextIndex >= props.contacts.length) return
  selectContact(props.contacts[nextIndex].id)
}

function saveStatus() {
  saving.value = true
  router.put(
    route('call-lists.update-contact-status', [props.callList.id, props.currentContactId]),
    statusForm.value,
    {
      preserveScroll: true,
      onFinish: () => { saving.value = false },
    }
  )
}

function saveAndNext() {
  saving.value = true
  router.put(
    route('call-lists.update-contact-status', [props.callList.id, props.currentContactId]),
    statusForm.value,
    {
      preserveScroll: true,
      onFinish: () => {
        saving.value = false
        const nextNotCalled = props.contacts.find(
          (c, i) => i > currentIndex.value && c.call_status === 'not_called'
        )
        if (nextNotCalled) {
          selectContact(nextNotCalled.id)
        } else if (currentIndex.value < props.contacts.length - 1) {
          navigate(1)
        }
      },
    }
  )
}
</script>
