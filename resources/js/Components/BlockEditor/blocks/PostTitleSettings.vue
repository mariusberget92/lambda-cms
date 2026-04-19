<script setup>
import TypographyControl from '../TypographyControl.vue'

const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>
<template>
  <div class="p-3">
    <!-- Content fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Tag</label>
        <select :value="block.data?.tag ?? 'h1'" @change="update('tag', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
          <option>h1</option><option>h2</option><option>h3</option>
        </select>
      </div>
    </div>
    <!-- Style tab -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <TypographyControl
        :model-value="block.data?.typography ?? {}"
        @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
      />
    </div>
  </div>
</template>
