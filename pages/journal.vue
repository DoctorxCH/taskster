<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <!-- Top Action Card -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 p-6 rounded-3xl shadow-xs">
      <div>
        <div class="flex items-center space-x-2.5">
          <span class="text-2xl">📖</span>
          <div>
            <h1 class="text-xl font-black text-slate-900">Projektjournal & Logbuch</h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Zentrale Übersicht aller Bausitzungen, Notizen und Journaleinträge über alle Ordner und Projekte.
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5 shrink-0">
        <button
          @click="openJournalNoteModal"
          type="button"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5 cursor-pointer shadow-xs"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>+ Journaleintrag</span>
        </button>
        <button
          @click="openJournalEntryModal"
          type="button"
          class="taskster_button_light px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5 cursor-pointer"
        >
          <Sparkles class="w-3.5 h-3.5 text-[#00A3C4]" />
          <span>+ Dokument / Protokoll (KI)</span>
        </button>
      </div>
    </div>

    <!-- Filter & Toolbar (Ordner wählen, Suche & Typ-Filter) -->
    <div class="bg-white border border-slate-200 rounded-3xl p-4 sm:p-5 space-y-4 shadow-xs">
      <!-- Tier 1: Folder Selector ("Ordner wählen") & Search -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
        <!-- Ordner wählen Dropdown -->
        <div class="md:col-span-5">
          <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
            Ordner wählen
          </label>
          <div class="relative">
            <select
              v-model="selectedFolderId"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option value="">📁 Alle Ordner anzeigen (Gesamtübersicht)</option>
              <option v-for="f in folders" :key="f.id" :value="f.id">
                📁 {{ f.name }} ({{ getFolderProjectCount(f.id) }} Projekte)
              </option>
            </select>
          </div>
        </div>

        <!-- Project Filter within Folder (if folder selected) -->
        <div :class="selectedFolderId ? 'md:col-span-3' : 'hidden'">
          <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
            Projekt filtern
          </label>
          <select
            v-model="selectedProjectId"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          >
            <option value="">Alle Projekte im Ordner</option>
            <option value="none">Nur reine Ordner-Einträge (ohne Projekt)</option>
            <option v-for="p in currentFolderProjects" :key="p.id" :value="p.id">
              {{ p.title }}
            </option>
          </select>
        </div>

        <!-- Full-text Search -->
        <div :class="selectedFolderId ? 'md:col-span-4' : 'md:col-span-7'">
          <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">
            Journal durchsuchen
          </label>
          <div class="relative w-full">
            <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3.5 top-3" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Im Journal, Text, Titel, Autor oder Projekt suchen..."
              class="w-full pl-9 pr-7 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Tier 2: Main Type Tabs & Category / Sorting Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
        <!-- Type Tabs -->
        <div class="flex items-center p-1 bg-slate-100 rounded-2xl space-x-1 overflow-x-auto">
          <button
            type="button"
            @click="filterType = 'all'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center space-x-1.5 shrink-0"
            :class="filterType === 'all' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
          >
            <span>Alle Einträge</span>
            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-slate-200/80 text-slate-700 font-extrabold">
              {{ allJournals.length }}
            </span>
          </button>
          <button
            type="button"
            @click="filterType = 'entry'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center space-x-1.5 shrink-0"
            :class="filterType === 'entry' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
          >
            <span>🏛️ Bausitzungen & Protokolle</span>
            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-slate-200/80 text-slate-700 font-extrabold">
              {{ entriesCount }}
            </span>
          </button>
          <button
            type="button"
            @click="filterType = 'note'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center space-x-1.5 shrink-0"
            :class="filterType === 'note' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
          >
            <span>✉️ Notizen & E-Mails</span>
            <span class="px-1.5 py-0.2 text-[10px] rounded-full bg-slate-200/80 text-slate-700 font-extrabold">
              {{ notesCount }}
            </span>
          </button>
        </div>

        <!-- Category & Sort Dropdowns -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <div class="flex items-center gap-1.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kategorie:</span>
            <select
              v-model="categoryFilter"
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#00A3C4] cursor-pointer"
            >
              <option value="">Alle Kategorien</option>
              <option value="bausitzung">🏛️ Bausitzung</option>
              <option value="bautagebuch">📋 Bautagebuch</option>
              <option value="abnahmebegehung">🔍 Abnahmebegehung</option>
              <option value="wetter_behinderung">⛈️ Wetter & Behinderung</option>
              <option value="regie">⏱️ Regiearbeit</option>
              <option value="email">✉️ E-Mail Import</option>
              <option value="notiz">📝 Notiz</option>
              <option value="mangel">⚠️ Mangel / Behinderung</option>
              <option value="allgemein">📖 Allgemein</option>
            </select>
          </div>

          <div class="flex items-center gap-1.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sortierung:</span>
            <select
              v-model="journalSortBy"
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#00A3C4] cursor-pointer"
            >
              <option value="date_desc">📅 Datum (Neueste zuerst)</option>
              <option value="date_asc">📅 Datum (Älteste zuerst)</option>
              <option value="project">📁 Auftrag / Projekt (A-Z)</option>
              <option value="category">🏷️ Kategorie</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-16">
      <div class="inline-block w-8 h-8 border-4 border-[#00A3C4] border-t-transparent rounded-full animate-spin"></div>
      <p class="text-xs text-slate-500 mt-2 font-medium">Journaleinträge werden geladen...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="filteredJournals.length === 0"
      class="text-center py-16 px-6 bg-white border border-dashed border-slate-300 rounded-3xl max-w-xl mx-auto shadow-xs"
    >
      <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-50 text-[#00A3C4] flex items-center justify-center mb-4 border border-cyan-200 shadow-2xs">
        <BookOpen class="w-7 h-7" />
      </div>
      <h3 class="text-base font-bold text-slate-900">Keine passenden Journaleinträge gefunden</h3>
      <p class="text-xs text-slate-500 mt-1.5 mb-6 leading-relaxed">
        Erfasse eine Bausitzung, ein Bautagebuch oder importiere eine E-Mail mit automatischer KI-Aktionserkennung.
      </p>
      <div class="flex items-center justify-center gap-3">
        <button
          @click="openJournalNoteModal"
          type="button"
          class="taskster_button px-5 text-xs h-[40px] rounded-lg flex items-center space-x-1.5 cursor-pointer shadow-xs"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>+ Journaleintrag</span>
        </button>
        <button
          @click="openJournalEntryModal"
          type="button"
          class="taskster_button_light px-5 text-xs h-[40px] rounded-lg flex items-center space-x-1.5 cursor-pointer"
        >
          <Sparkles class="w-3.5 h-3.5 text-[#00A3C4]" />
          <span>+ Dokument / Protokoll (KI)</span>
        </button>
      </div>
    </div>

    <!-- Journal Entries Stream (2-Column Grid matching Screenshot 2 / Project Journal) -->
    <div v-else class="space-y-4">
      <div
        v-for="entry in filteredJournals"
        :key="entry.id"
        class="bg-white border border-slate-200/90 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-md transition-all relative overflow-hidden"
        :class="[
          entry.type === 'entry' ? 'border-l-4 border-l-[#00A3C4]' : (entry.category === 'email' ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-indigo-400')
        ]"
      >
        <!-- Card Top Bar: Icon, Title, Badges, Author, Date, Quick Action Buttons -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
          <div class="flex items-start space-x-3 min-w-0">
            <div class="text-2xl shrink-0 mt-0.5">
              {{ getCategoryIcon(entry.category, entry.type) }}
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <h4 class="text-base font-bold text-slate-900 leading-snug">{{ entry.title || 'Ohne Titel' }}</h4>
                <span
                  class="text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full border tracking-wider"
                  :class="categoryBadgeClass(entry.category)"
                >
                  {{ categoryLabel(entry.category) }}
                </span>
              </div>
              <div class="text-xs text-slate-400 flex flex-wrap items-center gap-x-2.5 gap-y-0.5">
                <span>Von <strong class="text-slate-700 font-semibold">{{ entry.author_name || 'Benutzer' }}</strong></span>
                <span>•</span>
                <span>{{ formatDate(entry.entry_date || entry.created_at) }}</span>
                <span v-if="isEdited(entry)" class="text-[11px] font-medium text-slate-400 italic">
                  • bearbeitet {{ formatDateTime(entry.updated_at) }}
                </span>
                <span v-if="entry.task_title" class="text-[#00A3C4] font-semibold flex items-center space-x-1">
                  <span>• Verknüpft: {{ entry.task_title }}</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Top-Right Actions: Folder / Project link, AI Trigger, Edit, Visibility, Delete -->
          <div class="flex items-center space-x-2 shrink-0 self-end sm:self-start">
            <NuxtLink
              v-if="entry.folder_id"
              :to="`/folders/${entry.folder_id}`"
              class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 flex items-center gap-1 transition"
              title="Zum Ordner springen"
            >
              <Folder class="w-3 h-3 text-slate-500" />
              <span class="max-w-[110px] truncate">{{ entry.folder_name || 'Ordner' }}</span>
            </NuxtLink>

            <NuxtLink
              v-if="entry.project_id"
              :to="`/projects/${entry.project_id}`"
              class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-cyan-50 hover:bg-cyan-100 text-[#00A3C4] border border-cyan-200 flex items-center gap-1 transition"
              title="Zum Projekt springen"
            >
              <span class="max-w-[110px] truncate">📁 {{ entry.project_title || 'Projekt' }}</span>
            </NuxtLink>

            <button
              type="button"
              @click="triggerAiAnalysis(entry)"
              :disabled="analyzingEntryId === entry.id"
              class="text-[11px] font-bold px-2.5 py-1 rounded-lg border flex items-center space-x-1 transition cursor-pointer"
              :class="entry.metadata?.ai_summary
                ? 'bg-slate-50 hover:bg-cyan-50 border-slate-200 text-slate-700 hover:text-[#00A3C4]'
                : 'bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 text-white shadow-xs border-transparent'"
              :title="entry.metadata?.ai_summary ? 'KI-Analyse erneut ausführen' : 'Mit KI analysieren'"
            >
              <Sparkles class="w-3 h-3" :class="{ 'animate-spin': analyzingEntryId === entry.id }" />
              <span>{{ analyzingEntryId === entry.id ? 'Analysiere...' : (entry.metadata?.ai_summary ? 'KI aktualisieren' : '⚡ KI-Analyse') }}</span>
            </button>

            <button
              @click="openEditModal(entry)"
              type="button"
              class="p-1 rounded text-slate-400 hover:text-[#00A3C4] hover:bg-cyan-50 transition cursor-pointer"
              title="Eintrag bearbeiten"
            >
              <Pencil class="w-3.5 h-3.5" />
            </button>

            <span
              class="text-[10px] font-bold px-2 py-0.5 rounded-full border flex items-center space-x-1"
              :class="entry.visibility === 'only_me' ? 'bg-slate-100 border-slate-200 text-slate-700' : (entry.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-cyan-50 border-cyan-200 text-[#00A3C4]')"
              :title="entry.visibility"
            >
              <span>{{ entry.visibility === 'only_me' ? '🔒' : (entry.visibility === 'company' ? '🏢' : '🔵') }}</span>
              <span>{{ entry.visibility === 'only_me' ? 'Privat' : (entry.visibility === 'company' ? 'Firma' : 'Öffentlich (Projektleser)') }}</span>
            </span>

            <button
              v-if="canDeleteEntry(entry)"
              @click="confirmDelete(entry)"
              type="button"
              class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
              title="Eintrag löschen"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Card Body Grid: 2 Spalten (Breit links für Inhalt & KI, Schmal rechts für Metadaten & Verknüpfung) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
          <!-- HAUPTBEREICH (BREIT - 8 Spalten): Fokus auf KI-Zusammenfassung, Aktionskarten, Inhalt/Notizen -->
          <div class="lg:col-span-8 space-y-4">
            <!-- AI Summary Box -->
            <div v-if="entry.metadata?.ai_summary" class="p-4 rounded-2xl bg-gradient-to-r from-cyan-50/90 via-teal-50/60 to-blue-50/80 border border-cyan-200/90 shadow-2xs">
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <div class="flex items-center space-x-1.5 text-xs font-black text-cyan-950">
                  <Sparkles class="w-4 h-4 text-[#00A3C4] shrink-0" />
                  <span>KI-Zusammenfassung</span>
                  <span class="text-[10px] font-semibold px-2 py-0.2 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-300 ml-1">KI-Agent</span>
                </div>
                <button
                  type="button"
                  @click="triggerAiAnalysis(entry)"
                  :disabled="analyzingEntryId === entry.id"
                  class="text-[10px] font-bold text-cyan-800 hover:text-cyan-950 hover:underline flex items-center space-x-1 cursor-pointer"
                >
                  <Sparkles class="w-3 h-3" :class="{ 'animate-spin': analyzingEntryId === entry.id }" />
                  <span>{{ analyzingEntryId === entry.id ? 'Aktualisiere...' : 'Neu analysieren' }}</span>
                </button>
              </div>
              <p class="text-xs text-slate-800 leading-relaxed font-sans">
                {{ entry.metadata.ai_summary }}
              </p>
            </div>

            <!-- Interactive AI Action Cards (falls vorhanden) -->
            <div v-if="entry.metadata?.action_items && entry.metadata.action_items.length > 0" class="space-y-2.5">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-600 flex items-center space-x-1.5">
                <span>⚡</span>
                <span>Vorgeschlagene Aktionen (KI-Agent) ({{ entry.metadata.action_items.length }}):</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div
                  v-for="(item, idx) in entry.metadata.action_items"
                  :key="idx"
                  class="p-3 rounded-2xl border transition shadow-xs flex flex-col justify-between"
                  :class="[
                    item.applied ? 'bg-slate-50 border-slate-200 opacity-80' : (
                      item.type === 'create_task' ? 'bg-emerald-50/70 border-emerald-200 hover:border-emerald-400' :
                      item.type === 'update_task' ? 'bg-amber-50/70 border-amber-200 hover:border-amber-400' :
                      'bg-purple-50/70 border-purple-200 hover:border-purple-400'
                    )
                  ]"
                >
                  <div>
                    <div class="flex items-center justify-between gap-1 mb-1">
                      <span
                        class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border"
                        :class="[
                          item.type === 'create_task' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                          item.type === 'update_task' ? 'bg-amber-100 text-amber-800 border-amber-300' :
                          'bg-purple-100 text-purple-800 border-purple-300'
                        ]"
                      >
                        {{ item.type === 'create_task' ? '+ Neue Aufgabe' : (item.type === 'update_task' ? '✏️ Aktualisierung' : '✓ Abschliessen') }}
                      </span>

                      <span v-if="item.applied" class="text-[10px] font-bold text-emerald-700 flex items-center space-x-1">
                        <CheckCircle2 class="w-3 h-3" />
                        <span>Erledigt</span>
                      </span>
                    </div>

                    <h5 class="text-xs font-bold text-slate-900 leading-snug mb-1">
                      {{ item.title }}
                    </h5>

                    <p v-if="item.description || item.reason" class="text-[11px] text-slate-600 line-clamp-2 mb-2 leading-relaxed">
                      {{ item.description || item.reason }}
                    </p>

                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500 mb-2">
                      <span v-if="item.due_date || item.suggested_due_date" class="px-1.5 py-0.5 rounded bg-white/90 border border-slate-200">
                        📅 {{ item.due_date || item.suggested_due_date }}
                      </span>
                      <span v-if="item.priority" class="px-1.5 py-0.5 rounded bg-white/90 border border-slate-200 uppercase">
                        ⚡ {{ item.priority }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Text-Inhalt / E-Mail Body (Cleaned & 5-Line Smooth Collapse) -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Inhalt / Notizen</span>
                <button
                  v-if="hasOriginalText(entry)"
                  type="button"
                  @click="openOriginalView(entry)"
                  class="text-[11px] font-semibold text-slate-700 hover:text-[#00A3C4] bg-slate-100 hover:bg-cyan-50 px-2.5 py-1 rounded-lg border border-slate-200 hover:border-cyan-200 transition cursor-pointer flex items-center gap-1.5"
                  title="Vollständiges Original-Dokument / E-Mail ansehen"
                >
                  <Eye class="w-3.5 h-3.5 text-[#00A3C4]" />
                  <span>Original-Ansicht</span>
                </button>
              </div>

              <div class="relative">
                <div
                  class="text-xs text-slate-800 leading-relaxed bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80 whitespace-pre-wrap font-sans transition-all duration-300 ease-in-out overflow-hidden"
                  :style="isExpanded(entry.id) || !isLongContent(entry.content) ? { maxHeight: '3000px' } : { maxHeight: '6.75rem' }"
                >
                  {{ cleanContent(entry.content) }}
                </div>

                <!-- Soft gradient fade-out over 5th line when collapsed -->
                <div
                  v-if="isLongContent(entry.content) && !isExpanded(entry.id)"
                  class="absolute bottom-0 left-0 right-0 h-9 bg-gradient-to-t from-slate-100 via-slate-100/80 to-transparent pointer-events-none rounded-b-2xl"
                ></div>
              </div>

              <div v-if="isLongContent(entry.content)" class="mt-1.5">
                <button
                  type="button"
                  @click="toggleExpand(entry.id)"
                  class="inline-flex items-center gap-1 text-[11px] font-bold text-[#00A3C4] hover:text-[#008ba8] transition cursor-pointer"
                >
                  <ChevronDown v-if="!isExpanded(entry.id)" class="w-3.5 h-3.5" />
                  <ChevronUp v-else class="w-3.5 h-3.5" />
                  <span>{{ isExpanded(entry.id) ? 'Weniger anzeigen' : 'Mehr anzeigen' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- METADATEN-SIDEBAR (SCHMAL - 4 Spalten): Verknüpfte Aufgabe, Teilnehmer, Anhänge -->
          <div class="lg:col-span-4 space-y-3">
            <!-- 1. Prominentes Aufgaben-Verknüpfungs-Widget -->
            <div
              class="p-3.5 rounded-2xl border transition-all"
              :class="entry.task_id ? 'bg-cyan-50/70 border-cyan-200' : 'bg-slate-50/80 border-slate-200'"
            >
              <div class="flex items-center justify-between text-[11px] font-bold mb-1.5">
                <span class="flex items-center space-x-1" :class="entry.task_id ? 'text-cyan-900' : 'text-slate-600'">
                  <span>📌</span>
                  <span>Verknüpfte Aufgabe</span>
                </span>
                <span
                  class="text-[10px] font-bold uppercase px-2 py-0.2 rounded-md border"
                  :class="entry.task_id ? 'bg-white border-cyan-300 text-cyan-800' : 'bg-amber-50 border-amber-200 text-amber-800'"
                >
                  {{ entry.task_id ? (getTaskSectionTitle(entry.task_id) || 'Zugeordnet') : 'Offen' }}
                </span>
              </div>

              <!-- Zustand A: Aufgabe bereits verknüpft -->
              <div v-if="entry.task_id" class="space-y-2">
                <p class="text-xs font-bold text-slate-900 leading-snug line-clamp-2">
                  {{ entry.task_title || getTaskTitle(entry.task_id) }}
                </p>
                <NuxtLink
                  v-if="entry.project_id"
                  :to="`/projects/${entry.project_id}`"
                  class="inline-flex items-center gap-1 text-[11px] font-bold text-[#00A3C4] hover:underline transition cursor-pointer"
                >
                  <ExternalLink class="w-3 h-3" />
                  <span>Aufgabe im Projekt öffnen</span>
                </NuxtLink>

                <!-- Schnellauswahl zum Ändern oder Lösen der Verknüpfung -->
                <div class="pt-1.5 border-t border-cyan-200/70">
                  <select
                    :value="entry.task_id"
                    :disabled="updatingTaskId === entry.id"
                    @change="updateJournalTaskLink(entry, ($event.target as HTMLSelectElement).value)"
                    class="w-full px-2.5 py-1.5 bg-white border border-cyan-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-cyan-500 cursor-pointer"
                  >
                    <option value="">-- Verknüpfung lösen --</option>
                    <option v-for="t in getAvailableTasksForEntry(entry)" :key="t.id" :value="t.id">
                      {{ t.title }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Zustand B: Keine Aufgabe verknüpft -->
              <div v-else class="space-y-1">
                <label class="text-[10px] text-slate-400 font-semibold block uppercase">Aufgabe zuweisen:</label>
                <select
                  :disabled="updatingTaskId === entry.id"
                  @change="updateJournalTaskLink(entry, ($event.target as HTMLSelectElement).value)"
                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4] cursor-pointer"
                >
                  <option value="">-- Aufgabe auswählen --</option>
                  <option v-for="t in getAvailableTasksForEntry(entry)" :key="t.id" :value="t.id">
                    {{ t.title }}
                  </option>
                </select>
              </div>
            </div>

            <!-- 2. Absender-Details (bei E-Mails) -->
            <div v-if="entry.metadata?.email_sender || entry.metadata?.sender?.email" class="p-3 bg-amber-50/60 rounded-2xl border border-amber-200/70 text-xs text-amber-950 space-y-1">
              <div class="text-[11px] font-bold text-amber-900 uppercase tracking-wider flex items-center space-x-1">
                <span>✉️</span>
                <span>Absender</span>
              </div>
              <div class="font-bold text-slate-900 leading-tight">
                {{ entry.metadata?.sender?.name || entry.metadata?.email_sender || entry.metadata?.sender?.email }}
              </div>
              <div v-if="entry.metadata?.sender?.email" class="text-[11px] text-slate-500 font-mono truncate">
                &lt;{{ entry.metadata.sender.email }}&gt;
              </div>
            </div>

            <!-- 3. Teilnehmerliste (bei Protokollen) -->
            <div v-if="entry.attendees && entry.attendees.length > 0" class="p-3 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                <span class="flex items-center space-x-1">
                  <Users class="w-3 h-3 text-[#00A3C4]" />
                  <span>Teilnehmer</span>
                </span>
                <span class="text-[10px] font-bold px-1.5 py-0.2 rounded-md bg-white border border-slate-200 text-slate-700">
                  {{ entry.attendees.filter(a => a.present).length }} / {{ entry.attendees.length }}
                </span>
              </div>
              <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto pr-1">
                <div
                  v-for="atd in entry.attendees"
                  :key="atd.id || atd.name"
                  class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg text-[11px] font-medium border"
                  :class="atd.present ? 'bg-white border-emerald-300 text-slate-800' : 'bg-slate-100/70 border-slate-200 text-slate-400 line-through'"
                >
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="atd.present ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                  <span class="truncate max-w-[130px]">{{ atd.name }}</span>
                </div>
              </div>
            </div>

            <!-- 4. Dateianhänge -->
            <div v-if="entry.attachments && entry.attachments.length > 0" class="p-3 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-2">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center space-x-1">
                <Paperclip class="w-3 h-3 text-slate-400" />
                <span>Dateianhänge ({{ entry.attachments.length }})</span>
              </div>
              <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                <div
                  v-for="(att, attIdx) in entry.attachments"
                  :key="attIdx"
                  class="flex items-center justify-between p-2 rounded-xl bg-white hover:bg-cyan-50/60 border border-slate-200 hover:border-cyan-300 transition text-xs"
                >
                  <div class="flex items-center space-x-2 min-w-0">
                    <Paperclip class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <a
                      v-if="att.file_path"
                      :href="att.file_path"
                      :download="att.file_name"
                      target="_blank"
                      class="font-medium text-slate-800 hover:text-[#00A3C4] truncate max-w-[150px]"
                    >
                      {{ att.file_name }}
                    </a>
                    <span v-else class="font-medium text-slate-800 truncate max-w-[150px]">{{ att.file_name }}</span>
                  </div>
                  <span v-if="att.file_size" class="text-[10px] text-slate-400 shrink-0">({{ Math.round(att.file_size / 1024) }} KB)</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <JournalEntryModal
      :show="showEntryModal"
      :folder-id="selectedFolderId"
      :folders="folders"
      :projects="projects"
      :tasks="allTasks"
      :contacts="allContacts"
      :entry-to-edit="entryToEdit"
      @close="showEntryModal = false; entryToEdit = null"
      @saved="onEntrySaved"
    />

    <JournalNoteModal
      :show="showNoteModal"
      :folder-id="selectedFolderId"
      :folders="folders"
      :projects="projects"
      :tasks="allTasks"
      @close="showNoteModal = false"
      @saved="onEntrySaved"
    />

    <!-- Original-Ansicht In-App Popup Modal (Taskster Standard) -->
    <div
      v-if="showOriginalModal && originalEntry"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in"
      @mousedown.self="showOriginalModal = false"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full shadow-2xl flex flex-col max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 shrink-0">
          <div class="flex items-center space-x-3 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-cyan-50 text-[#00A3C4] border border-cyan-200 flex items-center justify-center shrink-0">
              <Eye class="w-4 h-4" />
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-slate-900 truncate">
                  {{ originalEntry.title || 'Original-Dokument' }}
                </h3>
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase tracking-wider shrink-0"
                  :class="categoryBadgeClass(originalEntry.category)"
                >
                  {{ categoryLabel(originalEntry.category) }}
                </span>
              </div>
              <p class="text-[11px] text-slate-500 truncate">
                Originalansicht des importierten Dokuments / der E-Mail
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="showOriginalModal = false"
            class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-200/60 transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto space-y-4">
          <!-- Metadata Card (Sender, Recipients, Date) -->
          <div
            v-if="originalEntry.metadata?.email_sender || originalEntry.metadata?.email_subject || originalEntry.author_name"
            class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-xs space-y-1.5"
          >
            <div v-if="originalEntry.metadata?.email_sender" class="flex items-start gap-2">
              <span class="font-bold text-slate-500 w-20 shrink-0">Absender:</span>
              <span class="text-slate-900 font-medium select-all">{{ originalEntry.metadata.email_sender }}</span>
            </div>
            <div v-if="originalEntry.metadata?.email_recipients" class="flex items-start gap-2">
              <span class="font-bold text-slate-500 w-20 shrink-0">Empfänger:</span>
              <span class="text-slate-700 select-all">{{ originalEntry.metadata.email_recipients }}</span>
            </div>
            <div v-if="originalEntry.metadata?.email_subject" class="flex items-start gap-2">
              <span class="font-bold text-slate-500 w-20 shrink-0">Betreff:</span>
              <span class="text-slate-900 font-semibold select-all">{{ originalEntry.metadata.email_subject }}</span>
            </div>
            <div class="flex items-start gap-2">
              <span class="font-bold text-slate-500 w-20 shrink-0">Datum:</span>
              <span class="text-slate-600">{{ formatDateTime(originalEntry.metadata?.email_date || originalEntry.entry_date || originalEntry.created_at) }}</span>
            </div>
          </div>

          <!-- Extracted Contacts Box if present -->
          <div v-if="originalEntry.metadata?.contacts && originalEntry.metadata.contacts.length > 0" class="space-y-2">
            <h5 class="text-[11px] font-bold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
              <span>👤</span>
              <span>Erkannte Kontakte (in Kontakte synchronisiert):</span>
            </h5>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="(c, cIdx) in originalEntry.metadata.contacts"
                :key="cIdx"
                class="p-3 bg-white border border-slate-200 rounded-xl text-xs space-y-1 shadow-2xs"
              >
                <div class="font-bold text-slate-900 flex items-center justify-between">
                  <span>{{ c.first_name }} {{ c.last_name }}</span>
                  <span v-if="c.role_function" class="text-[10px] font-normal text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded">{{ c.role_function }}</span>
                </div>
                <div v-if="c.company_name" class="text-slate-600 text-[11px]">🏢 {{ c.company_name }}</div>
                <div v-if="c.phone" class="text-slate-600 text-[11px]">📞 {{ c.phone }}</div>
                <div v-if="c.email" class="text-cyan-700 text-[11px]">✉️ {{ c.email }}</div>
                <div v-if="c.address" class="text-slate-500 text-[10px]">📍 {{ c.address }}</div>
              </div>
            </div>
          </div>

          <!-- Full Text Box -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="text-[11px] font-bold uppercase tracking-wider text-slate-600">
                Originaltext
              </label>
              <button
                type="button"
                @click="copyOriginalText"
                class="text-[11px] font-semibold text-[#00A3C4] hover:text-[#008ba8] flex items-center gap-1 cursor-pointer"
              >
                <Check v-if="copiedText" class="w-3.5 h-3.5 text-emerald-600" />
                <Copy v-else class="w-3.5 h-3.5" />
                <span>{{ copiedText ? 'Kopiert!' : 'Text kopieren' }}</span>
              </button>
            </div>
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-800 font-sans whitespace-pre-wrap leading-relaxed max-h-[50vh] overflow-y-auto selection:bg-cyan-100 select-text">
              {{ getOriginalContent(originalEntry) }}
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
          <button
            type="button"
            @click="copyOriginalText"
            class="taskster_button_light px-4 text-xs h-[38px] rounded-lg flex items-center gap-1.5 cursor-pointer"
          >
            <Check v-if="copiedText" class="w-3.5 h-3.5 text-emerald-600" />
            <Copy v-else class="w-3.5 h-3.5" />
            <span>{{ copiedText ? 'Text kopiert!' : 'Originaltext kopieren' }}</span>
          </button>

          <button
            type="button"
            @click="showOriginalModal = false"
            class="taskster_button px-6 text-xs h-[38px] rounded-lg cursor-pointer"
          >
            Schließen
          </button>
        </div>
      </div>
    </div>

    <JournalNoteModal
      :show="showNoteModal"
      :folder-id="selectedFolderId"
      :folders="folders"
      :projects="projects"
      :tasks="allTasks"
      @close="showNoteModal = false"
      @saved="onEntrySaved"
    />

    <!-- In-App Delete Confirmation Modal (Taskster Standard) -->
    <div
      v-if="itemToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
      @mousedown.self="itemToDelete = null"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full shadow-2xl p-6 sm:p-7 space-y-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-200 shrink-0">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Journal-Eintrag löschen</h3>
            <p class="text-xs text-slate-500">Dieser Vorgang kann nicht rückgängig gemacht werden</p>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Möchtest du diesen Journal-Eintrag wirklich unwiderruflich löschen?
        </p>

        <div v-if="deleteError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
          {{ deleteError }}
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button
            type="button"
            @click="itemToDelete = null"
            class="taskster_button_light px-5 text-xs h-[40px] rounded-lg cursor-pointer"
          >
            Abbrechen
          </button>
          <button
            type="button"
            @click="executeDelete"
            :disabled="deleting"
            class="taskster_button_accent px-5 text-xs h-[40px] rounded-lg cursor-pointer disabled:opacity-50"
          >
            {{ deleting ? 'Wird gelöscht...' : 'Eintrag löschen' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Feedback -->
    <div v-if="toast.show" class="fixed bottom-6 right-6 z-50 max-w-sm w-full transition-all duration-300">
      <div
        class="flex items-start gap-3 p-4 rounded-2xl shadow-xl border backdrop-blur-md"
        :class="toast.type === 'error' ? 'bg-rose-50/95 border-rose-200 text-rose-900' : toast.type === 'success' ? 'bg-emerald-50/95 border-emerald-200 text-emerald-900' : 'bg-slate-900/90 border-slate-700 text-white'"
      >
        <div class="flex-1 text-xs">
          <div class="font-bold">{{ toast.type === 'error' ? 'Fehler' : toast.type === 'success' ? 'Erfolg' : 'Hinweis' }}</div>
          <div class="mt-0.5 leading-relaxed">{{ toast.message }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  BookOpen,
  Plus,
  FileText,
  Search,
  Folder,
  Trash2,
  Paperclip,
  AlertTriangle,
  Pencil,
  Eye,
  Copy,
  Check,
  CheckCircle2,
  Sparkles,
  ChevronDown,
  ChevronUp,
  ExternalLink,
  Users
} from 'lucide-vue-next'
import { useAuth } from '~/composables/useAuth'

const { user, token } = useAuth()
const authHeaders = () => ({
  Authorization: token.value ? `Bearer ${token.value}` : ''
})

const folders = ref<any[]>([])
const projects = ref<any[]>([])
const allJournals = ref<any[]>([])
const allTasks = ref<any[]>([])
const allContacts = ref<any[]>([])

const selectedFolderId = ref('')
const selectedProjectId = ref('')
const filterType = ref<'all' | 'entry' | 'note'>('all')
const categoryFilter = ref('')
const searchQuery = ref('')
const journalSortBy = ref<'date_desc' | 'date_asc' | 'project' | 'category'>('date_desc')

const loading = ref(false)
const showEntryModal = ref(false)
const showNoteModal = ref(false)
const entryToEdit = ref<any | null>(null)

// 5-line expansion state
const expandedEntries = ref<Record<string, boolean>>({})
const isExpanded = (id: string) => !!expandedEntries.value[id]
const toggleExpand = (id: string) => {
  expandedEntries.value[id] = !expandedEntries.value[id]
}

// Clean duplicate / large linebreaks
const cleanContent = (text?: string) => {
  if (!text) return ''
  return text
    .replace(/\r\n/g, '\n')
    .replace(/\n{3,}/g, '\n\n')
    .trim()
}

// Check if content exceeds 5 lines or length threshold
const isLongContent = (text?: string) => {
  if (!text) return false
  const cleaned = cleanContent(text)
  const lines = cleaned.split('\n')
  return lines.length > 5 || cleaned.length > 250
}

// Original View Modal state
const showOriginalModal = ref(false)
const originalEntry = ref<any | null>(null)
const copiedText = ref(false)

const hasOriginalText = (entry: any) => {
  if (!entry) return false
  if (entry.metadata?.original_text || entry.metadata?.raw_text) return true
  if (entry.category === 'email') return true
  return isLongContent(entry.content)
}

const getOriginalContent = (entry: any) => {
  if (!entry) return ''
  return entry.metadata?.original_text || entry.metadata?.raw_text || entry.content || ''
}

const openOriginalView = (entry: any) => {
  originalEntry.value = entry
  copiedText.value = false
  showOriginalModal.value = true
}

const copyOriginalText = async () => {
  if (!originalEntry.value) return
  const text = getOriginalContent(originalEntry.value)
  try {
    await navigator.clipboard.writeText(text)
    copiedText.value = true
    setTimeout(() => { copiedText.value = false }, 2500)
  } catch (e) {
    console.error('Clipboard copy failed:', e)
  }
}

// Edit detection & date formatters
const isEdited = (entry: any) => {
  if (!entry?.updated_at || !entry?.created_at) return false
  const diff = new Date(entry.updated_at).getTime() - new Date(entry.created_at).getTime()
  return diff > 60000 // more than 1 minute difference
}

const formatDateTime = (dateStr?: string) => {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr)
    const date = d.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
    const time = d.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })
    return `${date} um ${time}`
  } catch (_) {
    return dateStr
  }
}

const openEditModal = (entry: any) => {
  entryToEdit.value = entry
  showEntryModal.value = true
}

const itemToDelete = ref<any | null>(null)
const deleting = ref(false)
const deleteError = ref('')

const toast = ref<{ show: boolean; message: string; type: 'success' | 'error' | 'info' }>({ show: false, message: '', type: 'info' })
let toastTimer: any = null
const showToast = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
  toast.value = { show: true, message, type }
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value.show = false }, 3500)
}

