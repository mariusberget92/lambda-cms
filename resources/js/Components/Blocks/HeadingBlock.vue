<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component
    :is="'h' + block.data.level"
    class="font-bold leading-tight"
    :style="hasIcon ? { display: 'flex', alignItems: 'center', gap: icon.gap ?? '0.5em' } : undefined"
  >
    <Icon
      v-if="hasIcon && (icon.position ?? 'prefix') !== 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      aria-hidden="true"
    />
    <span>{{ resolvedText }}</span>
    <Icon
      v-if="hasIcon && icon.position === 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      aria-hidden="true"
    />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedText = useFieldBinding(() => props.block, 'text')

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
