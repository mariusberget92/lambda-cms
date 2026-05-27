<template>
  <div :style="wrapperStyle">
    <component
      :is="resolvedUrl ? 'a' : 'span'"
      v-bind="resolvedUrl ? { href: resolvedUrl, target: block.data.target || '_self', rel: block.data.rel || undefined } : {}"
      class="btn-block"
      :class="[`btn-block--${variant}`, `btn-block--${size}`, fullWidth ? 'btn-block--full' : '']"
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

const buttonStyle = computed(() => {
  const s = {}
  if (radius.value)    s.borderRadius = radius.value
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

<style scoped>
.btn-block {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
  transition: opacity 150ms, background 150ms, border-color 150ms, color 150ms;
  cursor: pointer;
  text-decoration: none;
  border-radius: var(--blog-radius);
}
.btn-block--full { width: 100%; }

/* Sizes */
.btn-block--sm { padding: 0.375rem 0.75rem; font-size: 0.75rem; }
.btn-block--md { padding: 0.5rem 1.25rem; font-size: 0.875rem; }
.btn-block--lg { padding: 0.75rem 1.75rem; font-size: 1rem; }

/* Filled */
.btn-block--filled { background: var(--accent); color: var(--accent-ink); border: 1px solid transparent; }
.btn-block--filled:hover { opacity: 0.85; }

/* Outline */
.btn-block--outline { background: transparent; color: var(--accent); border: 1px solid var(--accent); }
.btn-block--outline:hover { background: var(--accent); color: var(--accent-ink); }

/* Ghost */
.btn-block--ghost { background: transparent; color: var(--ink); border: 1px solid transparent; }
.btn-block--ghost:hover { background: var(--bg); }
</style>
