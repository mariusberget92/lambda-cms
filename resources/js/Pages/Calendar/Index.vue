<template>
  <AppLayout title="Calendar">
    <Head title="Calendar" />

    <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-6">

      <!-- Left: Mini calendar -->
      <div class="rounded-lg border bg-card p-4">
        <!-- Month navigation -->
        <div class="flex items-center justify-between mb-4">
          <button
            @click="prevMonth"
            class="p-1 rounded hover:bg-accent transition-colors text-muted-foreground hover:text-foreground"
            aria-label="Previous month"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <span class="text-sm font-semibold">{{ monthLabel }}</span>
          <button
            @click="nextMonth"
            class="p-1 rounded hover:bg-accent transition-colors text-muted-foreground hover:text-foreground"
            aria-label="Next month"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <!-- Day-of-week headers (Mon–Sun). Use index as key to avoid duplicate-key warnings. -->
        <div class="grid grid-cols-7 mb-1">
          <div
            v-for="(dow, i) in ['M','T','W','T','F','S','S']"
            :key="i"
            class="text-center text-[10px] font-medium text-muted-foreground py-1"
          >{{ dow }}</div>
        </div>

        <!-- Calendar grid -->
        <div class="grid grid-cols-7 gap-0.5">
          <!-- Empty cells before month start -->
          <div v-for="n in paddingDays" :key="'pad-' + n" />

          <!-- Day cells -->
          <button
            v-for="cell in dayCells"
            :key="cell.dateStr"
            @click="selectDay(cell.dateStr)"
            class="relative aspect-square flex flex-col items-center justify-center rounded text-xs transition-colors"
            :class="{
              'bg-primary text-primary-foreground font-semibold': selectedDay === cell.dateStr,
              'hover:bg-accent': selectedDay !== cell.dateStr,
            }"
          >
            {{ cell.day }}
            <!-- Colour dot -->
            <span
              v-if="cell.dotColor"
              class="absolute bottom-0.5 status-dot inline-flex rounded-full h-1.5 w-1.5"
              :class="cell.dotColor"
            />
          </button>
        </div>

        <!-- Legend -->
        <div class="flex gap-3 mt-4 justify-center">
          <span class="flex items-center gap-1 text-[10px] text-muted-foreground">
            <span class="w-2 h-2 rounded-full bg-status-success-fg inline-block"></span>Published
          </span>
          <span class="flex items-center gap-1 text-[10px] text-muted-foreground">
            <span class="w-2 h-2 rounded-full bg-status-info-fg inline-block"></span>Scheduled
          </span>
          <span class="flex items-center gap-1 text-[10px] text-muted-foreground">
            <span class="w-2 h-2 rounded-full bg-status-warning-fg inline-block"></span>Draft
          </span>
        </div>
      </div>

      <!-- Right: Detail panel -->
      <div class="space-y-6">

        <!-- Selected day posts -->
        <div class="rounded-lg border bg-card p-4">
          <h2 class="text-sm font-semibold mb-3">
            {{ selectedDay ? 'Posts for ' + formattedSelectedDay : 'Select a day' }}
          </h2>

          <div v-if="!selectedDay" class="text-sm text-muted-foreground">
            Click a day on the calendar to see its posts.
          </div>

          <div v-else-if="selectedDayPosts.length === 0" class="text-sm text-muted-foreground">
            No posts on this day.
          </div>

          <ul v-else class="space-y-2">
            <li
              v-for="post in selectedDayPosts"
              :key="post.id"
            >
              <a
                :href="route('posts.edit', post.id)"
                class="flex items-center gap-3 rounded-md p-2 hover:bg-accent transition-colors group"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate group-hover:text-foreground">{{ post.title }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ formatTime(post.published_at) }} · {{ post.author_name }}
                  </p>
                </div>
                <StatusBadge :status="post.status" class="shrink-0" />
              </a>
            </li>
          </ul>
        </div>

        <!-- Unscheduled drafts -->
        <div class="rounded-lg border bg-card p-4">
          <h2 class="text-sm font-semibold mb-3">Unscheduled drafts</h2>

          <div v-if="unscheduledDrafts.length === 0" class="text-sm text-muted-foreground">
            No unscheduled drafts.
          </div>

          <ul v-else class="space-y-2">
            <li
              v-for="post in unscheduledDrafts"
              :key="post.id"
            >
              <a
                :href="route('posts.edit', post.id)"
                class="flex items-center gap-3 rounded-md p-2 hover:bg-accent transition-colors group"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate group-hover:text-foreground">{{ post.title }}</p>
                  <p class="text-xs text-muted-foreground">{{ post.author_name }}</p>
                </div>
                <StatusBadge status="draft" class="shrink-0" />
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  month:              { type: String, required: true },
  grouped:            { type: Object, default: () => ({}) },
  unscheduled_drafts: { type: Array,  default: () => [] },
})

// ── Reactive state ────────────────────────────────────────────────────────

const currentMonth      = ref(props.month)
const grouped           = ref(props.grouped)
const unscheduledDrafts = ref(props.unscheduled_drafts)
const selectedDay       = ref(null)

// ── Month navigation ──────────────────────────────────────────────────────

async function navigateToMonth(monthStr) {
  try {
    const url = route('calendar.data') + '?month=' + monthStr
    const res  = await fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()

    currentMonth.value      = monthStr
    grouped.value           = data.grouped
    unscheduledDrafts.value = data.unscheduled_drafts
    selectedDay.value       = null
  } catch (err) {
    console.error('Failed to load calendar data:', err)
  }
}

function prevMonth() {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const d = new Date(y, m - 2, 1)
  navigateToMonth(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
}

function nextMonth() {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const d = new Date(y, m, 1)
  navigateToMonth(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
}

// ── Calendar grid ─────────────────────────────────────────────────────────

const paddingDays = computed(() => {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const firstDow = new Date(y, m - 1, 1).getDay() // 0 = Sunday
  return (firstDow + 6) % 7 // convert to Monday-first (Mon=0)
})

const dayCells = computed(() => {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const daysInMonth = new Date(y, m, 0).getDate()
  const cells = []

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr  = `${currentMonth.value}-${String(d).padStart(2, '0')}`
    const posts    = grouped.value[dateStr] || []
    cells.push({ day: d, dateStr, dotColor: dotColorForPosts(posts) })
  }
  return cells
})

function dotColorForPosts(posts) {
  if (!posts || posts.length === 0) return null
  if (posts.some(p => p.status === 'scheduled'))  return 'bg-status-info-fg'
  if (posts.some(p => p.status === 'published'))  return 'bg-status-success-fg'
  return 'bg-status-warning-fg'
}

// ── Day selection ─────────────────────────────────────────────────────────

function selectDay(dateStr) {
  selectedDay.value = dateStr
}

const selectedDayPosts = computed(() => {
  if (!selectedDay.value) return []
  return grouped.value[selectedDay.value] || []
})

const formattedSelectedDay = computed(() => {
  if (!selectedDay.value) return ''
  return new Date(selectedDay.value + 'T00:00:00').toLocaleDateString('en-US', {
    weekday: 'long', month: 'long', day: 'numeric',
  })
})

// ── Helpers ───────────────────────────────────────────────────────────────

const monthLabel = computed(() => {
  const [y, m] = currentMonth.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })
})

function formatTime(isoString) {
  if (!isoString) return ''
  return new Date(isoString).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}
</script>
