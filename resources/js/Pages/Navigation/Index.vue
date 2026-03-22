<!-- resources/js/Pages/Navigation/Index.vue -->
<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { VueDraggable } from 'vue-draggable-plus'
import AppLayout from '@/Layouts/AppLayout.vue'
import SelectBox from '@/Components/SelectBox.vue'


const props = defineProps({
  items: { type: Array, default: () => [] },
  pages: { type: Array, default: () => [] },
})

// ─── Drag-to-reorder ──────────────────────────────────────────────────────────

const draggableItems = ref(props.items.map(i => ({ ...i })))

watch(() => props.items, (val) => {
  draggableItems.value = val.map(i => ({ ...i }))
})

function onReorder() {
  router.post(route('navigation.reorder'), {
    items: draggableItems.value.map((item, index) => ({
      id:         item.id,
      sort_order: index,
    })),
  }, { preserveScroll: true })
}

// ─── Add item form ────────────────────────────────────────────────────────────

const addType = ref('custom')

const form = useForm({
  type:    'custom',
  label:   '',
  url:     '',
  page_id: null,
})

function onTypeChange(type) {
  addType.value = type
  form.type    = type
  form.label   = ''
  form.url     = ''
  form.page_id = null
}

const pageOptions = computed(() => props.pages.map(p => ({ value: p.id, label: p.title })))

function onPageSelect(pageId) {
  if (!pageId) return
  form.page_id = pageId
  const page   = props.pages.find(p => p.id === pageId)
  if (page && !form.label) form.label = page.title
}

function submit() {
  form.post(route('navigation.store'), {
    onSuccess: () => {
      form.reset()
      addType.value = 'custom'
    },
    preserveScroll: true,
  })
}

function deleteItem(id) {
  if (!confirm('Remove this nav item?')) return
  router.delete(route('navigation.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <AppLayout title="Navigation">
  <Head title="Navigation" />

  <div class="max-w-4xl space-y-6">
    <h2 class="text-lg font-semibold">Navigation</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

      <!-- Left: current items -->
      <div class="rounded-lg border bg-card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Current items</p>

        <div v-if="!draggableItems.length" class="py-8 text-center text-sm text-muted-foreground">
          No nav items yet. Add your first item →
        </div>

        <VueDraggable
          v-model="draggableItems"
          handle=".drag-handle"
          class="space-y-2"
          @end="onReorder"
        >
          <div
            v-for="item in draggableItems"
            :key="item.id"
            class="flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm"
          >
            <span class="drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0">⋮⋮</span>

            <div class="flex-1 min-w-0">
              <span class="font-medium">{{ item.label }}</span>
              <span class="ml-2 text-xs text-muted-foreground truncate">{{ item.resolved_url }}</span>
            </div>

            <span
              class="shrink-0 text-xs rounded-full px-2 py-0.5"
              :class="item.type === 'page'
                ? 'bg-primary/10 text-primary'
                : 'bg-muted text-muted-foreground'"
            >{{ item.type === 'page' ? 'Page' : 'Custom' }}</span>

            <span
              v-if="item.type === 'page' && item.page_status !== 'published'"
              class="shrink-0 text-xs rounded-full px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
            >draft</span>

            <button
              type="button"
              class="shrink-0 text-muted-foreground hover:text-destructive transition-colors text-base leading-none"
              @click="deleteItem(item.id)"
            >&times;</button>
          </div>
        </VueDraggable>
      </div>

      <!-- Right: add item form -->
      <div class="rounded-lg border bg-card p-4 space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add item</p>

        <!-- Type toggle -->
        <div class="flex rounded-md border overflow-hidden text-sm">
          <button
            type="button"
            class="flex-1 px-3 py-1.5 transition-colors"
            :class="addType === 'custom' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
            @click="onTypeChange('custom')"
          >Custom link</button>
          <button
            type="button"
            class="flex-1 px-3 py-1.5 transition-colors"
            :class="addType === 'page' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
            @click="onTypeChange('page')"
          >Page</button>
        </div>

        <form @submit.prevent="submit" class="space-y-3">

          <!-- Page selector -->
          <div v-if="addType === 'page'">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Page</label>
            <SelectBox
              :model-value="null"
              :data="pageOptions"
              placeholder="Select a page…"
              @update:model-value="onPageSelect"
            />
            <p v-if="form.errors.page_id" class="mt-1 text-xs text-destructive">{{ form.errors.page_id }}</p>
          </div>

          <!-- Label -->
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
            <input
              v-model="form.label"
              type="text"
              placeholder="e.g. About"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <p v-if="form.errors.label" class="mt-1 text-xs text-destructive">{{ form.errors.label }}</p>
          </div>

          <!-- URL (custom only) -->
          <div v-if="addType === 'custom'">
            <label class="text-xs font-medium text-muted-foreground block mb-1">URL</label>
            <input
              v-model="form.url"
              type="text"
              placeholder="e.g. /about or https://example.com"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <p v-if="form.errors.url" class="mt-1 text-xs text-destructive">{{ form.errors.url }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
          >Add item</button>

        </form>
      </div>

    </div>
  </div>
  </AppLayout>
</template>
