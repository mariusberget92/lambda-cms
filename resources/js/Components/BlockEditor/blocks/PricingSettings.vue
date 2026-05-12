<!-- resources/js/Components/BlockEditor/blocks/PricingSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Plan name</label>
      <input :value="block.data.title" type="text" placeholder="Pro"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </div>
    <div class="flex gap-2">
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Price</label>
        <input :value="block.data.price" type="text" placeholder="$29"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { price: $event.target.value } })" />
      </div>
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Period</label>
        <input :value="block.data.period" type="text" placeholder="/month"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { period: $event.target.value } })" />
      </div>
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Description</label>
      <input :value="block.data.description" type="text" placeholder="Short plan description..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { description: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Badge <span class="opacity-60">(e.g. "Most popular")</span></label>
      <input :value="block.data.badge" type="text" placeholder="Most popular"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { badge: $event.target.value } })" />
    </div>
    <div class="flex gap-2">
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
        <input :value="block.data.buttonLabel" type="text" placeholder="Get started"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { buttonLabel: $event.target.value } })" />
      </div>
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button URL</label>
        <input :value="block.data.buttonUrl" type="text" placeholder="#"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emit('update', { id: block.id, data: { buttonUrl: $event.target.value } })" />
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Features</label>
      <div class="space-y-1.5">
        <div v-for="(feat, i) in (block.data.features ?? [])" :key="i" class="flex items-center gap-1.5">
          <button type="button"
            class="w-5 h-5 rounded shrink-0 flex items-center justify-center border transition-colors text-[10px]"
            :class="feat.included !== false ? 'bg-primary/10 border-primary text-primary' : 'bg-muted border-border text-muted-foreground'"
            :title="feat.included !== false ? 'Included' : 'Not included'"
            @click="updateFeature(i, 'included', feat.included === false ? true : false)">
            {{ feat.included !== false ? '✓' : '✕' }}
          </button>
          <input :value="feat.text" type="text" placeholder="Feature description"
            class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateFeature(i, 'text', $event.target.value)" />
          <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1 shrink-0" @click="removeFeature(i)">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addFeature">+ Add feature</button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div class="flex items-center gap-2">
      <input :id="`featured-${block.id}`" type="checkbox" :checked="block.data.featured"
        class="rounded"
        @change="emit('update', { id: block.id, data: { featured: $event.target.checked } })" />
      <label :for="`featured-${block.id}`" class="text-xs text-muted-foreground cursor-pointer">Featured / highlighted plan</label>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])

function updateFeature(i, key, value) {
  const features = [...(props.block.data.features ?? [])]
  features[i] = { ...features[i], [key]: value }
  emit('update', { id: props.block.id, data: { features } })
}
function removeFeature(i) {
  const features = [...(props.block.data.features ?? [])]
  features.splice(i, 1)
  emit('update', { id: props.block.id, data: { features } })
}
function addFeature() {
  const features = [...(props.block.data.features ?? []), { text: '', included: true }]
  emit('update', { id: props.block.id, data: { features } })
}
</script>
