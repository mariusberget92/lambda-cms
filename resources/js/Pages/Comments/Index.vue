<template>
  <AppLayout title="Comments">
    <Head title="Comments" />

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Comments</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Moderate reader comments</p>
      </div>
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

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-4 border-b">
      <a
        v-for="tab in tabs"
        :key="tab.value"
        :href="route('comments.index') + (tab.value !== 'pending' ? '?filter=' + tab.value : '')"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="filter === tab.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
      >
        {{ tab.label }}
        <span v-if="tab.value === 'pending' && pendingCount" class="ml-1.5 rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-semibold text-destructive-foreground">{{ pendingCount }}</span>
      </a>
    </div>

    <!-- Bulk actions bar -->
    <Transition name="fade">
      <div v-if="selected.length" class="flex items-center gap-3 mb-4 rounded-md border bg-muted/50 px-4 py-2.5 text-sm">
        <span class="text-muted-foreground">{{ selected.length }} selected</span>
        <div class="flex gap-2 ml-auto">
          <button type="button" @click="bulkAction('approve')" class="rounded-md bg-status-success-bg px-3 py-1.5 text-xs font-medium text-status-success-fg hover:opacity-80 transition-opacity">Approve</button>
          <button type="button" @click="bulkAction('reject')" class="rounded-md bg-status-warning-bg px-3 py-1.5 text-xs font-medium text-status-warning-fg hover:opacity-80 transition-opacity">Reject</button>
          <button type="button" @click="bulkAction('delete')" class="rounded-md bg-destructive/10 px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors">Delete</button>
        </div>
      </div>
    </Transition>

    <!-- Table -->
    <div class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-muted-foreground">
          <tr>
            <th class="px-4 py-3 w-8">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                @change="toggleAll"
                class="rounded border-border"
              />
            </th>
            <th class="text-left font-medium px-4 py-3">Author</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Comment</th>
            <th class="text-left font-medium px-4 py-3 hidden lg:table-cell">Post</th>
            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Status</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Date</th>
            <th class="px-4 py-3 w-28"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr v-if="comments.data.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
              <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
              </svg>
              No comments found.
            </td>
          </tr>
          <tr
            v-for="comment in comments.data"
            :key="comment.id"
            class="hover:bg-muted/30 transition-colors"
            :class="{ 'bg-muted/20': selected.includes(comment.id) }"
          >
            <td class="px-4 py-3">
              <input
                type="checkbox"
                :checked="selected.includes(comment.id)"
                @change="toggleOne(comment.id)"
                class="rounded border-border"
              />
            </td>
            <td class="px-4 py-3">
              <p class="font-medium leading-tight">{{ comment.author_name }}</p>
              <p v-if="comment.author_email" class="text-xs text-muted-foreground mt-0.5">{{ comment.author_email }}</p>
            </td>
            <td class="px-4 py-3 hidden md:table-cell text-muted-foreground max-w-xs">
              <p class="line-clamp-2">{{ comment.body_excerpt }}</p>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell">
              <a
                :href="'/blog/' + comment.post.slug"
                target="_blank"
                class="text-sm text-primary hover:underline line-clamp-1"
              >{{ comment.post.title }}</a>
            </td>
            <td class="px-4 py-3 hidden sm:table-cell">
              <StatusBadge :status="comment.status" />
            </td>
            <td class="px-4 py-3 hidden md:table-cell text-xs text-muted-foreground">{{ comment.created_at }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <button
                  v-if="comment.status !== 'approved'"
                  type="button"
                  @click="singleAction('approve', comment.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-status-success-bg hover:text-status-success-fg transition-colors"
                  title="Approve"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
                <button
                  v-if="comment.status !== 'rejected'"
                  type="button"
                  @click="singleAction('reject', comment.id)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-status-warning-bg hover:text-status-warning-fg transition-colors"
                  title="Reject"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
                <button
                  type="button"
                  @click="deleteTarget = comment"
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
    <div v-if="comments.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ comments.from }}–{{ comments.to }} of {{ comments.total }}
      </p>
      <div class="flex gap-1">
        <a
          v-for="link in comments.links"
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
          <h3 class="font-semibold text-base mb-2">Delete comment?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            Comment by <span class="font-medium text-foreground">{{ deleteTarget.author_name }}</span> will be permanently deleted.
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
              @click="confirmDelete"
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
import { computed, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

const props = defineProps({
  comments:     { type: Object, required: true },
  filter:       { type: String, default: "pending" },
  pendingCount: { type: Number, default: 0 },
});

const tabs = [
  { label: "Pending",  value: "pending"  },
  { label: "Approved", value: "approved" },
  { label: "Rejected", value: "rejected" },
  { label: "All",      value: "all"      },
];

// Selection
const selected = ref([]);
const allSelected  = computed(() => props.comments.data.length > 0 && selected.value.length === props.comments.data.length);
const someSelected = computed(() => selected.value.length > 0 && selected.value.length < props.comments.data.length);

function toggleAll() {
  if (allSelected.value) {
    selected.value = [];
  } else {
    selected.value = props.comments.data.map((c) => c.id);
  }
}
function toggleOne(id) {
  const idx = selected.value.indexOf(id);
  if (idx === -1) {
    selected.value.push(id);
  } else {
    selected.value.splice(idx, 1);
  }
}

// Actions
function singleAction(action, id) {
  const routeMap = { approve: "comments.approve", reject: "comments.reject" };
  router.patch(route(routeMap[action], id));
}

function bulkAction(action) {
  router.post(route("comments.bulk"), { action, ids: selected.value }, {
    onSuccess: () => { selected.value = []; },
  });
}

const deleteTarget = ref(null);
function confirmDelete() {
  if (!deleteTarget.value) return;
  router.delete(route("comments.destroy", deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null; },
  });
}

function decodeHtmlEntities(str) {
  const txt = document.createElement("textarea");
  txt.innerHTML = str;
  return txt.value;
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
