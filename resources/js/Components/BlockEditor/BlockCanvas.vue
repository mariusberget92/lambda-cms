<template>
  <div class="flex-1 flex flex-col overflow-hidden border-r border-white/8">

    <!-- Toolbar: breadcrumb + live preview toggle -->
    <div class="h-9 border-b border-white/8 bg-black/20 px-3 flex items-center justify-between shrink-0">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-1 text-xs text-white/40 overflow-hidden">
        <template v-if="selectedPath.length">
          <span
            v-for="(crumb, i) in selectedPath"
            :key="crumb.id"
            class="flex items-center gap-1 shrink-0"
          >
            <ChevronRight v-if="i > 0" class="w-3 h-3 text-white/20" />
            <button
              type="button"
              class="hover:text-white/70 transition-colors truncate max-w-[80px]"
              @click="$emit('select', crumb.id)"
            >
              {{ crumb.label }}
            </button>
          </span>
        </template>
        <span v-else class="text-white/25 italic text-xs">No block selected</span>
      </div>

      <!-- Live preview toggle -->
      <button
        type="button"
        class="flex items-center gap-1.5 text-xs px-2 py-1 rounded-md border transition-colors"
        :class="previewMode
          ? 'bg-primary/20 border-primary/40 text-primary'
          : 'bg-white/8 border-white/12 text-white/60 hover:text-white/80 hover:bg-white/12'"
        @click="previewMode = !previewMode"
      >
        <Eye class="w-3.5 h-3.5" />
        <span>Preview</span>
      </button>
    </div>

    <div class="relative flex-1 overflow-y-auto scrollbar-hidden">

      <!-- Live preview mode — reset to light theme so blocks render with :root CSS vars -->
      <div v-if="previewMode" data-theme="light" class="p-6 bg-white min-h-full">
        <BlockRenderer :blocks="blocks" />
      </div>

      <!-- Wireframe canvas mode -->
      <template v-else>
        <!-- Empty state -->
        <div
          v-if="draggableBlocks.length === 0"
          class="absolute inset-0 flex items-center justify-center pointer-events-none"
        >
          <div class="text-center">
            <LayoutTemplate class="w-8 h-8 mx-auto mb-3 text-white/20" />
            <p class="text-sm text-white/40">Drag a block from the left to get started</p>
            <p class="text-xs text-white/25 mt-1">or click a block type to add it</p>
          </div>
        </div>

        <VueDraggable
          v-model="draggableBlocks"
          tag="div"
          class="p-4 space-y-2 min-h-full editor-canvas-bg"
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
            class="group flex items-stretch rounded-lg border transition-colors cursor-pointer"
            :class="block.id === selectedId
              ? 'border-primary ring-1 ring-primary bg-primary/8'
              : 'border-white/10 bg-white/4 hover:border-white/20'"
            @click="$emit('select', block.id)"
          >
            <!-- Drag handle -->
            <div
              class="block-drag-handle shrink-0 w-7 flex items-center justify-center border-r border-transparent group-hover:border-white/8 cursor-grab active:cursor-grabbing text-white/20 group-hover:text-white/40 transition-colors"
              @click.stop
            >
              <GripVertical class="w-3.5 h-3.5" />
            </div>

            <!-- Content area -->
            <div class="flex-1 min-w-0">

              <EditorContainerBlock
                v-if="block.type === 'container' || block.type === 'columns'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update-children="$emit('update-children', $event)"
              />
              <EditorSectionBlock
                v-else-if="block.type === 'section'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update-children="$emit('update-children', $event)"
              />
              <EditorLoopBlock
                v-else-if="block.type === 'loop'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update-children="$emit('update-children', $event)"
              />
              <EditorLinkBlock
                v-else-if="block.type === 'link'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update-children="$emit('update-children', $event)"
              />
              <EditorAccordionBlock
                v-else-if="block.type === 'accordion'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update-children="$emit('update-children', $event)"
              />
              <EditorTabsBlock
                v-else-if="block.type === 'tabs'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update-children="$emit('update-children', $event)"
              />
              <EditorTableBlock
                v-else-if="block.type === 'table'"
                :block="block"
                :selected-id="selectedId"
                @select="$emit('select', $event)"
                @update="$emit('update', $event)"
              />

              <!-- Spacer -->
              <div v-else-if="block.type === 'spacer'" class="flex flex-col">
                <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center">
                  <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
                    {{ block.blockName || 'Spacer' }}
                  </span>
                </div>
                <div class="px-3 py-2">
                  <div
                    class="w-full flex items-center justify-center bg-white/5 border border-dashed border-white/15 rounded text-xs text-white/40 select-none"
                    :style="{ height: `${(block.data?.height?.default ?? 8) * 4}px` }"
                  >
                    h-{{ block.data?.height?.default ?? 8 }}
                  </div>
                </div>
              </div>

              <!-- Regular block -->
              <div v-else class="flex flex-col min-h-[2.5rem]">
                <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center gap-2">
                  <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
                    {{ block.blockName || LABELS[block.type] || block.type }}
                  </span>
                  <span v-if="block.blockName" class="text-[10px] text-white/25 uppercase tracking-wider">
                    {{ LABELS[block.type] || block.type }}
                  </span>
                </div>
                <div class="px-3 py-2 opacity-60">
                  <span v-if="isEmptyBlock(block)" class="text-xs text-white/30 italic">empty</span>
                  <component
                    v-else
                    :is="BLOCK_MAP[block.type]"
                    :block="block"
                    class="pointer-events-none"
                  />
                </div>
              </div>

            </div>
          </div>
        </VueDraggable>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { VueDraggable }  from 'vue-draggable-plus'
