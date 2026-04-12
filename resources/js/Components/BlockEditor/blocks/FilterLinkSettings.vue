<!-- resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue -->
<script setup>
const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
function update(key, value) {
  emit('update', { data: { ...props.block.data, [key]: value } })
}
</script>

<template>
  <div class="space-y-3 p-3">
    <div v-show="!tab || tab === 'content'" class="space-y-3">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">URL param name</label>
        <input
          type="text"
          :value="block.data?.paramName ?? 'category'"
          placeholder="category"
          @input="update('paramName', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs"
        />
        <p class="text-[10px] text-muted-foreground mt-1">
          Sets <code class="bg-muted px-1 rounded">?{{ block.data?.paramName || 'category' }}=slug</code> in the URL when clicked.
        </p>
      </div>
    </div>
  </div>
</template>
