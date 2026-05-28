<template>
  <AppLayout title="Media Library">
    <Head title="Media Library" />

    <PageHeader title="Media" description="Upload and manage media files">
      <template #actions>
        <label class="cursor-pointer rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors">
          <svg class="w-4 h-4 inline-block -mt-0.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload
          <input type="file" class="hidden" multiple @change="onFileInput" />
        </label>
      </template>
    </PageHeader>

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

        <button
          v-if="localItems.length"
          type="button"
          class="rounded-md border px-3 py-2 text-sm text-muted-foreground hover:bg-accent transition-colors"
          @click="toggleSelectAll"
        >
          {{ allSelected ? 'Deselect all' : 'Select all' }}
        </button>
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

        <!-- Upload guidance -->
        <p v-show="!uploading" class="text-xs text-muted-foreground mb-3">
          Accepted: {{ allowedExtensions }} · Max {{ maxUploadMb }} MB
        </p>

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
            <!-- Selection checkbox -->
            <div
              class="absolute top-1.5 left-1.5 z-10 transition-opacity"
              :class="selected.includes(item.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
              @click.stop="toggleSelect(item.id, $event)"
            >
              <div
                class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                :class="selected.includes(item.id)
                  ? 'bg-primary border-primary'
                  : 'bg-background/80 border-foreground/40 backdrop-blur-sm'"
              >
                <svg v-if="selected.includes(item.id)" class="w-3 h-3 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>

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
              <span
                v-if="fileTypeBadge(item.mime_type, item.original_filename)"
                class="text-[9px] font-bold tracking-wide px-1.5 py-0.5 rounded bg-muted-foreground/15 text-muted-foreground uppercase"
              >
                {{ fileTypeBadge(item.mime_type, item.original_filename) }}
              </span>
            </div>

            <!-- Hover info -->
            <div class="absolute bottom-0 left-0 right-0 bg-background/80 backdrop-blur-sm p-1.5 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
              <p class="truncate text-foreground font-medium leading-tight">{{ item.original_filename }}</p>
              <p class="text-muted-foreground">{{ item.formatted_size }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail panel — desktop sidebar (md+) -->
      <div
        v-if="activeItem"
        class="hidden md:flex w-72 shrink-0 rounded-lg border bg-card flex-col self-start sticky top-6"
      >
        <MediaDetailContent
          :active-item="activeItem"
          :detail-form="detailForm"
          :used-in="usedIn"
          :used-in-loading="usedInLoading"
          :copied="copied"
          :saving="saving"
          @copy="copyUrl"
          @save="saveDetail"
          @delete="confirmSingleDelete"
          @close="closeDetail"
          @edit="openEditorForExisting"
          @lightbox="openLightbox"
          @update:alt="detailForm.alt = $event"
          @update:description="detailForm.description = $event"
        />
      </div>
    </div>

    <!-- Detail panel — bottom sheet on mobile -->
    <Teleport to="body">
      <!-- Backdrop -->
      <div
        v-if="activeItem"
        class="fixed inset-0 z-[39] bg-black/40 md:hidden"
        @click="closeDetail"
      />
      <Transition name="sheet">
        <div
          v-if="activeItem"
          class="fixed inset-x-0 bottom-0 z-40 md:hidden rounded-t-2xl border-t bg-card shadow-2xl max-h-[85vh] overflow-y-auto"
        >
          <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 rounded-full bg-muted-foreground/30" />
          </div>
          <button
            type="button"
            class="absolute top-3 right-4 p-2 text-muted-foreground hover:text-foreground"
            @click="closeDetail"
            aria-label="Close"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
          <MediaDetailContent
            :active-item="activeItem"
            :detail-form="detailForm"
            :used-in="usedIn"
            :used-in-loading="usedInLoading"
            :copied="copied"
            :saving="saving"
            @copy="copyUrl"
            @save="saveDetail"
            @delete="confirmSingleDelete"
            @close="closeDetail"
            @lightbox="openLightbox"
            @update:alt="detailForm.alt = $event"
            @update:description="detailForm.description = $event"
          />
        </div>
      </Transition>
    </Teleport>

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

          <!-- Used-in warning -->
          <div v-if="usedIn?.length" class="rounded-md border border-destructive/30 bg-destructive/5 p-3 mb-4 mt-3">
            <p class="text-xs font-medium text-destructive mb-1.5">
              This file is used as the featured image in {{ usedIn.length }} post{{ usedIn.length !== 1 ? 's' : '' }}:
            </p>
            <ul class="space-y-0.5">
              <li v-for="post in usedIn" :key="post.id" class="text-xs text-muted-foreground truncate">
                · {{ post.title }}
              </li>
            </ul>
            <p class="text-xs text-muted-foreground mt-1.5">Deleting will remove the featured image from those posts.</p>
          </div>

          <p v-else class="text-sm text-muted-foreground mb-6">This action cannot be undone.</p>

          <div class="flex justify-end gap-2">
            <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent" @click="showSingleConfirm = false">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm text-destructive-foreground hover:bg-destructive/90" @click="doSingleDelete">Delete anyway</button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
    <MediaLightbox v-model="lightboxIndex" :images="lightboxImages" />

    <!-- Image editor -->
    <ImageEditor
      v-model="editorOpen"
      :src="editorSrc"
      :original-filename="editorFilename"
      :allow-skip="editorAllowSkip"
      @apply="onEditorApply"
      @skip="onEditorSkip"
      @cancel="onEditorCancel"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useDropZone } from '@vueuse/core'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { Dialog, DialogContent } from '@/components/ui/dialog'
