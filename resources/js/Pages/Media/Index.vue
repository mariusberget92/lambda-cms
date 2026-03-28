<template>
  <AppLayout title="Media Library">
    <Head title="Media Library" />

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <SelectBox
          :model-value="filters.type"
          :data="[
            { value: '',         label: 'All types' },
            { value: 'image',    label: 'Images' },
            { value: 'document', label: 'Documents' },
            { value: 'video',    label: 'Video' },
            { value: 'audio',    label: 'Audio' },
          ]"
          placeholder="All types"
          @update:model-value="onTypeChange"
        />

        <input
          v-model="filters.search"
          type="text"
          placeholder="Search files..."
          class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring w-48"
          @input="debouncedSearch"
        />
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="selected.length"
          type="button"
          class="rounded-md border border-destructive/50 px-3 py-2 text-sm text-destructive hover:bg-destructive hover:text-destructive-foreground transition-colors"
          @click="confirmBulkDelete"
        >
          Delete {{ selected.length }} selected
        </button>

        <label class="cursor-pointer rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors">
          <svg class="w-4 h-4 inline-block -mt-0.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload
          <input type="file" class="hidden" multiple @change="onFileInput" />
        </label>
      </div>
    </div>

    <!-- Main area: grid + detail panel -->
    <div class="flex gap-6">
      <!-- Drop zone / Grid -->
      <div
        ref="dropZoneRef"
        class="flex-1 min-h-[60vh] rounded-lg transition-colors"
        :class="isOverDropZone ? 'bg-primary/5 ring-2 ring-primary ring-inset' : ''"
      >
        <!-- Upload progress -->
        <div v-if="uploading" class="mb-4 rounded-lg border bg-card p-4 flex items-center gap-4">
          <span class="text-sm text-muted-foreground shrink-0">Uploading {{ uploadingName }}...</span>
          <div class="flex-1 bg-muted rounded-full h-2">
            <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
          </div>
          <span class="text-xs text-muted-foreground shrink-0">{{ uploadProgress }}%</span>
        </div>

        <!-- Empty state -->
        <div v-if="!localItems.length && !uploading" class="flex flex-col items-center justify-center py-24 text-muted-foreground">
          <svg class="w-10 h-10 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          <p class="text-sm">Drop files here or click Upload to add media.</p>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-3">
          <div
            v-for="item in localItems"
            :key="item.id"
            class="relative group aspect-square rounded-md overflow-hidden border bg-muted cursor-pointer"
            :class="[
              selected.includes(item.id) ? 'ring-2 ring-primary border-primary' : 'hover:border-foreground/30',
              activeItem?.id === item.id ? 'ring-2 ring-primary border-primary' : '',
            ]"
            @click="openDetail(item)"
          >
            <img
              v-if="item.type === 'image'"
              :src="item.url"
              :alt="item.alt ?? item.original_filename"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 p-2">
              <svg class="w-7 h-7 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span class="text-xs text-muted-foreground text-center leading-tight line-clamp-2">{{ item.original_filename }}</span>
            </div>

            <!-- Hover info -->
            <div class="absolute bottom-0 left-0 right-0 bg-background/80 backdrop-blur-sm p-1.5 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
              <p class="truncate text-foreground font-medium leading-tight">{{ item.original_filename }}</p>
              <p class="text-muted-foreground">{{ item.formatted_size }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail panel -->
      <div
        v-if="activeItem"
        class="w-72 shrink-0 rounded-lg border bg-card flex flex-col self-start sticky top-6"
      >
        <!-- Preview -->
        <div class="aspect-video w-full bg-muted rounded-t-lg overflow-hidden flex items-center justify-center">
          <img
            v-if="activeItem.type === 'image'"
            :src="activeItem.url"
            :alt="activeItem.alt ?? activeItem.original_filename"
            class="w-full h-full object-contain"
          />
          <svg v-else class="w-10 h-10 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>

        <div class="p-4 flex flex-col gap-4">
          <!-- Filename + meta -->
          <div>
            <p class="text-sm font-medium break-all leading-tight">{{ activeItem.original_filename }}</p>
            <p class="text-xs text-muted-foreground mt-0.5">
              {{ activeItem.formatted_size }}
              <template v-if="activeItem.width && activeItem.height"> · {{ activeItem.width }}×{{ activeItem.height }}</template>
            </p>
            <p class="text-xs text-muted-foreground">{{ activeItem.created_at }}</p>
            <p v-if="activeItem.uploader" class="text-xs text-muted-foreground">Uploaded by {{ activeItem.uploader }}</p>
          </div>

          <!-- Alt text -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-foreground">Alt text</label>
            <input
              v-model="detailForm.alt"
              type="text"
              placeholder="Describe this image..."
              class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>

          <!-- Description -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-foreground">Description</label>
            <textarea
              v-model="detailForm.description"
              rows="3"
              placeholder="Optional longer description..."
              class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
            />
          </div>

          <!-- Copy URL -->
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-foreground">URL</label>
            <div class="flex gap-1">
              <input
                :value="activeItem.url"
                type="text"
                readonly
                class="flex-1 rounded-md border bg-muted px-3 py-2 text-xs text-muted-foreground truncate"
              />
              <button
                type="button"
                class="shrink-0 rounded-md border px-2 py-2 text-xs hover:bg-accent transition-colors"
                @click="copyUrl"
                :title="copied ? 'Copied!' : 'Copy URL'"
              >
                <svg v-if="!copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <svg v-else class="w-3.5 h-3.5 text-status-success-fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-2 pt-1">
            <button
              type="button"
              class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
              :disabled="saving"
              @click="saveDetail"
            >
              {{ saving ? 'Saving...' : 'Save changes' }}
            </button>
            <button
              type="button"
              class="w-full rounded-md border border-destructive/50 px-4 py-2 text-sm text-destructive hover:bg-destructive hover:text-destructive-foreground transition-colors"
              @click="confirmSingleDelete"
            >
              Delete file
            </button>
            <button
              type="button"
              class="w-full rounded-md border px-4 py-2 text-sm text-muted-foreground hover:bg-accent transition-colors"
              @click="closeDetail"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="media.links?.length > 3" class="flex items-center justify-center gap-1 mt-8">
      <template v-for="link in media.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          class="px-3 py-1.5 text-sm rounded-md border transition-colors"
          :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'"
        >
          {{ decodeHtmlEntities(link.label) }}
        </Link>
        <span v-else class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed">
          {{ decodeHtmlEntities(link.label) }}
        </span>
      </template>
    </div>

    <!-- Bulk delete confirmation -->
    <Dialog v-model:open="showBulkConfirm">
      <DialogContent class="max-w-sm">
        <div class="p-6">
          <h3 class="text-base font-semibold mb-2">Delete {{ selected.length }} files?</h3>
          <p class="text-sm text-muted-foreground mb-6">This action cannot be undone.</p>
          <div class="flex justify-end gap-2">
            <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent" @click="showBulkConfirm = false">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm text-destructive-foreground hover:bg-destructive/90" @click="doBulkDelete">Delete</button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Single delete confirmation -->
    <Dialog v-model:open="showSingleConfirm">
      <DialogContent class="max-w-sm">
        <div class="p-6">
          <h3 class="text-base font-semibold mb-2">Delete this file?</h3>
          <p class="text-sm text-muted-foreground mb-1 break-all">{{ activeItem?.original_filename }}</p>
          <p class="text-sm text-muted-foreground mb-6">This action cannot be undone.</p>
          <div class="flex justify-end gap-2">
            <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent" @click="showSingleConfirm = false">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm text-destructive-foreground hover:bg-destructive/90" @click="doSingleDelete">Delete</button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useDropZone } from '@vueuse/core'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Dialog, DialogContent } from '@/components/ui/dialog'
