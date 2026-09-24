<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
    @mousedown.self="$emit('close')"
  >
    <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-5 sm:p-7 shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
      <!-- Header -->
      <div class="flex items-start justify-between pb-3 border-b border-slate-100 mb-3 shrink-0">
        <div>
          <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
            <span class="text-xl">📖</span>
            <span>Neuen Journaleintrag erfassen</span>
          </h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Offizielles Bauprotokoll, Bausitzung, Begehung oder E-Mail-Ablage mit KI-Analyse.
          </p>
        </div>
        <button
          @click="$emit('close')"
          type="button"
          class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg cursor-pointer"
        >
          ✕
        </button>
      </div>

      <!-- Scrollable Form Body -->
      <form @submit.prevent="handleSubmit" class="space-y-4 overflow-y-auto pr-1 flex-1">
        <div v-if="error" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
          {{ error }}
        </div>

        <!-- 1. Folder & Project Selection (when not fixed to a specific project) -->
        <div v-if="!fixedProjectId" class="space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200">
          <div v-if="!fixedFolderId && folderOptions.length > 0">
            <label class="block text-xs font-bold text-slate-700 mb-1">Ordner wählen</label>
            <select
              v-model="selectedFolderId"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4]"
            >
              <option value="">Alle Ordner / Ordnerübergreifend</option>
              <option v-for="f in folderOptions" :key="f.id" :value="f.id">📁 {{ f.name }}</option>
            </select>
          </div>

          <!-- Searchable Project Picker (Solves long dropdown with 70 entries) -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-bold text-slate-700">Projekt-Zuweisung</label>
              <span class="text-[11px] text-slate-500">
                {{ filteredProjects.length }} Projekt{{ filteredProjects.length === 1 ? '' : 'e' }} verfügbar
              </span>
            </div>

            <!-- Custom Searchable Combobox -->
            <div class="relative">
              <div
                @click="isProjectDropdownOpen = !isProjectDropdownOpen"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 flex items-center justify-between cursor-pointer hover:border-[#00A3C4]"
              >
                <div class="flex items-center gap-1.5 truncate">
                  <span v-if="selectedProjectId === 'auto'" class="text-[#00A3C4]">✨ Automatisch zuweisen (anhand Text/Titel)</span>
                  <span v-else-if="!selectedProjectId" class="text-slate-600">📂 Nur Ordner-Journal (Kein spezifisches Projekt)</span>
                  <span v-else class="text-slate-900 font-bold">📁 {{ getProjectDisplay(selectedProjectId) }}</span>
                </div>
                <ChevronDown class="w-4 h-4 text-slate-400 shrink-0 ml-1" />
              </div>

              <!-- Dropdown Menu with Live Search Filter -->
              <div
                v-if="isProjectDropdownOpen"
                class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl z-30 p-2 space-y-1.5 max-h-60 flex flex-col"
              >
                <!-- Search input inside dropdown -->
                <div class="relative shrink-0">
                  <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                  <input
                    v-model="projectSearchTerm"
                    type="text"
                    placeholder="Projekt suchen (z.B. Name, Adresse, ID)..."
                    class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
                    @click.stop
                  />
                </div>

                <div class="overflow-y-auto space-y-0.5 flex-1 pr-1">
                  <!-- Auto assign option -->
                  <button
                    type="button"
                    @click="selectProject('auto')"
                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-cyan-50 transition"
                    :class="selectedProjectId === 'auto' ? 'text-[#00A3C4] bg-cyan-50' : 'text-slate-700'"
                  >
                    <span>✨</span>
                    <span>Automatisch zuweisen (anhand Text/Titel)</span>
                    <Check v-if="selectedProjectId === 'auto'" class="w-3.5 h-3.5 ml-auto text-[#00A3C4]" />
                  </button>

                  <!-- General / Folder level option -->
                  <button
                    type="button"
                    @click="selectProject('')"
                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center gap-2 hover:bg-slate-100 transition"
                    :class="!selectedProjectId ? 'text-slate-900 font-bold bg-slate-100' : 'text-slate-600'"
                  >
                    <span>📂</span>
                    <span>Nur Ordner-Journal (Allgemein)</span>
                    <Check v-if="!selectedProjectId" class="w-3.5 h-3.5 ml-auto text-slate-700" />
                  </button>

                  <div v-if="filteredProjects.length === 0" class="px-2 py-3 text-center text-xs text-slate-400">
                    Kein Projekt gefunden
                  </div>

                  <!-- Specific Projects -->
                  <button
                    v-for="p in filteredProjects"
                    :key="p.id"
                    type="button"
                    @click="selectProject(p.id)"
                    class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center justify-between hover:bg-cyan-50/70 transition"
                    :class="selectedProjectId === p.id ? 'text-[#00A3C4] font-bold bg-cyan-50' : 'text-slate-700'"
                  >
                    <span class="truncate">📁 {{ p.title }}</span>
                    <Check v-if="selectedProjectId === p.id" class="w-3.5 h-3.5 ml-2 shrink-0 text-[#00A3C4]" />
                  </button>
                </div>
              </div>
            </div>
            <p v-if="selectedProjectId === 'auto'" class="text-[11px] text-slate-500 mt-1">
              Das System ordnet den Eintrag automatisch dem passenden Projekt zu (z.B. nach Kundennummer, Adresse oder Name im Text).
            </p>
          </div>
        </div>

        <!-- 2. E-Mail Ingestion & AI Analysis Banner (Rich Integration) -->
        <div class="p-3.5 rounded-2xl bg-gradient-to-r from-amber-50/90 via-orange-50/70 to-amber-50/90 border border-amber-200 shadow-2xs space-y-2.5">
          <div class="flex items-start justify-between">
            <div class="flex items-center space-x-2">
              <span class="text-lg">⚡</span>
              <div>
                <h4 class="text-xs font-black text-amber-950">E-Mail importieren & mit KI analysieren</h4>
                <p class="text-[11px] text-amber-900 leading-tight">
                  Füge den E-Mail-Text ein oder ziehe eine .eml / .msg Datei hinein.
                </p>
              </div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-white/80 border border-amber-300 text-amber-900">
              DeepSeek AI Engine
            </span>
          </div>

          <!-- Dropzone -->
          <div class="relative border-2 border-dashed border-amber-300/80 rounded-xl p-2.5 bg-white/80 text-center hover:bg-white transition cursor-pointer">
            <input
              type="file"
              accept=".eml,.msg,.txt"
              @change="handleDropEml"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            />
            <div class="flex items-center justify-center gap-2 text-xs font-bold text-amber-900">
              <Mail class="w-4 h-4 text-amber-700" />
              <span>.eml oder Textdatei hier ablegen zum automatischen Auslesen</span>
            </div>
          </div>

          <!-- Sender Fields (if category is email or sender extracted) -->
          <div v-if="form.category === 'email' || form.sender_name || form.sender_email" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
            <div>
              <label class="block text-[11px] font-bold text-amber-950 mb-0.5">Absender Name</label>
              <input
                v-model="form.sender_name"
                type="text"
                placeholder="z.B. Max Muster"
                class="w-full px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-amber-950 mb-0.5">Absender E-Mail</label>
              <input
                v-model="form.sender_email"
                type="email"
                placeholder="z.B. m.muster@partner.ch"
                class="w-full px-2.5 py-1.5 bg-white border border-amber-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <!-- AI Sync Checkbox -->
          <label class="flex items-start gap-2.5 pt-1 cursor-pointer select-none">
            <input
              v-model="form.analyzeWithAi"
              type="checkbox"
              class="w-4 h-4 mt-0.5 rounded border-amber-300 text-[#00A3C4] focus:ring-[#00A3C4]"
            />
            <div class="text-[11px]">
              <span class="font-bold text-amber-950">Mit KI analysieren & Aufgaben synchronisieren</span>
              <p class="text-amber-900/80">Generiert prägnante Zusammenfassung und schlägt Aktionskarten (neue Aufgaben, Fristen) vor.</p>
            </div>
          </label>
        </div>

        <!-- 3. Title / Betreff -->
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">
            Titel / Betreff <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="z. B. 14. Bausitzung Los 3 oder Bauabnahme Keller..."
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          />
        </div>

        <!-- 4. Category & Date/Time -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Kategorie</label>
            <select
              v-model="form.category"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option value="bausitzung">🏛️ Bausitzung</option>
              <option value="bautagebuch">📋 Bautagebuch</option>
              <option value="abnahmebegehung">🔍 Abnahmebegehung</option>
              <option value="wetter_behinderung">⛈️ Wetter & Behinderung</option>
              <option value="regie">⏱️ Regiearbeit</option>
              <option value="email">✉️ E-Mail Import</option>
              <option value="allgemein">📖 Allgemein</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Datum</label>
            <input
              v-model="form.entry_date"
              type="date"
              required
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>
        </div>

        <!-- 5. Visibility & Allowed Group -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Sichtbarkeit</label>
            <select
              v-model="form.visibility"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option value="all">🌐 Öffentlich (Projektleser)</option>
              <option value="company">🏢 Nur eigenes Unternehmen</option>
              <option value="group">👥 Nur ausgewählte Gruppe</option>
              <option value="only_me">🔒 Nur ich (Vertraulich)</option>
            </select>
          </div>
          <div v-if="form.visibility === 'group'">
            <label class="block text-xs font-bold text-slate-800 mb-1">Berechtigte Gruppe</label>
            <select
              v-model="form.allowed_group_id"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option :value="null">-- Gruppe auswählen --</option>
              <option v-for="g in userGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>
        </div>

        <!-- 6. Linked Task (optional with search filter) -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-bold text-slate-800">Verknüpfte Aufgabe (optional)</label>
            <span v-if="autoTaskMatch" class="text-[10px] font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded-md border border-cyan-200">
              ✨ Erkannt: {{ autoTaskMatch }}
            </span>
          </div>
          <select
            v-model="form.task_id"
            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
          >
            <option :value="null">-- Keine Verknüpfung --</option>
            <option value="auto">✨ Automatisch zuweisen (anhand Text)</option>
            <option v-for="t in availableTasks" :key="t.id" :value="t.id">
              {{ t.title }} {{ t.list_title ? `(${t.list_title})` : '' }}
            </option>
          </select>
        </div>

        <!-- 7. Attendees & Anwesenheit -->
        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2.5">
          <div class="flex items-center justify-between">
            <div>
              <label class="block text-xs font-black text-slate-900">Teilnehmer & Anwesenheit</label>
              <p class="text-[11px] text-slate-500">Wähle bestehende Projektkontakte aus oder füge neue Teilnehmer hinzu.</p>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-700">
              {{ form.attendees.length }} Teilnehmer
            </span>
          </div>

          <!-- Add Attendee Input Group -->
          <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
            <div class="sm:col-span-4" v-if="contactOptions.length > 0">
              <select
                v-model="selectedContactToAdd"
                @change="addContactToAttendees"
                class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4]"
              >
                <option value="">Kontakt auswählen... ({{ contactOptions.length }})</option>
                <option v-for="c in contactOptions" :key="c.id" :value="c.id">
                  {{ (c.first_name ? c.first_name + ' ' : '') + c.last_name }}{{ c.company_name ? ` (${c.company_name})` : '' }}
                </option>
              </select>
            </div>
            <div :class="contactOptions.length > 0 ? 'sm:col-span-3' : 'sm:col-span-5'">
              <input
                v-model="newAttendeeName"
                type="text"
                placeholder="Name"
                class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4]"
              />
            </div>
            <div :class="contactOptions.length > 0 ? 'sm:col-span-3' : 'sm:col-span-5'">
              <input
                v-model="newAttendeeRole"
                type="text"
                placeholder="z.B. Bauleitung, Polier"
                class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4]"
              />
            </div>
            <div class="sm:col-span-2">
              <button
                type="button"
                @click="addCustomAttendee"
                class="w-full py-1.5 px-2 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white transition cursor-pointer"
              >
                + Add
              </button>
            </div>
          </div>

          <!-- Attendees Badges / Table -->
          <div v-if="form.attendees.length > 0" class="flex flex-wrap gap-1.5 pt-1">
            <div
              v-for="(atd, idx) in form.attendees"
              :key="idx"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs font-medium"
            >
              <button
                type="button"
                @click="atd.present = !atd.present"
                class="px-1.5 py-0.2 rounded text-[10px] font-bold cursor-pointer"
                :class="atd.present ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
              >
                {{ atd.present ? 'Anwesend' : 'Abwesend' }}
              </button>
              <span class="font-bold text-slate-900">{{ atd.name }}</span>
              <span v-if="atd.role" class="text-slate-400 text-[11px]">({{ atd.role }})</span>
              <button
                type="button"
                @click="form.attendees.splice(idx, 1)"
                class="text-slate-400 hover:text-rose-600 text-xs ml-1 font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- 8. Protokolltext / Inhalt -->
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">
            Protokolltext / Inhalt <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="form.content"
            @paste="handleContentPaste"
            rows="5"
            required
            placeholder="Besprochene Punkte, Beschlüsse, Sachverhalt oder Notizen..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4] resize-y"
          ></textarea>
        </div>

        <!-- 9. Attachments Dropzone -->
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">
            Dateianhänge (Fotos, Pläne, PDFs)
          </label>
          <div class="relative border-2 border-dashed border-slate-300 rounded-xl p-3.5 text-center hover:border-[#00A3C4] bg-slate-50 hover:bg-white transition cursor-pointer">
            <input
              type="file"
              multiple
              @change="handleFileSelect"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            />
            <div class="flex flex-col items-center justify-center space-y-1">
              <UploadCloud class="w-5 h-5 text-slate-400" />
              <p class="text-xs text-slate-600 font-medium">Dateien hier ablegen oder zum Auswählen klicken (max. 10 MB)</p>
            </div>
          </div>

          <div v-if="form.attachments.length > 0" class="flex flex-wrap gap-2 mt-2">
            <div
              v-for="(att, attIdx) in form.attachments"
              :key="attIdx"
              class="flex items-center space-x-1.5 px-2.5 py-1 bg-slate-100 rounded-lg text-xs font-semibold text-slate-800 border border-slate-200"
            >
              <Paperclip class="w-3.5 h-3.5 text-slate-500 shrink-0" />
              <span class="truncate max-w-[180px]">{{ att.file_name }}</span>
              <span class="text-[10px] text-slate-400 font-normal">({{ Math.round(att.file_size / 1024) }} KB)</span>
              <button
                type="button"
                @click="form.attachments.splice(attIdx, 1)"
                class="text-slate-400 hover:text-rose-600 ml-1 font-bold cursor-pointer"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </form>

      <!-- Footer Buttons -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5 shrink-0 mt-3">
        <button
          type="button"
          @click="$emit('close')"
          class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
        >
          Abbrechen
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="saving"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
        >
          <span v-if="saving">Speichern...</span>
          <span v-else>Eintrag speichern</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import {
  ChevronDown,
  Search,
  Check,
  Mail,
  UploadCloud,
  Paperclip
} from 'lucide-vue-next'
import { parseRawEml, readFileAsDataUrl } from '~/utils/emailParser'
import { useAuth } from '~/composables/useAuth'

