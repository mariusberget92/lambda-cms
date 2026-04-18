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
  <div class="space-y-2 p-3">
    <!-- Content fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-2">
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
    <!-- Style tab -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <TypographyControl
        :model-value="block.data?.typography ?? {}"
        @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
      />
    </div>
  </div>
</template>
