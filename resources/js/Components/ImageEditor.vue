<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4"
    >
      <div
        class="bg-background rounded-xl shadow-2xl flex flex-col overflow-hidden"
        style="width: min(1000px, 96vw); height: min(88vh, 720px)"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b shrink-0">
          <span class="text-sm font-semibold">Edit Image</span>
          <span v-if="originalFilename" class="text-xs text-muted-foreground truncate max-w-xs">
            {{ originalFilename }}
          </span>
        </div>

        <!-- Main content -->
        <div class="flex flex-1 min-h-0">
          <!-- Image preview area -->
          <div class="flex-1 bg-zinc-950 flex items-center justify-center overflow-hidden relative min-w-0">
            <img
              ref="imageEl"
              :src="src"
              class="block max-w-full max-h-full"
              style="opacity: 0"
            />
          </div>

          <!-- Controls panel -->
          <div class="w-52 shrink-0 border-l flex flex-col overflow-hidden bg-background">
            <!-- Tabs -->
            <div class="flex border-b shrink-0">
              <button
                v-for="tab in TABS"
                :key="tab"
                @click="activeTab = tab"
                class="flex-1 py-2.5 text-xs font-medium transition-colors"
                :class="
                  activeTab === tab
                    ? 'text-foreground border-b-2 border-primary -mb-px'
                    : 'text-muted-foreground hover:text-foreground'
                "
              >
                {{ tab }}
              </button>
            </div>

            <!-- CROP tab -->
            <div v-show="activeTab === 'Crop'" class="flex-1 overflow-y-auto p-3 space-y-5">
              <div>
                <p class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase mb-2">
                  Aspect Ratio
                </p>
                <div class="space-y-0.5">
                  <button
                    v-for="r in RATIOS"
                    :key="r.label"
                    @click="setRatio(r)"
                    class="w-full text-left px-3 py-1.5 text-xs rounded transition-colors"
                    :class="
                      activeRatio === r.label
                        ? 'bg-primary text-primary-foreground'
                        : 'hover:bg-accent'
                    "
                  >
                    {{ r.label }}
                  </button>
                </div>
              </div>

              <div>
                <p class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase mb-2">
                  Transform
                </p>
                <div class="grid grid-cols-2 gap-1.5">
                  <button
                    @click="doRotate(-90)"
                    class="rounded border py-2 text-xs hover:bg-accent flex items-center justify-center gap-1 transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12a9.5 9.5 0 1 1 19 0M2.5 12V6.5m0 5.5H8"/>
                    </svg>
                    CCW
                  </button>
                  <button
                    @click="doRotate(90)"
                    class="rounded border py-2 text-xs hover:bg-accent flex items-center justify-center gap-1 transition-colors"
                  >
                    <svg class="w-3 h-3 -scale-x-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12a9.5 9.5 0 1 1 19 0M2.5 12V6.5m0 5.5H8"/>
                    </svg>
                    CW
                  </button>
                  <button
                    @click="doFlip('h')"
                    class="rounded border py-2 text-xs hover:bg-accent flex items-center justify-center transition-colors"
                  >
                    ⇆ Flip H
                  </button>
                  <button
                    @click="doFlip('v')"
                    class="rounded border py-2 text-xs hover:bg-accent flex items-center justify-center transition-colors"
                  >
                    ⇅ Flip V
                  </button>
                </div>
              </div>

              <div>
                <p class="text-[10px] font-semibold tracking-widest text-muted-foreground uppercase mb-2">
                  Output Size
                </p>
                <div class="flex items-center gap-1.5">
                  <input
                    v-model.number="outputW"
                    type="number"
                    min="1"
                    max="8000"
                    placeholder="W"
                    class="w-full rounded border bg-background px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-ring [appearance:textfield]"
                    @change="onWidthChange"
                  />
                  <span class="text-xs text-muted-foreground shrink-0">×</span>
                  <input
                    v-model.number="outputH"
                    type="number"
                    min="1"
                    max="8000"
                    placeholder="H"
                    class="w-full rounded border bg-background px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-ring [appearance:textfield]"
                    @change="onHeightChange"
                  />
                </div>
                <button
                  @click="lockAspect = !lockAspect"
                  class="mt-1.5 w-full rounded border px-2 py-1.5 text-xs transition-colors"
                  :class="
                    lockAspect
                      ? 'bg-primary/10 border-primary/30 text-primary'
                      : 'text-muted-foreground hover:bg-accent'
                  "
                >
                  {{ lockAspect ? '🔒 Aspect locked' : '🔓 Free size' }}
                </button>
              </div>
            </div>

            <!-- FILTER tab -->
            <div v-show="activeTab === 'Filter'" class="flex-1 overflow-y-auto p-3">
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="f in FILTER_PRESETS"
                  :key="f.name"
                  @click="applyPreset(f)"
                  class="rounded-lg border p-2 flex flex-col items-center gap-1.5 text-xs transition-colors"
                  :class="
                    activePreset === f.name
                      ? 'border-primary ring-1 ring-primary bg-primary/5'
                      : 'hover:bg-accent'
                  "
                >
                  <div
                    class="w-full aspect-square overflow-hidden rounded bg-muted"
                    :style="{ filter: f.css }"
                  >
                    <img
                      v-if="src"
                      :src="src"
                      class="w-full h-full object-cover"
                      loading="lazy"
                    />
                  </div>
                  <span>{{ f.name }}</span>
                </button>
              </div>
            </div>

            <!-- ADJUST tab -->
            <div v-show="activeTab === 'Adjust'" class="flex-1 overflow-y-auto p-3 space-y-5">
              <div v-for="s in SLIDERS" :key="s.key">
                <div class="flex justify-between items-center mb-1.5">
                  <span class="text-xs font-medium">{{ s.label }}</span>
                  <span class="text-xs text-muted-foreground tabular-nums w-12 text-right">
                    {{ adjusts[s.key] }}{{ s.unit }}
                  </span>
                </div>
                <input
                  type="range"
                  :min="s.min"
                  :max="s.max"
                  :step="s.step ?? 1"
                  v-model.number="adjusts[s.key]"
                  @input="applyLiveFilter"
                  class="w-full h-1 cursor-pointer accent-primary"
                />
              </div>
              <button
                @click="resetAdjusts"
                class="w-full rounded border px-3 py-1.5 text-xs text-muted-foreground hover:bg-accent transition-colors"
              >
                Reset adjustments
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-2 px-5 py-3 border-t shrink-0">
          <button
            v-if="allowSkip"
            @click="onSkip"
            class="rounded-md border px-4 py-2 text-sm text-muted-foreground hover:bg-accent transition-colors"
          >
            Skip
          </button>
          <button
            @click="onCancel"
            class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
          >
            Cancel
          </button>
          <button
            @click="onApply"
            :disabled="applying"
            class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors min-w-[90px]"
          >
            {{ applying ? 'Processing…' : 'Apply' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick, onUnmounted } from 'vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'

const props = defineProps({
  modelValue:       { type: Boolean, default: false },
  src:              { type: String,  default: '' },
  originalFilename: { type: String,  default: '' },
  allowSkip:        { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'apply', 'skip', 'cancel'])

const TABS = ['Crop', 'Filter', 'Adjust']

const RATIOS = [
  { label: 'Free',   value: NaN },
  { label: '1 : 1',  value: 1 },
  { label: '4 : 3',  value: 4 / 3 },
  { label: '3 : 2',  value: 3 / 2 },
  { label: '16 : 9', value: 16 / 9 },
  { label: '21 : 9', value: 21 / 9 },
  { label: '9 : 16', value: 9 / 16 },
  { label: '3 : 4',  value: 3 / 4 },
  { label: '2 : 3',  value: 2 / 3 },
]

const FILTER_PRESETS = [
  { name: 'Normal', css: 'none' },
  { name: 'Vivid',  css: 'saturate(1.8) contrast(1.1)' },
  { name: 'Muted',  css: 'saturate(0.6) brightness(1.05)' },
  { name: 'B&W',    css: 'grayscale(1)' },
  { name: 'Warm',   css: 'sepia(0.35) saturate(1.3) brightness(1.05)' },
  { name: 'Cool',   css: 'hue-rotate(20deg) saturate(0.9) brightness(1.05)' },
  { name: 'Fade',   css: 'brightness(1.1) saturate(0.65) contrast(0.85)' },
  { name: 'Drama',  css: 'contrast(1.4) brightness(0.9) saturate(1.3)' },
]

const SLIDERS = [
  { key: 'brightness', label: 'Brightness', min: -100, max: 100, unit: '' },
  { key: 'contrast',   label: 'Contrast',   min: -100, max: 100, unit: '' },
  { key: 'saturation', label: 'Saturation', min: -100, max: 100, unit: '' },
  { key: 'blur',       label: 'Blur',       min: 0,    max: 20,  step: 0.5, unit: 'px' },
]

const imageEl      = ref(null)
const activeTab    = ref('Crop')
const activeRatio  = ref('Free')
const activePreset = ref('Normal')
const applying     = ref(false)
const outputW      = ref(null)
const outputH      = ref(null)
const lockAspect   = ref(true)
const adjusts      = ref({ brightness: 0, contrast: 0, saturation: 0, blur: 0 })

let cropper        = null
let cropAspectRatio = null
let presetCss      = 'none'

function buildAdjustCss() {
  const a = adjusts.value
  const parts = []
  if (a.brightness !== 0) parts.push(`brightness(${(1 + a.brightness / 100).toFixed(3)})`)
  if (a.contrast !== 0)   parts.push(`contrast(${(1 + a.contrast / 100).toFixed(3)})`)
  if (a.saturation !== 0) parts.push(`saturate(${Math.max(0, 1 + a.saturation / 100).toFixed(3)})`)
  if (a.blur > 0)         parts.push(`blur(${a.blur}px)`)
  return parts.join(' ')
}

function buildFullFilterCss() {
  const p = presetCss !== 'none' ? presetCss : ''
  const a = buildAdjustCss()
  return [p, a].filter(Boolean).join(' ') || 'none'
}

function applyLiveFilter() {
  if (!imageEl.value) return
  const css = buildFullFilterCss()
  imageEl.value.style.filter = css === 'none' ? '' : css
}

function applyPreset(f) {
  activePreset.value = f.name
  presetCss = f.css
  applyLiveFilter()
}

function resetAdjusts() {
  adjusts.value = { brightness: 0, contrast: 0, saturation: 0, blur: 0 }
  applyLiveFilter()
}

function setRatio(r) {
  activeRatio.value = r.label
  cropper?.setAspectRatio(r.value)
}

function doRotate(deg) {
  cropper?.rotate(deg)
  syncOutputSize()
}

function doFlip(dir) {
  if (!cropper) return
  const d = cropper.getData()
  if (dir === 'h') cropper.scaleX(d.scaleX === -1 ? 1 : -1)
  else             cropper.scaleY(d.scaleY === -1 ? 1 : -1)
}

function syncOutputSize() {
  if (!cropper) return
  const cb = cropper.getCropBoxData()
  const id = cropper.getImageData()
  if (!id.naturalWidth || !cb.width) return
  const sx = id.naturalWidth / id.width
  const sy = id.naturalHeight / id.height
  const w  = Math.round(cb.width * sx)
  const h  = Math.round(cb.height * sy)
  outputW.value = w
  outputH.value = h
  if (w && h) cropAspectRatio = w / h
}

function onWidthChange() {
  if (lockAspect.value && cropAspectRatio && outputW.value) {
    outputH.value = Math.round(outputW.value / cropAspectRatio)
  }
}

function onHeightChange() {
  if (lockAspect.value && cropAspectRatio && outputH.value) {
    outputW.value = Math.round(outputH.value * cropAspectRatio)
  }
}

function initCropper() {
  destroyCropper()
  if (!imageEl.value || !props.src) return

  imageEl.value.style.opacity = '1'

  cropper = new Cropper(imageEl.value, {
    viewMode: 1,
    autoCropArea: 0.95,
    movable: true,
    zoomable: true,
    rotatable: true,
    scalable: true,
    background: false,
    guides: true,
    highlight: true,
    cropBoxResizable: true,
    cropBoxMovable: true,
    ready() {
      syncOutputSize()
      applyLiveFilter()
    },
    cropend: syncOutputSize,
  })
}

function destroyCropper() {
  if (cropper) {
    cropper.destroy()
    cropper = null
  }
}

function getOutputMime() {
  if (!props.src) return 'image/jpeg'
  const m = props.src.match(/^data:([^;]+)/)
  if (m) return m[1]
  const ext = props.src.split('?')[0].split('.').pop()?.toLowerCase()
  if (ext === 'png')  return 'image/png'
  if (ext === 'webp') return 'image/webp'
  return 'image/jpeg'
}

async function onApply() {
  if (!cropper || applying.value) return
  applying.value = true

  try {
    const canvasOpts = { imageSmoothingEnabled: true, imageSmoothingQuality: 'high' }
    if (outputW.value > 0) canvasOpts.width  = outputW.value
    if (outputH.value > 0) canvasOpts.height = outputH.value

    const cropped    = cropper.getCroppedCanvas(canvasOpts)
    const filterCss  = buildFullFilterCss()

    let exportCanvas
    if (filterCss && filterCss !== 'none') {
      exportCanvas        = document.createElement('canvas')
      exportCanvas.width  = cropped.width
      exportCanvas.height = cropped.height
      const ctx           = exportCanvas.getContext('2d')
      ctx.filter          = filterCss
      ctx.drawImage(cropped, 0, 0)
    } else {
      exportCanvas = cropped
    }

    const mime    = getOutputMime()
    const quality = mime === 'image/png' ? undefined : 0.92

    exportCanvas.toBlob(
      (blob) => {
        applying.value = false
        if (blob) emit('apply', blob)
        else emit('cancel')
      },
      mime,
      quality,
    )
  } catch {
    applying.value = false
    emit('cancel')
  }
}

function onSkip()   { emit('skip') }
function onCancel() { emit('cancel'); emit('update:modelValue', false) }

function reset() {
  activeTab.value    = 'Crop'
  activeRatio.value  = 'Free'
  activePreset.value = 'Normal'
  presetCss          = 'none'
  adjusts.value      = { brightness: 0, contrast: 0, saturation: 0, blur: 0 }
  outputW.value      = null
  outputH.value      = null
  lockAspect.value   = true
  cropAspectRatio    = null
}

watch(() => props.modelValue, async (val) => {
  if (val) {
    reset()
    await nextTick()
    initCropper()
  } else {
    destroyCropper()
  }
})

watch(() => props.src, async () => {
  if (props.modelValue) {
    reset()
    await nextTick()
    initCropper()
  }
})

onUnmounted(destroyCropper)
</script>
