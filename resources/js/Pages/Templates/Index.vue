<!-- resources/js/Pages/Templates/Index.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { LayoutTemplate, Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { formatDate } from '@/lib/utils.js'

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

const deleteTarget = ref(null)

function confirmDelete(template) {
  deleteTarget.value = template
}

function deleteTemplate() {
  if (!deleteTarget.value) return
  router.delete(route('templates.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
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
                  <th class="px-4 py-3 w-10"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="template in templates[type]"
                  :key="template.id"
                  class="border-b last:border-0 hover:bg-muted/30 transition-colors group"
                >
                  <td class="px-4 py-3 font-medium">{{ template.name }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="template.status" />
                  </td>
                  <td class="px-4 py-3 text-muted-foreground">{{ formatDate(template.updated_at) }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <a
                        :href="route('templates.edit', template.id)"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                        title="Edit"
                      >
                        <Pencil class="w-3.5 h-3.5" />
                      </a>
                      <button
                        type="button"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                        title="Delete"
                        @click="confirmDelete(template)"
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

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete template?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.name }}</span>" will be permanently deleted.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="deleteTemplate"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
