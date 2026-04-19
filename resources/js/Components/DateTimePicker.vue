<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import {
  CalendarRoot, CalendarHeader, CalendarHeading,
  CalendarGrid, CalendarGridHead, CalendarGridBody,
  CalendarGridRow, CalendarHeadCell, CalendarCell, CalendarCellTrigger,
  CalendarNext, CalendarPrev,
} from 'reka-ui'
import { CalendarDate, today, getLocalTimeZone } from '@internationalized/date'

const props = defineProps({
  modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const open  = ref(false)
const container = ref(null)
onClickOutside(container, () => { open.value = false })

// Internal state
const selectedCalDate = ref(undefined) // CalendarDate | undefined
const hours   = ref(12)   // 1–12
const minutes = ref(0)    // 0–59
const period  = ref('AM') // 'AM' | 'PM'

// Suppress the emit watcher while we are parsing an incoming modelValue
const parsing = ref(false)

// Parse incoming modelValue → internal state
watch(() => props.modelValue, (val) => {
  parsing.value = true
  if (!val) { selectedCalDate.value = undefined; parsing.value = false; return }
  const [datePart, timePart] = val.split('T')
  if (!datePart) { parsing.value = false; return }
  const [y, m, d] = datePart.split('-').map(Number)
  selectedCalDate.value = new CalendarDate(y, m, d)
  if (timePart) {
    const [h, min] = timePart.split(':').map(Number)
    period.value  = h >= 12 ? 'PM' : 'AM'
    hours.value   = h === 0 ? 12 : h > 12 ? h - 12 : h
    minutes.value = min
  }
  nextTick(() => { parsing.value = false })
}, { immediate: true })

// Emit combined YYYY-MM-DDTHH:mm whenever anything changes
watch([selectedCalDate, hours, minutes, period], () => {
  if (!selectedCalDate.value || parsing.value) return
  let h = hours.value
  if (period.value === 'AM' && h === 12) h = 0
  if (period.value === 'PM' && h !== 12) h += 12
  const pad = (n) => String(n).padStart(2, '0')
  const { year, month, day } = selectedCalDate.value
  emit('update:modelValue', `${year}-${pad(month)}-${pad(day)}T${pad(h)}:${pad(minutes.value)}`)
})

// Minimum selectable date = today
const minDate = computed(() => today(getLocalTimeZone()))

// When user picks today, ensure time is not in the past
function onDateSelect(val) {
  if (!val) return
  const t = today(getLocalTimeZone())
  const isToday = val.year === t.year && val.month === t.month && val.day === t.day
  if (!isToday) return
  const now = new Date()
  let h24 = hours.value
  if (period.value === 'AM' && h24 === 12) h24 = 0
  if (period.value === 'PM' && h24 !== 12) h24 += 12
  if (h24 < now.getHours() || (h24 === now.getHours() && minutes.value <= now.getMinutes())) {
    const next = new Date(now.getTime() + 60 * 60 * 1000)
    const nh = next.getHours()
    period.value  = nh >= 12 ? 'PM' : 'AM'
    hours.value   = nh === 0 ? 12 : nh > 12 ? nh - 12 : nh
    minutes.value = 0
  }
}

// Trigger display string
const displayValue = computed(() => {
  if (!selectedCalDate.value) return ''
  const { year, month, day } = selectedCalDate.value
  const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
  const pad = (n) => String(n).padStart(2, '0')
  return `${names[month - 1]} ${day}, ${year} · ${hours.value}:${pad(minutes.value)} ${period.value}`
})

function clampHours(e) {
  hours.value = Math.max(1, Math.min(12, parseInt(e.target.value) || 1))
}
function clampMinutes(e) {
  minutes.value = Math.max(0, Math.min(59, parseInt(e.target.value) || 0))
}
</script>

<template>
  <div ref="container" class="relative" @keydown.escape="open = false">
    <!-- Trigger -->
    <button
      type="button"
      class="w-full flex items-center gap-2 rounded-md border border-border bg-background px-3 py-1.5 text-sm text-left focus:outline-none focus:ring-2 focus:ring-ring"
      aria-haspopup="dialog"
      :aria-expanded="open"
      :aria-label="displayValue || 'Pick a date and time'"
      @click="open = !open"
    >
      <CalendarDays class="w-4 h-4 text-muted-foreground shrink-0" />
      <span :class="displayValue ? 'text-foreground' : 'text-muted-foreground'">
        {{ displayValue || 'Pick a date and time' }}
      </span>
    </button>

    <!-- Popover -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-show="open"
        class="absolute left-0 top-full z-50 mt-1 w-72 rounded-lg border border-border bg-card p-3 shadow-lg dark:shadow-black/40"
      >
        <!-- reka-ui Calendar -->
        <CalendarRoot
          v-model="selectedCalDate"
          :min-value="minDate"
          :week-starts-on="0"
          v-slot="{ weekDays, grid }"
          @update:model-value="onDateSelect"
        >
          <div v-for="month in grid" :key="month.value.toString()">
            <!-- Month header -->
            <CalendarHeader class="flex items-center justify-between mb-2">
              <CalendarPrev
                class="p-1 rounded hover:bg-accent/20 text-muted-foreground hover:text-foreground disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
              >
                <ChevronLeft class="w-4 h-4" />
              </CalendarPrev>
              <CalendarHeading class="text-sm font-medium" />
              <CalendarNext
                class="p-1 rounded hover:bg-accent/20 text-muted-foreground hover:text-foreground transition-colors"
              >
                <ChevronRight class="w-4 h-4" />
              </CalendarNext>
            </CalendarHeader>

            <!-- Day grid -->
            <CalendarGrid class="w-full">
              <CalendarGridHead>
                <CalendarGridRow class="grid grid-cols-7 mb-1">
                  <CalendarHeadCell
                    v-for="day in weekDays"
                    :key="day"
                    class="text-xs text-muted-foreground text-center font-normal pb-1"
                  >{{ day }}</CalendarHeadCell>
                </CalendarGridRow>
              </CalendarGridHead>
              <CalendarGridBody>
                <CalendarGridRow
                  v-for="(week, wi) in month.rows"
                  :key="wi"
                  class="grid grid-cols-7"
                >
                  <CalendarCell
                    v-for="date in week"
                    :key="date.toString()"
                    :date="date"
                    class="p-0"
                  >
                    <CalendarCellTrigger
                      :day="date"
                      :month="month.value"
                      v-slot="{ dayValue, selected, today: isToday, disabled, outsideView }"
                      class="w-full"
                    >
                      <div
                        class="w-8 h-8 mx-auto flex items-center justify-center rounded-md text-sm transition-colors"
                        :class="{
                          'bg-primary text-primary-foreground': selected,
                          'border border-primary text-primary font-medium': isToday && !selected,
                          'opacity-30 pointer-events-none': disabled,
                          'text-muted-foreground/40': outsideView,
                          'hover:bg-accent/20 cursor-pointer': !disabled && !selected,
                        }"
                      >{{ dayValue }}</div>
                    </CalendarCellTrigger>
                  </CalendarCell>
                </CalendarGridRow>
              </CalendarGridBody>
            </CalendarGrid>
          </div>
        </CalendarRoot>

        <!-- Time row -->
        <div class="border-t border-border mt-3 pt-3 flex items-center gap-2">
          <span class="text-xs text-muted-foreground">Time</span>
          <input
            type="number"
            :value="hours"
            min="1"
            max="12"
            class="w-10 text-center rounded border border-border bg-background text-sm px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-ring [appearance:textfield]"
            @input="clampHours"
          />
          <span class="text-muted-foreground font-medium">:</span>
          <input
            type="number"
            :value="String(minutes).padStart(2, '0')"
            min="0"
            max="59"
            class="w-10 text-center rounded border border-border bg-background text-sm px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-ring [appearance:textfield]"
            @input="clampMinutes"
          />
          <div class="ml-auto flex rounded border border-border overflow-hidden text-xs">
            <button
              type="button"
              class="px-2 py-1 transition-colors"
              :class="period === 'AM' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent/20'"
              @click="period = 'AM'"
            >AM</button>
            <button
              type="button"
              class="px-2 py-1 transition-colors border-l border-border"
              :class="period === 'PM' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent/20'"
              @click="period = 'PM'"
            >PM</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
