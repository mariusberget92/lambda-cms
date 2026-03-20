<!-- resources/js/components/BlockEditor/LayerItem.vue -->
<template>
  <div>
    <!-- Layer row -->
    <div
      class="flex items-center gap-1.5 rounded-md px-1.5 py-1.5 cursor-pointer transition-colors text-xs"
      :class="block.id === selectedId
        ? 'bg-primary text-primary-foreground'
        : 'hover:bg-accent text-foreground'"
      @click="$emit('select', block.id)"
    >
      <span
        class="layer-handle cursor-grab active:cursor-grabbing shrink-0"
        :class="block.id === selectedId ? 'text-primary-foreground/60' : 'text-muted-foreground'"
        @click.stop
      >
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
        </svg>
      </span>

      <span class="flex-1 truncate">{{ LABELS[block.type] ?? block.type }}</span>

      <button
        type="button"
        class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Remove block"
        @click.stop="$emit('remove', block.id)"
      >
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Indented children (container blocks only) -->
    <ul v-if="block.type === 'container' && block.children?.length" class="pl-4 space-y-0.5 mt-0.5">
      <li v-for="child in block.children" :key="child.id">
        <LayerItem
          :block="child"
          :selected-id="selectedId"
          @select="$emit('select', $event)"
          @remove="$emit('remove', $event)"
        />
      </li>
    </ul>
  </div>
</template>

<script setup>
const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container',
}

defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

defineEmits(['select', 'remove'])
</script>
