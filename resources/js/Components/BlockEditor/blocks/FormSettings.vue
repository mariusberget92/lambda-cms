<!-- resources/js/Components/BlockEditor/blocks/FormSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-4">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Fields</label>
      <div class="space-y-2">
        <div v-for="(field, i) in (block.data.fields ?? [])" :key="field.id" class="rounded border border-border p-2 space-y-1.5">
          <div class="flex items-center gap-1.5">
            <input :value="field.label" type="text" placeholder="Field label"
              class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              @input="updateField(i, 'label', $event.target.value)" />
            <SelectBox size="sm" :model-value="field.type ?? 'text'" :data="FIELD_TYPES" class="w-28 shrink-0"
              @update:model-value="v => updateField(i, 'type', v)" />
            <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1 shrink-0" @click="removeField(i)">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <input :value="field.placeholder" type="text" :placeholder="field.type === 'checkbox' ? 'Checkbox label' : 'Placeholder text'"
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateField(i, 'placeholder', $event.target.value)" />
          <div v-if="field.type === 'select'">
            <label class="text-[10px] text-muted-foreground block mb-1">Options (one per line)</label>
            <textarea :value="(field.options ?? []).join('\n')" rows="3"
              class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring resize-none"
              @change="updateField(i, 'options', $event.target.value.split('\n').map(s => s.trim()).filter(Boolean))" />
          </div>
          <div class="flex items-center gap-2">
            <input :id="`req-${block.id}-${i}`" type="checkbox" :checked="field.required"
              class="rounded" @change="updateField(i, 'required', $event.target.checked)" />
            <label :for="`req-${block.id}-${i}`" class="text-[10px] text-muted-foreground cursor-pointer">Required</label>
          </div>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addField">+ Add field</button>
    </div>

    <hr class="border-border" />

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Submit button label</label>
      <input :value="block.data.submitLabel" type="text" placeholder="Send message"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { submitLabel: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Success message</label>
      <input :value="block.data.successMessage" type="text" placeholder="Thank you!"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { successMessage: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Form action URL <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.action" type="text" placeholder="https://... (leave blank for demo mode)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { action: $event.target.value } })" />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const FIELD_TYPES = [
  { value: 'text',     label: 'Text' },
  { value: 'email',    label: 'Email' },
  { value: 'tel',      label: 'Phone' },
  { value: 'url',      label: 'URL' },
  { value: 'number',   label: 'Number' },
  { value: 'textarea', label: 'Textarea' },
  { value: 'select',   label: 'Select' },
  { value: 'checkbox', label: 'Checkbox' },
]

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])

function updateField(i, key, value) {
  const fields = [...(props.block.data.fields ?? [])]
  fields[i] = { ...fields[i], [key]: value }
  emit('update', { id: props.block.id, data: { fields } })
}
function removeField(i) {
  const fields = [...(props.block.data.fields ?? [])]
  fields.splice(i, 1)
  emit('update', { id: props.block.id, data: { fields } })
}
function addField() {
  const id = `f${Date.now()}`
  const fields = [...(props.block.data.fields ?? []), { id, type: 'text', label: '', placeholder: '', required: false }]
  emit('update', { id: props.block.id, data: { fields } })
}
</script>
