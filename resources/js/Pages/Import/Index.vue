<template>
  <AppLayout title="Import">
    <Head title="Import" />

    <PageHeader title="Import" description="Restore content from a Lambda CMS export file">
      <template #actions>
        <a
          :href="route('export.index')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Go to Export
        </a>
      </template>
    </PageHeader>

    <div class="max-w-2xl space-y-6">

      <!-- Import results -->
      <Transition name="fade">
        <div v-if="results" class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-sm font-semibold">Import complete</h3>
          </div>
          <table class="w-full text-sm">
            <thead>
              <tr class="text-xs text-muted-foreground border-b">
                <th class="text-left pb-2 font-medium">Entity</th>
                <th class="text-right pb-2 font-medium">Created</th>
                <th class="text-right pb-2 font-medium">Updated</th>
                <th class="text-right pb-2 font-medium">Skipped</th>
                <th class="text-right pb-2 font-medium">Failed</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(counts, entity) in results"
                :key="entity"
                class="border-b last:border-0"
              >
                <td class="py-2 capitalize font-medium">{{ entity }}</td>
                <td class="py-2 text-right">
                  <span :class="counts.created > 0 ? 'text-green-600 font-medium' : 'text-muted-foreground'">
                    {{ counts.created }}
                  </span>
                </td>
                <td class="py-2 text-right">
                  <span :class="counts.updated > 0 ? 'text-blue-600 font-medium' : 'text-muted-foreground'">
                    {{ counts.updated }}
                  </span>
                </td>
                <td class="py-2 text-right">
                  <span class="text-muted-foreground">{{ counts.skipped }}</span>
                </td>
                <td class="py-2 text-right">
                  <span :class="counts.failed > 0 ? 'text-destructive font-medium' : 'text-muted-foreground'">
                    {{ counts.failed }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Transition>

      <!-- Step 1: Upload file (shown when no preview) -->
      <template v-if="!preview">
        <div class="rounded-lg border bg-card p-6">
          <h3 class="text-sm font-semibold mb-1">Upload export file</h3>
          <p class="text-xs text-muted-foreground mb-4">Select a <code class="font-mono bg-muted px-1 rounded">.zip</code> file exported from Lambda CMS.</p>

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
                accept=".zip"
                class="absolute inset-0 opacity-0 cursor-pointer"
                @change="onFileChange"
              />
              <svg class="w-8 h-8 text-muted-foreground mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
              </svg>
              <p class="text-sm font-medium" v-if="!uploadForm.file">
                Drop your ZIP file here, or <span class="text-primary">browse</span>
              </p>
              <p class="text-sm font-medium text-primary" v-else>
                {{ uploadForm.file.name }}
              </p>
              <p class="text-xs text-muted-foreground mt-1">Max 200 MB</p>
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
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
                </svg>
                {{ uploadForm.processing ? 'Uploading…' : 'Continue' }}
              </button>

              <!-- Upload progress -->
              <div v-if="uploadForm.processing && uploadForm.progress" class="flex-1">
                <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                  <div
                    class="h-full bg-primary rounded-full transition-all"
                    :style="{ width: uploadForm.progress.percentage + '%' }"
                  />
                </div>
                <p class="text-xs text-muted-foreground mt-1">{{ uploadForm.progress.percentage }}%</p>
              </div>
            </div>
          </form>
        </div>
      </template>

      <!-- Step 2: Configure and run import (shown after preview) -->
      <template v-else>
        <!-- Preview summary -->
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h3 class="text-sm font-semibold">File preview</h3>
              <p class="text-xs text-muted-foreground mt-0.5">
                Exported on {{ previewDate }}
                <span v-if="preview.include_media_files"> · Includes media files</span>
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
          <div class="flex flex-wrap gap-2">
            <span
              v-for="(count, entity) in preview.entities"
              :key="entity"
              class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium"
            >
              <span class="capitalize">{{ entity }}</span>
              <span class="rounded-full bg-muted px-1.5 py-0.5 tabular-nums">{{ count }}</span>
            </span>
          </div>
        </div>

        <!-- Import options form -->
        <form @submit.prevent="submitImport">
          <!-- Entity selection -->
          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Select content to import</h3>
            <p class="text-xs text-muted-foreground mb-4">Uncheck entities you want to skip.</p>
            <div class="space-y-3">
              <label
                v-for="entity in availableEntities"
                :key="entity"
                class="flex items-center gap-3 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="entity"
                  v-model="selectedEntities"
                  class="h-4 w-4 rounded border-border accent-primary"
                />
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium capitalize">{{ entity }}</span>
                  <span class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium tabular-nums">
                    {{ preview.entities[entity] }}
                  </span>
                </div>
              </label>
            </div>
          </div>

          <!-- Conflict strategy -->
          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Conflict strategy</h3>
            <p class="text-xs text-muted-foreground mb-4">What to do when a record with the same slug already exists.</p>
            <div class="space-y-3">
              <label
                v-for="strategy in conflictStrategies"
                :key="strategy.value"
                class="flex items-start gap-3 cursor-pointer"
              >
                <input
                  type="radio"
                  :value="strategy.value"
                  v-model="conflictStrategy"
                  class="mt-0.5 h-4 w-4 border-border accent-primary"
                />
                <div>
                  <span class="text-sm font-medium">{{ strategy.label }}</span>
                  <p class="text-xs text-muted-foreground mt-0.5">{{ strategy.description }}</p>
                </div>
              </label>
            </div>
          </div>

          <div v-if="importErrors.entities || importErrors.conflict_strategy" class="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-xs text-destructive mb-4">
            {{ importErrors.entities || importErrors.conflict_strategy }}
          </div>

          <div class="flex items-center gap-3">
            <button
              type="submit"
              :disabled="selectedEntities.length === 0 || importProcessing"
              class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="importProcessing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
              </svg>
              {{ importProcessing ? 'Importing…' : 'Run Import' }}
            </button>
          </div>
        </form>
      </template>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
  results: Object,
  preview: Object,
})

