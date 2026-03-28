<script setup>
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
      <label class="flex items-center gap-2 text-xs cursor-pointer">
        <input type="checkbox"
          :checked="block.data?.showAvatar !== false"
          @change="update('showAvatar', $event.target.checked)"
          class="accent-primary" />
        Show avatar
      </label>
    </div>
  </div>
</template>
