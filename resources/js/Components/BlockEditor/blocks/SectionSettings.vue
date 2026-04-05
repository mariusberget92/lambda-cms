<script setup>
import { computed, ref } from 'vue'
import SelectBox   from '@/Components/SelectBox.vue'
import SpacingControl from '../SpacingControl.vue'
import MediaPicker from '@/Components/MediaPicker.vue'
import ColorPicker from '../ColorPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab: { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit   = defineEmits(['update'])

const d = computed(() => props.block.data ?? {})

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}

function updateNested(key, subKey, value) {
  emit('update', { id: props.block.id, data: { [key]: { ...(d.value[key] ?? {}), [subKey]: value } } })
}

const bgImageMode  = ref('library')
const showBgPicker = ref(false)

function onBgMediaSelect(media) {
  showBgPicker.value = false
  updateNested('bgImage', 'url', media.url)
}
</script>

<template>
  <div>

    <!-- Style tab fields -->
    <div v-show="!tab || tab === 'style'" class="space-y-4">

      <!-- Background -->
      <div class="space-y-2">
        <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Background</label>

        <div class="flex gap-1 flex-wrap">
          <button type="button" v-for="opt in ['none','color','image','gradient']" :key="opt"
            class="px-2 py-1 text-xs rounded border transition-colors"
            :class="d.bgType === opt ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
            @click="update('bgType', opt)">
            {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
          </button>
        </div>

        <!-- Color picker -->
        <div v-if="d.bgType === 'color'">
          <ColorPicker
            :model-value="d.bgColor ?? '#ffffff'"
            default="#ffffff"
            @update:model-value="v => update('bgColor', v)"
          />
        </div>

        <!-- Image picker -->
        <div v-if="d.bgType === 'image'" class="space-y-2">

          <!-- Source toggle -->
          <div class="flex gap-1 p-0.5 rounded-md bg-muted w-fit">
            <button type="button"
              class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
              :class="bgImageMode === 'library' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
              @click="bgImageMode = 'library'">Library</button>
            <button type="button"
              class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
              :class="bgImageMode === 'url' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
              @click="bgImageMode = 'url'">URL</button>
          </div>

          <!-- Library mode -->
          <div v-if="bgImageMode === 'library'">
            <div v-if="d.bgImage?.url" class="rounded overflow-hidden border mb-2 max-h-24">
              <img :src="d.bgImage.url" class="w-full h-full object-cover" />
            </div>
            <button type="button"
              class="w-full rounded border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
              @click="showBgPicker = true">
              {{ d.bgImage?.url ? 'Change image' : 'Select image' }}
            </button>
            <MediaPicker v-model="showBgPicker" :dark="true" @select="onBgMediaSelect" />
          </div>

          <!-- URL mode -->
          <input v-else type="url" :value="d.bgImage?.url ?? ''"
            @input="updateNested('bgImage', 'url', $event.target.value)"
            placeholder="https://..."
            class="w-full rounded border border-border bg-background px-2 py-1 text-xs" />
          <SelectBox
            :model-value="d.bgImage?.position ?? 'center'"
            :data="[
              { value: 'center',       label: 'Center' },
              { value: 'top',          label: 'Top' },
              { value: 'bottom',       label: 'Bottom' },
              { value: 'left center',  label: 'Left' },
              { value: 'right center', label: 'Right' },
            ]"
            @update:model-value="v => updateNested('bgImage', 'position', v)"
          />
          <SelectBox
            :model-value="d.bgImage?.size ?? 'cover'"
            :data="[
              { value: 'cover',   label: 'Cover' },
              { value: 'contain', label: 'Contain' },
              { value: 'auto',    label: 'Auto' },
            ]"
            @update:model-value="v => updateNested('bgImage', 'size', v)"
          />
          <div class="flex items-center gap-2">
            <input type="checkbox" id="section-parallax"
              :checked="d.bgImage?.parallax"
              @change="updateNested('bgImage', 'parallax', $event.target.checked)"
              class="rounded border-border accent-nord-green" />
            <label for="section-parallax" class="text-xs text-muted-foreground">Parallax (fixed attachment)</label>
          </div>
        </div>

        <!-- Gradient picker -->
        <div v-if="d.bgType === 'gradient'" class="space-y-2">
          <div class="flex gap-4 items-start">
            <div>
              <label class="text-[10px] text-muted-foreground block mb-1">From</label>
              <ColorPicker
                :model-value="d.bgGradient?.from ?? '#3b4252'"
                default="#3b4252"
                :show-value="false"
                @update:model-value="v => updateNested('bgGradient', 'from', v)"
              />
            </div>
            <div>
              <label class="text-[10px] text-muted-foreground block mb-1">To</label>
              <ColorPicker
                :model-value="d.bgGradient?.to ?? '#4c566a'"
                default="#4c566a"
                :show-value="false"
                @update:model-value="v => updateNested('bgGradient', 'to', v)"
              />
            </div>
          </div>
          <SelectBox
            :model-value="d.bgGradient?.direction ?? 'to-r'"
            :data="[
              { value: 'to-r',  label: 'Left to right' },
              { value: 'to-l',  label: 'Right to left' },
              { value: 'to-b',  label: 'Top to bottom' },
              { value: 'to-t',  label: 'Bottom to top' },
              { value: 'to-br', label: 'Top-left to bottom-right' },
              { value: 'to-bl', label: 'Top-right to bottom-left' },
            ]"
            @update:model-value="v => updateNested('bgGradient', 'direction', v)"
          />
        </div>
      </div>

      <!-- Width -->
      <div class="space-y-2">
        <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Width</label>
        <div class="flex items-center gap-2">
          <input type="checkbox" id="section-fullwidth"
            :checked="d.fullWidth"
            @change="update('fullWidth', $event.target.checked)"
            class="rounded border-border accent-nord-green" />
          <label for="section-fullwidth" class="text-xs text-muted-foreground">Full width (no inner max-width)</label>
        </div>
        <div v-if="!d.fullWidth">
          <label class="text-xs text-muted-foreground block mb-1">Inner max width</label>
          <SelectBox
            :model-value="d.innerMaxWidth ?? 'xl'"
            :data="[
              { value: 'sm',   label: 'SM (24rem)' },
              { value: 'md',   label: 'MD (28rem)' },
              { value: 'lg',   label: 'LG (32rem)' },
              { value: 'xl',   label: 'XL (36rem)' },
              { value: '2xl',  label: '2XL (42rem)' },
              { value: 'full', label: 'Full' },
            ]"
            @update:model-value="v => update('innerMaxWidth', v)"
          />
        </div>
      </div>

      <!-- Spacing -->
      <div class="space-y-2">
        <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Padding</label>
        <SpacingControl
          :model-value="typeof d.padding === 'object' ? d.padding : {}"
          @update:model-value="v => update('padding', v)"
        />
      </div>

      <!-- Min height -->
      <div class="space-y-1">
        <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Min Height</label>
        <SelectBox
          :model-value="d.minHeight ?? 'auto'"
          :data="[
            { value: 'auto',   label: 'Auto' },
            { value: 'screen', label: 'Full screen (100vh)' },
            { value: '1/2',    label: 'Half screen (50vh)' },
          ]"
          @update:model-value="v => update('minHeight', v)"
        />
      </div>

    </div>

  </div>
</template>
