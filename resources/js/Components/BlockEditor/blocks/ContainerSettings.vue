<script setup>
import { computed, ref } from 'vue'
import SelectBox     from '@/Components/SelectBox.vue'
import NumberInput   from '@/Components/NumberInput.vue'
import DimensionInput from '../DimensionInput.vue'
import SpacingControl from '../SpacingControl.vue'
import MediaPicker   from '@/Components/MediaPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab: { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit   = defineEmits(['update'])

const mode = computed(() => props.block.data.mode ?? 'flex')

// Helpers for reading/writing responsive objects
function getBreakpoint(field, bp) {
  const val = props.block.data[field]
  if (typeof val === 'object' && val !== null) return val[bp] ?? null
  if (bp === 'default') return val  // legacy flat value
  return null
}

function setBreakpoint(field, bp, value) {
  const current = props.block.data[field]
  const base = (typeof current === 'object' && current !== null)
    ? { ...current }
    : { default: current }
  emit('update', { id: props.block.id, data: { [field]: { ...base, [bp]: value } } })
}

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}

function updateNested(key, subKey, value) {
  const current = props.block.data[key]
  emit('update', { id: props.block.id, data: { [key]: { ...(current ?? {}), [subKey]: value } } })
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

    <!-- Content tab fields -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">

      <!-- Mode toggle -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Mode</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button type="button"
            :class="mode === 'flex' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            class="flex-1 py-1.5 transition-colors"
            @click="update('mode', 'flex')">Flex</button>
          <button type="button"
            :class="mode === 'grid' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            class="flex-1 py-1.5 transition-colors"
            @click="update('mode', 'grid')">Grid</button>
        </div>
      </div>

    </div>

    <!-- Style tab fields -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">

      <!-- Background -->
      <div class="space-y-2">
        <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Background</label>

        <div class="flex gap-1 flex-wrap">
          <button type="button" v-for="opt in ['none','color','image','gradient']" :key="opt"
            class="px-2 py-1 text-xs rounded border transition-colors"
            :class="block.data.bgType === opt ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
            @click="update('bgType', opt)">
            {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
          </button>
        </div>

        <!-- Color picker -->
        <div v-if="block.data.bgType === 'color'" class="flex items-center gap-2">
          <input type="color" :value="block.data.bgColor ?? '#ffffff'"
            @input="update('bgColor', $event.target.value)"
            class="h-8 w-16 cursor-pointer rounded border border-border" />
          <span class="text-xs text-muted-foreground">{{ block.data.bgColor ?? '#ffffff' }}</span>
        </div>

        <!-- Image picker -->
        <div v-if="block.data.bgType === 'image'" class="space-y-2">

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
            <div v-if="block.data.bgImage?.url" class="rounded overflow-hidden border mb-2 max-h-24">
              <img :src="block.data.bgImage.url" class="w-full h-full object-cover" />
            </div>
            <button type="button"
              class="w-full rounded border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
              @click="showBgPicker = true">
              {{ block.data.bgImage?.url ? 'Change image' : 'Select image' }}
            </button>
            <MediaPicker v-model="showBgPicker" :dark="true" @select="onBgMediaSelect" />
          </div>

          <!-- URL mode -->
          <input v-else type="url" :value="block.data.bgImage?.url ?? ''"
            @input="updateNested('bgImage', 'url', $event.target.value)"
            placeholder="https://..."
            class="w-full rounded border border-border bg-background px-2 py-1 text-xs" />
          <SelectBox
            :model-value="block.data.bgImage?.position ?? 'center'"
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
            :model-value="block.data.bgImage?.size ?? 'cover'"
            :data="[
              { value: 'cover',   label: 'Cover' },
              { value: 'contain', label: 'Contain' },
              { value: 'auto',    label: 'Auto' },
            ]"
            @update:model-value="v => updateNested('bgImage', 'size', v)"
          />
          <div class="flex items-center gap-2">
            <input type="checkbox" id="container-parallax"
              :checked="block.data.bgImage?.parallax"
              @change="updateNested('bgImage', 'parallax', $event.target.checked)"
              class="rounded border-border accent-nord-green" />
            <label for="container-parallax" class="text-xs text-muted-foreground">Parallax (fixed attachment)</label>
          </div>
        </div>

        <!-- Gradient picker -->
        <div v-if="block.data.bgType === 'gradient'" class="space-y-2">
          <div class="flex gap-2 items-center">
            <div>
              <label class="text-[10px] text-muted-foreground">From</label>
              <input type="color" :value="block.data.bgGradient?.from ?? '#3b4252'"
                @input="updateNested('bgGradient', 'from', $event.target.value)"
                class="block h-8 w-12 cursor-pointer rounded border border-border" />
            </div>
            <div>
              <label class="text-[10px] text-muted-foreground">To</label>
              <input type="color" :value="block.data.bgGradient?.to ?? '#4c566a'"
                @input="updateNested('bgGradient', 'to', $event.target.value)"
                class="block h-8 w-12 cursor-pointer rounded border border-border" />
            </div>
          </div>
          <SelectBox
            :model-value="block.data.bgGradient?.direction ?? 'to-r'"
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

      <!-- Flex-only controls -->
      <template v-if="mode === 'flex'">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Direction</label>
          <div class="grid grid-cols-3 gap-1">
            <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
              <span class="text-[10px] text-muted-foreground block mb-0.5 text-center">
                {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
              </span>
              <SelectBox
                :model-value="getBreakpoint('direction', bp)"
                :data="[{ value: 'row', label: 'Row' }, { value: 'column', label: 'Col' }]"
                @update:model-value="v => setBreakpoint('direction', bp, v)"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" id="container-wrap"
            :checked="block.data.wrap"
            @change="update('wrap', $event.target.checked)"
            class="rounded border-border accent-nord-green" />
          <label for="container-wrap" class="text-xs font-medium text-muted-foreground">Wrap items</label>
        </div>

        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Justify content</label>
          <SelectBox
            :model-value="block.data.justify"
            :data="[
              { value: 'start',   label: 'Start' },
              { value: 'center',  label: 'Center' },
              { value: 'end',     label: 'End' },
              { value: 'between', label: 'Space between' },
              { value: 'around',  label: 'Space around' },
            ]"
            @update:model-value="v => update('justify', v)"
          />
        </div>

        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Align items</label>
          <SelectBox
            :model-value="block.data.align"
            :data="[
              { value: 'start',   label: 'Start' },
              { value: 'center',  label: 'Center' },
              { value: 'end',     label: 'End' },
              { value: 'stretch', label: 'Stretch' },
            ]"
            @update:model-value="v => update('align', v)"
          />
        </div>
      </template>

      <!-- Grid-only controls -->
      <template v-if="mode === 'grid'">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Columns</label>
          <div class="grid grid-cols-3 gap-1">
            <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
              <span class="text-[10px] text-muted-foreground block mb-0.5 text-center">
                {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
              </span>
              <NumberInput
                :model-value="getBreakpoint('columns', bp) ?? ''"
                :min="1"
                :max="12"
                @update:model-value="v => setBreakpoint('columns', bp, v || null)"
              />
            </div>
          </div>
        </div>
      </template>

      <!-- Shared controls -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
        <DimensionInput
          :model-value="typeof block.data.gap === 'string' ? block.data.gap : ''"
          placeholder="0"
          @update:model-value="v => update('gap', v)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
        <SelectBox
          :model-value="block.data.maxWidth"
          :data="[
            { value: 'full',  label: 'Full' },
            { value: 'prose', label: 'Prose (65ch)' },
            { value: 'sm',    label: 'SM (24rem)' },
            { value: 'md',    label: 'MD (28rem)' },
            { value: 'lg',    label: 'LG (32rem)' },
            { value: 'xl',    label: 'XL (36rem)' },
            { value: '2xl',   label: '2XL (42rem)' },
          ]"
          @update:model-value="v => update('maxWidth', v)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Padding</label>
        <SpacingControl
          :model-value="typeof block.data.padding === 'object' ? block.data.padding : {}"
          @update:model-value="v => update('padding', v)"
        />
      </div>

    </div>

  </div>
</template>
