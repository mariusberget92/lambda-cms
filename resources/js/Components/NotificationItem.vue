<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { X, CircleCheck, CircleX, TriangleAlert, Info } from '@lucide/vue'

const props = defineProps({
  id:       { type: Number, required: true },
  type:     { type: String, default: 'info' },
  message:  { type: String, required: true },
  duration: { type: Number, default: null },
  actions:  { type: Array, default: () => [] },
  items:    { type: Array, default: () => [] },
})
const emit = defineEmits(['dismiss'])

const ACCENT = { success: '#22c55e', error: '#e05368', warning: '#f59e0b', info: '#3898ec' }
const ICONS  = { success: CircleCheck, error: CircleX, warning: TriangleAlert, info: Info }

const accent = ACCENT[props.type] ?? ACCENT.info
const Icon   = ICONS[props.type]  ?? Info

const progressWidth = ref(100)
let timer = null

onMounted(() => {
  if (props.duration !== null) {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => { progressWidth.value = 0 })
    })
    timer = setTimeout(() => emit('dismiss', props.id), props.duration)
  }
})

onBeforeUnmount(() => {
  if (timer) clearTimeout(timer)
})

function handleAction(handler) {
  if (typeof handler === 'function') handler()
  emit('dismiss', props.id)
}
</script>

<template>
  <div class="notification-toast relative w-80 rounded-lg border bg-card text-card-foreground overflow-hidden"
       :style="{ '--toast-accent': accent }">
    <!-- Accent strip -->
    <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-lg" :style="{ backgroundColor: accent }" />
    <!-- Icon + message -->
    <div class="flex items-start gap-2.5 p-3.5 pl-4 pr-9">
      <div class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center mt-0.5"
           :style="{ backgroundColor: accent + '18' }">
        <component :is="Icon" class="w-3.5 h-3.5" :style="{ color: accent }" />
      </div>
      <div class="min-w-0 flex-1">
        <span class="text-sm font-medium leading-snug">{{ message }}</span>
        <ul v-if="items.length" class="mt-1.5 space-y-0.5 list-disc list-inside">
          <li v-for="(item, i) in items" :key="i" class="text-xs text-muted-foreground leading-snug">
            {{ item }}
          </li>
        </ul>
      </div>
    </div>
    <!-- Actions -->
    <div v-if="actions.length" class="flex gap-2 px-3.5 pl-4 pb-3">
      <button
        type="button"
        v-for="action in actions"
        :key="action.label"
        class="text-xs font-medium hover:underline"
        :style="{ color: accent }"
        @click="handleAction(action.handler)"
      >{{ action.label }}</button>
    </div>
    <!-- Progress bar -->
    <div v-if="duration !== null" class="h-0.5 bg-border/30">
      <div class="h-full rounded-full"
           :style="{ backgroundColor: accent, width: progressWidth + '%', transition: `width ${duration}ms linear` }" />
    </div>
    <!-- Dismiss -->
    <button type="button" aria-label="Dismiss"
            class="absolute top-3 right-3 w-5 h-5 rounded-md flex items-center justify-center text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
            @click="emit('dismiss', id)">
      <X class="w-3 h-3" />
    </button>
  </div>
</template>
