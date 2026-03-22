<!-- resources/js/Components/BlockRenderer.vue -->
<template>
  <div :class="wrapperClass">
    <template v-for="block in blocks" :key="block.id">
      <!-- Skip block if its visibility condition evaluates to false -->
      <template v-if="isVisible(block)">
        <component
          v-if="block.customCss"
          :is="'style'"
        >#{{ block.customId ? CSS.escape(block.customId) : 'block-' + block.id }} { {{ sanitizeCss(block.customCss) }} }</component>
        <div
          :id="block.customId || `block-${block.id}`"
          :class="[block.customClasses || undefined, itemClass || undefined]"
          :style="block.fontFamily ? { fontFamily: `'${block.fontFamily}', sans-serif` } : undefined"
        >
          <component
            :is="BLOCK_MAP[block.type]"
            :block="block"
          />
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { inject, onMounted, watch } from 'vue'
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
import ContainerBlock from '@/Components/Blocks/ContainerBlock.vue'
import SectionBlock  from '@/Components/Blocks/SectionBlock.vue'
import SpacerBlock   from '@/Components/Blocks/SpacerBlock.vue'
import LoopBlock     from '@/Components/Blocks/LoopBlock.vue'

const props = defineProps({
  blocks:       { type: Array,  default: () => [] },
  // wrapperClass: replaces 'space-y-4' on the outer div.
  // Pass 'contents' to make the wrapper layout-transparent (e.g. inside a flex/grid container).
  wrapperClass: { type: String, default: 'space-y-4' },
  // itemClass: extra class(es) applied to every block's wrapper <div>.
  // Pass 'flex-1 min-w-0' from a flex-row ContainerBlock so blocks share space equally.
  itemClass:    { type: String, default: '' },
})

function sanitizeCss(css) {
  // Strip both opening and closing style tags to prevent tag breakout / HTML injection
  return css.replace(/<\/?style/gi, '')
}

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
  container: ContainerBlock,
  section:   SectionBlock,
  spacer:    SpacerBlock,
  loop:      LoopBlock,
}

// Injected by LoopItemProvider when this renderer is inside a loop iteration
const loopItem = inject('loopItem', null)

// Evaluate a block's visibility condition against the current loop item.
// If the block has no condition, or we're not inside a loop, always show it.
function isVisible(block) {
  const c = block.condition
  if (!c || !loopItem?.value) return true
  const v = loopItem.value[c.field]
  switch (c.op) {
    case '=':         return String(v) === String(c.value)
    case '!=':        return String(v) !== String(c.value)
    case 'not_empty': return !!v
    case 'empty':     return !v
    default:          return true
  }
}

const loadedFonts = new Set()

function loadFont(family) {
  if (!family || loadedFonts.has(family)) return
  loadedFonts.add(family)
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@400;600;700&display=swap`
  document.head.appendChild(link)
}

function loadFontsFromBlocks(blocks) {
  for (const block of blocks) {
    if (block.fontFamily) loadFont(block.fontFamily)
    if (['container', 'section', 'loop'].includes(block.type) && block.children?.length) {
      loadFontsFromBlocks(block.children)
    }
  }
}

onMounted(() => loadFontsFromBlocks(props.blocks))
watch(() => props.blocks, (val) => loadFontsFromBlocks(val), { deep: true })
</script>
