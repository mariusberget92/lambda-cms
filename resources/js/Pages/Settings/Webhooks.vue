<template>
  <AppLayout title="Webhooks">
    <Head title="Webhooks" />

    <PageHeader title="Webhooks" description="Fire HTTP callbacks when content events occur." />

    <div class="max-w-3xl space-y-6">

      <!-- Add webhook form -->
      <div class="rounded-lg border bg-card p-5">
        <h3 class="text-sm font-semibold mb-4">Add Webhook</h3>
        <form @submit.prevent="submitAdd" class="space-y-4">
          <div>
            <label class="text-sm font-medium block mb-1">Endpoint URL</label>
            <input
              v-model="addForm.url"
              type="url"
              placeholder="https://example.com/webhook"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': addForm.errors.url }"
            />
            <p v-if="addForm.errors.url" class="text-xs text-destructive mt-1">{{ addForm.errors.url }}</p>
          </div>

          <div>
            <label class="text-sm font-medium block mb-1">Secret <span class="text-muted-foreground font-normal">(optional — used for HMAC signature)</span></label>
            <input
              v-model="addForm.secret"
              type="text"
              placeholder="mysecret"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>

          <div>
            <label class="text-sm font-medium block mb-2">Events</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <label
                v-for="ev in ALL_EVENTS"
                :key="ev"
                class="flex items-center gap-2 text-sm cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="ev"
                  v-model="addForm.events"
                  class="rounded border-border"
                />
                <code class="text-xs">{{ ev }}</code>
              </label>
            </div>
            <p v-if="addForm.errors.events" class="text-xs text-destructive mt-1">{{ addForm.errors.events }}</p>
          </div>

          <div class="flex items-center gap-2">
            <input id="add_active" v-model="addForm.is_active" type="checkbox" class="rounded border-border" />
            <label for="add_active" class="text-sm">Active</label>
          </div>

          <button
            type="submit"
            :disabled="addForm.processing"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
          >
            {{ addForm.processing ? 'Adding...' : 'Add Webhook' }}
          </button>
        </form>
      </div>

      <!-- Existing webhooks -->
      <div v-if="webhooks.length === 0" class="text-sm text-muted-foreground text-center py-8 rounded-lg border bg-card">
        No webhooks configured yet.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="webhook in webhooks"
          :key="webhook.id"
          class="rounded-lg border bg-card p-4"
        >
          <!-- View mode -->
          <template v-if="editingId !== webhook.id">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0 space-y-1.5">
                <div class="flex items-center gap-2">
                  <span
                    class="inline-block w-2 h-2 rounded-full shrink-0"
                    :class="webhook.is_active ? 'bg-green-500' : 'bg-muted-foreground'"
                  />
                  <span class="text-sm font-mono truncate">{{ webhook.url }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="ev in webhook.events"
                    :key="ev"
                    class="text-[11px] px-1.5 py-0.5 rounded bg-muted text-muted-foreground font-mono"
                  >{{ ev }}</span>
                </div>
                <p v-if="webhook.last_triggered_at" class="text-xs text-muted-foreground">
                  Last fired: {{ new Date(webhook.last_triggered_at).toLocaleString() }}
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  class="text-xs text-muted-foreground hover:text-foreground transition-colors"
                  @click="startEdit(webhook)"
                >Edit</button>
                <button
                  type="button"
                  class="text-xs text-destructive hover:underline transition-colors"
                  @click="deleteWebhook(webhook.id)"
                >Delete</button>
              </div>
            </div>
          </template>

          <!-- Edit mode -->
          <template v-else>
            <form @submit.prevent="submitEdit(webhook)" class="space-y-3">
              <div>
                <label class="text-xs font-medium block mb-1">URL</label>
                <input
                  v-model="editForm.url"
                  type="url"
                  class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': editForm.errors.url }"
                />
              </div>
              <div>
                <label class="text-xs font-medium block mb-1">Secret</label>
                <input v-model="editForm.secret" type="text" class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs font-medium block mb-1">Events</label>
                <div class="flex flex-wrap gap-2">
                  <label v-for="ev in ALL_EVENTS" :key="ev" class="flex items-center gap-1.5 text-sm cursor-pointer">
                    <input type="checkbox" :value="ev" v-model="editForm.events" class="rounded border-border" />
                    <code class="text-xs">{{ ev }}</code>
                  </label>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <input :id="`edit_active_${webhook.id}`" v-model="editForm.is_active" type="checkbox" class="rounded border-border" />
                <label :for="`edit_active_${webhook.id}`" class="text-sm">Active</label>
              </div>
              <div class="flex gap-2">
                <button
                  type="submit"
                  :disabled="editForm.processing"
                  class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
                >Save</button>
                <button
                  type="button"
                  class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors hover:bg-accent"
                  @click="editingId = null"
                >Cancel</button>
              </div>
            </form>
          </template>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

defineProps({
  webhooks: { type: Array, default: () => [] },
})

const ALL_EVENTS = [
  'post.published', 'post.updated', 'post.deleted',
  'page.published', 'page.updated', 'page.deleted',
]

const addForm = useForm({
  url:       '',
  secret:    '',
  events:    [],
  is_active: true,
})

function submitAdd() {
  addForm.post(route('webhooks.store'), {
    onSuccess: () => addForm.reset(),
  })
}

const editingId = ref(null)
const editForm  = useForm({ url: '', secret: '', events: [], is_active: true })

function startEdit(webhook) {
  editingId.value   = webhook.id
  editForm.url      = webhook.url
  editForm.secret   = webhook.secret ?? ''
  editForm.events   = [...webhook.events]
  editForm.is_active = webhook.is_active
}

function submitEdit(webhook) {
  editForm.put(route('webhooks.update', webhook.id), {
    onSuccess: () => { editingId.value = null },
  })
}

function deleteWebhook(id) {
  if (confirm('Delete this webhook?')) {
    router.delete(route('webhooks.destroy', id))
  }
}
</script>
