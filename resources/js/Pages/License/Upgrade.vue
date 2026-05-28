<template>
  <AppLayout :title="featureMeta.title">
    <Head :title="featureMeta.title" />

    <div class="flex flex-col items-center justify-center min-h-[60vh] py-16 text-center">

      <!-- Icon -->
      <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
        <Icon icon="lucide:zap" class="text-primary" width="32" height="32" />
      </div>

      <!-- Headline -->
      <h1 class="text-2xl font-bold tracking-tight mb-2">
        {{ featureMeta.title }} requires Pro
      </h1>
      <p class="text-muted-foreground max-w-sm mb-8">
        {{ featureMeta.description }}
        Upgrade to Lambda CMS Pro to unlock this and other advanced features.
      </p>

      <!-- Feature comparison cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10 w-full max-w-lg text-left">

        <div class="rounded-lg border border-border bg-card p-5">
          <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground mb-3">Free</p>
          <ul class="space-y-2 text-sm">
            <li v-for="item in freeFeatures" :key="item" class="flex items-center gap-2">
              <Icon icon="lucide:check" class="text-primary shrink-0" width="14" height="14" />
              {{ item }}
            </li>
          </ul>
        </div>

        <div class="rounded-lg border border-primary bg-primary/5 p-5 relative">
          <span class="absolute -top-2.5 right-3 rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-primary-foreground uppercase tracking-wider">Pro</span>
          <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground mb-3">Pro — everything in Free, plus</p>
          <ul class="space-y-2 text-sm">
            <li v-for="item in proFeatures" :key="item" class="flex items-center gap-2">
              <Icon icon="lucide:zap" class="text-primary shrink-0" width="14" height="14" />
              {{ item }}
            </li>
          </ul>
        </div>
      </div>

      <!-- CTA -->
      <div class="flex flex-col sm:flex-row gap-3 items-center">
        <Link
          :href="route('settings.index') + '?tab=license'"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
        >
          <Icon icon="lucide:key" width="14" height="14" />
          Enter license key
        </Link>
        <a
          href="https://lambdacms.io/pro"
          target="_blank"
          rel="noopener"
          class="inline-flex items-center gap-2 rounded-md border border-border px-5 py-2.5 text-sm font-medium text-foreground hover:bg-muted transition-colors"
        >
          Get a license
          <Icon icon="lucide:arrow-up-right" width="14" height="14" />
        </a>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  feature: {
    type: String,
    required: true,
  },
})

const FEATURE_META = {
  calendar: {
    title: 'Content Calendar',
    description: 'The content calendar lets you plan and schedule posts on a visual monthly grid.',
  },
  webhooks: {
    title: 'Webhooks',
    description: 'Webhooks notify external services in real-time when content is published, updated, or deleted.',
  },
  scheduling: {
    title: 'Scheduled Publishing',
    description: 'Schedule posts to go live automatically at a specific date and time.',
  },
}

const featureMeta = computed(() => FEATURE_META[props.feature] ?? {
  title: 'Pro Feature',
  description: 'This feature is available on Lambda CMS Pro.',
})

const freeFeatures = [
  'Unlimited posts & pages',
  'Full block editor',
  'Template system',
  'Media library',
  'Categories & tags',
  'Comments moderation',
  'REST API',
  'User management',
  'SEO settings',
]

const proFeatures = [
  'Scheduled publishing',
  'Content calendar',
  'Webhooks',
  'Custom JS injection',
  'Priority support',
]
</script>
