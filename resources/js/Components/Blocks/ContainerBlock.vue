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

  // flex / inline-flex mode (default)
  const displayClass = mode.value === 'inline-flex' ? 'inline-flex' : 'flex'
  return [
    displayClass,
    resolveResponsive(d.direction ?? 'row', v => v === 'column' ? 'flex-col' : 'flex-row'),
    d.wrap ? 'flex-wrap' : 'flex-nowrap',
    gapIsString.value     ? null : (GAP_MAP[d.gap]     ?? 'gap-4'),
    JUSTIFY_MAP[d.justify]     ?? 'justify-start',
    ALIGN_MAP[d.align]         ?? 'items-start',
    // inline-flex sizes itself to content — skip max-width constraint
    mode.value !== 'inline-flex' ? (MAX_WIDTH_MAP[d.maxWidth]  ?? 'max-w-full') : null,
    paddingIsObject.value ? null : (PADDING_MAP[d.padding] ?? 'p-4'),
  ].filter(Boolean).join(' ')
})

const containerStyle = computed(() => {
  const d = props.block.data ?? {}
  const style = {}
  // inline-flex: force via inline style so it isn't dependent on Tailwind scanning the dynamic class
  if (mode.value === 'inline-flex') style.display = 'inline-flex'
  if (gapIsString.value && d.gap)     style.gap     = d.gap
  if (paddingIsObject.value)          Object.assign(style, paddingToStyle(d.padding))
  return style
})

// When this container is flex/inline-flex/grid, the BlockRenderer wrapper must be layout-transparent
// ('contents') so each child block becomes a direct flex/grid item — not nested inside a
// 'space-y-4' div that collapses the whole row into a single column.
// In flex-row mode each item also gets 'flex-1 min-w-0' so siblings share space equally.
const isFlexRow = computed(() => {
  if (mode.value !== 'flex' && mode.value !== 'inline-flex') return false
  const dir = props.block.data?.direction
  // direction can be a plain string or a responsive object; default is row
  const defaultDir = typeof dir === 'object' ? (dir?.default ?? 'row') : (dir ?? 'row')
  return defaultDir !== 'column'
})

const rendererWrapperClass = computed(() =>
  mode.value === 'flex' || mode.value === 'inline-flex' || mode.value === 'grid' ? 'contents' : 'space-y-4'
)

const rendererItemClass = computed(() => {
  if (!isFlexRow.value) return ''
  const justify = props.block.data?.justify
  // With space-distribution justify, items should be natural width — flex-1 would
  // consume all space and leave nothing for justify to distribute
  if (justify === 'between' || justify === 'around') return 'min-w-0'
  // childGrow: false → children keep natural width (no flex-1)
  if (props.block.data?.childGrow === false) return 'min-w-0'
  return 'flex-1 min-w-0'
})
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
