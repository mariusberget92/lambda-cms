<!-- resources/js/Components/BlockEditor/blocks/TestimonialSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Quote</label>
      <textarea
        :value="block.data.quote ?? ''"
        rows="3"
        placeholder="Testimonial quote…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { quote: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Author</label>
      <input
        :value="block.data.author ?? ''"
        type="text"
        placeholder="Jane Doe"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { author: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Role</label>
      <input
        :value="block.data.role ?? ''"
        type="text"
        placeholder="CEO"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { role: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Company</label>
      <input
        :value="block.data.company ?? ''"
        type="text"
        placeholder="Acme Corp"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { company: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Avatar URL</label>
      <div class="flex gap-1">
        <input
          :value="block.data.avatar?.url ?? ''"
          type="text"
          placeholder="https://…"
          class="flex-1 min-w-0 rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="updateAvatar('url', $event.target.value)"
        />
        <button type="button"
          class="shrink-0 rounded-md border bg-background px-2 py-1.5 text-xs hover:bg-muted transition-colors"
          @click="showPicker = true"
        >Library</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">
        Star rating
        <span class="text-foreground font-semibold">{{ block.data.rating ?? 5 }} / 5</span>
      </label>
      <input
        type="range" min="0" max="5" step="1"
        :value="block.data.rating ?? 5"
        class="w-full accent-primary"
        @input="emit('update', { id: block.id, data: { rating: Number($event.target.value) } })"
      />
      <p class="text-[10px] text-muted-foreground mt-0.5">Set to 0 to hide stars</p>
    </div>

    <MediaPicker v-model="showPicker" :dark="true" @select="onAvatarSelect" />
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Layout</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="[val, label] in [['card', 'Card'], ['inline', 'Inline'], ['minimal', 'Minimal']]"
          :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.variant ?? 'card') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { variant: val } })"
        >{{ label }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)

function updateAvatar(key, value) {
  emit('update', { id: props.block.id, data: { avatar: { ...(props.block.data.avatar ?? {}), [key]: value } } })
}
function onAvatarSelect(media) {
  showPicker.value = false
  updateAvatar('url', media.url)
}
</script>
