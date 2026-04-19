# Block Editor Settings Overhaul Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace raw Tailwind class strings with proper UI inputs for typography and visual effects across all text-rendering blocks in the block editor.

**Architecture:** Extend `TypographyControl.vue` with 4 new fields (letter spacing, text decoration, text transform, text shadow). Apply all typography settings universally in `BlockRenderer.blockWrapperStyle()` so every block benefits automatically. Add a new Effects section to `AdvancedSettings.vue` (opacity, cursor, overflow, z-index, transition) also applied in `blockWrapperStyle()`. Wire `TypographyControl` into 9 blocks that currently have no typography UI, and update `STYLE_BLOCKS` in `BlockLayers.vue` to show the Style tab for them.

**Tech Stack:** Vue 3 SFCs, Tailwind CSS 4, no backend changes needed.

---

### Task 1: Extend TypographyControl.vue

**Files:**
- Modify: `resources/js/components/BlockEditor/TypographyControl.vue`

**Step 1: Add letter spacing SelectBox after the Line height control**

Replace the closing `</div>` + `</template>` section with:

```vue
    <!-- Letter spacing -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Letter spacing</label>
      <SelectBox size="sm"
        :model-value="modelValue.letterSpacing ?? ''"
        :data="[
          { value: '',        label: 'Inherit' },
          { value: '-0.05em', label: 'Tighter' },
          { value: '0em',     label: 'Normal' },
          { value: '0.025em', label: 'Wide' },
          { value: '0.05em',  label: 'Wider' },
          { value: '0.1em',   label: 'Widest' },
        ]"
        @update:model-value="v => update('letterSpacing', v || null)"
      />
    </div>

    <!-- Text decoration -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text decoration</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="opt in [{ value: 'none', label: 'None' }, { value: 'underline', label: 'Underline' }, { value: 'line-through', label: 'Strike' }]"
          :key="opt.value"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(modelValue.textDecoration ?? 'none') === opt.value
            ? 'bg-primary text-primary-foreground'
            : 'bg-background text-foreground'"
          @click="update('textDecoration', opt.value === 'none' ? null : opt.value)"
        >{{ opt.label }}</button>
      </div>
    </div>

    <!-- Text transform -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Transform</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="opt in [{ value: 'none', label: 'None' }, { value: 'uppercase', label: 'AA' }, { value: 'lowercase', label: 'aa' }, { value: 'capitalize', label: 'Aa' }]"
          :key="opt.value"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(modelValue.textTransform ?? 'none') === opt.value
            ? 'bg-primary text-primary-foreground'
            : 'bg-background text-foreground'"
          @click="update('textTransform', opt.value === 'none' ? null : opt.value)"
        >{{ opt.label }}</button>
      </div>
    </div>

    <!-- Text shadow -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text shadow</label>
      <div class="space-y-2">
        <ColorPicker
          :model-value="modelValue.textShadow?.color ?? ''"
          default="#000000"
          :show-reset="true"
          @update:model-value="v => updateShadow('color', v)"
        />
        <div class="grid grid-cols-3 gap-1">
          <div>
            <label class="text-[10px] text-muted-foreground block mb-0.5">X (px)</label>
            <input type="number" :value="modelValue.textShadow?.x ?? 0"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateShadow('x', Number($event.target.value))" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground block mb-0.5">Y (px)</label>
            <input type="number" :value="modelValue.textShadow?.y ?? 0"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateShadow('y', Number($event.target.value))" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground block mb-0.5">Blur</label>
            <input type="number" :value="modelValue.textShadow?.blur ?? 0" min="0"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateShadow('blur', Number($event.target.value))" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

**Step 2: Add `updateShadow` helper to the script**

After the existing `update` function, add:

```js
function updateShadow(key, value) {
  emit('update:modelValue', {
    ...props.modelValue,
    textShadow: { ...(props.modelValue.textShadow ?? {}), [key]: value },
  })
}
```

**Step 3: Remove the old closing `</div></template>` that you replaced**

The file's template section now ends with the new shadow block's `</div></div></template>`.

**Step 4: Commit**

```bash
git add resources/js/components/BlockEditor/TypographyControl.vue
git commit -m "feat: extend TypographyControl with letter spacing, decoration, transform, text shadow"
```

---

### Task 2: Apply typography + new fields in BlockRenderer

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue` (function `blockWrapperStyle`, ~line 80)

