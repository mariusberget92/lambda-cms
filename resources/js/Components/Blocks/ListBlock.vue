<!-- resources/js/Components/Blocks/ListBlock.vue -->
<template>
  <component :is="listTag" :class="listClass">
    <li
      v-for="(item, i) in block.data?.items ?? []"
      :key="i"
      :class="itemClass"
    >
      <Icon
        v-if="style === 'check'"
        icon="lucide:check"
        class="shrink-0 text-primary"
        style="font-size: 1rem; margin-top: 0.15em"
        aria-hidden="true"
      />
      <Icon
        v-else-if="style === 'arrow'"
        icon="lucide:arrow-right"
        class="shrink-0 text-primary"
        style="font-size: 1rem; margin-top: 0.15em"
        aria-hidden="true"
      />
      <Icon
        v-else-if="style === 'x'"
        icon="lucide:x"
        class="shrink-0 text-destructive"
        style="font-size: 1rem; margin-top: 0.15em"
        aria-hidden="true"
      />
      <span>{{ item }}</span>
    </li>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const style   = computed(() => props.block.data?.style   ?? 'bullet')
const spacing = computed(() => props.block.data?.spacing ?? 'normal')

const SPACING_MAP = { compact: 'space-y-0.5', normal: 'space-y-1.5', loose: 'space-y-3' }

const listTag = computed(() => style.value === 'numbered' ? 'ol' : 'ul')

const listClass = computed(() => {
  const sp = SPACING_MAP[spacing.value] ?? SPACING_MAP.normal
  if (style.value === 'numbered') return `${sp} list-decimal list-inside`
  if (style.value === 'bullet')   return `${sp} list-disc list-inside`
  return sp
})

const itemClass = computed(() => {
  if (['check', 'arrow', 'x'].includes(style.value)) return 'flex items-start gap-2 text-sm'
  return 'text-sm'
})
</script>
