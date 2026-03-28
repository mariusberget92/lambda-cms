<script setup>
const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit = defineEmits(['update'])
function update(key, value) {
  emit('update', { data: { ...props.block.data, [key]: value } })
}
</script>

<template>
  <div class="space-y-3 p-3">
    <!-- Content fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Placeholder</label>
        <input type="text" :value="block.data?.placeholder ?? 'Search…'"
          @input="update('placeholder', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs" />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
        <input type="text" :value="block.data?.buttonLabel ?? 'Search'"
          @input="update('buttonLabel', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs" />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Scope</label>
        <select :value="block.data?.scope ?? 'posts'"
          @change="update('scope', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
          <option value="posts">Posts only</option>
          <option value="all">Posts + Pages</option>
        </select>
      </div>
    </div>
  </div>
</template>
