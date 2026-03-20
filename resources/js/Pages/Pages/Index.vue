<!-- resources/js/Pages/Pages/Index.vue -->
<script setup>
import AppLayout  from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref }    from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'

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

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea')
  txt.innerHTML = str
  return txt.value
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
    <Head title="Pages" />

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

    <!-- Flash message -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

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
              <StatusBadge :status="page.status" />
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
  </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
