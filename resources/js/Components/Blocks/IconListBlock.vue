<!-- resources/js/Components/Blocks/IconListBlock.vue -->
<template>
  <ul
    :class="[
      direction === 'horizontal'
        ? 'flex flex-wrap'
        : 'flex flex-col',
    ]"
    :style="{ gap: block.data.gap ?? '0.75rem' }"
  >
    <li
      v-for="(item, i) in items"
      :key="i"
      class="flex items-start"
      :style="{ gap: block.data.iconGap ?? '0.6em' }"
    >
      <Icon
        v-if="item.icon"
        :icon="item.icon"
        class="shrink-0 mt-[0.15em]"
        :style="iconStyle(item)"
        aria-hidden="true"
      />
      <span class="text-sm leading-snug">{{ item.text }}</span>
    </li>
  </ul>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const items     = computed(() => props.block.data?.items     ?? [])
const direction = computed(() => props.block.data?.direction ?? 'vertical')

function iconStyle(item) {
  const s = {}
  const size  = item.iconSize  ?? props.block.data?.iconSize  ?? '1.1em'
  const color = item.iconColor ?? props.block.data?.iconColor ?? null
  if (size)  s.fontSize = size
  if (color) s.color    = color
  return s
}
</script>
