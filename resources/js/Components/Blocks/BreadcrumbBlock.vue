<!-- resources/js/Components/Blocks/BreadcrumbBlock.vue -->
<template>
  <nav aria-label="Breadcrumb">
    <ol class="flex items-center flex-wrap gap-1 text-sm">
      <li v-for="(item, i) in block.data?.items ?? []" :key="i" class="flex items-center gap-1">
        <span v-if="i > 0" :class="separatorClass" aria-hidden="true">{{ separatorChar }}</span>
        <a
          v-if="item.url && i < (block.data.items.length - 1)"
          :href="item.url"
          class="text-muted-foreground hover:text-foreground transition-colors"
        >{{ item.label }}</a>
        <span v-else class="text-foreground font-medium" :aria-current="i === block.data.items.length - 1 ? 'page' : undefined">
          {{ item.label }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const separator = computed(() => props.block.data?.separator ?? 'chevron')

const separatorChar = computed(() => ({ slash: '/', chevron: '›', dot: '·' }[separator.value] ?? '›'))
const separatorClass = 'text-muted-foreground/50 select-none'
</script>
