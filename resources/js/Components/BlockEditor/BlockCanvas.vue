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
          :id="block.customId || `block-${block.id}`"
          class="group flex items-stretch rounded-lg border bg-card transition-colors cursor-pointer"
          :class="block.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground'"
          @click="$emit('select', block.id)"
        >
          <!-- Custom CSS injection -->
          <component v-if="block.customCss" :is="'style'">
            #{{ block.customId ? CSS.escape(block.customId) : 'block-' + block.id }} { {{ sanitizeCss(block.customCss) }} }
          </component>

          <!-- Fixed-width drag handle column — always reserves the same space -->
          <div
            class="block-drag-handle shrink-0 w-7 flex items-center justify-center border-r border-transparent group-hover:border-border/50 cursor-grab active:cursor-grabbing text-muted-foreground/40 group-hover:text-muted-foreground transition-colors"
            @click.stop
          >
            <GripVertical class="w-3.5 h-3.5" />
          </div>

          <!-- Content area — always starts at the same x position -->
          <div class="flex-1 min-w-0">

            <!-- Container block: nested sortable children -->
            <EditorContainerBlock
              v-if="block.type === 'container'"
              :block="block"
              :selected-id="selectedId"
              @select="$emit('select', $event)"
              @update-children="$emit('update-children', $event)"
            />

            <!-- Section block: nested sortable children with visual label -->
            <EditorSectionBlock
              v-else-if="block.type === 'section'"
              :block="block"
              :selected-id="selectedId"
              @select="$emit('select', $event)"
              @update-children="$emit('update-children', $event)"
            />

            <!-- Loop block: teal-bordered nested drop zone -->
            <EditorLoopBlock
              v-else-if="block.type === 'loop'"
              :block="block"
              :selected-id="selectedId"
              @select="$emit('select', $event)"
              @update-children="$emit('update-children', $event)"
            />

            <!-- Spacer block: visual placeholder -->
            <div v-else-if="block.type === 'spacer'" class="px-3 py-3">
              <div
                class="w-full flex items-center justify-center bg-muted/30 border border-dashed border-muted-foreground/30 rounded text-xs text-muted-foreground select-none"
                :style="{ height: `${(block.data?.height?.default ?? 8) * 4}px` }"
              >
                Spacer (h-{{ block.data?.height?.default ?? 8 }})
              </div>
            </div>

            <!-- Regular block: live render -->
            <div v-else class="px-3 py-3 min-h-[2.5rem]">
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
import EditorSectionBlock   from './EditorSectionBlock.vue'
import EditorLoopBlock      from './EditorLoopBlock.vue'
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
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
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

function sanitizeCss(css) {
  return css.replace(/<\/?style/gi, '')
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
