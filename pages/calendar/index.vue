<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">
    <!-- Kopfzeile -->
    <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-cyan-50 flex items-center justify-center">
          <CalendarDays class="w-5 h-5 text-[#0891B2]" />
        </div>
        <div>
          <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kalender</h1>
          <p class="text-xs text-slate-500">{{ $t('calendar.beschreibung') }}</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Ansicht -->
        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-md p-0.5">
          <button
            v-for="v in views"
            :key="v.key"
            type="button"
            class="h-8 px-3 text-xs font-semibold rounded transition-colors"
            :class="view === v.key ? 'bg-[#0891B2] text-white' : 'text-slate-600 hover:bg-slate-100'"
            @click="view = v.key"
          >
            {{ v.label }}
          </button>
        </div>

        <!-- Navigation -->
        <div class="flex items-center bg-white border border-slate-300 rounded-md">
          <button type="button" class="h-9 w-9 flex items-center justify-center text-slate-500 hover:bg-slate-100 rounded-l-md" :title="$t('common.zurueck')" @click="shift(-1)">
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button type="button" class="h-9 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100 border-x border-slate-200" @click="goToday">
            Heute
          </button>
          <button type="button" class="h-9 w-9 flex items-center justify-center text-slate-500 hover:bg-slate-100 rounded-r-md" :title="$t('calendar.weiter_title')" @click="shift(1)">
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>

        <button type="button" class="taskster_button" @click="openCreate()">
          <Plus class="w-4 h-4" />
          <span>Termin</span>
        </button>
      </div>
    </div>

    <!-- Titelzeile + Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <h2 class="text-lg font-semibold text-slate-900">{{ rangeLabel }}</h2>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Kategorie-Filter -->
        <div class="flex flex-wrap items-center gap-1.5">
          <button
            type="button"
            class="h-7 px-2.5 rounded text-xs font-medium border transition-colors"
            :class="!activeCategories.length ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'"
            @click="activeCategories = []"
          >
            Alle
          </button>
          <div
            v-for="c in categories"
            :key="c.id"
            class="group relative flex items-center"
          >
            <button
              type="button"
              class="h-7 px-2.5 rounded text-xs font-medium border transition-colors flex items-center gap-1.5"
              :class="activeCategories.includes(c.id) ? 'text-white border-transparent' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'"
              :style="activeCategories.includes(c.id) ? { backgroundColor: c.color } : {}"
              @click="toggleCategory(c.id)"
            >
              <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: activeCategories.includes(c.id) ? '#fff' : c.color }" />
              {{ c.name }}
            </button>
            <div v-if="c.owner_id === user?.id || !c.owner_id || (c.company_id === user?.company_id && user?.company_role === 'admin') || user?.is_superadmin" class="absolute -top-2 -right-2 hidden group-hover:flex space-x-0.5 bg-white border border-slate-200 rounded shadow-sm z-10 p-0.5">
              <button @click="editCategory(c)" class="p-1 text-slate-500 hover:bg-cyan-50 hover:text-cyan-600 rounded" :title="$t('common.bearbeiten')">
                ✏️
              </button>
              <button @click="deleteCategory(c)" class="p-1 text-slate-500 hover:bg-rose-50 hover:text-rose-600 rounded" :title="$t('common.loeschen')">
                ✕
              </button>
            </div>
          </div>
        </div>

        <button
          type="button"
          class="h-7 px-2.5 rounded text-xs font-medium border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 flex items-center gap-1.5"
          @click="openNewCategoryModal"
        >
          <Plus class="w-3 h-3" />
          {{ $t('calendar.neue_kategorie') }}
        </button>
      </div>
    </div>

    <!-- Ladezustand -->
    <div v-if="loading" class="bg-white border border-slate-200 rounded-lg p-16 text-center">
      <Loader2 class="w-6 h-6 text-slate-400 animate-spin mx-auto mb-3" />
      <p class="text-sm text-slate-500">{{ $t('calendar.wird_geladen') }}</p>
    </div>

    <!-- MONATSANSICHT -->
    <div v-else-if="view === 'month'" class="bg-white border border-slate-200 rounded-lg overflow-hidden">
      <!-- Wochentage -->
      <div class="grid border-b border-slate-200 bg-slate-50" :style="{ gridTemplateColumns: monthGridCols }">
        <div v-if="calSettings.show_week_numbers" class="py-2 text-center text-[10px] font-semibold text-slate-400 uppercase tracking-wide border-r border-slate-200">
          KW
        </div>
        <div
          v-for="d in weekdays"
          :key="d"
          class="py-2 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide"
        >
          {{ d }}
        </div>
      </div>

      <!-- Raster -->
      <div class="grid" :style="{ gridTemplateColumns: monthGridCols }">
        <template v-for="(cell, i) in monthCells" :key="i">
          <!-- Kalenderwoche (nur am Wochenanfang) -->
          <div
            v-if="calSettings.show_week_numbers && i % visibleWeekdayIndexes.length === 0"
            class="border-b border-r border-slate-100 bg-slate-50/60 flex items-start justify-center pt-1.5"
          >
            <span class="text-[10px] font-semibold text-slate-400">{{ monthWeekNumbers[Math.floor(i / visibleWeekdayIndexes.length)] }}</span>
          </div>

          <div
            class="min-h-[110px] border-b border-r border-slate-100 p-1.5 transition-colors relative group/cell"
            :class="[
              !cell.inMonth ? 'bg-slate-50/60' : 'bg-white',
              dragOverKey === cell.key ? 'bg-cyan-50 ring-2 ring-inset ring-[#0891B2]' : ''
            ]"
            @dragover.prevent="onDragOver(cell)"
            @dragleave="onDragLeave(cell)"
            @drop.prevent="onDrop(cell)"
            @dblclick="openCreate(cell.key)"
          >
          <!-- Tagesnummer -->
          <div class="flex items-center justify-between mb-1">
            <button
              type="button"
              class="w-6 h-6 rounded text-xs font-semibold flex items-center justify-center transition-colors"
              :class="cell.key === todayKey
                ? 'bg-[#0891B2] text-white'
                : cell.inMonth ? 'text-slate-700 hover:bg-slate-100' : 'text-slate-400'"
              @click="openCreate(cell.key)"
            >
              {{ cell.day }}
            </button>
            <button
              type="button"
              class="opacity-0 group-hover/cell:opacity-100 w-5 h-5 rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-opacity"
              :title="$t('calendar.termin_an_tag')"
              @click="openCreate(cell.key)"
            >
              <Plus class="w-3 h-3 mx-auto" />
            </button>
          </div>

          <!-- Termine -->
          <div class="space-y-0.5">
            <button
              v-for="ev in cell.events.slice(0, 3)"
              :key="ev.id"
              type="button"
              draggable="true"
              class="w-full text-left px-1.5 py-0.5 rounded text-[11px] font-medium truncate transition-opacity hover:opacity-80 cursor-grab active:cursor-grabbing"
              :class="[
                draggingId === ev.id ? 'opacity-40' : '',
                ev.my_status === 'pending' ? 'border-dashed border border-cyan-500/70 font-normal bg-cyan-50/40' : '',
                ev.my_status === 'declined' || ev.status === 'cancelled' ? 'line-through opacity-50 grayscale' : ''
              ]"
              :style="{ backgroundColor: ev.color + '22', color: ev.color, borderLeft: '3px solid ' + ev.color }"
              :title="eventTooltip(ev)"
              @dragstart="onDragStart(ev, $event)"
              @dragend="onDragEnd"
              @click.stop="openEdit(ev)"
            >
              <span v-if="ev.my_status === 'pending'" class="font-bold text-cyan-700 mr-0.5" :title="$t('calendar.nicht_beantwortet')">?</span>
              <span v-if="!ev.allDay" class="font-mono text-[10px] opacity-70">{{ timeOf(ev.start) }}</span>
              {{ ev.title }}
            </button>

            <button
              v-if="cell.events.length > 3"
              type="button"
              class="w-full text-left px-1.5 text-[10px] font-semibold text-slate-500 hover:text-slate-800"
              @click="openDayDetail(cell.key)"
            >
              +{{ cell.events.length - 3 }} weitere
            </button>
          </div>
          </div>
        </template>
      </div>
    </div>

    <!-- WOCHENANSICHT -->
    <div v-else-if="view === 'week'" class="bg-white border border-slate-200 rounded-lg overflow-hidden">
      <div class="grid border-b border-slate-200 bg-slate-50" :style="{ gridTemplateColumns: weekGridCols }">
        <div class="py-2 flex flex-col items-center justify-center">
          <span v-if="calSettings.show_week_numbers" class="text-[10px] font-semibold text-slate-400 leading-tight">KW</span>
          <span v-if="calSettings.show_week_numbers" class="text-xs font-bold text-slate-600 leading-tight">{{ currentWeekNumber }}</span>
        </div>
        <div
          v-for="d in weekDays"
          :key="d.key"
          class="py-2 text-center border-l border-slate-200"
          :class="d.isWeekend ? 'bg-slate-100/60' : ''"
        >
          <div class="text-[11px] font-semibold text-slate-500 uppercase">{{ d.weekday }}</div>
          <button
            type="button"
            class="mt-0.5 w-7 h-7 rounded text-sm font-semibold mx-auto flex items-center justify-center transition-colors"
            :class="d.key === todayKey ? 'bg-[#0891B2] text-white' : 'text-slate-700 hover:bg-slate-200'"
            @click="openCreate(d.key)"
          >
            {{ d.day }}
          </button>
        </div>
      </div>

      <!-- Ganztägige Termine (eigene Zeile wie in Outlook) -->
      <div v-if="weekAllDay.some((c: any) => c.items.length)" class="grid border-b border-slate-200 bg-slate-50/50" :style="{ gridTemplateColumns: weekGridCols }">
        <div class="py-1.5 pr-2 text-[10px] font-medium text-slate-400 text-right">ganztägig</div>
        <div v-for="c in weekAllDay" :key="c.key" class="border-l border-slate-200 p-1 space-y-0.5 min-h-[28px]">
          <button
            v-for="ev in c.items"
            :key="ev.id"
            type="button"
            draggable="true"
            class="w-full rounded px-1.5 py-0.5 text-[11px] font-semibold text-left truncate transition-opacity hover:opacity-90 cursor-grab active:cursor-grabbing"
            :class="draggingId === ev.id ? 'opacity-40' : ''"
            :style="{ backgroundColor: ev.color + '22', color: ev.color, borderLeft: '3px solid ' + ev.color }"
            :title="eventTooltip(ev)"
            @dragstart="onDragStart(ev, $event)"
            @dragend="onDragEnd"
            @click.stop="openEdit(ev)"
          >
            {{ ev.title }}
          </button>
        </div>
      </div>

      <div ref="weekGridRef" class="grid max-h-[600px] overflow-y-auto relative" :style="{ gridTemplateColumns: weekGridCols }" @scroll="onWeekGridScroll">
        <!-- Stunden-Spalte -->
        <div>
          <div
            v-for="h in hours"
            :key="h"
            class="h-14 border-b border-slate-100 text-[10px] font-medium text-right pr-2 pt-0.5"
            :class="isWorkHour(h) ? 'text-slate-500' : 'text-slate-300'"
          >
            {{ timeOf(`2000-01-01 ${String(h).padStart(2, '0')}:00:00`) }}
          </div>
        </div>

        <!-- Tagesspalten -->
        <div
          v-for="d in weekDays"
          :key="d.key"
          class="relative border-l border-slate-200"
        >
          <!-- Sticky Indikatoren für unsichtbare Termine oben/unten -->
          <div v-if="getEarlierEventsCount(d.key) > 0" class="sticky top-1 z-30 flex justify-center pointer-events-none h-0 overflow-visible">
            <button
              type="button"
              class="pointer-events-auto shadow-md bg-[#0891B2] hover:bg-[#077ca0] text-white text-xs font-bold h-7 px-2.5 rounded-full flex items-center justify-center gap-1 transition-transform hover:scale-105"
              title="Nach oben zu früheren Terminen scrollen"
              @click.stop="scrollToEarlierEvents(d.key)"
            >
              <ChevronUp class="w-3.5 h-3.5" />
              <span>+{{ getEarlierEventsCount(d.key) }}</span>
            </button>
          </div>

          <div v-if="getLaterEventsCount(d.key) > 0" class="sticky bottom-7 z-30 flex justify-center pointer-events-none h-0 overflow-visible">
            <button
              type="button"
              class="pointer-events-auto shadow-md bg-[#0891B2] hover:bg-[#077ca0] text-white text-xs font-bold h-7 px-2.5 rounded-full flex items-center justify-center gap-1 transition-transform hover:scale-105"
              title="Nach unten zu späteren Terminen scrollen"
              @click.stop="scrollToLaterEvents(d.key)"
            >
              <ChevronDown class="w-3.5 h-3.5" />
              <span>+{{ getLaterEventsCount(d.key) }}</span>
            </button>
          </div>
          <!-- Stundenraster (Klick = neuer Termin) -->
          <div
            v-for="h in hours"
            :key="h"
            class="h-14 border-b border-slate-100 hover:bg-cyan-50/40 transition-colors cursor-pointer"
            :class="[
              dragOverKey === d.key + '-' + h ? 'bg-cyan-50' : '',
              isWorkHour(h) ? 'bg-white' : 'bg-slate-50/70'
            ]"
            @click="openCreate(d.key, h)"
            @dragover.prevent="dragOverKey = d.key + '-' + h"
            @drop.prevent="onDropAt(d.key, h)"
          />

          <!-- Termine (absolut positioniert mit Kollisions-Nebeneinander-Berechnung) -->
          <button
            v-for="item in timedEventsWithLayoutForDay(d.key)"
            :key="item.event.id"
            type="button"
            draggable="true"
            class="absolute rounded px-1.5 py-0.5 text-[11px] font-medium text-left overflow-hidden transition-opacity hover:opacity-90 cursor-grab active:cursor-grabbing z-10"
            :class="[
              draggingId === item.event.id ? 'opacity-40' : '',
              eventStatusClasses(item.event)
            ]"
            :style="weekEventStyle(item)"
            :title="eventTooltip(item.event)"
            @dragstart="onDragStart(item.event, $event)"
            @dragend="onDragEnd"
            @click.stop="openEdit(item.event)"
          >
            <div class="truncate font-semibold flex items-center justify-between gap-1">
              <span class="truncate" :class="item.event.my_status === 'declined' || item.event.status === 'cancelled' ? 'line-through' : ''">{{ item.event.title }}</span>
              <span v-if="item.event.my_status === 'pending'" class="shrink-0 px-1 text-[9px] font-bold rounded bg-cyan-100 text-cyan-800 border border-cyan-300">Offen</span>
            </div>
            <div class="truncate text-[10px] opacity-80">{{ timeOf(item.event.start) }}–{{ timeOf(item.event.end) }}</div>
          </button>
        </div>
      </div>
    </div>

    <!-- TAGESANSICHT -->
    <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden">
      <div class="px-5 h-14 flex items-center justify-between border-b border-slate-200">
        <div class="flex items-center gap-2">
          <span class="text-sm font-semibold text-slate-900">{{ dayLabel }}</span>
          <span v-if="isToday" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-cyan-50 text-cyan-800 border border-cyan-200">
            Heute
          </span>
        </div>
        <span class="text-xs text-slate-500">{{ dayEvents.length }} Termin(e)</span>
      </div>

      <div class="divide-y divide-slate-100">
        <div
          v-for="ev in dayEvents"
          :key="ev.id"
          class="px-5 py-3 flex items-start gap-3 hover:bg-slate-50 transition-colors cursor-pointer"
          @click="openEdit(ev)"
        >
          <span class="w-1 self-stretch rounded-full shrink-0" :style="{ backgroundColor: ev.color }" />
          <div class="w-20 shrink-0 text-xs font-mono text-slate-500 pt-0.5">
            <div v-if="ev.allDay" class="font-semibold">ganztägig</div>
            <template v-else>
              <div>{{ timeOf(ev.start) }}</div>
              <div class="text-slate-400">{{ timeOf(ev.end) }}</div>
            </template>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-slate-900 truncate" :class="ev.my_status === 'declined' || ev.status === 'cancelled' ? 'line-through text-slate-400' : ''">{{ ev.title }}</span>
              <span
                v-if="ev.category_name"
                class="px-1.5 py-0.5 rounded text-[10px] font-medium shrink-0"
                :style="{ backgroundColor: ev.color + '22', color: ev.color }"
              >
                {{ ev.category_name }}
              </span>
              <span
                v-if="ev.my_status"
                class="px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0"
                :class="statusClass(ev.my_status)"
              >
                {{ statusLabel(ev.my_status) }}
              </span>
            </div>

            <!-- Schnellantwort-Knöpfe für Einladungen direkt in der Tagesansicht -->
            <div v-if="!ev.is_organizer && ev.my_status" class="mt-2 flex flex-wrap items-center gap-2" @click.stop>
              <span class="text-[11px] text-slate-500 font-medium">Antwort:</span>
              <button
                type="button"
                class="px-2.5 py-1 text-[11px] font-semibold rounded transition-colors"
                :class="ev.my_status === 'accepted' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200'"
                @click.stop="quickRespond(ev, 'accepted')"
              >
                Zusagen
              </button>
              <button
                type="button"
                class="px-2.5 py-1 text-[11px] font-semibold rounded transition-colors"
                :class="ev.my_status === 'tentative' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200'"
                @click.stop="quickRespond(ev, 'tentative')"
              >
                Vorbehalt
              </button>
              <button
                type="button"
                class="px-2.5 py-1 text-[11px] font-semibold rounded transition-colors"
                :class="ev.my_status === 'declined' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'"
                @click.stop="quickRespond(ev, 'declined')"
              >
                Absagen
              </button>
            </div>

            <div v-if="ev.location" class="text-xs text-slate-500 mt-1 flex items-center gap-1">
              <MapPin class="w-3 h-3" />
              {{ ev.location }}
            </div>

            <!-- Karte & Route für Termine mit Ort -->
            <div v-if="ev.location" class="mt-2">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <button
                  type="button"
                  class="text-[11px] font-semibold text-[#0891B2] hover:underline"
                  @click.stop="toggleEventMap(ev)"
                >
                  {{ openEventMaps[ev.id] ? 'Karte einklappen' : 'Karte anzeigen' }}
                </button>
                <a
                  :href="googleMapsUrl(ev.location)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 hover:underline"
                  @click.stop
                >
                  Google Maps
                </a>
                <a
                  :href="osmUrl(ev.location)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 hover:underline"
                  @click.stop
                >
                  OpenStreetMap
                </a>
                <button
                  type="button"
                  class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 hover:underline"
                  title="Route ab meinem Standort"
                  @click.stop="openEventRoute(ev)"
                >
                  Route
                </button>
              </div>

              <div
                v-if="openEventMaps[ev.id]"
                class="mt-2 rounded-md overflow-hidden border border-slate-200 bg-slate-100 relative h-[170px]"
              >
                <div v-if="eventMapLoading[ev.id]" class="absolute inset-0 flex items-center justify-center bg-slate-50/90 text-xs font-medium text-slate-600 gap-2">
                  <Loader2 class="w-3.5 h-3.5 animate-spin" />
                  Karte wird geladen…
                </div>
                <iframe
                  v-if="eventMapUrl(ev)"
                  :src="eventMapUrl(ev)"
                  class="w-full h-full border-0"
                  loading="lazy"
                  title="OpenStreetMap Karte"
                />
                <div v-else-if="!eventMapLoading[ev.id]" class="p-3 text-center text-[11px] text-slate-500">
                  Standort konnte nicht ermittelt werden.
                </div>
              </div>
            </div>

            <div v-if="ev.description" class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ ev.description }}</div>
            <div v-if="ev.attendee_count > 1" class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
              <Users class="w-3 h-3" />
              {{ ev.attendee_count }} Teilnehmer
            </div>
          </div>
        </div>

        <div v-if="dayEvents.length === 0" class="py-16 text-center">
          <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center mx-auto mb-3">
            <CalendarDays class="w-6 h-6 text-slate-400" />
          </div>
          <p class="text-sm font-semibold text-slate-800">Keine Termine</p>
          <p class="text-xs text-slate-500 mt-1">Für diesen Tag ist nichts geplant.</p>
          <button type="button" class="taskster_button mt-4" @click="openCreate(selectedDate)">
            <Plus class="w-4 h-4" />
            Termin erstellen
          </button>
        </div>
      </div>
    </div>

    <!-- Tages-Detail (Monatsansicht: "+N weitere") -->
    <div v-if="dayDetailKey" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40" @mousedown.self="dayDetailKey = null">
      <div class="bg-white rounded-lg shadow-md w-full max-w-md max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between px-5 h-14 border-b border-slate-200">
          <h2 class="text-base font-semibold text-slate-900">{{ formatDayLong(dayDetailKey) }}</h2>
          <button type="button" class="h-8 w-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100" @click="dayDetailKey = null">
            <X class="w-4 h-4" />
          </button>
        </div>
        <div class="p-3 overflow-y-auto flex-1 space-y-1">
          <button
            v-for="ev in dayDetailEvents"
            :key="ev.id"
            type="button"
            class="w-full text-left px-3 py-2 rounded-md hover:bg-slate-50 transition-colors flex items-start gap-2"
            @click="dayDetailKey = null; openEdit(ev)"
          >
            <span class="w-1 self-stretch rounded-full shrink-0" :style="{ backgroundColor: ev.color }" />
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-medium text-slate-800 truncate">{{ ev.title }}</span>
              <span class="block text-[11px] text-slate-500">
                {{ ev.allDay ? 'ganztägig' : timeOf(ev.start) + '–' + timeOf(ev.end) }}
                <template v-if="ev.location"> · {{ ev.location }}</template>
              </span>
            </span>
          </button>
        </div>
        <div class="flex justify-end px-5 h-16 items-center border-t border-slate-200">
          <button type="button" class="taskster_button" @click="dayDetailKey = null; openCreate(dayDetailKey)">
            <Plus class="w-4 h-4" />
            Termin
          </button>
        </div>
      </div>
    </div>

    <!-- Termin-Modal -->
    <CalendarEventModal
      v-if="showEventModal"
      :event="editingEvent"
      :default-date="defaultDate"
      :default-hour="defaultHour"
      :categories="categories"
      :projects="projects"
      :members="members"
      :defaults="calSettings"
      @close="showEventModal = false"
      @saved="onEventSaved"
      @deleted="onEventDeleted"
    />

    <!-- Kategorie-Modal -->
    <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40" @mousedown.self="showCategoryModal = false">
      <div class="bg-white rounded-lg shadow-md w-full max-w-sm">
        <div class="flex items-center justify-between px-5 h-14 border-b border-slate-200">
          <h2 class="text-base font-semibold text-slate-900">{{ editingCategoryId ? 'Kategorie bearbeiten' : $t('calendar.neue_kategorie') }}</h2>
          <button type="button" class="h-8 w-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100" @click="showCategoryModal = false">
            <X class="w-4 h-4" />
          </button>
        </div>
        <form class="p-5 space-y-4" @submit.prevent="saveCategory">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Name</label>
            <input v-model="newCategory.name" type="text" required placeholder="z.B. Werkstatt" class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Farbe</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="c in colorChoices"
                :key="c"
                type="button"
                class="w-7 h-7 rounded-md border-2 transition-transform hover:scale-110"
                :class="newCategory.color === c ? 'border-slate-900 ring-2 ring-slate-300' : 'border-white shadow-sm'"
                :style="{ backgroundColor: c }"
                @click="newCategory.color = c"
              />
            </div>
          </div>
          <label v-if="user?.company_id" class="flex items-center gap-2 text-xs text-slate-700">
            <input v-model="newCategory.company_wide" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0" />
            Für die ganze Firma sichtbar
          </label>
          <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
            <button type="button" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg" @click="showCategoryModal = false">
              Abbrechen
            </button>
            <button type="submit" class="taskster_button px-6 text-xs h-[42px] rounded-lg">Speichern</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bestätigungs-Modal zum Löschen einer Kategorie (kein Browser-confirm) -->
    <div v-if="categoryToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40" @mousedown.self="categoryToDelete = null">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 space-y-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 shrink-0">
            <X class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900">Kategorie löschen?</h3>
            <p class="text-xs text-slate-500 mt-0.5">Möchtest du die Kategorie "{{ categoryToDelete.name }}" wirklich löschen? Bestehende Termine bleiben erhalten (ohne Kategorie).</p>
          </div>
        </div>

        <div v-if="categoryErrorMessage" class="p-3 bg-rose-50 border border-rose-200 rounded-md text-xs text-rose-700">
          {{ categoryErrorMessage }}
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg" @click="categoryToDelete = null">
            Abbrechen
          </button>
          <button type="button" class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg" :disabled="isDeletingCategory" @click="confirmDeleteCategory">
            <Loader2 v-if="isDeletingCategory" class="w-4 h-4 animate-spin mr-1.5 inline" />
            Löschen
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  CalendarDays, ChevronLeft, ChevronRight, Plus, X, Loader2,
  MapPin, Users, ChevronUp, ChevronDown
} from 'lucide-vue-next'

