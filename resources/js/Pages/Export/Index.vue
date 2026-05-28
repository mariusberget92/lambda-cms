<template>
  <AppLayout title="Export">
    <Head title="Export" />

    <PageHeader title="Export" description="Download your content as a portable ZIP file">
      <template #actions>
        <a
          :href="route('import.index')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/>
          </svg>
          Go to Import
        </a>
      </template>
    </PageHeader>

    <div class="max-w-2xl space-y-6">

      <!-- Entity selection -->
      <div class="rounded-lg border bg-card p-6">
        <h3 class="text-sm font-semibold mb-1">Select content to export</h3>
        <p class="text-xs text-muted-foreground mb-4">Choose which data to include in the ZIP file.</p>
        <div class="space-y-3">
          <label
            v-for="entity in entityOptions"
            :key="entity.value"
            class="flex items-start gap-3 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="entity.value"
              v-model="selectedEntities"
              class="mt-0.5 h-4 w-4 rounded border-border accent-primary"
            />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium">{{ entity.label }}</span>
                <span class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium tabular-nums">
                  {{ counts[entity.value] }}
                </span>
              </div>
              <p class="text-xs text-muted-foreground mt-0.5">{{ entity.description }}</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Options -->
      <div class="rounded-lg border bg-card p-6">
        <h3 class="text-sm font-semibold mb-1">Options</h3>
        <p class="text-xs text-muted-foreground mb-4">Additional settings for this export.</p>
        <label class="flex items-start gap-3 cursor-pointer">
          <input
            type="checkbox"
            v-model="includeMediaFiles"
            class="mt-0.5 h-4 w-4 rounded border-border accent-primary"
          />
          <div>
            <span class="text-sm font-medium">Include media files</span>
            <p class="text-xs text-muted-foreground mt-0.5">
              Bundle the actual image and file uploads in the ZIP. This can significantly increase the export size.
              When disabled, only the media metadata is exported.
            </p>
          </div>
        </label>
      </div>

      <!-- Info note -->
      <div class="rounded-lg border border-border bg-muted/30 p-4 text-xs text-muted-foreground space-y-1">
        <p class="font-medium text-foreground">About the export format</p>
        <p>The ZIP contains JSON files per entity type and an optional <code class="font-mono bg-muted px-1 rounded">media/</code> folder.
          It can be re-imported into any Lambda CMS instance using the Import tool.</p>
        <p>Note: block editor media references are exported as-is and may need re-linking if media IDs differ on the target site.</p>
      </div>

      <!-- Export button -->
      <div class="flex items-center gap-3">
        <button
          type="button"
          :disabled="selectedEntities.length === 0"
          @click="startExport"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Download ZIP
        </button>
        <span v-if="selectedEntities.length === 0" class="text-xs text-muted-foreground">Select at least one entity to export.</span>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
  counts: Object,
})

const selectedEntities = ref(['posts', 'categories', 'tags', 'media'])
const includeMediaFiles = ref(false)

const entityOptions = [
  { value: 'posts',      label: 'Posts',      description: 'Post content, metadata, status, and taxonomy relationships.' },
  { value: 'categories', label: 'Categories', description: 'Category names, slugs, descriptions, and colors.' },
  { value: 'tags',       label: 'Tags',       description: 'Tag names and slugs.' },
  { value: 'media',      label: 'Media',      description: 'Media library metadata (alt text, descriptions, file info).' },
]

function startExport() {
  if (selectedEntities.value.length === 0) return

  const url = new URL(route('export.download'), window.location.origin)
  selectedEntities.value.forEach(e => url.searchParams.append('entities[]', e))
  if (includeMediaFiles.value) {
    url.searchParams.set('include_media_files', '1')
  }

  window.location.href = url.toString()
}
</script>