const analyzingEntryId = ref<string | null>(null)

const triggerAiAnalysis = async (entry: any) => {
  if (!entry.content?.trim() && !entry.title?.trim()) {
    showToast('Eintrag hat keinen Text zum Analysieren.', 'info')
    return
  }
  analyzingEntryId.value = entry.id
  try {
    const endpoint = entry.project_id
      ? `/api/projects/${entry.project_id}/journal/parse-email`
      : '/api/journals/parse-email'
    const res = await $fetch<any>(endpoint, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        journal_id: entry.id,
        folder_id: entry.folder_id || null,
        project_id: entry.project_id || null,
        task_id: entry.task_id || null,
        subject: entry.title,
        content: entry.content,
        category: entry.category,
        visibility: entry.visibility
      }
    })
    if (res.metadata) {
      entry.metadata = res.metadata
    } else if (res.entry?.metadata) {
      entry.metadata = res.entry.metadata
    }
    showToast('KI-Zusammenfassung erfolgreich aktualisiert!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || err.message || 'Fehler bei der KI-Analyse', 'error')
  } finally {
    analyzingEntryId.value = null
  }
}

const loadData = async () => {
  loading.value = true
  try {
    const [foldersRes, projectsRes, journalsRes, contactsRes, tasksRes] = await Promise.all([
      $fetch<any>('/api/folders', { headers: authHeaders() }).catch(() => ({ folders: [] })),
      $fetch<any>('/api/projects', { headers: authHeaders() }).catch(() => ({ projects: [] })),
      $fetch<any>(selectedFolderId.value ? `/api/journals?folder_id=${selectedFolderId.value}` : '/api/journals', { headers: authHeaders() }).catch(() => ({ entries: [] })),
      $fetch<any>('/api/contacts', { headers: authHeaders() }).catch(() => ({ contacts: [] })),
      $fetch<any>('/api/tasks', { headers: authHeaders() }).catch(() => ({ tasks: [] }))
    ])

    folders.value = foldersRes.folders || []
    projects.value = projectsRes.projects || []
    allJournals.value = journalsRes.entries || []
    allContacts.value = contactsRes.contacts || []
    allTasks.value = tasksRes.tasks || []
  } catch (err) {
    console.error('Error loading journal overview data:', err)
  } finally {
    loading.value = false
  }
}

