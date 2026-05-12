<!-- resources/js/Components/Blocks/CardBlock.vue -->
<template>
  <div :class="cardClass" :style="cardStyle">
    <img
      v-if="resolvedImageUrl"
      :src="resolvedImageUrl"
      :alt="block.data?.imageAlt ?? ''"
      class="w-full object-cover"
      :style="{ aspectRatio: block.data?.imageAspect ?? '16/9' }"
    />
    <div class="p-5 flex flex-col gap-2">
      <p v-if="block.data?.label" class="text-xs font-semibold uppercase tracking-wider text-primary">
        {{ block.data.label }}
      </p>
      <component
        v-if="resolvedTitle"
        :is="'h' + (block.data?.headingLevel ?? 3)"
        class="font-bold leading-snug"
      >{{ resolvedTitle }}</component>
      <p v-if="resolvedText" class="text-sm text-muted-foreground leading-relaxed">
        {{ resolvedText }}
      </p>
      <div v-if="resolvedButtonLabel && resolvedButtonUrl" class="mt-2">
        <a :href="resolvedButtonUrl" :class="btnClass">{{ resolvedButtonLabel }}</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedImageUrl    = useFieldBinding(() => props.block, 'imageUrl')
const resolvedTitle       = useFieldBinding(() => props.block, 'title')
const resolvedText        = useFieldBinding(() => props.block, 'text')
const resolvedButtonLabel = useFieldBinding(() => props.block, 'buttonLabel')
const resolvedButtonUrl   = useFieldBinding(() => props.block, 'buttonUrl')

const variant = computed(() => props.block.data?.variant ?? 'bordered')

const cardClass = computed(() => {
  const base = 'overflow-hidden rounded-xl flex flex-col'
  if (variant.value === 'shadowed') return `${base} shadow-lg bg-card`
  if (variant.value === 'flat')     return `${base} bg-card`
  return `${base} border border-border bg-card`
})

const cardStyle = computed(() => {
  const s = {}
  if (props.block.data?.bgColor) s.backgroundColor = props.block.data.bgColor
  return s
})

const btnVariant = computed(() => props.block.data?.buttonVariant ?? 'filled')
const btnClass = computed(() => {
  const base = 'inline-flex items-center rounded-md px-4 py-2 text-sm font-medium transition-colors'
  if (btnVariant.value === 'outline') return `${base} border border-primary text-primary hover:bg-primary/5`
  if (btnVariant.value === 'ghost')   return `${base} text-primary hover:bg-primary/5`
  return `${base} bg-primary text-primary-foreground hover:bg-primary/90`
})
</script>