const props = defineProps<{
  show: boolean
  folderId?: string
  projectId?: string
  folders?: Array<{ id: string; name: string }>
  projects?: Array<{ id: string; title: string; folder_id?: string; custom_data?: any }>
  tasks?: Array<{ id: string; title: string; list_title?: string; list_id?: string }>
  userGroups?: Array<{ id: string; name: string }>
  contacts?: Array<any>
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved', entry: any): void
}>()

const { token } = useAuth()
const authHeaders = () => ({
  Authorization: token.value ? `Bearer ${token.value}` : ''
})

const fixedProjectId = computed(() => props.projectId || '')
const fixedFolderId = computed(() => props.folderId || '')

const folderOptions = computed(() => props.folders || [])
const selectedFolderId = ref(props.folderId || '')

const selectedProjectId = ref(props.projectId || 'auto')
const isProjectDropdownOpen = ref(false)
const projectSearchTerm = ref('')

const saving = ref(false)
const error = ref('')

const selectedContactToAdd = ref('')
const newAttendeeName = ref('')
const newAttendeeRole = ref('')
const autoTaskMatch = ref('')

const form = ref({
  title: '',
  category: 'bausitzung',
  entry_date: new Date().toISOString().substring(0, 10),
  visibility: 'all',
  allowed_group_id: null as string | null,
  task_id: null as string | null,
  content: '',
  sender_name: '',
  sender_email: '',
  analyzeWithAi: false,
  attendees: [] as Array<{ name: string; role?: string; email?: string; contact_id?: string; present: boolean }>,
  attachments: [] as Array<{ file_name: string; file_type: string; file_size: number; file_path: string }>
})

