<!-- resources/js/Components/Blocks/CounterBlock.vue -->
<template>
  <div ref="root" class="flex flex-wrap gap-8" :class="alignClass">
    <div v-for="(stat, i) in stats" :key="i" class="flex flex-col" :class="itemAlignClass">
      <div class="text-4xl font-extrabold leading-tight tabular-nums" :style="{ color: stat.color || 'var(--primary)' }">
        {{ stat.prefix || '' }}{{ displayed[i].toLocaleString() }}{{ stat.suffix || '' }}
      </div>
      <div v-if="stat.label" class="text-sm text-muted-foreground mt-1">{{ stat.label }}</div>
    </div>
    <div v-if="stats.length === 0" class="text-muted-foreground text-sm w-full text-center py-4">
      No stats added yet
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, onUnmounted } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const root = ref(null)
const stats = computed(() => props.block.data?.stats || [])
const displayed = reactive(Array(20).fill(0))

const alignment = computed(() => props.block.data?.alignment || 'center')
const alignClass = computed(() => ({
  'justify-start':  alignment.value === 'left',
  'justify-center': alignment.value === 'center',
  'justify-end':    alignment.value === 'right',
}))
const itemAlignClass = computed(() => ({
  'items-start text-left':   alignment.value === 'left',
  'items-center text-center': alignment.value === 'center',
  'items-end text-right':    alignment.value === 'right',
}))

function animateCount(index, target, duration = 1800) {
  const start = performance.now()
  const step = (now) => {
    const t = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - t, 4)
    displayed[index] = Math.round(target * eased)
    if (t < 1) requestAnimationFrame(step)
    else displayed[index] = target
  }
  requestAnimationFrame(step)
}

let observer = null
onMounted(() => {
  observer = new IntersectionObserver(([entry]) => {
    if (entry.isIntersecting) {
      stats.value.forEach((s, i) => animateCount(i, Number(s.value) || 0))
      observer.disconnect()
    }
  }, { threshold: 0.3 })
  if (root.value) observer.observe(root.value)
})
onUnmounted(() => observer?.disconnect())
</script>
