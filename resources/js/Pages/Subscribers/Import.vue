<template>
  <AppLayout title="Import Subscribers">
    <Head title="Import Subscribers" />

    <PageHeader title="Import Subscribers" description="Import subscribers from a CSV file">
      <template #actions>
        <a
          :href="route('subscribers.index')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          Back to Subscribers
        </a>
      </template>
    </PageHeader>

    <div class="max-w-2xl space-y-6">

      <!-- Import results -->
      <Transition name="fade">
        <div v-if="results && !results.error" class="rounded-lg border bg-card p-6">
          <div class="flex items-center gap-2 mb-4">
            <CheckCircle class="w-5 h-5 text-green-500" />
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

      <!-- Step 1: Upload -->
      <template v-if="!preview">
        <div class="rounded-lg border bg-card p-6">
          <h3 class="text-sm font-semibold mb-1">Upload CSV file</h3>
          <p class="text-xs text-muted-foreground mb-4">The file should contain at minimum an <code class="font-mono bg-muted px-1 rounded">email</code> column.</p>

          <form @submit.prevent="submitUpload">
            <div
              class="relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-border p-8 text-center transition-colors"
              :class="{ 'border-primary bg-primary/5': isDragOver }"
              @dragover.prevent="isDragOver = true"
              @dragleave.prevent="isDragOver = false"
              @drop.prevent="onDrop"
            >
              <input ref="fileInput" type="file" accept=".csv" class="absolute inset-0 opacity-0 cursor-pointer" @change="onFileChange" />
              <Upload class="w-8 h-8 text-muted-foreground mb-3" />
              <p class="text-sm font-medium" v-if="!uploadForm.file">
                Drop your CSV file here, or <span class="text-primary">browse</span>
              </p>
              <p class="text-sm font-medium text-primary" v-else>{{ uploadForm.file.name }}</p>
              <p class="text-xs text-muted-foreground mt-1">Max 20 MB</p>
            </div>

            <div v-if="uploadForm.errors.file" class="mt-2 text-xs text-destructive">{{ uploadForm.errors.file }}</div>

            <div class="mt-4">
              <button
                type="submit"
                :disabled="!uploadForm.file || uploadForm.processing"
                class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ uploadForm.processing ? 'Uploading...' : 'Continue' }}
              </button>
            </div>
          </form>
        </div>
      </template>

      <!-- Step 2: Configure -->
      <template v-else>
        <div class="rounded-lg border bg-card p-6">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h3 class="text-sm font-semibold">File preview</h3>
              <p class="text-xs text-muted-foreground mt-0.5">
                {{ preview.total_rows }} row{{ preview.total_rows !== 1 ? 's' : '' }} · {{ preview.headers.length }} column{{ preview.headers.length !== 1 ? 's' : '' }}
              </p>
            </div>
            <button type="button" @click="clearPreview" class="text-xs text-muted-foreground hover:text-foreground transition-colors shrink-0">
              Choose different file
            </button>
          </div>
          <div class="overflow-x-auto -mx-6 px-6">
            <table class="w-full text-xs">
              <thead>
                <tr class="border-b">
                  <th v-for="(h, i) in preview.headers" :key="i" class="text-left pb-2 pr-4 font-medium text-muted-foreground whitespace-nowrap">{{ h }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, ri) in preview.sample_rows" :key="ri" class="border-b last:border-0">
                  <td v-for="(cell, ci) in row" :key="ci" class="py-1.5 pr-4 whitespace-nowrap truncate max-w-[200px]">{{ cell || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <form @submit.prevent="submitImport">
          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Column mapping</h3>
            <p class="text-xs text-muted-foreground mb-4">Map each CSV column to a subscriber field.</p>
            <div class="space-y-3">
              <div v-for="(header, i) in preview.headers" :key="i" class="flex items-center gap-3">
                <span class="text-sm font-mono text-muted-foreground w-40 truncate shrink-0" :title="header">{{ header }}</span>
                <ArrowRight class="w-4 h-4 text-muted-foreground shrink-0" />
                <SelectBox
                  :model-value="columnMap[i] ?? 'skip'"
                  :data="fieldOptions"
                  @update:model-value="v => columnMap[i] = v"
                />
              </div>
            </div>
          </div>

          <div class="rounded-lg border bg-card p-6 mb-6">
            <h3 class="text-sm font-semibold mb-1">Conflict strategy</h3>
            <p class="text-xs text-muted-foreground mb-4">What to do when an email already exists.</p>
            <div class="space-y-3">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" value="skip" v-model="conflictStrategy" class="mt-0.5" />
                <div>
                  <span class="text-sm font-medium">Skip</span>
                  <p class="text-xs text-muted-foreground mt-0.5">Leave existing subscribers untouched.</p>
                </div>
              </label>
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" value="overwrite" v-model="conflictStrategy" class="mt-0.5" />
                <div>
                  <span class="text-sm font-medium">Overwrite</span>
                  <p class="text-xs text-muted-foreground mt-0.5">Update existing subscribers with imported data.</p>
                </div>
              </label>
            </div>
          </div>

          <button
            type="submit"
            :disabled="importProcessing"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ importProcessing ? 'Importing...' : 'Run Import' }}
          </button>
        </form>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SelectBox from '@/Components/SelectBox.vue'
import { Upload, CheckCircle, ArrowRight } from '@lucide/vue'

const props = defineProps({
  preview: Object,
  results: Object,
})

const isDragOver = ref(false)
const importProcessing = ref(false)
const conflictStrategy = ref('skip')
const columnMap = reactive({})

const uploadForm = useForm({ file: null })

const fieldOptions = [
  { value: 'skip',  label: '— Skip —' },
  { value: 'email', label: 'Email' },
  { value: 'name',  label: 'Name' },
]

function onFileChange(e) { uploadForm.file = e.target.files[0] ?? null }
function onDrop(e) {
  isDragOver.value = false
  const file = e.dataTransfer.files[0]
  if (file?.name.endsWith('.csv')) uploadForm.file = file
}

function submitUpload() {
  uploadForm.post(route('subscribers.import.preview'), { forceFormData: true })
}

watch(() => props.preview, (p) => {
  if (!p?.headers) return
  const autoMap = { email: 'email', 'e-mail': 'email', name: 'name', 'full name': 'name', fullname: 'name' }
  p.headers.forEach((h, i) => {
    columnMap[i] = autoMap[h.toLowerCase().trim()] ?? 'skip'
  })
}, { immediate: true })

function submitImport() {
  importProcessing.value = true
  router.post(route('subscribers.import.store'), {
    tmp_path: props.preview.tmp_path,
    column_map: { ...columnMap },
    conflict_strategy: conflictStrategy.value,
  }, {
    onFinish: () => { importProcessing.value = false },
  })
}

function clearPreview() {
  router.get(route('subscribers.import'))
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
