<template>
  <AppLayout title="Tags">
    <Head title="Tags" />

    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Tags</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Label posts with keywords</p>
      </div>
      <a
        :href="route('tags.create')"
        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New tag
      </a>
    </div>

    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="mb-4 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Tag cloud / grid -->
    <div v-if="tags.length === 0" class="rounded-lg border p-12 text-center text-muted-foreground">
      <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
      </svg>
      No tags yet.
    </div>

    <div v-else class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-muted-foreground">
          <tr>
            <th class="text-left font-medium px-4 py-3">Tag</th>
            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell w-24">Posts</th>
            <th class="px-4 py-3 w-10"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr
            v-for="tag in tags"
            :key="tag.id"
            class="hover:bg-muted/30 transition-colors group"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium">
                  {{ tag.name }}
                </span>
                <span class="text-xs text-muted-foreground font-mono">{{ tag.slug }}</span>
              </div>
            </td>
            <td class="px-4 py-3 hidden sm:table-cell">
              <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
                {{ tag.posts_count }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <a
                  :href="route('tags.edit', tag.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                  title="Edit"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </a>
                <button
                  type="button"
                  @click="confirmDelete(tag)"
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
        </tbody>
      </table>
    </div>

    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete tag?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.name }}</span>" will be permanently deleted and removed from all posts.
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="deleteTarget = null" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</button>
            <button type="button" @click="deleteTag" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({ tags: Array });

const deleteTarget = ref(null);
function confirmDelete(tag) { deleteTarget.value = tag; }
function deleteTag() {
  if (!deleteTarget.value) return;
  router.delete(route("tags.destroy", deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null; },
  });
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
