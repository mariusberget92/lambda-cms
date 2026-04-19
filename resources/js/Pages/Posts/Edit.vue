<template>
  <AppLayout title="Edit Post">
    <Head title="Edit Post" />

    <form @submit.prevent="submit">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a
            :href="route('posts.index')"
            title="Go back"
            aria-label="Go back"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
          >
            <ArrowLeft class="w-4 h-4" />
          </a>
          <div>
            <h2 class="text-lg font-semibold">Edit post</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1 max-w-xs">{{ post.title }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <a
            v-if="post.preview_token"
            :href="route('preview.post', post.preview_token)"
            target="_blank"
            rel="noopener"
            class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent inline-flex items-center gap-1.5"
          >
            <ExternalLink class="w-3.5 h-3.5" />
            Preview
          </a>
          <button
            type="button"
            @click="form.status = 'draft'; submit()"
            :disabled="form.processing"
            class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save as draft' }}
          </button>
          <button
            type="button"
            @click="submitPrimary()"
            :disabled="form.processing"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
          >
            <span v-if="form.processing">Saving...</span>
            <span v-else-if="form.status === 'scheduled'">Schedule</span>
            <span v-else-if="form.status === 'published'">Update</span>
            <span v-else>Publish</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Title -->
          <div>
            <input
              v-model="form.title"
              type="text"
              placeholder="Post title..."
              class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.title }"
            />
            <p v-if="form.errors.title" class="text-xs text-destructive mt-1">{{ form.errors.title }}</p>
          </div>

          <!-- Excerpt -->
          <div>
            <textarea
              v-model="form.excerpt"
              placeholder="Short excerpt (optional)..."
              rows="2"
              class="w-full rounded-lg border bg-background px-4 py-2.5 text-sm placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring resize-none"
              :class="{ 'border-destructive': form.errors.excerpt }"
            />
            <p v-if="form.errors.excerpt" class="text-xs text-destructive mt-1">{{ form.errors.excerpt }}</p>
            <div class="flex justify-between mt-1">
              <p class="text-xs text-muted-foreground ml-auto">{{ (form.excerpt ?? '').length }}/500</p>
            </div>
          </div>

          <!-- Editor -->
          <div>
            <div class="rounded-lg border overflow-hidden">
              <TiptapEditor v-model="form.body" />
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Status -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" @change="onStatusChange('draft')" />
                <div>
                  <span class="text-sm font-medium">Draft</span>
                  <p class="text-xs text-muted-foreground">Only visible to you</p>
                </div>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="scheduled" class="accent-primary" />
                <div>
                  <span class="text-sm font-medium">Scheduled</span>
                  <p class="text-xs text-muted-foreground">Auto-publishes at a set time</p>
                </div>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" />
                <div>
                  <span class="text-sm font-medium">Published</span>
                  <p class="text-xs text-muted-foreground">Visible to everyone</p>
                </div>
              </label>
            </div>

            <!-- Datetime picker — only when Scheduled -->
            <div v-show="form.status === 'scheduled'" class="mt-3 pt-3 border-t border-border">
              <label class="text-xs font-medium text-muted-foreground mb-1 block">Publish on</label>
              <DateTimePicker v-model="form.published_at" />
              <p v-if="daysUntilPublish" class="text-xs text-status-info-fg mt-1">
                ⏱ publishes in {{ daysUntilPublish }} day{{ daysUntilPublish === 1 ? '' : 's' }}
              </p>
            </div>
          </div>

          <!-- Categories -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Categories</h3>
            <div v-if="categories.length === 0" class="text-xs text-muted-foreground">
              No categories yet.
              <a :href="route('categories.create')" class="underline hover:text-foreground">Create one</a>
            </div>
            <CategoryInput v-else :categories="categories" v-model="form.category_ids" />
          </div>

          <!-- Tags -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Tags</h3>
            <TagInput
              :tags="tags"
              v-model="form.tag_ids"
              v-model:new-tag-names="form.new_tag_names"
            />
          </div>

          <!-- Featured Image -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Featured Image</h3>
            <div v-if="featuredImage" class="mb-3 relative group">
              <img
                :src="featuredImage.url"
                :alt="featuredImage.alt ?? 'Featured image'"
                class="w-full aspect-video object-cover rounded-md"
              />
              <button
                type="button"
                class="absolute top-1 right-1 w-6 h-6 rounded-full bg-background/80 flex items-center justify-center text-muted-foreground hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
                @click="removeFeaturedImage"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
            <button
              type="button"
              class="w-full rounded-md border border-dashed px-3 py-2 text-sm text-muted-foreground hover:border-primary hover:text-primary transition-colors"
              @click="showMediaPicker = true"
            >
              {{ featuredImage ? 'Change image' : 'Select image' }}
            </button>
          </div>

          <MediaPicker
            v-model="showMediaPicker"
            confirm-label="Use as featured image"
            @select="onFeaturedImageSelect"
          />

          <!-- Comments -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Comments</h3>
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.comments_enabled"
                class="w-4 h-4 rounded border-border accent-nord-green"
              />
              <div>
                <span class="text-sm font-medium">Allow comments</span>
                <p class="text-xs text-muted-foreground">Let readers comment on this post</p>
              </div>
            </label>
          </div>

          <!-- Featured -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Featured</h3>
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                v-model="form.featured"
                class="w-4 h-4 rounded border-border accent-nord-green"
              />
              <div>
                <span class="text-sm font-medium">Featured post</span>
                <p class="text-xs text-muted-foreground">Highlight this post in featured loops</p>
              </div>
            </label>
          </div>

          <!-- SEO -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">SEO</h3>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium mb-1">Meta title</label>
                <input
                  v-model="form.meta_title"
                  type="text"
                  maxlength="100"
                  class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  placeholder="Leave blank to use post title"
                />
                <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_title ?? '').length }}/100</p>
              </div>
              <div>
                <label class="block text-xs font-medium mb-1">Meta description</label>
                <textarea
                  v-model="form.meta_description"
                  rows="3"
                  maxlength="300"
                  class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                  placeholder="Leave blank to use excerpt"
                />
                <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_description ?? '').length }}/300</p>
              </div>
              <div>
                <label class="block text-xs font-medium mb-1">Keywords</label>
                <input
                  v-model="form.meta_keywords"
                  type="text"
                  maxlength="255"
                  class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  placeholder="Leave blank to use site defaults"
                />
                <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_keywords ?? '').length }}/255</p>
              </div>
            </div>
          </div>

          <!-- Details -->
          <div class="rounded-lg border bg-card p-4 text-sm space-y-1.5">
            <h3 class="font-medium mb-2">Details</h3>
            <div class="flex justify-between text-muted-foreground">
              <span>Slug</span>
              <span class="font-mono text-xs truncate max-w-[10rem]">{{ post.slug }}</span>
            </div>
            <div v-if="post.published_at" class="flex justify-between text-muted-foreground">
              <span>{{ post.status === 'scheduled' ? 'Scheduled' : 'Published' }}</span>
              <span>{{ post.published_at?.replace('T', ' ') }}</span>
            </div>
          </div>

          <!-- Revisions panel -->
          <div class="rounded-lg border bg-card">
            <button
              type="button"
              class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium"
              @click="toggleRevisions"
            >
              <span>Revision History</span>
              <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': revisionsOpen }" />
            </button>

            <div v-if="revisionsOpen" class="border-t px-4 py-3 space-y-1">
              <div v-if="revisionsLoading" class="text-xs text-muted-foreground text-center py-3">Loading…</div>
              <div v-else-if="revisions.length === 0" class="text-xs text-muted-foreground text-center py-3">No revisions yet.</div>
              <div
                v-for="rev in revisions"
                :key="rev.id"
                class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted/50"
              >
                <div class="min-w-0">
                  <p class="text-xs font-medium truncate">{{ rev.user?.name ?? 'Unknown' }}</p>
                  <p class="text-[11px] text-muted-foreground">{{ new Date(rev.created_at).toLocaleString() }}</p>
                </div>
                <button
                  type="button"
                  class="shrink-0 rounded-md border px-2 py-1 text-xs hover:bg-accent transition-colors"
                  @click="restoreRevision(rev)"
                >
                  Restore
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

    <!-- Restore revision confirmation modal -->
    <Transition name="fade">
      <div v-if="restoreTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="restoreTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Restore version?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            Your current unsaved changes will be replaced by this revision. This cannot be undone.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="restoreTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="confirmRestore"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
            >
              Restore
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, useForm } from "@inertiajs/vue3";
import axios from 'axios'
import { ChevronDown, ArrowLeft, X, ExternalLink } from 'lucide-vue-next'
import AppLayout from "@/Layouts/AppLayout.vue";
import TiptapEditor from "@/Components/TiptapEditor.vue";
import MediaPicker from '@/Components/MediaPicker.vue'
import DateTimePicker from '@/Components/DateTimePicker.vue'
import TagInput from '@/Components/TagInput.vue'
import CategoryInput from '@/Components/CategoryInput.vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify, dismiss } = useNotifications()
let autosaveToastId = null

