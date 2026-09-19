<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <div class="flex items-center space-x-2 text-xs font-bold text-slate-500 mb-1">
          <NuxtLink to="/dashboard" class="hover:text-cyan-700 transition">Workspace</NuxtLink>
          <span>/</span>
          <span class="text-slate-800">Zeitrapportierung</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center space-x-3">
          <span>⏱️</span>
          <span>Zeitrapportierung & Controlling</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 font-medium mt-1">
          Alle erfassten Arbeitszeiten, Budgets und abrechenbaren Leistungen im Gesamtüberblick.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center flex-wrap gap-2.5">
        <button
          @click="exportCsv"
          :disabled="filteredEntries.length === 0"
          type="button"
          class="taskster_button_light px-5 text-xs h-[42px] rounded-lg flex items-center space-x-2 disabled:opacity-50 cursor-pointer"
          title="Als CSV-Datei (Excel-kompatibel) herunterladen"
        >
          <span>📥</span>
          <span>CSV Export</span>
        </button>

        <button
          @click="printRapport"
          :disabled="filteredEntries.length === 0"
          type="button"
          class="taskster_button_light px-5 text-xs h-[42px] rounded-lg flex items-center space-x-2 disabled:opacity-50 cursor-pointer"
          title="Druckansicht für Kundenrapport öffnen"
        >
          <span>🖨️</span>
          <span class="hidden sm:inline">Drucken</span>
        </button>

        <button
          @click="openCreateModal"
          type="button"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg flex items-center space-x-2 cursor-pointer shadow-md"
        >
          <span class="text-base font-black">+</span>
          <span>Zeit erfassen</span>
        </button>
      </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Total Duration -->
      <div class="liquid_glass p-5 rounded-3xl border border-white/70 shadow-lg relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-black tracking-wider uppercase text-slate-500">Erfasste Zeit</span>
          <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-sm font-black shadow-xs">
            ⏱️
          </span>
        </div>
        <div class="mt-3">
          <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            {{ formatHoursAndMinutes(summary.totalMinutes) }}
          </div>
          <div class="text-xs font-semibold text-cyan-800 mt-0.5">
            {{ (summary.totalMinutes / 60).toFixed(2) }} Dezimalstunden
          </div>
        </div>
      </div>

      <!-- Total Cost / Amount -->
      <div class="liquid_glass p-5 rounded-3xl border border-white/70 shadow-lg relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-black tracking-wider uppercase text-slate-500">Abrechenbarer Wert</span>
          <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm font-black shadow-xs">
            💰
          </span>
        </div>
        <div class="mt-3">
          <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            {{ formatCurrency(summary.totalCost) }}
          </div>
          <div class="text-xs font-semibold text-emerald-700 mt-0.5">
            basierend auf hinterlegten Stundensätzen
          </div>
        </div>
      </div>

      <!-- Average Rate -->
      <div class="liquid_glass p-5 rounded-3xl border border-white/70 shadow-lg relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-black tracking-wider uppercase text-slate-500">Ø Stundensatz</span>
          <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm font-black shadow-xs">
            📈
          </span>
        </div>
        <div class="mt-3">
          <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            {{ averageRate.toFixed(2) }} <span class="text-sm font-bold text-slate-500">CHF/h</span>
          </div>
          <div class="text-xs font-semibold text-indigo-700 mt-0.5">
            effektiver Mischsatz aller Einträge
          </div>
        </div>
      </div>

      <!-- Total Entries Count -->
      <div class="liquid_glass p-5 rounded-3xl border border-white/70 shadow-lg relative overflow-hidden group">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-black tracking-wider uppercase text-slate-500">Buchungen</span>
          <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-sm font-black shadow-xs">
            📋
          </span>
        </div>
        <div class="mt-3">
          <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            {{ filteredEntries.length }}
          </div>
          <div class="text-xs font-semibold text-slate-600 mt-0.5">
            {{ stopwatchEntriesCount }} via Stoppuhr, {{ manualEntriesCount }} manuell
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="liquid_glass p-5 rounded-3xl border border-white/70 shadow-xl mb-8 space-y-4">
      <!-- Quick Range Switcher -->
      <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200/60">
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="text-xs font-black text-slate-500 uppercase mr-1">Zeitraum:</span>
          <button
            v-for="preset in presets"
            :key="preset.id"
            @click="selectPreset(preset.id)"
            type="button"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="activePreset === preset.id ? 'bg-[#00A3C4] text-white shadow-xs' : 'bg-white/80 hover:bg-white text-slate-700 border border-slate-200/80'"
          >
            {{ preset.label }}
          </button>
        </div>

        <div class="text-xs font-semibold text-slate-500">
          {{ filteredEntries.length }} von {{ allEntries.length }} Einträgen angezeigt
        </div>
      </div>

      <!-- Detailed Filters Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Search Input -->
        <div class="relative">
          <span class="absolute left-3.5 top-2.5 text-slate-400 text-xs">🔍</span>
          <input
            v-model="searchFilter"
            type="text"
            placeholder="Suche nach Text, Aufgabe..."
            class="w-full pl-9 pr-3 py-2 bg-white/95 rounded-xl text-xs text-slate-800 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          />
        </div>

        <!-- Project Filter -->
        <div>
          <select
            v-model="selectedProjectId"
            @change="loadTimeEntries"
            class="w-full px-3 py-2 bg-white/95 rounded-xl text-xs font-bold text-slate-800 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          >
            <option value="">Alle Projekte ({{ availableProjects.length }})</option>
            <option v-for="p in availableProjects" :key="p.id" :value="p.id">
              {{ p.title }}
            </option>
          </select>
        </div>

        <!-- Date From -->
        <div class="flex items-center space-x-2">
          <span class="text-[11px] font-bold text-slate-500 shrink-0">Von:</span>
          <input
            v-model="dateFrom"
            @change="onCustomDateChange"
            type="date"
            class="w-full px-3 py-2 bg-white/95 rounded-xl text-xs font-bold text-slate-800 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          />
        </div>

        <!-- Date To -->
        <div class="flex items-center space-x-2">
          <span class="text-[11px] font-bold text-slate-500 shrink-0">Bis:</span>
          <input
            v-model="dateTo"
            @change="onCustomDateChange"
            type="date"
            class="w-full px-3 py-2 bg-white/95 rounded-xl text-xs font-bold text-slate-800 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          />
        </div>
      </div>
    </div>

    <!-- Time Entries Table Section -->
    <div class="liquid_glass rounded-3xl border border-white/70 shadow-2xl overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="py-16 text-center">
        <div class="inline-block animate-spin text-3xl mb-2">⏱️</div>
        <p class="text-xs font-bold text-slate-600">Zeitrapporte werden geladen...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredEntries.length === 0" class="py-16 px-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-cyan-50 text-[#00A3C4] flex items-center justify-center text-2xl font-black mx-auto mb-3 shadow-xs">
          ⏱️
        </div>
        <h3 class="text-sm font-black text-slate-800">Keine Zeiteinträge gefunden</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">
          Im ausgewählten Zeitraum liegen keine Buchungen vor. Starte die Live-Stoppuhr oben oder trage eine Zeit manuell nach.
        </p>
        <button
          @click="openCreateModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg inline-flex items-center space-x-2"
        >
          <span>+ Zeit manuell eintragen</span>
        </button>
      </div>

      <!-- Interactive Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200/80 bg-slate-50/70 text-[11px] font-black uppercase tracking-wider text-slate-500">
              <th class="py-3.5 px-4 sm:px-6">Datum</th>
              <th class="py-3.5 px-4">Mitarbeiter</th>
              <th class="py-3.5 px-4">Projekt & Aufgabe</th>
              <th class="py-3.5 px-4">Erfassung</th>
              <th class="py-3.5 px-4">Beschreibung</th>
              <th class="py-3.5 px-4 text-right">Dauer</th>
              <th class="py-3.5 px-4 text-right">Ansatz</th>
              <th class="py-3.5 px-4 text-right">Betrag</th>
              <th class="py-3.5 px-4 sm:px-6 text-right">Aktionen</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr
              v-for="entry in filteredEntries"
              :key="entry.id"
              class="hover:bg-white/80 transition-colors group"
            >
              <!-- Date -->
              <td class="py-3 px-4 sm:px-6 font-bold text-slate-800 whitespace-nowrap">
                {{ formatDate(entry.entry_date) }}
              </td>

              <!-- User -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="flex items-center space-x-2">
                  <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-[10px] font-black">
                    {{ (entry.user_name || 'U').charAt(0).toUpperCase() }}
                  </div>
                  <span class="font-bold text-slate-800">{{ entry.user_name }}</span>
                </div>
              </td>

              <!-- Project & Task -->
              <td class="py-3 px-4 min-w-[200px]">
                <NuxtLink
                  :to="'/projects/' + entry.project_id"
                  class="font-extrabold text-[#00A3C4] hover:underline block truncate"
                >
                  {{ entry.project_title }}
                </NuxtLink>
                <span v-if="entry.task_title" class="text-[11px] text-slate-600 block truncate">
                  🎯 {{ entry.task_title }}
                </span>
              </td>

              <!-- Type Badge -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span
                  v-if="!entry.is_manual"
                  class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-cyan-100 text-cyan-800 border border-cyan-200"
                  title="Über Live-Stoppuhr gestoppt"
                >
                  <span>⏱️</span>
                  <span>Stoppuhr</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"
                  title="Manuelle Zeiterfassung"
                >
                  <span>✏️</span>
                  <span>Manuell</span>
                </span>
              </td>

              <!-- Description -->
              <td class="py-3 px-4 text-slate-700 max-w-[260px] truncate" :title="entry.description">
                {{ entry.description || '–' }}
              </td>

              <!-- Duration -->
              <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap">
                <div>{{ formatHoursAndMinutes(entry.duration_minutes) }}</div>
                <div class="text-[10px] text-slate-600 font-semibold">
                  {{ (entry.duration_minutes / 60).toFixed(2) }} h
                </div>
              </td>

              <!-- Rate -->
              <td class="py-3 px-4 text-right font-semibold text-slate-600 whitespace-nowrap">
                {{ (Number(entry.hourly_rate) || 0).toFixed(2) }} {{ entry.project_currency || 'CHF' }}/h
              </td>

              <!-- Amount -->
              <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap">
                {{ ((Number(entry.duration_minutes) / 60) * (Number(entry.hourly_rate) || 0)).toFixed(2) }} {{ entry.project_currency || 'CHF' }}
              </td>

              <!-- Actions -->
              <td class="py-3 px-4 sm:px-6 text-right whitespace-nowrap">
                <div class="flex items-center justify-end space-x-1">
                  <button
                    @click="openEditModal(entry)"
                    class="p-1.5 rounded-lg hover:bg-cyan-50 text-slate-600 hover:text-[#00A3C4] transition cursor-pointer"
                    title="Eintrag bearbeiten"
                  >
                    ✏️
                  </button>
                  <button
                    @click="deleteEntry(entry.id)"
                    class="p-1.5 rounded-lg hover:bg-rose-50 text-slate-600 hover:text-rose-600 transition cursor-pointer"
                    title="Eintrag löschen"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Table Footer Summary -->
          <tfoot>
            <tr class="bg-slate-50/90 font-black text-xs text-slate-900 border-t-2 border-slate-200">
              <td colspan="5" class="py-3.5 px-4 sm:px-6 uppercase tracking-wider text-slate-600">
                Summe der gefilterten Auswahl
              </td>
              <td class="py-3.5 px-4 text-right text-cyan-800">
                {{ formatHoursAndMinutes(summary.totalMinutes) }}
              </td>
              <td class="py-3.5 px-4 text-right text-slate-500">
                Ø {{ averageRate.toFixed(2) }}
              </td>
              <td class="py-3.5 px-4 text-right text-emerald-800">
                {{ formatCurrency(summary.totalCost) }}
              </td>
              <td class="py-3.5 px-4 sm:px-6"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Modal: Create / Book Manual Time -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 overflow-hidden relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
          <div class="flex items-center space-x-2">
            <span class="text-xl">⏱️</span>
            <h3 class="text-base font-black text-slate-900">Arbeitszeit manuell erfassen</h3>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitCreateTime" class="space-y-4">
          <!-- Project Selection -->
          <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
              Projekt <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="createForm.project_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option value="" disabled>Projekt auswählen...</option>
              <option v-for="p in availableProjects" :key="p.id" :value="p.id">
                {{ p.title }}
              </option>
            </select>
          </div>

          <!-- Date & Duration -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
                Datum <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="createForm.entry_date"
                type="date"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
                Dauer (Minuten) <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.number="createForm.duration_minutes"
                type="number"
                min="1"
                required
                placeholder="z.B. 90 (für 1.5h)"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
              />
              <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">
                = {{ (createForm.duration_minutes / 60 || 0).toFixed(2) }} Stunden
              </span>
            </div>
          </div>

          <!-- Hourly Rate -->
          <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
              Stundensatz (CHF / EUR)
            </label>
            <input
              v-model.number="createForm.hourly_rate"
              type="number"
              step="0.5"
              min="0"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
              Tätigkeitsbeschreibung / Notiz
            </label>
            <textarea
              v-model="createForm.description"
              rows="3"
              placeholder="Welche Arbeiten wurden ausgeführt?"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
            ></textarea>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
            <button
              @click="showCreateModal = false"
              type="button"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="submittingTime"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ submittingTime ? 'Speichert...' : 'Zeit erfassen' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Time Entry -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 overflow-hidden relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
          <div class="flex items-center space-x-2">
            <span class="text-xl">✏️</span>
            <h3 class="text-base font-black text-slate-900">Zeiteintrag bearbeiten</h3>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitEditTime" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
                Datum <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="editForm.entry_date"
                type="date"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
                Dauer (Minuten) <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.number="editForm.duration_minutes"
                type="number"
                min="1"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
              />
              <span class="text-[10px] text-slate-500 font-semibold block mt-0.5">
                = {{ (editForm.duration_minutes / 60 || 0).toFixed(2) }} Stunden
              </span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
              Stundensatz (CHF / EUR)
            </label>
            <input
              v-model.number="editForm.hourly_rate"
              type="number"
              step="0.5"
              min="0"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>

          <div>
            <label class="block text-xs font-black uppercase tracking-wider text-slate-600 mb-1">
              Tätigkeitsbeschreibung / Notiz
            </label>
            <textarea
              v-model="editForm.description"
              rows="3"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:ring-2 focus:ring-[#00A3C4]"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
            <button
              @click="showEditModal = false"
              type="button"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="submittingTime"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ submittingTime ? 'Speichert...' : 'Änderungen speichern' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const { user, token } = useAuth()

const authHeaders = () => ({
  'Authorization': `Bearer ${token.value || ''}`
})

const loading = ref(true)
const allEntries = ref<any[]>([])
const availableProjects = ref<any[]>([])
const searchFilter = ref('')
const selectedProjectId = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const activePreset = ref('this_month')

const showCreateModal = ref(false)
const showEditModal = ref(false)
const submittingTime = ref(false)

const createForm = ref({
  project_id: '',
  entry_date: new Date().toISOString().substring(0, 10),
  duration_minutes: 60,
  hourly_rate: 120,
  description: ''
})

const editForm = ref({
  id: '',
  entry_date: '',
  duration_minutes: 0,
  hourly_rate: 0,
  description: ''
})

const presets = [
  { id: 'today', label: 'Heute' },
  { id: 'this_week', label: 'Diese Woche' },
  { id: 'this_month', label: 'Dieser Monat' },
  { id: 'all', label: 'Gesamt' },
  { id: 'custom', label: 'Benutzerdefiniert' }
]

const selectPreset = (presetId: string) => {
  activePreset.value = presetId
  const now = new Date()

  if (presetId === 'today') {
    const todayStr = now.toISOString().substring(0, 10)
    dateFrom.value = todayStr
    dateTo.value = todayStr
  } else if (presetId === 'this_week') {
    const day = now.getDay()
    const diff = now.getDate() - day + (day === 0 ? -6 : 1) // Monday
    const monday = new Date(now.setDate(diff))
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)
    dateFrom.value = monday.toISOString().substring(0, 10)
    dateTo.value = sunday.toISOString().substring(0, 10)
  } else if (presetId === 'this_month') {
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    dateFrom.value = firstDay.toISOString().substring(0, 10)
    dateTo.value = lastDay.toISOString().substring(0, 10)
  } else if (presetId === 'all') {
    dateFrom.value = ''
    dateTo.value = ''
  }
  loadTimeEntries()
}

const onCustomDateChange = () => {
  activePreset.value = 'custom'
  loadTimeEntries()
}

const loadAvailableProjects = async () => {
  try {
    const res = await $fetch<{ projects: any[] }>('/api/projects', {
      headers: authHeaders()
    })
    const projs = res.projects || []
    availableProjects.value = projs
    if (projs.length > 0 && !createForm.value.project_id) {
      createForm.value.project_id = projs[0].id
    }
  } catch (err) {
    console.error('Failed to load available projects', err)
  }
}

const loadTimeEntries = async () => {
  loading.value = true
  try {
    let url = '/api/time-entries?'
    const queryParams: string[] = []
    if (selectedProjectId.value) {
      queryParams.push(`project_id=${encodeURIComponent(selectedProjectId.value)}`)
    }
    if (dateFrom.value) {
      queryParams.push(`date_from=${encodeURIComponent(dateFrom.value)}`)
    }
    if (dateTo.value) {
      queryParams.push(`date_to=${encodeURIComponent(dateTo.value)}`)
    }
    url += queryParams.join('&')

    const res = await $fetch<{ entries: any[], summary: any }>(url, {
      headers: authHeaders()
    })
    allEntries.value = res.entries || []
  } catch (err) {
    allEntries.value = []
  } finally {
    loading.value = false
  }
}

const filteredEntries = computed(() => {
  let list = allEntries.value
  if (searchFilter.value.trim()) {
    const q = searchFilter.value.toLowerCase().trim()
    list = list.filter(e =>
      (e.description && e.description.toLowerCase().includes(q)) ||
      (e.task_title && e.task_title.toLowerCase().includes(q)) ||
      (e.project_title && e.project_title.toLowerCase().includes(q)) ||
      (e.user_name && e.user_name.toLowerCase().includes(q))
    )
  }
  return list
})

const summary = computed(() => {
  let totalMinutes = 0
  let totalCost = 0
  for (const e of filteredEntries.value) {
    const mins = Number(e.duration_minutes) || 0
    const rate = Number(e.hourly_rate) || 0
    totalMinutes += mins
    totalCost += (mins / 60) * rate
  }
  return {
    totalMinutes,
    totalCost
  }
})

const averageRate = computed(() => {
  const totalHours = summary.value.totalMinutes / 60
  if (totalHours <= 0) return 0
  return summary.value.totalCost / totalHours
})

const stopwatchEntriesCount = computed(() => {
  return filteredEntries.value.filter(e => !e.is_manual).length
})

const manualEntriesCount = computed(() => {
  return filteredEntries.value.filter(e => e.is_manual).length
})

const formatHoursAndMinutes = (minutes: number) => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  if (h === 0) return `${m} Min.`
  if (m === 0) return `${h} Std.`
  return `${h} Std. ${m} Min.`
}

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('de-CH', { style: 'currency', currency: 'CHF' }).format(val || 0)
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const openCreateModal = () => {
  createForm.value.entry_date = new Date().toISOString().substring(0, 10)
  createForm.value.duration_minutes = 60
  createForm.value.hourly_rate = Number(user.value?.hourly_rate) || 120
  createForm.value.description = ''
  if (availableProjects.value.length > 0 && !createForm.value.project_id) {
    createForm.value.project_id = availableProjects.value[0].id
  }
  showCreateModal.value = true
}

