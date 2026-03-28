<!-- resources/js/Pages/Pages/Index.vue -->
<script setup>
import AppLayout  from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref }    from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import DataTable from '@/Components/DataTable.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

const props = defineProps({
  pages: Object, // paginated
})

const deleteTarget = ref(null)

function confirmDelete(page) {
  deleteTarget.value = page
}

function deletePage() {
  if (!deleteTarget.value) return
  router.delete(route('pages.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}


</script>

<template>
  <AppLayout title="Pages">
    <Head title="Pages" />

    <div class="mb-4">
      <h2 class="text-lg font-semibold">Pages</h2>
      <p class="text-sm text-muted-foreground mt-0.5">Manage custom site pages</p>
    </div>

    <div class="flex items-center gap-3 mb-4">
      <a
        :href="route('pages.create')"
        class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
      >
        New page
      </a>
    </div>

    <DataTable :loading="false" :empty="!pages.data.length">
      <template #empty>
        No pages yet. <a :href="route('pages.create')" class="text-primary hover:underline">Create one.</a>
      </template>
      <template #headers>
        <th class="text-left">Title</th>
        <th class="text-left hidden sm:table-cell">Slug</th>
        <th class="text-left hidden sm:table-cell">Status</th>
        <th class="text-left hidden md:table-cell">Created</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="page in pages.data"
          :key="page.id"
          class="hover:bg-muted/30 transition-colors group"
        >
          <td class="font-medium">{{ page.title }}</td>
          <td class="hidden sm:table-cell text-muted-foreground font-mono text-xs">/{{ page.slug }}</td>
          <td class="hidden sm:table-cell">
            <StatusBadge :status="page.status" />
          </td>
          <td class="hidden md:table-cell text-muted-foreground text-xs">{{ page.created_at }}</td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('pages.edit', page.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                type="button"
                @click="confirmDelete(page)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                title="Delete"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="pages.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ pages.from }}–{{ pages.to }} of {{ pages.total }}
      </p>
      <div class="flex gap-1">
        <a
          v-for="link in pages.links"
          :key="link.label"
          :href="link.url ?? undefined"
          class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm transition-colors"
          :class="link.active
            ? 'bg-primary text-primary-foreground font-medium'
            : link.url
              ? 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              : 'text-muted-foreground/40 cursor-not-allowed pointer-events-none'"
        >{{ decodeHtmlEntities(link.label) }}</a>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete page?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.title }}</span>" will be permanently deleted.
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
              @click="deletePage"
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
