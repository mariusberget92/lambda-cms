<!-- resources/js/Components/BlockEditor/blocks/SliderSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Slides</label>
      <button type="button" class="text-xs px-2 py-1 rounded-md bg-primary/20 text-primary hover:bg-primary/30 transition-colors" @click="addSlide">+ Add slide</button>
    </div>

    <div v-for="(slide, i) in slides" :key="i" class="border border-white/10 rounded-lg p-3 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-muted-foreground">Slide {{ i + 1 }}</span>
        <button type="button" class="text-xs text-destructive hover:opacity-80" @click="removeSlide(i)">Remove</button>
      </div>

      <div>
        <label class="text-xs text-muted-foreground block mb-1">Image URL</label>
        <div class="flex gap-1">
          <input
            :value="slide.image || ''"
            type="text"
            placeholder="https://…"
            class="flex-1 min-w-0 rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateSlide(i, 'image', $event.target.value)"
          />
          <button type="button" class="shrink-0 rounded-md border bg-background px-2 py-1.5 text-xs hover:bg-muted transition-colors" @click="openPicker(i)">Library</button>
        </div>
      </div>

      <div>
        <label class="text-xs text-muted-foreground block mb-1">Title</label>
        <input
          :value="slide.title || ''"
          type="text"
          placeholder="Slide title…"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="updateSlide(i, 'title', $event.target.value)"
        />
      </div>

      <div>
        <label class="text-xs text-muted-foreground block mb-1">Description</label>
        <textarea
          :value="slide.description || ''"
          rows="2"
          placeholder="Optional description…"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
          @input="updateSlide(i, 'description', $event.target.value)"
        />
      </div>
    </div>

    <div v-if="slides.length === 0" class="text-center py-6 text-xs text-muted-foreground border border-dashed border-white/10 rounded-lg">
      No slides yet. Click "+ Add slide" to start.
    </div>

    <MediaPicker v-model="showPicker" :dark="true" @select="onPickerSelect" />
  </div>

  <!-- Style tab -->
  <div v-show="tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Height</label>
      <input
        :value="block.data.height || '320px'"
        type="text"
        placeholder="320px"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { height: $event.target.value } })"
      />
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.overlay ?? false" @update:model-value="v => emit('update', { id: block.id, data: { overlay: v } })" />
      <span class="text-xs text-muted-foreground">Dark overlay on images</span>
    </label>

    <div v-if="block.data.overlay">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Overlay color</label>
      <ColorPicker :model-value="block.data.overlayColor || 'rgba(0,0,0,0.4)'" @update:model-value="v => emit('update', { id: block.id, data: { overlayColor: v } })" />
    </div>

    <hr class="border-white/8" />

    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.showArrows ?? true" @update:model-value="v => emit('update', { id: block.id, data: { showArrows: v } })" />
      <span class="text-xs text-muted-foreground">Show navigation arrows</span>
    </label>

    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.showDots ?? true" @update:model-value="v => emit('update', { id: block.id, data: { showDots: v } })" />
      <span class="text-xs text-muted-foreground">Show dot indicators</span>
    </label>

    <hr class="border-white/8" />

    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.autoplay ?? false" @update:model-value="v => emit('update', { id: block.id, data: { autoplay: v } })" />
      <span class="text-xs text-muted-foreground">Autoplay</span>
    </label>

    <div v-if="block.data.autoplay">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Delay (seconds)</label>
      <input
        :value="block.data.autoplayDelay || 4"
        type="number"
        min="1"
        max="30"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { autoplayDelay: Number($event.target.value) } })"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import EditorCheckbox from '../EditorCheckbox.vue'
import ColorPicker    from '../ColorPicker.vue'
import MediaPicker    from '@/Components/MediaPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const slides = computed(() => props.block.data?.slides || [])
const showPicker = ref(false)
const pickerSlideIndex = ref(null)

function addSlide() {
  emit('update', { id: props.block.id, data: { slides: [...slides.value, { image: '', title: '', description: '' }] } })
}
function removeSlide(i) {
  emit('update', { id: props.block.id, data: { slides: slides.value.filter((_, idx) => idx !== i) } })
}
function updateSlide(i, key, value) {
  const updated = slides.value.map((s, idx) => idx === i ? { ...s, [key]: value } : s)
  emit('update', { id: props.block.id, data: { slides: updated } })
}
function openPicker(i) {
  pickerSlideIndex.value = i
  showPicker.value = true
}
function onPickerSelect(media) {
  showPicker.value = false
  if (pickerSlideIndex.value !== null) {
    updateSlide(pickerSlideIndex.value, 'image', media.url)
    pickerSlideIndex.value = null
  }
}
</script>
