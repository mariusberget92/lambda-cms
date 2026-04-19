<!-- resources/js/Components/Blocks/ParagraphBlock.vue -->
<template>
  <div
    :style="hasIcon ? { display: 'flex', alignItems: 'flex-start', gap: icon.gap ?? '0.5em' } : undefined"
  >
    <Icon
      v-if="hasIcon && (icon.position ?? 'prefix') !== 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      class="shrink-0 mt-[0.2em]"
      aria-hidden="true"
    />
    <div class="prose prose-sm max-w-none dark:prose-invert" v-html="resolvedContent" />
    <Icon
      v-if="hasIcon && icon.position === 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      class="shrink-0 mt-[0.2em]"
      aria-hidden="true"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedContent = useFieldBinding(() => props.block, 'content')

const icon    = computed(() => props.block.data?.icon ?? null)
const hasIcon = computed(() => !!(icon.value?.name))

const iconStyle = computed(() => {
  if (!icon.value) return {}
  const s = {}
  if (icon.value.size)  s.fontSize = icon.value.size
  if (icon.value.color) s.color    = icon.value.color
  return s
})
</script>
