<template>
  <AppLayout title="Edit Post">
    <Head title="Edit Post" />

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
            <h2 class="text-lg font-semibold">Edit post</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1 max-w-xs">{{ post.title }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            @click="form.status = 'draft'; submit()"
            :disabled="form.processing"
            class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-50"
          >
            Save as draft
          </button>
          <button
            type="button"
            @click="form.status = 'published'; submit()"
            :disabled="form.processing"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : form.status === 'published' ? 'Update' : 'Publish' }}
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
            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
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
            <div class="flex justify-between mt-1">
              <p v-if="form.errors.excerpt" class="text-xs text-destructive">{{ form.errors.excerpt }}</p>
              <p v-else class="text-xs text-muted-foreground ml-auto">{{ (form.excerpt ?? '').length }}/500</p>
            </div>
          </div>

          <!-- Editor (tabbed: Rich Text / Block Editor) -->
          <div>
            <!-- Tab bar -->
            <div class="flex border-b border-border mb-0">
              <button
                type="button"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                :class="!form.use_block_editor
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground'"
                @click="switchTab(false)"
              >
                Rich Text
              </button>
              <button
                type="button"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
                :class="form.use_block_editor
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground'"
                @click="switchTab(true)"
              >
                Block Editor
              </button>
            </div>

            <!-- Rich text panel -->
            <div v-if="!form.use_block_editor" class="border border-t-0 rounded-b-lg overflow-hidden">
              <TiptapEditor v-model="form.body" />
            </div>

            <!-- Block editor panel -->
            <div v-else class="border border-t-0 rounded-b-lg overflow-hidden">
              <BlockEditor
                :model-value="form.blocks"
                :is-admin="$page.props.auth.user?.role === 'administrator'"
                @update:model-value="form.blocks = filterEmptyBlocks($event)"
              />
            </div>

            <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
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
              <input
                type="datetime-local"
                v-model="form.published_at"
                class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              />
              <p v-if="daysUntilPublish" class="text-xs text-indigo-600 mt-1">
                ⏱ publishes in {{ daysUntilPublish }} day{{ daysUntilPublish === 1 ? '' : 's' }}
              </p>
              <p v-if="form.errors.published_at" class="text-xs text-destructive mt-1">
                {{ form.errors.published_at }}
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
            <div v-else class="flex flex-wrap gap-2">
              <label
                v-for="cat in categories"
                :key="cat.id"
                class="flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="cat.id"
                  v-model="form.category_ids"
                  class="accent-primary rounded"
                />
                <span
                  class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                  :class="form.category_ids.includes(cat.id)
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'text-muted-foreground border-border hover:border-foreground'"
                >
                  {{ cat.name }}
                </span>
              </label>
            </div>
          </div>

          <!-- Tags -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Tags</h3>
            <div v-if="tags.length === 0" class="text-xs text-muted-foreground">
              No tags yet.
              <a :href="route('tags.create')" class="underline hover:text-foreground">Create one</a>
            </div>
            <div v-else class="flex flex-wrap gap-2">
              <label
                v-for="tag in tags"
                :key="tag.id"
                class="flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="tag.id"
                  v-model="form.tag_ids"
                  class="accent-primary rounded"
                />
                <span
                  class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                  :class="form.tag_ids.includes(tag.id)
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'text-muted-foreground border-border hover:border-foreground'"
                >
                  {{ tag.name }}
                </span>
              </label>
            </div>
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
                class="w-4 h-4 rounded border-border accent-primary"
              />
              <div>
                <span class="text-sm font-medium">Allow comments</span>
                <p class="text-xs text-muted-foreground">Let readers comment on this post</p>
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
        </div>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import TiptapEditor from "@/Components/TiptapEditor.vue";
import MediaPicker from '@/Components/MediaPicker.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'

const props = defineProps({
  post:       Object,
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
});

const form = useForm({
  title:             props.post.title,
  excerpt:           props.post.excerpt ?? "",
  body:              props.post.body ?? "",
  status:            props.post.status,
  published_at:      props.post.published_at ?? '',
  category_ids:      props.post.category_ids ?? [],
  tag_ids:           props.post.tag_ids ?? [],
  featured_image_id: props.post.featured_image_id ?? null,
  comments_enabled:  props.post.comments_enabled ?? true,
  use_block_editor:  props.post.use_block_editor ?? false,
  blocks:            props.post.blocks ?? [],
  meta_title:        props.post.meta_title ?? null,
  meta_description:  props.post.meta_description ?? null,
  meta_keywords:     props.post.meta_keywords ?? null,
});

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

function switchTab(toBlockEditor) {
  if (toBlockEditor === form.use_block_editor) return

  const hasContent = toBlockEditor ? (form.body ?? '').trim().length > 0
                                   : (form.blocks ?? []).length > 0

  if (hasContent) {
    const other = toBlockEditor ? 'rich text' : 'block editor'
    if (!confirm(`Switching tabs will clear your ${other} content if you save. Continue?`)) return
  }

  form.use_block_editor = toBlockEditor
  // Clear the inactive mode's data
  if (toBlockEditor) { form.body   = '' }
  else               { form.blocks = [] }
}

function filterEmptyBlocks(blocks) {
  return (blocks ?? []).filter(b => {
    const d = b.data ?? {}
    return Object.values(d).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
  })
}

function submit() {
  form.put(route("posts.update", props.post.id));
}
</script>
