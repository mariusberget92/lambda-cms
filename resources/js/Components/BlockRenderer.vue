<!-- resources/js/Components/BlockRenderer.vue -->
<template>
  <div class="space-y-4">
    <template v-for="block in blocks" :key="block.id">
      <component
        v-if="block.customCss"
        :is="'style'"
>#{{ block.customId ? CSS.escape(block.customId) : 'block-' + block.id }} { {{ sanitizeCss(block.customCss) }} }</component>
      <div
        :id="block.customId || `block-${block.id}`"
        :class="block.customClasses || undefined"
        :style="block.fontFamily ? { fontFamily: `'${block.fontFamily}', sans-serif` } : undefined"
      >
        <component
          :is="BLOCK_MAP[block.type]"
          :block="block"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
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

const props = defineProps({ blocks: { type: Array, default: () => [] } })

function sanitizeCss(css) {
  // Prevent </style> tag breakout which would allow HTML injection
  return css.replace(/<\/style/gi, '')
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
    if (block.type === 'container' && block.children?.length) {
      loadFontsFromBlocks(block.children)
    }
  }
}

onMounted(() => loadFontsFromBlocks(props.blocks))
watch(() => props.blocks, (val) => loadFontsFromBlocks(val), { deep: true })
</script>
