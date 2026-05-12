<!-- resources/js/Components/Blocks/BannerBlock.vue -->
<template>
  <div v-if="!dismissed" :class="bannerClass" role="banner">
    <div class="flex items-center gap-2 flex-1 min-w-0">
      <Icon
        v-if="iconName"
        :icon="iconName"
        class="shrink-0"
        style="font-size: 1.1rem"
        aria-hidden="true"
      />
      <span class="text-sm">{{ block.data?.text }}</span>
      <a
        v-if="block.data?.linkLabel && block.data?.linkUrl"
        :href="block.data.linkUrl"
        class="font-semibold underline underline-offset-2 hover:no-underline ml-1 shrink-0"
      >{{ block.data.linkLabel }} →</a>
    </div>
    <button
      v-if="block.data?.dismissible"
      type="button"
      class="shrink-0 opacity-60 hover:opacity-100 transition-opacity ml-4"
      aria-label="Dismiss"
      @click="dismissed = true"
    >
      <Icon icon="lucide:x" style="font-size: 1rem" />
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const dismissed = ref(false)

const TYPE_CONFIG = {
  info:    { cls: 'bg-blue-600   text-white' },
  success: { cls: 'bg-green-600  text-white' },
  warning: { cls: 'bg-yellow-500 text-white' },
  promo:   { cls: 'bg-primary    text-primary-foreground' },
  neutral: { cls: 'bg-muted      text-foreground border-b border-border' },
}

const typeConfig = computed(() => TYPE_CONFIG[props.block.data?.type ?? 'info'] ?? TYPE_CONFIG.info)
const bannerClass = computed(() => `w-full flex items-center justify-between px-4 py-2.5 ${typeConfig.value.cls}`)
const iconName = computed(() => props.block.data?.icon ?? null)
</script>
