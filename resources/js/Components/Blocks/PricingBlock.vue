<!-- resources/js/Components/Blocks/PricingBlock.vue -->
<template>
  <div
    class="relative flex flex-col rounded-xl overflow-hidden border"
    :style="cardStyle"
  >
    <!-- Badge -->
    <div v-if="block.data?.badge" class="absolute top-4 right-4">
      <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :style="badgeStyle">{{ block.data.badge }}</span>
    </div>

    <!-- Header -->
    <div class="p-6 pb-4">
      <div v-if="block.data?.eyebrow" class="text-xs font-semibold uppercase tracking-wider mb-1 opacity-60">{{ block.data.eyebrow }}</div>
      <h3 class="text-xl font-bold">{{ block.data?.name || 'Plan' }}</h3>
      <p v-if="block.data?.description" class="text-sm text-muted-foreground mt-1">{{ block.data.description }}</p>
      <div class="mt-5 flex items-end gap-1">
        <span class="text-[2.75rem] font-black leading-none" :style="{ color: accentColor }">
          {{ block.data?.currency || '$' }}{{ block.data?.price ?? '0' }}
        </span>
        <span class="text-muted-foreground text-sm mb-1.5">/ {{ block.data?.period || 'month' }}</span>
      </div>
    </div>

    <div class="border-t border-border mx-6" />

    <!-- Features -->
    <div class="p-6 pt-5 flex-1">
      <ul class="space-y-3">
        <li v-for="(f, i) in features" :key="i" class="flex items-start gap-2.5 text-sm">
          <svg v-if="f.included !== false" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 shrink-0 mt-0.5" :style="{ color: accentColor }">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
          <svg v-else viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 shrink-0 mt-0.5 opacity-25">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
          <span :class="f.included === false ? 'line-through opacity-40' : ''">{{ f.text }}</span>
        </li>
      </ul>
    </div>

    <!-- CTA -->
    <div v-if="block.data?.button?.text" class="px-6 pb-6">
      <a :href="block.data.button.href || '#'" class="block w-full text-center py-3 rounded-lg font-semibold text-sm transition-all" :style="btnStyle">
        {{ block.data.button.text }}
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const features = computed(() => props.block.data?.features || [])
const accentColor = computed(() => props.block.data?.accentColor || 'var(--primary)')

const cardStyle = computed(() => {
  const d = props.block.data || {}
  const style = { background: d.bgColor || 'var(--card)' }
  if (d.featured) {
    style.borderColor = d.accentColor || 'var(--primary)'
    style.borderWidth = '2px'
    style.borderStyle = 'solid'
  } else {
    style.borderColor = 'var(--border)'
    style.borderWidth = '1px'
    style.borderStyle = 'solid'
  }
  return style
})

const badgeStyle = computed(() => ({
  background: props.block.data?.accentColor || 'var(--primary)',
  color: '#fff',
}))

const btnStyle = computed(() => {
  const d = props.block.data || {}
  const color = d.accentColor || 'var(--primary)'
  if (d.button?.variant === 'outline') {
    return { border: `2px solid ${color}`, color, background: 'transparent' }
  }
  return { background: color, color: '#fff' }
})
</script>
