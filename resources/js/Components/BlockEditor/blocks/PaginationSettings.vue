<!-- resources/js/Components/BlockEditor/blocks/PaginationSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Page URL param</label>
      <input
        :value="block.data.pageParam ?? 'page'"
        type="text"
        placeholder="page"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { pageParam: $event.target.value } })"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Must match the URL param key set in the paired Loop block's filter.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Style</label>
      <SelectBox size="sm"
        :model-value="block.data.style ?? 'prev-next'"
        :data="[
          { value: 'prev-next', label: 'Prev / Next only' },
          { value: 'numbered',  label: 'Numbered (shows current page)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { style: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Previous label</label>
      <input
        :value="block.data.prevLabel ?? '← Previous'"
        type="text"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { prevLabel: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Next label</label>
      <input
        :value="block.data.nextLabel ?? 'Next →'"
        type="text"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { nextLabel: $event.target.value } })"
      />
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex gap-1">
        <button
          v-for="al in ['left', 'center', 'right']"
          :key="al"
          type="button"
          class="flex-1 px-2 py-1 text-xs rounded border capitalize transition-colors"
          :class="(block.data.alignment ?? 'center') === al
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background border-border hover:border-muted-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: al } })"
        >{{ al }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button style</label>
      <SelectBox size="sm"
        :model-value="block.data.buttonStyle ?? 'outline'"
        :data="[
          { value: 'outline', label: 'Outline' },
          { value: 'ghost',   label: 'Ghost' },
          { value: 'solid',   label: 'Solid' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { buttonStyle: v } })"
      />
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
