<!-- resources/js/Components/BlockEditor/blocks/IconBoxSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input
        :value="block.data.title ?? ''"
        type="text"
        placeholder="Feature title…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Description</label>
      <textarea
        :value="block.data.description ?? ''"
        rows="2"
        placeholder="Short description…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { description: $event.target.value } })"
      />
    </div>

    <!-- Icon -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon</label>
      <IconPickerInput
        :model-value="block.data.icon?.name ?? null"
        @update:model-value="v => updateIcon('name', v || null)"
      />
    </div>

    <template v-if="block.data.icon?.name">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Icon size</label>
        <input
          :value="block.data.icon?.size ?? '2rem'"
          type="text"
          placeholder="2rem"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="updateIcon('size', $event.target.value || '2rem')"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Icon color</label>
        <ColorPicker
          :model-value="block.data.icon?.color ?? ''"
          default="var(--primary)"
          :show-reset="true"
          @update:model-value="v => updateIcon('color', v || null)"
        />
      </div>
    </template>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Layout</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="[val, label] in [['vertical', 'Vertical'], ['horizontal', 'Horizontal']]"
          :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.layout ?? 'vertical') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { layout: val } })"
        >{{ label }}</button>
      </div>
    </div>

    <div v-if="(block.data.layout ?? 'vertical') === 'vertical'">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="al in ['left', 'center', 'right']"
          :key="al"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.alignment ?? 'center') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: al } })"
        >{{ al }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="[val, label] in [['plain', 'Plain'], ['boxed', 'Boxed'], ['circle', 'Circle']]"
          :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.iconStyle ?? 'plain') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { iconStyle: val } })"
        >{{ label }}</button>
      </div>
    </div>

    <div v-if="(block.data.iconStyle ?? 'plain') !== 'plain'">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon background</label>
      <ColorPicker
        :model-value="block.data.icon?.bgColor ?? ''"
        default="var(--primary)"
        :show-reset="true"
        @update:model-value="v => updateIcon('bgColor', v || null)"
      />
    </div>
  </div>
</template>

<script setup>
import IconPickerInput from '../IconPickerInput.vue'
import ColorPicker     from '../ColorPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

function updateIcon(key, value) {
  emit('update', { id: props.block.id, data: { icon: { ...(props.block.data.icon ?? {}), [key]: value } } })
}
</script>
