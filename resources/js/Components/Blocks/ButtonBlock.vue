<template>
  <div :class="alignClass">
    <component
      :is="href ? 'a' : 'button'"
      :href="href || undefined"
      :target="href ? target : undefined"
      :type="href ? undefined : 'button'"
      :style="btnStyle"
    >
      <Icon v-if="hasIcon && iconPos === 'prefix'" :icon="icon.name" :style="iconSty" aria-hidden="true" />
      <span>{{ text }}</span>
      <Icon v-if="hasIcon && iconPos === 'suffix'" :icon="icon.name" :style="iconSty" aria-hidden="true" />
    </component>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const d = computed(() => props.block.data ?? {})

const _text   = useFieldBinding(() => props.block, 'text')
const text    = computed(() => _text.value || 'Button')
const href    = computed(() => d.value.href ?? '')
const target  = computed(() => d.value.target ?? '_self')
const variant = computed(() => d.value.variant ?? 'solid')
const sz      = computed(() => d.value.size ?? 'md')
const icon    = computed(() => d.value.icon ?? {})
const hasIcon = computed(() => !!icon.value.name)
const iconPos = computed(() => icon.value.position ?? 'prefix')

const SIZES = {
  sm: { padding: '0.25rem 0.75rem', fontSize: '0.8125rem' },
  md: { padding: '0.5rem 1.25rem',  fontSize: '0.875rem'  },
  lg: { padding: '0.75rem 1.75rem', fontSize: '1rem'      },
}

const alignClass = computed(() => {
  const a = d.value.alignment ?? 'left'
  return a === 'center' ? 'text-center' : a === 'right' ? 'text-right' : ''
})

const btnStyle = computed(() => {
  const size      = SIZES[sz.value] ?? SIZES.md
  const bgColor   = d.value.bgColor
  const txtColor  = d.value.textColor
  const bdrColor  = d.value.borderColor
  const radius    = d.value.borderRadius ?? '0.375rem'
  const v         = variant.value

  const base = {
    display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
    gap: '0.5rem', fontWeight: '500', lineHeight: '1.25', cursor: 'pointer',
    textDecoration: 'none', whiteSpace: 'nowrap', borderRadius: radius,
    transition: 'opacity 0.15s, filter 0.15s',
    ...size,
    ...(d.value.fullWidth ? { width: '100%' } : {}),
  }

  if (v === 'solid') {
    return { ...base, backgroundColor: bgColor ?? 'var(--primary)', color: txtColor ?? 'var(--primary-foreground)', border: '1px solid transparent' }
  }
  if (v === 'outline') {
    return { ...base, backgroundColor: 'transparent', color: txtColor ?? bgColor ?? 'var(--primary)', border: `1px solid ${bdrColor ?? bgColor ?? 'var(--primary)'}` }
  }
  if (v === 'ghost') {
    return { ...base, backgroundColor: 'transparent', color: txtColor ?? bgColor ?? 'var(--primary)', border: '1px solid transparent' }
  }
  // link
  return { ...base, backgroundColor: 'transparent', color: txtColor ?? bgColor ?? 'var(--primary)', border: 'none', textDecoration: 'underline', padding: '0' }
})

const iconSty = computed(() => {
  const s = {}
  if (icon.value.size)  s.fontSize = icon.value.size
  if (icon.value.color) s.color    = icon.value.color
  return s
})
</script>
