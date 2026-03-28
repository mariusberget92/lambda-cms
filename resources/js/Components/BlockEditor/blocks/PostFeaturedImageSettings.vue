<script setup>
const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>
<template>
  <div class="space-y-3 p-3">
    <!-- Style fields -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
        <input type="text" :value="block.data?.maxWidth ?? '100%'"
          @input="update('maxWidth', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs" />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Aspect ratio</label>
        <select :value="block.data?.aspectRatio ?? 'auto'" @change="update('aspectRatio', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
          <option value="auto">Auto</option>
          <option value="16/9">16:9</option>
          <option value="4/3">4:3</option>
          <option value="1/1">1:1</option>
        </select>
      </div>
    </div>
  </div>
</template>
