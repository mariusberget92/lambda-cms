<template>
  <AppLayout title="Edit Post">
    <Head title="Edit Post" />

    <form @submit.prevent="submit">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a
            :href="route('posts.index')"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <h2 class="text-lg font-semibold">Edit post</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1 max-w-xs">{{ post.title }}</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            @click="form.status = 'draft'; submit()"
            :disabled="form.processing"
            class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-50"
          >
            Save as draft
          </button>
          <button
            type="button"
            @click="form.status = 'published'; submit()"
            :disabled="form.processing"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : form.status === 'published' ? 'Update' : 'Publish' }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Title -->
          <div>
            <input
              v-model="form.title"
              type="text"
              placeholder="Post title..."
              class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
          </div>

          <!-- Excerpt -->
          <div>
            <textarea
              v-model="form.excerpt"
              placeholder="Short excerpt (optional)..."
              rows="2"
              class="w-full rounded-lg border bg-background px-4 py-2.5 text-sm placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring resize-none"
              :class="{ 'border-destructive': form.errors.excerpt }"
            />
            <div class="flex justify-between mt-1">
              <p v-if="form.errors.excerpt" class="text-xs text-destructive">{{ form.errors.excerpt }}</p>
              <p v-else class="text-xs text-muted-foreground ml-auto">{{ (form.excerpt ?? '').length }}/500</p>
            </div>
          </div>

          <!-- Editor -->
          <div>
            <TiptapEditor v-model="form.body" />
            <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Status -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
                <div>
                  <span class="text-sm font-medium">Draft</span>
                  <p class="text-xs text-muted-foreground">Only visible to you</p>
                </div>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" />
                <div>
                  <span class="text-sm font-medium">Published</span>
                  <p class="text-xs text-muted-foreground">Visible to everyone</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Category -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Category</h3>
            <select
              v-model="form.category_id"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option :value="null">— None —</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <p v-if="categories.length === 0" class="mt-2 text-xs text-muted-foreground">
              No categories yet.
              <a :href="route('categories.create')" class="underline hover:text-foreground">Create one</a>
            </p>
          </div>

          <!-- Tags -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Tags</h3>
            <div v-if="tags.length === 0" class="text-xs text-muted-foreground">
              No tags yet.
              <a :href="route('tags.create')" class="underline hover:text-foreground">Create one</a>
            </div>
            <div v-else class="flex flex-wrap gap-2">
              <label
                v-for="tag in tags"
                :key="tag.id"
                class="flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="tag.id"
                  v-model="form.tag_ids"
                  class="accent-primary rounded"
                />
                <span
                  class="text-xs px-2 py-0.5 rounded-full border transition-colors"
                  :class="form.tag_ids.includes(tag.id)
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'text-muted-foreground border-border hover:border-foreground'"
                >
                  {{ tag.name }}
                </span>
              </label>
            </div>
          </div>

          <!-- Details -->
          <div class="rounded-lg border bg-card p-4 text-sm space-y-1.5">
            <h3 class="font-medium mb-2">Details</h3>
            <div class="flex justify-between text-muted-foreground">
              <span>Slug</span>
              <span class="font-mono text-xs truncate max-w-[10rem]">{{ post.slug }}</span>
            </div>
            <div v-if="post.published_at" class="flex justify-between text-muted-foreground">
              <span>Published</span>
              <span>{{ post.published_at }}</span>
            </div>
          </div>
        </div>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import TiptapEditor from "@/Components/TiptapEditor.vue";

const props = defineProps({
  post:       Object,
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
});

const form = useForm({
  title:       props.post.title,
  excerpt:     props.post.excerpt ?? "",
  body:        props.post.body ?? "",
  status:      props.post.status,
  category_id: props.post.category_id ?? null,
  tag_ids:     props.post.tag_ids ?? [],
});

function submit() {
  form.put(route("posts.update", props.post.id));
}
</script>