**Step 1: Add typography application block inside `blockWrapperStyle`**

After the existing `if (block.fontFamily)` line, add:

```js
  // Typography (from TypographyControl — cascades into block content)
  const typo = block.data?.typography
  if (typo) {
    if (typo.textAlign)      style.textAlign      = typo.textAlign
    if (typo.color)          style.color          = typo.color
    if (typo.fontSize)       style.fontSize       = typo.fontSize
    if (typo.fontWeight)     style.fontWeight     = typo.fontWeight
    if (typo.lineHeight)     style.lineHeight     = typo.lineHeight
    if (typo.letterSpacing)  style.letterSpacing  = typo.letterSpacing
    if (typo.textDecoration) style.textDecoration = typo.textDecoration
    if (typo.textTransform)  style.textTransform  = typo.textTransform
    if (typo.textShadow?.color) {
      const ts = typo.textShadow
      style.textShadow = `${ts.x ?? 0}px ${ts.y ?? 0}px ${ts.blur ?? 0}px ${ts.color}`
    }
  }
```

**Step 2: Commit**

```bash
git add resources/js/Components/BlockRenderer.vue
git commit -m "feat: apply typography settings from block.data.typography in blockWrapperStyle"
```

---

### Task 3: Add TypographyControl to LinkSettings

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/LinkSettings.vue`

**Step 1: Import TypographyControl in the script**

Add to imports:
```js
import TypographyControl from '../TypographyControl.vue'
```

**Step 2: Replace the Style tab content**

Replace:
```vue
  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
```

With:
```vue
  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <TypographyControl
      :model-value="block.data?.typography ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
    />
    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
```

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/LinkSettings.vue
git commit -m "feat: add TypographyControl to LinkSettings style tab"
```

---

### Task 4: Add style tab + TypographyControl to 8 text blocks

Each of the 8 blocks below needs a style tab div added with `TypographyControl`. Follow this exact pattern for each.

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/FilterLinkSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/NavigationSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/SearchSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostTitleSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostMetaSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostAuthorSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostTaxonomySettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/ArchiveTitleSettings.vue`

**Pattern to apply to every file:**

1. Add import in `<script setup>`:
```js
import TypographyControl from '../TypographyControl.vue'
```

2. Add style tab section inside `<template>` (after the existing content div):
```vue
    <!-- Style tab -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <TypographyControl
        :model-value="block.data?.typography ?? {}"
        @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
      />
    </div>
```

3. Make sure `block.id` is available — every file already receives `block` as a prop. For files that currently emit without `id` (e.g. `FilterLinkSettings` emits `{ data: {...} }` without `id`), update the typography emit to match the standard pattern above (other emits in the file can stay as-is).

**Step 1: Apply to FilterLinkSettings.vue**

Add import, add style tab div after the closing `</div>` of the content section (but before `</template>`).

**Step 2: Apply to NavigationSettings.vue**

Same pattern. Add style tab div after the existing content.

**Step 3: Apply to SearchSettings.vue**

Same pattern.

**Step 4: Apply to PostTitleSettings.vue**

Add import, add style tab div after the tag selector div, inside the outer `<div class="p-3">`.

**Step 5: Apply to PostMetaSettings.vue**

Same pattern — add style tab after the checkboxes content section.

**Step 6: Apply to PostAuthorSettings.vue**

Same pattern.

**Step 7: Apply to PostTaxonomySettings.vue**

Same pattern.

**Step 8: Apply to ArchiveTitleSettings.vue**

Same pattern — nearly identical to PostTitleSettings.

**Step 9: Commit all 8 files**

```bash
git add resources/js/components/BlockEditor/blocks/FilterLinkSettings.vue \
        resources/js/components/BlockEditor/blocks/NavigationSettings.vue \
        resources/js/components/BlockEditor/blocks/SearchSettings.vue \
        resources/js/components/BlockEditor/blocks/PostTitleSettings.vue \
        resources/js/components/BlockEditor/blocks/PostMetaSettings.vue \
        resources/js/components/BlockEditor/blocks/PostAuthorSettings.vue \
        resources/js/components/BlockEditor/blocks/PostTaxonomySettings.vue \
        resources/js/components/BlockEditor/blocks/ArchiveTitleSettings.vue
