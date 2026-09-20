<template>
  <div class="px-3 pb-3">
    <!-- Kopfzeile: Monat + Navigation -->
    <div class="flex items-center justify-between mb-2 px-1">
      <button
        type="button"
        class="h-6 w-6 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
        title="Vorheriger Monat"
        @click="shiftMonth(-1)"
      >
        <ChevronLeft class="w-3.5 h-3.5" />
      </button>

      <button
        type="button"
        class="text-xs font-semibold text-slate-700 hover:text-[#0891B2] transition-colors px-1"
        title="Zum heutigen Monat"
        @click="goToday"
      >
        {{ monthLabel }}
      </button>

      <button
        type="button"
        class="h-6 w-6 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
        title="Nächster Monat"
        @click="shiftMonth(1)"
      >
        <ChevronRight class="w-3.5 h-3.5" />
      </button>
    </div>

    <!-- Wochentage -->
    <div class="grid grid-cols-7 gap-0.5 mb-1">
      <div
        v-for="d in weekdays"
        :key="d"
        class="text-center text-[10px] font-semibold text-slate-400 uppercase"
      >
        {{ d }}
      </div>
    </div>

    <!-- Tage -->
    <div class="grid grid-cols-7 gap-0.5">
      <button
        v-for="(cell, i) in cells"
        :key="i"
        type="button"
        :disabled="!cell.day"
        class="relative h-7 rounded text-[11px] font-medium transition-colors flex flex-col items-center justify-center"
        :class="cellClass(cell)"
        :title="cell.day ? tooltipFor(cell) : ''"
        @click="cell.day && selectDay(cell)"
      >
        <span v-if="cell.day">{{ cell.day }}</span>

        <!-- Termin-Punkt -->
        <span
          v-if="cell.day && cell.count > 0"
          class="absolute bottom-0.5 w-1 h-1 rounded-full"
          :class="cell.overdue > 0 ? 'bg-rose-500' : 'bg-[#0891B2]'"
        />
      </button>
    </div>

    <!-- Ausgewählter Tag: Terminliste -->
    <div v-if="selectedDate && selectedItems.length > 0" class="mt-3 pt-3 border-t border-slate-200">
      <div class="flex items-center justify-between mb-1.5 px-1">
        <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
          {{ selectedLabel }}
        </span>
        <button
          type="button"
          class="text-slate-400 hover:text-slate-700"
          title="Auswahl aufheben"
          @click="selectedDate = null"
        >
          <X class="w-3 h-3" />
        </button>
      </div>

      <ul class="space-y-1">
        <li v-for="item in selectedItems" :key="item.id">
          <NuxtLink
            :to="`/projects/${item.project_id}?task=${item.id}`"
            class="flex items-start gap-2 px-2 py-1.5 rounded-md hover:bg-slate-100 transition-colors group"
          >
            <span
              class="w-1.5 h-1.5 rounded-full mt-1.5 shrink-0"
              :class="item.overdue ? 'bg-rose-500' : statusDot(item.status)"
            />
            <span class="min-w-0 flex-1">
              <span
                class="block text-xs font-medium truncate"
                :class="item.overdue ? 'text-rose-700' : 'text-slate-700 group-hover:text-slate-900'"
              >
                {{ item.title }}
              </span>
              <span class="block text-[10px] text-slate-400 truncate">
                {{ item.project_title }}
              </span>
            </span>
          </NuxtLink>
        </li>
      </ul>
    </div>

    <!-- Leerzustand des ausgewählten Tages -->
    <div v-else-if="selectedDate" class="mt-3 pt-3 border-t border-slate-200 px-1">
      <div class="flex items-center justify-between">
        <span class="text-[11px] text-slate-400">{{ selectedLabel }} – keine Termine</span>
        <button type="button" class="text-slate-400 hover:text-slate-700" @click="selectedDate = null">
          <X class="w-3 h-3" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next'

const { authHeaders } = useAuth()

const weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']

