<!-- resources/js/Components/BlockEditor/blocks/ConditionSettings.vue -->
<template>
  <div class="space-y-2 pt-3 border-t mt-3">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Visibility Condition</p>

    <label class="flex items-center gap-2 text-xs cursor-pointer">
      <EditorCheckbox
        :model-value="hasCondition"
        @update:model-value="toggleCondition"
      />
      Show only if…
    </label>

    <template v-if="hasCondition">
      <SelectBox size="sm"
        :model-value="condition.field"
        :data="fieldOptions"
        placeholder="Field..."
        @update:model-value="v => update({ field: v })"
      />
      <SelectBox size="sm"
        :model-value="condition.op"
        :data="OPS"
        @update:model-value="v => update({ op: v })"
      />
      <input
        v-if="condition.op !== 'not_empty' && condition.op !== 'empty'"
        :value="condition.value ?? ''"
        type="text"
        placeholder="Value..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="update({ value: $event.target.value })"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'
import EditorCheckbox from '../EditorCheckbox.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})

const emit = defineEmits(['update'])

const OPS = [
  { value: '=',         label: 'Equals' },
  { value: '!=',        label: 'Not equals' },
  { value: 'not_empty', label: 'Is not empty' },
  { value: 'empty',     label: 'Is empty' },
]

const fieldOptions = computed(() => props.loopFields)
const hasCondition = computed(() => !!props.block.condition)
const condition    = computed(() => props.block.condition ?? { field: '', op: '=', value: '' })

function toggleCondition(checked) {
  // 'condition' is a top-level block attr (not inside data) — goes into updateBlock(...attrs)
  emit('update', {
    id:        props.block.id,
    condition: checked ? { field: '', op: '=', value: '' } : null,
  })
}

function update(patch) {
  emit('update', { id: props.block.id, condition: { ...condition.value, ...patch } })
}
</script>
