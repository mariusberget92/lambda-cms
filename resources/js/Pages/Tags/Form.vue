<template>
  <AppLayout :title="isEditing ? 'Edit Tag' : 'New Tag'">
    <Head :title="isEditing ? 'Edit Tag' : 'New Tag'" />

    <form @submit.prevent="submit" class="max-w-sm">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('tags.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit tag' : 'New tag' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">{{ isEditing ? tag.name : 'Add a new keyword tag' }}</p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6">
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="e.g. javascript"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
            autofocus
          />
          <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a :href="route('tags.index')" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create tag' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  tag: { type: Object, default: null },
});

const isEditing = computed(() => !!props.tag);

const form = useForm({
  name: props.tag?.name ?? "",
});

function submit() {
  if (isEditing.value) {
    form.put(route("tags.update", props.tag.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    });
  } else {
    form.post(route("tags.store"), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    });
  }
}
</script>
