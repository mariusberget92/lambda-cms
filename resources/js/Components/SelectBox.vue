<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { ChevronDown, X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: [String, Number, Array], default: null },
  data:        { type: Array,   default: () => [] },
  multiple:    { type: Boolean, default: false },
  searchable:  { type: Boolean, default: false },
  placeholder: { type: String,  default: 'Select...' },
  disabled:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const open        = ref(false)
const search      = ref('')
const root        = ref(null)
const searchInput = ref(null)

onClickOutside(root, () => { open.value = false })

watch(open, (val) => {
  if (val) {
    search.value = ''
    if (props.searchable) nextTick(() => searchInput.value?.focus())
  }
})

const filteredItems = computed(() => {
  if (!props.searchable || !search.value) return props.data
  const q = search.value.toLowerCase()
  return props.data.filter(item => item.label.toLowerCase().includes(q))
})

const isSelected = (value) => {
  if (props.multiple) return Array.isArray(props.modelValue) && props.modelValue.includes(value)
  return props.modelValue === value
}

const triggerLabel = computed(() => {
  if (props.multiple) {
    const count = Array.isArray(props.modelValue) ? props.modelValue.length : 0
    return count === 0 ? null : `${count} selected`
  }
  return props.data.find(item => item.value === props.modelValue)?.label ?? null
})

const hasSelection = computed(() => {
  if (props.multiple) return Array.isArray(props.modelValue) && props.modelValue.length > 0
  return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const select = (value) => {
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

const clear = () => {
  emit('update:modelValue', props.multiple ? [] : null)
}

const toggle = () => {
  if (!props.disabled) open.value = !open.value
}
</script>

<template>
  <div ref="root" class="relative" @keydown.escape="open = false">
    <!-- Trigger row — outer div wraps label button + icon buttons as siblings -->
    <div
      class="w-full flex items-center rounded-md border bg-background text-sm focus-within:ring-2 focus-within:ring-ring"
      :class="[
        open ? 'ring-2 ring-ring border-ring' : '',
        disabled ? 'opacity-50 cursor-not-allowed' : '',
      ]"
    >
      <!-- Label area — clicking this toggles the dropdown -->
      <button
        type="button"
        :disabled="disabled"
        aria-haspopup="listbox"
        :aria-expanded="open"
        class="flex-1 flex items-center px-3 py-2 text-left focus:outline-none disabled:cursor-not-allowed"
        @click="toggle"
      >
        <span :class="triggerLabel ? 'text-foreground' : 'text-muted-foreground'">
          {{ triggerLabel ?? placeholder }}
        </span>
      </button>

      <!-- Icon buttons — clear and chevron, siblings of the label button -->
      <span class="flex items-center gap-1 pr-2 shrink-0" @click="toggle">
        <button
          v-if="hasSelection"
          type="button"
          aria-label="Clear selection"
          class="text-muted-foreground hover:text-foreground focus:outline-none"
          @click="clear"
        >
          <X class="w-3.5 h-3.5" />
        </button>
        <ChevronDown
          class="w-4 h-4 text-muted-foreground transition-transform duration-150"
          :class="{ 'rotate-180': open }"
        />
      </span>
    </div>

    <!-- Dropdown panel -->
    <div
      v-show="open"
      class="absolute left-0 top-full z-50 mt-1 w-full rounded-md border bg-background shadow-md"
    >
      <!-- Search input -->
      <div v-if="searchable" class="p-2 border-b">
        <input
          ref="searchInput"
          v-model="search"
          type="text"
          placeholder="Search..."
          class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      <!-- Item list -->
      <ul role="listbox" :aria-multiselectable="multiple" class="max-h-60 overflow-y-auto py-1">
        <li
          v-if="filteredItems.length === 0"
          class="px-3 py-2 text-sm text-muted-foreground"
        >
          No results
        </li>
        <li
          v-for="item in filteredItems"
          :key="item.value"
          role="option"
          :aria-selected="isSelected(item.value)"
          class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer select-none"
          :class="isSelected(item.value) && !multiple
            ? 'bg-primary text-primary-foreground'
            : 'hover:bg-accent hover:text-accent-foreground'"
          @click="select(item.value)"
        >
          <input
            v-if="multiple"
            type="checkbox"
            :checked="isSelected(item.value)"
            class="shrink-0 accent-nord-green"
            tabindex="-1"
            @click.stop
          />
          {{ item.label }}
        </li>
      </ul>
    </div>
  </div>
</template>