watch(() => props.show, (newVal) => {
  if (newVal) {
    selectedFolderId.value = props.folderId || ''
    selectedProjectId.value = props.projectId || 'auto'
    projectSearchTerm.value = ''
    isProjectDropdownOpen.value = false
    error.value = ''
    autoTaskMatch.value = ''
    form.value = {
      title: '',
      category: 'bausitzung',
      entry_date: new Date().toISOString().substring(0, 10),
      visibility: 'all',
      allowed_group_id: null,
      task_id: null,
      content: '',
      sender_name: '',
      sender_email: '',
      analyzeWithAi: false,
      attendees: [],
      attachments: []
    }
  }
})

// Filter projects by folder (if selected) and by search query
const filteredProjects = computed(() => {
  let list = props.projects || []
  if (selectedFolderId.value) {
    list = list.filter(p => !p.folder_id || p.folder_id === selectedFolderId.value)
  }
  if (projectSearchTerm.value.trim()) {
    const q = projectSearchTerm.value.toLowerCase().trim()
    list = list.filter(p => {
      const titleMatch = (p.title || '').toLowerCase().includes(q)
      if (titleMatch) return true
      if (p.custom_data) {
        try {
          const cd = typeof p.custom_data === 'string' ? JSON.parse(p.custom_data) : p.custom_data
          return Object.values(cd).some(v => String(v).toLowerCase().includes(q))
        } catch (_) {}
      }
      return false
    })
  }
  return list
})

