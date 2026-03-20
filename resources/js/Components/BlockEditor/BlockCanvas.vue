<!-- resources/js/Components/BlockEditor/BlockCanvas.vue -->
<template>
  <div class="flex-1 flex flex-col overflow-hidden border-r bg-background">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Canvas</p>
    </div>

    <div class="relative flex-1 overflow-y-auto">
      <!-- Empty state overlay -->
      <div
        v-if="draggableBlocks.length === 0"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
      >
        <div class="text-center">
          <p class="text-sm text-muted-foreground">Drag blocks from the left panel</p>
          <p class="text-xs text-muted-foreground/60 mt-1">or click a block type to add it</p>
        </div>
      </div>

      <VueDraggable
        v-model="draggableBlocks"
        tag="div"
        class="p-4 space-y-2 min-h-full"
        :group="{ name: 'canvas' }"
        :animation="150"
        handle=".block-drag-handle"
        ghost-class="opacity-40"
        @add="onAdd"
      >
        <div
          v-for="block in draggableBlocks"
          :key="block.id"
          class="group relative rounded-lg border bg-card transition-colors cursor-pointer"
          :class="block.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground'"
          @click="$emit('select', block.id)"
        >
          <!-- Drag handle -->
          <div
            class="block-drag-handle absolute left-2 top-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity"
            @click.stop
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
            </svg>
          </div>

          <!-- Block preview -->
          <div class="px-8 py-3">
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">{{ blockLabel(block.type) }}</p>
            <p class="text-sm text-foreground line-clamp-2 min-h-[1.25rem]">{{ blockPreview(block) }}</p>
          </div>
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'

const props = defineProps({
  blocks:     { type: Array,  default: () => [] },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'reorder'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

function onAdd(evt) {
  const newBlock = draggableBlocks.value[evt.newIndex]
  if (newBlock) emit('select', newBlock.id)
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML',
}

function blockLabel(type) {
  return LABELS[type] ?? type
}

function blockPreview(block) {
  const d = block.data ?? {}
  switch (block.type) {
    case 'paragraph': return d.content   || '(empty)'
    case 'heading':   return d.text      || '(empty)'
    case 'image':     return d.caption || d.alt || d.url || '(no image)'
    case 'quote':     return d.text      || '(empty)'
    case 'code':      return d.language  ? `[${d.language}]` : '(empty)'
    case 'gallery':   return d.items?.length ? `${d.items.length} image(s)` : '(empty)'
    case 'video':     return d.url       || '(no URL)'
    case 'divider':   return '————————'
    case 'cta':       return d.headline || d.text || '(empty)'
    case 'html':      return d.content   ? '(HTML content)' : '(empty)'
    default:          return ''
  }
}
</script>
