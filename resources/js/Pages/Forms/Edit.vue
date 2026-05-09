<!-- resources/js/Pages/Forms/Edit.vue -->
<template>
  <AppLayout :title="isEditing ? `Edit: ${form?.name}` : 'New Form'">
    <Head :title="isEditing ? 'Edit Form' : 'New Form'" />

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <a
        :href="route('forms.index')"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div class="flex-1">
        <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit form' : 'New form' }}</h2>
        <p class="text-sm text-muted-foreground mt-0.5">{{ isEditing ? formData.name : 'Build your form fields below' }}</p>
      </div>
      <a
        v-if="isEditing"
        :href="route('forms.submissions', form.id)"
        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:bg-accent transition-colors"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Submissions
      </a>
      <button
        type="button"
        :disabled="saving"
        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-60"
        @click="save"
      >
        <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        {{ saving ? 'Saving…' : isEditing ? 'Save changes' : 'Create form' }}
      </button>
    </div>

    <!-- Server-side errors -->
    <div v-if="Object.keys(errors).length" class="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 p-4">
      <ul class="text-sm text-destructive space-y-1 list-disc list-inside">
        <li v-for="(msg, key) in errors" :key="key">{{ msg }}</li>
      </ul>
    </div>

    <!-- Form settings card -->
    <div class="rounded-lg border bg-card p-4 mb-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Form name <span class="text-destructive">*</span></label>
          <input
            v-model="formData.name"
            type="text"
            placeholder="Contact form"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': errors.name }"
            @input="autoSlug"
          />
          <p v-if="errors.name" class="text-xs text-destructive mt-1">{{ errors.name }}</p>
        </div>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Slug <span class="text-destructive">*</span></label>
          <input
            v-model="formData.slug"
            type="text"
            placeholder="contact-form"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': errors.slug }"
          />
          <p v-if="errors.slug" class="text-xs text-destructive mt-1">{{ errors.slug }}</p>
        </div>
        <div class="sm:col-span-2">
          <label class="text-xs font-medium text-muted-foreground block mb-1">Description</label>
          <input
            v-model="formData.description"
            type="text"
            placeholder="Optional description for this form"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Success message</label>
          <input
            v-model="formData.success_message"
            type="text"
            placeholder="Thank you! Your submission has been received."
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Email notifications</label>
          <input
            v-model="formData.notify_email"
            type="email"
            placeholder="admin@example.com"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': errors.notify_email }"
          />
          <p v-if="errors.notify_email" class="text-xs text-destructive mt-1">{{ errors.notify_email }}</p>
        </div>
      </div>
    </div>

    <!-- Builder: 3 panels -->
    <div class="flex gap-4" style="min-height: 460px">

      <!-- Left: Field type palette -->
      <div class="w-44 shrink-0 rounded-lg border bg-card overflow-hidden flex flex-col">
        <div class="px-3 py-2 border-b bg-muted/30 shrink-0">
          <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Field types</p>
        </div>
        <VueDraggable
          :model-value="FIELD_TYPES"
          tag="div"
          class="flex-1 p-2 grid grid-cols-2 gap-1.5 content-start overflow-y-auto"
          :group="{ name: 'formfields', pull: 'clone', put: false }"
          :sort="false"
          :clone="cloneField"
        >
          <div
            v-for="ft in FIELD_TYPES"
            :key="ft.type"
            class="flex flex-col items-center justify-center gap-1.5 rounded-lg border bg-white/5 border-white/10 px-1 py-3 cursor-grab active:cursor-grabbing hover:bg-white/10 hover:border-white/20 transition-colors select-none"
            :title="`${ft.label} — drag or click to add`"
            @click="addField(ft.type)"
          >
            <component :is="ft.icon" class="w-4 h-4 shrink-0 text-[var(--chart-1)]" />
            <span class="text-[10px] leading-none text-center text-muted-foreground">{{ ft.label }}</span>
          </div>
        </VueDraggable>
      </div>

      <!-- Centre: Field list -->
      <div class="flex-1 min-w-0 rounded-lg border bg-card overflow-hidden flex flex-col">
        <div class="px-3 py-2 border-b bg-muted/30 flex items-center justify-between shrink-0">
          <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Fields</p>
          <span class="text-[10px] text-muted-foreground">{{ formData.fields.length }} field{{ formData.fields.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- Empty state -->
        <div v-if="formData.fields.length === 0" class="flex-1 flex items-center justify-center p-8">
          <div class="text-center">
            <svg class="w-8 h-8 mx-auto mb-3 text-muted-foreground/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <p class="text-sm text-muted-foreground">Drag a field type from the left<br>or click one to add it</p>
          </div>
        </div>

        <VueDraggable
          v-model="formData.fields"
          tag="div"
          class="flex-1 p-2 space-y-1 overflow-y-auto"
          handle=".field-drag-handle"
          ghost-class="opacity-40"
          :group="{ name: 'formfields' }"
          :animation="150"
        >
          <div
            v-for="(field, i) in formData.fields"
            :key="field._key"
            class="group flex items-center gap-2 rounded-md px-2.5 py-2 cursor-pointer transition-colors text-sm"
            :class="selectedIndex === i
              ? 'bg-primary text-primary-foreground'
              : 'hover:bg-accent text-foreground'"
            @click="selectedIndex = i"
          >
            <span
              class="field-drag-handle cursor-grab active:cursor-grabbing shrink-0"
              :class="selectedIndex === i ? 'text-primary-foreground/60' : 'text-muted-foreground'"
              @click.stop
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M9 5h.01M9 12h.01M9 19h.01M15 5h.01M15 12h.01M15 19h.01"/>
              </svg>
            </span>
            <component
              :is="FIELD_TYPE_MAP[field.type]?.icon ?? DefaultIcon"
              class="w-3.5 h-3.5 shrink-0"
              :class="selectedIndex === i ? 'text-primary-foreground/80' : 'text-muted-foreground'"
            />
            <span class="flex-1 truncate font-medium text-xs">{{ field.label || '(no label)' }}</span>
            <span class="text-[9px] opacity-60 shrink-0">{{ FIELD_TYPE_MAP[field.type]?.label }}</span>
            <span
              v-if="field.required"
              class="shrink-0 text-[9px] font-bold px-1 py-0.5 rounded"
              :class="selectedIndex === i ? 'bg-white/25 text-white' : 'bg-destructive/15 text-destructive'"
            >REQ</span>
            <button
              type="button"
              class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
              :class="selectedIndex === i ? 'text-primary-foreground' : 'text-muted-foreground'"
              title="Remove field"
              @click.stop="removeField(i)"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </VueDraggable>
      </div>

      <!-- Right: Field settings -->
      <div class="w-72 shrink-0 rounded-lg border bg-card overflow-hidden flex flex-col">
        <div class="px-3 py-2 border-b bg-muted/30 shrink-0">
          <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">Field settings</p>
        </div>

        <div v-if="selectedField === null" class="flex-1 flex items-center justify-center p-6">
          <p class="text-xs text-muted-foreground text-center">Select a field<br>to edit its settings</p>
        </div>

        <div v-else class="flex-1 overflow-y-auto p-3 space-y-3">
          <!-- Type badge -->
          <div class="flex items-center gap-2 pb-1">
            <component :is="FIELD_TYPE_MAP[selectedField.type]?.icon ?? DefaultIcon" class="w-3.5 h-3.5 text-muted-foreground" />
            <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">{{ FIELD_TYPE_MAP[selectedField.type]?.label }}</span>
          </div>

          <!-- Label -->
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Label <span class="text-destructive">*</span></label>
            <input
              v-model="selectedField.label"
              type="text"
              placeholder="Field label"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              @input="autoFieldName"
            />
          </div>

          <!-- Field name (key) -->
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Field key</label>
            <input
              v-model="selectedField.name"
              type="text"
              placeholder="field_name"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <p class="text-[10px] text-muted-foreground mt-0.5">Used as the submission data key</p>
          </div>

          <!-- Placeholder (for text-like fields) -->
          <div v-if="['text','email','textarea','number','date','phone','url'].includes(selectedField.type)">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Placeholder</label>
            <input
              v-model="selectedField.placeholder"
              type="text"
              placeholder="Enter placeholder text"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>

          <!-- Help text -->
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Help text</label>
            <input
              v-model="selectedField.help_text"
              type="text"
              placeholder="Optional hint under the field"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>

          <!-- Default value (hidden fields) -->
          <div v-if="selectedField.type === 'hidden'">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Value</label>
            <input
              v-model="selectedField.default_value"
              type="text"
              placeholder="Hidden value"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>

          <!-- Required toggle -->
          <label v-if="selectedField.type !== 'hidden'" class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" v-model="selectedField.required" class="rounded accent-primary w-3.5 h-3.5" />
            <span class="text-xs text-muted-foreground">Required field</span>
          </label>

          <!-- Width toggle -->
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Width</label>
            <div class="flex rounded-md border overflow-hidden text-xs">
              <button
                type="button"
                class="flex-1 py-1.5 transition-colors"
                :class="(selectedField.width ?? 'full') === 'full' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                @click="selectedField.width = 'full'"
              >Full width</button>
              <button
                type="button"
                class="flex-1 py-1.5 transition-colors"
                :class="selectedField.width === 'half' ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                @click="selectedField.width = 'half'"
              >Half width</button>
            </div>
          </div>

          <!-- Options (select / radio / checkboxes) -->
          <div v-if="['select', 'radio', 'checkboxes'].includes(selectedField.type)">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Options</label>
            <div class="space-y-1">
              <div
                v-for="(opt, j) in (selectedField.options ?? [])"
                :key="j"
                class="flex items-center gap-1"
              >
                <input
                  :value="opt"
                  type="text"
                  class="flex-1 min-w-0 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
                  @input="updateOption(j, $event.target.value)"
                />
                <button
                  type="button"
                  class="shrink-0 text-muted-foreground hover:text-destructive transition-colors"
                  @click="removeOption(j)"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
            </div>
            <button
              type="button"
              class="mt-1.5 text-xs text-primary hover:underline"
              @click="addOption"
            >+ Add option</button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, markRaw } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { VueDraggable } from 'vue-draggable-plus'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'
import {
  AlignLeft, Mail, AlignJustify, ChevronDown, List, CheckSquare,
  Square, Hash, Calendar, Phone, Link, EyeOff,
} from 'lucide-vue-next'

const { notify } = useNotifications()

const props = defineProps({
  form:   { type: Object, default: null },
  fields: { type: Array,  default: () => [] },
})

const isEditing = computed(() => !!props.form?.id)
const saving    = ref(false)
const errors    = ref({})

// Field type definitions
const FIELD_TYPES = [
  { type: 'text',        label: 'Text',      icon: markRaw(AlignLeft) },
  { type: 'email',       label: 'Email',     icon: markRaw(Mail) },
  { type: 'textarea',    label: 'Textarea',  icon: markRaw(AlignJustify) },
  { type: 'select',      label: 'Select',    icon: markRaw(ChevronDown) },
  { type: 'radio',       label: 'Radio',     icon: markRaw(List) },
  { type: 'checkboxes',  label: 'Checks',    icon: markRaw(CheckSquare) },
  { type: 'checkbox',    label: 'Checkbox',  icon: markRaw(Square) },
  { type: 'number',      label: 'Number',    icon: markRaw(Hash) },
  { type: 'date',        label: 'Date',      icon: markRaw(Calendar) },
  { type: 'phone',       label: 'Phone',     icon: markRaw(Phone) },
  { type: 'url',         label: 'URL',       icon: markRaw(Link) },
  { type: 'hidden',      label: 'Hidden',    icon: markRaw(EyeOff) },
]

const FIELD_TYPE_MAP = Object.fromEntries(FIELD_TYPES.map(ft => [ft.type, ft]))
const DefaultIcon    = markRaw(AlignLeft)

// ── Form data ─────────────────────────────────────────────────────────────────
let _keyCounter = 0
function makeKey() { return ++_keyCounter }

function prepareFields(rawFields) {
  return (rawFields ?? []).map(f => ({ ...f, _key: makeKey() }))
}

const formData = ref({
  name:              props.form?.name              ?? '',
  slug:              props.form?.slug              ?? '',
  description:       props.form?.description       ?? '',
  success_message:   props.form?.success_message   ?? 'Thank you! Your submission has been received.',
  notify_email:      props.form?.notify_email       ?? '',
  store_submissions: props.form?.store_submissions ?? true,
  fields:            prepareFields(props.fields),
})

// ── Selected field ─────────────────────────────────────────────────────────────
const selectedIndex = ref(null)
const selectedField = computed(() =>
  selectedIndex.value !== null ? formData.value.fields[selectedIndex.value] ?? null : null
)

// ── Slug / field name helpers ─────────────────────────────────────────────────
let _slugEdited = isEditing.value
function toSlug(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}
function toSnake(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
}
function autoSlug() {
  if (!_slugEdited) formData.value.slug = toSlug(formData.value.name)
}

function autoFieldName() {
  if (!selectedField.value) return
  // Only auto-update if the name looks auto-generated (matches the previous label-derived value)
  const prev = toSnake(selectedField.value.name || '')
  if (!selectedField.value.name || selectedField.value.name === prev || selectedField.value.name === toSnake(selectedField.value.label)) {
    selectedField.value.name = toSnake(selectedField.value.label)
  }
}

// Watch slug input so we know the user manually set it
function onSlugInput() { _slugEdited = true }

// ── Field management ──────────────────────────────────────────────────────────
function cloneField(typeDef) {
  const label = typeDef.label
  const field = {
    _key:          makeKey(),
    type:          typeDef.type,
    label,
    name:          toSnake(label),
    placeholder:   '',
    help_text:     '',
    required:      false,
    options:       ['select', 'radio', 'checkboxes'].includes(typeDef.type)
                     ? ['Option 1', 'Option 2', 'Option 3']
                     : null,
    default_value: '',
    width:         'full',
  }
  return field
}

function addField(type) {
  const typeDef = FIELD_TYPE_MAP[type]
  if (!typeDef) return
  const field = cloneField(typeDef)
  formData.value.fields.push(field)
  selectedIndex.value = formData.value.fields.length - 1
}

function removeField(i) {
  formData.value.fields.splice(i, 1)
  if (selectedIndex.value === i) selectedIndex.value = null
  else if (selectedIndex.value !== null && selectedIndex.value > i) selectedIndex.value--
}

// ── Options helpers ───────────────────────────────────────────────────────────
function addOption() {
  if (!selectedField.value) return
  if (!selectedField.value.options) selectedField.value.options = []
  selectedField.value.options.push(`Option ${selectedField.value.options.length + 1}`)
}
function removeOption(j) {
  if (!selectedField.value?.options) return
  selectedField.value.options.splice(j, 1)
}
function updateOption(j, value) {
  if (!selectedField.value?.options) return
  selectedField.value.options[j] = value
}

// ── Save ─────────────────────────────────────────────────────────────────────
function save() {
  if (saving.value) return
  saving.value = true
  errors.value = {}

  const payload = {
    name:              formData.value.name,
    slug:              formData.value.slug,
    description:       formData.value.description,
    success_message:   formData.value.success_message,
    notify_email:      formData.value.notify_email,
    store_submissions: formData.value.store_submissions,
    fields: formData.value.fields.map(({ _key, ...rest }) => rest),
  }

  const opts = {
    onError: (errs) => { errors.value = errs; saving.value = false },
    onFinish: () => { saving.value = false },
    onSuccess: () => notify({ title: isEditing.value ? 'Form saved' : 'Form created', type: 'success' }),
  }

  if (isEditing.value) {
    router.put(route('forms.update', props.form.id), payload, opts)
  } else {
    router.post(route('forms.store'), payload, opts)
  }
}
</script>
