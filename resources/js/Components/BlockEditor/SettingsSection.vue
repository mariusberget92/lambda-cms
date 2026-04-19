<script setup>
import { ref } from 'vue'
import { ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  label:       { type: String,  required: true },
  defaultOpen: { type: Boolean, default: false },
})

const open = ref(props.defaultOpen)
</script>

<template>
  <div class="border-t border-white/8">
    <button
      type="button"
      class="w-full flex items-center justify-between py-2.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
      @click="open = !open"
    >
      <span>{{ label }}</span>
      <ChevronRight
        class="w-3.5 h-3.5 shrink-0 transition-transform duration-150"
        :class="open ? 'rotate-90' : ''"
      />
    </button>
    <div v-if="open" class="pb-3 space-y-3">
      <slot />
    </div>
  </div>
</template>
