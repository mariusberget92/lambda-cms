<!-- resources/js/Components/Blocks/CountdownBlock.vue -->
<template>
  <div :class="['text-center', block.data?.align === 'left' ? 'text-left' : block.data?.align === 'right' ? 'text-right' : 'text-center']">
    <p v-if="block.data?.title" class="font-semibold text-base mb-4">{{ block.data.title }}</p>

    <div v-if="!expired" class="flex flex-wrap gap-4" :class="block.data?.align === 'center' || !block.data?.align ? 'justify-center' : block.data?.align === 'right' ? 'justify-end' : 'justify-start'">
      <div v-if="block.data?.showDays !== false" :class="unitClass">
        <div :class="valueClass">{{ pad(timeLeft.days) }}</div>
        <div :class="labelClass">Days</div>
      </div>
      <div v-if="block.data?.showHours !== false" :class="unitClass">
        <div :class="valueClass">{{ pad(timeLeft.hours) }}</div>
        <div :class="labelClass">Hours</div>
      </div>
      <div v-if="block.data?.showMinutes !== false" :class="unitClass">
        <div :class="valueClass">{{ pad(timeLeft.minutes) }}</div>
        <div :class="labelClass">Minutes</div>
      </div>
      <div v-if="block.data?.showSeconds !== false" :class="unitClass">
        <div :class="valueClass">{{ pad(timeLeft.seconds) }}</div>
        <div :class="labelClass">Seconds</div>
      </div>
    </div>

    <p v-else class="text-muted-foreground">{{ block.data?.expiredMessage || 'The event has started!' }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const timeLeft = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 })
const expired  = ref(false)
let timer = null

function calc() {
  const target = props.block.data?.targetDate ? new Date(props.block.data.targetDate).getTime() : 0
  if (!target) { expired.value = false; return }
  const diff = target - Date.now()
  if (diff <= 0) { expired.value = true; timeLeft.value = { days: 0, hours: 0, minutes: 0, seconds: 0 }; return }
  expired.value = false
  timeLeft.value = {
    days:    Math.floor(diff / 86400000),
    hours:   Math.floor((diff % 86400000) / 3600000),
    minutes: Math.floor((diff % 3600000)  / 60000),
    seconds: Math.floor((diff % 60000)    / 1000),
  }
}

function pad(n) { return String(n).padStart(2, '0') }

const style    = computed(() => props.block.data?.style ?? 'box')
const unitClass  = computed(() => style.value === 'box' ? 'flex flex-col items-center rounded-xl border border-border bg-card px-5 py-4 min-w-[5rem]' : 'flex flex-col items-center min-w-[4rem]')
const valueClass = computed(() => 'text-3xl font-bold tabular-nums leading-none')
const labelClass = 'text-xs text-muted-foreground mt-1 uppercase tracking-wider'

onMounted(() => { calc(); timer = setInterval(calc, 1000) })
onUnmounted(() => clearInterval(timer))
</script>