import { GripVertical, Eye, ChevronRight, LayoutTemplate } from '@lucide/vue'
import EditorContainerBlock from './EditorContainerBlock.vue'
import EditorSectionBlock   from './EditorSectionBlock.vue'
import EditorLoopBlock      from './EditorLoopBlock.vue'
import EditorLinkBlock      from './EditorLinkBlock.vue'
import EditorAccordionBlock from './EditorAccordionBlock.vue'
import EditorTabsBlock      from './EditorTabsBlock.vue'
import EditorTableBlock     from './EditorTableBlock.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
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
import EmbedBlock       from '@/Components/Blocks/EmbedBlock.vue'
import PaginationBlock  from '@/Components/Blocks/PaginationBlock.vue'
import NavigationBlock  from '@/Components/Blocks/NavigationBlock.vue'
import TableBlock       from '@/Components/Blocks/TableBlock.vue'
import EditorNavigationBlock from './EditorNavigationBlock.vue'
import CoverBlock        from '@/Components/Blocks/CoverBlock.vue'
import StatCardBlock     from '@/Components/Blocks/StatCardBlock.vue'
import CategoryChipBlock from '@/Components/Blocks/CategoryChipBlock.vue'
import BandBlock         from '@/Components/Blocks/BandBlock.vue'
import SectionHeaderBlock from '@/Components/Blocks/SectionHeaderBlock.vue'

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
  embed:      EmbedBlock,
  pagination: PaginationBlock,
  navigation: EditorNavigationBlock,
  table:      TableBlock,
  cover:          CoverBlock,
  'stat-card':    StatCardBlock,
  'category-chip': CategoryChipBlock,
  band:           BandBlock,
  'section-header': SectionHeaderBlock,
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
  link: 'Link', accordion: 'Accordion', 'accordion-item': 'Acc. Item',
  tabs: 'Tabs', 'tab-item': 'Tab', embed: 'Embed', pagination: 'Pagination',
  navigation: 'Navigation', table: 'Table',
  button: 'Button', 'icon-list': 'Icon List', columns: 'Columns',
  cover: 'Cover', 'stat-card': 'Stat Card', 'category-chip': 'Category Chip',
  band: 'Band', 'section-header': 'Section Header',
}

const props = defineProps({
  blocks:     { type: Array,  default: () => [] },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'reorder', 'update-children', 'update'])

// Local ref pattern: keeps draggable in sync without stale reverts
const _list = ref([...(props.blocks ?? [])])
watch(() => props.blocks, (v) => { _list.value = v })

const draggableBlocks = computed({
  get: () => _list.value,
  set: (val) => {
    _list.value = val
    emit('reorder', val)
  },
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
    case 'embed':     return !d.url
    default:          return false
  }
}

// Live preview toggle
const previewMode = ref(false)

// Breadcrumb: find the path from root to selectedId
function findPath(blocks, targetId, path = []) {
  for (const b of blocks) {
    const crumb = { id: b.id, label: b.blockName || LABELS[b.type] || b.type }
    const next = [...path, crumb]
    if (b.id === targetId) return next
    if (b.children?.length) {
      const found = findPath(b.children, targetId, next)
      if (found) return found
    }
  }
  return null
}

const selectedPath = computed(() => {
  if (!props.selectedId) return []
  return findPath(props.blocks, props.selectedId) ?? []
})
</script>
