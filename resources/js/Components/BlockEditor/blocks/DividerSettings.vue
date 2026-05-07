<!-- resources/js/Components/BlockEditor/blocks/DividerSettings.vue -->
<template>
  <div>

    <!-- Style tab fields -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-2">Style</label>
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            class="rounded-md border px-2 py-3 text-center text-xs transition-colors"
            :class="block.data.style === opt.value
              ? 'border-primary bg-primary text-primary-foreground font-medium'
              : 'border-border bg-background text-muted-foreground hover:border-muted-foreground'"
            @click="emit('update', { id: block.id, data: { style: opt.value } })"
          >
            <div class="mb-1">{{ opt.preview }}</div>
            <div>{{ opt.label }}</div>
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
defineProps({
  block: { type: Object, required: true },
  tab: { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit = defineEmits(['update'])

const options = [
  { value: 'line',  label: 'Line',  preview: '——' },
  { value: 'dots',  label: 'Dots',  preview: '· · ·' },
  { value: 'space', label: 'Space', preview: '∅' },
]
</script>
