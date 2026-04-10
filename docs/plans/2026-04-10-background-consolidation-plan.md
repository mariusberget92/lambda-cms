# Background Consolidation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Consolidate all block background controls into the Advanced tab with full Color + Gradient + Image support, removing duplicated settings from individual block Style tabs.

**Architecture:** `advBg*` keys in `block.data` are the single source of truth. `blockWrapperStyle()` in `BlockRenderer.vue` renders them as inline styles on the outer wrapper div. Block components (`SectionBlock`, `ContainerBlock`, `CtaBlock`) stop managing their own background. Settings UI panels (`ParagraphSettings`, `QuoteSettings`, `ContainerSettings`, `SectionSettings`, `CtaSettings`) have their background sections stripped.

**Tech Stack:** Vue 3 SFCs, `MediaPicker.vue` (dark mode, emits `select` event with `{ url, ... }`), existing `ColorPicker`, `SelectBox`, `SpacingControl`.

---

## Task 1: Upgrade `AdvancedSettings.vue` — add Image background type

**Files:**
- Modify: `resources/js/Components/BlockEditor/blocks/AdvancedSettings.vue`

**Step 1: Replace the entire file**

The new file adds `image` as a 4th background type with MediaPicker, position, size, and parallax controls. The `ref` import and `MediaPicker` import are new; everything else is unchanged.

```vue
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
```

**Step 2: Build**
```bash
cd /c/Users/mariu/Herd/lambda-cms && npm run build 2>&1 | tail -5
```
Expected: `✓ built in`

**Step 3: Commit**
```bash
git add resources/js/Components/BlockEditor/blocks/AdvancedSettings.vue
git commit -m "feat: add image background type to AdvancedSettings"
```

---

## Task 2: Update `BlockRenderer.vue` — add image branch

**Files:**
- Modify: `resources/js/components/BlockRenderer.vue`

**Step 1: Add image branch to `blockWrapperStyle`**

Find the existing gradient branch (lines ~88-92) and add the image branch immediately after:

```js
// FIND this exact block:
  } else if (bgType === 'gradient' && block.data?.advBgGradient) {
    const g = block.data.advBgGradient
    const dir = { 'to-r': 'to right', 'to-l': 'to left', 'to-b': 'to bottom', 'to-t': 'to top', 'to-br': 'to bottom right', 'to-bl': 'to bottom left' }[g.direction ?? 'to-r'] ?? 'to right'
    style.backgroundImage = `linear-gradient(${dir}, ${g.from ?? '#3b4252'}, ${g.to ?? '#4c566a'})`
  }
  return Object.keys(style).length ? style : undefined

// REPLACE with:
  } else if (bgType === 'gradient' && block.data?.advBgGradient) {
    const g = block.data.advBgGradient
    const dir = { 'to-r': 'to right', 'to-l': 'to left', 'to-b': 'to bottom', 'to-t': 'to top', 'to-br': 'to bottom right', 'to-bl': 'to bottom left' }[g.direction ?? 'to-r'] ?? 'to right'
    style.backgroundImage = `linear-gradient(${dir}, ${g.from ?? '#3b4252'}, ${g.to ?? '#4c566a'})`
  } else if (bgType === 'image' && block.data?.advBgImage?.url) {
    const img = block.data.advBgImage
    style.backgroundImage    = `url('${img.url}')`
    style.backgroundPosition = img.position ?? 'center'
    style.backgroundSize     = img.size ?? 'cover'
    style.backgroundRepeat   = 'no-repeat'
    if (img.parallax) style.backgroundAttachment = 'fixed'
  }
  return Object.keys(style).length ? style : undefined
```

**Step 2: Build**
```bash
npm run build 2>&1 | tail -5
```
Expected: `✓ built in`

**Step 3: Commit**
```bash
git add resources/js/components/BlockRenderer.vue
git commit -m "feat: render advBgImage in BlockRenderer wrapper style"
```

---

## Task 3: Strip `bgColor` from `ParagraphSettings.vue` and `QuoteSettings.vue`

**Files:**
- Modify: `resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/QuoteSettings.vue`

**Step 1: Remove background section from `ParagraphSettings.vue`**

Find and delete this block (lines 29-37 approx):
```html
<div>
  <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Background</label>
  <ColorPicker
    :model-value="block.data.bgColor"
    default="#ffffff"
    :show-reset="true"
    @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v } })"
  />
</div>
```

After removing, check if `ColorPicker` is still imported/used elsewhere in the file. If not, remove its import too.

**Step 2: Remove background section from `QuoteSettings.vue`**

Find and delete this block (lines 77-86 approx):
```html
<div>
  <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Background</label>
  <ColorPicker
    :model-value="block.data.bgColor"
    default="#ffffff"
    :show-reset="true"
    @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v } })"
  />
</div>
```

Same: remove ColorPicker import if no longer used.

**Step 3: Build**
```bash
npm run build 2>&1 | tail -5
```
Expected: `✓ built in`

