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
  blue:   { bg: 'color-mix(in srgb, #5e81ac 15%, transparent)', fg: '#5e81ac' },
  green:  { bg: 'color-mix(in srgb, #a3be8c 20%, transparent)', fg: '#638a47' },
  cyan:   { bg: 'color-mix(in srgb, #88c0d0 15%, transparent)', fg: '#4a8fa0' },
  yellow: { bg: 'color-mix(in srgb, #ebcb8b 20%, transparent)', fg: '#a07c20' },
  red:    { bg: 'color-mix(in srgb, #bf616a 15%, transparent)', fg: '#bf616a' },
  purple: { bg: 'color-mix(in srgb, #b48ead 15%, transparent)', fg: '#8a5f89' },
}
</script>