const getProjectDisplay = (pId: string) => {
  const p = (props.projects || []).find(item => item.id === pId)
  return p ? p.title : pId
}

const selectProject = (id: string) => {
  selectedProjectId.value = id
  isProjectDropdownOpen.value = false
}

const availableTasks = computed(() => props.tasks || [])
const contactOptions = computed(() => props.contacts || [])

const addContactToAttendees = () => {
  if (!selectedContactToAdd.value) return
  const c = contactOptions.value.find(item => item.id === selectedContactToAdd.value)
  if (!c) return
  const fullName = ((c.first_name ? c.first_name + ' ' : '') + (c.last_name || '')).trim() || 'Kontakt'
  if (!form.value.attendees.some(a => a.contact_id === c.id)) {
    form.value.attendees.push({
      contact_id: c.id,
      name: fullName,
      email: c.email || '',
      role: c.role_function || c.company_name || 'Kontakt',
      present: true
    })
  }
  selectedContactToAdd.value = ''
}

const addCustomAttendee = () => {
  if (!newAttendeeName.value.trim()) return
  form.value.attendees.push({
    name: newAttendeeName.value.trim(),
    role: newAttendeeRole.value.trim() || undefined,
    present: true
  })
  newAttendeeName.value = ''
  newAttendeeRole.value = ''
}

