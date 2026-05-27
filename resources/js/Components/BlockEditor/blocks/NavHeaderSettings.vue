<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Logo text <span class="font-normal">(leave blank to use site name)</span></label>
      <input
        :value="block.data.logoText"
        type="text"
        placeholder="Uses site name by default"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { logoText: $event.target.value } })"
      />
    </div>

    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Show search (⌘K)</label>
      <button
        type="button"
        class="relative inline-flex h-5 w-9 rounded-full border-2 border-transparent transition-colors"
        :class="block.data.showSearch !== false ? 'bg-primary' : 'bg-muted'"
        @click="emit('update', { id: block.id, data: { showSearch: block.data.showSearch === false } })"
      >
        <span
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
          :class="block.data.showSearch !== false ? 'translate-x-4' : 'translate-x-0'"
        />
      </button>
    </div>

    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Sticky navbar</label>
      <button
        type="button"
        class="relative inline-flex h-5 w-9 rounded-full border-2 border-transparent transition-colors"
        :class="block.data.sticky !== false ? 'bg-primary' : 'bg-muted'"
        @click="emit('update', { id: block.id, data: { sticky: block.data.sticky === false } })"
      >
        <span
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
          :class="block.data.sticky !== false ? 'translate-x-4' : 'translate-x-0'"
        />
      </button>
    </div>

    <p class="text-[11px] text-muted-foreground/60 leading-relaxed pt-1">
      Nav links are managed under <strong>Settings → Navigation</strong>.
    </p>
  </div>
</template>

<script setup>
const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
