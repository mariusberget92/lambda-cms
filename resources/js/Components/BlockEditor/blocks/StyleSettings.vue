<!-- resources/js/components/BlockEditor/blocks/StyleSettings.vue -->
<script setup>
import { ref, computed } from 'vue'
import SelectBox    from '@/Components/SelectBox.vue'
import NumberInput    from '@/Components/NumberInput.vue'
import DimensionInput from '../DimensionInput.vue'
import BorderControl  from '../BorderControl.vue'
import ShadowControl  from '../ShadowControl.vue'
import SpacingControl from '../SpacingControl.vue'
import ColorPicker    from '../ColorPicker.vue'
import MediaPicker    from '@/components/MediaPicker.vue'
import DynamicField   from './DynamicField.vue'
import EditorCheckbox from '../EditorCheckbox.vue'
import SettingsSection from '../SettingsSection.vue'

const FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Nunito',
  'Source Sans 3', 'Merriweather', 'Playfair Display', 'Lora', 'PT Serif', 'Libre Baskerville',
  'EB Garamond', 'Oswald', 'Bebas Neue', 'DM Sans', 'DM Serif Display', 'Figtree',
  'Plus Jakarta Sans', 'Outfit', 'Manrope', 'Sora', 'Space Grotesk',
  'JetBrains Mono', 'Fira Code', 'Source Code Pro',
]

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

const showImagePicker = ref(false)

// Smart-open: sections auto-expand if the block already has data there
const hasSpacing = computed(() => {
  const p = props.block.data?.padding
  const m = props.block.data?.margin
  return (typeof p === 'object' && p !== null && Object.values(p).some(Boolean)) ||
         (typeof m === 'object' && m !== null && Object.values(m).some(Boolean))
})
const hasBackground = computed(() =>
  !!props.block.data?.advBgType && props.block.data.advBgType !== 'none'
)
const hasEffects = computed(() => {
  const d = props.block.data ?? {}
  return (d.opacity != null && d.opacity !== 100) ||
         !!d.cursor || !!d.overflow ||
         d.zIndex != null ||
         !!d.transitionDuration
})

// Display section
const containerMode = computed(() => props.block.data?.mode ?? 'flex')

const showDisplay = computed(() =>
  props.block.type === 'container' || props.block.type === 'section'
)

const hasDisplay = computed(() => showDisplay.value)

function getBreakpoint(field, bp) {
  const val = props.block.data?.[field]
  if (typeof val === 'object' && val !== null) return val[bp] ?? null
  if (bp === 'default') return val ?? null
  return null
}

function setBreakpoint(field, bp, value) {
  const current = props.block.data?.[field]
  const base = (typeof current === 'object' && current !== null)
    ? { ...current }
    : { default: current }
  updateData(field, { ...base, [bp]: value })
}

// Border section
const hasBorder = computed(() => {
  const b = props.block.data?.border ?? {}
  return !!(b.radiusTL || b.radiusTR || b.radiusBL || b.radiusBR || b.radius ||
            (b.style && b.style !== 'none') || props.block.data?.shadow)
})

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
function onBgImageSelect(media) {
  showImagePicker.value = false
  updateNestedData('advBgImage', 'url', media.url)
}
function onBgUrlBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...(props.block.bindings ?? {}), [fieldName]: loopField } })
}
function onBgUrlUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>