const { user, authHeaders } = useAuth()
const route = useRoute()

// ---------------------------------------------------------------------------
// Zustand
// ---------------------------------------------------------------------------
type ViewKey = 'month' | 'week' | 'day'
const views: { key: ViewKey; label: string }[] = [
  { key: 'month', label: 'Monat' },
  { key: 'week', label: 'Woche' },
  { key: 'day', label: 'Tag' }
]

const view = ref<ViewKey>('month')
const cursor = ref(new Date())
const selectedDate = ref(toKey(new Date()))
const loading = ref(false)
const events = ref<any[]>([])
const tasks = ref<any[]>([])
const categories = ref<any[]>([])
const projects = ref<any[]>([])
const members = ref<any[]>([])
const activeCategories = ref<string[]>([])

const showEventModal = ref(false)
const editingEvent = ref<any>(null)
const defaultDate = ref<string | null>(null)
const defaultHour = ref<number | null>(null)
const dayDetailKey = ref<string | null>(null)

const showCategoryModal = ref(false)
const editingCategoryId = ref('')
const newCategory = ref({ name: '', color: '#0891B2', company_wide: false })
const colorChoices = ['#0891B2', '#7C3AED', '#D97706', '#059669', '#DC2626', '#2563EB', '#DB2777', '#64748B']

