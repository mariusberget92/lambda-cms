<!-- resources/js/Components/Blocks/IconBlock.vue -->
<template>
  <div v-if="block.data?.name" :class="['flex', ALIGN_MAP[block.data?.align ?? 'center']]">
    <Icon
      :icon="block.data.name"
      :style="iconStyle"
      aria-hidden="true"
    />
  </div>
  <div v-else class="flex items-center justify-center text-muted-foreground/30 text-sm py-4 border border-dashed rounded">
    No icon selected
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const ALIGN_MAP = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }

const iconStyle = computed(() => {
  const d = props.block.data ?? {}
  const s = { fontSize: d.size ?? '2.5rem' }
  if (d.color) s.color = d.color
  return s
})
</script>
