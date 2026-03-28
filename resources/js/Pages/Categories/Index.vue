<template>
  <AppLayout title="Categories">
    <Head title="Categories" />

    <div class="mb-4">
      <h2 class="text-lg font-semibold">Categories</h2>
      <p class="text-sm text-muted-foreground mt-0.5">Organise your posts by topic</p>
    </div>

    <div class="flex items-center gap-3 mb-4">
      <a
        :href="route('categories.create')"
        class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New category
      </a>
    </div>

    <DataTable :loading="false" :empty="categories.length === 0">
      <template #empty>
        <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
        </svg>
        No categories yet.
      </template>
      <template #headers>
        <th class="text-left">Name</th>
        <th class="text-left hidden md:table-cell">Description</th>
        <th class="text-left hidden sm:table-cell w-24">Posts</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="cat in categories"
          :key="cat.id"
          class="hover:bg-muted/30 transition-colors group"
        >
          <td>
            <div class="font-medium">{{ cat.name }}</div>
            <div class="text-xs text-muted-foreground font-mono mt-0.5">{{ cat.slug }}</div>
          </td>
          <td class="hidden md:table-cell text-muted-foreground text-sm">
            {{ cat.description ?? '—' }}
          </td>
          <td class="hidden sm:table-cell">
            <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
              {{ cat.posts_count }}
            </span>
          </td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('categories.edit', cat.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                type="button"
                @click="deleteCategory(cat)"
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

  </AppLayout>
</template>

<script setup>
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from '@/Components/DataTable.vue'

defineProps({ categories: Array });

function deleteCategory(category) {
  if (
    category.posts_count > 0 &&
    !window.confirm(
      `"${category.name}" is used by ${category.posts_count} post${category.posts_count !== 1 ? 's' : ''}. Delete anyway? Posts will not be deleted.`
    )
  ) return

  router.delete(route('categories.destroy', category.id))
}
</script>
