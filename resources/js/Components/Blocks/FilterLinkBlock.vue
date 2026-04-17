<!-- resources/js/Components/Blocks/FilterLinkBlock.vue -->
<script setup>
import { computed, inject } from 'vue'
import { router } from '@inertiajs/vue3'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)

const paramName = computed(() => props.block.data?.paramName || 'category')
const slug      = computed(() => loopItem?.value?.slug ?? '')
const label     = useFieldBinding(() => props.block, 'label')

const filterUrl = computed(() => slug.value ? `/?${paramName.value}=${slug.value}` : '/')

const isActive = computed(() => {
  if (!slug.value) return false
  try {
    const params = new URLSearchParams(window.location.search)
    return params.get(paramName.value) === slug.value
  } catch {
    return false
  }
})

function navigate(e) {
  e.preventDefault()
  router.get(filterUrl.value, {}, { preserveScroll: true })
}
</script>

<template>
  <a
    :href="filterUrl"
    :class="[
      'transition-colors',
      isActive ? 'font-semibold text-foreground ring-2 ring-primary/40' : 'text-muted-foreground hover:text-foreground',
    ]"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>
