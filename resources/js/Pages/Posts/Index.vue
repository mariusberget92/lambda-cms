<template>
  <AppLayout title="Posts">
    <Head title="Posts" />

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Posts</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage your blog articles</p>
      </div>
      <a
        :href="route('posts.create')"
        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New post
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

    <!-- Filters -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1 max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
        </svg>
        <input
          v-model="search"
          type="search"
          placeholder="Search posts..."
          class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          @input="applyFilters"
        />
      </div>
      <select
        v-model="statusFilter"
        class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="applyFilters"
      >
        <option value="">All statuses</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
      </select>
    </div>

    <!-- Table -->
    <div class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-muted-foreground">
          <tr>
            <th class="text-left font-medium px-4 py-3">Title</th>
            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Author</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Categories</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Status</th>
            <th class="text-left font-medium px-4 py-3 hidden lg:table-cell">Date</th>
            <th class="px-4 py-3 w-10"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr v-if="posts.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
              <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              No posts found.
            </td>
          </tr>
          <tr
            v-for="post in posts.data"
            :key="post.id"
            class="hover:bg-muted/30 transition-colors group"
          >
            <td class="px-4 py-3">
              <div class="font-medium line-clamp-1">{{ post.title }}</div>
              <div v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-1 mt-0.5 hidden sm:block">{{ post.excerpt }}</div>
            </td>
            <td class="px-4 py-3 hidden sm:table-cell text-muted-foreground">{{ post.author }}</td>
            <td class="px-4 py-3 hidden md:table-cell text-muted-foreground text-xs">
              <span v-if="post.categories?.length">{{ post.categories.map(c => c.name).join(', ') }}</span>
              <span v-else class="text-muted-foreground/50">—</span>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="post.status === 'published'
                  ? 'bg-status-success-bg text-status-success-fg'
                  : 'bg-status-warning-bg text-status-warning-fg'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="post.status === 'published' ? 'bg-status-success-fg' : 'bg-status-warning-fg'"></span>
                {{ post.status === 'published' ? 'Published' : 'Draft' }}
              </span>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">
              {{ post.published_at ?? post.created_at }}
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <a
                  :href="route('posts.edit', post.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                  title="Edit"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </a>
                <button
                  type="button"
                  @click="confirmDelete(post)"
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

    <!-- Pagination -->
    <div v-if="posts.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ posts.from }}–{{ posts.to }} of {{ posts.total }}
      </p>
      <div class="flex gap-1">
        <a
          v-for="link in posts.links"
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
          <h3 class="font-semibold text-base mb-2">Delete post?</h3>
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
              @click="deletePost"
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

<script setup>
import { ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  posts: Object,
  filters: Object,
});

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea');
  txt.innerHTML = str;
  return txt.value;
}

let searchTimeout = null;
function applyFilters() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(
      route("posts.index"),
      { search: search.value, status: statusFilter.value },
      { preserveState: true, replace: true }
    );
  }, 300);
}

const deleteTarget = ref(null);
function confirmDelete(post) {
  deleteTarget.value = post;
}
function deletePost() {
  if (!deleteTarget.value) return;
  router.delete(route("posts.destroy", deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null; },
  });
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
