<!-- resources/js/Pages/Templates/Index.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { LayoutTemplate, Plus, Pencil, Trash2 } from 'lucide-vue-next'

const props = defineProps({
  templates: Object, // grouped by type
})

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
}

const ALL_TYPES = ['blog-index', 'single-post', 'archive', 'search-results']

const deletingId = ref(null)

function deleteTemplate(id) {
  if (!confirm('Delete this template?')) return
  deletingId.value = id
  router.delete(route('templates.destroy', id), {
    onFinish: () => { deletingId.value = null },
  })
}

function hasAny() {
  return ALL_TYPES.some(t => props.templates[t]?.length > 0)
}
</script>

<template>
  <AppLayout title="Templates">
    <Head title="Templates" />

    <div class="mb-4">
      <h2 class="text-lg font-semibold">Templates</h2>
      <p class="text-sm text-muted-foreground mt-0.5">Manage site layout templates</p>
    </div>

    <!-- New template buttons per type -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
      <span class="text-sm text-muted-foreground shrink-0">New template:</span>
      <a
        v-for="type in ALL_TYPES"
        :key="type"
        :href="route('templates.create', { type })"
        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-accent transition-colors"
      >
        <Plus class="w-3.5 h-3.5" />
        {{ TYPE_LABELS[type] }}
      </a>
    </div>

    <!-- Grouped by type -->
    <div v-if="hasAny()" class="space-y-6">
      <div v-for="type in ALL_TYPES" :key="type">
        <template v-if="templates[type]?.length > 0">
          <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-2 px-1">
            {{ TYPE_LABELS[type] }}
          </h3>
          <div class="rounded-lg border bg-card overflow-hidden">
            <table class="w-full text-sm">
              <thead class="border-b bg-muted/50">
                <tr>
                  <th class="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                  <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                  <th class="px-4 py-3 text-left font-medium text-muted-foreground">Updated</th>
                  <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="template in templates[type]"
                  :key="template.id"
                  class="border-b last:border-0 hover:bg-muted/30 transition-colors"
                >
                  <td class="px-4 py-3 font-medium">{{ template.name }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="template.status" />
                  </td>
                  <td class="px-4 py-3 text-muted-foreground">{{ template.updated_at }}</td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <a
                        :href="route('templates.edit', template.id)"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                        title="Edit"
                      >
                        <Pencil class="w-3.5 h-3.5" />
                      </a>
                      <button
                        type="button"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-md text-muted-foreground hover:text-destructive hover:bg-accent transition-colors"
                        :disabled="deletingId === template.id"
                        title="Delete"
                        @click="deleteTemplate(template.id)"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="rounded-lg border bg-card px-6 py-12 text-center">
      <LayoutTemplate class="w-10 h-10 mx-auto text-muted-foreground/40 mb-3" />
      <p class="text-sm text-muted-foreground mb-4">No templates yet. Create one using the buttons above.</p>
    </div>

  </AppLayout>
</template>
