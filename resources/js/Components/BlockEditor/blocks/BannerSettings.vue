<!-- resources/js/Components/BlockEditor/blocks/BannerSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Type</label>
      <div class="grid grid-cols-3 gap-1">
        <button v-for="[val, lbl, cls] in TYPES" :key="val" type="button"
          class="py-1.5 text-xs rounded border transition-colors font-medium"
          :class="(block.data.type ?? 'info') === val
            ? 'border-primary bg-primary/10 text-primary'
            : 'border-border bg-background hover:border-muted-foreground text-muted-foreground'"
          @click="emit('update', { id: block.id, data: { type: val } })">
          <span :class="cls">{{ lbl }}</span>
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text</label>
      <input :value="block.data.text" type="text" placeholder="Announcement text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </div>

    <div class="flex gap-2">
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Link label</label>
        <input :value="block.data.linkLabel" type="text" placeholder="Learn more"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { linkLabel: $event.target.value } })" />
      </div>
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Link URL</label>
        <input :value="block.data.linkUrl" type="text" placeholder="https://..."
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { linkUrl: $event.target.value } })" />
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon <span class="opacity-60">(optional)</span></label>
      <IconPickerInput :model-value="block.data.icon ?? null"
        @update:model-value="v => emit('update', { id: block.id, data: { icon: v || null } })" />
    </div>

    <div class="flex items-center gap-2">
      <input :id="`dismissible-${block.id}`" type="checkbox" :checked="block.data.dismissible"
        class="rounded"
        @change="emit('update', { id: block.id, data: { dismissible: $event.target.checked } })" />
      <label :for="`dismissible-${block.id}`" class="text-xs text-muted-foreground cursor-pointer">Dismissible</label>
    </div>
  </div>
</template>

<script setup>
import IconPickerInput from '../IconPickerInput.vue'

const TYPES = [
  ['info',    'Info',    'text-blue-600'],
  ['success', 'Success', 'text-green-600'],
  ['warning', 'Warning', 'text-yellow-600'],
  ['promo',   'Promo',   'text-purple-600'],
  ['neutral', 'Neutral', 'text-foreground'],
]

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])
</script>
