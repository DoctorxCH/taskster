<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <!-- Breadcrumb & Top Header Card -->
    <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
          <NuxtLink to="/dashboard" class="hover:text-[#00A3C4] transition-colors flex items-center gap-1">
            <LayoutDashboard class="w-3.5 h-3.5" />
            <span>Dashboard</span>
          </NuxtLink>
          <span>/</span>
          <span class="text-slate-800 font-semibold flex items-center gap-1">
            <Clock class="w-3.5 h-3.5 text-[#00A3C4]" />
            <span>Zeitrapportierung</span>
          </span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
          <Clock class="w-6 h-6 text-[#00A3C4]" />
          <span>Zeitrapportierung & Controlling</span>
        </h1>
        <p class="text-xs text-slate-500 mt-1">
          Alle erfassten Arbeitszeiten, Budgets und abrechenbaren Leistungen im Gesamtüberblick.
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center flex-wrap gap-2">
        <button
          @click="exportCsv"
          :disabled="filteredEntries.length === 0"
          type="button"
          class="taskster_button_light px-4 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5"
          title="Als CSV-Datei herunterladen"
        >
          <Download class="w-3.5 h-3.5 text-slate-600" />
          <span>CSV Export</span>
        </button>

        <button
          @click="printRapport"
          :disabled="filteredEntries.length === 0"
          type="button"
          class="taskster_button_light px-4 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5"
          title="Druckansicht öffnen"
        >
          <Printer class="w-3.5 h-3.5 text-slate-600" />
          <span class="hidden sm:inline">Drucken</span>
        </button>

        <button
          @click="openCreateModal"
          type="button"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5 font-medium"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Zeit erfassen</span>
        </button>
      </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Duration -->
      <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-2 text-slate-500">
          <Clock class="w-4 h-4 text-[#00A3C4]" />
          <span class="text-xs font-semibold uppercase tracking-wide">Erfasste Zeit</span>
        </div>
        <div class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">
          {{ formatHoursAndMinutes(summary.totalMinutes) }}
        </div>
        <div class="text-xs text-slate-500 mt-0.5">
          {{ (summary.totalMinutes / 60).toFixed(2) }} Dezimalstunden
        </div>
      </div>

      <!-- Total Cost / Amount -->
      <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-2 text-slate-500">
          <Coins class="w-4 h-4 text-emerald-600" />
          <span class="text-xs font-semibold uppercase tracking-wide">Abrechenbarer Wert</span>
        </div>
        <div class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">
          {{ formatCurrency(summary.totalCost) }}
        </div>
        <div class="text-xs text-slate-500 mt-0.5">
          nach hinterlegten Stundensätzen
        </div>
      </div>

      <!-- Average Rate -->
      <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-2 text-slate-500">
          <TrendingUp class="w-4 h-4 text-cyan-600" />
          <span class="text-xs font-semibold uppercase tracking-wide">Ø Stundensatz</span>
        </div>
        <div class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">
          {{ averageRate.toFixed(2) }} <span class="text-sm font-normal text-slate-500">CHF/h</span>
        </div>
        <div class="text-xs text-slate-500 mt-0.5">
          Mischsatz aller Einträge
        </div>
      </div>

      <!-- Total Entries Count -->
      <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-2 text-slate-500">
          <FileText class="w-4 h-4 text-slate-600" />
          <span class="text-xs font-semibold uppercase tracking-wide">Buchungen</span>
        </div>
        <div class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">
          {{ filteredEntries.length }}
        </div>
        <div class="text-xs text-slate-500 mt-0.5">
          {{ stopwatchEntriesCount }} Stoppuhr, {{ manualEntriesCount }} manuell
        </div>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-6 shadow-sm space-y-4">
      <!-- Quick Range Switcher -->
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-200">
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Zeitraum:</span>
          <button
            v-for="preset in presets"
            :key="preset.id"
            @click="selectPreset(preset.id)"
            type="button"
            class="px-3 py-1 rounded text-xs font-semibold transition cursor-pointer"
            :class="activePreset === preset.id ? 'bg-[#00A3C4] text-white shadow-2xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'"
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
          <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
          <input
            v-model="searchFilter"
            type="text"
            placeholder="Suche nach Text, Aufgabe..."
            class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
          />
        </div>

        <!-- Project Filter -->
        <div>
          <select
            v-model="selectedProjectId"
            @change="loadTimeEntries"
            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
          >
            <option value="">Alle Projekte ({{ availableProjects.length }})</option>
            <option v-for="p in availableProjects" :key="p.id" :value="p.id">
              {{ p.title }}
            </option>
          </select>
        </div>

        <!-- Date From -->
        <div class="flex items-center space-x-2">
          <span class="text-xs font-semibold text-slate-500 shrink-0">Von:</span>
          <input
            v-model="dateFrom"
            @change="onCustomDateChange"
            type="date"
            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
          />
        </div>

        <!-- Date To -->
        <div class="flex items-center space-x-2">
          <span class="text-xs font-semibold text-slate-500 shrink-0">Bis:</span>
          <input
            v-model="dateTo"
            @change="onCustomDateChange"
            type="date"
            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-md text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
          />
        </div>
      </div>
    </div>

    <!-- Time Entries Table Section -->
    <div class="bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="py-16 text-center">
        <Clock class="w-8 h-8 text-[#00A3C4] animate-spin mx-auto mb-2" />
        <p class="text-xs font-semibold text-slate-600">Zeitrapporte werden geladen...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredEntries.length === 0" class="py-16 px-6 text-center">
        <div class="w-12 h-12 rounded-lg bg-cyan-50 text-[#00A3C4] flex items-center justify-center mx-auto mb-3 border border-cyan-200">
          <Clock class="w-6 h-6" />
        </div>
        <h3 class="text-sm font-bold text-slate-900">Keine Zeiteinträge gefunden</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">
          Im ausgewählten Zeitraum liegen keine Buchungen vor. Starte die Live-Stoppuhr oben oder trage eine Zeit manuell nach.
        </p>
        <button
          @click="openCreateModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg inline-flex items-center space-x-1.5"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Zeit manuell eintragen</span>
        </button>
      </div>

      <!-- Interactive Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
              <th class="py-3 px-4 sm:px-6">Datum</th>
              <th class="py-3 px-4">Mitarbeiter</th>
              <th class="py-3 px-4">Projekt & Aufgabe</th>
              <th class="py-3 px-4">Erfassung</th>
              <th class="py-3 px-4">Beschreibung</th>
              <th class="py-3 px-4 text-right">Dauer</th>
              <th class="py-3 px-4 text-right">Ansatz</th>
              <th class="py-3 px-4 text-right">Betrag</th>
              <th class="py-3 px-4 sm:px-6 text-right">Aktionen</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr
              v-for="entry in filteredEntries"
              :key="entry.id"
              class="hover:bg-slate-50 transition-colors group"
            >
              <!-- Date -->
              <td class="py-3 px-4 sm:px-6 font-semibold text-slate-800 whitespace-nowrap">
                {{ formatDate(entry.entry_date) }}
              </td>

              <!-- User -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="flex items-center space-x-2">
                  <div class="w-6 h-6 rounded bg-cyan-100 text-[#00A3C4] flex items-center justify-center text-[10px] font-bold border border-cyan-200">
                    {{ (entry.user_name || 'U').charAt(0).toUpperCase() }}
                  </div>
                  <span class="font-semibold text-slate-800">{{ entry.user_name }}</span>
                </div>
              </td>

              <!-- Project & Task -->
              <td class="py-3 px-4 min-w-[200px]">
                <NuxtLink
                  :to="'/projects/' + entry.project_id"
                  class="font-bold text-[#00A3C4] hover:underline block truncate"
                >
                  {{ entry.project_title }}
                </NuxtLink>
                <span v-if="entry.task_title" class="text-[11px] text-slate-600 flex items-center gap-1 truncate mt-0.5">
                  <Target class="w-3 h-3 text-cyan-600 inline shrink-0" />
                  <span>{{ entry.task_title }}</span>
                </span>
              </td>

              <!-- Type Badge -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span
                  v-if="!entry.is_manual"
                  class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-[#00A3C4] border border-cyan-200"
                  title="Über Live-Stoppuhr gestoppt"
                >
                  <Clock class="w-3 h-3 text-[#00A3C4]" />
                  <span>Stoppuhr</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center space-x-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200"
                  title="Manuelle Zeiterfassung"
                >
                  <Pencil class="w-3 h-3 text-slate-500" />
                  <span>Manuell</span>
                </span>
              </td>

              <!-- Description -->
              <td class="py-3 px-4 text-slate-700 max-w-[260px] truncate" :title="entry.description">
                {{ entry.description || '–' }}
              </td>

              <!-- Duration -->
              <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                <div>{{ formatHoursAndMinutes(entry.duration_minutes) }}</div>
                <div class="text-[10px] text-slate-500 font-medium">
                  {{ (entry.duration_minutes / 60).toFixed(2) }} h
                </div>
              </td>

              <!-- Rate -->
              <td class="py-3 px-4 text-right font-medium text-slate-600 whitespace-nowrap">
                {{ (Number(entry.hourly_rate) || 0).toFixed(2) }} {{ entry.project_currency || 'CHF' }}/h
              </td>

              <!-- Amount -->
              <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                {{ ((Number(entry.duration_minutes) / 60) * (Number(entry.hourly_rate) || 0)).toFixed(2) }} {{ entry.project_currency || 'CHF' }}
              </td>

              <!-- Actions -->
              <td class="py-3 px-4 sm:px-6 text-right whitespace-nowrap">
                <div class="flex items-center justify-end space-x-1">
                  <button
                    @click="openEditModal(entry)"
                    class="p-1 rounded hover:bg-slate-100 text-slate-500 hover:text-[#00A3C4] transition cursor-pointer"
                    title="Eintrag bearbeiten"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="deleteEntry(entry.id)"
                    class="p-1 rounded hover:bg-rose-50 text-slate-500 hover:text-rose-600 transition cursor-pointer"
                    title="Eintrag löschen"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Table Footer Summary -->
          <tfoot>
            <tr class="bg-slate-50 font-bold text-xs text-slate-900 border-t-2 border-slate-200">
              <td colspan="5" class="py-3 px-4 sm:px-6 uppercase tracking-wider text-slate-600">
                Summe der gefilterten Auswahl
              </td>
              <td class="py-3 px-4 text-right text-[#00A3C4]">
                {{ formatHoursAndMinutes(summary.totalMinutes) }}
              </td>
              <td class="py-3 px-4 text-right text-slate-500">
                Ø {{ averageRate.toFixed(2) }}
              </td>
              <td class="py-3 px-4 text-right text-emerald-700">
                {{ formatCurrency(summary.totalCost) }}
              </td>
              <td class="py-3 px-4 sm:px-6"></td>
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
    <!-- UNIVERSAL IN-APP CONFIRMATION MODAL (Zero Native Popups) -->
    <div v-if="confirmModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center space-x-3">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
            :class="confirmModal.danger ? 'bg-rose-100 border border-rose-200 text-rose-600' : 'bg-cyan-100 border border-cyan-200 text-[#00A3C4]'"
          >
            <AlertTriangle v-if="confirmModal.danger" class="w-5 h-5" />
            <Info v-else class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">{{ confirmModal.title }}</h3>
            <p v-if="confirmModal.subtitle" class="text-xs text-slate-500 mt-0.5">{{ confirmModal.subtitle }}</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">{{ confirmModal.message }}</p>

        <div v-if="confirmModal.error" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
          {{ confirmModal.error }}
        </div>

        <div class="flex items-center justify-end space-x-3 pt-2">
          <button
            type="button"
            @click="confirmModal.show = false"
            :disabled="confirmModal.loading"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
          >
            Abbrechen
          </button>
          <button
            type="button"
            @click="executeConfirmModalAction"
            :disabled="confirmModal.loading"
            :class="confirmModal.danger ? 'taskster_button_accent' : 'taskster_button'"
            class="px-6 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-2"
          >
            <span>{{ confirmModal.loading ? 'Wird ausgeführt...' : confirmModal.confirmText }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- UNIVERSAL IN-APP TOAST FEEDBACK (Zero Native Popups) -->
    <div v-if="pageToast.show" class="fixed bottom-6 right-6 z-50 max-w-sm w-full transition-all duration-300">
      <div
        class="flex items-start gap-3 p-4 rounded-2xl shadow-xl border backdrop-blur-md"
        :class="pageToast.type === 'error' ? 'bg-rose-50/95 border-rose-200 text-rose-900' : pageToast.type === 'success' ? 'bg-emerald-50/95 border-emerald-200 text-emerald-900' : 'bg-slate-900/90 border-slate-700 text-white'"
      >
        <CheckCircle2 v-if="pageToast.type === 'success'" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
        <AlertTriangle v-else-if="pageToast.type === 'error'" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
        <Info v-else class="w-5 h-5 text-cyan-400 shrink-0 mt-0.5" />
        <div class="flex-1 text-xs">
          <div class="font-bold">{{ pageToast.title || (pageToast.type === 'error' ? 'Hinweis' : pageToast.type === 'success' ? 'Erfolg' : 'Info') }}</div>
          <div class="mt-0.5 leading-relaxed">{{ pageToast.message }}</div>
        </div>
        <button @click="pageToast.show = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  AlertTriangle,
  CheckCircle2,
  Info,
  Clock,
  Download,
  Printer,
  Plus,
  Search,
  FileText,
  TrendingUp,
  Coins,
  Pencil,
  Trash2,
  X,
  LayoutDashboard,
  Target
} from 'lucide-vue-next'

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
    showToast('Zeiteintrag erfolgreich erfasst', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Erfassen der Zeit', 'error')
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
    showToast('Zeiteintrag erfolgreich aktualisiert', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Aktualisieren des Zeiteintrags', 'error')
  } finally {
    submittingTime.value = false
  }
}

const deleteEntry = async (id: string) => {
  triggerConfirmModal({
    title: 'Zeiteintrag löschen',
    subtitle: 'Dieser Vorgang kann nicht rückgängig gemacht werden',
    message: 'Möchtest du diesen Zeiteintrag wirklich unwiderruflich löschen?',
    confirmText: 'Eintrag löschen',
    danger: true,
    action: async () => {
      await $fetch(`/api/time-entries/${id}`, {
        method: 'DELETE',
        headers: authHeaders()
      })
      allEntries.value = allEntries.value.filter(e => e.id !== id)
      showToast('Zeiteintrag erfolgreich gelöscht', 'success')
    }
  })
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
