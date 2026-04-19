<script setup>
import { computed }         from 'vue'
import BlockRenderer         from '@/Components/BlockRenderer.vue'
import { resolveResponsive } from '@/lib/blockUtils.js'

const props = defineProps({ block: { type: Object, required: true } })

const MAX_WIDTH_MAP = {
  full: 'max-w-full', sm: 'max-w-sm', md: 'max-w-md',
  lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl',
}

const MIN_HEIGHT_MAP = {
  auto: '', screen: 'min-h-screen', '1/2': 'min-h-[50vh]',
}

const outerStyle = computed(() => {
  return outerPaddingStyle.value
})

// New format: d.padding = { top, right, bottom, left } with CSS strings
// Legacy format: d.paddingY / d.paddingX as responsive Tailwind integers
const paddingIsObject = computed(() =>
  typeof (props.block.data?.padding) === 'object' && props.block.data?.padding !== null
)

const outerClasses = computed(() => {
  const d = props.block.data ?? {}
  if (paddingIsObject.value) {
    return [
      'w-full',
      MIN_HEIGHT_MAP[d.minHeight] ?? '',
    ].filter(Boolean).join(' ')
  }
  // legacy
  return [
    'w-full',
    resolveResponsive(d.paddingY ?? { default: 16 }, v => `py-${v}`),
    resolveResponsive(d.paddingX ?? { default: 8 },  v => `px-${v}`),
    MIN_HEIGHT_MAP[d.minHeight] ?? '',
  ].filter(Boolean).join(' ')
})

const outerPaddingStyle = computed(() => {
  if (!paddingIsObject.value) return {}
  const p = props.block.data?.padding ?? {}
  const style = {}
  if (p.top)    style.paddingTop    = p.top
  if (p.right)  style.paddingRight  = p.right
  if (p.bottom) style.paddingBottom = p.bottom
  if (p.left)   style.paddingLeft   = p.left
  return style
})

const innerClasses = computed(() => {
  const d = props.block.data ?? {}
  if (d.fullWidth) return 'w-full'
  return [
    MAX_WIDTH_MAP[d.innerMaxWidth] ?? 'max-w-xl',
    'mx-auto w-full',
  ].join(' ')
})
</script>

<template>
  <section :class="outerClasses" :style="outerStyle">
    <div :class="innerClasses">
      <BlockRenderer :blocks="block.children ?? []" />
    </div>
  </section>
</template>
