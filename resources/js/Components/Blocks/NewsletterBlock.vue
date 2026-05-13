<template>
  <div class="rounded-xl border bg-card p-6 text-center max-w-md mx-auto">
    <h3 v-if="block.data?.heading" class="text-xl font-semibold mb-2">{{ block.data.heading }}</h3>
    <p v-if="block.data?.description" class="text-muted-foreground text-sm mb-4">{{ block.data.description }}</p>

    <div v-if="subscribed" class="text-sm text-green-600 dark:text-green-400 font-medium py-2">
      {{ block.data?.successMessage || 'Check your email to confirm your subscription!' }}
    </div>

    <form v-else @submit.prevent="handleSubmit" class="flex flex-col sm:flex-row gap-2">
      <input
        v-if="block.data?.showName"
        v-model="name"
        type="text"
        :placeholder="block.data?.namePlaceholder || 'Your name'"
        class="flex-1 rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
      />
      <input
        v-model="email"
        type="email"
        :placeholder="block.data?.emailPlaceholder || 'Your email address'"
        required
        class="flex-1 rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
      />
      <button
        type="submit"
        :disabled="loading"
        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-60 shrink-0"
      >
        {{ loading ? 'Subscribing…' : (block.data?.buttonLabel || 'Subscribe') }}
      </button>
    </form>

    <p v-if="error" class="mt-2 text-xs text-destructive">{{ error }}</p>
    <p v-if="block.data?.disclaimer" class="mt-3 text-xs text-muted-foreground">{{ block.data.disclaimer }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  block: { type: Object, required: true },
})

const email     = ref('')
const name      = ref('')
const loading   = ref(false)
const subscribed = ref(false)
const error     = ref('')

async function handleSubmit() {
  loading.value = true
  error.value   = ''

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
    const res = await fetch('/newsletter/subscribe', {
      method: 'POST',
      headers: {
        'Content-Type':  'application/json',
        'Accept':        'application/json',
        'X-CSRF-TOKEN':  csrf ?? '',
      },
      body: JSON.stringify({ email: email.value, name: name.value || undefined }),
    })

    const data = await res.json()

    if (res.ok || res.status === 422) {
      subscribed.value = true
    } else {
      error.value = data?.message ?? 'Something went wrong. Please try again.'
    }
  } catch {
    error.value = 'Network error. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>
