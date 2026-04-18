<script setup>
import TypographyControl from '../TypographyControl.vue'
import EditorCheckbox from '../EditorCheckbox.vue'

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
        <EditorCheckbox
          :model-value="block.data?.[field] !== false"
          @update:model-value="v => update(field, v)"
        />
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