const submitCreateTime = async () => {
  if (!createForm.value.project_id || createForm.value.duration_minutes <= 0) return
  submittingTime.value = true
  try {
    await $fetch('/api/time-entries', {
      method: 'POST',
      headers: authHeaders(),
      body: createForm.value
    })
    showCreateModal.value = false
    await loadTimeEntries()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erfassen der Zeit')
  } finally {
    submittingTime.value = false
  }
}

const openEditModal = (entry: any) => {
  editForm.value = {
    id: entry.id,
    entry_date: entry.entry_date || new Date().toISOString().substring(0, 10),
    duration_minutes: entry.duration_minutes,
    hourly_rate: entry.hourly_rate,
    description: entry.description || ''
  }
  showEditModal.value = true
}

const submitEditTime = async () => {
  if (!editForm.value.id || editForm.value.duration_minutes <= 0) return
  submittingTime.value = true
  try {
    await $fetch(`/api/time-entries/${editForm.value.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: editForm.value
    })
    showEditModal.value = false
    await loadTimeEntries()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Aktualisieren des Zeiteintrags')
  } finally {
    submittingTime.value = false
  }
}

const deleteEntry = async (id: string) => {
  if (!confirm('Möchtest du diesen Zeiteintrag wirklich löschen?')) return
  try {
    await $fetch(`/api/time-entries/${id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    allEntries.value = allEntries.value.filter(e => e.id !== id)
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Eintrags')
  }
}

const exportCsv = () => {
  if (filteredEntries.value.length === 0) return
  const headers = ['Datum', 'Mitarbeiter', 'Projekt', 'Aufgabe', 'Erfassungsart', 'Beschreibung', 'Dauer (Minuten)', 'Dauer (Stunden)', 'Stundensatz', 'Betrag (CHF)']
  const rows = filteredEntries.value.map(e => [
    e.entry_date,
    `"${(e.user_name || '').replace(/"/g, '""')}"`,
    `"${(e.project_title || '').replace(/"/g, '""')}"`,
    `"${(e.task_title || '').replace(/"/g, '""')}"`,
    e.is_manual ? 'Manuell' : 'Stoppuhr',
    `"${(e.description || '').replace(/"/g, '""')}"`,
    e.duration_minutes,
    (e.duration_minutes / 60).toFixed(2),
    (Number(e.hourly_rate) || 0).toFixed(2),
    ((Number(e.duration_minutes) / 60) * (Number(e.hourly_rate) || 0)).toFixed(2)
  ])

  const csvContent = '\uFEFF' + [headers.join(';'), ...rows.map(r => r.join(';'))].join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `zeitrapporte_${new Date().toISOString().substring(0, 10)}.csv`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const printRapport = () => {
  window.print()
}

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (!user.value) {
    navigateTo('/login')
    return
  }
  selectPreset('this_month')
  await loadAvailableProjects()
})
</script>

<style scoped>
@media print {
  body {
    background: white !important;
  }
  header, aside, .taskster_button, .taskster_button_light {
    display: none !important;
  }
}
</style>
