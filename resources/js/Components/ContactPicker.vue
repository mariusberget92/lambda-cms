<template>
  <div ref="wrapperRef" class="relative">
    <div class="relative">
      <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
      </svg>
      <input
        v-model="query"
        type="text"
        :placeholder="placeholder"
        class="w-full rounded-md border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        @focus="open = true"
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
        v-if="open && filtered.length > 0"
        class="absolute z-20 top-full mt-1 w-full max-h-48 overflow-y-auto rounded-lg border bg-card shadow-md py-1"
      >
        <button
          v-for="contact in filtered"
          :key="contact.id"
          type="button"
          class="w-full text-left px-3 py-2 text-sm hover:bg-accent transition-colors"
          @click="select(contact)"
        >
          {{ contact.name }}
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  contacts: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Search contacts...' },
})

const emit = defineEmits(['select'])

const query = ref('')
const open = ref(false)
const wrapperRef = ref(null)

const filtered = computed(() => {
  const q = query.value.toLowerCase()
  return props.contacts.filter(c => c.name.toLowerCase().includes(q)).slice(0, 20)
})

function select(contact) {
  emit('select', contact.id)
  query.value = ''
  open.value = false
}

function handleClickOutside(e) {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))
</script>
