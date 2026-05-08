<!-- resources/js/Components/BlockEditor/blocks/PricingSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Plan name</label>
      <input
        :value="block.data.name || ''"
        type="text"
        placeholder="Starter"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { name: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Eyebrow text</label>
      <input
        :value="block.data.eyebrow || ''"
        type="text"
        placeholder="Best value"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { eyebrow: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Description</label>
      <textarea
        :value="block.data.description || ''"
        rows="2"
        placeholder="Short plan description…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { description: $event.target.value } })"
      />
    </div>

    <div class="grid grid-cols-3 gap-2">
      <div>
        <label class="text-xs text-muted-foreground block mb-1">Currency</label>
        <input
          :value="block.data.currency || '$'"
          type="text"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { currency: $event.target.value } })"
        />
      </div>
      <div>
        <label class="text-xs text-muted-foreground block mb-1">Price</label>
        <input
          :value="block.data.price ?? 0"
          type="text"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { price: $event.target.value } })"
        />
      </div>
      <div>
        <label class="text-xs text-muted-foreground block mb-1">Period</label>
        <input
          :value="block.data.period || 'month'"
          type="text"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { period: $event.target.value } })"
        />
      </div>
    </div>

    <!-- Features -->
    <hr class="border-white/8" />
    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Features</label>
      <button type="button" class="text-xs px-2 py-1 rounded-md bg-primary/20 text-primary hover:bg-primary/30 transition-colors" @click="addFeature">+ Add</button>
    </div>

    <div v-for="(f, i) in features" :key="i" class="flex items-center gap-2">
      <EditorCheckbox :model-value="f.included !== false" @update:model-value="v => updateFeature(i, 'included', v)" />
      <input
        :value="f.text || ''"
        type="text"
        placeholder="Feature…"
        class="flex-1 min-w-0 rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="updateFeature(i, 'text', $event.target.value)"
      />
      <button type="button" class="shrink-0 text-destructive hover:opacity-80" @click="removeFeature(i)">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- CTA Button -->
    <hr class="border-white/8" />
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">CTA button label</label>
      <input
        :value="block.data.button?.text || ''"
        type="text"
        placeholder="Get started"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="updateButton('text', $event.target.value)"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">CTA button URL</label>
      <input
        :value="block.data.button?.href || ''"
        type="text"
        placeholder="https://…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="updateButton('href', $event.target.value)"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="v in ['solid', 'outline']"
          :key="v"
          type="button"
          class="flex-1 py-1.5 capitalize transition-colors"
          :class="(block.data.button?.variant ?? 'solid') === v ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="updateButton('variant', v)"
        >{{ v }}</button>
      </div>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="tab === 'style'" class="space-y-3">
    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.featured ?? false" @update:model-value="v => emit('update', { id: block.id, data: { featured: v } })" />
      <span class="text-xs text-muted-foreground">Featured / highlighted plan</span>
    </label>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Badge text</label>
      <input
        :value="block.data.badge || ''"
        type="text"
        placeholder="Most popular"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { badge: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Accent color</label>
      <ColorPicker :model-value="block.data.accentColor || ''" @update:model-value="v => emit('update', { id: block.id, data: { accentColor: v } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Card background</label>
      <ColorPicker :model-value="block.data.bgColor || ''" @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v } })" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import EditorCheckbox from '../EditorCheckbox.vue'
import ColorPicker    from '../ColorPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const features = computed(() => props.block.data?.features || [])

function addFeature() {
  emit('update', { id: props.block.id, data: { features: [...features.value, { text: '', included: true }] } })
}
function removeFeature(i) {
  emit('update', { id: props.block.id, data: { features: features.value.filter((_, idx) => idx !== i) } })
}
function updateFeature(i, key, value) {
  const updated = features.value.map((f, idx) => idx === i ? { ...f, [key]: value } : f)
  emit('update', { id: props.block.id, data: { features: updated } })
}
function updateButton(key, value) {
  emit('update', { id: props.block.id, data: { button: { ...(props.block.data.button ?? {}), [key]: value } } })
}
</script>
