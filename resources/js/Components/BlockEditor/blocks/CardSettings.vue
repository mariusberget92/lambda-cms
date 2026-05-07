<!-- resources/js/Components/BlockEditor/blocks/CardSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input
        :value="block.data.title ?? ''"
        type="text"
        placeholder="Card title…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Subtitle / label</label>
      <input
        :value="block.data.subtitle ?? ''"
        type="text"
        placeholder="Optional subtitle…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { subtitle: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Body</label>
      <textarea
        :value="block.data.body ?? ''"
        rows="3"
        placeholder="Card body text…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { body: $event.target.value } })"
      />
    </div>

    <!-- Image -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image URL</label>
      <div class="flex gap-1">
        <input
          :value="block.data.image?.url ?? ''"
          type="text"
          placeholder="https://…"
          class="flex-1 min-w-0 rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="updateImage('url', $event.target.value)"
        />
        <button type="button"
          class="shrink-0 rounded-md border bg-background px-2 py-1.5 text-xs hover:bg-muted transition-colors"
          @click="showPicker = true"
        >Library</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image aspect ratio</label>
      <SelectBox size="sm"
        :model-value="block.data.image?.aspectRatio ?? '16/9'"
        :data="[
          { value: '16/9', label: '16 : 9' },
          { value: '4/3',  label: '4 : 3' },
          { value: '1/1',  label: '1 : 1 (square)' },
          { value: '3/2',  label: '3 : 2' },
          { value: '3/4',  label: '3 : 4 (portrait)' },
        ]"
        @update:model-value="v => updateImage('aspectRatio', v)"
      />
    </div>

    <!-- Button -->
    <hr class="border-white/8" />
    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.button?.show ?? false" @update:model-value="v => updateButton('show', v)" />
      <span class="text-xs text-muted-foreground">Show button</span>
    </label>

    <template v-if="block.data.button?.show">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
        <input
          :value="block.data.button?.text ?? ''"
          type="text"
          placeholder="Learn more"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="updateButton('text', $event.target.value)"
        />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button URL</label>
        <input
          :value="block.data.button?.href ?? ''"
          type="text"
          placeholder="https://…"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="updateButton('href', $event.target.value)"
        />
      </div>
    </template>

    <MediaPicker v-model="showPicker" :dark="true" @select="onImageSelect" />
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Card style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="v in ['default', 'bordered', 'elevated', 'flat']"
          :key="v"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.variant ?? 'default') === v ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { variant: v } })"
        >{{ v }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="[val, label] in [['sm', 'SM'], ['md', 'MD'], ['lg', 'LG']]"
          :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.padding ?? 'md') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { padding: val } })"
        >{{ label }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import SelectBox      from '@/Components/SelectBox.vue'
import EditorCheckbox from '../EditorCheckbox.vue'
import MediaPicker    from '@/Components/MediaPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)

function updateImage(key, value) {
  emit('update', { id: props.block.id, data: { image: { ...(props.block.data.image ?? {}), [key]: value } } })
}
function updateButton(key, value) {
  emit('update', { id: props.block.id, data: { button: { ...(props.block.data.button ?? {}), [key]: value } } })
}
function onImageSelect(media) {
  showPicker.value = false
  updateImage('url', media.url)
}
</script>
