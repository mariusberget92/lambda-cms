<!-- resources/js/Components/Blocks/ButtonBlock.vue -->
<template>
  <div :class="['flex', ALIGN_MAP[block.data?.align ?? 'left']]">
    <a
      :href="resolvedUrl || '#'"
      :target="block.data?.target ?? '_self'"
      :class="btnClass"
      :style="btnStyle"
    >
      <Icon
        v-if="iconName && iconPosition !== 'suffix'"
        :icon="iconName"
        class="shrink-0"
        :style="{ fontSize: block.data?.icon?.size ?? '1em' }"
        aria-hidden="true"
      />
      {{ resolvedLabel || 'Click here' }}
      <Icon
        v-if="iconName && iconPosition === 'suffix'"
        :icon="iconName"
        class="shrink-0"
        :style="{ fontSize: block.data?.icon?.size ?? '1em' }"
        aria-hidden="true"
      />
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedLabel = useFieldBinding(() => props.block, 'label')
const resolvedUrl   = useFieldBinding(() => props.block, 'url')

const ALIGN_MAP = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }

const SIZE_CLASS = { sm: 'px-3 py-1.5 text-xs', md: 'px-5 py-2 text-sm', lg: 'px-7 py-3 text-base' }

const variant  = computed(() => props.block.data?.variant ?? 'filled')
const size     = computed(() => props.block.data?.size    ?? 'md')
const iconName = computed(() => props.block.data?.icon?.name ?? null)
const iconPosition = computed(() => props.block.data?.icon?.position ?? 'prefix')

const btnClass = computed(() => {
  const base = 'inline-flex items-center gap-2 rounded-md font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring'
  const sz = SIZE_CLASS[size.value] ?? SIZE_CLASS.md
  if (variant.value === 'outline') return `${base} ${sz} border border-primary text-primary bg-transparent hover:bg-primary/5`
  if (variant.value === 'ghost')   return `${base} ${sz} bg-transparent text-foreground hover:bg-muted`
  return `${base} ${sz} bg-primary text-primary-foreground hover:bg-primary/90`
})

const btnStyle = computed(() => {
  const d = props.block.data ?? {}
  const s = {}
  if (d.bgColor)      s.backgroundColor = d.bgColor
  if (d.textColor)    s.color           = d.textColor
  if (d.borderRadius) s.borderRadius    = d.borderRadius
  return s
})
</script>
