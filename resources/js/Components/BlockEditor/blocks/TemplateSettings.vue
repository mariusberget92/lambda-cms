<!-- resources/js/Components/BlockEditor/blocks/TemplateSettings.vue -->
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ExternalLink } from 'lucide-vue-next'

const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
  'partial':        'Partial',
}

const sharedTemplates = computed(() => usePage().props.sharedTemplates ?? [])

// Group templates by type for the <select> optgroups
const groups = computed(() => {
  const map = {}
  for (const t of sharedTemplates.value) {
    if (!map[t.type]) map[t.type] = []
    map[t.type].push(t)
  }
  return Object.entries(map).map(([type, items]) => ({
    label: TYPE_LABELS[type] ?? type,
    items,
  }))
})

const selectedId = computed(() => props.block.data?.template_id ?? null)

const editUrl = computed(() =>
  selectedId.value ? `/templates/${selectedId.value}/edit` : null
)

function update(value) {
  emit('update', { data: { ...props.block.data, template_id: value ? Number(value) : null } })
}
</script>

<template>
  <div class="space-y-3 p-3">
    <div v-show="!tab || tab === 'content'" class="space-y-3">

      <!-- No templates exist yet -->
      <div v-if="sharedTemplates.length === 0" class="rounded-md border border-dashed p-4 text-center">
        <p class="text-xs text-muted-foreground">No published templates yet.</p>
        <a
          href="/templates"
          target="_blank"
          class="mt-1 inline-flex items-center gap-1 text-xs text-primary hover:underline"
        >
          Create one in Templates <ExternalLink class="w-3 h-3" />
        </a>
      </div>

      <!-- Template selector -->
      <template v-else>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Template</label>
          <select
            :value="selectedId"
            @change="update($event.target.value || null)"
            class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs"
          >
            <option value="">— Select a template —</option>
            <optgroup
              v-for="group in groups"
              :key="group.label"
              :label="group.label"
            >
              <option
                v-for="t in group.items"
                :key="t.id"
                :value="t.id"
              >
                {{ t.title }}
              </option>
            </optgroup>
          </select>
        </div>

        <a
          v-if="editUrl"
          :href="editUrl"
          target="_blank"
          class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
        >
          Edit template <ExternalLink class="w-3 h-3" />
        </a>
      </template>

    </div>
  </div>
</template>
