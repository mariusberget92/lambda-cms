<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <p class="text-xs text-muted-foreground">In a loop, the chip binds automatically to the current category. Use the fields below for standalone chips.</p>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
      <input
        :value="block.data.label"
        type="text"
        placeholder="Category name"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Slug (for link)</label>
      <input
        :value="block.data.slug"
        type="text"
        placeholder="category-slug"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { slug: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Hue (0–360)</label>
      <div class="flex items-center gap-2">
        <input
          type="range"
          min="0" max="360"
          :value="block.data.hue ?? 220"
          class="flex-1 h-1.5 rounded appearance-none cursor-pointer"
          @input="emit('update', { id: block.id, data: { hue: Number($event.target.value) } })"
        />
        <div class="w-6 h-6 rounded-full shrink-0" :style="{ background: `oklch(0.62 0.16 ${block.data.hue ?? 220})` }" />
        <span class="font-mono text-xs text-muted-foreground w-8">{{ block.data.hue ?? 220 }}</span>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <input
        type="checkbox"
        id="chip-active"
        :checked="block.data.active ?? false"
        @change="emit('update', { id: block.id, data: { active: $event.target.checked } })"
      />
      <label for="chip-active" class="text-xs text-muted-foreground">Active state (filled)</label>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit  = defineEmits(['update'])
</script>
