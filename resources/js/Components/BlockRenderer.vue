<!-- resources/js/Components/BlockRenderer.vue -->
<template>
  <div :class="wrapperClass">
    <template v-for="block in blocks" :key="block.id">
      <!-- Skip block if its visibility condition evaluates to false -->
      <template v-if="isVisible(block)">
        <component
          v-if="block.customCss"
          :is="'style'"
        >#{{ block.customId ? CSS.escape(block.customId) : 'lambda-be-' + block.id }} { {{ sanitizeCss(block.customCss) }} }</component>
        <div
          :id="block.customId || `lambda-be-${block.id}`"
          :class="[block.customClasses || undefined, itemClass || undefined]"
          :style="blockWrapperStyle(block)"
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
import ContainerBlock from '@/Components/Blocks/ContainerBlock.vue'
import SectionBlock  from '@/Components/Blocks/SectionBlock.vue'
import SpacerBlock   from '@/Components/Blocks/SpacerBlock.vue'
import LoopBlock     from '@/Components/Blocks/LoopBlock.vue'
import PostTitleBlock         from '@/Components/Blocks/PostTitleBlock.vue'
import PostBodyBlock          from '@/Components/Blocks/PostBodyBlock.vue'
import PostFeaturedImageBlock from '@/Components/Blocks/PostFeaturedImageBlock.vue'
import PostMetaBlock          from '@/Components/Blocks/PostMetaBlock.vue'
import PostAuthorBlock        from '@/Components/Blocks/PostAuthorBlock.vue'
import PostTaxonomyBlock      from '@/Components/Blocks/PostTaxonomyBlock.vue'
import PostCommentsBlock      from '@/Components/Blocks/PostCommentsBlock.vue'
import ArchiveTitleBlock      from '@/Components/Blocks/ArchiveTitleBlock.vue'
import SearchBlock            from '@/Components/Blocks/SearchBlock.vue'
import NavigationBlock        from '@/components/Blocks/NavigationBlock.vue'
import LinkBlock              from '@/components/Blocks/LinkBlock.vue'
import FilterLinkBlock        from '@/Components/Blocks/FilterLinkBlock.vue'
import TemplateBlock          from '@/Components/Blocks/TemplateBlock.vue'
import TableBlock             from '@/Components/Blocks/TableBlock.vue'

const props = defineProps({
  blocks:       { type: Array,  default: () => [] },
  // wrapperClass: replaces 'space-y-4' on the outer div.
  // Pass 'contents' to make the wrapper layout-transparent (e.g. inside a flex/grid container).
  wrapperClass: { type: String, default: 'space-y-4' },
  // itemClass: extra class(es) applied to every block's wrapper <div>.
  // Pass 'flex-1 min-w-0' from a flex-row ContainerBlock so blocks share space equally.
  itemClass:    { type: String, default: '' },
})

// Blocks that manage their own padding internally (do not apply AdvancedSettings padding on wrapper)
const SELF_PADDED_TYPES = new Set(['container', 'section'])

function spacingStyle(obj, prop) {
  if (!obj || typeof obj !== 'object') return {}
  const out = {}
  if (obj.top)    out[prop + 'Top']    = obj.top
  if (obj.right)  out[prop + 'Right']  = obj.right
  if (obj.bottom) out[prop + 'Bottom'] = obj.bottom
  if (obj.left)   out[prop + 'Left']   = obj.left
  return out
}

