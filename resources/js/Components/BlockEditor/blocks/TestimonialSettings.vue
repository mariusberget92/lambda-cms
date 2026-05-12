<!-- resources/js/Components/BlockEditor/blocks/TestimonialSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Quote text" field-name="text" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <textarea :value="block.data.text" rows="4" placeholder="What they said..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Author name</label>
      <input :value="block.data.authorName" type="text" placeholder="Jane Smith"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { authorName: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Role / Title</label>
      <input :value="block.data.authorRole" type="text" placeholder="CEO"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { authorRole: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Company</label>
      <input :value="block.data.authorCompany" type="text" placeholder="Acme Corp"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { authorCompany: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Avatar URL <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.avatarUrl" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { avatarUrl: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Star rating <span class="opacity-60">(0 = hidden)</span></label>
      <div class="flex gap-1">
        <button
          v-for="n in [0, 1, 2, 3, 4, 5]"
          :key="n"
          type="button"
          class="flex-1 py-1.5 text-xs rounded border transition-colors"
          :class="(block.data.rating ?? 5) === n ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:border-muted-foreground'"
          @click="emit('update', { id: block.id, data: { rating: n } })">
          {{ n === 0 ? '—' : n + '★' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Layout</label>
      <div class="grid grid-cols-3 gap-1.5">
        <button
          v-for="[val, lbl, desc] in [['card', 'Card', 'Bordered card'], ['minimal', 'Minimal', 'Left border'], ['featured', 'Featured', 'Primary bg']]"
          :key="val"
          type="button"
          class="py-2 px-1 text-xs rounded border transition-colors text-center"
          :class="(block.data.layout ?? 'card') === val
            ? 'border-primary bg-primary/10 text-primary font-medium'
            : 'border-border bg-background hover:border-muted-foreground text-muted-foreground'"
          @click="emit('update', { id: block.id, data: { layout: val } })"
        >
          <div class="font-medium mb-0.5">{{ lbl }}</div>
          <div class="text-[9px] opacity-70">{{ desc }}</div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
