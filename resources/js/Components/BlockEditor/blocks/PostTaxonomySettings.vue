<script setup>
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
      <label class="flex items-center gap-2 text-xs cursor-pointer">
        <input type="checkbox"
          :checked="block.data?.showCategories !== false"
          @change="update('showCategories', $event.target.checked)"
          class="accent-primary" />
        Show categories
      </label>
      <label class="flex items-center gap-2 text-xs cursor-pointer">
        <input type="checkbox"
          :checked="block.data?.showTags !== false"
          @change="update('showTags', $event.target.checked)"
          class="accent-primary" />
        Show tags
      </label>
    </div>
  </div>
</template>