function blockWrapperStyle(block) {
  const style = {}
  if (block.fontFamily) style.fontFamily = `'${block.fontFamily}', sans-serif`
  // Typography (from TypographyControl — cascades into block content)
  const typo = block.data?.typography
  if (typo) {
    if (typo.textAlign)      style.textAlign      = typo.textAlign
    if (typo.color)          style.color          = typo.color
    if (typo.fontSize)       style.fontSize       = typo.fontSize
    if (typo.fontWeight)     style.fontWeight     = typo.fontWeight
    if (typo.lineHeight)     style.lineHeight     = typo.lineHeight
    if (typo.letterSpacing)  style.letterSpacing  = typo.letterSpacing
    if (typo.textDecoration) style.textDecoration = typo.textDecoration
    if (typo.textTransform)  style.textTransform  = typo.textTransform
    if (typo.textShadow?.color) {
      const ts = typo.textShadow
      style.textShadow = `${ts.x ?? 0}px ${ts.y ?? 0}px ${ts.blur ?? 0}px ${ts.color}`
    }
  }
  if (block.data?.margin) Object.assign(style, spacingStyle(block.data.margin, 'margin'))
  if (block.data?.padding && !SELF_PADDED_TYPES.has(block.type)) {
    Object.assign(style, spacingStyle(block.data.padding, 'padding'))
  }
  const bgType = block.data?.advBgType
  if (bgType === 'color' && block.data?.advBgColor) {
    style.backgroundColor = block.data.advBgColor
  } else if (bgType === 'gradient' && block.data?.advBgGradient) {
    const g = block.data.advBgGradient
    const dir = { 'to-r': 'to right', 'to-l': 'to left', 'to-b': 'to bottom', 'to-t': 'to top', 'to-br': 'to bottom right', 'to-bl': 'to bottom left' }[g.direction ?? 'to-r'] ?? 'to right'
    style.backgroundImage = `linear-gradient(${dir}, ${g.from ?? '#3b4252'}, ${g.to ?? '#4c566a'})`
  } else if (bgType === 'image') {
    const img = block.data.advBgImage ?? {}
    // Resolve dynamic binding first, fall back to static URL
    let url = img.url ?? null
    const binding = block.bindings?.advBgImageUrl
    if (binding && loopItem?.value) {
      const field = binding.replace(/^(?:loop|post):/, '')
      url = loopItem.value[field] ?? url
    }
    if (url) {
      style.backgroundImage    = `url('${url}')`
      style.backgroundPosition = img.position ?? 'center'
      style.backgroundSize     = img.size ?? 'cover'
      style.backgroundRepeat   = 'no-repeat'
      if (img.parallax) style.backgroundAttachment = 'fixed'
    }
  }
  // Border radius + border stroke (from BorderControl data)
  const border = block.data?.border
  if (border) {
    // Radius — per-corner (new) or legacy single value
    if (border.radiusTL || border.radiusTR || border.radiusBL || border.radiusBR) {
      if (border.radiusTL) style.borderTopLeftRadius     = border.radiusTL
      if (border.radiusTR) style.borderTopRightRadius    = border.radiusTR
      if (border.radiusBL) style.borderBottomLeftRadius  = border.radiusBL
      if (border.radiusBR) style.borderBottomRightRadius = border.radiusBR
    } else if (border.radius) {
      style.borderRadius = border.radius   // legacy single-value fallback
    }
    // Border stroke
    if (border.style && border.style !== 'none') {
      style.borderStyle = border.style
      if (border.width) style.borderWidth = border.width
      if (border.color) style.borderColor = border.color
    }
  }

  // Shadow (from BorderControl / ShadowControl)
  if (block.data?.shadow) style.boxShadow = block.data.shadow

  // Effects (from AdvancedSettings Effects section)
  if (block.data?.opacity != null && block.data.opacity !== 100) {
    style.opacity = block.data.opacity / 100
  }
  if (block.data?.cursor)   style.cursor   = block.data.cursor
  if (block.data?.overflow) style.overflow = block.data.overflow
  if (block.data?.zIndex != null) style.zIndex = block.data.zIndex
  if (block.data?.transitionDuration) {
    const dur  = block.data.transitionDuration
    const ease = block.data.transitionEasing ?? 'ease'
    style.transition = `all ${dur} ${ease}`
  }

  return Object.keys(style).length ? style : undefined
}

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
  container: ContainerBlock,
  section:   SectionBlock,
  spacer:    SpacerBlock,
  loop:      LoopBlock,
  'post-title':          PostTitleBlock,
  'post-body':           PostBodyBlock,
  'post-featured-image': PostFeaturedImageBlock,
  'post-meta':           PostMetaBlock,
  'post-author':         PostAuthorBlock,
  'post-taxonomy':       PostTaxonomyBlock,
  'post-comments':       PostCommentsBlock,
  'archive-title':       ArchiveTitleBlock,
  'archive-loop':        LoopBlock,
  search:                SearchBlock,
  navigation:            NavigationBlock,
  link:                  LinkBlock,
  'filter-link':         FilterLinkBlock,
  'template':            TemplateBlock,
  table:                 TableBlock,
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
    if (['container', 'section', 'loop', 'archive-loop', 'link'].includes(block.type) && block.children?.length) {
      loadFontsFromBlocks(block.children)
    }
  }
}

onMounted(() => loadFontsFromBlocks(props.blocks))
watch(() => props.blocks, (val) => loadFontsFromBlocks(val), { deep: true })
</script>