watch(selectedFolderId, async (newFolderId) => {
  selectedProjectId.value = ''
  loading.value = true
  try {
    const url = newFolderId ? `/api/journals?folder_id=${newFolderId}` : '/api/journals'
    const res = await $fetch<any>(url, { headers: authHeaders() })
    allJournals.value = res.entries || []
  } catch (err) {
    console.error('Failed to load journals for folder:', err)
  } finally {
    loading.value = false
  }
})

onMounted(() => {
  loadData()
})

const currentFolderProjects = computed(() => {
  if (!selectedFolderId.value) return projects.value
  return projects.value.filter(p => p.folder_id === selectedFolderId.value)
})

const getFolderProjectCount = (fId: string) => {
  return projects.value.filter(p => p.folder_id === fId).length
}

const entriesCount = computed(() => {
  return allJournals.value.filter(j => j.type === 'entry' || (j.category && !['notiz', 'note'].includes(j.category))).length
})

const notesCount = computed(() => {
  return allJournals.value.filter(j => j.type === 'note' || j.category === 'notiz' || j.category === 'note' || j.category === 'email').length
})

const filteredJournals = computed(() => {
  const filtered = allJournals.value.filter(j => {
    // Project filter
    if (selectedProjectId.value) {
      if (selectedProjectId.value === 'none' && j.project_id) return false
      if (selectedProjectId.value !== 'none' && j.project_id !== selectedProjectId.value) return false
    }

    // Type filter
    if (filterType.value === 'entry') {
      if (j.type === 'note' && (j.category === 'notiz' || j.category === 'note')) return false
    } else if (filterType.value === 'note') {
      if (j.type === 'entry' && !['notiz', 'note', 'email'].includes(j.category)) return false
    }

    // Category filter
    if (categoryFilter.value && j.category !== categoryFilter.value) {
      return false
    }

    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim()
      const t = (j.title || '').toLowerCase().includes(q)
      const c = (j.content || '').toLowerCase().includes(q)
      const u = (j.author_name || '').toLowerCase().includes(q)
      const p = (j.project_title || '').toLowerCase().includes(q)
      const f = (j.folder_name || '').toLowerCase().includes(q)
      return t || c || u || p || f
    }

    return true
  })

  // Sorting
  return filtered.slice().sort((a, b) => {
    if (journalSortBy.value === 'date_asc') {
      const da = new Date(a.entry_date || a.created_at).getTime()
      const db = new Date(b.entry_date || b.created_at).getTime()
      return da - db
    } else if (journalSortBy.value === 'project') {
      const pa = (a.project_title || a.folder_name || '').toLowerCase()
      const pb = (b.project_title || b.folder_name || '').toLowerCase()
      return pa.localeCompare(pb, 'de')
    } else if (journalSortBy.value === 'category') {
      const ca = categoryLabel(a.category).toLowerCase()
      const cb = categoryLabel(b.category).toLowerCase()
      return ca.localeCompare(cb, 'de')
    } else {
      // date_desc (default)
      const da = new Date(a.entry_date || a.created_at).getTime()
      const db = new Date(b.entry_date || b.created_at).getTime()
      return db - da
    }
  })
})

