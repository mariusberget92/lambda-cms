<!-- resources/js/Pages/Pages/Index.vue -->
<script setup>
import AppLayout  from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref }    from 'vue'

const props = defineProps({
  pages: Object, // paginated
})

const deletingId = ref(null)

function confirmDelete(page) {
  if (!confirm(`Delete "${page.title}"? This cannot be undone.`)) return
  deletingId.value = page.id
  router.delete(route('pages.destroy', page.id), {
    onFinish: () => { deletingId.value = null },
  })
}

const columns = [
  { key: 'title',      label: 'Title' },
  { key: 'slug',       label: 'Slug' },
  { key: 'status',     label: 'Status' },
  { key: 'created_at', label: 'Created' },
]
</script>

<template>
  <AppLayout title="Pages">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Pages</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage custom site pages</p>
      </div>
      <a
        :href="route('pages.create')"
        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
      >
        New page
      </a>
    </div>

    <div class="rounded-lg border bg-card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="border-b bg-muted/50">
          <tr>
            <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-left font-medium text-muted-foreground">
              {{ col.label }}
            </th>
            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="page in pages.data"
            :key="page.id"
            class="border-b last:border-0 hover:bg-muted/30 transition-colors"
          >
            <td class="px-4 py-3 font-medium">{{ page.title }}</td>
            <td class="px-4 py-3 text-muted-foreground font-mono text-xs">/{{ page.slug }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="page.status === 'published'
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                  : 'bg-muted text-muted-foreground'"
              >
                {{ page.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-muted-foreground">{{ page.created_at }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <a
                  :href="route('pages.edit', page.id)"
                  class="text-xs font-medium text-primary hover:underline"
                >Edit</a>
                <button
                  type="button"
                  class="text-xs font-medium text-destructive hover:underline"
                  :disabled="deletingId === page.id"
                  @click="confirmDelete(page)"
                >
                  {{ deletingId === page.id ? 'Deleting...' : 'Delete' }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!pages.data.length">
            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground text-sm">
              No pages yet. <a :href="route('pages.create')" class="text-primary hover:underline">Create one.</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
