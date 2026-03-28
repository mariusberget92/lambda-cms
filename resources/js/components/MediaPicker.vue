<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-4xl max-h-[85vh] flex flex-col gap-0 p-0">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-base font-semibold">Media Library</h2>
        <div class="flex items-center gap-2">
          <SelectBox
            v-model="filters.type"
            :data="[
              { value: '',         label: 'All types' },
              { value: 'image',    label: 'Images' },
              { value: 'document', label: 'Documents' },
              { value: 'video',    label: 'Video' },
              { value: 'audio',    label: 'Audio' },
            ]"
            placeholder="All types"
          />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search..."
            class="rounded-md border bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring w-40"
          />
        </div>
      </div>

      <!-- Drop zone + grid -->
      <div
        ref="dropZoneRef"
        class="flex-1 overflow-y-auto p-4"
        :class="isOverDropZone ? 'bg-primary/5 ring-2 ring-primary ring-inset' : ''"
      >
        <!-- Upload progress -->
        <div v-if="uploading" class="mb-4 rounded-lg border bg-card p-3 flex items-center gap-3">
          <div class="flex-1 bg-muted rounded-full h-1.5">
            <div class="bg-primary h-1.5 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
          </div>
          <span class="text-xs text-muted-foreground shrink-0">{{ uploadProgress }}%</span>
        </div>

        <!-- Upload button -->
        <label class="mb-4 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed p-4 text-sm text-muted-foreground hover:border-primary hover:text-primary transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Click to upload or drag &amp; drop files here
          <input type="file" class="hidden" multiple @change="onFileInput" />
        </label>

        <!-- Empty state -->
        <div v-if="!items.length && !loading" class="py-16 text-center text-sm text-muted-foreground">
          No media files yet.
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-2">
          <button
            v-for="item in items"
            :key="item.id"
            type="button"
            class="relative group aspect-square rounded-md overflow-hidden border bg-muted focus:outline-none focus:ring-2 focus:ring-ring transition-colors"
            :class="selectedId === item.id ? 'ring-2 ring-primary border-primary' : 'hover:border-foreground/30'"
            @click="selectedId = item.id"
          >
            <img
              v-if="item.type === 'image'"
              :src="item.url"
              :alt="item.alt ?? item.original_filename"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 p-2">
              <svg class="w-6 h-6 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span class="text-xs text-muted-foreground text-center leading-tight line-clamp-2">{{ item.original_filename }}</span>
            </div>

            <div v-if="selectedId === item.id" class="absolute inset-0 bg-primary/20 flex items-center justify-center">
              <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
          </button>
        </div>

        <!-- Load more -->
        <div v-if="nextPageUrl" class="mt-4 text-center">
          <button
            type="button"
            class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
            :disabled="loading"
            @click="loadMore"
          >
            {{ loading ? 'Loading...' : 'Load more' }}
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between px-6 py-4 border-t gap-4">
        <div v-if="selectedItem" class="text-sm text-muted-foreground truncate">
          <span class="font-medium text-foreground">{{ selectedItem.original_filename }}</span>
          · {{ selectedItem.formatted_size }}
          <span v-if="selectedItem.width">· {{ selectedItem.width }}×{{ selectedItem.height }}</span>
        </div>
        <div v-else class="text-sm text-muted-foreground">No file selected</div>

        <div class="flex gap-2 shrink-0">
          <button
            type="button"
            class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
            @click="isOpen = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
            :disabled="!selectedId"
            @click="confirm"
          >
            {{ confirmLabel }}
          </button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useDropZone } from '@vueuse/core'
import axios from 'axios'
import { Dialog, DialogContent } from '@/components/ui/dialog'
import SelectBox from '@/Components/SelectBox.vue'
import { useNotifications } from '@/composables/useNotifications'

const { notify } = useNotifications()

const props = defineProps({
  modelValue:   { type: Boolean, default: false },
  confirmLabel: { type: String,  default: 'Select' },
})

const emit = defineEmits(['update:modelValue', 'select'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const items        = ref([])
const nextPageUrl  = ref(null)
const loading      = ref(false)
const selectedId   = ref(null)
const uploading    = ref(false)
const uploadProgress = ref(0)
const filters      = ref({ type: '', search: '' })
const dropZoneRef  = ref(null)

const selectedItem = computed(() => items.value.find(i => i.id === selectedId.value) ?? null)

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files) => uploadFiles(files),
})

async function load(reset = true) {
  loading.value = true
  if (reset) {
    items.value      = []
    nextPageUrl.value = null
    selectedId.value  = null
  }

  const params = {}
  if (filters.value.type)   params.type   = filters.value.type
  if (filters.value.search) params.search = filters.value.search

  try {
    const url = reset ? '/media' : nextPageUrl.value
    const { data } = await axios.get(url, {
      params: reset ? params : {},
      headers: { 'X-Inertia': 'true', 'X-Inertia-Version': '1' },
    })
    const page = data?.props?.media ?? data
    items.value      = reset ? page.data : [...items.value, ...page.data]
    nextPageUrl.value = page.next_page_url ?? null
  } catch (err) {
    notify('Failed to load media. Please try again.', 'error')
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (!nextPageUrl.value || loading.value) return
  await load(false)
}

let filterTimer = null
watch(filters, () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => load(true), 300)
}, { deep: true })

watch(isOpen, (open) => {
  if (open) load(true)
})

function onFileInput(event) {
  uploadFiles(Array.from(event.target.files))
  event.target.value = ''
}

async function uploadFiles(files) {
  if (!files?.length) return

  for (const file of files) {
    uploading.value      = true
    uploadProgress.value = 0

    const formData = new FormData()
    formData.append('file', file)

    try {
      const { data } = await axios.post('/media', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json',
        },
        onUploadProgress: (e) => {
          uploadProgress.value = e.total ? Math.round((e.loaded * 100) / e.total) : 0
        },
      })
      items.value.unshift(data)
      selectedId.value = data.id
    } catch (err) {
      notify(err.response?.data?.message ?? 'Upload failed. Check file type and size.', 'error')
    } finally {
      uploading.value = false
    }
  }
}

function confirm() {
  if (!selectedItem.value) return
  emit('select', selectedItem.value)
  isOpen.value = false
}
</script>
