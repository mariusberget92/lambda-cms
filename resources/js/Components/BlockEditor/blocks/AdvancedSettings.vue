<!-- resources/js/components/BlockEditor/blocks/AdvancedSettings.vue -->
<template>
  <div class="space-y-3 pt-3 border-t mt-3">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Spacing</p>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-2">Padding</label>
      <SpacingControl
        :model-value="block.data?.padding && typeof block.data.padding === 'object' ? block.data.padding : {}"
        allow-auto
        @update:model-value="v => updateData('padding', v)"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-2">Margin</label>
      <SpacingControl
        :model-value="block.data?.margin && typeof block.data.margin === 'object' ? block.data.margin : {}"
        allow-auto
        @update:model-value="v => updateData('margin', v)"
      />
    </div>

    <!-- Background -->
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground pt-1 border-t">Background</p>

    <div class="space-y-2">
      <div class="flex gap-1 flex-wrap">
        <button type="button" v-for="opt in ['none','color','gradient','image']" :key="opt"
          class="px-2 py-1 text-xs rounded border transition-colors"
          :class="(block.data?.advBgType ?? 'none') === opt ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
          @click="updateData('advBgType', opt)">
          {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
        </button>
      </div>

      <!-- Color -->
      <div v-if="block.data?.advBgType === 'color'">
        <ColorPicker
          :model-value="block.data?.advBgColor ?? '#ffffff'"
          default="#ffffff"
          @update:model-value="v => updateData('advBgColor', v)"
        />
      </div>

      <!-- Gradient -->
      <div v-if="block.data?.advBgType === 'gradient'" class="space-y-2">
        <div class="flex gap-4 items-start">
          <div>
            <label class="text-[10px] text-muted-foreground block mb-1">From</label>
            <ColorPicker
              :model-value="block.data?.advBgGradient?.from ?? '#3b4252'"
              default="#3b4252"
              :show-value="false"
              @update:model-value="v => updateNestedData('advBgGradient', 'from', v)"
            />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground block mb-1">To</label>
            <ColorPicker
              :model-value="block.data?.advBgGradient?.to ?? '#4c566a'"
              default="#4c566a"
              :show-value="false"
              @update:model-value="v => updateNestedData('advBgGradient', 'to', v)"
            />
          </div>
        </div>
        <SelectBox size="sm"
          :model-value="block.data?.advBgGradient?.direction ?? 'to-r'"
          :data="[
            { value: 'to-r',  label: 'Left → Right' },
            { value: 'to-l',  label: 'Right → Left' },
            { value: 'to-b',  label: 'Top → Bottom' },
            { value: 'to-t',  label: 'Bottom → Top' },
            { value: 'to-br', label: 'Top-left → Bottom-right' },
            { value: 'to-bl', label: 'Top-right → Bottom-left' },
          ]"
          @update:model-value="v => updateNestedData('advBgGradient', 'direction', v)"
        />
      </div>

      <!-- Image -->
      <div v-if="block.data?.advBgType === 'image'" class="space-y-2">
        <div class="flex gap-1">
          <input
            :value="block.data?.advBgImage?.url ?? ''"
            type="text"
            placeholder="https://… or pick from library"
            class="flex-1 min-w-0 rounded border bg-background px-2 py-1 text-xs"
            @input="updateNestedData('advBgImage', 'url', $event.target.value)"
          />
          <button type="button"
            class="shrink-0 rounded border bg-background px-2 py-1 text-xs hover:bg-muted transition-colors"
            @click="showImagePicker = true">
            Library
          </button>
        </div>
        <div>
          <label class="text-[10px] text-muted-foreground block mb-1">Position</label>
          <div class="flex gap-1 flex-wrap">
            <button type="button" v-for="pos in ['center','top','bottom','left','right']" :key="pos"
              class="px-2 py-0.5 text-[10px] rounded border transition-colors capitalize"
              :class="(block.data?.advBgImage?.position ?? 'center') === pos ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
              @click="updateNestedData('advBgImage', 'position', pos)">
              {{ pos }}
            </button>
          </div>
        </div>
        <div>
          <label class="text-[10px] text-muted-foreground block mb-1">Size</label>
          <div class="flex gap-1">
            <button type="button" v-for="sz in ['cover','contain','auto']" :key="sz"
              class="flex-1 px-2 py-0.5 text-[10px] rounded border transition-colors capitalize"
              :class="(block.data?.advBgImage?.size ?? 'cover') === sz ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
              @click="updateNestedData('advBgImage', 'size', sz)">
              {{ sz }}
            </button>
          </div>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox"
            :checked="block.data?.advBgImage?.parallax ?? false"
            @change="updateNestedData('advBgImage', 'parallax', $event.target.checked)"
          />
          <span class="text-xs text-muted-foreground">Parallax (fixed)</span>
        </label>
      </div>
    </div>

    <MediaPicker v-model="showImagePicker" :dark="true" @select="onBgImageSelect" />

    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground pt-1 border-t">Advanced</p>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Block label</label>
      <input type="text" :value="block.blockName ?? ''" @input="update('blockName', $event.target.value)"
        placeholder="e.g. Hero heading"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs" />
      <p class="text-[10px] text-muted-foreground mt-1">Shown in the canvas and layers panel.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font family</label>
      <SelectBox size="sm"
        :model-value="block.fontFamily ?? ''"
        :data="[{ value: '', label: 'Site default' }, ...FONTS.map(f => ({ value: f, label: f }))]"
        :item-style="item => item.value ? { fontFamily: item.value } : {}"
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
      <CssEditor
        :model-value="block.customCss ?? ''"
        @update:model-value="v => update('customCss', v)"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Scoped to this block automatically.</p>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'
import SpacingControl from '../SpacingControl.vue'
import ColorPicker from '../ColorPicker.vue'
import CssEditor from '../CssEditor.vue'
import MediaPicker from '@/components/MediaPicker.vue'

const FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Nunito',
  'Source Sans 3', 'Merriweather', 'Playfair Display', 'Lora', 'PT Serif', 'Libre Baskerville',
  'EB Garamond', 'Oswald', 'Bebas Neue', 'DM Sans', 'DM Serif Display', 'Figtree',
  'Plus Jakarta Sans', 'Outfit', 'Manrope', 'Sora', 'Space Grotesk',
  'JetBrains Mono', 'Fira Code', 'Source Code Pro',
]

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

const showImagePicker = ref(false)

function onBgImageSelect(media) {
  showImagePicker.value = false
  updateNestedData('advBgImage', 'url', media.url)
}

function update(key, value) {
  emit('update', { id: props.block.id, [key]: value })
}

function updateData(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}

function updateNestedData(key, subKey, value) {
  const current = props.block.data?.[key] ?? {}
  emit('update', { id: props.block.id, data: { [key]: { ...current, [subKey]: value } } })
}
</script>
