<script setup>
const props = defineProps({ block: { type: Object, required: true } })

const TONE_COLORS = {
  positive: 'oklch(0.62 0.16 142)',
  negative: 'oklch(0.62 0.16 25)',
  neutral:  'var(--soft)',
}

const trendColor = (tone) => TONE_COLORS[tone] || TONE_COLORS.neutral
</script>

<template>
  <div
    class="flex flex-col gap-1 p-5"
    :style="{
      background: 'var(--panel)',
      border: '1px solid var(--line-strong)',
      borderRadius: 'var(--blog-radius, 6px)',
    }"
  >
    <!-- Big number -->
    <div
      class="font-display font-bold leading-none tabular-nums"
      :style="{
        fontFamily: '\'Space Grotesk\', sans-serif',
        fontVariantNumeric: 'tabular-nums',
        fontSize: 'clamp(1.75rem, 4vw, 2.5rem)',
        color: 'var(--ink)',
        letterSpacing: '-0.03em',
      }"
    >{{ block.data?.value || '—' }}</div>

    <!-- Label -->
    <p class="font-mono-blog text-[11px] uppercase tracking-widest mt-1" style="color:var(--soft);">
      {{ block.data?.label || 'metric' }}
    </p>

    <!-- Trend -->
    <p
      v-if="block.data?.trend"
      class="font-mono-blog text-[11px] mt-1"
      :style="{ color: trendColor(block.data?.trendTone) }"
    >{{ block.data.trend }}</p>
  </div>
</template>