const openNewCategoryModal = () => {
  editingCategoryId.value = ''
  newCategory.value = { name: '', color: '#0891B2', company_wide: false }
  showCategoryModal.value = true
}

const editCategory = (c: any) => {
  editingCategoryId.value = c.id
  newCategory.value = { name: c.name, color: c.color, company_wide: !!c.company_id }
  showCategoryModal.value = true
}

const draggingId = ref<string | null>(null)
const dragOverKey = ref<string | null>(null)

// ---------------------------------------------------------------------------
// Persönliche Einstellungen (users.settings)
// ---------------------------------------------------------------------------
const DEFAULT_CAL = {
  default_view: 'month',
  week_start: 1,
  show_week_numbers: false,
  show_weekends: true,
  workday_start: '07:00',
  workday_end: '17:00',
  slot_minutes: 30,
  default_duration_minutes: 60,
  default_reminder_minutes: 15,
  default_category_id: null,
  default_visibility: 'private',
  show_tasks: true,
  show_declined: false,
  time_format: '24h'
}

const calSettings = computed(() => ({
  ...DEFAULT_CAL,
  ...((user.value?.settings?.calendar as any) || {})
}))

/** 0 = Sonntag, 1 = Montag */
const weekStart = computed(() => Number(calSettings.value.week_start) === 0 ? 0 : 1)

