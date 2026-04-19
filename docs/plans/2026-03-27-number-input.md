# NumberInput Component Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a `NumberInput.vue` component with custom Nord-styled ▲▼ chevron buttons replacing the browser's native spinners, then swap it in across all 11 number inputs in the codebase.

**Architecture:** A wrapper `<div>` contains a native `<input type="number">` (spinners hidden via `[appearance:textfield]`) and two absolutely-positioned chevron buttons on the right. Buttons call `stepUp()`/`stepDown()` on the input ref — `min`/`max`/`step` enforcement is automatic. Uses `inheritAttrs: false` so `id` and other HTML attrs forward to the `<input>`, not the wrapper.

**Tech Stack:** Vue 3 `<script setup>`, lucide-vue-next (`ChevronUp`/`ChevronDown`), Tailwind 4 Nord tokens.

---

### Task 1: Create `NumberInput.vue`

**Files:**
- Create: `resources/js/Components/NumberInput.vue`

**Step 1: Create the file**

```vue
<script setup>
import { ref } from 'vue'
import { ChevronUp, ChevronDown } from 'lucide-vue-next'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: [Number, String], default: 0 },
  min:      { type: Number, default: undefined },
  max:      { type: Number, default: undefined },
  step:     { type: Number, default: 1 },
  disabled: { type: Boolean, default: false },
  error:    { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const inputRef = ref(null)

function onInput(e) {
  emit('update:modelValue', e.target.value === '' ? '' : Number(e.target.value))
}

function stepUp() {
  inputRef.value?.stepUp()
  emit('update:modelValue', Number(inputRef.value.value))
}

function stepDown() {
  inputRef.value?.stepDown()
  emit('update:modelValue', Number(inputRef.value.value))
}
</script>

<template>
  <div class="relative inline-flex w-full">
    <input
      ref="inputRef"
      v-bind="$attrs"
      type="number"
      :value="modelValue"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      class="w-full rounded-md border bg-background pl-3 pr-7 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring [appearance:textfield] disabled:opacity-50 disabled:cursor-not-allowed"
      :class="error ? 'border-destructive' : 'border-border'"
      @input="onInput"
    />
    <div class="absolute right-0 inset-y-0 flex flex-col border-l border-border rounded-r-md overflow-hidden">
      <button
        type="button"
        tabindex="-1"
        :disabled="disabled"
        class="flex-1 flex items-center justify-center px-1 text-muted-foreground hover:bg-accent/20 hover:text-foreground transition-colors border-b border-border disabled:opacity-40 disabled:cursor-not-allowed"
        @click="stepUp"
      >
        <ChevronUp class="w-3 h-3" />
      </button>
      <button
        type="button"
        tabindex="-1"
        :disabled="disabled"
        class="flex-1 flex items-center justify-center px-1 text-muted-foreground hover:bg-accent/20 hover:text-foreground transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
        @click="stepDown"
      >
        <ChevronDown class="w-3 h-3" />
      </button>
    </div>
  </div>
</template>
```

**Key decisions:**
- `inheritAttrs: false` + `v-bind="$attrs"` on `<input>` so `id="mail_port"` etc. reach the input (label `for=` associations work)
- `error` prop drives `border-destructive` vs `border-border` — avoids Tailwind class merging conflicts
- `tabindex="-1"` on buttons keeps tab focus on the native input only
- `onInput` emits `Number(value)` or `''` for empty — callers that used `v-model.number` can drop the `.number` modifier

**Step 2: Commit**

```bash
git add resources/js/Components/NumberInput.vue
git commit -m "feat: NumberInput — custom Nord-styled spinner buttons"
```

---

### Task 2: Update Install pages

**Files:**
- Modify: `resources/js/Pages/Install/Mail.vue`
- Modify: `resources/js/Pages/Install/Database.vue`

**Step 1: Read both files, add import to each**

In each file's `<script setup>`, add:
```js
import NumberInput from '@/Components/NumberInput.vue'
```

**Step 2: Mail.vue — replace port input**

Find:
```html
            <input
              v-model="form.port"
              type="number"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
              :class="{ 'border-destructive': form.errors.port }"
            />
```

Replace with:
```html
            <NumberInput
              v-model="form.port"
              :error="!!form.errors.port"
            />
```

**Step 3: Database.vue — replace port input (identical pattern)**

Find:
```html
            <input
              v-model="form.port"
              type="number"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
              :class="{ 'border-destructive': form.errors.port }"
            />
```

Replace with:
```html
            <NumberInput
              v-model="form.port"
              :error="!!form.errors.port"
            />
```

**Step 4: Commit**

```bash
git add resources/js/Pages/Install/Mail.vue resources/js/Pages/Install/Database.vue
git commit -m "feat: install pages — use NumberInput for port fields"
```

---

### Task 3: Update `Settings/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

**Step 1: Read the file, add import**

```js
import NumberInput from '@/Components/NumberInput.vue'
```

**Step 2: Replace mail port input**

Find (around line 156):
```html
              <input
                id="mail_port"
                v-model.number="mailForm['mail.port']"
                type="number"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mailForm.errors['mail.port'] }"
              />
```

Replace with:
```html
              <NumberInput
                id="mail_port"
                v-model="mailForm['mail.port']"
                :error="!!mailForm.errors['mail.port']"
              />
```

**Step 3: Replace max upload MB input**

Find (around line 273):
```html
              <input
                id="media_max_upload_mb"
                v-model.number="mediaForm['media.max_upload_mb']"
                type="number"
                min="1"
                max="100"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mediaForm.errors['media.max_upload_mb'] }"
              />
```

