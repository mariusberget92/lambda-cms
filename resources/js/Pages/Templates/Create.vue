<!-- resources/js/Pages/Templates/Create.vue -->
<script setup>
import PageBuilderLayout  from '@/Layouts/PageBuilderLayout.vue'
import TemplateBuilderBar from '@/Components/TemplateBuilderBar.vue'
import BlockEditor        from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { POST_CONTEXT_FIELDS, SOURCES } from '@/lib/loopSources.js'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { computed } from 'vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()
const authUser = usePage().props.auth.user

const props = defineProps({
  type: { type: String, required: true },
})

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
  'partial':        'Partial',
}

const typeLabel = TYPE_LABELS[props.type] ?? props.type

const form = useForm({
  title:            '',
  type:             props.type,
  loop_source:      'posts',
  status:           'draft',
  blocks:           [],
  meta_title:       '',
  meta_description: '',
  meta_keywords:    '',
})

// For single-post templates: use post context fields.
// For all others: expose the selected loop source's fields as binding targets.
const isSinglePost     = computed(() => props.type === 'single-post')
const defaultLoopSource = computed(() => isSinglePost.value ? null : form.loop_source)

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.post(route('templates.store'), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <PageBuilderLayout>
    <Head :title="`New ${typeLabel} Template`" />

    <template #bar>
      <TemplateBuilderBar
        :back-href="route('templates.index')"
        :title="form.title"
        :type-label="typeLabel"
        :loop-source="form.loop_source"
        :show-loop-source="!isSinglePost"
        :status="form.status"
        :meta-title="form.meta_title"
        :meta-description="form.meta_description"
        :meta-keywords="form.meta_keywords"
        :processing="form.processing"
        save-label="Create template"
        @update:title="form.title = $event"
        @update:loop-source="form.loop_source = $event"
        @update:status="form.status = $event"
        @update:meta-title="form.meta_title = $event"
        @update:meta-description="form.meta_description = $event"
        @update:meta-keywords="form.meta_keywords = $event"
        @save="submit"
      />
    </template>

    <BlockEditor
      fullscreen
      :model-value="form.blocks"
      :is-admin="authUser?.role === 'administrator'"
      :context-fields="isSinglePost ? POST_CONTEXT_FIELDS : []"
      :default-loop-source="defaultLoopSource"
      @update:model-value="form.blocks = $event"
    />

  </PageBuilderLayout>
</template>
