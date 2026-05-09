<!-- resources/js/Components/BlockEditor/blocks/FormSettings.vue -->
<template>
  <div class="space-y-3">
    <!-- Form selector -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Form</label>
      <select
        :value="block.data.form_id ?? ''"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { form_id: Number($event.target.value) || null } })"
      >
        <option value="">— Select a form —</option>
        <option v-for="f in forms" :key="f.id" :value="f.id">{{ f.name }}</option>
      </select>
      <div v-if="loadError" class="text-xs text-destructive mt-1">Could not load forms.</div>
    </div>

    <!-- Link to edit the selected form -->
    <div v-if="block.data.form_id" class="flex items-center gap-3">
      <a
        :href="`/forms/${block.data.form_id}/edit`"
        class="text-xs text-primary hover:underline"
        target="_blank"
      >Edit form →</a>
      <a
        :href="`/forms/${block.data.form_id}/submissions`"
        class="text-xs text-muted-foreground hover:text-foreground hover:underline"
        target="_blank"
      >View submissions →</a>
    </div>

    <hr class="border-white/8" />

    <!-- Submit button label -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Submit button label</label>
      <input
        :value="block.data.submitLabel || 'Submit'"
        type="text"
        placeholder="Submit"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { submitLabel: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const forms     = ref([])
const loadError = ref(false)

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/forms')
    forms.value = data
  } catch {
    loadError.value = true
  }
})
</script>