const isDragOver   = ref(false)
const fileInput    = ref(null)
const importErrors = ref({})

// ── Step 1: Upload form ───────────────────────────────────────────────────────

const uploadForm = useForm({ file: null })

function onFileChange(e) {
  uploadForm.file = e.target.files[0] ?? null
}

function onDrop(e) {
  isDragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file?.name.endsWith('.zip')) {
    uploadForm.file = file
  }
}

function submitUpload() {
  uploadForm.post(route('import.preview'), { forceFormData: true })
}

// ── Step 2: Import form ───────────────────────────────────────────────────────

const availableEntities  = computed(() => Object.keys(props.preview?.entities ?? {}))
const selectedEntities   = ref([])
const conflictStrategy   = ref('skip')
const importProcessing   = ref(false)

watch(availableEntities, (val) => {
  selectedEntities.value = val.slice()
}, { immediate: true })

const conflictStrategies = [
  { value: 'skip',      label: 'Skip',      description: "Leave existing records untouched. Only create records that don't exist yet." },
  { value: 'overwrite', label: 'Overwrite', description: 'Update existing records with the imported data.' },
  { value: 'duplicate', label: 'Duplicate', description: 'Always create new records, generating a unique slug when needed.' },
]

function submitImport() {
  importProcessing.value = true
  importErrors.value = {}
  router.post(
    route('import.store'),
    {
      tmp_path:          props.preview.tmp_path,
      entities:          selectedEntities.value,
      conflict_strategy: conflictStrategy.value,
    },
    {
      onFinish: () => { importProcessing.value = false },
      onError:  (errors) => { importErrors.value = errors },
    }
  )
}

function clearPreview() {
  router.get(route('import.index'))
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const previewDate = computed(() => {
  if (!props.preview?.exported_at) return 'unknown date'
  return new Date(props.preview.exported_at).toLocaleDateString(undefined, {
    year: 'numeric', month: 'long', day: 'numeric',
  })
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