git commit -m "feat: add Style tab with TypographyControl to 8 text-rendering blocks"
```

---

### Task 5: Update STYLE_BLOCKS in BlockLayers.vue

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue` (line ~224)

**Step 1: Add the 8 new block types to the STYLE_BLOCKS Set**

Find:
```js
const STYLE_BLOCKS = new Set([
  'container', 'section', 'spacer', 'divider', 'loop',
  'post-featured-image', 'archive-loop',
  'link', 'accordion', 'tabs', 'embed', 'pagination', 'heading',
  // Content blocks with style tabs
  'paragraph', 'image', 'gallery', 'video', 'quote', 'cta', 'code',
  'table',
])
```

Replace with:
```js
const STYLE_BLOCKS = new Set([
  'container', 'section', 'spacer', 'divider', 'loop',
  'post-featured-image', 'archive-loop',
  'link', 'accordion', 'tabs', 'embed', 'pagination', 'heading',
  // Content blocks with style tabs
  'paragraph', 'image', 'gallery', 'video', 'quote', 'cta', 'code',
  'table',
  // Text-rendering blocks now with TypographyControl
  'filter-link', 'navigation', 'search',
  'post-title', 'post-meta', 'post-author', 'post-taxonomy',
  'archive-title',
])
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: add 8 text blocks to STYLE_BLOCKS to show Style tab"
```

---

### Task 6: Add Effects section to AdvancedSettings.vue

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/AdvancedSettings.vue`

**Step 1: Add the Effects section after the Background section (before the "Advanced" section)**

Find the line:
```vue
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground pt-1 border-t">Advanced</p>
```

Insert before it:
```vue
    <!-- Effects -->
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground pt-1 border-t">Effects</p>

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
        :value="block.data?.zIndex ?? ''"
        placeholder="Auto"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs"
        @input="updateData('zIndex', $event.target.value !== '' ? Number($event.target.value) : null)"
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
            { value: '',           label: 'Ease (default)' },
            { value: 'linear',     label: 'Linear' },
            { value: 'ease-in',    label: 'Ease in' },
            { value: 'ease-out',   label: 'Ease out' },
            { value: 'ease-in-out', label: 'Ease in-out' },
          ]"
          @update:model-value="v => updateData('transitionEasing', v || null)"
        />
      </div>
    </div>

```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/AdvancedSettings.vue
git commit -m "feat: add Effects section (opacity, cursor, overflow, z-index, transition) to AdvancedSettings"
```

---

### Task 7: Apply Effects in BlockRenderer.blockWrapperStyle

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue`

**Step 1: Add effects block after the border section, before the `return` statement**

```js
  // Effects (from AdvancedSettings Effects section)
  if (block.data?.opacity != null && block.data.opacity !== 100) {
    style.opacity = block.data.opacity / 100
  }
  if (block.data?.cursor)   style.cursor   = block.data.cursor
  if (block.data?.overflow) style.overflow = block.data.overflow
  if (block.data?.zIndex != null) style.zIndex = block.data.zIndex
  if (block.data?.transitionDuration) {
    const dur  = block.data.transitionDuration
    const ease = block.data.transitionEasing ?? 'ease'
    style.transition = `all ${dur} ${ease}`
  }
```

**Step 2: Commit**

```bash
git add resources/js/Components/BlockRenderer.vue
git commit -m "feat: apply opacity, cursor, overflow, z-index, transition in blockWrapperStyle"
```

---

### Task 8: Build and verify

**Step 1: Run the build**

```bash
npm run build
```

Expected: `✓ built in ~10s` with no new errors (pre-existing chunk size warnings are fine).

**Step 2: Verify in the browser**

1. Open any page/post with the block editor
2. Select a **Heading** block → Style tab → confirm all typography fields are present including the new letter spacing, decoration, transform, shadow fields
3. Select a **Post Title** block → confirm Style tab now appears and contains TypographyControl
4. Select a **Filter Link** block → confirm Style tab appears
5. Select any block → Advanced tab → confirm Effects section appears with opacity slider, cursor, overflow, z-index, transition controls
6. Set opacity to 50 on any block → confirm the block renders at 50% opacity in the canvas

**Step 3: Final commit (if any fixups were needed)**

```bash
git add -A
git commit -m "fix: block editor settings overhaul cleanup"
```
