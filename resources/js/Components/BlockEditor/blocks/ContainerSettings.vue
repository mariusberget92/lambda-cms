<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'
import NumberInput from '@/Components/NumberInput.vue'
import DimensionInput from '../DimensionInput.vue'
import SpacingControl from '../SpacingControl.vue'

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
