<script setup>
import { ref, computed } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { X } from 'lucide-vue-next'

const props = defineProps({
  categories: { type: Array, default: () => [] }, // [{ id, name }]
  modelValue: { type: Array, default: () => [] }, // selected category_ids
})

const emit = defineEmits(['update:modelValue'])

const query     = ref('')
const open      = ref(false)
const inputRef  = ref(null)
const container = ref(null)

onClickOutside(container, () => { open.value = false })

const filteredCategories = computed(() => {
  const q = query.value.trim().toLowerCase()
  return props.categories.filter(c =>
    !props.modelValue.includes(c.id) &&
    (!q || c.name.toLowerCase().includes(q))
  )
})

function select(cat) {
  emit('update:modelValue', [...props.modelValue, cat.id])
  query.value = ''
  inputRef.value?.focus()
}

function remove(id) {
  emit('update:modelValue', props.modelValue.filter(i => i !== id))
}

function onKeydown(e) {
  if (e.key === 'Enter') {
    e.preventDefault()
    if (filteredCategories.value.length === 1) select(filteredCategories.value[0])
  }
  if (e.key === 'Escape') open.value = false
  if (e.key === 'Backspace' && query.value === '' && props.modelValue.length) {
    remove(props.modelValue[props.modelValue.length - 1])
  }
}

function catName(id) {
  return props.categories.find(c => c.id === id)?.name ?? ''
}
</script>

<template>
  <div ref="container" class="relative">
    <div
      class="flex flex-wrap gap-1 items-center rounded-md border border-border bg-[var(--input-bg)] px-2 py-1.5 focus-within:ring-2 focus-within:ring-ring cursor-text min-h-[2.25rem]"
      @click="inputRef?.focus(); open = true"
    >
      <span
        v-for="id in modelValue"
        :key="id"
        class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20"
      >
        {{ catName(id) }}
        <button type="button" :aria-label="`Remove ${catName(id)}`" class="hover:text-destructive transition-colors" @click.stop="remove(id)">
          <X class="w-3 h-3" />
        </button>
      </span>

      <input
        ref="inputRef"
        v-model="query"
        type="text"
        placeholder="Search categories..."
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

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open && filteredCategories.length > 0"
        role="listbox"
        class="absolute left-0 top-full z-50 mt-1 w-full max-h-48 origin-top overflow-y-auto rounded-md border border-border bg-card shadow-lg"
      >
        <button
          v-for="cat in filteredCategories"
          :key="cat.id"
          type="button"
          role="option"
          aria-selected="false"
          class="w-full text-left px-3 py-1.5 text-sm hover:bg-accent/20 transition-colors"
          @mousedown.prevent="select(cat)"
        >
          {{ cat.name }}
        </button>
      </div>
    </Transition>
  </div>
</template>