const props = defineProps({
  post:       Object,
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
  autosave:   { type: Object, default: null },
});

const form = useForm({
  title:             props.post.title,
  excerpt:           props.post.excerpt ?? "",
  body:              props.post.body ?? "",
  status:            props.post.status,
  published_at:      props.post.published_at ?? '',
  category_ids:      props.post.category_ids ?? [],
  tag_ids:           props.post.tag_ids ?? [],
  new_tag_names:     [],
  featured_image_id: props.post.featured_image_id ?? null,
  featured:          props.post.featured ?? false,
  comments_enabled:  props.post.comments_enabled ?? true,
  meta_title:        props.post.meta_title ?? null,
  meta_description:  props.post.meta_description ?? null,
  meta_keywords:     props.post.meta_keywords ?? null,
});

// Autosave
let autosaveTimer = null

watch(form, () => {
  clearTimeout(autosaveTimer)
  autosaveTimer = setTimeout(doAutosave, 10000)
}, { deep: true })

async function doAutosave() {
  try {
    const res = await axios.post(route('posts.autosave', props.post.id), {
      payload: form.data(),
    })
    if (autosaveToastId !== null) dismiss(autosaveToastId)
    autosaveToastId = notify(`Draft saved at ${res.data.saved_at}`, 'info')
  } catch {
    notify('Autosave failed — check your connection', 'error')
    autosaveToastId = null
  }
}

