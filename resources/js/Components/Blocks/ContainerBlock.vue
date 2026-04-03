<script setup>
import { computed }         from 'vue'
import BlockRenderer         from '@/Components/BlockRenderer.vue'
import { resolveResponsive } from '@/lib/blockUtils.js'

const props = defineProps({ block: { type: Object, required: true } })

const JUSTIFY_MAP   = { start: 'justify-start', center: 'justify-center', end: 'justify-end', between: 'justify-between', around: 'justify-around' }
const ALIGN_MAP     = { start: 'items-start',   center: 'items-center',   end: 'items-end',   stretch: 'items-stretch' }
const MAX_WIDTH_MAP = { full: 'max-w-full', prose: 'max-w-prose', sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl' }
// Legacy Tailwind integer maps (kept for backward-compat with older block data)
const GAP_MAP     = Object.fromEntries(Array.from({length: 17}, (_, i) => [i, `gap-${i}`]))
const PADDING_MAP = Object.fromEntries(Array.from({length: 17}, (_, i) => [i, `p-${i}`]))

// Convert a 4-side padding object { top, right, bottom, left } to an inline style object.
// Returns {} if nothing is set.
function paddingToStyle(p) {
  if (!p || typeof p !== 'object') return {}
  const out = {}
  if (p.top)    out.paddingTop    = p.top
  if (p.right)  out.paddingRight  = p.right
  if (p.bottom) out.paddingBottom = p.bottom
  if (p.left)   out.paddingLeft   = p.left
  return out
}

const mode = computed(() => props.block.data?.mode ?? 'flex')

// gap: new format is a CSS string like "1rem"; legacy is a number (0-16)
// padding: new format is { top, right, bottom, left }; legacy is a number (0-16)
const gapIsString     = computed(() => typeof (props.block.data?.gap)     === 'string')
const paddingIsObject = computed(() => typeof (props.block.data?.padding)  === 'object' && props.block.data?.padding !== null)

const containerClasses = computed(() => {
  const d = props.block.data ?? {}

  if (mode.value === 'grid') {
    return [
      'grid',
      resolveResponsive(d.columns ?? { default: 2 }, v => `grid-cols-${v}`),
      gapIsString.value     ? null : (GAP_MAP[d.gap]     ?? 'gap-4'),
      MAX_WIDTH_MAP[d.maxWidth]  ?? 'max-w-full',
      paddingIsObject.value ? null : (PADDING_MAP[d.padding] ?? 'p-4'),
    ].filter(Boolean).join(' ')
  }

  // flex mode (default)
  return [
    'flex',
    resolveResponsive(d.direction ?? 'row', v => v === 'column' ? 'flex-col' : 'flex-row'),
    d.wrap ? 'flex-wrap' : 'flex-nowrap',
    gapIsString.value     ? null : (GAP_MAP[d.gap]     ?? 'gap-4'),
    JUSTIFY_MAP[d.justify]     ?? 'justify-start',
    ALIGN_MAP[d.align]         ?? 'items-start',
    MAX_WIDTH_MAP[d.maxWidth]  ?? 'max-w-full',
    paddingIsObject.value ? null : (PADDING_MAP[d.padding] ?? 'p-4'),
  ].filter(Boolean).join(' ')
})

const bgStyle = computed(() => {
  const d = props.block.data ?? {}
  const style = {}
  if (d.bgType === 'color' && d.bgColor) {
    style.backgroundColor = d.bgColor
  } else if (d.bgType === 'image' && d.bgImage?.url) {
    style.backgroundImage    = `url('${d.bgImage.url}')`
    style.backgroundPosition = d.bgImage.position ?? 'center'
    style.backgroundSize     = d.bgImage.size ?? 'cover'
    style.backgroundRepeat   = 'no-repeat'
    if (d.bgImage.parallax) style.backgroundAttachment = 'fixed'
  } else if (d.bgType === 'gradient' && d.bgGradient) {
    const { from, to, direction } = d.bgGradient
    const dir = {
      'to-r': 'to right', 'to-l': 'to left',
      'to-b': 'to bottom', 'to-t': 'to top',
      'to-br': 'to bottom right', 'to-bl': 'to bottom left',
    }[direction] ?? 'to right'
    style.backgroundImage = `linear-gradient(${dir}, ${from ?? '#3b4252'}, ${to ?? '#4c566a'})`
  }
  return style
})

const containerStyle = computed(() => {
  const d = props.block.data ?? {}
  const style = {}
  if (gapIsString.value && d.gap)     style.gap     = d.gap
  if (paddingIsObject.value)          Object.assign(style, paddingToStyle(d.padding))
  return { ...bgStyle.value, ...style }
})

// When this container is flex or grid, the BlockRenderer wrapper must be layout-transparent
// ('contents') so each child block becomes a direct flex/grid item — not nested inside a
// 'space-y-4' div that collapses the whole row into a single column.
// In flex-row mode each item also gets 'flex-1 min-w-0' so siblings share space equally.
const isFlexRow = computed(() => {
  if (mode.value !== 'flex') return false
  const dir = props.block.data?.direction
  // direction can be a plain string or a responsive object; default is row
  const defaultDir = typeof dir === 'object' ? (dir?.default ?? 'row') : (dir ?? 'row')
  return defaultDir !== 'column'
})

const rendererWrapperClass = computed(() =>
  mode.value === 'flex' || mode.value === 'grid' ? 'contents' : 'space-y-4'
)

const rendererItemClass = computed(() =>
  isFlexRow.value ? 'flex-1 min-w-0' : ''
)
</script>

<template>
  <div :class="containerClasses" :style="containerStyle">
    <BlockRenderer
      :blocks="block.children ?? []"
      :wrapper-class="rendererWrapperClass"
      :item-class="rendererItemClass"
    />
  </div>
</template>
