<template>
  <AppLayout title="Posts">
    <Head title="Posts" />

    <PageHeader title="Posts" description="Manage your blog posts">
      <template #actions>
        <a
          :href="route('posts.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New post
        </a>
      </template>
    </PageHeader>

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
      <SelectBox
        :model-value="statusFilter"
        :data="[
          { value: '',          label: 'All statuses' },
          { value: 'published', label: 'Published' },
          { value: 'scheduled', label: 'Scheduled' },
          { value: 'draft',     label: 'Draft' },
        ]"
        placeholder="All statuses"
        @update:model-value="onStatusChange"
      />
    </div>

    <!-- Table -->
    <div class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-muted-foreground">
          <tr>
            <th class="px-4 py-3 w-10">
              <input
                type="checkbox"
                :checked="isAllSelected"
                :indeterminate="selectedIds.length > 0 && !isAllSelected"
                @change="toggleAll"
                class="rounded"
              />
            </th>
            <th class="text-left font-medium px-4 py-3">Title</th>
            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Author</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Categories</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Status</th>
            <th class="text-left font-medium px-4 py-3 hidden lg:table-cell">Date</th>
            <th class="text-left font-medium px-4 py-3 hidden xl:table-cell">Views</th>
            <th class="px-4 py-3 w-10"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr v-if="posts.data.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
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
            :class="{ 'bg-muted/20': selectedIds.includes(post.id) }"
          >
            <td class="px-4 py-3 w-10">
              <input
                type="checkbox"
                :checked="selectedIds.includes(post.id)"
                @change="toggleRow(post.id)"
                class="rounded"
              />
            </td>
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
              <StatusBadge :status="post.status" />
              <p v-if="post.expires_at" class="text-[11px] text-muted-foreground mt-0.5">
                ⏱ Expires {{ formatDate(post.expires_at) }}
              </p>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">
              {{ formatDate(post.published_at ?? post.created_at) }}
            </td>
            <td class="px-4 py-3 hidden xl:table-cell text-muted-foreground text-xs">
              {{ (post.views ?? 0).toLocaleString() }}
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

    <!-- Bulk delete confirmation modal -->
    <Transition name="fade">
      <div v-if="showBulkDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showBulkDeleteModal = false" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">
            Delete {{ selectedIds.length }} post{{ selectedIds.length === 1 ? '' : 's' }}?
          </h3>
          <p class="text-sm text-muted-foreground mb-5">This cannot be undone.</p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="showBulkDeleteModal = false"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="executeBulkDelete"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Sticky bulk action toolbar (z-40, below modals at z-50) -->
    <Transition name="slide-up">
      <div
        v-if="selectedIds.length > 0"
        class="fixed bottom-0 left-0 right-0 z-40 bg-card border-t shadow-lg"
      >
        <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center gap-3">
          <span class="text-sm font-medium text-muted-foreground">
            {{ selectedIds.length }} selected
          </span>
          <div class="flex items-center gap-2 ml-2">
            <button
              type="button"
              @click="bulkAction('publish')"
              class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
            >
              Publish
            </button>
            <button
              type="button"
              @click="bulkAction('draft')"
              class="rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-accent transition-colors"
            >
              Draft
            </button>
            <button
              type="button"
              @click="confirmBulkDelete"
              class="rounded-md border border-destructive/30 px-3 py-1.5 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
            >
              Delete
            </button>
          </div>
          <button
            type="button"
            @click="selectedIds = []"
            class="ml-auto text-sm text-muted-foreground hover:text-foreground transition-colors"
            aria-label="Clear selection"
          >
            ✕
          </button>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import SelectBox from '@/Components/SelectBox.vue'
import { decodeHtmlEntities, formatDate } from '@/lib/utils.js'
import { useNotifications } from '@/composables/useNotifications'

const props = defineProps({
  posts: Object,
  filters: Object,
});

const page = usePage()
const { notify } = useNotifications()

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.status) notify(flash.status, 'success')
  }
)

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "");

function onStatusChange(v) {
  statusFilter.value = v
  applyFilters()
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

// -- Selection state --

const selectedIds = ref([]);
const showBulkDeleteModal = ref(false);

// Reset selection whenever Inertia refreshes the posts prop
watch(
  () => props.posts,
  () => { selectedIds.value = []; }
);

const isAllSelected = computed(() =>
  props.posts.data.length > 0 &&
  props.posts.data.every((p) => selectedIds.value.includes(p.id))
);

function toggleAll() {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = props.posts.data.map((p) => p.id);
  }
}

function toggleRow(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx === -1) {
    selectedIds.value.push(id);
  } else {
    selectedIds.value.splice(idx, 1);
  }
}

// -- Bulk actions --

function bulkAction(action) {
  router.post(
    route("posts.bulk"),
    { action, ids: selectedIds.value },
    { onSuccess: () => { selectedIds.value = []; } }
  );
}

function confirmBulkDelete() {
  showBulkDeleteModal.value = true;
}

function executeBulkDelete() {
  showBulkDeleteModal.value = false;
  bulkAction("delete");
}

// -- Single-post delete --

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

.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); }
</style>
