<template>
  <AppLayout title="New Post">
    <Head title="New Post" />

    <form @submit.prevent="submit">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a
            :href="route('posts.index')"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <h2 class="text-lg font-semibold">New post</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Create a new blog article</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            @click="form.status = 'draft'; submit()"
            :disabled="form.processing"
            class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save draft' }}
          </button>
          <button
            type="button"
            @click="form.status = 'published'; submit()"
            :disabled="form.processing"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
          >
            {{ form.processing ? 'Publishing...' : 'Publish' }}
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
          <div class="space-y-2">
            <!-- Mode switcher -->
            <div class="flex items-center gap-1 rounded-lg border border-border bg-muted/40 p-1 w-fit">
              <button
                v-for="mode in editorModes"
                :key="mode.value"
                type="button"
                class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                :class="editorMode === mode.value
                  ? 'bg-background text-foreground shadow-sm'
                  : 'text-muted-foreground hover:text-foreground'"
                @click="editorMode = mode.value"
              >
                <Icon :icon="mode.icon" width="12" height="12" />
                {{ mode.label }}
              </button>
            </div>

            <div class="rounded-lg border overflow-hidden">
              <TiptapEditor v-if="editorMode === 'wysiwyg'" v-model="form.body" />
              <MarkdownEditor v-else v-model="form.body" />
            </div>

            <!-- .md file upload (markdown mode only) -->
            <div v-if="editorMode === 'markdown'" class="flex items-center gap-2">
              <label
                class="inline-flex items-center gap-1.5 cursor-pointer rounded-md border px-3 py-1.5 text-xs font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
              >
                <Icon icon="lucide:upload" width="12" height="12" />
                Import .md file
                <input type="file" accept=".md,text/markdown" class="sr-only" @change="importMarkdownFile" />
              </label>
              <span class="text-xs text-muted-foreground">CommonMark + GFM supported</span>
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
                <div class="flex-1 min-w-0">
                  <span class="text-sm font-medium">Scheduled</span>
                  <p class="text-xs text-muted-foreground">Auto-publishes at a set time</p>
                </div>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" @change="onStatusChange('published')" />
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
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
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

          <!-- Custom JS -->
          <div class="rounded-lg border bg-card">
            <button
              type="button"
              class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium"
              @click="customJsOpen = !customJsOpen"
            >
              <span>Custom JS</span>
              <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': customJsOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="customJsOpen" class="border-t px-4 py-3">
              <p class="text-xs text-muted-foreground mb-2">JavaScript injected on this post's page only.</p>
              <JsEditor v-model="form.custom_js" />
            </div>
          </div>
        </div>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import TiptapEditor from "@/components/TiptapEditor.vue";
import MarkdownEditor from "@/Components/MarkdownEditor.vue";
import MediaPicker from '@/components/MediaPicker.vue'
import DateTimePicker from '@/Components/DateTimePicker.vue'
import TagInput from '@/Components/TagInput.vue'
import CategoryInput from '@/Components/CategoryInput.vue'
import JsEditor from '@/Components/JsEditor.vue'
import { useNotifications } from '@/composables/useNotifications.js'
const { notify } = useNotifications()
const page = usePage()

// ── Editor mode ──────────────────────────────────────────────────────────────
const editorModes = [
  { value: 'wysiwyg',   label: 'Rich Text', icon: 'lucide:pilcrow' },
  { value: 'markdown',  label: 'Markdown',  icon: 'lucide:file-text' },
]
const editorMode = ref('wysiwyg')

watch(editorMode, (mode) => {
  form.body_format = mode === 'markdown' ? 'markdown' : 'html'
})

function importMarkdownFile(event) {
  const file = event.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (e) => { form.body = e.target.result ?? '' }
  reader.readAsText(file)
  event.target.value = ''
}

defineProps({
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
});

const form = useForm({
  title:             "",
  excerpt:           "",
  body:              "",
  body_format:       "html",
  status:            "draft",
  published_at:      '',
  category_ids:      [],
  tag_ids:           [],
  new_tag_names:     [],
  featured_image_id: null,
  featured:          false,
  comments_enabled:  true,
  meta_title:        null,
  meta_description:  null,
  meta_keywords:     null,
  custom_js:         null,
});

const customJsOpen = ref(false)

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
const featuredImage   = ref(null)

function onFeaturedImageSelect(media) {
  featuredImage.value    = media
  form.featured_image_id = media.id
}

function removeFeaturedImage() {
  featuredImage.value    = null
  form.featured_image_id = null
}

function submit() {
  if (form.status === 'scheduled' && !form.published_at) {
    notify('A scheduled date is required when status is "Scheduled".', 'error')
    return
  }
  form.post(route("posts.store"), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}
</script>
