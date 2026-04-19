<template>
  <AppLayout :title="isEditing ? 'Edit Category' : 'New Category'">
    <Head :title="isEditing ? 'Edit Category' : 'New Category'" />

    <PageHeader :title="isEditing ? 'Edit category' : 'New category'" />

    <form @submit.prevent="submit" class="max-w-xl">

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
          <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
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
          <p v-if="form.errors.description" class="text-xs text-destructive mt-1">{{ form.errors.description }}</p>
          <div class="flex justify-between">
            <p class="text-xs text-muted-foreground ml-auto">{{ (form.description ?? '').length }}/500</p>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-sm font-medium">Color</label>
          <ColorPickerPopover v-model="form.color" />
          <p class="text-xs text-muted-foreground">Optional accent color for this category.</p>
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a :href="route('categories.index')" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
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
import PageHeader from '@/Components/PageHeader.vue'
import { useNotifications } from '@/composables/useNotifications.js'
import ColorPickerPopover from '@/Components/ColorPickerPopover.vue'

const { notify } = useNotifications()

const props = defineProps({
  category: { type: Object, default: null },
});

const isEditing = computed(() => !!props.category);

const form = useForm({
  name:        props.category?.name        ?? "",
  description: props.category?.description ?? "",
  color:       props.category?.color       ?? null,
});

function submit() {
  if (isEditing.value) {
    form.put(route("categories.update", props.category.id), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    });
  } else {
    form.post(route("categories.store"), {
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    });
  }
}
</script>
