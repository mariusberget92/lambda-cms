<!-- resources/js/Components/Blocks/FormBlock.vue -->
<template>
  <div>
    <!-- No form selected (editor state) -->
    <div v-if="!block.data?.form_id" class="py-6 text-center text-sm text-muted-foreground italic border border-dashed rounded-lg border-border/50">
      No form selected
    </div>

    <!-- Loading -->
    <div v-else-if="loading" class="py-8 flex justify-center">
      <div class="w-5 h-5 rounded-full border-2 border-border border-t-primary animate-spin" />
    </div>

    <!-- Form not found -->
    <div v-else-if="!formData" class="py-4 text-sm text-muted-foreground italic">
      Form not found.
    </div>

    <!-- Success state -->
    <div v-else-if="submitted" class="rounded-lg border border-border bg-card p-6 text-center">
      <svg class="w-10 h-10 mx-auto mb-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-base font-medium">{{ successMessage }}</p>
    </div>

    <!-- Form -->
    <form v-else @submit.prevent="submit" novalidate class="space-y-4">
      <div class="flex flex-wrap gap-4">
        <div
          v-for="field in formData.fields"
          :key="field.id"
          :class="field.width === 'half' ? 'w-[calc(50%-0.5rem)]' : 'w-full'"
        >
          <label v-if="field.type !== 'hidden' && field.type !== 'checkbox'" class="block text-sm font-medium mb-1">
            {{ field.label }}
            <span v-if="field.required" class="text-destructive ml-0.5">*</span>
          </label>

          <!-- Text / Email / Number / Phone / URL / Date -->
          <input
            v-if="['text','email','number','phone','url','date'].includes(field.type)"
            v-model="values[field.name]"
            :type="field.type === 'phone' ? 'tel' : field.type"
            :placeholder="field.placeholder || ''"
            :required="field.required"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': fieldErrors[field.name] }"
          />

          <!-- Textarea -->
          <textarea
            v-else-if="field.type === 'textarea'"
            v-model="values[field.name]"
            :placeholder="field.placeholder || ''"
            :required="field.required"
            rows="4"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-y"
            :class="{ 'border-destructive': fieldErrors[field.name] }"
          />

          <!-- Select -->
          <select
            v-else-if="field.type === 'select'"
            v-model="values[field.name]"
            :required="field.required"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': fieldErrors[field.name] }"
          >
            <option value="">{{ field.placeholder || 'Select an option' }}</option>
            <option v-for="opt in (field.options ?? [])" :key="opt" :value="opt">{{ opt }}</option>
          </select>

          <!-- Radio -->
          <div v-else-if="field.type === 'radio'" class="space-y-1.5">
            <label v-for="opt in (field.options ?? [])" :key="opt" class="flex items-center gap-2 cursor-pointer text-sm">
              <input type="radio" v-model="values[field.name]" :value="opt" :name="field.name" class="accent-primary" />
              {{ opt }}
            </label>
          </div>

          <!-- Checkboxes (multiple) -->
          <div v-else-if="field.type === 'checkboxes'" class="space-y-1.5">
            <label v-for="opt in (field.options ?? [])" :key="opt" class="flex items-center gap-2 cursor-pointer text-sm">
              <input
                type="checkbox"
                :value="opt"
                :checked="(values[field.name] ?? []).includes(opt)"
                class="accent-primary rounded"
                @change="toggleCheckbox(field.name, opt, $event.target.checked)"
              />
              {{ opt }}
            </label>
          </div>

          <!-- Single Checkbox (agreement) -->
          <label v-else-if="field.type === 'checkbox'" class="flex items-start gap-2.5 cursor-pointer text-sm">
            <input type="checkbox" v-model="values[field.name]" class="mt-0.5 accent-primary rounded" />
            <span>{{ field.label }}<span v-if="field.required" class="text-destructive ml-0.5">*</span></span>
          </label>

          <!-- Hidden -->
          <input v-else-if="field.type === 'hidden'" type="hidden" :name="field.name" :value="field.default_value" />

          <!-- Help text -->
          <p v-if="field.help_text && field.type !== 'hidden'" class="text-xs text-muted-foreground mt-1">{{ field.help_text }}</p>

          <!-- Error -->
          <p v-if="fieldErrors[field.name]" class="text-xs text-destructive mt-1">{{ fieldErrors[field.name] }}</p>
        </div>
      </div>

      <!-- Global error -->
      <p v-if="globalError" class="text-sm text-destructive">{{ globalError }}</p>

      <!-- Submit -->
      <div>
        <button
          type="submit"
          :disabled="submitting"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-60"
        >
          <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          {{ submitting ? 'Sending…' : (block.data?.submitLabel || 'Submit') }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ block: { type: Object, required: true } })

const loading     = ref(false)
const formData    = ref(null)
const values      = reactive({})
const fieldErrors = reactive({})
const globalError = ref(null)
const submitting  = ref(false)
const submitted   = ref(false)
const successMessage = ref('')

onMounted(async () => {
  const id = props.block.data?.form_id
  if (!id) return
  loading.value = true
  try {
    const { data } = await axios.get(`/api/forms/${id}`)
    formData.value = data
    // Initialise values
    for (const field of data.fields) {
      if (field.type === 'checkboxes') values[field.name] = []
      else if (field.type === 'checkbox') values[field.name] = false
      else values[field.name] = field.default_value ?? ''
    }
  } catch {
    formData.value = null
  } finally {
    loading.value = false
  }
})

function toggleCheckbox(name, opt, checked) {
  if (!Array.isArray(values[name])) values[name] = []
  if (checked) values[name] = [...values[name], opt]
  else values[name] = values[name].filter(v => v !== opt)
}

async function submit() {
  if (submitting.value) return
  submitting.value = true
  globalError.value = null
  Object.keys(fieldErrors).forEach(k => delete fieldErrors[k])

  try {
    const { data } = await axios.post(`/forms/${formData.value.slug}/submit`, values)
    submitted.value = true
    successMessage.value = data.message
  } catch (err) {
    if (err.response?.status === 422) {
      const errs = err.response.data?.errors ?? {}
      Object.assign(fieldErrors, Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      ))
    } else {
      globalError.value = 'Something went wrong. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}
</script>
