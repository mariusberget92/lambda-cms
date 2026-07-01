<template>
  <AppLayout title="CRM Import">
    <Head title="CRM Import" />

    <PageHeader title="CRM Import" description="Import CRM data from a CSV file">
      <template #actions>
        <a
          :href="route('crm-export.index')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Go to CRM Export
        </a>
      </template>
    </PageHeader>

    <div class="max-w-2xl space-y-6">

      <!-- Import results -->
      <Transition name="fade">
        <div v-if="results && !results.error" class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-sm font-semibold">Import complete</h3>
          </div>
          <div class="grid grid-cols-4 gap-4 text-center">
            <div>
              <p class="text-2xl font-bold" :class="results.created > 0 ? 'text-green-600' : 'text-muted-foreground'">{{ results.created }}</p>
              <p class="text-xs text-muted-foreground">Created</p>
            </div>
            <div>
              <p class="text-2xl font-bold" :class="results.updated > 0 ? 'text-blue-600' : 'text-muted-foreground'">{{ results.updated }}</p>
              <p class="text-xs text-muted-foreground">Updated</p>
            </div>
            <div>
              <p class="text-2xl font-bold text-muted-foreground">{{ results.skipped }}</p>
              <p class="text-xs text-muted-foreground">Skipped</p>
            </div>
            <div>
              <p class="text-2xl font-bold" :class="results.failed > 0 ? 'text-destructive' : 'text-muted-foreground'">{{ results.failed }}</p>
              <p class="text-xs text-muted-foreground">Failed</p>
            </div>
          </div>
        </div>
      </Transition>

      <div v-if="results?.error" class="rounded-md border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
        {{ results.error }}
      </div>

      <!-- Step 1: Upload file (shown when no preview) -->
      <template v-if="!preview">
        <div class="rounded-lg border bg-card p-6">
          <h3 class="text-sm font-semibold mb-1">Upload CSV file</h3>
          <p class="text-xs text-muted-foreground mb-4">Select a <code class="font-mono bg-muted px-1 rounded">.csv</code> file with your CRM data.</p>

          <form @submit.prevent="submitUpload">
            <div
              class="relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-border p-8 text-center transition-colors"
              :class="{ 'border-primary bg-primary/5': isDragOver }"
              @dragover.prevent="isDragOver = true"
              @dragleave.prevent="isDragOver = false"
              @drop.prevent="onDrop"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".csv"
                class="absolute inset-0 opacity-0 cursor-pointer"
                @change="onFileChange"
              />
              <svg class="w-8 h-8 text-muted-foreground mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
              </svg>
              <p class="text-sm font-medium" v-if="!uploadForm.file">
                Drop your CSV file here, or <span class="text-primary">browse</span>
              </p>
              <p class="text-sm font-medium text-primary" v-else>
                {{ uploadForm.file.name }}
              </p>
              <p class="text-xs text-muted-foreground mt-1">Max 20 MB</p>
            </div>

            <div v-if="uploadForm.errors.file" class="mt-2 text-xs text-destructive">
              {{ uploadForm.errors.file }}
            </div>

            <div class="mt-4 flex items-center gap-3">
              <button
                type="submit"
                :disabled="!uploadForm.file || uploadForm.processing"
                class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="uploadForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ uploadForm.processing ? 'Uploading...' : 'Continue' }}
              </button>
            </div>
          </form>
        </div>
      </template>

      <!-- Step 2: Configure and run import -->
      <template v-else>
        <!-- Preview summary -->
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h3 class="text-sm font-semibold">File preview</h3>
              <p class="text-xs text-muted-foreground mt-0.5">
                {{ preview.total_rows }} row{{ preview.total_rows !== 1 ? 's' : '' }} found · {{ preview.headers.length }} column{{ preview.headers.length !== 1 ? 's' : '' }}
              </p>
            </div>
            <button
              type="button"
              @click="clearPreview"
              class="text-xs text-muted-foreground hover:text-foreground transition-colors shrink-0"
            >
              Choose different file
            </button>
          </div>

          <!-- Sample data table -->
          <div class="overflow-x-auto -mx-6 px-6">
            <table class="w-full text-xs">
              <thead>
                <tr class="border-b">
                  <th v-for="(header, i) in preview.headers" :key="i" class="text-left pb-2 pr-4 font-medium text-muted-foreground whitespace-nowrap">
                    {{ header }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, ri) in preview.sample_rows" :key="ri" class="border-b last:border-0">
                  <td v-for="(cell, ci) in row" :key="ci" class="py-1.5 pr-4 whitespace-nowrap truncate max-w-[200px]">
                    {{ cell || '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <form @submit.prevent="submitImport">
          <!-- Entity type -->
          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Import as</h3>
            <p class="text-xs text-muted-foreground mb-4">What type of data does this CSV contain?</p>
            <div class="space-y-3">
              <label v-for="opt in entityOptions" :key="opt.value" class="flex items-start gap-3 cursor-pointer">
                <input type="radio" :value="opt.value" v-model="entity" class="mt-0.5" />
                <div>
                  <span class="text-sm font-medium">{{ opt.label }}</span>
                  <p class="text-xs text-muted-foreground mt-0.5">{{ opt.description }}</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Column mapping -->
          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Column mapping</h3>
            <p class="text-xs text-muted-foreground mb-4">Map each CSV column to a field, or skip it.</p>
            <div class="space-y-3">
              <div v-for="(header, i) in preview.headers" :key="i" class="flex items-center gap-3">
                <span class="text-sm font-mono text-muted-foreground w-40 truncate shrink-0" :title="header">{{ header }}</span>
                <svg class="w-4 h-4 text-muted-foreground shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
                <SelectBox
                  :model-value="columnMap[i] ?? 'skip'"
                  :data="fieldOptions"
                  @update:model-value="v => columnMap[i] = v"
                />
              </div>
            </div>
          </div>

          <!-- Conflict strategy -->
          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Conflict strategy</h3>
            <p class="text-xs text-muted-foreground mb-4">What to do when a matching record already exists.</p>
            <div class="space-y-3">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" value="skip" v-model="conflictStrategy" class="mt-0.5" />
                <div>
                  <span class="text-sm font-medium">Skip</span>
                  <p class="text-xs text-muted-foreground mt-0.5">Leave existing records untouched. Only create new records.</p>
                </div>
              </label>
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" value="overwrite" v-model="conflictStrategy" class="mt-0.5" />
                <div>
                  <span class="text-sm font-medium">Overwrite</span>
                  <p class="text-xs text-muted-foreground mt-0.5">Update existing records with the imported data.</p>
                </div>
              </label>
            </div>
          </div>

          <div v-if="Object.keys(importErrors).length > 0" class="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive mb-4">
            {{ Object.values(importErrors).flat().join(', ') }}
          </div>

          <div class="flex items-center gap-3">
            <button
              type="submit"
              :disabled="!entity || importProcessing"
              class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="importProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ importProcessing ? 'Importing...' : 'Run Import' }}
            </button>
          </div>
        </form>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  preview: Object,
  results: Object,
})

