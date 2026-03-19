<!-- resources/js/Components/BlockEditor/blocks/CodeSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Language</label>
      <select
        :value="block.data.language"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { language: $event.target.value } })"
      >
        <option value="javascript">JavaScript</option>
        <option value="typescript">TypeScript</option>
        <option value="php">PHP</option>
        <option value="python">Python</option>
        <option value="html">HTML</option>
        <option value="css">CSS</option>
        <option value="bash">Bash</option>
        <option value="json">JSON</option>
        <option value="sql">SQL</option>
        <option value="plaintext">Plain text</option>
      </select>
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
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