async function restoreAutosave() {
  try {
    const fields = props.autosave.payload
    Object.keys(fields).forEach(key => {
      if (key in form) form[key] = fields[key]
    })
    await axios.delete(route('posts.autosave.destroy', props.post.id))
    notify('Draft restored.', 'success')
  } catch {
    notify('Could not restore draft — please try again.', 'error')
  }
}

async function dismissAutosave() {
  try {
    await axios.delete(route('posts.autosave.destroy', props.post.id))
  } catch {
    // autosave cleanup failed — not critical
  }
}

onMounted(() => {
  if (
    props.autosave &&
    props.post.updated_at &&
    new Date(props.autosave.updated_at) > new Date(props.post.updated_at)
  ) {
    notify(
      'You have unsaved changes from a previous session.',
      'info',
      {
        duration: null,
        actions: [
          { label: 'Restore', handler: restoreAutosave },
          { label: 'Dismiss', handler: dismissAutosave },
        ],
      }
    )
  }
})

onBeforeUnmount(() => clearTimeout(autosaveTimer))

// Revisions
const revisionsOpen    = ref(false)
const revisionsLoading = ref(false)
const revisions        = ref([])

async function loadRevisions() {
  if (revisions.value.length > 0) return
  revisionsLoading.value = true
  try {
    const res = await axios.get(route('posts.revisions', props.post.id))
    revisions.value = res.data
  } finally {
    revisionsLoading.value = false
  }
}

function toggleRevisions() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) loadRevisions()
}

const restoreTarget = ref(null)

function restoreRevision(revision) {
  restoreTarget.value = revision
}

async function confirmRestore() {
  try {
    const res = await axios.get(route('revisions.restore', restoreTarget.value.id))
    const payload = res.data
    Object.keys(payload).forEach(key => {
      if (key in form) form[key] = payload[key]
    })
    revisions.value = []
    revisionsOpen.value = false
    restoreTarget.value = null
  } catch {
    notify('Failed to restore revision. Please try again.', 'error')
  }
}

const daysUntilPublish = computed(() => {
  if (!form.published_at) return null
  const diff = Math.ceil((new Date(form.published_at) - Date.now()) / 86400000)
  return diff > 0 ? diff : null
})

function onStatusChange(newStatus) {
  if (newStatus === 'draft') {
    form.published_at = ''
  }
}

const showMediaPicker = ref(false)
const featuredImage   = ref(props.post.featured_image ?? null)

function onFeaturedImageSelect(media) {
  featuredImage.value    = media
  form.featured_image_id = media.id
}

function removeFeaturedImage() {
  featuredImage.value    = null
  form.featured_image_id = null
}

// Primary action button — keeps 'scheduled' status if already set, else publishes
function submitPrimary() {
  if (form.status !== 'scheduled') {
    form.status = 'published'
  }
  submit()
}

function submit() {
  if (form.status === 'scheduled' && !form.published_at) {
    notify('A scheduled date is required when status is "Scheduled".', 'error')
    return
  }
  form.put(route("posts.update", props.post.id), {
    preserveState:  true,
    preserveScroll: true,
    onSuccess: () => notify('Post saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}
</script>
