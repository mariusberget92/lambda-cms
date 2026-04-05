<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Headline" field-name="headline" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.headline" type="text" placeholder="Bold headline..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { headline: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Body text" field-name="text" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.text" type="text" placeholder="Supporting text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button label" field-name="button_label" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.button_label" type="text" placeholder="Click here"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_label: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button URL" field-name="button_url" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.button_url" type="url" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_url: $event.target.value } })" />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <!-- Background (same pattern as ContainerSettings) -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Background</label>
      <div class="flex gap-1 flex-wrap">
        <button v-for="opt in ['none','color','image','gradient']" :key="opt" type="button"
          class="px-2 py-1 text-xs rounded border transition-colors"
          :class="block.data.bgType === opt ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
          @click="update('bgType', opt)">
          {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
        </button>
      </div>

      <div v-if="block.data.bgType === 'color'" class="flex items-center gap-2">
        <input type="color" :value="block.data.bgColor ?? '#ffffff'"
          @input="update('bgColor', $event.target.value)"
          class="h-8 w-14 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground">{{ block.data.bgColor ?? '#ffffff' }}</span>
      </div>

      <div v-if="block.data.bgType === 'image'" class="space-y-2">
        <div class="flex gap-1 p-0.5 rounded-md bg-muted w-fit">
          <button type="button" class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
            :class="bgImageMode === 'library' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
            @click="bgImageMode = 'library'">Library</button>
          <button type="button" class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
            :class="bgImageMode === 'url' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
            @click="bgImageMode = 'url'">URL</button>
        </div>
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
        <input v-else type="url" :value="block.data.bgImage?.url ?? ''"
          @input="updateNested('bgImage', 'url', $event.target.value)"
          placeholder="https://..."
          class="w-full rounded border border-border bg-background px-2 py-1 text-xs" />
        <SelectBox :model-value="block.data.bgImage?.position ?? 'center'"
          :data="[{ value:'center',label:'Center'},{ value:'top',label:'Top'},{ value:'bottom',label:'Bottom'},{ value:'left center',label:'Left'},{ value:'right center',label:'Right'}]"
          @update:model-value="v => updateNested('bgImage','position',v)" />
        <SelectBox :model-value="block.data.bgImage?.size ?? 'cover'"
          :data="[{ value:'cover',label:'Cover'},{ value:'contain',label:'Contain'},{ value:'auto',label:'Auto'}]"
          @update:model-value="v => updateNested('bgImage','size',v)" />
      </div>

      <div v-if="block.data.bgType === 'gradient'" class="space-y-2">
        <div class="flex gap-2 items-center">
          <div>
            <label class="text-[10px] text-muted-foreground">From</label>
            <input type="color" :value="block.data.bgGradient?.from ?? '#3b4252'"
              @input="updateNested('bgGradient','from',$event.target.value)"
              class="block h-8 w-12 cursor-pointer rounded border border-border" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground">To</label>
            <input type="color" :value="block.data.bgGradient?.to ?? '#4c566a'"
              @input="updateNested('bgGradient','to',$event.target.value)"
              class="block h-8 w-12 cursor-pointer rounded border border-border" />
          </div>
        </div>
        <SelectBox :model-value="block.data.bgGradient?.direction ?? 'to-r'"
          :data="[{ value:'to-r',label:'Left → Right'},{ value:'to-l',label:'Right → Left'},{ value:'to-b',label:'Top → Bottom'},{ value:'to-t',label:'Bottom → Top'}]"
          @update:model-value="v => updateNested('bgGradient','direction',v)" />
      </div>
    </div>

    <!-- Text alignment -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Text alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="align in ['left','center','right']" :key="align" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="block.data.textAlign === align ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="update('textAlign', block.data.textAlign === align ? null : align)">
          {{ align.charAt(0).toUpperCase() + align.slice(1) }}
        </button>
      </div>
    </div>

    <!-- Headline color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Headline color</label>
      <div class="flex items-center gap-2">
        <input type="color" :value="block.data.headlineColor ?? '#ffffff'"
          @input="update('headlineColor', $event.target.value)"
          class="h-8 w-14 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground flex-1">{{ block.data.headlineColor ?? 'Inherit' }}</span>
        <button v-if="block.data.headlineColor" type="button"
          class="text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click="update('headlineColor', null)">Reset</button>
      </div>
    </div>

    <!-- Body text color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Body text color</label>
      <div class="flex items-center gap-2">
        <input type="color" :value="block.data.textColor ?? '#ffffff'"
          @input="update('textColor', $event.target.value)"
          class="h-8 w-14 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground flex-1">{{ block.data.textColor ?? 'Inherit' }}</span>
        <button v-if="block.data.textColor" type="button"
          class="text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click="update('textColor', null)">Reset</button>
      </div>
    </div>

    <!-- Button style -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Button style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="variant in ['filled', 'outline']" :key="variant" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.button?.variant ?? 'filled') === variant ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="updateNested('button', 'variant', variant)">
          {{ variant.charAt(0).toUpperCase() + variant.slice(1) }}
        </button>
      </div>
      <div class="flex gap-2">
        <div class="flex-1">
          <label class="text-[10px] text-muted-foreground block mb-1">Bg color</label>
          <input type="color" :value="block.data.button?.bgColor ?? '#5e81ac'"
            @input="updateNested('button', 'bgColor', $event.target.value)"
            class="h-7 w-full cursor-pointer rounded border border-border" />
        </div>
        <div class="flex-1">
          <label class="text-[10px] text-muted-foreground block mb-1">Text color</label>
          <input type="color" :value="block.data.button?.textColor ?? '#eceff4'"
            @input="updateNested('button', 'textColor', $event.target.value)"
            class="h-7 w-full cursor-pointer rounded border border-border" />
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button radius</label>
        <DimensionInput :model-value="block.data.button?.radius ?? ''"
          placeholder="0"
          @update:model-value="v => updateNested('button', 'radius', v || null)" />
      </div>
    </div>

    <!-- Padding -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding</label>
      <SpacingControl
        :model-value="typeof block.data.padding === 'object' ? block.data.padding : {}"
        @update:model-value="v => update('padding', v)"
      />
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import DynamicField  from './DynamicField.vue'
import SelectBox     from '@/Components/SelectBox.vue'
import DimensionInput from '../DimensionInput.vue'
import SpacingControl from '../SpacingControl.vue'
import MediaPicker   from '@/Components/MediaPicker.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

const bgImageMode  = ref('library')
const showBgPicker = ref(false)

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
function updateNested(key, subKey, value) {
  const current = props.block.data[key] ?? {}
  emit('update', { id: props.block.id, data: { [key]: { ...current, [subKey]: value } } })
}
function onBgMediaSelect(media) {
  showBgPicker.value = false
  updateNested('bgImage', 'url', media.url)
}
function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