**Step 4: Commit**
```bash
git add resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue \
        resources/js/Components/BlockEditor/blocks/QuoteSettings.vue
git commit -m "refactor: remove duplicate bgColor from Paragraph and Quote style tabs"
```

---

## Task 4: Strip background sections from `ContainerSettings`, `SectionSettings`, `CtaSettings`

**Files:**
- Modify: `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/SectionSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/CtaSettings.vue`

**Step 1: Remove background from `ContainerSettings.vue`**

The background section spans ~lines 78–197. It starts with a label like:
```html
<p class="text-xs font-semibold ...">Background</p>
```
and ends before the next section. Delete the entire block including the label.

Also remove these from the script:
- The `showBgPicker` ref
- The `onBgMediaSelect` function
- The `MediaPicker` import (if only used for bg)

**Step 2: Remove background from `SectionSettings.vue`**

Same approach — the background section is ~lines 39–158. Delete everything from the Background label down to (but not including) the next settings section. Remove `showBgPicker` ref, `onBgMediaSelect` function, and `MediaPicker` import if unused.

**Step 3: Remove background from `CtaSettings.vue`**

The background section is ~lines 33–110. Delete it. **Do not touch** the button styling section (uses `block.data.button` object, separate from bg). Remove `showBgPicker`, `onBgMediaSelect`, and `MediaPicker` import.

**Step 4: Build**
```bash
npm run build 2>&1 | tail -5
```
Expected: `✓ built in`. Fix any "unused variable" or missing import errors before proceeding.

**Step 5: Commit**
```bash
git add resources/js/Components/BlockEditor/blocks/ContainerSettings.vue \
        resources/js/Components/BlockEditor/blocks/SectionSettings.vue \
        resources/js/Components/BlockEditor/blocks/CtaSettings.vue
git commit -m "refactor: remove duplicate background sections from Container, Section, CTA style tabs"
```

---

## Task 5: Strip bg rendering from `SectionBlock`, `ContainerBlock`, `CtaBlock`

**Files:**
- Modify: `resources/js/Components/Blocks/SectionBlock.vue`
- Modify: `resources/js/Components/Blocks/ContainerBlock.vue`
- Modify: `resources/js/Components/Blocks/CtaBlock.vue`

**Step 1: Update `SectionBlock.vue`**

The `outerStyle` computed property currently builds background styles from `d.bgType / d.bgColor / d.bgImage / d.bgGradient`. Remove those branches, keeping only the padding logic. The result should look like:

```js
const outerStyle = computed(() => {
  return outerPaddingStyle.value   // or however padding was kept
})
```

Check the exact code and keep any non-background styling intact (min-height, padding, etc.).

**Step 2: Update `ContainerBlock.vue`**

Remove the entire `bgStyle` computed property (lines ~60–81). Then update `containerStyle` (lines ~83–89) to no longer spread `bgStyle`. Before:
```js
const containerStyle = computed(() => ({
  ...bgStyle.value,
  gap: ...,
  ...
}))
```
After:
```js
const containerStyle = computed(() => ({
  gap: ...,
  ...
}))
```

**Step 3: Update `CtaBlock.vue`**

The CTA block has `bg-card` hardcoded on its root div:
```html
<div class="my-4 rounded-lg border bg-card p-6 text-center">
```

Remove `bg-card` so the BlockRenderer wrapper background shows through:
```html
<div class="my-4 rounded-lg border p-6 text-center">
```

**Step 4: Build**
```bash
npm run build 2>&1 | tail -5
```
Expected: `✓ built in`

**Step 5: Commit**
```bash
git add resources/js/Components/Blocks/SectionBlock.vue \
        resources/js/Components/Blocks/ContainerBlock.vue \
        resources/js/Components/Blocks/CtaBlock.vue
git commit -m "refactor: remove own bg rendering from Section, Container, CTA — BlockRenderer handles all bg"
```

---

## Task 6: Final build + manual verification

**Step 1: Full build**
```bash
npm run build 2>&1 | tail -8
```
Expected: `✓ built in`

**Step 2: Run tests**
```bash
php artisan test --stop-on-failure 2>&1 | tail -5
```
Expected: all pass (the AutosaveTest failure is pre-existing, unrelated).

**Step 3: Manual check in browser**

Open the block editor and verify:

- [ ] Paragraph block → Style tab has NO background section
- [ ] Quote block → Style tab has NO background section
- [ ] Container block → Style tab has NO background section
- [ ] Section block → Style tab has NO background section
- [ ] CTA block → Style tab has NO background section
- [ ] Any block → Advanced tab shows Background with 4 options: None / Color / Gradient / Image
- [ ] Image option shows URL input + Library button + Position + Size + Parallax controls
- [ ] Clicking "Library" opens dark MediaPicker; selecting an image writes the URL
- [ ] Color/Gradient still work as before
- [ ] On a published page, section/container with image background renders correctly (image fills block)

**Step 4: Commit**
```bash
git add -A
git commit -m "chore: verified background consolidation complete"
```
(only if there were any remaining unstaged changes; skip if clean)