const isDragOver = ref(false)
const importErrors = ref({})

// ── Step 1: Upload ──────────────────────────────────────────────────────────────

const uploadForm = useForm({ file: null })

function onFileChange(e) {
  uploadForm.file = e.target.files[0] ?? null
}

function onDrop(e) {
  isDragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file?.name.endsWith('.csv')) {
    uploadForm.file = file
  }
}

function submitUpload() {
  uploadForm.post(route('crm-import.preview'), { forceFormData: true })
}

// ── Step 2: Configure ───────────────────────────────────────────────────────────

const entity = ref('contacts')
const conflictStrategy = ref('skip')
const importProcessing = ref(false)

const columnMap = reactive({})

const contactFields = [
  { value: 'skip',         label: '— Skip —' },
  { value: 'first_name',   label: 'First Name' },
  { value: 'last_name',    label: 'Last Name' },
  { value: 'email',        label: 'Email' },
  { value: 'phone',        label: 'Phone' },
  { value: 'position',     label: 'Position' },
  { value: 'company_name', label: 'Company Name' },
  { value: 'status',       label: 'Status' },
  { value: 'notes',        label: 'Notes' },
]

const companyFields = [
  { value: 'skip',    label: '— Skip —' },
  { value: 'name',    label: 'Name' },
  { value: 'domain',  label: 'Domain' },
  { value: 'phone',   label: 'Phone' },
  { value: 'address', label: 'Address' },
  { value: 'notes',   label: 'Notes' },
]

const fieldOptions = computed(() =>
  entity.value === 'companies' ? companyFields : contactFields
)

const entityOptions = [
  { value: 'contacts',  label: 'Contacts',  description: 'Import rows as contacts. Matches by email for conflict detection.' },
  { value: 'companies', label: 'Companies', description: 'Import rows as companies. Matches by name for conflict detection.' },
]

// Auto-map columns by header name
watch(() => props.preview, (p) => {
  if (!p?.headers) return
  const autoMap = {
    first_name: 'first_name', firstname: 'first_name', 'first name': 'first_name',
    last_name: 'last_name', lastname: 'last_name', 'last name': 'last_name',
    email: 'email', 'e-mail': 'email',
    phone: 'phone', telephone: 'phone',
    position: 'position', title: 'position', 'job title': 'position',
    company: 'company_name', company_name: 'company_name', 'company name': 'company_name',
    status: 'status',
    notes: 'notes', note: 'notes',
    name: 'name',
    domain: 'domain', website: 'domain',
    address: 'address',
  }
  p.headers.forEach((header, i) => {
    const key = header.toLowerCase().trim()
    columnMap[i] = autoMap[key] ?? 'skip'
  })
}, { immediate: true })

function submitImport() {
  importProcessing.value = true
  importErrors.value = {}
  router.post(
    route('crm-import.store'),
    {
      entity: entity.value,
      tmp_path: props.preview.tmp_path,
      column_map: { ...columnMap },
      conflict_strategy: conflictStrategy.value,
    },
    {
      onFinish: () => { importProcessing.value = false },
      onError: (errors) => { importErrors.value = errors },
    }
  )
}

function clearPreview() {
  router.get(route('crm-import.index'))
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