const openJournalEntryModal = () => {
  entryToEdit.value = null
  showEntryModal.value = true
}

const openJournalNoteModal = () => {
  showNoteModal.value = true
}

const onEntrySaved = async () => {
  entryToEdit.value = null
  const url = selectedFolderId.value ? `/api/journals?folder_id=${selectedFolderId.value}` : '/api/journals'
  const res = await $fetch<any>(url, { headers: authHeaders() })
  allJournals.value = res.entries || []
  showToast('Journal-Eintrag erfolgreich gespeichert', 'success')
}

const canDeleteEntry = (entry: any) => {
  if (!user.value) return false
  if (user.value.is_superadmin) return true
  if (user.value.id === entry.author_id || user.value.id === entry.user_id) return true
  return true
}

const confirmDelete = (entry: any) => {
  deleteError.value = ''
  itemToDelete.value = entry
}

const executeDelete = async () => {
  if (!itemToDelete.value) return
  deleting.value = true
  deleteError.value = ''
  try {
    await $fetch(`/api/journals/${itemToDelete.value.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    allJournals.value = allJournals.value.filter(j => j.id !== itemToDelete.value.id)
    itemToDelete.value = null
    showToast('Journal-Eintrag erfolgreich gelöscht', 'success')
  } catch (err: any) {
    deleteError.value = err.data?.statusMessage || err.message || 'Fehler beim Löschen des Eintrags'
    console.error('Delete failed:', err)
  } finally {
    deleting.value = false
  }
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return ''
  try {
    return new Date(dateStr).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch (_) {
    return dateStr
  }
}

const categoryLabel = (cat?: string) => {
  const map: Record<string, string> = {
    bausitzung: 'Bausitzung',
    bautagebuch: 'Bautagebuch',
    abnahmebegehung: 'Abnahme',
    wetter_behinderung: 'Wetter / Behinderung',
    regie: 'Regiearbeit',
    email: 'E-Mail',
    notiz: 'Notiz',
    mangel: 'Mangel',
    allgemein: 'Allgemein'
  }
  return (cat && map[cat]) ? map[cat] : (cat || 'Allgemein')
}

const categoryBadgeClass = (cat?: string) => {
  switch (cat) {
    case 'mangel':
    case 'wetter_behinderung':
      return 'bg-rose-50 text-rose-700 border-rose-200'
    case 'baufortschritt':
    case 'abnahmebegehung':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    case 'email':
      return 'bg-amber-50 text-amber-800 border-amber-200'
    case 'bausitzung':
      return 'bg-indigo-50 text-indigo-700 border-indigo-200'
    default:
      return 'bg-cyan-50 text-cyan-700 border-cyan-200'
  }
}

const getCategoryIcon = (category?: string, type?: string) => {
  if (type === 'note' || category === 'notiz') return '📝'
  if (category === 'email') return '✉️'
  if (category === 'regie') return '⏱️'
  if (category === 'bausitzung') return '🏛️'
  if (category === 'bautagebuch') return '📋'
  if (category === 'abnahmebegehung') return '🔍'
  if (category === 'wetter_behinderung') return '⛈️'
  if (category === 'mangel') return '⚠️'
  return '📖'
}

const updatingTaskId = ref<string | null>(null)

const updateJournalTaskLink = async (entry: any, newTaskId: string) => {
  updatingTaskId.value = entry.id
  try {
    await $fetch(`/api/journals/${entry.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: { task_id: newTaskId || null }
    })
    entry.task_id = newTaskId || null
    if (newTaskId) {
      const found = allTasks.value.find(t => t.id === newTaskId)
      entry.task_title = found ? found.title : ''
    } else {
      entry.task_title = ''
    }
    showToast('Aufgaben-Verknüpfung aktualisiert', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Aktualisieren der Aufgabe', 'error')
  } finally {
    updatingTaskId.value = null
  }
}

const getTaskTitle = (taskId?: string) => {
  if (!taskId) return ''
  const t = allTasks.value.find(item => item.id === taskId)
  return t ? t.title : taskId
}

const getTaskSectionTitle = (taskId?: string) => {
  if (!taskId) return ''
  const t = allTasks.value.find(item => item.id === taskId)
  return t?.section_title || t?.status || 'Zugeordnet'
}

const getAvailableTasksForEntry = (entry: any) => {
  if (entry.project_id) {
    const list = allTasks.value.filter(t => t.project_id === entry.project_id)
    return list.length > 0 ? list : allTasks.value
  }
  return allTasks.value
}
</script>
