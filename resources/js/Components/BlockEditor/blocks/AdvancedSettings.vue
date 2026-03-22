<!-- resources/js/components/BlockEditor/blocks/AdvancedSettings.vue -->
<template>
  <div class="space-y-3 pt-3 border-t mt-3">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Advanced</p>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Block label</label>
      <input type="text" :value="block.blockName ?? ''" @input="update('blockName', $event.target.value)"
        placeholder="e.g. Hero heading"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs" />
      <p class="text-[10px] text-muted-foreground mt-1">Shown in the canvas and layers panel.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font family</label>
      <SelectBox
        :model-value="block.fontFamily ?? ''"
        :data="[{ value: '', label: 'Site default' }, ...FONTS.map(f => ({ value: f, label: f }))]"
        @update:model-value="v => update('fontFamily', v)"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom ID</label>
      <input type="text" :value="block.customId ?? ''" @input="update('customId', $event.target.value)"
        placeholder="my-section"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom classes</label>
      <input type="text" :value="block.customClasses ?? ''" @input="update('customClasses', $event.target.value)"
        placeholder="my-class another-class"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom CSS</label>
      <textarea
        :value="block.customCss ?? ''"
        @input="update('customCss', $event.target.value)"
        rows="4"
        placeholder="color: red;&#10;font-size: 1.2em;"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono resize-none"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Scoped to this block automatically.</p>
    </div>

  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Nunito',
  'Source Sans 3', 'Merriweather', 'Playfair Display', 'Lora', 'PT Serif', 'Libre Baskerville',
  'EB Garamond', 'Oswald', 'Bebas Neue', 'DM Sans', 'DM Serif Display', 'Figtree',
  'Plus Jakarta Sans', 'Outfit', 'Manrope', 'Sora', 'Space Grotesk',
  'JetBrains Mono', 'Fira Code', 'Source Code Pro',
]

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, [key]: value })
}
</script>