const weekdays = computed(() => {
  const base = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa']
  return Array.from({ length: 7 }, (_, i) => base[(weekStart.value + i) % 7])
})

const hours = Array.from({ length: 24 }, (_, i) => i)

/** Arbeitszeit-Grenzen in Minuten seit Mitternacht. */
const workStartMin = computed(() => {
  const [h, m] = String(calSettings.value.workday_start).split(':').map(Number)
  return (h || 0) * 60 + (m || 0)
})
const workEndMin = computed(() => {
  const [h, m] = String(calSettings.value.workday_end).split(':').map(Number)
  return (h || 0) * 60 + (m || 0)
})

/** Ist diese Stunde Teil der Arbeitszeit? */
function isWorkHour(h: number) {
  const start = h * 60
  return start >= workStartMin.value && start < workEndMin.value
}

/** Sichtbare Wochentage (Wochenende optional ausgeblendet). */
const visibleWeekdayIndexes = computed(() => {
  const all = Array.from({ length: 7 }, (_, i) => i)
  if (calSettings.value.show_weekends) return all
  // Wochenende = die beiden Tage, die auf Fr folgen (Sa/So)
  return all.filter((i) => {
    const dow = (weekStart.value + i) % 7
    return dow !== 0 && dow !== 6
  })
})

