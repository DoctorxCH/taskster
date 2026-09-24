<template>
  <div class="min-h-full p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">
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
          @click="openJournalEntryModal"
          type="button"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5 cursor-pointer shadow-xs"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>+ Journaleintrag</span>
        </button>
        <button
          @click="openJournalNoteModal"
          type="button"
          class="taskster_button_light px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5 cursor-pointer"
        >
          <FileText class="w-3.5 h-3.5 text-slate-600" />
          <span>+ Notiz</span>
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

      <!-- Tier 2: Main Type Tabs & Category Filter -->
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

        <!-- Category Dropdown -->
        <div class="flex items-center gap-2">
          <select
            v-model="categoryFilter"
            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-[#00A3C4]"
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
          @click="openJournalEntryModal"
          type="button"
          class="taskster_button px-5 text-xs h-[40px] rounded-lg flex items-center space-x-1.5 cursor-pointer shadow-xs"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>+ Journaleintrag</span>
        </button>
        <button
          @click="openJournalNoteModal"
          type="button"
          class="taskster_button_light px-5 text-xs h-[40px] rounded-lg flex items-center space-x-1.5 cursor-pointer"
        >
          <FileText class="w-3.5 h-3.5 text-slate-600" />
          <span>+ Notiz</span>
        </button>
      </div>
    </div>

    <!-- Journal Entries Stream -->
    <div v-else class="space-y-4">
      <div
        v-for="entry in filteredJournals"
        :key="entry.id"
        class="bg-white border border-slate-200 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-md transition"
      >
        <!-- Entry Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-bold text-slate-900">{{ entry.author_name || 'Benutzer' }}</span>
            <span class="text-slate-300">•</span>
            <span class="text-xs text-slate-500">{{ formatDate(entry.entry_date || entry.created_at) }}</span>

            <!-- Category badge -->
            <span
              class="text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase tracking-wider"
              :class="categoryBadgeClass(entry.category)"
            >
              {{ categoryLabel(entry.category) }}
            </span>

            <!-- Visibility badge -->
            <span v-if="entry.visibility && entry.visibility !== 'all'" class="text-[10px] font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
              {{ entry.visibility === 'only_me' ? '🔒 Privat' : (entry.visibility === 'company' ? '🏢 Firma' : '👥 Gruppe') }}
            </span>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <!-- Folder link badge -->
            <NuxtLink
              v-if="entry.folder_id"
              :to="`/folders/${entry.folder_id}`"
              class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 flex items-center gap-1 transition"
              title="Zum Ordner springen"
            >
              <Folder class="w-3 h-3 text-slate-500" />
              <span>{{ entry.folder_name || 'Ordner' }}</span>
            </NuxtLink>

            <!-- Project link badge -->
            <NuxtLink
              v-if="entry.project_id"
              :to="`/projects/${entry.project_id}`"
              class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-cyan-50 hover:bg-cyan-100 text-[#00A3C4] border border-cyan-200 flex items-center gap-1 transition"
              title="Zum Projekt springen"
            >
              <span>📁 {{ entry.project_title || 'Projekt' }}</span>
            </NuxtLink>
            <span v-else class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200">
              Nur Ordner
            </span>

            <!-- Delete action -->
            <button
              v-if="canDeleteEntry(entry)"
              @click="confirmDelete(entry)"
              class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
              title="Eintrag löschen"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Title -->
        <h4 v-if="entry.title" class="text-sm font-bold text-slate-900 mt-3 mb-1.5">
          {{ entry.title }}
        </h4>

        <!-- Content -->
        <p class="text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
          {{ entry.content }}
        </p>

        <!-- Linked Task -->
        <div v-if="entry.task_id" class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-100 text-xs">
          <span class="text-slate-600 flex items-center gap-1.5 font-medium">
            <span>📌 Verknüpfte Aufgabe:</span>
            <strong class="text-slate-900 font-bold">{{ entry.task_title || 'Aufgabe' }}</strong>
          </span>
          <NuxtLink
            v-if="entry.project_id"
            :to="`/projects/${entry.project_id}`"
            class="text-[11px] font-bold text-[#00A3C4] hover:underline"
          >
            Im Projekt öffnen →
          </NuxtLink>
        </div>

        <!-- Attendees badges -->
        <div v-if="entry.attendees && entry.attendees.length > 0" class="flex flex-wrap items-center gap-1.5 mt-3 pt-2.5 border-t border-slate-100">
          <span class="text-[11px] font-bold text-slate-500 mr-1">Teilnehmer:</span>
          <span
            v-for="(atd, atdIdx) in entry.attendees"
            :key="atdIdx"
            class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-md border font-medium"
            :class="atd.present ? 'bg-slate-50 border-slate-200 text-slate-700' : 'bg-slate-50 border-slate-200 text-slate-400 line-through'"
          >
            {{ atd.name }}{{ atd.role ? ` (${atd.role})` : '' }}
          </span>
        </div>

        <!-- Attachments badges -->
        <div v-if="entry.attachments && entry.attachments.length > 0" class="flex flex-wrap items-center gap-2 mt-3 pt-2 border-t border-slate-100">
          <div
            v-for="(att, attIdx) in entry.attachments"
            :key="attIdx"
            class="flex items-center space-x-1 text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 font-medium"
          >
            <Paperclip class="w-3 h-3 text-slate-400" />
            <span>{{ att.file_name }}</span>
            <span class="text-[10px] text-slate-400">({{ Math.round(att.file_size / 1024) }} KB)</span>
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
      @close="showEntryModal = false"
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
  AlertTriangle
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

const loading = ref(false)
const showEntryModal = ref(false)
const showNoteModal = ref(false)

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
  return allJournals.value.filter(j => {
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
})

const openJournalEntryModal = () => {
  showEntryModal.value = true
}

const openJournalNoteModal = () => {
  showNoteModal.value = true
}

const onEntrySaved = async () => {
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
</script>
