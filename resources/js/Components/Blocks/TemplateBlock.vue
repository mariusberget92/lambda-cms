<!-- resources/js/Components/Blocks/TemplateBlock.vue -->
<script setup>
import { computed, inject, provide } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

// Depth guard — prevents infinite loops when templates embed each other
const depth = inject('templateDepth', 0)
const MAX_DEPTH = 10
provide('templateDepth', depth + 1)

const sharedTemplates = computed(() => usePage().props.sharedTemplates ?? [])

const template = computed(() =>
  sharedTemplates.value.find(t => t.id === props.block.data?.template_id) ?? null
)
</script>

<template>
  <!-- Depth limit exceeded -->
  <div
    v-if="depth >= MAX_DEPTH"
    class="rounded-lg border border-dashed border-destructive/40 px-4 py-6 text-center text-sm text-destructive/70"
  >
    Template nesting limit reached
  </div>

  <!-- Template found: render its blocks inline, inheriting any loop context -->
  <BlockRenderer
    v-else-if="template"
    :blocks="template.blocks ?? []"
    wrapper-class="contents"
  />

  <!-- No template selected or template was deleted/unpublished -->
  <div
    v-else
    class="rounded-lg border border-dashed border-border/60 px-4 py-6 text-center text-sm text-muted-foreground"
  >
    {{ block.data?.template_id ? 'Template not found (deleted or unpublished)' : 'No template selected — choose one in settings' }}
  </div>
</template>
