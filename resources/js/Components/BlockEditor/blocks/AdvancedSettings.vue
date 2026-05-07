<!-- resources/js/components/BlockEditor/blocks/AdvancedSettings.vue -->
<template>
  <div class="space-y-3">

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
import CssEditor from '../CssEditor.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, [key]: value })
}
</script>
