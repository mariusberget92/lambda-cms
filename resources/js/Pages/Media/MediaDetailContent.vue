<template>
  <!-- Preview -->
  <div class="aspect-video w-full bg-muted rounded-t-lg overflow-hidden flex items-center justify-center">
    <img
      v-if="activeItem.type === 'image'"
      :src="activeItem.url"
      :alt="activeItem.alt ?? activeItem.original_filename"
      class="w-full h-full object-contain cursor-zoom-in"
      @click="emit('lightbox', activeItem)"
    />
    <svg v-else class="w-10 h-10 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
  </div>

  <div class="p-4 flex flex-col gap-4">
    <!-- Filename + meta -->
    <div>
      <p class="text-sm font-medium break-all leading-tight">{{ activeItem.original_filename }}</p>
      <p class="text-xs text-muted-foreground mt-0.5">
        {{ activeItem.formatted_size }}
        <template v-if="activeItem.width && activeItem.height"> · {{ activeItem.width }}×{{ activeItem.height }}</template>
      </p>
      <p class="text-xs text-muted-foreground">{{ formatDateTime(activeItem.created_at) }}</p>
      <p v-if="activeItem.uploader" class="text-xs text-muted-foreground">Uploaded by {{ activeItem.uploader }}</p>
    </div>

    <!-- Used in -->
    <div class="flex flex-col gap-1">
      <p class="text-xs font-medium text-foreground">Used in</p>
      <div v-if="usedInLoading" class="space-y-1.5">
        <div class="h-3 rounded bg-muted animate-pulse w-3/4" />
        <div class="h-3 rounded bg-muted animate-pulse w-1/2" />
      </div>
      <p v-else-if="!usedIn?.length" class="text-xs text-muted-foreground">Not used anywhere</p>
      <ul v-else class="space-y-1">
        <li v-for="post in usedIn" :key="post.id">
          <a
            :href="route('posts.edit', post.id)"
            target="_blank"
            rel="noopener"
            class="text-xs text-primary hover:underline underline-offset-2 line-clamp-1"
          >
            {{ post.title }}
          </a>
        </li>
      </ul>
    </div>

    <!-- Alt text -->
    <div class="flex flex-col gap-1">
      <label class="text-xs font-medium text-foreground">Alt text</label>
      <input
        :value="detailForm.alt"
        type="text"
        placeholder="Describe this image..."
        class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update:alt', $event.target.value)"
      />
    </div>

    <!-- Description -->
    <div class="flex flex-col gap-1">
      <label class="text-xs font-medium text-foreground">Description</label>
      <textarea
        :value="detailForm.description"
        rows="3"
        placeholder="Optional longer description..."
        class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update:description', $event.target.value)"
      />
      <p
        class="text-xs text-right"
        :class="detailForm.description.length >= 1900 ? 'text-destructive' : 'text-muted-foreground'"
      >
        {{ detailForm.description.length }} / 2000
      </p>
    </div>

    <!-- Copy URL -->
    <div class="flex flex-col gap-1">
      <label class="text-xs font-medium text-foreground">URL</label>
      <div class="flex gap-1">
        <input
          :value="activeItem.url"
          type="text"
          readonly
          class="flex-1 rounded-md border bg-muted px-3 py-2 text-xs text-muted-foreground truncate"
        />
        <button
          type="button"
          class="shrink-0 rounded-md border px-2 py-2 text-xs hover:bg-accent transition-colors"
          @click="emit('copy')"
          :title="copied ? 'Copied!' : 'Copy URL'"
        >
          <svg v-if="!copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5 text-status-success-fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col gap-2 pt-1">
      <button
        type="button"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
        :disabled="saving"
        @click="emit('save')"
      >
        {{ saving ? 'Saving...' : 'Save changes' }}
      </button>
      <button
        type="button"
        class="w-full rounded-md border border-destructive/50 px-4 py-2 text-sm text-destructive hover:bg-destructive hover:text-destructive-foreground transition-colors"
        @click="emit('delete')"
      >
        Delete file
      </button>
      <button
        type="button"
        class="w-full rounded-md border px-4 py-2 text-sm text-muted-foreground hover:bg-accent transition-colors"
        @click="emit('close')"
      >
        Close
      </button>
    </div>
  </div>
</template>

<script setup>
import { formatDateTime } from '@/lib/utils.js'

defineProps({
  activeItem:     { type: Object, required: true },
  detailForm:     { type: Object, required: true },
  usedIn:         { type: [Array, null], default: null },
  usedInLoading:  { type: Boolean, default: false },
  copied:         { type: Boolean, default: false },
  saving:         { type: Boolean, default: false },
})

const emit = defineEmits(['copy', 'save', 'delete', 'close', 'lightbox', 'update:alt', 'update:description'])
</script>
