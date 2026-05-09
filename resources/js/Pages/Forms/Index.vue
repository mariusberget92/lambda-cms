<!-- resources/js/Pages/Forms/Index.vue -->
<template>
  <AppLayout title="Forms">
    <Head title="Forms" />

    <PageHeader title="Forms" description="Build and manage contact forms">
      <template #actions>
        <a
          :href="route('forms.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New form
        </a>
      </template>
    </PageHeader>

    <DataTable :empty="forms.data.length === 0">
      <template #empty>
        <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        No forms yet. Create your first form to get started.
      </template>
      <template #headers>
        <th class="text-left">Name</th>
        <th class="text-left hidden sm:table-cell">Fields</th>
        <th class="text-left hidden md:table-cell">Submissions</th>
        <th class="text-left hidden md:table-cell">Created</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr v-for="form in forms.data" :key="form.id" class="hover:bg-muted/30 transition-colors group">
          <td>
            <div>
              <a :href="route('forms.edit', form.id)" class="font-medium hover:text-primary transition-colors">{{ form.name }}</a>
              <p class="text-xs text-muted-foreground font-mono mt-0.5">{{ form.slug }}</p>
            </div>
          </td>
          <td class="hidden sm:table-cell">
            <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
              {{ form.fields_count }} field{{ form.fields_count !== 1 ? 's' : '' }}
            </span>
          </td>
          <td class="hidden md:table-cell">
            <a
              :href="route('forms.submissions', form.id)"
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors"
              :class="form.submissions_count > 0 ? 'bg-primary/15 text-primary hover:bg-primary/25' : 'bg-muted text-muted-foreground'"
            >
              {{ form.submissions_count }} submission{{ form.submissions_count !== 1 ? 's' : '' }}
            </a>
          </td>
          <td class="hidden md:table-cell text-sm text-muted-foreground">{{ form.created_at }}</td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('forms.edit', form.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <a
                :href="route('forms.submissions', form.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="View submissions"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
              </a>
              <button
                type="button"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                title="Delete"
                @click="confirmDelete(form)"
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
    <div v-if="forms.last_page > 1" class="mt-4 flex justify-center gap-1">
      <a
        v-for="link in forms.links"
        :key="link.label"
        :href="link.url ?? '#'"
        v-html="link.label"
        class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-md text-sm transition-colors"
        :class="link.active ? 'bg-primary text-primary-foreground font-medium' : link.url ? 'hover:bg-accent text-foreground' : 'text-muted-foreground cursor-default'"
      />
    </div>

    <!-- Delete modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete form?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.name }}</span>" and all its submissions will be permanently deleted.
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors" @click="deleteTarget = null">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors" @click="doDelete">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout  from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable  from '@/Components/DataTable.vue'

const props = defineProps({
  forms: { type: Object, required: true },
})

const deleteTarget = ref(null)
function confirmDelete(form) { deleteTarget.value = form }
function doDelete() {
  router.delete(route('forms.destroy', deleteTarget.value.id))
  deleteTarget.value = null
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
