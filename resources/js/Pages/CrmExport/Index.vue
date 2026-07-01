<template>
  <AppLayout title="CRM Export">
    <Head title="CRM Export" />

    <PageHeader title="CRM Export" description="Download CRM data as CSV">
      <template #actions>
        <a
          :href="route('crm-import.index')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
          </svg>
          Go to CRM Import
        </a>
      </template>
    </PageHeader>

    <div class="max-w-2xl space-y-6">
      <div class="rounded-lg border bg-card p-6">
        <h3 class="text-sm font-semibold mb-1">Select data to export</h3>
        <p class="text-xs text-muted-foreground mb-4">Choose which CRM data to download as a CSV file.</p>
        <div class="space-y-3">
          <label
            v-for="entity in entityOptions"
            :key="entity.value"
            class="flex items-start gap-3 cursor-pointer"
            :class="{ 'opacity-40 cursor-default': entity.disabled }"
          >
            <input
              type="radio"
              :value="entity.value"
              v-model="selectedEntity"
              :disabled="entity.disabled"
              class="mt-0.5"
            />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium">{{ entity.label }}</span>
                <span v-if="entity.count !== null" class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium tabular-nums">
                  {{ entity.count }}
                </span>
              </div>
              <p class="text-xs text-muted-foreground mt-0.5">{{ entity.description }}</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Info note -->
      <div class="rounded-lg border border-border bg-muted/30 p-4 text-xs text-muted-foreground space-y-1">
        <p class="font-medium text-foreground">About the export format</p>
        <p>Each export creates a standard CSV file that can be opened in Excel, Google Sheets, or any spreadsheet application.
          Call list exports include one row per contact with the list name, contact details, and call status.</p>
      </div>

      <div class="flex items-center gap-3">
        <button
          type="button"
          :disabled="!selectedEntity"
          @click="startExport"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Download CSV
        </button>
        <span v-if="!selectedEntity" class="text-xs text-muted-foreground">Select a data type to export.</span>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
  counts: Object,
})

const page = usePage()
const perms = computed(() => page.props.auth?.user?.permissions ?? [])

const selectedEntity = ref('')

const entityOptions = computed(() => [
  {
    value: 'contacts',
    label: 'Contacts',
    description: 'Export all contacts with name, email, phone, company, and status.',
    count: props.counts.contacts,
    disabled: props.counts.contacts === null,
  },
  {
    value: 'companies',
    label: 'Companies',
    description: 'Export all companies with name, domain, phone, and address.',
    count: props.counts.companies,
    disabled: props.counts.companies === null,
  },
  {
    value: 'call_lists',
    label: 'Call Lists',
    description: 'Export call lists with contact details and call statuses (one row per contact).',
    count: props.counts.call_lists,
    disabled: props.counts.call_lists === null,
  },
])

function startExport() {
  if (!selectedEntity.value) return

  const url = new URL(route('crm-export.download'), window.location.origin)
  url.searchParams.set('entity', selectedEntity.value)
  window.location.href = url.toString()
}
</script>
