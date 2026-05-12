<!-- resources/js/Components/Blocks/HeroBlock.vue -->
<template>
  <div :class="heroClass" :style="heroStyle">
    <div :class="innerClass">
      <p v-if="block.data?.eyebrow" class="text-sm font-semibold uppercase tracking-widest text-primary mb-3">
        {{ block.data.eyebrow }}
      </p>
      <component
        v-if="resolvedHeadline"
        :is="'h' + (block.data?.headingLevel ?? 1)"
        :class="['font-bold leading-tight mb-4', headingSizeClass]"
      >{{ resolvedHeadline }}</component>
      <p v-if="resolvedSubtext" :class="['leading-relaxed mb-8', subtextSizeClass]">
        {{ resolvedSubtext }}
      </p>
      <div v-if="(block.data?.buttons ?? []).length" :class="['flex flex-wrap gap-3', buttonAlignClass]">
        <a
          v-for="(btn, i) in block.data.buttons"
          :key="i"
          :href="btn.url || '#'"
          :target="btn.target ?? '_self'"
          :class="buttonClass(btn.variant ?? (i === 0 ? 'filled' : 'outline'))"
        >{{ btn.label }}</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedHeadline = useFieldBinding(() => props.block, 'headline')
const resolvedSubtext  = useFieldBinding(() => props.block, 'subtext')

const align = computed(() => props.block.data?.align ?? 'center')
const size  = computed(() => props.block.data?.size  ?? 'md')

const ALIGN_CLASS  = { left: 'text-left items-start',   center: 'text-center items-center',  right: 'text-right items-end' }
const BUTTON_ALIGN = { left: 'justify-start',            center: 'justify-center',             right: 'justify-end' }
const HEADING_SIZE = { sm: 'text-3xl md:text-4xl',       md: 'text-4xl md:text-5xl lg:text-6xl', lg: 'text-5xl md:text-6xl lg:text-7xl' }
const SUBTEXT_SIZE = { sm: 'text-base text-muted-foreground', md: 'text-lg text-muted-foreground', lg: 'text-xl text-muted-foreground' }

const heroClass      = computed(() => 'w-full flex items-center justify-center')
const innerClass     = computed(() => `w-full flex flex-col max-w-4xl mx-auto px-6 ${ALIGN_CLASS[align.value] ?? ALIGN_CLASS.center}`)
const headingSizeClass = computed(() => HEADING_SIZE[size.value] ?? HEADING_SIZE.md)
const subtextSizeClass = computed(() => SUBTEXT_SIZE[size.value] ?? SUBTEXT_SIZE.md)
const buttonAlignClass = computed(() => BUTTON_ALIGN[align.value] ?? 'justify-center')

const heroStyle = computed(() => {
  const d = props.block.data ?? {}
  const s = { paddingTop: d.paddingY ?? '5rem', paddingBottom: d.paddingY ?? '5rem' }
  if (d.bgColor)   s.backgroundColor = d.bgColor
  if (d.textColor) s.color           = d.textColor
  if (d.bgImage) {
    s.backgroundImage    = `url('${d.bgImage}')`
    s.backgroundSize     = 'cover'
    s.backgroundPosition = 'center'
    s.backgroundRepeat   = 'no-repeat'
  }
  if (d.minHeight) s.minHeight = d.minHeight
  return s
})

function buttonClass(variant) {
  const base = 'inline-flex items-center px-6 py-3 rounded-lg font-medium text-sm transition-colors'
  if (variant === 'outline') return `${base} border-2 border-current bg-transparent hover:bg-current/5`
  if (variant === 'ghost')   return `${base} bg-transparent hover:bg-muted`
  return `${base} bg-primary text-primary-foreground hover:bg-primary/90`
}
</script>
