<template>
  <div>
    <!-- Label row -->
    <div v-if="d.showLabel !== false || d.showValue !== false" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.375rem;">
      <span v-if="d.showLabel !== false && d.label" :style="{ fontSize: '0.875rem', fontWeight: '500', color: 'var(--foreground)' }">{{ d.label }}</span>
      <span v-if="d.showValue !== false" :style="{ fontSize: '0.8125rem', fontWeight: '600', color: d.color ?? 'var(--primary)' }">{{ value }}%</span>
    </div>

    <!-- Track -->
    <div :style="trackStyle">
      <div :style="barStyle" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const d = computed(() => props.block.data ?? {})

const value    = computed(() => Math.min(100, Math.max(0, Number(d.value.value ?? 0))))
const animated = ref(false)

onMounted(() => {
  if (d.value.animated !== false) {
    requestAnimationFrame(() => { animated.value = true })
  } else {
    animated.value = true
  }
})

const trackStyle = computed(() => ({
  height: d.value.height ?? '8px',
  backgroundColor: d.value.trackColor ?? 'var(--muted)',
  borderRadius: '9999px',
  overflow: 'hidden',
}))

const barStyle = computed(() => ({
  height: '100%',
  width: animated.value ? `${value.value}%` : '0%',
  backgroundColor: d.value.color ?? 'var(--primary)',
  borderRadius: '9999px',
  transition: d.value.animated !== false ? 'width 1s ease-in-out' : 'none',
  backgroundImage: d.value.striped
    ? 'repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.15) 10px, rgba(255,255,255,0.15) 20px)'
    : 'none',
}))
</script>
