<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>
<template>
  <div class="p-3">
    <label class="text-xs font-medium text-muted-foreground block mb-1">Tag</label>
    <select :value="block.data?.tag ?? 'h1'" @change="update('tag', $event.target.value)"
      class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
      <option>h1</option><option>h2</option><option>h3</option>
    </select>
  </div>
</template>
