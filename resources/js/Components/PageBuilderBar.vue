<!-- resources/js/Components/PageBuilderBar.vue -->
<template>
  <header class="flex items-center gap-3 px-3 h-11 shrink-0 border-b border-white/10 bg-[#181825] z-10">

    <!-- Back -->
    <a
      :href="backHref"
      class="inline-flex items-center justify-center w-7 h-7 rounded text-white/50 hover:text-white hover:bg-white/10 transition-colors shrink-0"
      title="Back to pages"
    >
      <ArrowLeft class="w-4 h-4" />
    </a>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Title -->
    <input
      :value="title"
      type="text"
      placeholder="Page title…"
      class="flex-1 min-w-0 bg-transparent rounded px-2 py-1 text-sm font-medium text-white placeholder:text-white/30 focus:outline-none"
      @input="$emit('update:title', $event.target.value)"
    />

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Slug -->
    <div class="flex items-center gap-1.5 shrink-0">
      <span class="text-xs text-white/40">/</span>
      <input
        :value="slug"
        type="text"
        placeholder="page-slug"
        class="w-36 bg-white/5 border border-white/10 rounded px-2 py-1 text-xs text-white/80 focus:outline-none focus:border-white/30 focus:bg-white/10 transition-colors"
        @input="$emit('update:slug', $event.target.value)"
      />
    </div>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Status pills -->
    <div class="flex items-center gap-1 shrink-0">
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
        :class="status === 'draft'
          ? 'bg-primary text-primary-foreground'
          : 'text-white/50 hover:text-white hover:bg-white/10'"
        @click="$emit('update:status', 'draft')"
      >Draft</button>
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
        :class="status === 'published'
          ? 'bg-primary text-primary-foreground'
          : 'text-white/50 hover:text-white hover:bg-white/10'"
        @click="$emit('update:status', 'published')"
      >Published</button>
    </div>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- SEO popover -->
    <div class="relative shrink-0" ref="seoRef">
      <button
        type="button"
        class="flex items-center gap-1 px-2 py-1 rounded text-xs text-white/50 hover:text-white hover:bg-white/10 transition-colors"
        @click="seoOpen = !seoOpen"
      >
        SEO
        <ChevronDown class="w-3 h-3 transition-transform" :class="{ 'rotate-180': seoOpen }" />
      </button>
      <Transition name="popover">
        <div
          v-if="seoOpen"
          class="absolute right-0 top-full mt-1 w-80 rounded-lg border border-white/10 bg-[#181825] shadow-2xl p-4 z-50 space-y-3"
        >
          <p class="text-xs font-semibold text-white/50 uppercase tracking-wider">SEO</p>
          <div>
            <label class="text-xs text-white/40 block mb-1">Meta title</label>
            <input
              :value="metaTitle"
              type="text"
              class="w-full bg-white/5 border border-white/10 rounded px-2 py-1.5 text-xs text-white focus:outline-none focus:border-white/30 transition-colors"
              @input="$emit('update:metaTitle', $event.target.value)"
            />
          </div>
          <div>
            <label class="text-xs text-white/40 block mb-1">Meta description</label>
            <textarea
              :value="metaDescription"
              rows="2"
              class="w-full bg-white/5 border border-white/10 rounded px-2 py-1.5 text-xs text-white resize-none focus:outline-none focus:border-white/30 transition-colors"
              @input="$emit('update:metaDescription', $event.target.value)"
            />
          </div>
          <div>
            <label class="text-xs text-white/40 block mb-1">Meta keywords</label>
            <input
              :value="metaKeywords"
              type="text"
              class="w-full bg-white/5 border border-white/10 rounded px-2 py-1.5 text-xs text-white focus:outline-none focus:border-white/30 transition-colors"
              @input="$emit('update:metaKeywords', $event.target.value)"
            />
          </div>
        </div>
      </Transition>
    </div>

    <!-- Revisions popover (only shown when revisions prop is provided) -->
    <div v-if="showRevisions" class="relative shrink-0" ref="revisionsRef">
      <button
        type="button"
        class="flex items-center gap-1 px-2 py-1 rounded text-xs text-white/50 hover:text-white hover:bg-white/10 transition-colors"
        @click="onRevisionsToggle"
      >
        Revisions
        <ChevronDown class="w-3 h-3 transition-transform" :class="{ 'rotate-180': revisionsOpen }" />
      </button>
      <Transition name="popover">
        <div
          v-if="revisionsOpen"
          class="absolute right-0 top-full mt-1 w-72 rounded-lg border border-white/10 bg-[#181825] shadow-2xl p-4 z-50"
        >
          <p class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Revisions</p>
          <div v-if="revisionsLoading" class="text-xs text-white/40 text-center py-3">Loading…</div>
          <div v-else-if="!revisions.length" class="text-xs text-white/40 text-center py-3">No revisions yet.</div>
          <div v-else class="space-y-1.5 max-h-60 overflow-y-auto">
            <div
              v-for="rev in revisions"
              :key="rev.id"
              class="flex items-center justify-between gap-2 rounded border border-white/10 px-2.5 py-1.5 hover:bg-white/5"
            >
              <div class="min-w-0">
                <p class="text-xs font-medium text-white truncate">{{ rev.user?.name ?? 'Unknown' }}</p>
                <p class="text-[11px] text-white/40">{{ new Date(rev.created_at).toLocaleString() }}</p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded border border-white/20 px-2 py-0.5 text-xs text-white/70 hover:bg-white/10 transition-colors"
                @click="$emit('restoreRevision', rev)"
              >Restore</button>
            </div>
          </div>
        </div>
      </Transition>
    </div>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Save -->
    <button
      type="button"
      :disabled="processing"
      class="shrink-0 rounded px-3 py-1.5 text-xs font-medium bg-primary text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors"
      @click="$emit('save')"
    >
      {{ processing ? 'Saving…' : saveLabel }}
    </button>

  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ArrowLeft, ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  backHref:         { type: String,  required: true },
  title:            { type: String,  default: '' },
  slug:             { type: String,  default: '' },
  status:           { type: String,  default: 'draft' },
  metaTitle:        { type: String,  default: '' },
  metaDescription:  { type: String,  default: '' },
  metaKeywords:     { type: String,  default: '' },
  processing:       { type: Boolean, default: false },
  saveLabel:        { type: String,  default: 'Save page' },
  showRevisions:    { type: Boolean, default: false },
  revisions:        { type: Array,   default: () => [] },
  revisionsLoading: { type: Boolean, default: false },
})

const emit = defineEmits([
  'update:title', 'update:slug', 'update:status',
  'update:metaTitle', 'update:metaDescription', 'update:metaKeywords',
  'save', 'restoreRevision', 'revisionsOpen',
])

const seoOpen       = ref(false)
const revisionsOpen = ref(false)
const seoRef        = ref(null)
const revisionsRef  = ref(null)

function onRevisionsToggle() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) emit('revisionsOpen')
}

// Close popovers when clicking outside
function onClickOutside(e) {
  if (seoRef.value && !seoRef.value.contains(e.target)) seoOpen.value = false
  if (revisionsRef.value && !revisionsRef.value.contains(e.target)) revisionsOpen.value = false
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<style scoped>
.popover-enter-active, .popover-leave-active { transition: opacity 0.1s, transform 0.1s; }
.popover-enter-from, .popover-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
