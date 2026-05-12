<!-- resources/js/Components/Blocks/FeatureBlock.vue -->
<template>
  <div :class="wrapperClass">
    <!-- Icon -->
    <div v-if="iconName" :class="iconWrapperClass" :style="iconWrapperStyle">
      <Icon :icon="iconName" :style="iconStyle" aria-hidden="true" />
    </div>
    <!-- Text -->
    <div :class="textWrapperClass">
      <component
        v-if="resolvedTitle"
        :is="'h' + (block.data?.headingLevel ?? 3)"
        class="font-semibold leading-snug mb-1"
      >{{ resolvedTitle }}</component>
      <p v-if="resolvedText" class="text-sm text-muted-foreground leading-relaxed">{{ resolvedText }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedTitle = useFieldBinding(() => props.block, 'title')
const resolvedText  = useFieldBinding(() => props.block, 'text')

const layout  = computed(() => props.block.data?.layout  ?? 'vertical')
const iconName = computed(() => props.block.data?.icon   ?? null)

const wrapperClass = computed(() =>
  layout.value === 'horizontal'
    ? 'flex items-start gap-4'
    : 'flex flex-col items-start gap-3'
)

const textWrapperClass = computed(() => layout.value === 'horizontal' ? 'flex-1' : '')

const iconWrapperClass = computed(() => {
  const base = 'flex items-center justify-center rounded-xl shrink-0'
  return props.block.data?.iconBgColor ? `${base} p-2.5` : base
})

const iconWrapperStyle = computed(() => {
  const s = {}
  if (props.block.data?.iconBgColor) s.backgroundColor = props.block.data.iconBgColor
  return s
})

const iconStyle = computed(() => {
  const s = { fontSize: props.block.data?.iconSize ?? '1.75rem' }
  if (props.block.data?.iconColor) s.color = props.block.data.iconColor
  else s.color = 'var(--primary)'
  return s
})
</script>