/** Spaltenanzahl für das Wochenraster (Stundenspalte + sichtbare Tage). */
const weekGridCols = computed(() => `60px repeat(${visibleWeekdayIndexes.value.length},1fr)`)

/** Spaltenanzahl für das Monatsraster (optional KW-Spalte + sichtbare Tage). */
const monthGridCols = computed(() => {
  const days = `repeat(${visibleWeekdayIndexes.value.length},1fr)`
  return calSettings.value.show_week_numbers ? `36px ${days}` : days
})

/** Kalenderwoche (ISO 8601) für ein Datum. */
function isoWeek(d: Date): number {
  const t = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()))
  const dayNum = t.getUTCDay() || 7
  t.setUTCDate(t.getUTCDate() + 4 - dayNum)
  const yearStart = new Date(Date.UTC(t.getUTCFullYear(), 0, 1))
  return Math.ceil((((t.getTime() - yearStart.getTime()) / 86400000) + 1) / 7)
}

// ---------------------------------------------------------------------------
// Datums-Helfer
// ---------------------------------------------------------------------------
function toKey(d: Date): string {
  const p = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`
}

const todayKey = toKey(new Date())

const timeOf = (v: string) => {
  if (!v) return ''
  const d = new Date(String(v).replace(' ', 'T'))
  if (isNaN(d.getTime())) return ''
  const h = d.getHours()
  const m = String(d.getMinutes()).padStart(2, '0')
  if (calSettings.value.time_format === '12h') {
    const suffix = h < 12 ? 'AM' : 'PM'
    const h12 = h % 12 === 0 ? 12 : h % 12
    return `${h12}:${m} ${suffix}`
  }
  return `${String(h).padStart(2, '0')}:${m}`
}

const formatDayLong = (key: string) => {
  const d = new Date(key + 'T00:00:00')
  return d.toLocaleDateString('de-CH', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
}

// ---------------------------------------------------------------------------
// Zeitraum
// ---------------------------------------------------------------------------
const range = computed(() => {
  const c = cursor.value
  const ws = weekStart.value
  if (view.value === 'month') {
    const first = new Date(c.getFullYear(), c.getMonth(), 1)
    const last = new Date(c.getFullYear(), c.getMonth() + 1, 0)
    // Raster beginnt am eingestellten ersten Wochentag
    const start = new Date(first)
    start.setDate(first.getDate() - ((first.getDay() - ws + 7) % 7))
    const end = new Date(last)
    end.setDate(last.getDate() + (6 - ((last.getDay() - ws + 7) % 7)))
    return { start, end }
  }
  if (view.value === 'week') {
    const start = new Date(c)
    start.setDate(c.getDate() - ((c.getDay() - ws + 7) % 7))
    const end = new Date(start)
    end.setDate(start.getDate() + 6)
    return { start, end }
  }
  return { start: new Date(c), end: new Date(c) }
})

const rangeLabel = computed(() => {
  const { start, end } = range.value
  const opts: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'long', year: 'numeric' }
  if (view.value === 'day') return start.toLocaleDateString('de-CH', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
  if (view.value === 'month') return start.toLocaleDateString('de-CH', { month: 'long', year: 'numeric' })
  return `${start.toLocaleDateString('de-CH', opts)} – ${end.toLocaleDateString('de-CH', opts)}`
})

const dayLabel = computed(() => cursor.value.toLocaleDateString('de-CH', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }))
const isToday = computed(() => toKey(cursor.value) === todayKey)

// ---------------------------------------------------------------------------
// Raster
// ---------------------------------------------------------------------------
const monthCells = computed(() => {
  const { start, end } = range.value
  const cells: any[] = []
  const d = new Date(start)
  while (d <= end) {
    const key = toKey(d)
    cells.push({
      key,
      day: d.getDate(),
      inMonth: d.getMonth() === cursor.value.getMonth(),
      events: eventsForDay(key)
    })
    d.setDate(d.getDate() + 1)
  }
  return cells
})

const weekDays = computed(() => {
  const { start } = range.value
  const out: any[] = []
  for (const i of visibleWeekdayIndexes.value) {
    const d = new Date(start)
    d.setDate(start.getDate() + i)
    out.push({
      key: toKey(d),
      day: d.getDate(),
      weekday: weekdays.value[i],
      isWeekend: [0, 6].includes(d.getDay())
    })
  }
  return out
})

/** Kalenderwochen-Nummern je Rasterzeile (Monatsansicht). */
const monthWeekNumbers = computed(() => {
  const { start, end } = range.value
  const out: number[] = []
  const d = new Date(start)
  while (d <= end) {
    out.push(isoWeek(d))
    d.setDate(d.getDate() + 7)
  }
  return out
})

const currentWeekNumber = computed(() => isoWeek(range.value.start))

const dayEvents = computed(() => eventsForDay(selectedDate.value))

const dayDetailEvents = computed(() => dayDetailKey.value ? eventsForDay(dayDetailKey.value) : [])

/** Alle Termine eines Tages (Events + Aufgaben), gefiltert nach Kategorie. */
function eventsForDay(key: string) {
  const evs = events.value.filter((e) => String(e.start).slice(0, 10) === key)
  const tks = calSettings.value.show_tasks
    ? tasks.value.filter((t) => String(t.start).slice(0, 10) === key)
    : []
  let all = [...evs, ...tks]

  // Abgesagte Termine optional ausblenden
  if (!calSettings.value.show_declined) {
    all = all.filter((e) => e.type === 'task' || e.my_status !== 'declined')
  }

  if (!activeCategories.value.length) return all
  return all.filter((e) => e.type === 'task' || activeCategories.value.includes(e.category_id))
}

// ---------------------------------------------------------------------------
// Wochen-Positionierung
// ---------------------------------------------------------------------------
/** Nur Termine mit Uhrzeit kommen ins Stundenraster. */
function timedEventsForDay(key: string) {
  return eventsForDay(key).filter((e) => !e.allDay)
}

/** Berechnet Nebeneinander-Spalten (Collision Layout) für Termine desselben Tages. */
function timedEventsWithLayoutForDay(key: string) {
  const evs = timedEventsForDay(key)
  if (!evs.length) return []

  const items = evs.map((ev) => {
    const s = new Date(String(ev.start).replace(' ', 'T'))
    const e = new Date(String(ev.end).replace(' ', 'T'))
    const startMin = isNaN(s.getTime()) ? 0 : s.getHours() * 60 + s.getMinutes()
    let endMin = isNaN(e.getTime()) ? startMin + 60 : e.getHours() * 60 + e.getMinutes()
    if (endMin <= startMin) endMin = startMin + 15

    return {
      event: ev,
      startMin,
      endMin,
      colIndex: 0
    }
  })

  items.sort((a, b) => {
    if (a.startMin !== b.startMin) return a.startMin - b.startMin
    return (b.endMin - b.startMin) - (a.endMin - a.startMin)
  })

  const clusters: typeof items[] = []
  let currentCluster: typeof items = []
  let clusterMaxEnd = -1

  for (const item of items) {
    if (currentCluster.length === 0) {
      currentCluster.push(item)
      clusterMaxEnd = item.endMin
    } else if (item.startMin < clusterMaxEnd) {
      currentCluster.push(item)
      if (item.endMin > clusterMaxEnd) clusterMaxEnd = item.endMin
    } else {
      clusters.push(currentCluster)
      currentCluster = [item]
      clusterMaxEnd = item.endMin
    }
  }
  if (currentCluster.length > 0) clusters.push(currentCluster)

  const result: any[] = []

  for (const cluster of clusters) {
    const columns: typeof items[] = []

    for (const item of cluster) {
      let placed = false
      for (let c = 0; c < columns.length; c++) {
        const lastInCol = columns[c][columns[c].length - 1]
        if (lastInCol.endMin <= item.startMin) {
          columns[c].push(item)
          item.colIndex = c
          placed = true
          break
        }
      }
      if (!placed) {
        columns.push([item])
        item.colIndex = columns.length - 1
      }
    }

    const maxCols = columns.length
    for (const item of cluster) {
      const colIndex = item.colIndex || 0
      const left = (colIndex / maxCols) * 100
      const width = (100 / maxCols)

      result.push({
        event: item.event,
        startMin: item.startMin,
        endMin: item.endMin,
        leftPercent: left,
        widthPercent: width,
        maxCols,
        colIndex
      })
    }
  }

  return result
}

/** Ganztägige Termine je Wochentag (eigene Zeile). */
const weekAllDay = computed(() =>
  weekDays.value.map((d) => ({
    key: d.key,
    items: eventsForDay(d.key).filter((e) => e.allDay)
  }))
)

// ---------------------------------------------------------------------------
// Scroll & Unsichtbare Termine (07:00 Standard & +N Badges)
// ---------------------------------------------------------------------------
const weekGridRef = ref<HTMLElement | null>(null)
const gridScrollTop = ref(0)
const gridClientHeight = ref(600)

function onWeekGridScroll(e: Event) {
  const el = e.target as HTMLElement
  if (!el) return
  gridScrollTop.value = el.scrollTop
  gridClientHeight.value = el.clientHeight || 600
}

const topVisibleMin = computed(() => (gridScrollTop.value / 56) * 60)
const bottomVisibleMin = computed(() => ((gridScrollTop.value + gridClientHeight.value) / 56) * 60)

function getEarlierEventsCount(dayKey: string): number {
  const items = timedEventsWithLayoutForDay(dayKey)
  return items.filter((item: any) => item.endMin <= topVisibleMin.value + 15).length
}

function getLaterEventsCount(dayKey: string): number {
  const items = timedEventsWithLayoutForDay(dayKey)
  return items.filter((item: any) => item.startMin >= bottomVisibleMin.value - 15).length
}

function scrollToStartHour() {
  nextTick(() => {
    if (weekGridRef.value) {
      const targetTop = (workStartMin.value / 60) * 56
      weekGridRef.value.scrollTop = targetTop
      gridScrollTop.value = targetTop
      gridClientHeight.value = weekGridRef.value.clientHeight || 600
    }
  })
}

function scrollToEarlierEvents(dayKey: string) {
  const items = timedEventsWithLayoutForDay(dayKey).filter((item: any) => item.endMin <= topVisibleMin.value + 15)
  if (!items.length) return
  const minStart = Math.min(...items.map((i: any) => i.startMin))
  if (weekGridRef.value) {
    weekGridRef.value.scrollTo({
      top: Math.max(0, (minStart / 60) * 56 - 10),
      behavior: 'smooth'
    })
  }
}

function scrollToLaterEvents(dayKey: string) {
  const items = timedEventsWithLayoutForDay(dayKey).filter((item: any) => item.startMin >= bottomVisibleMin.value - 15)
  if (!items.length) return
  const maxEnd = Math.max(...items.map((i: any) => i.endMin))
  if (weekGridRef.value) {
    weekGridRef.value.scrollTo({
      top: (maxEnd / 60) * 56 - gridClientHeight.value + 20,
      behavior: 'smooth'
    })
  }
}

function weekEventStyle(item: any) {
  const ev = item.event || item
  const startMin = item.startMin !== undefined ? item.startMin : (() => {
    const s = new Date(String(ev.start).replace(' ', 'T'))
    return s.getHours() * 60 + s.getMinutes()
  })()
  const endMin = item.endMin !== undefined ? item.endMin : (() => {
    const e = new Date(String(ev.end).replace(' ', 'T'))
    return e.getHours() * 60 + e.getMinutes()
  })()

  const top = (startMin / 60) * 56
  const height = Math.max(((endMin - startMin) / 60) * 56, 22)
  const left = item.leftPercent ?? 0
  const width = item.widthPercent ?? 100

  return {
    top: top + 'px',
    height: height + 'px',
    left: `calc(${left}% + 1px)`,
    width: `calc(${width}% - 2px)`,
    backgroundColor: ev.color + '22',
    color: ev.color,
    borderLeft: '3px solid ' + ev.color
  }
}

function eventStatusClasses(ev: any) {
  const classes: string[] = []
  if (ev.my_status === 'pending') {
    classes.push('border-2 border-dashed border-cyan-500/70 bg-cyan-50/40')
  } else if (ev.my_status === 'tentative') {
    classes.push('border-2 border-dotted border-amber-500/70')
  } else if (ev.my_status === 'declined' || ev.status === 'cancelled') {
    classes.push('line-through opacity-50 grayscale')
  }
  return classes.join(' ')
}

async function quickRespond(ev: any, status: 'accepted' | 'declined' | 'tentative') {
  try {
    await $fetch(`/api/events/${ev.id}/respond`, {
      method: 'POST',
      headers: authHeaders(),
      body: { status }
    })
    ev.my_status = status
    await loadEvents()
  } catch (err: any) {
    alert(err?.data?.statusMessage || 'Antwort konnte nicht gespeichert werden')
  }
}

// ---------------------------------------------------------------------------
// Drag & Drop
// ---------------------------------------------------------------------------
function onDragStart(ev: any, e: DragEvent) {
  if (!ev.editable) {
    e.preventDefault()
    return
  }
  draggingId.value = ev.id
  e.dataTransfer?.setData('text/plain', ev.id)
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move'
}

function onDragEnd() {
  draggingId.value = null
  dragOverKey.value = null
}

function onDragOver(cell: any) {
  if (draggingId.value) dragOverKey.value = cell.key
}

function onDragLeave(cell: any) {
  if (dragOverKey.value === cell.key) dragOverKey.value = null
}

/** Verschiebt einen Termin auf einen anderen Tag (Zeit bleibt erhalten). */
async function onDrop(cell: any) {
  const id = draggingId.value
  dragOverKey.value = null
  draggingId.value = null
  if (!id) return

  const ev = events.value.find((e) => e.id === id)
  if (!ev || !ev.editable) return

  const oldKey = String(ev.start).slice(0, 10)
  if (oldKey === cell.key) return

  const start = new Date(String(ev.start).replace(' ', 'T'))
  const end = new Date(String(ev.end).replace(' ', 'T'))
  const dayDiff = Math.round(
    (new Date(cell.key + 'T00:00:00').getTime() - new Date(oldKey + 'T00:00:00').getTime()) / 86400000
  )
  start.setDate(start.getDate() + dayDiff)
  end.setDate(end.getDate() + dayDiff)

  await moveEvent(ev, fmt(start), fmt(end))
}

/** Verschiebt einen Termin auf Tag + Stunde (Wochenansicht). */
async function onDropAt(dayKey: string, hour: number) {
  const id = draggingId.value
  dragOverKey.value = null
  draggingId.value = null
  if (!id) return

  const ev = events.value.find((e) => e.id === id)
  if (!ev || !ev.editable) return

  const oldStart = new Date(String(ev.start).replace(' ', 'T'))
  const oldEnd = new Date(String(ev.end).replace(' ', 'T'))
  const durationMs = oldEnd.getTime() - oldStart.getTime()

  const newStart = new Date(dayKey + 'T00:00:00')
  newStart.setHours(hour, 0, 0, 0)
  const newEnd = new Date(newStart.getTime() + durationMs)

  await moveEvent(ev, fmt(newStart), fmt(newEnd))
}

function fmt(d: Date): string {
  const p = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:00`
}

