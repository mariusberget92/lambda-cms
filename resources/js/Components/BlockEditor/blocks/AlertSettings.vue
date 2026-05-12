<!-- resources/js/Components/BlockEditor/blocks/AlertSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Type</label>
      <div class="grid grid-cols-2 gap-1.5">
        <button
          v-for="[val, lbl, color] in TYPES"
          :key="val"
          type="button"
          class="py-1.5 px-2 text-xs rounded border transition-colors text-left"
          :class="(block.data.type ?? 'info') === val
            ? 'border-primary bg-primary/10 text-primary font-medium'
            : 'border-border bg-background hover:border-muted-foreground'"
          @click="emit('update', { id: block.id, data: { type: val } })"
        >
          <span :class="color" class="font-semibold">{{ lbl }}</span>
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.title" type="text" placeholder="Alert title..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Message</label>
      <textarea :value="block.data.message" placeholder="Alert message..." rows="3"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { message: $event.target.value } })" />
    </div>

    <div class="flex items-center gap-2">
      <input
        :id="`show-icon-${block.id}`"
        type="checkbox"
        :checked="block.data.showIcon !== false"
        class="rounded"
        @change="emit('update', { id: block.id, data: { showIcon: $event.target.checked } })"
      />
      <label :for="`show-icon-${block.id}`" class="text-xs text-muted-foreground cursor-pointer">Show icon</label>
    </div>
  </div>
</template>

<script setup>
const TYPES = [
  ['info',    'Info',    'text-blue-600'],
  ['success', 'Success', 'text-green-600'],
  ['warning', 'Warning', 'text-yellow-600'],
  ['error',   'Error',   'text-red-600'],
]

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
