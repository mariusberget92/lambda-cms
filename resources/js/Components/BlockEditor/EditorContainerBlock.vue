<!-- resources/js/Components/BlockEditor/EditorContainerBlock.vue -->
<template>
  <div class="px-3 py-3">
    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">
      {{ block.blockName || 'Container' }}
    </p>

    <!-- Custom CSS injection for children -->
    <template v-for="child in localChildren" :key="'style-' + child.id">
      <component v-if="child.customCss" :is="'style'">
        #{{ child.customId ? CSS.escape(child.customId) : 'block-' + child.id }} { {{ sanitizeCss(child.customCss) }} }
      </component>
    </template>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      :class="draggableClass"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <div
        v-for="child in localChildren"
        :key="child.id"
        :id="child.customId || `block-${child.id}`"
        class="group relative flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 cursor-pointer text-xs transition-colors"
        :class="[
          child.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground',
          isFlexRow ? 'flex-1 min-w-0' : '',
        ]"
        @click.stop="$emit('select', child.id)"
      >
        <span class="child-drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0" @click.stop>
          <GripVertical class="w-3 h-3" />
        </span>
        <div class="flex-1 min-w-0 overflow-hidden">
          <span class="text-xs block truncate leading-none">
            {{ child.blockName || LABELS[child.type] || child.type }}
          </span>
          <span v-if="child.blockName" class="text-[10px] text-muted-foreground/50 leading-none mt-0.5 block">
            {{ LABELS[child.type] || child.type }}
          </span>
        </div>
      </div>

      <div v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-muted-foreground/60 pointer-events-none">
        Drop blocks here
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical } from 'lucide-vue-next'
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
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'update-children'])

const mode = computed(() => props.block.data?.mode ?? 'flex')

const isFlexRow = computed(() => {
  if (mode.value !== 'flex') return false
  const dir = props.block.data?.direction
  const defaultDir = typeof dir === 'object' ? (dir?.default ?? 'row') : (dir ?? 'row')
  return defaultDir !== 'column'
})

// Mirror the actual flex/grid layout in the editor so the preview matches the live page
const draggableClass = computed(() => {
  const base = 'min-h-[40px] rounded-md border-2 border-dashed border-border p-2'
  if (isFlexRow.value) return `${base} flex flex-row flex-wrap gap-2`
  return `${base} space-y-1.5`
})

function sanitizeCss(css) {
  return css.replace(/<\/?style/gi, '')
}

const _children = ref([...(props.block.children ?? [])])
watch(() => props.block.children, (v) => { _children.value = v ?? [] })

const localChildren = computed({
  get: () => _children.value,
  set: (val) => {
    _children.value = val
    emit('update-children', { id: props.block.id, children: val })
  },
})

function onAdd(evt) {
  const newChild = localChildren.value[evt.newIndex]
  if (newChild) emit('select', newChild.id)
}
</script>
