<!-- resources/js/components/BlockEditor/blocks/AdvancedSettings.vue -->
<template>
  <div class="space-y-3">

    <!-- Device visibility -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1.5">Device visibility</label>
      <div class="flex gap-1.5">
        <button
          v-for="d in DEVICES"
          :key="d.key"
          type="button"
          class="flex-1 flex flex-col items-center gap-1 py-2 rounded-md border text-xs transition-colors"
          :class="visibility[d.key] !== false
            ? 'border-primary/50 bg-primary/10 text-primary'
            : 'border-border bg-muted/30 text-muted-foreground/40 line-through'"
          :title="visibility[d.key] !== false ? `Hide on ${d.label}` : `Show on ${d.label}`"
          @click="toggleVisibility(d.key)"
        >
          <component :is="d.icon" class="w-3.5 h-3.5" />
          <span>{{ d.label }}</span>
        </button>
      </div>
      <p class="text-[10px] text-muted-foreground mt-1.5">Active devices are highlighted. Block is always visible in the editor.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Block label</label>
      <input type="text" :value="block.blockName ?? ''" @input="update('blockName', $event.target.value)"
        placeholder="e.g. Hero heading"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
      <p class="text-[10px] text-muted-foreground mt-1">Shown in the canvas and layers panel.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom ID</label>
      <input type="text" :value="block.customId ?? ''" @input="update('customId', $event.target.value)"
        placeholder="my-section"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom classes</label>
      <input type="text" :value="block.customClasses ?? ''" @input="update('customClasses', $event.target.value)"
        placeholder="my-class another-class"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom CSS</label>
      <CssEditor
        :model-value="block.customCss ?? ''"
        @update:model-value="v => update('customCss', v)"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Scoped to this block automatically.</p>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Smartphone, Tablet, Monitor } from 'lucide-vue-next'
import CssEditor from '../CssEditor.vue'

const DEVICES = [
  { key: 'mobile',  label: 'Mobile',  icon: Smartphone },
  { key: 'tablet',  label: 'Tablet',  icon: Tablet },
  { key: 'desktop', label: 'Desktop', icon: Monitor },
]

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, [key]: value })
}

const visibility = computed(() => props.block.visibility ?? { mobile: true, tablet: true, desktop: true })

function toggleVisibility(key) {
  const current = visibility.value
  emit('update', { id: props.block.id, visibility: { ...current, [key]: current[key] === false ? true : false } })
}
</script>
