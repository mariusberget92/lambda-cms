<template>
  <div class="min-h-screen bg-background flex flex-col items-center justify-center gap-6">
    <div class="flex flex-col items-center gap-2 text-center">
      <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center">
        <svg class="w-6 h-6 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 7l10 4 10-4-10-4zM2 17l10 4 10-4M2 12l10 4 10-4" />
        </svg>
      </div>
      <h1 class="text-3xl font-bold tracking-tight">Lambda CMS</h1>
      <p class="text-muted-foreground text-sm max-w-xs">
        A modern content management system built with Laravel, Inertia, and Vue 3.
      </p>
    </div>

    <div class="flex gap-3">
      <a
        :href="route('login')"
        class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      >
        Sign in
      </a>
    </div>
  </div>
</template>

<script setup>
defineOptions({ layout: null });
</script>
