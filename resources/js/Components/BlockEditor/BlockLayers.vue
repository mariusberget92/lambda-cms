<!-- resources/js/Components/BlockEditor/BlockLayers.vue -->
<template>
  <div class="w-64 shrink-0 border-l flex flex-col bg-sidebar overflow-hidden">

    <!-- Layers list -->
    <div class="flex flex-col shrink-0" style="max-height: 40%">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
      </div>

      <div v-if="draggableBlocks.length === 0" class="px-3 py-4 text-xs text-muted-foreground text-center">
        No blocks yet
      </div>

      <VueDraggable
        v-else
        v-model="draggableBlocks"
        tag="div"
        class="overflow-y-auto p-1.5 space-y-0.5"
        handle=".layer-handle"
        :animation="150"
      >
        <div
          v-for="block in draggableBlocks"
          :key="block.id"
          class="flex items-center gap-1.5 rounded-md px-1.5 py-1.5 cursor-pointer transition-colors text-xs"
          :class="block.id === selectedId
            ? 'bg-primary text-primary-foreground'
            : 'hover:bg-accent text-foreground'"
          @click="$emit('select', block.id)"
        >
          <span
            class="layer-handle cursor-grab active:cursor-grabbing shrink-0"
            :class="block.id === selectedId ? 'text-primary-foreground/60' : 'text-muted-foreground'"
            @click.stop
          >
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
            </svg>
          </span>

          <span class="flex-1 truncate">{{ blockLabel(block.type) }}</span>

          <button
            type="button"
            class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
            :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
            title="Remove block"
            @click.stop="$emit('remove', block.id)"
          >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </VueDraggable>
    </div>

    <!-- Settings panel -->
    <div class="flex-1 flex flex-col border-t overflow-hidden">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ selectedBlock ? blockLabel(selectedBlock.type) + ' Settings' : 'Settings' }}
        </p>
      </div>

      <div class="flex-1 overflow-y-auto p-3">
        <div v-if="!selectedBlock" class="h-full flex items-center justify-center">
          <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
        </div>

        <div v-else-if="selectedBlock.type === 'html' && !isAdmin" class="rounded-md border border-dashed p-4 text-center">
          <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
        </div>

        <component
          v-else
          :is="settingsComponent"
          :block="selectedBlock"
          :is-admin="isAdmin"
          :meta="meta"
          @update="$emit('update', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import HeadingSettings   from './blocks/HeadingSettings.vue'
import ParagraphSettings from './blocks/ParagraphSettings.vue'
import ImageSettings     from './blocks/ImageSettings.vue'
import QuoteSettings     from './blocks/QuoteSettings.vue'
import CodeSettings      from './blocks/CodeSettings.vue'
import GallerySettings   from './blocks/GallerySettings.vue'
import VideoSettings     from './blocks/VideoSettings.vue'
import DividerSettings   from './blocks/DividerSettings.vue'
import CtaSettings       from './blocks/CtaSettings.vue'
import HtmlSettings      from './blocks/HtmlSettings.vue'
import ComponentSettings from './blocks/ComponentSettings.vue'

const props = defineProps({
  blocks:        { type: Array,   default: () => [] },
  selectedId:    { type: String,  default: null },
  selectedBlock: { type: Object,  default: null },
  isAdmin:       { type: Boolean, default: false },
  meta:          { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['select', 'remove', 'reorder', 'update'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
}

const COMPONENT_MAP = {
  paragraph: ParagraphSettings, heading: HeadingSettings, image: ImageSettings,
  quote: QuoteSettings, code: CodeSettings, gallery: GallerySettings,
  video: VideoSettings, divider: DividerSettings, cta: CtaSettings, html: HtmlSettings,
  component: ComponentSettings,
}

const settingsComponent = computed(() =>
  props.selectedBlock ? COMPONENT_MAP[props.selectedBlock.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