import SelectBox from '@/Components/SelectBox.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'
import { useNotifications } from '@/composables/useNotifications'
import MediaLightbox from './MediaLightbox.vue'
import MediaDetailContent from './MediaDetailContent.vue'
import ImageEditor from '@/components/ImageEditor.vue'

const { notify } = useNotifications()

const props = defineProps({
  media:             Object,
  filters:           { type: Object, default: () => ({}) },
  maxUploadMb:       { type: Number, default: 10 },
  allowedExtensions: { type: String, default: 'jpg, png, gif, webp, svg, pdf' },
})

const localItems      = ref([...props.media.data])
const selected        = ref([])

function toggleSelect(id, event) {
  event.stopPropagation()
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}

const allSelected = computed(() => localItems.value.length > 0 && localItems.value.every(i => selected.value.includes(i.id)))

function toggleSelectAll() {
  if (allSelected.value) selected.value = []
  else selected.value = localItems.value.map(i => i.id)
}
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
const usedIn        = ref(null)   // null = not loaded, [] = empty, [...] = posts
const usedInLoading = ref(false)
const lightboxIndex = ref(null)

const lightboxImages = computed(() =>
  localItems.value.filter(i => i.type === 'image')
)

function openLightbox(item) {
  const idx = lightboxImages.value.findIndex(i => i.id === item.id)
  if (idx !== -1) lightboxIndex.value = idx
}

let usageFetchId = 0

async function fetchUsage(mediaId) {
  const myId = ++usageFetchId
  usedIn.value        = null
  usedInLoading.value = true
  try {
    const { data } = await axios.get(route('media.usage', mediaId))
    if (myId !== usageFetchId) return
    usedIn.value = data.posts
  } catch {
    if (myId !== usageFetchId) return
    usedIn.value = []
  } finally {
    if (myId === usageFetchId) usedInLoading.value = false
  }
}

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
  fetchUsage(item.id)
}

function closeDetail() {
  activeItem.value = null
  usedIn.value     = null
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
    notify(err.response?.data?.message ?? 'Save failed.', 'error')
  } finally {
    saving.value = false
  }
}

function copyUrl() {
  if (!activeItem.value) return
  navigator.clipboard.writeText(activeItem.value.url).then(() => {
    copied.value = true
    notify('URL copied', 'success')
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
    notify('Delete failed.', 'error')
  }
}

// ── Image editor ─────────────────────────────────────────────────────────────
const EDITABLE_MIME_SET = new Set(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])

const editorOpen       = ref(false)
const editorSrc        = ref('')
const editorFilename   = ref('')
const editorAllowSkip  = ref(false)
const editingMediaItem = ref(null)   // set when editing an existing media item
const uploadQueue      = ref([])     // pending File objects waiting for editor
let   queueObjUrl      = null        // current object URL to revoke after use

function onFileInput(event) {
  uploadFiles(Array.from(event.target.files))
  event.target.value = ''
}

