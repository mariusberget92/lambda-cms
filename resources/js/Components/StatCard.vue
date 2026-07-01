<template>
  <component
    :is="href ? 'a' : 'div'"
    :href="href || undefined"
    class="rounded-xl border bg-card p-5 flex flex-col"
    :class="href ? 'hover:shadow-md transition-shadow cursor-pointer' : ''"
    style="box-shadow: var(--shadow-sm)"
  >
    <div class="flex items-center justify-between">
      <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
      <div
        class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
        :style="{ backgroundColor: colorMap[color]?.bg, color: colorMap[color]?.fg }"
      >
        <slot name="icon" />
      </div>
    </div>
    <p class="text-2xl font-bold mt-3 text-foreground">{{ value }}</p>
  </component>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  color: { type: String, default: 'blue', validator: (v) => ['blue','green','cyan','yellow','red','purple'].includes(v) },
  href:  { type: String, default: '' },
})

const colorMap = {
  blue:   { bg: 'color-mix(in srgb, #3898ec 12%, transparent)', fg: '#3898ec' },
  green:  { bg: 'color-mix(in srgb, #22c55e 12%, transparent)', fg: '#16a34a' },
  cyan:   { bg: 'color-mix(in srgb, #06b6d4 12%, transparent)', fg: '#0891b2' },
  yellow: { bg: 'color-mix(in srgb, #f59e0b 12%, transparent)', fg: '#d97706' },
  red:    { bg: 'color-mix(in srgb, #e05368 10%, transparent)', fg: '#e05368' },
  purple: { bg: 'color-mix(in srgb, #8b5cf6 12%, transparent)', fg: '#7c3aed' },
}
</script>
