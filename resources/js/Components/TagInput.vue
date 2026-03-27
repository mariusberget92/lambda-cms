<script setup>
import { ref, computed } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { X } from 'lucide-vue-next'

const props = defineProps({
  tags:         { type: Array, default: () => [] }, // [{ id, name }]
  modelValue:   { type: Array, default: () => [] }, // selected tag_ids
  newTagNames:  { type: Array, default: () => [] }, // new tag name strings
})

const emit = defineEmits(['update:modelValue', 'update:newTagNames'])

const query    = ref('')
const open     = ref(false)
const inputRef = ref(null)
const container = ref(null)

onClickOutside(container, () => { open.value = false })

// Tags not yet selected (by ID)
const filteredTags = computed(() => {
  const q = query.value.trim().toLowerCase()
  return props.tags.filter(t =>
    !props.modelValue.includes(t.id) &&
    (!q || t.name.toLowerCase().includes(q))
  )
})

// Show "+ Create" row when query doesn't exactly match any existing tag name
const showCreate = computed(() => {
  const q = query.value.trim()
  if (!q) return false
  const alreadyNew = props.newTagNames.some(n => n.toLowerCase() === q.toLowerCase())
  const exactMatch = props.tags.some(t => t.name.toLowerCase() === q.toLowerCase())
  return !alreadyNew && !exactMatch
})

function selectExisting(tag) {
  emit('update:modelValue', [...props.modelValue, tag.id])
  query.value = ''
  inputRef.value?.focus()
}

function createNew() {
  const name = query.value.trim()
  if (!name) return
  emit('update:newTagNames', [...props.newTagNames, name])
  query.value = ''
  inputRef.value?.focus()
}

function removeExisting(id) {
  emit('update:modelValue', props.modelValue.filter(i => i !== id))
}

function removeNew(name) {
  emit('update:newTagNames', props.newTagNames.filter(n => n !== name))
}

function onKeydown(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    // If there's exactly one filtered result and no create row, select it
    if (filteredTags.value.length === 1 && !showCreate.value) {
      selectExisting(filteredTags.value[0])
    } else if (showCreate.value) {
      createNew()
    }
  }
  if (e.key === 'Escape') {
    open.value = false
  }
  if (e.key === 'Backspace' && query.value === '') {
    // Remove last pill (new tags first, then existing)
    if (props.newTagNames.length) {
      removeNew(props.newTagNames[props.newTagNames.length - 1])
    } else if (props.modelValue.length) {
      removeExisting(props.modelValue[props.modelValue.length - 1])
    }
  }
}

// Tag name lookup helper for existing tags
function tagName(id) {
  return props.tags.find(t => t.id === id)?.name ?? ''
}
</script>

<template>
  <div ref="container" class="relative">
    <!-- Input area -->
    <div
      class="flex flex-wrap gap-1 items-center rounded-md border border-border bg-background px-2 py-1.5 focus-within:ring-2 focus-within:ring-ring cursor-text min-h-[2.25rem]"
      @click="inputRef?.focus(); open = true"
    >
      <!-- Existing tag pills -->
      <span
        v-for="id in modelValue"
        :key="'e-' + id"
        class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20"
      >
        {{ tagName(id) }}
        <button type="button" :aria-label="`Remove ${tagName(id)}`" class="hover:text-destructive transition-colors" @click.stop="removeExisting(id)">
          <X class="w-3 h-3" />
        </button>
      </span>

      <!-- New tag pills -->
      <span
        v-for="name in newTagNames"
        :key="'n-' + name"
        class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-accent/20 text-foreground border border-border"
      >
        +{{ name }}
        <button type="button" :aria-label="`Remove ${name}`" class="hover:text-destructive transition-colors" @click.stop="removeNew(name)">
          <X class="w-3 h-3" />
        </button>
      </span>

      <!-- Text input -->
      <input
        ref="inputRef"
        v-model="query"
        type="text"
        placeholder="Search or add tags..."
        role="combobox"
        aria-haspopup="listbox"
        :aria-expanded="open"
        aria-autocomplete="list"
        class="flex-1 min-w-[8rem] bg-transparent text-sm outline-none placeholder:text-muted-foreground"
        @focus="open = true"
        @keydown="onKeydown"
        @input="open = true"
      />
    </div>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open && (filteredTags.length > 0 || showCreate)"
        role="listbox"
        class="absolute left-0 top-full z-50 mt-1 w-full max-h-48 origin-top overflow-y-auto rounded-md border border-border bg-card shadow-lg"
      >
        <button
          v-for="tag in filteredTags"
          :key="tag.id"
          type="button"
          role="option"
          aria-selected="false"
          class="w-full text-left px-3 py-1.5 text-sm hover:bg-accent/20 transition-colors"
          @mousedown.prevent="selectExisting(tag)"
        >
          {{ tag.name }}
        </button>
        <button
          v-if="showCreate"
          type="button"
          class="w-full text-left px-3 py-1.5 text-sm text-muted-foreground hover:bg-accent/20 hover:text-foreground transition-colors border-t border-border"
          @mousedown.prevent="createNew"
        >
          + Create "{{ query.trim() }}"
        </button>
      </div>
    </Transition>
  </div>
</template>
