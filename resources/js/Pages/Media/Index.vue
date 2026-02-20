<template>
  <AppLayout title="Media Library">
    <Head title="Media Library" />

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <select
          v-model="filters.type"
          class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="applyFilters"
        >
          <option value="">All types</option>
          <option value="image">Images</option>
          <option value="document">Documents</option>
          <option value="video">Video</option>
          <option value="audio">Audio</option>
        </select>

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

        <label class="cursor-pointer rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
          <svg class="w-4 h-4 inline-block -mt-0.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload
          <input type="file" class="hidden" multiple @change="onFileInput" />
        </label>
      </div>
    </div>

    <!-- Drop zone -->
    <div
      ref="dropZoneRef"
      class="min-h-[60vh] rounded-lg transition-colors"
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
          :class="selected.includes(item.id) ? 'ring-2 ring-primary border-primary' : 'hover:border-foreground/30'"
          @click="toggleSelect(item.id)"
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

          <!-- Checkbox -->
          <div
            class="absolute top-1.5 left-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
            :class="{ 'opacity-100': selected.includes(item.id) }"
          >
            <div
              class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
              :class="selected.includes(item.id) ? 'bg-primary border-primary' : 'bg-background/80 border-muted-foreground'"
            >
              <svg v-if="selected.includes(item.id)" class="w-3 h-3 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>

          <!-- Hover info -->
          <div class="absolute bottom-0 left-0 right-0 bg-background/80 backdrop-blur-sm p-1.5 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
            <p class="truncate text-foreground font-medium leading-tight">{{ item.original_filename }}</p>
            <p class="text-muted-foreground">{{ item.formatted_size }}</p>
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
          {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
        </Link>
        <span v-else class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed">
          {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
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
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useDropZone } from '@vueuse/core'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Dialog, DialogContent } from '@/components/ui/dialog'

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
const dropZoneRef     = ref(null)

const filters = ref({
  type:   props.filters.type   ?? '',
  search: props.filters.search ?? '',
})

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files) => uploadFiles(files),
})

function applyFilters() {
  router.get(route('media.index'), filters.value, { preserveState: true, replace: true })
}

let searchTimer = null
function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 300)
}

function toggleSelect(id) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) {
    selected.value.push(id)
  } else {
    selected.value.splice(idx, 1)
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
    selected.value = []
    showBulkConfirm.value = false
  } catch (err) {
    alert('Bulk delete failed.')
  }
}
</script>
