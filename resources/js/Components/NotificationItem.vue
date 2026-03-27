<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { X, CircleCheck, CircleX, TriangleAlert, Info } from 'lucide-vue-next'

const props = defineProps({
  id:       { type: Number, required: true },
  type:     { type: String, default: 'info' },
  message:  { type: String, required: true },
  duration: { type: Number, default: null },
  actions:  { type: Array, default: () => [] },
  items:    { type: Array, default: () => [] },
})
const emit = defineEmits(['dismiss'])

const ACCENT = { success: '#a3be8c', error: '#bf616a', warning: '#ebcb8b', info: '#5e81ac' }
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
  <div class="relative w-80 rounded-md border shadow-md bg-background overflow-hidden"
       :style="{ borderLeftColor: accent, borderLeftWidth: '4px' }">
    <!-- Icon + message -->
    <div class="flex items-start gap-2 p-3 pr-8">
      <component :is="Icon" class="w-4 h-4 mt-0.5 shrink-0" :style="{ color: accent }" />
      <div class="min-w-0 flex-1">
        <span class="text-sm leading-snug">{{ message }}</span>
        <ul v-if="items.length" class="mt-1.5 space-y-0.5 list-disc list-inside">
          <li v-for="(item, i) in items" :key="i" class="text-xs text-muted-foreground leading-snug">
            {{ item }}
          </li>
        </ul>
      </div>
    </div>
    <!-- Actions -->
    <div v-if="actions.length" class="flex gap-2 px-3 pb-2">
      <button
        type="button"
        v-for="action in actions"
        :key="action.label"
        class="text-xs underline"
        @click="handleAction(action.handler)"
      >{{ action.label }}</button>
    </div>
    <!-- Progress bar -->
    <div v-if="duration !== null"
         class="h-0.5"
         :style="{ backgroundColor: accent, width: progressWidth + '%', transition: `width ${duration}ms linear` }" />
    <!-- Dismiss -->
    <button type="button" aria-label="Dismiss" class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
            @click="emit('dismiss', id)">
      <X class="w-3.5 h-3.5" />
    </button>
  </div>
</template>
