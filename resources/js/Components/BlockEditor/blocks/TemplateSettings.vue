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

const partials = computed(() => usePage().props.partials ?? [])

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

      <!-- No partials exist yet -->
      <div v-if="partials.length === 0" class="rounded-md border border-dashed p-4 text-center">
        <p class="text-xs text-muted-foreground">No partials yet.</p>
        <a
          href="/templates"
          target="_blank"
          class="mt-1 inline-flex items-center gap-1 text-xs text-primary hover:underline"
        >
          Create one in Templates <ExternalLink class="w-3 h-3" />
        </a>
      </div>

      <!-- Partial selector -->
      <template v-else>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Partial</label>
          <select
            :value="selectedId"
            @change="update($event.target.value || null)"
            class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs"
          >
            <option value="">— Select a partial —</option>
            <option
              v-for="p in partials"
              :key="p.id"
              :value="p.id"
            >
              {{ p.title }}
            </option>
          </select>
        </div>

        <a
          v-if="editUrl"
          :href="editUrl"
          target="_blank"
          class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
        >
          Edit partial <ExternalLink class="w-3 h-3" />
        </a>
      </template>

    </div>
  </div>
</template>