const handleDropEml = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  const file = target.files[0]
  const text = await file.text()
  const parsed = parseRawEml(text)

  form.value.title = parsed.subject || file.name.replace(/\.[^/.]+$/, '')
  if (parsed.fromName) form.value.sender_name = parsed.fromName
  if (parsed.fromEmail) form.value.sender_email = parsed.fromEmail
  form.value.content = parsed.body || text.trim()
  form.value.category = 'email'
  form.value.analyzeWithAi = true

  const base64 = await readFileAsDataUrl(file)
  form.value.attachments.push({
    file_name: file.name,
    file_type: file.type || 'message/rfc822',
    file_size: file.size,
    file_path: base64
  })
}

const handleContentPaste = (e: ClipboardEvent) => {
  const pasted = e.clipboardData?.getData('text') || ''
  if (pasted && (
    pasted.includes('Content-Transfer-Encoding:') ||
    pasted.includes('Content-Type: text/') ||
    /^--[a-zA-Z0-9_-]+/m.test(pasted) ||
    (pasted.includes('From:') && pasted.includes('Subject:'))
  )) {
    const parsed = parseRawEml(pasted)
    if (parsed.body && parsed.body !== pasted) {
      e.preventDefault()
      form.value.content = parsed.body
      if (!form.value.title && parsed.subject) {
        form.value.title = parsed.subject
      }
      if (parsed.fromEmail) {
        form.value.sender_email = parsed.fromEmail
        form.value.sender_name = parsed.fromName
        form.value.category = 'email'
        form.value.analyzeWithAi = true
      }
    }
  }
}

