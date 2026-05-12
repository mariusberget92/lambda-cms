<!-- resources/js/Components/Blocks/FormBlock.vue -->
<template>
  <div>
    <form
      v-if="!submitted"
      :action="block.data?.action || undefined"
      :method="block.data?.method || 'POST'"
      class="space-y-4"
      @submit.prevent="handleSubmit"
    >
      <div v-for="field in block.data?.fields ?? []" :key="field.id">
        <label :for="`${block.id}-${field.id}`" class="block text-sm font-medium mb-1">
          {{ field.label }}
          <span v-if="field.required" class="text-destructive ml-0.5" aria-hidden="true">*</span>
        </label>

        <textarea
          v-if="field.type === 'textarea'"
          :id="`${block.id}-${field.id}`"
          :name="field.id"
          :placeholder="field.placeholder"
          :required="field.required"
          rows="4"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        />

        <select
          v-else-if="field.type === 'select'"
          :id="`${block.id}-${field.id}`"
          :name="field.id"
          :required="field.required"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">{{ field.placeholder || 'Select an option' }}</option>
          <option v-for="opt in (field.options ?? [])" :key="opt" :value="opt">{{ opt }}</option>
        </select>

        <div v-else-if="field.type === 'checkbox'" class="flex items-center gap-2">
          <input
            :id="`${block.id}-${field.id}`"
            type="checkbox"
            :name="field.id"
            :required="field.required"
            class="rounded border-border"
          />
          <span class="text-sm text-muted-foreground">{{ field.placeholder }}</span>
        </div>

        <input
          v-else
          :id="`${block.id}-${field.id}`"
          :type="field.type || 'text'"
          :name="field.id"
          :placeholder="field.placeholder"
          :required="field.required"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      <button
        type="submit"
        class="inline-flex items-center rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors focus:outline-none focus:ring-2 focus:ring-ring"
      >{{ block.data?.submitLabel || 'Submit' }}</button>
    </form>

    <div
      v-else
      class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-900 dark:border-green-800 dark:bg-green-950/30 dark:text-green-100"
    >
      <Icon icon="lucide:circle-check" style="font-size: 1.1rem" aria-hidden="true" />
      <p class="text-sm">{{ block.data?.successMessage || 'Thank you! Your message has been sent.' }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const submitted = ref(false)

async function handleSubmit(e) {
  const action = props.block.data?.action
  if (!action) { submitted.value = true; return }
  try {
    const form = e.target
    const data = new FormData(form)
    await fetch(action, { method: props.block.data?.method ?? 'POST', body: data })
    submitted.value = true
  } catch {
    submitted.value = true
  }
}
</script>
