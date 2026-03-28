<template>
  <AppLayout title="Tags">
    <Head title="Tags" />

    <div class="mb-4">
      <h2 class="text-lg font-semibold">Tags</h2>
      <p class="text-sm text-muted-foreground mt-0.5">Label posts with keywords</p>
    </div>

    <div class="flex items-center gap-3 mb-4">
      <a
        :href="route('tags.create')"
        class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New tag
      </a>
    </div>

    <!-- Tag bubble map -->
    <div class="rounded-lg border bg-card p-6 mb-6">
      <p class="text-xs font-medium text-muted-foreground mb-4">Tag cloud</p>
      <div v-if="tags.length === 0" class="text-sm text-muted-foreground text-center py-4">
        No tags yet.
      </div>
      <div v-else class="flex flex-wrap gap-2 items-center justify-center">
        <a
          v-for="(tag, i) in tags"
          :key="tag.id"
          :href="route('tags.edit', tag.id)"
          :title="`${tag.name} — ${tag.posts_count} post${tag.posts_count !== 1 ? 's' : ''}`"
          :style="bubbleStyle(tag, i)"
          class="rounded-full border font-medium transition-all duration-150 hover:scale-105 hover:opacity-90 cursor-pointer leading-none"
        >
          {{ tag.name }}
        </a>
      </div>
    </div>

    <DataTable :loading="false" :empty="tags.length === 0">
      <template #empty>
        <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
        </svg>
        No tags yet.
      </template>
      <template #headers>
        <th class="text-left">Tag</th>
        <th class="text-left hidden sm:table-cell w-24">Posts</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr
          v-for="tag in tags"
          :key="tag.id"
          class="hover:bg-muted/30 transition-colors group"
        >
          <td>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium">
                {{ tag.name }}
              </span>
              <span class="text-xs text-muted-foreground font-mono">{{ tag.slug }}</span>
            </div>
          </td>
          <td class="hidden sm:table-cell">
            <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
              {{ tag.posts_count }}
            </span>
          </td>
          <td>
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
                @click="deleteTag(tag)"
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

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete tag?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteTarget.name }}</span>"
            <span v-if="deleteTarget.posts_count > 0"> is used by {{ deleteTarget.posts_count }} post{{ deleteTarget.posts_count !== 1 ? 's' : '' }}. Posts will not be deleted.</span>
            <span v-else> will be permanently deleted.</span>
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">
              Cancel
            </button>
            <button type="button" @click="confirmDelete"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import DataTable from '@/Components/DataTable.vue'

const props = defineProps({ tags: Array });

const maxCount = computed(() => Math.max(...props.tags.map(t => t.posts_count), 1))

const CHART_COLORS = [
  { bg: 'color-mix(in srgb, var(--color-chart-1) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-1) 40%, transparent)', text: 'var(--color-chart-1)' },
  { bg: 'color-mix(in srgb, var(--color-chart-2) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-2) 40%, transparent)', text: 'var(--color-chart-2)' },
  { bg: 'color-mix(in srgb, var(--color-chart-3) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-3) 40%, transparent)', text: 'var(--color-chart-3)' },
  { bg: 'color-mix(in srgb, var(--color-chart-4) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-4) 40%, transparent)', text: 'var(--color-chart-4)' },
  { bg: 'color-mix(in srgb, var(--color-chart-5) 15%, transparent)', border: 'color-mix(in srgb, var(--color-chart-5) 40%, transparent)', text: 'var(--color-chart-5)' },
]

function bubbleStyle(tag, index) {
  const ratio = maxCount.value > 0 ? tag.posts_count / maxCount.value : 0
  const px = 10 + ratio * 22   // 10px → 32px horizontal padding
  const py = 5  + ratio * 11   // 5px  → 16px vertical padding
  const fontSize = 11 + ratio * 8  // 11px → 19px
  const color = CHART_COLORS[index % CHART_COLORS.length]
  return {
    paddingLeft:     `${px}px`,
    paddingRight:    `${px}px`,
    paddingTop:      `${py}px`,
    paddingBottom:   `${py}px`,
    fontSize:        `${fontSize}px`,
    backgroundColor: color.bg,
    borderColor:     color.border,
    color:           color.text,
  }
}

const deleteTarget = ref(null)

function deleteTag(tag) {
  deleteTarget.value = tag
}

function confirmDelete() {
  router.delete(route('tags.destroy', deleteTarget.value.id))
  deleteTarget.value = null
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
