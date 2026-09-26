<template>
  <div class="px-1 pb-1">
    <!-- Kopfzeile: Monat + Navigation -->
    <div class="flex items-center justify-between mb-3 px-1">
      <div class="flex items-center space-x-1">
        <button
          type="button"
          class="h-7 w-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer border border-slate-200"
          :title="t('calendar.vorheriger_monat')"
          @click="shiftMonth(-1)"
        >
          <ChevronLeft class="w-4 h-4" />
        </button>
        <button
          type="button"
          class="h-7 w-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer border border-slate-200"
          :title="t('calendar.naechster_monat')"
          @click="shiftMonth(1)"
        >
          <ChevronRight class="w-4 h-4" />
        </button>
        <button
          v-if="viewYear !== today.getFullYear() || viewMonth !== (today.getMonth() + 1)"
          type="button"
          class="px-2 h-7 text-[11px] font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition cursor-pointer border border-slate-200"
          @click="goToday"
        >
          {{ $t('mini_cal.heute') }}
        </button>
      </div>

      <NuxtLink
        to="/calendar"
        class="text-xs font-bold text-slate-800 hover:text-[#00A3C4] transition px-1"
        :title="$t('mini_cal.kalender_oeffnen')"
      >
        {{ monthLabel }}
      </NuxtLink>
    </div>

    <!-- Wochentage -->
    <div class="grid grid-cols-7 gap-1 mb-1.5">
      <div
        v-for="d in weekdays"
        :key="d"
        class="text-center text-[10px] font-bold text-slate-400 uppercase py-0.5"
      >
        {{ d }}
      </div>
    </div>

    <!-- Tage Grid -->
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="(cell, i) in cells"
        :key="i"
        type="button"
        :disabled="!cell.day"
        class="relative h-8 rounded-lg text-xs font-semibold transition flex flex-col items-center justify-center cursor-pointer disabled:cursor-default"
        :class="cellClass(cell)"
        :title="cell.day ? tooltipFor(cell) : ''"
        @click="cell.day && selectDay(cell)"
      >
        <span v-if="cell.day">{{ cell.day }}</span>

        <!-- Termin-Punkt -->
        <span
          v-if="cell.day && cell.count > 0"
          class="absolute bottom-1 w-1.5 h-1.5 rounded-full"
          :class="cell.overdue > 0 ? 'bg-rose-500' : (cell.key === selectedDate ? 'bg-white' : 'bg-[#00A3C4]')"
        />
      </button>
    </div>

    <!-- Ausgewählter Tag: Terminliste -->
    <div v-if="selectedDate && selectedItems.length > 0" class="mt-4 pt-3 border-t border-slate-200">
      <div class="flex items-center justify-between mb-2 px-1">
        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">
          {{ selectedLabel }}
        </span>
        <button
          type="button"
          class="text-slate-400 hover:text-slate-700 p-1 hover:bg-slate-100 rounded transition cursor-pointer"
          :title="$t('mini_cal.auswahl_aufheben')"
          @click="selectedDate = null"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>

      <ul class="space-y-1.5 max-h-48 overflow-y-auto pr-0.5">
        <li v-for="item in selectedItems" :key="item.id">
          <NuxtLink
            :to="`/projects/${item.project_id}?task=${item.id}`"
            class="flex items-start gap-2.5 px-3 py-2 rounded-xl bg-slate-50 hover:bg-cyan-50/50 border border-slate-200/80 hover:border-cyan-300 transition-all group"
          >
            <span
              class="w-2 h-2 rounded-full mt-1.5 shrink-0"
              :class="item.overdue ? 'bg-rose-500' : statusDot(item.status)"
            />
            <div class="min-w-0 flex-1">
              <span
                class="block text-xs font-bold truncate transition-colors"
                :class="item.overdue ? 'text-rose-700' : 'text-slate-900 group-hover:text-[#00A3C4]'"
              >
                {{ item.title }}
              </span>
              <span class="block text-[11px] text-slate-500 truncate mt-0.5">
                {{ item.project_title }}
              </span>
            </div>
          </NuxtLink>
        </li>
      </ul>
    </div>

    <!-- Ausgewählter Tag: Keine Termine -->
    <div v-else-if="selectedDate" class="mt-4 pt-3 border-t border-slate-200 px-1">
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">{{ selectedLabel }} – {{ $t('mini_cal.keine_termine') }}</span>
        <button type="button" class="text-slate-400 hover:text-slate-700 p-1 hover:bg-slate-100 rounded transition cursor-pointer" @click="selectedDate = null">
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Standard (kein Tag gewählt): Nächste Fristen & Termine -->
    <div v-else class="mt-4 pt-3 border-t border-slate-200">
      <div class="flex items-center justify-between mb-2 px-1">
        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">
          {{ $t('mini_cal.naechste_faelligkeiten') }}
        </span>
        <span v-if="upcomingItems.length > 0" class="text-[10px] text-slate-500 font-semibold px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200">
          {{ upcomingItems.length }}
        </span>
      </div>

      <div v-if="loading" class="py-4 text-center text-xs text-slate-400 font-medium">
        ...
      </div>

      <ul v-else-if="upcomingItems.length > 0" class="space-y-1.5 max-h-48 overflow-y-auto pr-0.5">
        <li v-for="item in upcomingItems" :key="item.id">
          <NuxtLink
            :to="`/projects/${item.project_id}?task=${item.id}`"
            class="flex items-start gap-2.5 px-3 py-2 rounded-xl bg-slate-50 hover:bg-cyan-50/50 border border-slate-200/80 hover:border-cyan-300 transition-all group"
          >
            <span
              class="w-2 h-2 rounded-full mt-1.5 shrink-0"
              :class="item.overdue ? 'bg-rose-500 animate-pulse' : 'bg-[#00A3C4]'"
            />
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-1.5">
                <span
                  class="block text-xs font-bold truncate transition-colors"
                  :class="item.overdue ? 'text-rose-700' : 'text-slate-900 group-hover:text-[#00A3C4]'"
                >
                  {{ item.title }}
                </span>
                <span
                  class="text-[10px] font-semibold px-1.5 py-0.5 rounded shrink-0 border"
                  :class="item.overdue ? 'bg-rose-100 text-rose-800 border-rose-200' : (item.dateKey === todayKey ? 'bg-amber-100 text-amber-900 border-amber-300 font-bold' : 'bg-white text-slate-600 border-slate-200')"
                >
                  {{ formatItemDueDate(item.dateKey, item.overdue) }}
                </span>
              </div>
              <span class="block text-[11px] text-slate-500 truncate mt-0.5">
                {{ item.project_title }}
              </span>
            </div>
          </NuxtLink>
        </li>
      </ul>

      <div v-else class="py-3 px-2 text-center text-xs text-slate-400 font-medium">
        {{ $t('mini_cal.keine_anstehenden_fristen') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next'

const { authHeaders } = useAuth()
const { t, locale } = useI18n()

const weekdays = computed(() => {
  if (locale.value === 'sk') return ['Po', 'Ut', 'St', 'Št', 'Pi', 'So', 'Ne']
  if (locale.value === 'en') return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  return ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']
})

const today = new Date()
const viewYear = ref(today.getFullYear())
const viewMonth = ref(today.getMonth() + 1) // 1-12
const days = ref<Record<string, any>>({})
const selectedDate = ref<string | null>(null)
const loading = ref(false)

const pad = (n: number) => String(n).padStart(2, '0')
const todayKey = `${today.getFullYear()}-${pad(today.getMonth() + 1)}-${pad(today.getDate())}`

const currentLocaleCode = computed(() => {
  if (locale.value === 'sk') return 'sk-SK'
  if (locale.value === 'en') return 'en-US'
  return 'de-CH'
})

const monthLabel = computed(() => {
  const d = new Date(viewYear.value, viewMonth.value - 1, 1)
  return d.toLocaleDateString(currentLocaleCode.value, { month: 'long', year: 'numeric' })
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
  if (!cell.day) return 'text-transparent cursor-default select-none'
  const isToday = cell.key === todayKey
  const isSelected = cell.key === selectedDate.value

  if (isSelected) return 'bg-[#00A3C4] text-white font-bold shadow-xs'
  if (isToday) return 'bg-cyan-50 text-[#00A3C4] font-bold ring-1 ring-[#00A3C4]/40 hover:bg-cyan-100/60'
  if (cell.count > 0) return 'text-slate-900 font-bold hover:bg-slate-100'
  return 'text-slate-500 hover:bg-slate-100 font-medium'
}

const statusDot = (status: string) => {
  if (status === 'done') return 'bg-emerald-500'
  if (status === 'in_progress') return 'bg-[#00A3C4]'
  if (status === 'review') return 'bg-amber-500'
  return 'bg-slate-400'
}

const tooltipFor = (cell: any) => {
  if (cell.count === 0) return ''
  const parts = [`${cell.count} ${cell.count === 1 ? t('calendar.termin_an_diesem_tag') || 'Termin' : t('calendar.termin_eintraege') || 'Termine'}`]
  if (cell.overdue > 0) parts.push(`${cell.overdue} ${t('mini_cal.ueberfaellig') || 'überfällig'}`)
  return parts.join(' · ')
}

const selectedItems = computed(() => {
  if (!selectedDate.value) return []
  return days.value[selectedDate.value]?.items || []
})

const selectedLabel = computed(() => {
  if (!selectedDate.value) return ''
  const d = new Date(selectedDate.value + 'T00:00:00')
  return d.toLocaleDateString(currentLocaleCode.value, { day: '2-digit', month: 'long' })
})

const upcomingItems = computed(() => {
  const allItems: any[] = []
  for (const [dateKey, dayData] of Object.entries(days.value)) {
    if (dayData && Array.isArray(dayData.items)) {
      for (const it of dayData.items) {
        allItems.push({
          ...it,
          dateKey
        })
      }
    }
  }
  // Prioritize overdue, then chronological order
  return allItems.sort((a, b) => {
    if (a.overdue && !b.overdue) return -1
    if (!a.overdue && b.overdue) return 1
    return a.dateKey.localeCompare(b.dateKey)
  }).slice(0, 4)
})

const formatItemDueDate = (dateKey: string, isOverdue: boolean) => {
  if (dateKey === todayKey) return t('mini_cal.heute') || 'Heute'
  if (isOverdue) return t('mini_cal.ueberfaellig') || 'Überfällig'
  const d = new Date(dateKey + 'T00:00:00')
  return d.toLocaleDateString(currentLocaleCode.value, { day: 'numeric', month: 'short' })
}

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
