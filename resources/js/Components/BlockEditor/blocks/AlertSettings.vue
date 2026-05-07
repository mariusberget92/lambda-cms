<!-- resources/js/Components/BlockEditor/blocks/AlertSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Type</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="t in ['info', 'success', 'warning', 'error']"
          :key="t"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.type ?? 'info') === t ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { type: t } })"
        >{{ t }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input
        :value="block.data.title ?? ''"
        type="text"
        placeholder="Optional title…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Message</label>
      <textarea
        :value="block.data.message ?? ''"
        rows="3"
        placeholder="Alert message…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { message: $event.target.value } })"
      />
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.showIcon !== false" @update:model-value="v => emit('update', { id: block.id, data: { showIcon: v } })" />
      <span class="text-xs text-muted-foreground">Show icon</span>
    </label>
  </div>
</template>

<script setup>
import EditorCheckbox from '../EditorCheckbox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