/** Optimistisches Update + API-Aufruf. */
async function moveEvent(ev: any, startAt: string, endAt: string) {
  const prevStart = String(ev.start || '').replace('T', ' ').slice(0, 16)
  const prevEnd = String(ev.end || '').replace('T', ' ').slice(0, 16)
  const nextStart = String(startAt || '').replace('T', ' ').slice(0, 16)
  const nextEnd = String(endAt || '').replace('T', ' ').slice(0, 16)

  // Bei unverändertem Zeitpunkt keinen API-Aufruf ausführen
  if (prevStart === nextStart && prevEnd === nextEnd) {
    return
  }

  const oldStart = ev.start
  const oldEnd = ev.end
  ev.start = startAt
  ev.end = endAt

  try {
    await $fetch(`/api/events/${ev.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { start_at: startAt, end_at: endAt }
    })
  } catch (err: any) {
    ev.start = oldStart
    ev.end = oldEnd
    alert(err?.data?.statusMessage || 'Termin konnte nicht verschoben werden')
  }
}

// ---------------------------------------------------------------------------
// Karte & Route für Termine
// ---------------------------------------------------------------------------
const { geocode, embedUrl, osmUrl, googleMapsUrl, routeFromHere } = useAddressSearch()

const openEventMaps = ref<Record<string, boolean>>({})
const eventMapLoading = ref<Record<string, boolean>>({})
const eventMapPoints = ref<Record<string, { lat: number; lon: number } | null>>({})

/** Koordinaten: bevorzugt aus der DB, sonst einmalig geocodieren. */
async function ensureEventPoint(ev: any) {
  if (eventMapPoints.value[ev.id]) return eventMapPoints.value[ev.id]

  if (ev.latitude !== null && ev.latitude !== undefined && ev.longitude !== null && ev.longitude !== undefined) {
    eventMapPoints.value[ev.id] = { lat: Number(ev.latitude), lon: Number(ev.longitude) }
    return eventMapPoints.value[ev.id]
  }

  if (!ev.location) return null
  eventMapLoading.value[ev.id] = true
  try {
    const point = await geocode(ev.location)
    eventMapPoints.value[ev.id] = point
    return point
  } finally {
    eventMapLoading.value[ev.id] = false
  }
}

async function toggleEventMap(ev: any) {
  const current = Boolean(openEventMaps.value[ev.id])
  openEventMaps.value[ev.id] = !current
  if (!current) await ensureEventPoint(ev)
}

function eventMapUrl(ev: any) {
  const point = eventMapPoints.value[ev.id]
  return point ? embedUrl(point) : ''
}

async function openEventRoute(ev: any) {
  if (!ev.location) return
  const url = await routeFromHere(ev.location, 'driving')
  window.open(url, '_blank', 'noopener')
}

// ---------------------------------------------------------------------------
// Modal-Steuerung
// ---------------------------------------------------------------------------
function openCreate(dayKey?: string, hour?: number) {
  editingEvent.value = null
  defaultDate.value = dayKey || selectedDate.value
  defaultHour.value = hour ?? null
  showEventModal.value = true
}

function openEdit(ev: any) {
  if (ev.type === 'task') {
    navigateTo(`/projects/${ev.project_id}?task=${ev.task_id}`)
    return
  }
  editingEvent.value = ev
  defaultDate.value = null
  defaultHour.value = null
  showEventModal.value = true
}

function openDayDetail(key: string) {
  dayDetailKey.value = key
}

async function onEventSaved() {
  showEventModal.value = false
  await loadEvents()
}

async function onEventDeleted() {
  showEventModal.value = false
  await loadEvents()
}

// ---------------------------------------------------------------------------
// Kategorien
// ---------------------------------------------------------------------------
function toggleCategory(id: string) {
  const i = activeCategories.value.indexOf(id)
  if (i === -1) activeCategories.value.push(id)
  else activeCategories.value.splice(i, 1)
}

async function saveCategory() {
  try {
    if (editingCategoryId.value) {
      await $fetch(`/api/event-categories/${editingCategoryId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: newCategory.value
      })
    } else {
      await $fetch('/api/event-categories', {
        method: 'POST',
        headers: authHeaders(),
        body: newCategory.value
      })
    }
    showCategoryModal.value = false
    newCategory.value = { name: '', color: '#0891B2', company_wide: false }
    editingCategoryId.value = ''
    await loadCategories()
  } catch (err: any) {
    alert(err?.data?.statusMessage || $t('calendar.kategorie_konnte_nicht_erstellt_wer'))
  }
}

