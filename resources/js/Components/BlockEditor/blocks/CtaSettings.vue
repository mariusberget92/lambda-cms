<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <div class="space-y-3">
    <div v-for="field in fields" :key="field.key">
      <label class="text-xs font-medium text-muted-foreground block mb-1">{{ field.label }}</label>
      <input
        :value="block.data[field.key]"
        :type="field.type ?? 'text'"
        :placeholder="field.placeholder"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { [field.key]: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

const fields = [
  { key: 'headline',     label: 'Headline',      placeholder: 'Bold headline...' },
  { key: 'text',         label: 'Body text',      placeholder: 'Supporting text...' },
  { key: 'button_label', label: 'Button label',   placeholder: 'Click here' },
  { key: 'button_url',   label: 'Button URL',     placeholder: 'https://...', type: 'url' },
]
</script>
