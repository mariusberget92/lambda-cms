<!-- resources/js/Pages/Pages/Create.vue -->
<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { watch }   from 'vue'
import { filterEmptyBlocks } from '@/lib/utils.js'

const authUser = usePage().props.auth.user

const props = defineProps({
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
})

const form = useForm({
  title:            '',
  slug:             '',
  status:           'draft',
  blocks:           [],
  meta_title:       '',
  meta_description: '',
  meta_keywords:    '',
})

// Auto-generate slug from title
watch(() => form.title, (val, oldVal) => {
  if (!form.slug || form.slug === slugify(oldVal)) {
    form.slug = slugify(val)
  }
})

function slugify(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.post(route('pages.store'))
}
</script>

<template>
  <AppLayout title="New Page">
    <Head title="New Page" />
    <form @submit.prevent="submit">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a :href="route('pages.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <h2 class="text-lg font-semibold">New page</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Create a custom site page</p>
          </div>
        </div>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? 'Saving...' : 'Save page' }}
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main: block editor -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Title -->
          <div>
            <input
              v-model="form.title"
              type="text"
              placeholder="Page title..."
              class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
          </div>

          <!-- Block editor -->
          <BlockEditor
            :model-value="form.blocks"
            :is-admin="authUser?.role === 'administrator'"
            :meta="{ categories: props.categories, tags: props.tags }"
            @update:model-value="form.blocks = $event"
          />
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Slug -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">URL Slug</h3>
            <div class="flex items-center gap-1 text-sm text-muted-foreground mb-1">
              <span>/</span>
              <input
                v-model="form.slug"
                type="text"
                placeholder="page-slug"
                class="flex-1 rounded border bg-background px-2 py-1 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': form.errors.slug }"
              />
            </div>
            <p v-if="form.errors.slug" class="mt-1 text-xs text-destructive">{{ form.errors.slug }}</p>
          </div>

          <!-- Status -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
                <span class="text-sm font-medium">Draft</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" />
                <span class="text-sm font-medium">Published</span>
              </label>
            </div>
          </div>

          <!-- SEO accordion -->
          <details class="rounded-lg border bg-card">
            <summary class="px-4 py-3 text-sm font-medium cursor-pointer">SEO (optional)</summary>
            <div class="px-4 pb-4 space-y-3 border-t pt-3">
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
                <input v-model="form.meta_title" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
                <textarea v-model="form.meta_description" rows="3" class="w-full rounded border bg-background px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
                <input v-model="form.meta_keywords" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
            </div>
          </details>
        </div>
      </div>
    </form>
  </AppLayout>
</template>