<template>
  <!-- Font family (no section wrapper) -->
  <div class="border-t border-white/8 pt-3">
    <label class="text-xs font-medium text-muted-foreground block mb-1">Font family</label>
    <SelectBox size="sm"
      :model-value="block.fontFamily ?? ''"
      :data="[{ value: '', label: 'Site default' }, ...FONTS.map(f => ({ value: f, label: f }))]"
      :item-style="item => item.value ? { fontFamily: item.value } : {}"
      @update:model-value="v => update('fontFamily', v)"
    />
  </div>

  <!-- Display section — container and section blocks only -->
  <SettingsSection v-if="showDisplay" label="Display" :default-open="hasDisplay">

    <!-- Container: flex/grid controls -->
    <template v-if="block.type === 'container'">

      <!-- Mode toggle -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Mode</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button type="button"
            v-for="m in ['flex', 'grid', 'inline-flex']" :key="m"
            class="flex-1 py-1.5 transition-colors capitalize"
            :class="containerMode === m ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            @click="updateData('mode', m)">
            {{ m }}
          </button>
        </div>
      </div>

      <!-- Flex-only controls -->
      <template v-if="containerMode === 'flex' || containerMode === 'inline-flex'">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Direction</label>
          <div class="grid grid-cols-3 gap-1">
            <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
              <span class="text-[10px] text-muted-foreground block mb-0.5 text-center">
                {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
              </span>
              <SelectBox size="sm"
                :model-value="getBreakpoint('direction', bp)"
                :data="[{ value: 'row', label: 'Row' }, { value: 'column', label: 'Col' }]"
                @update:model-value="v => setBreakpoint('direction', bp, v)"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <EditorCheckbox :model-value="block.data?.wrap ?? false" @update:model-value="v => updateData('wrap', v)" />
          <span class="text-xs text-muted-foreground">Wrap items</span>
        </div>

        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Justify content</label>
          <SelectBox size="sm"
            :model-value="block.data?.justify ?? 'start'"
            :data="[
              { value: 'start',   label: 'Start' },
              { value: 'center',  label: 'Center' },
              { value: 'end',     label: 'End' },
              { value: 'between', label: 'Space between' },
              { value: 'around',  label: 'Space around' },
            ]"
            @update:model-value="v => updateData('justify', v)"
          />
        </div>

        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Align items</label>
          <SelectBox size="sm"
            :model-value="block.data?.align ?? 'start'"
            :data="[
              { value: 'start',   label: 'Start' },
              { value: 'center',  label: 'Center' },
              { value: 'end',     label: 'End' },
              { value: 'stretch', label: 'Stretch' },
            ]"
            @update:model-value="v => updateData('align', v)"
          />
        </div>
      </template>

      <!-- Grid-only controls -->
      <template v-if="containerMode === 'grid'">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Columns</label>
          <div class="grid grid-cols-3 gap-1">
            <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
              <span class="text-[10px] text-muted-foreground block mb-0.5 text-center">
                {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
              </span>
              <NumberInput size="sm"
                :model-value="getBreakpoint('columns', bp) ?? ''"
                :min="1" :max="12"
                @update:model-value="v => setBreakpoint('columns', bp, v || null)"
              />
            </div>
          </div>
        </div>
      </template>

      <!-- Shared flex+grid controls -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
        <DimensionInput
          :model-value="typeof block.data?.gap === 'string' ? block.data.gap : ''"
          placeholder="0"
          @update:model-value="v => updateData('gap', v)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
        <SelectBox size="sm"
          :model-value="block.data?.maxWidth ?? 'full'"
          :data="[
            { value: 'full',  label: 'Full' },
            { value: 'prose', label: 'Prose (65ch)' },
            { value: 'sm',    label: 'SM (24rem)' },
            { value: 'md',    label: 'MD (28rem)' },
            { value: 'lg',    label: 'LG (32rem)' },
            { value: 'xl',    label: 'XL (36rem)' },
            { value: '2xl',   label: '2XL (42rem)' },
          ]"
          @update:model-value="v => updateData('maxWidth', v)"
        />
      </div>

    </template>

    <!-- Section: layout controls -->
    <template v-if="block.type === 'section'">
      <div class="flex items-center gap-2">
        <EditorCheckbox :model-value="block.data?.fullWidth ?? false" @update:model-value="v => updateData('fullWidth', v)" />
        <span class="text-xs text-muted-foreground">Full width (no inner max-width)</span>
      </div>

      <div v-if="!(block.data?.fullWidth)">
        <label class="text-xs text-muted-foreground block mb-1">Inner max width</label>
        <SelectBox size="sm"
          :model-value="block.data?.innerMaxWidth ?? 'xl'"
          :data="[
            { value: 'sm',   label: 'SM (24rem)' },
            { value: 'md',   label: 'MD (28rem)' },
            { value: 'lg',   label: 'LG (32rem)' },
            { value: 'xl',   label: 'XL (36rem)' },
            { value: '2xl',  label: '2XL (42rem)' },
            { value: 'full', label: 'Full' },
          ]"
          @update:model-value="v => updateData('innerMaxWidth', v)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Min height</label>
        <SelectBox size="sm"
          :model-value="block.data?.minHeight ?? 'auto'"
          :data="[
            { value: 'auto',   label: 'Auto' },
            { value: 'screen', label: 'Full screen (100vh)' },
            { value: '1/2',    label: 'Half screen (50vh)' },
          ]"
          @update:model-value="v => updateData('minHeight', v)"
        />
      </div>
    </template>

  </SettingsSection>

  <!-- Spacing -->
  <SettingsSection label="Spacing" :default-open="hasSpacing">
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
  </SettingsSection>

  <!-- Border & Shadow -->
  <SettingsSection label="Border & Shadow" :default-open="hasBorder">
    <BorderControl
      :model-value="block.data?.border ?? {}"
      @update:model-value="v => updateData('border', v)"
    />
    <ShadowControl
      :model-value="block.data?.shadow ?? ''"
      @update:model-value="v => updateData('shadow', v)"
    />
  </SettingsSection>

  <!-- Background -->
  <SettingsSection label="Background" :default-open="hasBackground">
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
        <DynamicField
          label="Image URL"
          field-name="advBgImageUrl"
          :block="block"
          :available-fields="availableFields"
          @bind="onBgUrlBind"
          @unbind="onBgUrlUnbind"
        >
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
        </DynamicField>
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
          <EditorCheckbox
            :model-value="block.data?.advBgImage?.parallax ?? false"
            @update:model-value="v => updateNestedData('advBgImage', 'parallax', v)"
          />
          <span class="text-xs text-muted-foreground">Parallax (fixed)</span>
        </label>

        <MediaPicker v-model="showImagePicker" :dark="true" @select="onBgImageSelect" />
      </div>
    </div>
  </SettingsSection>

  <!-- Effects -->
  <SettingsSection label="Effects" :default-open="hasEffects">
    <!-- Opacity -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">
        Opacity <span class="text-foreground font-semibold">{{ block.data?.opacity ?? 100 }}%</span>
      </label>
      <input
        type="range" min="0" max="100" step="1"
        :value="block.data?.opacity ?? 100"
        class="w-full accent-primary"
        @input="updateData('opacity', Number($event.target.value))"
      />
    </div>

    <!-- Cursor -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Cursor</label>
      <SelectBox size="sm"
        :model-value="block.data?.cursor ?? ''"
        :data="[
          { value: '',              label: 'Default' },
          { value: 'pointer',       label: 'Pointer (hand)' },
          { value: 'not-allowed',   label: 'Not allowed' },
          { value: 'wait',          label: 'Wait (spinner)' },
          { value: 'text',          label: 'Text (I-beam)' },
          { value: 'grab',          label: 'Grab' },
        ]"
        @update:model-value="v => updateData('cursor', v || null)"
      />
    </div>

    <!-- Overflow -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Overflow</label>
      <SelectBox size="sm"
        :model-value="block.data?.overflow ?? ''"
        :data="[
          { value: '',        label: 'Visible (default)' },
          { value: 'hidden',  label: 'Hidden' },
          { value: 'auto',    label: 'Auto (scrollbar if needed)' },
          { value: 'scroll',  label: 'Scroll (always)' },
        ]"
        @update:model-value="v => updateData('overflow', v || null)"
      />
    </div>

    <!-- Z-index -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Z-index</label>
      <input
        type="number"
        step="1"
        :value="block.data?.zIndex ?? ''"
        placeholder="Auto"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs"
        @input="updateData('zIndex', $event.target.value !== '' ? Math.round(Number($event.target.value)) : null)"
      />
    </div>

    <!-- Transition -->
    <div class="grid grid-cols-2 gap-2">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Transition</label>
        <SelectBox size="sm"
          :model-value="block.data?.transitionDuration ?? ''"
          :data="[
            { value: '',       label: 'None' },
            { value: '75ms',   label: '75ms' },
            { value: '150ms',  label: '150ms' },
            { value: '300ms',  label: '300ms' },
            { value: '500ms',  label: '500ms' },
            { value: '700ms',  label: '700ms' },
            { value: '1000ms', label: '1s' },
          ]"
          @update:model-value="v => updateData('transitionDuration', v || null)"
        />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Easing</label>
        <SelectBox size="sm"
          :model-value="block.data?.transitionEasing ?? ''"
          :data="[
            { value: '',            label: 'Ease (default)' },
            { value: 'linear',      label: 'Linear' },
            { value: 'ease-in',     label: 'Ease in' },
            { value: 'ease-out',    label: 'Ease out' },
            { value: 'ease-in-out', label: 'Ease in-out' },
          ]"
          @update:model-value="v => updateData('transitionEasing', v || null)"
        />
      </div>
    </div>
  </SettingsSection>
</template>
