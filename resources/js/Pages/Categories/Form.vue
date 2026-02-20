<template>
  <AppLayout :title="isEditing ? 'Edit Category' : 'New Category'">
    <Head :title="isEditing ? 'Edit Category' : 'New Category'" />

    <form @submit.prevent="submit" class="max-w-xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('categories.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit category' : 'New category' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">{{ isEditing ? category.name : 'Add a new content category' }}</p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="e.g. Technology"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
            autofocus
          />
          <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
        </div>

        <div class="space-y-1">
          <label for="description" class="text-sm font-medium">Description <span class="text-muted-foreground font-normal">(optional)</span></label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            placeholder="A brief description of this category..."
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
            :class="{ 'border-destructive': form.errors.description }"
          />
          <div class="flex justify-between">
            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
            <p v-else class="text-xs text-muted-foreground ml-auto">{{ (form.description ?? '').length }}/500</p>
          </div>
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a :href="route('categories.index')" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create category' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  category: { type: Object, default: null },
});

const isEditing = computed(() => !!props.category);

const form = useForm({
  name:        props.category?.name        ?? "",
  description: props.category?.description ?? "",
});

function submit() {
  if (isEditing.value) {
    form.put(route("categories.update", props.category.id));
  } else {
    form.post(route("categories.store"));
  }
}
</script>