import SelectBox from '@/Components/SelectBox.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

const props = defineProps({
  media:   Object,
  filters: { type: Object, default: () => ({}) },
})

const localItems      = ref([...props.media.data])
const selected        = ref([])
const uploading       = ref(false)
const uploadProgress  = ref(0)
const uploadingName   = ref('')
const showBulkConfirm = ref(false)
const showSingleConfirm = ref(false)
const dropZoneRef     = ref(null)

// Detail panel
const activeItem  = ref(null)
const detailForm  = ref({ alt: '', description: '' })
const saving      = ref(false)
const copied      = ref(false)

const filters = ref({
  type:   props.filters.type   ?? '',
  search: props.filters.search ?? '',
})

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files) => uploadFiles(files),
})

function onTypeChange(v) {
  filters.value.type = v
  applyFilters()
}

function applyFilters() {
  router.get(route('media.index'), filters.value, { preserveState: true, replace: true })
}

let searchTimer = null
function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 300)
}

function openDetail(item) {
  activeItem.value  = item
  detailForm.value  = { alt: item.alt ?? '', description: item.description ?? '' }
  copied.value      = false
}

function closeDetail() {
  activeItem.value = null
}

async function saveDetail() {
  if (!activeItem.value) return
  saving.value = true
  try {
    const { data } = await axios.patch(route('media.update', activeItem.value.id), detailForm.value)
    const idx = localItems.value.findIndex(i => i.id === data.id)
    if (idx !== -1) localItems.value[idx] = data
    activeItem.value = data
  } catch (err) {
    alert(err.response?.data?.message ?? 'Save failed.')
  } finally {
    saving.value = false
  }
}

function copyUrl() {
  if (!activeItem.value) return
  navigator.clipboard.writeText(activeItem.value.url).then(() => {
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  })
}

function confirmSingleDelete() {
  showSingleConfirm.value = true
}

async function doSingleDelete() {
  if (!activeItem.value) return
  try {
    await axios.delete(route('media.destroy', activeItem.value.id))
    localItems.value = localItems.value.filter(i => i.id !== activeItem.value.id)
    closeDetail()
    showSingleConfirm.value = false
  } catch (err) {
    alert('Delete failed.')
  }
}

function onFileInput(event) {
  uploadFiles(Array.from(event.target.files))
  event.target.value = ''
}

async function uploadFiles(files) {
  if (!files?.length) return

  for (const file of files) {
    uploading.value      = true
    uploadProgress.value = 0
    uploadingName.value  = file.name

    const formData = new FormData()
    formData.append('file', file)

    try {
      const { data } = await axios.post(route('media.store'), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json',
        },
        onUploadProgress: (e) => {
          uploadProgress.value = e.total ? Math.round((e.loaded * 100) / e.total) : 0
        },
      })
      localItems.value.unshift(data)
    } catch (err) {
      alert(err.response?.data?.message ?? 'Upload failed. Check file type and size.')
    } finally {
      uploading.value = false
    }
  }
}

function confirmBulkDelete() {
  showBulkConfirm.value = true
}

async function doBulkDelete() {
  try {
    await axios.delete(route('media.bulk-destroy'), {
      data: { ids: selected.value },
    })
    localItems.value = localItems.value.filter(i => !selected.value.includes(i.id))
    if (activeItem.value && selected.value.includes(activeItem.value.id)) {
      closeDetail()
    }
    selected.value = []
    showBulkConfirm.value = false
  } catch (err) {
    alert('Bulk delete failed.')
  }
}
</script>
