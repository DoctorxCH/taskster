<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
    <!-- Header with MeisterTask Liquid Glass Styling -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
      <div>
        <div class="inline-flex items-center space-x-2 text-xs font-bold text-slate-800 px-3.5 py-1.5 rounded-full liquid_glass_pill mb-2 shadow-xs">
          <span>📇</span>
          <span>Baustellen- & Projektverzeichnis</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center space-x-2">
          <span>Kontakte & Ansprechpartner</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-700 font-medium mt-1">
          Verwalte Handwerker, Bauleiter, Planer und Behörden. Geteilte Kontakte stehen deinem Team und Projektmitgliedern sofort zur Verfügung.
        </p>
      </div>

      <!-- Action Button -->
      <div class="flex items-center space-x-3 shrink-0">
        <button
          @click="openCreateModal()"
          type="button"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-md"
        >
          <span>+ Neuer Kontakt</span>
        </button>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
      <div class="liquid_glass_card p-4 rounded-2xl shadow-xs flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-800 flex items-center justify-center text-lg font-black shrink-0">
          👥
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Gesamt</p>
          <p class="text-lg font-black text-slate-900">{{ contacts.length }}</p>
        </div>
      </div>

      <div class="liquid_glass_card p-4 rounded-2xl shadow-xs flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg font-black shrink-0">
          🏢
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Firma geteilt</p>
          <p class="text-lg font-black text-emerald-900">{{ sharedCompanyCount }}</p>
        </div>
      </div>

      <div class="liquid_glass_card p-4 rounded-2xl shadow-xs flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg font-black shrink-0">
          🏗️
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Mit Projektbezug</p>
          <p class="text-lg font-black text-blue-900">{{ projectLinkedCount }}</p>
        </div>
      </div>

      <div class="liquid_glass_card p-4 rounded-2xl shadow-xs flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-lg font-black shrink-0">
          🔒
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Privat / Eigene</p>
          <p class="text-lg font-black text-purple-900">{{ privateCount }}</p>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="liquid_glass_card p-4 sm:p-5 rounded-3xl mb-6 shadow-md">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1 min-w-[240px]">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
          <input
            v-model="searchFilter"
            type="text"
            placeholder="Nach Name, Firma, Funktion, Telefon oder E-Mail suchen..."
            class="w-full pl-10 pr-4 py-2.5 bg-white/95 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          />
        </div>

        <!-- Filter Dropdowns -->
        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Group Filter -->
          <div class="relative">
            <select
              v-model="selectedGroup"
              class="px-3 py-2 bg-white/95 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#00A3C4] cursor-pointer"
            >
              <option value="">Alle Gruppen</option>
              <option v-for="g in groupOptions" :key="g" :value="g">{{ g }}</option>
            </select>
          </div>

          <!-- Project Filter -->
          <div class="relative max-w-[200px]">
            <select
              v-model="selectedProjectId"
              class="w-full px-3 py-2 bg-white/95 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#00A3C4] truncate cursor-pointer"
            >
              <option value="">Alle Projekte</option>
              <option v-for="p in availableProjects" :key="p.id" :value="p.id">{{ p.title }}</option>
            </select>
          </div>

          <!-- Scope Filter -->
          <div class="relative">
            <select
              v-model="selectedScope"
              class="px-3 py-2 bg-white/95 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#00A3C4] cursor-pointer"
            >
              <option value="">Alle Freigaben</option>
              <option value="company">🏢 Im Unternehmen geteilt</option>
              <option value="private">🔒 Nur Privat / Eigene</option>
            </select>
          </div>

          <!-- View Mode Toggle -->
          <div class="bg-white/90 border border-slate-200/80 rounded-xl p-0.5 flex items-center space-x-1 shadow-xs h-[38px]">
            <button
              @click="viewMode = 'cards'"
              class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer h-[32px]"
              :class="viewMode === 'cards' ? 'bg-cyan-50 text-cyan-800 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
              title="Kartenansicht"
            >
              <span>▦</span>
              <span class="hidden sm:inline">Karten</span>
            </button>
            <button
              @click="viewMode = 'table'"
              class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer h-[32px]"
              :class="viewMode === 'table' ? 'bg-cyan-50 text-cyan-800 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
              title="Tabellenansicht"
            >
              <span>☰</span>
              <span class="hidden sm:inline">Tabelle</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-20 liquid_glass_card rounded-3xl">
      <div class="inline-block animate-spin text-3xl mb-3">📇</div>
      <p class="text-xs font-bold text-slate-700">Kontakte werden geladen...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredContacts.length === 0" class="text-center py-16 px-6 liquid_glass_card rounded-3xl">
      <div class="w-16 h-16 rounded-2xl bg-cyan-50 text-[#00A3C4] flex items-center justify-center text-3xl font-black mx-auto mb-3 shadow-xs">
        📇
      </div>
      <h3 class="text-base font-black text-slate-900">Keine Kontakte gefunden</h3>
      <p class="text-xs text-slate-600 max-w-md mx-auto mt-1 mb-6">
        {{ searchFilter || selectedGroup || selectedProjectId || selectedScope ? 'Für die ausgewählten Filterkriterien wurden keine Kontakte gefunden.' : 'Erfasse deine Handwerker, Bauleiter, Partner und Behörden, um sie schnell griffbereit zu haben.' }}
      </p>
      <button
        @click="openCreateModal()"
        class="taskster_button px-6 text-xs h-[42px] rounded-lg"
      >
        + Ersten Kontakt anlegen
      </button>
    </div>

    <!-- CARDS VIEW -->
    <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="c in filteredContacts"
        :key="c.id"
        class="liquid_glass_card rounded-3xl p-5 shadow-md flex flex-col justify-between hover:shadow-xl transition group"
      >
        <div>
          <!-- Card Top: Avatar, Name, Company, Function -->
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="flex items-start space-x-3 min-w-0">
              <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#00A3C4] to-teal-500 text-white flex items-center justify-center font-black text-sm shadow-sm shrink-0">
                {{ getInitials(c) }}
              </div>
              <div class="min-w-0">
                <h3 class="text-sm font-black text-slate-900 truncate leading-tight">
                  {{ formatFullName(c) }}
                </h3>
                <p v-if="c.company_name" class="text-xs font-bold text-cyan-800 truncate mt-0.5">
                  🏢 {{ c.company_name }}
                </p>
                <p v-if="c.role_function" class="text-[11px] font-semibold text-slate-600 truncate mt-0.5">
                  👷 {{ c.role_function }}
                </p>
              </div>
            </div>

            <!-- Scope / Sharing Badge -->
            <span
              class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold border"
              :class="c.share_scope === 'company' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
              :title="c.share_scope === 'company' ? 'Im Unternehmen freigegeben' : 'Nur für mich und zugewiesene Projektmitglieder'"
            >
              {{ c.share_scope === 'company' ? '🏢 Team' : '🔒 Privat' }}
            </span>
          </div>

          <!-- Group & Tags Badges -->
          <div class="flex flex-wrap items-center gap-1.5 mb-3">
            <span v-if="c.category_group" class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
              🏷️ {{ c.category_group }}
            </span>
            <span
              v-for="(tag, idx) in c.tags"
              :key="idx"
              class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-cyan-50 text-cyan-900 border border-cyan-200"
            >
              #{{ tag }}
            </span>
          </div>

          <!-- Project Link Badge if present -->
          <div v-if="c.project_id" class="mb-3">
            <NuxtLink
              :to="`/projects/${c.project_id}`"
              class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-900 border border-blue-200 transition"
              title="Zum Projekt wechseln"
            >
              <span>📋</span>
              <span class="truncate max-w-[190px]">{{ c.project_title || 'Projekt' }}</span>
              <span class="text-blue-500">→</span>
            </NuxtLink>
          </div>

          <!-- Contact Details (Phone, Mobile, Email) -->
          <div class="space-y-1.5 text-xs text-slate-700 bg-white/70 p-3 rounded-2xl border border-white/80 mb-3">
            <div v-if="c.mobile" class="flex items-center space-x-2">
              <span class="text-slate-400">📱</span>
              <a :href="`tel:${c.mobile}`" class="font-bold text-[#00A3C4] hover:underline truncate">
                {{ c.mobile }}
              </a>
              <a :href="`https://wa.me/${cleanPhoneForWhatsApp(c.mobile)}`" target="_blank" rel="noopener" class="text-[10px] text-emerald-700 hover:text-emerald-900 font-bold ml-auto" title="WhatsApp Chat öffnen">
                WhatsApp
              </a>
            </div>

            <div v-if="c.phone" class="flex items-center space-x-2">
              <span class="text-slate-400">📞</span>
              <a :href="`tel:${c.phone}`" class="font-semibold text-slate-800 hover:underline truncate">
                {{ c.phone }}
              </a>
            </div>

            <div v-if="c.email" class="flex items-center space-x-2">
              <span class="text-slate-400">✉️</span>
              <a :href="`mailto:${c.email}`" class="font-semibold text-cyan-800 hover:underline truncate">
                {{ c.email }}
              </a>
            </div>

            <div v-if="!c.mobile && !c.phone && !c.email" class="text-[11px] text-slate-400 italic">
              Keine Telefonnummer oder E-Mail hinterlegt
            </div>
          </div>

          <!-- Notes -->
          <p v-if="c.notes" class="text-[11px] text-slate-600 line-clamp-2 italic mb-3">
            "{{ c.notes }}"
          </p>
        </div>

        <!-- Card Footer / Actions -->
        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <!-- Download vCard Action -->
          <button
            @click="exportSingleVCard(c)"
            type="button"
            class="text-[11px] font-bold text-slate-600 hover:text-[#00A3C4] flex items-center space-x-1 py-1 px-2 rounded-lg hover:bg-cyan-50 transition cursor-pointer"
            title="Als digitale Visitenkarte (.vcf) herunterladen"
          >
            <span>📥</span>
            <span>vCard</span>
          </button>

          <!-- Edit & Delete -->
          <div class="flex items-center space-x-1.5">
            <button
              v-if="c.can_edit"
              @click="openEditModal(c)"
              type="button"
              class="p-1.5 text-slate-500 hover:text-[#00A3C4] hover:bg-cyan-50 rounded-lg transition text-xs font-bold"
              title="Kontakt bearbeiten"
            >
              ✏️
            </button>
            <button
              v-if="c.can_edit"
              @click="deleteContact(c)"
              type="button"
              class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition text-xs font-bold"
              title="Kontakt löschen"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TABLE VIEW -->
    <div v-else class="liquid_glass_card rounded-3xl overflow-hidden shadow-md">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200/80 bg-slate-50/70 text-[11px] font-black uppercase tracking-wider text-slate-500">
              <th class="py-3.5 px-4 sm:px-6">Name / Firma</th>
              <th class="py-3.5 px-4">Funktion</th>
              <th class="py-3.5 px-4">Telefon & Mobile</th>
              <th class="py-3.5 px-4">E-Mail</th>
              <th class="py-3.5 px-4">Projekt</th>
              <th class="py-3.5 px-4">Freigabe</th>
              <th class="py-3.5 px-4 sm:px-6 text-right">Aktionen</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr
              v-for="c in filteredContacts"
              :key="c.id"
              class="hover:bg-white/80 transition-colors group"
            >
              <td class="py-3 px-4 sm:px-6">
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#00A3C4] to-teal-500 text-white flex items-center justify-center font-black text-xs shrink-0">
                    {{ getInitials(c) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-slate-900 truncate">{{ formatFullName(c) }}</p>
                    <p v-if="c.company_name" class="text-[11px] text-cyan-800 font-semibold truncate">{{ c.company_name }}</p>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4">
                <span v-if="c.role_function" class="font-medium text-slate-700">{{ c.role_function }}</span>
                <span v-else class="text-slate-400 italic">-</span>
              </td>
              <td class="py-3 px-4">
                <div class="space-y-0.5">
                  <div v-if="c.mobile">
                    <a :href="`tel:${c.mobile}`" class="text-[#00A3C4] font-bold hover:underline">
                      {{ c.mobile }}
                    </a>
                  </div>
                  <div v-if="c.phone">
                    <a :href="`tel:${c.phone}`" class="text-slate-700 hover:underline">
                      {{ c.phone }}
                    </a>
                  </div>
                  <span v-if="!c.mobile && !c.phone" class="text-slate-400 italic">-</span>
                </div>
              </td>
              <td class="py-3 px-4">
                <a v-if="c.email" :href="`mailto:${c.email}`" class="text-cyan-800 font-semibold hover:underline truncate block max-w-[180px]">
                  {{ c.email }}
                </a>
                <span v-else class="text-slate-400 italic">-</span>
              </td>
              <td class="py-3 px-4">
                <NuxtLink
                  v-if="c.project_id"
                  :to="`/projects/${c.project_id}`"
                  class="text-blue-900 font-bold hover:underline truncate block max-w-[160px]"
                >
                  📋 {{ c.project_title || 'Projekt' }}
                </NuxtLink>
                <span v-else class="text-slate-400 italic">-</span>
              </td>
              <td class="py-3 px-4">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border inline-block"
                  :class="c.share_scope === 'company' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                >
                  {{ c.share_scope === 'company' ? '🏢 Team' : '🔒 Privat' }}
                </span>
              </td>
              <td class="py-3 px-4 sm:px-6 text-right">
                <div class="flex items-center justify-end space-x-1">
                  <button
                    @click="exportSingleVCard(c)"
                    type="button"
                    class="p-1.5 text-slate-500 hover:text-[#00A3C4] hover:bg-cyan-50 rounded-lg transition text-xs font-bold"
                    title="vCard Visitenkarte herunterladen"
                  >
                    📥
                  </button>
                  <button
                    v-if="c.can_edit"
                    @click="openEditModal(c)"
                    type="button"
                    class="p-1.5 text-slate-500 hover:text-[#00A3C4] hover:bg-cyan-50 rounded-lg transition text-xs font-bold"
                    title="Bearbeiten"
                  >
                    ✏️
                  </button>
                  <button
                    v-if="c.can_edit"
                    @click="deleteContact(c)"
                    type="button"
                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition text-xs font-bold"
                    title="Löschen"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: Create / Edit Contact -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5 shrink-0">
          <div>
            <h3 class="text-lg font-black text-slate-900 flex items-center space-x-2">
              <span>{{ isEditing ? '✏️' : '📇' }}</span>
              <span>{{ isEditing ? 'Kontakt bearbeiten' : 'Neuen Kontakt anlegen' }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Erfasse alle Kontaktdaten für die Baustelle oder das Projekt.
            </p>
          </div>
          <button
            @click="showModal = false"
            type="button"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <!-- Modal Form Scrollable Body -->
        <form @submit.prevent="saveContact" class="space-y-4 overflow-y-auto pr-1 flex-1">
          <!-- Error banner if any -->
          <div v-if="modalError" class="p-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-900 text-xs font-bold">
            {{ modalError }}
          </div>

          <!-- KI-Autofill Assistent Box -->
          <div class="p-3.5 rounded-2xl bg-gradient-to-r from-cyan-50 via-teal-50 to-blue-50 border border-cyan-200 shadow-xs">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center space-x-2">
                <span class="text-base">✨</span>
                <span class="text-xs font-black text-slate-900">KI-Autofill Assistent</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-[#00A3C4] text-white">DeepSeek V4</span>
              </div>
              <button
                @click="showAiInput = !showAiInput"
                type="button"
                class="text-[11px] font-bold text-cyan-800 hover:text-cyan-950 underline cursor-pointer"
              >
                {{ showAiInput ? 'Eingabe schließen' : 'Freitext / Signatur einfügen' }}
              </button>
            </div>

            <div v-if="showAiInput" class="space-y-2.5 mt-3 pt-3 border-t border-cyan-200/60">
              <p class="text-[11px] text-slate-600">
                Füge eine E-Mail-Signatur, Notizen von der Baustelle oder rohen Text ein. Die KI extrahiert Name, Firma, Telefonnummern, E-Mail und Funktion automatisch:
              </p>
              <textarea
                v-model="aiRawText"
                rows="3"
                placeholder="Beispiel: Hans Peter, Bauleiter bei Steiner Tiefbau AG in Zürich, Tel 044 123 45 67, Mobile 079 987 65 43, h.peter@steiner.ch - zuständig für Grabenbau"
                class="w-full px-3 py-2 bg-white border border-cyan-300 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              ></textarea>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span v-if="aiStatusMessage" class="text-[11px] font-bold" :class="aiStatusSuccess ? 'text-emerald-700' : 'text-rose-600'">
                  {{ aiStatusMessage }}
                </span>
                <span v-else class="text-[10px] text-slate-400">Texte werden sicher verarbeitet</span>

                <button
                  @click="runAiExtraction"
                  type="button"
                  :disabled="aiLoading || !aiRawText.trim()"
                  class="taskster_button px-4 text-xs h-[36px] rounded-lg self-end sm:self-auto"
                >
                  <span v-if="aiLoading" class="animate-spin text-sm">⏳</span>
                  <span v-else>✨</span>
                  <span>{{ aiLoading ? 'KI analysiert...' : 'Automatisch ausfüllen' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Name & Vorname & Firma -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">
                Nachname / Name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.last_name"
                type="text"
                required
                placeholder="z.B. Müller"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Vorname</label>
              <input
                v-model="form.first_name"
                type="text"
                placeholder="z.B. Hans"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Firma / Organisation</label>
              <input
                v-model="form.company_name"
                type="text"
                placeholder="z.B. Tiefbau Schweiz AG"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Funktion / Gewerk</label>
              <input
                v-model="form.role_function"
                type="text"
                placeholder="z.B. Bauleiter, Polier, Architekt"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <!-- Telefone & Email -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Mobiltelefon (Handy)</label>
              <input
                v-model="form.mobile"
                type="tel"
                placeholder="+41 79 123 45 67"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Festnetz / Telefon</label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="+41 44 123 45 67"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">E-Mail</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="kontakt@firma.ch"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <!-- Projektzugehörigkeit & Gruppe -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Projektzugehörigkeit (optional)</label>
              <select
                v-model="form.project_id"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4] cursor-pointer"
              >
                <option value="">-- Keine Projektzuordnung --</option>
                <option v-for="p in availableProjects" :key="p.id" :value="p.id">
                  {{ p.title }}
                </option>
              </select>
              <p class="text-[10px] text-slate-500 mt-1">
                Mitglieder dieses Projekts oder Ordners können diesen Kontakt automatisch einsehen.
              </p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Gruppe / Kategorie</label>
              <div class="relative">
                <input
                  v-model="form.category_group"
                  type="text"
                  list="group-suggestions"
                  placeholder="z.B. Handwerker, Planer"
                  class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
                />
                <datalist id="group-suggestions">
                  <option v-for="g in groupOptions" :key="g" :value="g" />
                </datalist>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Tags / Schlagwörter</label>
            <div class="flex flex-wrap items-center gap-1.5 p-2 bg-slate-50 border border-slate-200 rounded-xl min-h-[42px]">
              <span
                v-for="(t, idx) in form.tags"
                :key="idx"
                class="px-2 py-0.5 rounded-lg text-xs font-bold bg-[#00A3C4] text-white inline-flex items-center space-x-1"
              >
                <span>#{{ t }}</span>
                <button @click="removeTag(idx)" type="button" class="hover:text-rose-200 text-xs font-black">×</button>
              </span>
              <input
                v-model="newTagInput"
                @keydown.enter.prevent="addTag"
                @keydown.comma.prevent="addTag"
                type="text"
                placeholder="Tag tippen + Enter..."
                class="text-xs font-medium bg-transparent border-none focus:outline-none text-slate-800 placeholder-slate-400 flex-1 min-w-[120px]"
              />
            </div>
          </div>

          <!-- Notizen -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Notizen & Bemerkungen</label>
            <textarea
              v-model="form.notes"
              rows="3"
              placeholder="Besondere Absprachen, Arbeitszeiten, Erreichbarkeit..."
              class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            ></textarea>
          </div>

          <!-- Sharing Toggle: Im Unternehmen teilen -->
          <div class="p-3.5 rounded-2xl bg-cyan-50/70 border border-cyan-200/80 flex items-start space-x-3">
            <input
              id="shareScopeCheck"
              v-model="form.is_company_shared"
              type="checkbox"
              class="mt-1 h-4 w-4 rounded border-slate-300 text-[#00A3C4] focus:ring-[#00A3C4]"
            />
            <label for="shareScopeCheck" class="text-xs text-slate-800 cursor-pointer">
              <span class="font-bold block">🏢 Im gesamten Unternehmen freigeben</span>
              <span class="text-[11px] text-slate-600 block mt-0.5">
                Wenn aktiviert, können alle Kollegen deines Unternehmens diesen Kontakt sehen und nutzen.
              </span>
            </label>
          </div>

          <!-- Modal Footer Buttons per Design Rule -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 shrink-0">
            <button
              @click="showModal = false"
              type="button"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ saving ? 'Speichert...' : (isEditing ? 'Änderungen speichern' : 'Kontakt erstellen') }}
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
const contacts = ref<any[]>([])
const availableProjects = ref<any[]>([])

// Filters
const searchFilter = ref('')
const selectedGroup = ref('')
const selectedProjectId = ref('')
const selectedScope = ref('')
const viewMode = ref<'cards' | 'table'>('cards')

// Modal State
const showModal = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const modalError = ref('')
const newTagInput = ref('')

// AI Autofill State
const showAiInput = ref(false)
const aiRawText = ref('')
const aiLoading = ref(false)
const aiStatusMessage = ref('')
const aiStatusSuccess = ref(false)

const form = ref({
  id: '',
  first_name: '',
  last_name: '',
  company_name: '',
  role_function: '',
  phone: '',
  mobile: '',
  email: '',
  project_id: '',
  category_group: 'Handwerker',
  tags: [] as string[],
  notes: '',
  is_company_shared: true
})

const groupOptions = [
  'Handwerker',
  'Bauleitung',
  'Planer & Architekten',
  'Ingenieure & Geometer',
  'Behörden & Ämter',
  'Bauträger & Eigentümer',
  'Lieferanten & Logistik',
  'Sicherheitsbeauftragte',
  'Sonstige'
]

// Counters
const sharedCompanyCount = computed(() => contacts.value.filter(c => c.share_scope === 'company').length)
const projectLinkedCount = computed(() => contacts.value.filter(c => Boolean(c.project_id)).length)
const privateCount = computed(() => contacts.value.filter(c => c.share_scope !== 'company').length)

// Filtered Contacts
const filteredContacts = computed(() => {
  let list = contacts.value

  if (selectedGroup.value) {
    list = list.filter(c => c.category_group === selectedGroup.value)
  }

  if (selectedProjectId.value) {
    list = list.filter(c => c.project_id === selectedProjectId.value)
  }

  if (selectedScope.value) {
    list = list.filter(c => c.share_scope === selectedScope.value)
  }

  if (searchFilter.value.trim()) {
    const q = searchFilter.value.toLowerCase().trim()
    list = list.filter(c => {
      const full = `${c.first_name || ''} ${c.last_name || ''}`.toLowerCase()
      const comp = (c.company_name || '').toLowerCase()
      const role = (c.role_function || '').toLowerCase()
      const mail = (c.email || '').toLowerCase()
      const phone = (c.phone || '').toLowerCase()
      const mobile = (c.mobile || '').toLowerCase()
      const tagsStr = (c.tags || []).join(' ').toLowerCase()
      return full.includes(q) || comp.includes(q) || role.includes(q) || mail.includes(q) || phone.includes(q) || mobile.includes(q) || tagsStr.includes(q)
    })
  }

  return list
})

const getInitials = (c: any) => {
  const f = (c.first_name || '').charAt(0).toUpperCase()
  const l = (c.last_name || '').charAt(0).toUpperCase()
  return (f + l) || '?'
}

const formatFullName = (c: any) => {
  if (c.first_name && c.last_name) return `${c.first_name} ${c.last_name}`
  return c.last_name || c.first_name || 'Unbenannt'
}

const cleanPhoneForWhatsApp = (num: string) => {
  return (num || '').replace(/[^0-9]/g, '')
}

// Tag handling
const addTag = () => {
  const val = newTagInput.value.trim().replace(/^#/, '')
  if (val && !form.value.tags.includes(val)) {
    form.value.tags.push(val)
  }
  newTagInput.value = ''
}

const removeTag = (idx: number) => {
  form.value.tags.splice(idx, 1)
}

// Open Modal
const openCreateModal = (defaultProjectId?: string) => {
  isEditing.value = false
  modalError.value = ''
  newTagInput.value = ''
  showAiInput.value = false
  aiRawText.value = ''
  aiStatusMessage.value = ''
  aiStatusSuccess.value = false
  form.value = {
    id: '',
    first_name: '',
    last_name: '',
    company_name: '',
    role_function: '',
    phone: '',
    mobile: '',
    email: '',
    project_id: defaultProjectId || '',
    category_group: 'Handwerker',
    tags: [],
    notes: '',
    is_company_shared: Boolean(user.value?.company_id)
  }
  showModal.value = true
}

const openEditModal = (contact: any) => {
  isEditing.value = true
  modalError.value = ''
  newTagInput.value = ''
  showAiInput.value = false
  aiRawText.value = ''
  aiStatusMessage.value = ''
  aiStatusSuccess.value = false
  form.value = {
    id: contact.id,
    first_name: contact.first_name || '',
    last_name: contact.last_name || '',
    company_name: contact.company_name || '',
    role_function: contact.role_function || '',
    phone: contact.phone || '',
    mobile: contact.mobile || '',
    email: contact.email || '',
    project_id: contact.project_id || '',
    category_group: contact.category_group || 'Handwerker',
    tags: Array.isArray(contact.tags) ? [...contact.tags] : [],
    notes: contact.notes || '',
    is_company_shared: contact.share_scope === 'company'
  }
  showModal.value = true
}

const runAiExtraction = async () => {
  if (!aiRawText.value.trim()) return
  aiLoading.value = true
  aiStatusMessage.value = 'KI extrahiert Daten...'
  aiStatusSuccess.value = false

  try {
    const res = await $fetch<{ success: boolean; text: string }>('/api/ai/chat', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        prompt: `Extrahiere alle Kontaktdaten aus folgendem Text und gib ein valides JSON-Objekt mit genau folgenden Feldern zurück:
- first_name: string (Vorname, falls vorhanden)
- last_name: string (Nachname oder Firmenname falls kein Personenname)
- company_name: string (Firma/Organisation)
- role_function: string (Funktion/Gewerk/Berufsbezeichnung)
- phone: string (Festnetznummer)
- mobile: string (Mobilfunknummer)
- email: string (E-Mail)
- category_group: string (wähle passend aus: 'Handwerker', 'Bauleitung', 'Planer & Architekten', 'Ingenieure & Geometer', 'Behörden & Ämter', 'Bauträger & Eigentümer', 'Lieferanten & Logistik', 'Sicherheitsbeauftragte', 'Sonstige')
- tags: string[] (passende Schlagworte / Spezialisierungen)
- notes: string (Zusätzliche nützliche Notizen)

Text:
"""
${aiRawText.value.trim()}
"""`,
        json: true,
        system: 'Du bist ein intelligenter Assistent für Baudokumentation und Kontaktmanagement. Antworte ausschliesslich mit einem JSON-Objekt ohne Markdown-Formatierung.'
      }
    })

    if (!res || !res.text) {
      throw new Error('Keine Antwort von der KI erhalten.')
    }

    let parsed: any = null
    try {
      const clean = res.text.replace(/^```(?:json)?\s*/i, '').replace(/```$/, '').trim()
      parsed = JSON.parse(clean)
    } catch {
      throw new Error('KI-Rückgabe konnte nicht als JSON interpretiert werden.')
    }

    if (parsed) {
      if (parsed.first_name) form.value.first_name = String(parsed.first_name).trim()
      if (parsed.last_name) form.value.last_name = String(parsed.last_name).trim()
      if (parsed.company_name) form.value.company_name = String(parsed.company_name).trim()
      if (parsed.role_function) form.value.role_function = String(parsed.role_function).trim()
      if (parsed.phone) form.value.phone = String(parsed.phone).trim()
      if (parsed.mobile) form.value.mobile = String(parsed.mobile).trim()
      if (parsed.email) form.value.email = String(parsed.email).trim()
      if (parsed.category_group) form.value.category_group = String(parsed.category_group).trim()
      if (Array.isArray(parsed.tags) && parsed.tags.length > 0) {
        const set = new Set([...form.value.tags, ...parsed.tags.map((t: any) => String(t).trim())])
        form.value.tags = Array.from(set).filter(Boolean)
      }
      if (parsed.notes) {
        form.value.notes = form.value.notes
          ? `${form.value.notes}\n${parsed.notes}`
          : parsed.notes
      }

      aiStatusSuccess.value = true
      aiStatusMessage.value = '✓ Daten erfolgreich erkannt und ins Formular übertragen!'
    }
  } catch (err: any) {
    aiStatusSuccess.value = false
    aiStatusMessage.value = err.data?.statusMessage || err.message || 'Fehler bei der KI-Erkennung.'
  } finally {
    aiLoading.value = false
  }
}

// API Calls
const loadContacts = async () => {
  loading.value = true
  try {
    const res = await $fetch<{ contacts: any[] }>('/api/contacts', {
      headers: authHeaders()
    })
    contacts.value = res.contacts || []
  } catch (err) {
    contacts.value = []
  } finally {
    loading.value = false
  }
}

const loadAvailableProjects = async () => {
  try {
    const res = await $fetch<{ projects: any[] }>('/api/projects', {
      headers: authHeaders()
    })
    availableProjects.value = res.projects || []
  } catch (err) {
    availableProjects.value = []
  }
}

const saveContact = async () => {
  if (!form.value.last_name.trim()) {
    modalError.value = 'Bitte gib mindestens einen Nachnamen oder Firmennamen ein.'
    return
  }

  saving.value = true
  modalError.value = ''

  const payload = {
    first_name: form.value.first_name.trim(),
    last_name: form.value.last_name.trim(),
    company_name: form.value.company_name.trim(),
    role_function: form.value.role_function.trim(),
    phone: form.value.phone.trim(),
    mobile: form.value.mobile.trim(),
    email: form.value.email.trim(),
    project_id: form.value.project_id || null,
    category_group: form.value.category_group,
    tags: form.value.tags,
    notes: form.value.notes.trim(),
    share_scope: form.value.is_company_shared ? 'company' : 'private'
  }

  try {
    if (isEditing.value) {
      await $fetch(`/api/contacts/${form.value.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: payload
      })
    } else {
      await $fetch('/api/contacts', {
        method: 'POST',
        headers: authHeaders(),
        body: payload
      })
    }

    showModal.value = false
    await loadContacts()
  } catch (err: any) {
    modalError.value = err.data?.statusMessage || 'Fehler beim Speichern des Kontakts'
  } finally {
    saving.value = false
  }
}

const deleteContact = async (c: any) => {
  const name = formatFullName(c)
  if (!confirm(`Möchtest du den Kontakt "${name}" wirklich löschen?`)) return
  try {
    await $fetch(`/api/contacts/${c.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    contacts.value = contacts.value.filter(item => item.id !== c.id)
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Kontakts')
  }
}

// vCard (.vcf) Export
const exportSingleVCard = (c: any) => {
  const fullName = formatFullName(c)
  const vcardLines = [
    'BEGIN:VCARD',
    'VERSION:3.0',
    `N:${c.last_name || ''};${c.first_name || ''};;;`,
    `FN:${fullName}`,
    c.company_name ? `ORG:${c.company_name}` : '',
    c.role_function ? `TITLE:${c.role_function}` : '',
    c.phone ? `TEL;TYPE=WORK,VOICE:${c.phone}` : '',
    c.mobile ? `TEL;TYPE=CELL,VOICE:${c.mobile}` : '',
    c.email ? `EMAIL;TYPE=WORK,INTERNET:${c.email}` : '',
    c.notes ? `NOTE:${c.notes.replace(/\n/g, '\\n')}` : '',
    'END:VCARD'
  ].filter(Boolean)

  const vcfContent = vcardLines.join('\r\n')
  const blob = new Blob([vcfContent], { type: 'text/vcard;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${fullName.replace(/\s+/g, '_')}.vcf`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
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
  await Promise.all([loadContacts(), loadAvailableProjects()])
})
</script>
