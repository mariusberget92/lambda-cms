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
            class="block-drag-handle absolute left-2 top-3 cursor-grab active:cursor-grabbing text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity"
            @click.stop
          >
            <GripVertical class="w-4 h-4" />
          </div>

          <!-- Container block: nested sortable children -->
          <EditorContainerBlock
            v-if="block.type === 'container'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />

          <!-- Regular block: live render -->
          <div v-else class="px-8 py-3 min-h-[2.5rem]">
            <div
              v-if="isEmptyBlock(block)"
              class="text-xs text-muted-foreground italic"
            >{{ LABELS[block.type] ?? block.type }} — empty</div>
            <component
              v-else
              :is="BLOCK_MAP[block.type]"
              :block="block"
              class="pointer-events-none"
            />
          </div>
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable }  from 'vue-draggable-plus'
import { GripVertical }  from 'lucide-vue-next'
import EditorContainerBlock from './EditorContainerBlock.vue'
import ParagraphBlock from '@/Components/Blocks/ParagraphBlock.vue'
import HeadingBlock   from '@/Components/Blocks/HeadingBlock.vue'
import ImageBlock     from '@/Components/Blocks/ImageBlock.vue'
import QuoteBlock     from '@/Components/Blocks/QuoteBlock.vue'
import CodeBlock      from '@/Components/Blocks/CodeBlock.vue'
import GalleryBlock   from '@/Components/Blocks/GalleryBlock.vue'
import VideoBlock     from '@/Components/Blocks/VideoBlock.vue'
import DividerBlock   from '@/Components/Blocks/DividerBlock.vue'
import CtaBlock       from '@/Components/Blocks/CtaBlock.vue'
import HtmlBlock      from '@/Components/Blocks/HtmlBlock.vue'
import PostListBlock  from '@/Components/Blocks/PostListBlock.vue'

const BLOCK_MAP = {
  paragraph: ParagraphBlock,
  heading:   HeadingBlock,
  image:     ImageBlock,
  quote:     QuoteBlock,
  code:      CodeBlock,
  gallery:   GalleryBlock,
  video:     VideoBlock,
  divider:   DividerBlock,
  cta:       CtaBlock,
  html:      HtmlBlock,
  component: PostListBlock,
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container',
}

const props = defineProps({
  blocks:     { type: Array,  default: () => [] },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'reorder', 'update-children'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

function onAdd(evt) {
  const newBlock = draggableBlocks.value[evt.newIndex]
  if (newBlock) emit('select', newBlock.id)
}

function isEmptyBlock(block) {
  const d = block.data ?? {}
  switch (block.type) {
    case 'paragraph': return !d.content
    case 'heading':   return !d.text
    case 'image':     return !d.url
    case 'quote':     return !d.text
    case 'code':      return !d.content
    case 'gallery':   return !(d.items?.length)
    case 'video':     return !d.url
    case 'cta':       return !d.headline && !d.text
    case 'html':      return !d.content
    default:          return false
  }
}
</script>
