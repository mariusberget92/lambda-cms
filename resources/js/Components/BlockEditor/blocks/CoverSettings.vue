<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Variant</label>
      <SelectBox size="sm"
        :model-value="block.data.variant ?? 'flat'"
        :data="[
          { value: 'flat',   label: 'Flat' },
          { value: 'stripe', label: 'Stripe' },
          { value: 'frame',  label: 'Frame' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { variant: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Glyph (category word)</label>
      <input
        :value="block.data.glyph"
        type="text"
        placeholder="essays"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { glyph: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">File path</label>
      <input
        :value="block.data.filepath"
        type="text"
        placeholder="~/lambdacms/posts/slug.md"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
        @input="emit('update', { id: block.id, data: { filepath: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Callsign</label>
      <input
        :value="block.data.callsign"
        type="text"
        placeholder="λ.14"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
        @input="emit('update', { id: block.id, data: { callsign: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Issue №</label>
      <input
        :value="block.data.issueNo"
        type="text"
        placeholder="№ 14"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
        @input="emit('update', { id: block.id, data: { issueNo: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Category hue (0–360)</label>
      <div class="flex items-center gap-2">
        <input
          type="range"
          min="0" max="360"
          :value="block.data.hue ?? 220"
          class="flex-1 h-1.5 rounded appearance-none cursor-pointer"
          @input="emit('update', { id: block.id, data: { hue: Number($event.target.value) } })"
        />
        <div
          class="w-6 h-6 rounded-full shrink-0"
          :style="{ background: `oklch(0.62 0.16 ${block.data.hue ?? 220})` }"
        />
        <span class="font-mono text-xs text-muted-foreground w-8">{{ block.data.hue ?? 220 }}</span>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <input
        type="checkbox"
        id="cover-status-ok"
        :checked="block.data.statusOk !== false"
        @change="emit('update', { id: block.id, data: { statusOk: $event.target.checked } })"
      />
      <label for="cover-status-ok" class="text-xs text-muted-foreground">Show "build · ok" status bar</label>
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
