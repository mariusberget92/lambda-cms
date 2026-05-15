<script setup>
import { ref, computed } from 'vue'
import { ChevronDown, Check, X, Minus } from 'lucide-vue-next'

const props = defineProps({
  title:           { type: String, default: '' },
  body:            { type: String, default: '' },
  metaDescription: { type: String, default: '' },
  hasFeaturedImage:{ type: Boolean, default: false },
})

const open         = ref(false)
const focusKeyword = ref('')

// ── Helpers ───────────────────────────────────────────────────────────────────

function stripHtml(html) {
  return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
}

function kwMatches(text, kw) {
  if (!kw || !text) return 0
  const re = new RegExp(kw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi')
  return (text.match(re) ?? []).length
}

function syllables(word) {
  word = word.toLowerCase().replace(/[^a-z]/g, '')
  if (!word) return 0
  if (word.length <= 3) return 1
  word = word.replace(/e$/, '')
  const groups = word.match(/[aeiouy]+/g)
  return groups ? Math.max(1, groups.length) : 1
}

// ── Derived text ──────────────────────────────────────────────────────────────

const bodyText = computed(() => stripHtml(props.body))

const words = computed(() => bodyText.value.split(/\s+/).filter(w => w.length > 0))

const wordCount = computed(() => words.value.length)

const first100 = computed(() => words.value.slice(0, 100).join(' '))

// ── Readability (Flesch-Kincaid Reading Ease) ─────────────────────────────────

const readability = computed(() => {
  if (wordCount.value < 10) return null
  const sentenceCount = Math.max(1, (bodyText.value.match(/[.!?]+/g) ?? []).length)
  const syllableCount = words.value.reduce((n, w) => n + syllables(w), 0)
  const raw = 206.835
    - 1.015 * (wordCount.value / sentenceCount)
    - 84.6  * (syllableCount / wordCount.value)
  const score = Math.round(Math.max(0, Math.min(100, raw)))
  const label = score >= 90 ? 'Very Easy'
    : score >= 80 ? 'Easy'
    : score >= 70 ? 'Fairly Easy'
    : score >= 60 ? 'Standard'
    : score >= 50 ? 'Fairly Difficult'
    : score >= 30 ? 'Difficult'
    : 'Very Difficult'
  return { score, label, ok: score >= 60 }
})

// ── Checks ────────────────────────────────────────────────────────────────────

const checks = computed(() => {
  const kw      = focusKeyword.value.trim()
  const kwLower = kw.toLowerCase()
  const descLen = (props.metaDescription ?? '').length

  const density = kw && wordCount.value
    ? (kwMatches(bodyText.value, kw) / wordCount.value) * 100
    : null

  const base = [
    {
      key:  'title-length',
      label: `Title length (${(props.title ?? '').length} chars)`,
      pass:  (props.title ?? '').length >= 30 && (props.title ?? '').length <= 60,
      tip:   '30–60 characters recommended',
    },
    {
      key:   'word-count',
      label: `Word count (${wordCount.value})`,
      pass:  wordCount.value >= 300,
      tip:   '300+ words recommended',
    },
    {
      key:   'meta-desc',
      label: 'Meta description set',
      pass:  descLen > 0,
      tip:   'Improves how search results look',
    },
    {
      key:   'meta-desc-length',
      label: `Meta description length (${descLen} chars)`,
      pass:  descLen >= 120 && descLen <= 160,
      tip:   '120–160 characters recommended',
      skip:  descLen === 0,
    },
    {
      key:   'featured-image',
      label: 'Featured image set',
      pass:  props.hasFeaturedImage,
      tip:   'Improves social sharing previews',
    },
    {
      key:   'readability',
      label: readability.value
        ? `Readability: ${readability.value.label} (${readability.value.score}/100)`
        : 'Readability (write more to score)',
      pass:  readability.value?.ok ?? false,
      tip:   'Aim for Standard (60+) or above',
      skip:  !readability.value,
    },
  ]

  const kwChecks = [
    {
      key:     'kw-title',
      label:   'Focus keyword in title',
      pass:    !!kw && kwMatches(props.title ?? '', kw) > 0,
      tip:     'Use the keyword in your post title',
      keyword: true,
    },
    {
      key:     'kw-meta',
      label:   'Focus keyword in meta description',
      pass:    !!kw && kwMatches(props.metaDescription ?? '', kw) > 0,
      tip:     'Use the keyword in your meta description',
      keyword: true,
    },
    {
      key:     'kw-density',
      label:   density !== null ? `Keyword density (${density.toFixed(1)}%)` : 'Keyword density',
      pass:    density !== null && density >= 0.5 && density <= 3,
      tip:     '0.5%–3% density recommended',
      keyword: true,
    },
    {
      key:     'kw-intro',
      label:   'Focus keyword in first 100 words',
      pass:    !!kw && kwMatches(first100.value, kw) > 0,
      tip:     'Introduce the keyword early in your content',
      keyword: true,
    },
  ]

  return [...base.filter(c => !c.skip), ...kwChecks]
})

// ── Score ─────────────────────────────────────────────────────────────────────

const score = computed(() => {
  const applicable = checks.value.filter(c => !c.keyword || !!focusKeyword.value.trim())
  if (!applicable.length) return 0
  return Math.round(applicable.filter(c => c.pass).length / applicable.length * 100)
})

const scoreDot = computed(() =>
  score.value >= 70 ? 'bg-green-500'
  : score.value >= 40 ? 'bg-yellow-500'
  : 'bg-red-500'
)

const scoreText = computed(() =>
  score.value >= 70 ? 'text-green-600 dark:text-green-400'
  : score.value >= 40 ? 'text-yellow-600 dark:text-yellow-400'
  : 'text-red-600 dark:text-red-400'
)
</script>

<template>
  <div class="rounded-lg border bg-card">
    <!-- Toggle header -->
    <button
      type="button"
      class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium"
      @click="open = !open"
    >
      <span>SEO &amp; Readability</span>
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold tabular-nums" :class="scoreText">{{ score }}%</span>
        <div class="w-2 h-2 rounded-full shrink-0" :class="scoreDot" />
        <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" />
      </div>
    </button>

    <div v-if="open" class="border-t px-4 py-3 space-y-3">
      <!-- Focus keyword -->
      <div>
        <label class="block text-xs font-medium mb-1">Focus keyword</label>
        <input
          v-model="focusKeyword"
          type="text"
          placeholder="e.g. content management"
          class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <p class="text-[11px] text-muted-foreground mt-1">Session-only — not saved to the post</p>
      </div>

      <!-- Checklist -->
      <div class="space-y-1.5 pt-1 border-t">
        <div
          v-for="check in checks"
          :key="check.key"
          class="flex items-start gap-2"
        >
          <!-- Status icon -->
          <div class="mt-0.5 shrink-0">
            <Minus
              v-if="check.keyword && !focusKeyword.trim()"
              class="w-3.5 h-3.5 text-muted-foreground/30"
            />
            <Check v-else-if="check.pass" class="w-3.5 h-3.5 text-green-500" />
            <X v-else class="w-3.5 h-3.5 text-red-500" />
          </div>

          <!-- Label + tip -->
          <p
            class="text-xs leading-tight"
            :class="check.keyword && !focusKeyword.trim() ? 'text-muted-foreground/40' : 'text-foreground'"
          >
            {{ check.label }}
            <span
              v-if="!check.pass && !(check.keyword && !focusKeyword.trim())"
              class="text-muted-foreground"
            > — {{ check.tip }}</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
