<!-- resources/js/Components/BlockEditor/blocks/FileDownloadSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">File URL <span class="text-destructive">*</span></label>
      <input :value="block.data.url" type="text" placeholder="https://.../file.pdf"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Filename</label>
      <input :value="block.data.filename" type="text" placeholder="document.pdf"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { filename: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">File type <span class="opacity-60">(for icon)</span></label>
      <SelectBox size="sm"
        :model-value="block.data.filetype ?? 'pdf'"
        :data="FILE_TYPES"
        @update:model-value="v => emit('update', { id: block.id, data: { filetype: v } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">File size <span class="opacity-60">(e.g. 2.4 MB)</span></label>
      <input :value="block.data.filesize" type="text" placeholder="2.4 MB"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { filesize: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Description <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.description" type="text" placeholder="Short description..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { description: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
      <input :value="block.data.buttonLabel" type="text" placeholder="Download"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { buttonLabel: $event.target.value } })" />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const FILE_TYPES = [
  { value: 'pdf',  label: 'PDF' }, { value: 'docx', label: 'Word (DOCX)' },
  { value: 'xlsx', label: 'Excel (XLSX)' }, { value: 'pptx', label: 'PowerPoint' },
  { value: 'zip',  label: 'ZIP' }, { value: 'rar',  label: 'RAR' },
  { value: 'mp3',  label: 'MP3' }, { value: 'mp4',  label: 'MP4' },
  { value: 'jpg',  label: 'Image (JPG/PNG)' }, { value: 'svg', label: 'SVG' },
  { value: 'other', label: 'Other' },
]

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])
</script>