async function uploadFiles(files) {
  if (!files?.length) return
  const maxBytes = props.maxUploadMb * 1024 * 1024
  const toQueue  = []

  for (const file of files) {
    if (file.size > maxBytes) {
      notify(`"${file.name}" exceeds the ${props.maxUploadMb} MB limit.`, 'error')
      continue
    }
    if (EDITABLE_MIME_SET.has(file.type)) {
      toQueue.push(file)
    } else {
      await doUpload(file)
    }
  }

  if (toQueue.length) {
    uploadQueue.value.push(...toQueue)
    if (!editorOpen.value) processNextInQueue()
  }
}

function processNextInQueue() {
  if (!uploadQueue.value.length) return
  const file = uploadQueue.value[0]
  queueObjUrl         = URL.createObjectURL(file)
  editorSrc.value     = queueObjUrl
  editorFilename.value = file.name
  editorAllowSkip.value = true
  editorOpen.value    = true
}

async function onEditorApply(blob) {
  editorOpen.value = false

  if (editingMediaItem.value) {
    await doReplaceExisting(blob)
    editingMediaItem.value = null
    return
  }

  if (!uploadQueue.value.length) return
  const original = uploadQueue.value[0]
  revokeQueueUrl()
  const edited = new File([blob], original.name, { type: blob.type })
  await doUpload(edited)
  uploadQueue.value.shift()
  processNextInQueue()
}

async function onEditorSkip() {
  editorOpen.value = false
  if (!uploadQueue.value.length) return
  const original = uploadQueue.value[0]
  revokeQueueUrl()
  await doUpload(original)
  uploadQueue.value.shift()
  processNextInQueue()
}

function onEditorCancel() {
  editorOpen.value = false
  if (editingMediaItem.value) {
    editingMediaItem.value = null
    return
  }
  revokeQueueUrl()
  uploadQueue.value.shift()
  processNextInQueue()
}

function revokeQueueUrl() {
  if (queueObjUrl) {
    URL.revokeObjectURL(queueObjUrl)
    queueObjUrl = null
  }
}

function openEditorForExisting(item) {
  editingMediaItem.value = item ?? activeItem.value
  editorSrc.value        = editingMediaItem.value.url
  editorFilename.value   = editingMediaItem.value.original_filename
  editorAllowSkip.value  = false
  editorOpen.value       = true
}

async function doReplaceExisting(blob) {
  const item = editingMediaItem.value
  if (!item) return
  const formData = new FormData()
  formData.append('file', blob, item.original_filename)
  try {
    const { data } = await axios.post(route('media.replace', item.id), formData, {
      headers: { Accept: 'application/json' },
    })
    const idx = localItems.value.findIndex(i => i.id === data.id)
    if (idx !== -1) localItems.value[idx] = data
    if (activeItem.value?.id === data.id) activeItem.value = data
    notify('Image updated.', 'success')
  } catch (err) {
    notify(err.response?.data?.message ?? 'Failed to save edited image.', 'error')
  }
}

async function doUpload(file) {
  uploading.value      = true
  uploadProgress.value = 0
  uploadingName.value  = file.name

  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await axios.post(route('media.store'), formData, {
      headers: { Accept: 'application/json' },
      onUploadProgress: (e) => {
        uploadProgress.value = e.total ? Math.round((e.loaded * 100) / e.total) : 0
      },
    })
    localItems.value.unshift(data)
  } catch (err) {
    notify(err.response?.data?.message ?? 'Upload failed. Check file type and size.', 'error')
  } finally {
    uploading.value = false
  }
}

function confirmBulkDelete() {
  showBulkConfirm.value = true
}

function fileTypeBadge(mimeType, filename) {
  const map = {
    'application/pdf':                                                           'PDF',
    'application/msword':                                                        'DOC',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document':  'DOCX',
    'application/vnd.ms-excel':                                                  'XLS',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':        'XLSX',
    'application/zip':                                                           'ZIP',
    'video/mp4':   'MP4',
    'video/webm':  'WEBM',
    'video/ogg':   'OGV',
    'audio/mpeg':  'MP3',
    'audio/ogg':   'OGG',
    'audio/wav':   'WAV',
    'audio/webm':  'WEBA',
  }
  if (mimeType && map[mimeType]) return map[mimeType]
  // Fallback: derive from file extension
  if (filename) {
    const ext = filename.split('.').pop()
    if (ext && ext !== filename) return ext.toUpperCase()
  }
  return null
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
    notify('Bulk delete failed.', 'error')
  }
}
</script>

<style scoped>
.sheet-enter-active, .sheet-leave-active { transition: transform 0.25s ease; }
.sheet-enter-from, .sheet-leave-to { transform: translateY(100%); }
</style>
