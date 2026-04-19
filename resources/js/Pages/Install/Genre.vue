<!-- resources/js/Pages/Install/Genre.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3'
import InstallLayout from '@/Layouts/InstallLayout.vue'

defineOptions({ layout: InstallLayout })

const props = defineProps({
  step:   { type: Number, required: true },
  genres: { type: Object, required: true },
})

const form = useForm({
  genre: 'technology',
})

function submit() {
  form.post('/install/genre')
}
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold mb-1">Choose Your Blog Theme</h2>
    <p class="text-sm text-muted-foreground mb-6">
      Pick a theme and we'll populate your blog with 10 sample posts, categories,
      and tags to get you started. You can change everything later.
    </p>

    <form @submit.prevent="submit" class="space-y-6">

      <!-- Genre grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <button
          v-for="(genre, key) in genres"
          :key="key"
          type="button"
          class="relative flex flex-col items-center gap-2 rounded-xl border-2 p-4 text-center transition-all focus:outline-none"
          :class="form.genre === key
            ? 'border-primary bg-primary/5 shadow-sm'
            : 'border-border bg-background hover:border-primary/40 hover:bg-muted/30'"
          @click="form.genre = key"
        >
          <!-- Selected indicator -->
          <span
            v-if="form.genre === key"
            class="absolute top-2 right-2 w-4 h-4 rounded-full bg-primary flex items-center justify-center"
          >
            <svg class="w-2.5 h-2.5 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </span>

          <span class="text-2xl leading-none">{{ genre.emoji }}</span>
          <span class="text-xs font-medium leading-tight">{{ genre.label }}</span>
        </button>
      </div>

      <!-- Selected genre confirmation -->
      <div v-if="form.genre" class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm">
        <span class="font-medium">Selected:</span>
        {{ genres[form.genre]?.emoji }} {{ genres[form.genre]?.label }}
        <span v-if="form.genre !== 'empty'" class="text-muted-foreground ml-1">
          — 10 sample posts will be created
        </span>
        <span v-else class="text-muted-foreground ml-1">
          — Starting with a blank slate
        </span>
      </div>

      <button
        type="submit"
        :disabled="form.processing || !form.genre"
        class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors flex items-center justify-center gap-2"
      >
        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        {{ form.processing ? 'Setting up your blog…' : 'Finish Setup →' }}
      </button>

      <p class="text-xs text-center text-muted-foreground">
        This is the last step. Setup typically takes 5–10 seconds.
      </p>
    </form>
  </div>
</template>
