<!-- resources/js/Pages/Pages/Create.vue -->
<script setup>
import PageBuilderLayout from '@/Layouts/PageBuilderLayout.vue'
import PageBuilderBar    from '@/Components/PageBuilderBar.vue'
import BlockEditor       from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { watch } from 'vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()
const authUser   = usePage().props.auth.user

const props = defineProps({
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
})

const form = useForm({
  title:            '',
  slug:             '',
  status:           'draft',
  blocks:           [],
  meta_title:       '',
  meta_description: '',
  meta_keywords:    '',
  custom_js:        '',
})

watch(() => form.title, (val, oldVal) => {
  if (!form.slug || form.slug === slugify(oldVal)) {
    form.slug = slugify(val)
  }
})

function slugify(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.post(route('pages.store'), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <PageBuilderLayout>
    <Head title="New Page" />

    <template #bar>
      <PageBuilderBar
        :back-href="route('pages.index')"
        :title="form.title"
        :slug="form.slug"
        :status="form.status"
        :meta-title="form.meta_title"
        :meta-description="form.meta_description"
        :meta-keywords="form.meta_keywords"
        :custom-js="form.custom_js"
        :processing="form.processing"
        save-label="Save page"
        :show-revisions="false"
        @update:title="form.title = $event"
        @update:slug="form.slug = $event"
        @update:status="form.status = $event"
        @update:meta-title="form.meta_title = $event"
        @update:meta-description="form.meta_description = $event"
        @update:meta-keywords="form.meta_keywords = $event"
        @update:custom-js="form.custom_js = $event"
        @save="submit"
      />
    </template>

    <BlockEditor
      :model-value="form.blocks"
      :is-admin="authUser?.role === 'administrator'"
      :meta="{ categories: props.categories, tags: props.tags }"
      :fullscreen="true"
      @update:model-value="form.blocks = $event"
    />
  </PageBuilderLayout>
</template>
