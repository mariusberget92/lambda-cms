<!-- resources/js/Components/BlockEditor/blocks/CodeSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Language</label>
      <SelectBox
        :model-value="block.data.language"
        :data="[
          { value: 'javascript', label: 'JavaScript' },
          { value: 'typescript', label: 'TypeScript' },
          { value: 'php',        label: 'PHP' },
          { value: 'python',     label: 'Python' },
          { value: 'html',       label: 'HTML' },
          { value: 'css',        label: 'CSS' },
          { value: 'bash',       label: 'Bash' },
          { value: 'json',       label: 'JSON' },
          { value: 'sql',        label: 'SQL' },
          { value: 'plaintext',  label: 'Plain text' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { language: v } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Code</label>
      <textarea
        :value="block.data.code"
        rows="8"
        placeholder="Paste code here..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
        @input="emit('update', { id: block.id, data: { code: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
