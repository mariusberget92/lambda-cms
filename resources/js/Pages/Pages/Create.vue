<!-- resources/js/Pages/Pages/Create.vue -->
<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { watch, ref } from 'vue'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ChevronDown, ArrowLeft } from 'lucide-vue-next'
import { useNotifications } from '@/composables/useNotifications.js'
const { notify } = useNotifications()

const seoOpen = ref(false)

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
  form.post(route('pages.store'), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <AppLayout title="New Page">
    <Head title="New Page" />
    <form @submit.prevent="submit" class="space-y-4">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a :href="route('pages.index')" title="Go back" aria-label="Go back" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <ArrowLeft class="w-4 h-4" />
          </a>
          <div>
            <h2 class="text-lg font-semibold">New page</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Create a custom site page</p>
          </div>
        </div>
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Saving...' : 'Save page' }}
        </button>
      </div>

      <!-- Meta card: title + slug/status/SEO inline -->
      <div class="rounded-lg border bg-card p-4 space-y-3">
        <!-- Title -->
        <div>
          <input
            v-model="form.title"
            type="text"
            placeholder="Page title..."
            class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.title }"
          />
          <p v-if="form.errors.title" class="text-xs text-destructive mt-1">{{ form.errors.title }}</p>
        </div>

        <!-- Inline sub-fields: slug · status · SEO -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-2 border-t border-border/50">
          <!-- Slug -->
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-xs font-medium text-muted-foreground shrink-0">Slug /</span>
            <input
              v-model="form.slug"
              type="text"
              placeholder="page-slug"
              class="w-44 rounded border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.slug }"
            />
            <p v-if="form.errors.slug" class="text-xs text-destructive mt-1">{{ form.errors.slug }}</p>
          </div>
          <div class="h-4 w-px bg-border hidden sm:block shrink-0" />

          <!-- Status -->
          <div class="flex items-center gap-4 shrink-0">
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
              <span class="text-sm font-medium">Draft</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input type="radio" v-model="form.status" value="published" class="accent-primary" />
              <span class="text-sm font-medium">Published</span>
            </label>
          </div>

          <div class="h-4 w-px bg-border hidden sm:block shrink-0" />

          <!-- SEO toggle -->
          <button type="button" @click="seoOpen = !seoOpen"
            class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors shrink-0">
            SEO
            <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': seoOpen }" />
          </button>
        </div>

        <!-- SEO fields (expanded) -->
        <div v-if="seoOpen" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-border/50">
          <div>
            <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
            <input v-model="form.meta_title" type="text"
              class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div>
            <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
            <textarea v-model="form.meta_description" rows="2"
              class="w-full rounded border bg-background px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div>
            <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
            <input v-model="form.meta_keywords" type="text"
              class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
        </div>
      </div>

      <!-- Block editor: full remaining width -->
      <BlockEditor
        :model-value="form.blocks"
        :is-admin="authUser?.role === 'administrator'"
        :meta="{ categories: props.categories, tags: props.tags }"
        @update:model-value="form.blocks = $event"
      />

    </form>
  </AppLayout>
</template>