const categoryToDelete = ref<any>(null)
const isDeletingCategory = ref(false)
const categoryErrorMessage = ref('')

function deleteCategory(cat: any) {
  categoryErrorMessage.value = ''
  categoryToDelete.value = cat
}

async function confirmDeleteCategory() {
  if (!categoryToDelete.value) return
  isDeletingCategory.value = true
  categoryErrorMessage.value = ''
  try {
    await $fetch(`/api/event-categories/${categoryToDelete.value.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    categoryToDelete.value = null
    await loadCategories()
    await loadEvents()
  } catch (err: any) {
    categoryErrorMessage.value = err?.data?.statusMessage || err?.data?.message || t('calendar.kategorie_loeschen_fehler')
  } finally {
    isDeletingCategory.value = false
  }
}

// ---------------------------------------------------------------------------
// Badges
// ---------------------------------------------------------------------------
const statusClass = (s: string) => ({
  'bg-emerald-50 text-emerald-700 border border-emerald-200': s === 'accepted',
  'bg-rose-50 text-rose-700 border border-rose-200': s === 'declined',
  'bg-amber-50 text-amber-700 border border-amber-200': s === 'tentative',
  'bg-slate-100 text-slate-600 border border-slate-200': s === 'pending'
})

const statusLabel = (s: string) =>
  ({ accepted: 'Zugesagt', declined: 'Abgesagt', tentative: 'Vorbehalt', pending: 'Offen' } as any)[s] || s

function eventTooltip(ev: any) {
  const parts = [ev.title]
  if (!ev.allDay) parts.push(`${timeOf(ev.start)}–${timeOf(ev.end)}`)
  if (ev.location) parts.push(ev.location)
  if (ev.category_name) parts.push(ev.category_name)
  if (ev.attendee_count > 1) parts.push(`${ev.attendee_count} Teilnehmer`)
  return parts.join(' · ')
}

// ---------------------------------------------------------------------------
// Navigation
// ---------------------------------------------------------------------------
function shift(delta: number) {
  const c = new Date(cursor.value)
  if (view.value === 'month') c.setMonth(c.getMonth() + delta)
  else if (view.value === 'week') c.setDate(c.getDate() + delta * 7)
  else c.setDate(c.getDate() + delta)
  cursor.value = c
  selectedDate.value = toKey(c)
  loadEvents()
}

function goToday() {
  cursor.value = new Date()
  selectedDate.value = todayKey
  loadEvents()
}

// ---------------------------------------------------------------------------
// Daten laden
// ---------------------------------------------------------------------------
async function loadEvents() {
  loading.value = true
  try {
    const { start, end } = range.value
    const res = await $fetch<any>('/api/events', {
      headers: authHeaders(),
      params: { from: toKey(start), to: toKey(end) }
    })
    events.value = res.events || []
    tasks.value = res.tasks || []
  } catch {
    events.value = []
    tasks.value = []
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const res = await $fetch<any>('/api/event-categories', { headers: authHeaders() })
    categories.value = res.categories || []
  } catch {
    categories.value = []
  }
}

async function loadProjects() {
  try {
    const res = await $fetch<any>('/api/projects', { headers: authHeaders() })
    projects.value = res.projects || []
  } catch {
    projects.value = []
  }
}

async function loadMembers() {
  try {
    const res = await $fetch<any>('/api/companies/members', { headers: authHeaders() })
    members.value = res.members || []
  } catch {
    members.value = []
  }
}

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (route.query.date && typeof route.query.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(route.query.date)) {
    const [y, m, d] = route.query.date.split('-').map(Number)
    cursor.value = new Date(y, m - 1, d)
    selectedDate.value = route.query.date
  }
  // Standard-Ansicht aus den persönlichen Einstellungen übernehmen
  const dv = calSettings.value.default_view
  if (dv === 'week' || dv === 'day' || dv === 'month') view.value = dv
  await Promise.all([loadEvents(), loadCategories(), loadProjects(), loadMembers()])
  if (view.value === 'week') {
    scrollToStartHour()
  }
})

watch(() => route.query.date, (newDate) => {
  if (newDate && typeof newDate === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(newDate)) {
    const [y, m, d] = newDate.split('-').map(Number)
    cursor.value = new Date(y, m - 1, d)
    selectedDate.value = newDate
    loadEvents()
  }
})

watch(view, (newVal) => {
  loadEvents()
  if (newVal === 'week') {
    scrollToStartHour()
  }
})
</script>