const handleFileSelect = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files) return
  for (let i = 0; i < target.files.length; i++) {
    const file = target.files[i]
    if (file.size > 10 * 1024 * 1024) continue
    const base64 = await readFileAsDataUrl(file)
    form.value.attachments.push({
      file_name: file.name,
      file_type: file.type || 'application/octet-stream',
      file_size: file.size,
      file_path: base64
    })
  }
}

const handleSubmit = async () => {
  if (!form.value.title.trim()) {
    error.value = 'Bitte gib einen Titel oder Betreff an.'
    return
  }
  if (!form.value.content.trim()) {
    error.value = 'Bitte gib einen Inhalt oder Bericht an.'
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload = {
      folder_id: selectedFolderId.value || fixedFolderId.value || null,
      project_id: fixedProjectId.value || (selectedProjectId.value === 'auto' ? 'auto' : (selectedProjectId.value || null)),
      task_id: form.value.task_id || null,
      type: 'entry',
      category: form.value.category,
      title: form.value.title.trim(),
      content: form.value.content.trim(),
      visibility: form.value.visibility,
      allowed_group_id: form.value.visibility === 'group' ? form.value.allowed_group_id : null,
      attendees: form.value.attendees,
      attachments: form.value.attachments,
      metadata: form.value.category === 'email' ? {
        sender: {
          name: form.value.sender_name,
          email: form.value.sender_email
        }
      } : {}
    }

    const res = await $fetch<any>('/api/journals', {
      method: 'POST',
      headers: authHeaders(),
      body: payload
    })

    const createdEntry = res.entry || res

    // If AI analysis checked, run parse-email
    if (form.value.analyzeWithAi && createdEntry?.id) {
      try {
        const prjId = createdEntry.project_id || payload.project_id
        const targetUrl = prjId && prjId !== 'auto'
          ? `/api/projects/${prjId}/journal/parse-email`
          : '/api/journals/parse-email'

        await $fetch<any>(targetUrl, {
          method: 'POST',
          headers: authHeaders(),
          body: {
            journal_id: createdEntry.id,
            folder_id: payload.folder_id,
            project_id: prjId !== 'auto' ? prjId : null,
            task_id: payload.task_id,
            subject: payload.title,
            content: payload.content,
            category: payload.category,
            visibility: payload.visibility,
            sender: {
              name: form.value.sender_name,
              email: form.value.sender_email
            }
          }
        })
      } catch (aiErr) {
        console.warn('AI analysis skipped/failed:', aiErr)
      }
    }

    emit('saved', createdEntry)
    emit('close')
  } catch (err: any) {
    error.value = err.data?.statusMessage || err.message || 'Fehler beim Speichern des Journaleintrags'
  } finally {
    saving.value = false
  }
}
</script>
