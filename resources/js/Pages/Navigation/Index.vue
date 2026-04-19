<!-- resources/js/Pages/Navigation/Index.vue -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { GripVertical, Plus, Pencil, Trash2, X, Check, ExternalLink } from 'lucide-vue-next'

const props = defineProps({
  items: { type: Array, default: () => [] },
  pages: { type: Array, default: () => [] },
})

// ── Add form ─────────────────────────────────────────────────────────────────
const addForm = useForm({
  type:    'custom',
  label:   '',
  url:     '',
  page_id: null,
})

function addItem() {
  addForm.post(route('navigation.store'), {
    preserveScroll: true,
    onSuccess: () => addForm.reset(),
  })
}

// ── Inline edit ──────────────────────────────────────────────────────────────
const editingId = ref(null)
const editForm  = useForm({ type: 'custom', label: '', url: '', page_id: null })

function startEdit(item) {
  editingId.value = item.id
  editForm.type    = item.type
  editForm.label   = item.label
  editForm.url     = item.url ?? ''
  editForm.page_id = item.page_id
}

function saveEdit(item) {
  editForm.put(route('navigation.update', item.id), {
    preserveScroll: true,
    onSuccess: () => { editingId.value = null },
  })
}

function cancelEdit() { editingId.value = null }

function deleteItem(item) {
  if (!confirm(`Delete "${item.label}"?`)) return
  router.delete(route('navigation.destroy', item.id), { preserveScroll: true })
}

// ── Drag-to-reorder ──────────────────────────────────────────────────────────
const localItems = ref([...props.items])

// Watch for prop changes (after save)
import { watch } from 'vue'
watch(() => props.items, (v) => { localItems.value = [...v] })

let dragFrom = null

function onDragStart(index) { dragFrom = index }

function onDragOver(e, index) {
  e.preventDefault()
  if (dragFrom === null || dragFrom === index) return
  const moved = localItems.value.splice(dragFrom, 1)[0]
  localItems.value.splice(index, 0, moved)
  dragFrom = index
}

function onDrop() {
  dragFrom = null
  router.post(route('navigation.reorder'), {
    ids: localItems.value.map(i => i.id),
  }, { preserveScroll: true })
}

// ── Computed display URL ──────────────────────────────────────────────────────
function displayUrl(item) {
  if (item.type === 'page') return `/${item.page_slug ?? '…'}`
  return item.url ?? ''
}
</script>

<template>
  <AppLayout>
    <PageHeader title="Navigation" description="Manage the public site navigation links." />

    <div class="max-w-2xl space-y-6">

      <!-- Current items list -->
      <div v-if="localItems.length" class="border border-border rounded-lg overflow-hidden">
        <div
          v-for="(item, index) in localItems"
          :key="item.id"
          draggable="true"
          @dragstart="onDragStart(index)"
          @dragover="onDragOver($event, index)"
          @drop="onDrop"
          class="flex items-center gap-3 px-4 py-3 border-b border-border last:border-b-0 bg-background"
        >
          <!-- Drag handle -->
          <span class="cursor-grab active:cursor-grabbing text-muted-foreground shrink-0">
            <GripVertical class="w-4 h-4" />
          </span>

          <!-- Inline edit mode -->
          <template v-if="editingId === item.id">
            <div class="flex-1 flex items-center gap-2 flex-wrap">
              <!-- Type toggle -->
              <div class="flex rounded-md border overflow-hidden text-xs shrink-0">
                <button type="button"
                  :class="editForm.type === 'custom' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                  class="px-2.5 py-1 transition-colors"
                  @click="editForm.type = 'custom'">URL</button>
                <button type="button"
                  :class="editForm.type === 'page' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                  class="px-2.5 py-1 transition-colors"
                  @click="editForm.type = 'page'">Page</button>
              </div>

              <input
                v-model="editForm.label"
                type="text"
                placeholder="Label"
                class="min-w-0 rounded border border-input bg-background px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
              />

              <input
                v-if="editForm.type === 'custom'"
                v-model="editForm.url"
                type="text"
                placeholder="https://… or /path"
                class="min-w-0 flex-1 rounded border border-input bg-background px-2 py-1 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-ring"
              />
              <select
                v-else
                v-model="editForm.page_id"
                class="min-w-0 flex-1 rounded border border-input bg-background px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
              >
                <option :value="null" disabled>Choose page…</option>
                <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.title }}</option>
              </select>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button type="button" @click="saveEdit(item)" class="inline-flex items-center justify-center w-7 h-7 rounded text-green-600 hover:bg-green-50 transition-colors" title="Save">
                <Check class="w-3.5 h-3.5" />
              </button>
              <button type="button" @click="cancelEdit" class="inline-flex items-center justify-center w-7 h-7 rounded text-muted-foreground hover:bg-accent transition-colors" title="Cancel">
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </template>

          <!-- Display mode -->
          <template v-else>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ item.label }}</p>
              <p class="text-xs text-muted-foreground font-mono truncate flex items-center gap-1">
                {{ displayUrl(item) }}
                <ExternalLink v-if="item.type === 'custom' && item.url?.startsWith('http')" class="w-3 h-3 shrink-0" />
              </p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button type="button" @click="startEdit(item)" class="inline-flex items-center justify-center w-7 h-7 rounded text-muted-foreground hover:bg-accent transition-colors" title="Edit">
                <Pencil class="w-3.5 h-3.5" />
              </button>
              <button type="button" @click="deleteItem(item)" class="inline-flex items-center justify-center w-7 h-7 rounded text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors" title="Delete">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </template>
        </div>
      </div>

      <p v-else class="text-sm text-muted-foreground">No navigation items yet. Add one below.</p>

      <!-- Add form -->
      <form @submit.prevent="addItem" class="border border-border rounded-lg p-4 space-y-3">
        <p class="text-sm font-semibold">Add navigation item</p>

        <div class="flex items-center gap-3 flex-wrap">
          <!-- Type toggle -->
          <div class="flex rounded-md border overflow-hidden text-xs shrink-0">
            <button type="button"
              :class="addForm.type === 'custom' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
              class="px-3 py-1.5 transition-colors"
              @click="addForm.type = 'custom'">Custom URL</button>
            <button type="button"
              :class="addForm.type === 'page' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
              class="px-3 py-1.5 transition-colors"
              @click="addForm.type = 'page'">Page</button>
          </div>

          <input
            v-model="addForm.label"
            type="text"
            placeholder="Label (e.g. Blog)"
            required
            class="flex-1 min-w-[140px] rounded-md border border-input bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>

        <div v-if="addForm.type === 'custom'">
          <input
            v-model="addForm.url"
            type="text"
            placeholder="https://… or /path"
            required
            class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div v-else>
          <select
            v-model="addForm.page_id"
            required
            class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option :value="null" disabled>Choose a published page…</option>
            <option v-for="p in pages" :key="p.id" :value="p.id">{{ p.title }}</option>
          </select>
        </div>

        <div v-if="addForm.errors.label || addForm.errors.url || addForm.errors.page_id" class="text-xs text-destructive">
          {{ addForm.errors.label || addForm.errors.url || addForm.errors.page_id }}
        </div>

        <button
          type="submit"
          :disabled="addForm.processing"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
        >
          <Plus class="w-4 h-4" />
          Add item
        </button>
      </form>

    </div>
  </AppLayout>
</template>
