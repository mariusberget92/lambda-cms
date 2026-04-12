<!-- resources/js/Components/Blocks/TemplateBlock.vue -->
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const partials = computed(() => usePage().props.partials ?? [])

const partial = computed(() =>
  partials.value.find(p => p.id === props.block.data?.template_id) ?? null
)
</script>

<template>
  <!-- Partial found: render its blocks inline, inheriting any loop context -->
  <BlockRenderer
    v-if="partial"
    :blocks="partial.blocks ?? []"
    wrapper-class="contents"
  />

  <!-- No partial selected or partial was deleted/unpublished -->
  <div
    v-else
    class="rounded-lg border border-dashed border-border/60 px-4 py-6 text-center text-sm text-muted-foreground"
  >
    {{ block.data?.template_id ? 'Partial not found (deleted or unpublished)' : 'No partial selected — choose one in settings' }}
  </div>
</template>
