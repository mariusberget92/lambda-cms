<template>
  <div ref="rootRef" class="relative" @keydown.escape="open = false">

    <!-- Trigger -->
    <button
      type="button"
      :disabled="disabled"
      class="w-full flex items-center justify-between rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
      :class="{ 'ring-2 ring-ring': open }"
      @click="toggle"
    >
      <span :class="{ 'text-muted-foreground': !hasSelection }">{{ displayLabel }}</span>

      <span class="flex items-center gap-1 shrink-0 ml-2">
        <!-- Clear button — only when something is selected -->
        <span
          v-if="hasSelection"
          role="button"
          tabindex="0"
          aria-label="Clear selection"
          class="text-muted-foreground hover:text-foreground transition-colors"
          @click.stop="clear"
          @keydown.enter.stop="clear"
          @keydown.space.prevent.stop="clear"
        >
          <X class="w-3.5 h-3.5" />
        </span>

        <!-- Chevron — rotates when open -->
        <ChevronDown
          class="w-4 h-4 text-muted-foreground transition-transform duration-150"
          :class="{ 'rotate-180': open }"
        />
      </span>
    </button>

    <!-- Dropdown panel -->
    <div
      v-show="open"
      class="absolute left-0 top-full mt-1 w-full z-50 rounded-md border bg-background shadow-md"
    >
      <!-- Search input (searchable=true only) -->
      <div v-if="searchable" class="p-2 border-b border-border">
        <input
          ref="searchRef"
          v-model="search"
          type="text"
          placeholder="Search..."
          class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      <!-- Item list -->
      <ul class="max-h-60 overflow-y-auto py-1" role="listbox">
        <li
          v-if="filteredItems.length === 0"
          role="option"
          aria-disabled="true"
          class="px-3 py-2 text-sm text-muted-foreground select-none"
        >
          No results
        </li>
        <li
          v-for="item in filteredItems"
          :key="item.value"
          role="option"
          :aria-selected="isSelected(item.value)"
          class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer select-none transition-colors"
          :class="!multiple && isSelected(item.value)
            ? 'bg-primary text-primary-foreground'
            : 'hover:bg-accent hover:text-accent-foreground'"
          @click="select(item.value)"
        >
          <!-- Checkbox (multiple=true only) -->
          <input
            v-if="multiple"
            type="checkbox"
            :checked="isSelected(item.value)"
            class="accent-nord-green shrink-0"
            tabindex="-1"
            @click.stop
          />
          {{ item.label }}
        </li>
      </ul>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { X, ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: [String, Number, Array, null], default: null },
  data:        { type: Array,   default: () => [] },
  multiple:    { type: Boolean, default: false },
  searchable:  { type: Boolean, default: false },
  placeholder: { type: String,  default: 'Select...' },
  disabled:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

// ── State ──────────────────────────────────────────────────────────────────
const open      = ref(false)
const search    = ref('')
const rootRef   = ref(null)
const searchRef = ref(null)

// Close on outside click
onClickOutside(rootRef, () => { open.value = false })

// Reset search + auto-focus search input on open
watch(open, (val) => {
  if (val) {
    search.value = ''
    if (props.searchable) {
      nextTick(() => searchRef.value?.focus())
    }
  }
})

// ── Derived ────────────────────────────────────────────────────────────────
const hasSelection = computed(() => {
  if (props.multiple) return Array.isArray(props.modelValue) && props.modelValue.length > 0
  return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const displayLabel = computed(() => {
  if (!hasSelection.value) return props.placeholder
  if (props.multiple) {
    const n = props.modelValue.length
    return n === 1 ? '1 selected' : `${n} selected`
  }
  return props.data.find(i => i.value === props.modelValue)?.label ?? props.placeholder
})

const filteredItems = computed(() => {
  if (!props.searchable || !search.value) return props.data
  const q = search.value.toLowerCase()
  return props.data.filter(i => i.label.toLowerCase().includes(q))
})

// ── Actions ────────────────────────────────────────────────────────────────
function isSelected(value) {
  if (props.multiple) return Array.isArray(props.modelValue) && props.modelValue.includes(value)
  return props.modelValue === value
}

function select(value) {
  if (props.multiple) {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const idx = current.indexOf(value)
    if (idx === -1) current.push(value)
    else current.splice(idx, 1)
    emit('update:modelValue', current)
  } else {
    emit('update:modelValue', value)
    open.value = false
  }
}

function clear(event) {
  event?.stopPropagation()
  emit('update:modelValue', props.multiple ? [] : null)
}

function toggle() {
  if (!props.disabled) open.value = !open.value
}
</script>
