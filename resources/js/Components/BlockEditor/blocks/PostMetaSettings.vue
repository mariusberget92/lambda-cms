<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>
<template>
  <div class="space-y-2 p-3">
    <p class="text-xs font-medium text-muted-foreground mb-2">Show fields</p>
    <label v-for="field in ['date', 'author', 'readTime']" :key="field"
      class="flex items-center gap-2 text-xs cursor-pointer">
      <input type="checkbox"
        :checked="block.data?.[field] !== false"
        @change="update(field, $event.target.checked)"
        class="accent-primary" />
      {{ { date: 'Published date', author: 'Author name', readTime: 'Read time' }[field] }}
    </label>
  </div>
</template>
