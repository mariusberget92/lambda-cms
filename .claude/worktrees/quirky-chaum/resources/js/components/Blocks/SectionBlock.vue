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
  const d = props.block.data ?? {}
  const styles = {}

  if (d.bgType === 'color' && d.bgColor) {
    styles.backgroundColor = d.bgColor
  } else if (d.bgType === 'image' && d.bgImage?.url) {
    styles.backgroundImage    = `url('${d.bgImage.url}')`
    styles.backgroundPosition = d.bgImage.position ?? 'center'
    styles.backgroundSize     = d.bgImage.size ?? 'cover'
    styles.backgroundRepeat   = 'no-repeat'
  } else if (d.bgType === 'gradient' && d.bgGradient) {
    const { from, to, direction } = d.bgGradient
    const dir = {
      'to-r': 'to right', 'to-l': 'to left',
      'to-b': 'to bottom', 'to-t': 'to top',
      'to-br': 'to bottom right', 'to-bl': 'to bottom left',
    }[direction] ?? 'to right'
    styles.backgroundImage = `linear-gradient(${dir}, ${from ?? '#3b4252'}, ${to ?? '#4c566a'})`
  }

  return styles
})

const outerClasses = computed(() => {
  const d = props.block.data ?? {}
  return [
    'w-full',
    resolveResponsive(d.paddingY ?? { default: 16 }, v => `py-${v}`),
    resolveResponsive(d.paddingX ?? { default: 8 },  v => `px-${v}`),
    MIN_HEIGHT_MAP[d.minHeight] ?? '',
  ].filter(Boolean).join(' ')
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
