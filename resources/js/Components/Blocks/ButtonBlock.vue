<!-- resources/js/Components/Blocks/ButtonBlock.vue -->
<template>
  <div :style="wrapperStyle">
    <component
      :is="resolvedUrl ? 'a' : 'span'"
      v-bind="resolvedUrl ? { href: resolvedUrl, target: block.data.target || '_self', rel: block.data.rel || undefined } : {}"
      :class="buttonClass"
      :style="buttonStyle"
    >
      <Icon
        v-if="hasIcon && iconPosition !== 'suffix'"
        :icon="icon.name"
        :style="iconStyle"
        class="shrink-0"
        aria-hidden="true"
      />
      <span>{{ resolvedLabel || 'Button' }}</span>
      <Icon
        v-if="hasIcon && iconPosition === 'suffix'"
        :icon="icon.name"
        :style="iconStyle"
        class="shrink-0"
        aria-hidden="true"
      />
    </component>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedLabel = useFieldBinding(() => props.block, 'label')
const resolvedUrl   = useFieldBinding(() => props.block, 'url')

const variant   = computed(() => props.block.data?.variant   ?? 'filled')
const size      = computed(() => props.block.data?.size      ?? 'md')
const alignment = computed(() => props.block.data?.alignment ?? 'left')
const fullWidth = computed(() => props.block.data?.fullWidth ?? false)
const bgColor   = computed(() => props.block.data?.bgColor   ?? null)
const textColor = computed(() => props.block.data?.textColor ?? null)
const radius    = computed(() => props.block.data?.radius    ?? null)

const icon         = computed(() => props.block.data?.icon ?? null)
const hasIcon      = computed(() => !!(icon.value?.name))
const iconPosition = computed(() => icon.value?.position ?? 'prefix')

const iconStyle = computed(() => {
  if (!icon.value) return {}
  const s = {}
  if (icon.value.size)  s.fontSize = icon.value.size
  if (icon.value.color) s.color    = icon.value.color
  return s
})

const sizeClasses = { sm: 'px-3 py-1.5 text-xs', md: 'px-5 py-2 text-sm', lg: 'px-7 py-3 text-base' }

const buttonClass = computed(() => {
  const base = [
    'inline-flex items-center justify-center gap-2 font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
    sizeClasses[size.value] ?? sizeClasses.md,
    fullWidth.value ? 'w-full' : '',
  ]
  if (!bgColor.value) {
    if (variant.value === 'filled') base.push('bg-primary text-primary-foreground hover:bg-[var(--primary-hover)]')
    else if (variant.value === 'outline') base.push('border border-primary text-primary hover:bg-primary/10')
    else base.push('text-primary hover:bg-primary/10')
  }
  return base
})

const buttonStyle = computed(() => {
  const s = {}
  if (radius.value)  s.borderRadius = radius.value
  if (bgColor.value) {
    if (variant.value === 'filled') {
      s.backgroundColor = bgColor.value
    } else if (variant.value === 'outline') {
      s.borderColor = bgColor.value
      s.color = bgColor.value
    }
  }
  if (textColor.value) s.color = textColor.value
  return s
})

const wrapperStyle = computed(() => {
  const map = { left: 'flex-start', center: 'center', right: 'flex-end' }
  return { display: 'flex', justifyContent: map[alignment.value] ?? 'flex-start' }
})
</script>
