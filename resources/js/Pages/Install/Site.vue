<script setup>
import { useForm, router } from '@inertiajs/vue3'
import InstallLayout from '@/Layouts/InstallLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'

defineOptions({ layout: InstallLayout })

const props = defineProps({
  step: Number,
  siteUrl: String,
})

const { notify } = useNotifications()

const form = useForm({
  site_name: 'Lambda CMS',
  site_url: props.siteUrl ?? '',
})

function submit() {
  form.post('/install/site', {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold mb-1">Site Configuration</h2>
    <p class="text-sm text-muted-foreground mb-6">Set your site name and URL.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1.5">Site Name</label>
        <input
          v-model="form.site_name"
          type="text"
          placeholder="My Blog"
          class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
          :class="{ 'border-destructive': form.errors.site_name }"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1.5">Site URL</label>
        <input
          v-model="form.site_url"
          type="url"
          placeholder="https://example.com"
          class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
          :class="{ 'border-destructive': form.errors.site_url }"
        />
      </div>

      <div class="flex items-center justify-between pt-2">
        <button type="button" @click="router.visit('/install/database')" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
          ← Back
        </button>
        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex items-center gap-2 bg-primary text-primary-foreground text-sm font-medium px-5 py-2 rounded-md hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
        >
          <span>Next</span>
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </form>
  </div>
</template>
