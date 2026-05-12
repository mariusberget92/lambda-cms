<!-- resources/js/Components/Blocks/FileDownloadBlock.vue -->
<template>
  <div class="flex items-center gap-4 rounded-xl border border-border bg-card px-4 py-3">
    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-primary" :style="{ backgroundColor: 'color-mix(in srgb, var(--primary) 10%, transparent)' }">
      <Icon :icon="fileIcon" style="font-size: 1.25rem" aria-hidden="true" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-medium text-sm truncate">{{ block.data?.filename || 'Untitled file' }}</p>
      <p v-if="block.data?.description" class="text-xs text-muted-foreground truncate">{{ block.data.description }}</p>
      <p v-if="block.data?.filesize" class="text-xs text-muted-foreground">{{ block.data.filesize }}</p>
    </div>
    <a
      v-if="block.data?.url"
      :href="block.data.url"
      download
      class="shrink-0 inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
    >
      <Icon icon="lucide:download" style="font-size: 0.875rem" aria-hidden="true" />
      {{ block.data?.buttonLabel || 'Download' }}
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const FILE_ICONS = {
  pdf:  'lucide:file-text',
  zip:  'lucide:file-archive',
  rar:  'lucide:file-archive',
  docx: 'lucide:file-type',
  doc:  'lucide:file-type',
  xlsx: 'lucide:file-spreadsheet',
  xls:  'lucide:file-spreadsheet',
  pptx: 'lucide:presentation',
  ppt:  'lucide:presentation',
  mp3:  'lucide:file-audio',
  mp4:  'lucide:file-video',
  jpg:  'lucide:file-image',
  jpeg: 'lucide:file-image',
  png:  'lucide:file-image',
  svg:  'lucide:file-image',
}

const fileIcon = computed(() => {
  const ext = (props.block.data?.filetype ?? '').toLowerCase()
  return FILE_ICONS[ext] ?? 'lucide:file'
})
</script>
