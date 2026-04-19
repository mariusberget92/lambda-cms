<template>
  <div class="space-y-4 p-4">
    <!-- Content tab -->
    <div v-show="!tab || tab === 'content'" class="space-y-4">
      <!-- Style -->
      <div class="space-y-1.5">
        <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Style</label>
        <div class="flex gap-1.5 flex-wrap">
          <button
            v-for="s in styles"
            :key="s.value"
            type="button"
            @click="emit('update', { id: block.id, data: { style: s.value } })"
            class="px-2.5 py-1 rounded text-xs font-medium border transition-colors"
            :class="block.data.style === s.value ? 'bg-primary text-primary-foreground border-primary' : 'hover:bg-accent'"
          >{{ s.label }}</button>
        </div>
      </div>

      <!-- Alignment -->
      <div class="space-y-1.5">
        <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Alignment</label>
        <div class="flex gap-1.5">
          <button v-for="a in alignments" :key="a.value" type="button"
            @click="emit('update', { id: block.id, data: { alignment: a.value } })"
            class="px-2.5 py-1 rounded text-xs font-medium border transition-colors"
            :class="block.data.alignment === a.value ? 'bg-primary text-primary-foreground border-primary' : 'hover:bg-accent'"
          >{{ a.label }}</button>
        </div>
      </div>

      <!-- Links -->
      <div class="space-y-1.5">
        <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Links</label>
        <div class="space-y-2">
          <div
            v-for="(link, i) in block.data.links"
            :key="i"
            class="rounded-md border bg-muted/20 p-2 space-y-1.5"
          >
            <div class="flex items-center gap-2">
              <input
                :value="link.label"
                @input="updateLink(i, 'label', $event.target.value)"
                type="text"
                placeholder="Label"
                class="flex-1 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              />
              <button type="button" @click="removeLink(i)" class="text-destructive hover:opacity-80 text-xs px-1">✕</button>
            </div>
            <input
              :value="link.url"
              @input="updateLink(i, 'url', $event.target.value)"
              type="text"
              placeholder="URL (e.g. /about)"
              class="w-full rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <label class="flex items-center gap-1.5 text-xs cursor-pointer">
              <EditorCheckbox :model-value="link.newTab" @update:model-value="v => updateLink(i, 'newTab', v)" />
              Open in new tab
            </label>
          </div>
        </div>
        <button
          type="button"
          @click="addLink"
          class="mt-1 w-full rounded-md border border-dashed px-3 py-1.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
        >+ Add link</button>
      </div>
    </div>

    <!-- Style tab -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <TypographyControl
        :model-value="block.data?.typography ?? {}"
        @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
      />
    </div>
  </div>
</template>

<script setup>
import TypographyControl from '../TypographyControl.vue'
import EditorCheckbox from '../EditorCheckbox.vue'

const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const styles = [
  { value: 'horizontal', label: 'Horizontal' },
  { value: 'vertical', label: 'Vertical' },
  { value: 'pills', label: 'Pills' },
  { value: 'minimal', label: 'Minimal' },
]

const alignments = [
  { value: 'left', label: 'Left' },
  { value: 'center', label: 'Center' },
  { value: 'right', label: 'Right' },
]

function addLink() {
  const links = [...(props.block.data.links ?? []), { label: '', url: '', newTab: false }]
  emit('update', { id: props.block.id, data: { links } })
}

function removeLink(i) {
  const links = (props.block.data.links ?? []).filter((_, idx) => idx !== i)
  emit('update', { id: props.block.id, data: { links } })
}

function updateLink(i, key, val) {
  const links = (props.block.data.links ?? []).map((l, idx) =>
    idx === i ? { ...l, [key]: val } : l
  )
  emit('update', { id: props.block.id, data: { links } })
}
</script>
