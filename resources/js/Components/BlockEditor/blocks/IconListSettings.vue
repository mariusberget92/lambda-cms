<!-- resources/js/Components/BlockEditor/blocks/IconListSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <!-- Items -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Items</label>
      <div class="space-y-2">
        <div
          v-for="(item, idx) in items"
          :key="idx"
          class="rounded-md border border-border bg-background/40 p-2 space-y-2"
        >
          <div class="flex items-center gap-2">
            <span class="text-[10px] text-muted-foreground w-5 shrink-0 text-right">{{ idx + 1 }}.</span>
            <IconPickerInput
              :model-value="item.icon || null"
              class="flex-1"
              @update:model-value="v => updateItem(idx, 'icon', v || null)"
            />
            <button
              type="button"
              class="text-muted-foreground hover:text-destructive transition-colors ml-1"
              title="Remove item"
              @click="removeItem(idx)"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
          <input
            :value="item.text"
            type="text"
            placeholder="Item text"
            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(idx, 'text', $event.target.value)"
          />
        </div>
      </div>
      <button
        type="button"
        class="mt-2 w-full rounded-md border border-dashed border-border py-1.5 text-xs text-muted-foreground hover:text-foreground hover:border-muted-foreground transition-colors flex items-center justify-center gap-1"
        @click="addItem"
      >
        <Plus class="w-3.5 h-3.5" /> Add Item
      </button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">
    <!-- Direction -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Direction</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="d in ['vertical','horizontal']" :key="d" type="button"
          class="flex-1 py-1.5 capitalize transition-colors"
          :class="(block.data.direction ?? 'vertical') === d ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { direction: d } })"
        >{{ d }}</button>
      </div>
    </div>

    <!-- Item gap -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap between items</label>
      <DimensionInput
        :model-value="block.data.gap ?? '0.75rem'"
        placeholder="0.75rem"
        @update:model-value="v => emit('update', { id: block.id, data: { gap: v || '0.75rem' } })"
      />
    </div>

    <!-- Icon-text gap -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon / text gap</label>
      <DimensionInput
        :model-value="block.data.iconGap ?? '0.6em'"
        placeholder="0.6em"
        @update:model-value="v => emit('update', { id: block.id, data: { iconGap: v || '0.6em' } })"
      />
    </div>

    <!-- Default icon size -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Default icon size</label>
      <DimensionInput
        :model-value="block.data.iconSize ?? '1.1em'"
        placeholder="1.1em"
        @update:model-value="v => emit('update', { id: block.id, data: { iconSize: v || '1.1em' } })"
      />
    </div>

    <!-- Default icon color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Default icon color</label>
      <ColorPicker
        :model-value="block.data.iconColor"
        :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { iconColor: v } })"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Plus, X } from '@lucide/vue'
import IconPickerInput from '../IconPickerInput.vue'
import ColorPicker     from '../ColorPicker.vue'
import DimensionInput  from '../DimensionInput.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const items = computed(() => props.block.data?.items ?? [])

function updateItem(idx, key, value) {
  const updated = items.value.map((item, i) =>
    i === idx ? { ...item, [key]: value } : item
  )
  emit('update', { id: props.block.id, data: { items: updated } })
}

function removeItem(idx) {
  const updated = items.value.filter((_, i) => i !== idx)
  emit('update', { id: props.block.id, data: { items: updated } })
}

function addItem() {
  const updated = [...items.value, { icon: 'lucide:check', text: '' }]
  emit('update', { id: props.block.id, data: { items: updated } })
}
</script>