Replace with:
```html
              <NumberInput
                id="media_max_upload_mb"
                v-model="mediaForm['media.max_upload_mb']"
                :min="1"
                :max="100"
                :error="!!mediaForm.errors['media.max_upload_mb']"
              />
```

**Step 4: Replace resize max width input**

Find (around line 286):
```html
              <input
                id="media_resize_max_width"
                v-model.number="mediaForm['media.resize_max_width']"
                type="number"
                min="320"
                max="8000"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mediaForm.errors['media.resize_max_width'] }"
              />
```

Replace with:
```html
              <NumberInput
                id="media_resize_max_width"
                v-model="mediaForm['media.resize_max_width']"
                :min="320"
                :max="8000"
                :error="!!mediaForm.errors['media.resize_max_width']"
              />
```

**Step 5: Replace comments per page input**

Find (around line 337):
```html
              <input
                id="comments_per_page"
                v-model.number="commentsForm['comments.per_page']"
                type="number"
                min="5"
                max="100"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': commentsForm.errors['comments.per_page'] }"
              />
```

Replace with:
```html
              <NumberInput
                id="comments_per_page"
                v-model="commentsForm['comments.per_page']"
                :min="5"
                :max="100"
                :error="!!commentsForm.errors['comments.per_page']"
              />
```

**Step 6: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "feat: settings — use NumberInput for numeric fields"
```

---

### Task 4: Update BlockEditor settings

**Files:**
- Modify: `resources/js/Components/BlockEditor/blocks/LoopSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/ComponentSettings.vue`

**Step 1: Read all three files, add import to each**

```js
import NumberInput from '@/Components/NumberInput.vue'
```

**Step 2: LoopSettings.vue — replace limit input**

Find (around line 104):
```html
        <input
          type="number"
          min="1"
          max="100"
          :value="block.data.limit ?? 12"
          class="w-full rounded border bg-background px-2 py-1.5 text-sm"
          @input="emitData({ limit: parseInt($event.target.value) || 12 })"
        />
```

Replace with:
```html
        <NumberInput
          :model-value="block.data.limit ?? 12"
          :min="1"
          :max="100"
          @update:model-value="emitData({ limit: $event || 12 })"
        />
```

**Step 3: LoopSettings.vue — replace offset input**

Find (around line 116):
```html
        <input
          type="number"
          min="0"
          :value="block.data.offset ?? 0"
          class="w-full rounded border bg-background px-2 py-1.5 text-sm"
          @input="emitData({ offset: parseInt($event.target.value) || 0 })"
        />
```

Replace with:
```html
        <NumberInput
          :model-value="block.data.offset ?? 0"
          :min="0"
          @update:model-value="emitData({ offset: $event || 0 })"
        />
```

**Step 4: ContainerSettings.vue — replace columns inputs (3 breakpoints in a v-for)**

Find (around line 117):
```html
            <input
              type="number" min="1" max="12"
              :value="getBreakpoint('columns', bp) ?? ''"
              placeholder="–"
              class="w-full rounded border border-border bg-background px-1.5 py-1 text-xs text-center"
              @change="v => setBreakpoint('columns', bp, parseInt(v.target.value) || null)"
            />
```

Replace with:
```html
            <NumberInput
              :model-value="getBreakpoint('columns', bp) ?? ''"
              :min="1"
              :max="12"
              @update:model-value="v => setBreakpoint('columns', bp, v || null)"
            />
```

**Step 5: ComponentSettings.vue — replace limit input**

Find (around line 34):
```html
          <input
            type="number"
            min="1"
            max="100"
            :value="block.data.limit"
            class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="update('limit', parseInt($event.target.value) || 6)"
          />
```

Replace with:
```html
          <NumberInput
            :model-value="block.data.limit"
            :min="1"
            :max="100"
            @update:model-value="update('limit', $event || 6)"
          />
```

**Step 6: ComponentSettings.vue — replace offset input**

Find (around line 44):
```html
          <input
            type="number"
            min="0"
            :value="block.data.offset"
            class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="update('offset', parseInt($event.target.value) || 0)"
          />
```

Replace with:
```html
          <NumberInput
            :model-value="block.data.offset"
            :min="0"
            @update:model-value="update('offset', $event || 0)"
          />
```

**Step 7: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/LoopSettings.vue resources/js/Components/BlockEditor/blocks/ContainerSettings.vue resources/js/Components/BlockEditor/blocks/ComponentSettings.vue
git commit -m "feat: block editor settings — use NumberInput for numeric fields"
```

---

### Task 5: Build and verify

**Step 1: Run production build**

```bash
npm run build
```
Expected: no errors, build succeeds.

**Step 2: Smoke test checklist**
- [ ] Settings → Mail: port field shows custom ▲▼ buttons, matches border/background colors
- [ ] Settings → Media: max upload MB and resize width fields have custom spinners
- [ ] Settings → Comments: per page field has custom spinners
- [ ] Install wizard: port fields on Database and Mail steps have custom spinners
- [ ] Block editor → Loop block settings: limit/offset fields have custom spinners
- [ ] Block editor → Container settings (grid mode): columns inputs have custom spinners
- [ ] Dark mode: buttons use `hover:bg-accent/20`, borders use `border-border` — all match theme
- [ ] Error state: submitting invalid data shows `border-destructive` on the relevant field

**Step 3: Commit if any fixes needed**

```bash
git add -A
git commit -m "fix: NumberInput polish"
```