const today = new Date()
const viewYear = ref(today.getFullYear())
const viewMonth = ref(today.getMonth() + 1) // 1-12
const days = ref<Record<string, any>>({})
const selectedDate = ref<string | null>(null)
const loading = ref(false)

const pad = (n: number) => String(n).padStart(2, '0')
const todayKey = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`

const monthLabel = computed(() => {
  const d = new Date(viewYear.value, viewMonth.value - 1, 1)
  return d.toLocaleDateString('de-CH', { month: 'long', year: 'numeric' })
})

/**
 * Baut das 7×N-Raster. Montag ist der erste Wochentag.
 * Zellen ohne Tag (Vor-/Nachmonat) bleiben leer.
 */
const cells = computed(() => {
  const firstOfMonth = new Date(viewYear.value, viewMonth.value - 1, 1)
  const daysInMonth = new Date(viewYear.value, viewMonth.value, 0).getDate()

  // Wochentag des 1. (0=So … 6=Sa) → auf Montag=0 umrechnen
  const startOffset = (firstOfMonth.getDay() + 6) % 7

  const result: any[] = []
  for (let i = 0; i < startOffset; i++) {
    result.push({ day: null })
  }
  for (let d = 1; d <= daysInMonth; d++) {
    const key = `${viewYear.value}-${pad(viewMonth.value)}-${pad(d)}`
    const info = days.value[key]
    result.push({
      day: d,
      key,
      count: info?.count || 0,
      overdue: info?.overdue || 0,
      items: info?.items || []
    })
  }
  return result
})

const cellClass = (cell: any) => {
  if (!cell.day) return 'text-transparent cursor-default'
  const isToday = cell.key === todayKey
  const isSelected = cell.key === selectedDate.value
  const isWeekend = false

  if (isSelected) return 'bg-[#0891B2] text-white font-semibold'
  if (isToday) return 'bg-cyan-50 text-[#0891B2] font-bold ring-1 ring-[#0891B2]/30'
  if (cell.count > 0) return 'text-slate-700 hover:bg-slate-100'
  return 'text-slate-500 hover:bg-slate-100'
}

const statusDot = (status: string) => ({
  'bg-emerald-500': status === 'done',
  'bg-cyan-500': status === 'in_progress',
  'bg-amber-500': status === 'review',
  'bg-slate-400': status === 'todo'
})

const tooltipFor = (cell: any) => {
  if (cell.count === 0) return ''
  const parts = [`${cell.count} Termin${cell.count === 1 ? '' : 'e'}`]
  if (cell.overdue > 0) parts.push(`${cell.overdue} überfällig`)
  return parts.join(' · ')
}

const selectedItems = computed(() => {
  if (!selectedDate.value) return []
  return days.value[selectedDate.value]?.items || []
})

const selectedLabel = computed(() => {
  if (!selectedDate.value) return ''
  const d = new Date(selectedDate.value + 'T00:00:00')
  return d.toLocaleDateString('de-CH', { day: '2-digit', month: 'long' })
})

const selectDay = (cell: any) => {
  selectedDate.value = selectedDate.value === cell.key ? null : cell.key
}

const loadMonth = async () => {
  loading.value = true
  try {
    const res = await $fetch<any>('/api/calendar', {
      headers: authHeaders(),
      params: { year: viewYear.value, month: viewMonth.value }
    })
    days.value = res.days || {}
  } catch {
    days.value = {}
  } finally {
    loading.value = false
  }
}

const shiftMonth = (delta: number) => {
  let m = viewMonth.value + delta
  let y = viewYear.value
  if (m < 1) { m = 12; y-- }
  if (m > 12) { m = 1; y++ }
  viewMonth.value = m
  viewYear.value = y
  selectedDate.value = null
  loadMonth()
}

const goToday = () => {
  viewYear.value = today.getFullYear()
  viewMonth.value = today.getMonth() + 1
  selectedDate.value = null
  loadMonth()
}

onMounted(loadMonth)
</script>
