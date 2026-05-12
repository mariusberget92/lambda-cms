<!-- resources/js/Components/Blocks/AlertBlock.vue -->
<template>
  <div :class="alertClass" role="alert">
    <Icon
      v-if="block.data?.showIcon !== false"
      :icon="typeConfig.icon"
      class="shrink-0 mt-0.5"
      style="font-size: 1.1rem"
      aria-hidden="true"
    />
    <div class="flex-1 min-w-0">
      <p v-if="block.data?.title" class="font-semibold text-sm mb-0.5">{{ block.data.title }}</p>
      <p v-if="block.data?.message" class="text-sm leading-relaxed opacity-90">{{ block.data.message }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const TYPE_CONFIG = {
  info:    { cls: 'bg-blue-50   border-blue-200   text-blue-900   dark:bg-blue-950/30   dark:border-blue-800   dark:text-blue-100',   icon: 'lucide:info' },
  success: { cls: 'bg-green-50  border-green-200  text-green-900  dark:bg-green-950/30  dark:border-green-800  dark:text-green-100',  icon: 'lucide:circle-check' },
  warning: { cls: 'bg-yellow-50 border-yellow-200 text-yellow-900 dark:bg-yellow-950/30 dark:border-yellow-800 dark:text-yellow-100', icon: 'lucide:triangle-alert' },
  error:   { cls: 'bg-red-50    border-red-200    text-red-900    dark:bg-red-950/30    dark:border-red-800    dark:text-red-100',    icon: 'lucide:circle-x' },
}

const typeConfig = computed(() => TYPE_CONFIG[props.block.data?.type ?? 'info'] ?? TYPE_CONFIG.info)
const alertClass = computed(() => `flex items-start gap-3 rounded-lg border px-4 py-3 ${typeConfig.value.cls}`)
</script>
