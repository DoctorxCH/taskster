<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
    @mousedown.self="$emit('close')"
  >
    <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-5 sm:p-7 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="flex items-start justify-between pb-3 border-b border-slate-100 mb-3 shrink-0">
        <div>
          <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
            <span class="text-xl">📝</span>
            <span>Neuen Journaleintrag erfassen</span>
          </h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Schneller Journaleintrag mit automatischer Projekt- und Aufgabenzuweisung.
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
      <form @submit.prevent="handleSubmit" class="space-y-3.5 overflow-y-auto pr-1 flex-1">
        <div v-if="error" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
          {{ error }}
        </div>

        <!-- 1. Note Text (Primary Field - Autofocused) -->
        <div>
          <label class="block text-xs font-bold text-slate-800 mb-1">
            Journaleintrag / Inhalt <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="content"
            required
            rows="5"
            autofocus
            placeholder="Schreibe deinen Journaleintrag, Feststellung, Mangel oder Notiz hier rein..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4] resize-y"
          ></textarea>
        </div>

        <!-- 2. Project Assignment (with Instant Search Filter for 70+ projects) -->
        <div v-if="!fixedProjectId" class="space-y-1">
          <div class="flex items-center justify-between mb-0.5">
            <label class="block text-xs font-bold text-slate-700">Projekt-Zuweisung</label>
            <span class="text-[10px] text-slate-400">
              {{ filteredProjects.length }} Projekt{{ filteredProjects.length === 1 ? '' : 'e' }}
            </span>
          </div>

          <!-- Custom Combobox with Search Input -->
          <div class="relative">
            <div
              @click="isProjectDropdownOpen = !isProjectDropdownOpen"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 flex items-center justify-between cursor-pointer hover:bg-white hover:border-[#00A3C4] transition"
            >
              <div class="flex items-center gap-1.5 truncate">
                <span v-if="selectedProjectId === 'auto'" class="text-[#00A3C4] font-bold">✨ Automatisch zuweisen (anhand Text)</span>
                <span v-else-if="!selectedProjectId" class="text-slate-600">📂 Nur Ordner-Journal (Allgemein)</span>
                <span v-else class="text-slate-900 font-bold">📁 {{ getProjectDisplay(selectedProjectId) }}</span>
              </div>
              <ChevronDown class="w-4 h-4 text-slate-400 shrink-0 ml-1" />
            </div>

            <!-- Popover with live search -->
            <div
              v-if="isProjectDropdownOpen"
              class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl z-30 p-2 space-y-1 max-h-56 flex flex-col"
            >
              <div class="relative shrink-0">
                <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                <input
                  v-model="projectSearchTerm"
                  type="text"
                  placeholder="Projekt suchen (z.B. Balmstr, Name)..."
                  class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
                  @click.stop
                />
              </div>

              <div class="overflow-y-auto space-y-0.5 flex-1 pr-1">
                <!-- Auto option -->
                <button
                  type="button"
                  @click="selectProject('auto')"
                  class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-cyan-50 transition"
                  :class="selectedProjectId === 'auto' ? 'text-[#00A3C4] bg-cyan-50' : 'text-slate-700'"
                >
                  <span>✨</span>
                  <span>Automatisch zuweisen (anhand Text)</span>
                  <Check v-if="selectedProjectId === 'auto'" class="w-3.5 h-3.5 ml-auto text-[#00A3C4]" />
                </button>

                <!-- Folder only -->
                <button
                  type="button"
                  @click="selectProject('')"
                  class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center gap-2 hover:bg-slate-100 transition"
                  :class="!selectedProjectId ? 'text-slate-900 font-bold bg-slate-100' : 'text-slate-600'"
                >
                  <span>📂</span>
                  <span>Nur Ordner-Journal (Kein Projekt)</span>
                  <Check v-if="!selectedProjectId" class="w-3.5 h-3.5 ml-auto text-slate-700" />
                </button>

                <!-- Project List -->
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
        </div>

        <!-- 3. Task Assignment (User Choice: Manual or Suggestion, Never Auto-Forced) -->
        <div class="space-y-1">
          <label class="block text-xs font-bold text-slate-700 mb-0.5">Aufgaben-Zuweisung</label>
          <div class="relative">
            <div
              @click="isTaskDropdownOpen = !isTaskDropdownOpen"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 flex items-center justify-between cursor-pointer hover:bg-white hover:border-[#00A3C4] transition"
            >
              <div class="flex items-center gap-1.5 truncate">
                <span v-if="!selectedTaskId" class="text-slate-500">-- Keine Verknüpfung --</span>
                <span v-else class="text-slate-900 font-bold">☑️ {{ getTaskDisplay(selectedTaskId) }}</span>
              </div>
              <ChevronDown class="w-4 h-4 text-slate-400 shrink-0 ml-1" />
            </div>

            <!-- Task Popover with instant search -->
            <div
              v-if="isTaskDropdownOpen"
              class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-2xl shadow-xl z-30 p-2 space-y-1 max-h-52 flex flex-col"
            >
              <div class="relative shrink-0">
                <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                <input
                  v-model="taskSearchTerm"
                  type="text"
                  placeholder="Aufgabe suchen..."
                  class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
                  @click.stop
                />
              </div>

              <div class="overflow-y-auto space-y-0.5 flex-1 pr-1">
                <button
                  type="button"
                  @click="selectTask('')"
                  class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center gap-2 hover:bg-slate-100 transition"
                  :class="!selectedTaskId ? 'text-slate-900 font-bold bg-slate-100' : 'text-slate-600'"
                >
                  <span>--</span>
                  <span>Keine Verknüpfung</span>
                  <Check v-if="!selectedTaskId" class="w-3.5 h-3.5 ml-auto text-slate-700" />
                </button>

                <button
                  v-for="t in filteredTasks"
                  :key="t.id"
                  type="button"
                  @click="selectTask(t.id)"
                  class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs font-medium flex items-center justify-between hover:bg-cyan-50/70 transition"
                  :class="selectedTaskId === t.id ? 'text-[#00A3C4] font-bold bg-cyan-50' : 'text-slate-700'"
                >
                  <span class="truncate">{{ t.title }}</span>
                  <Check v-if="selectedTaskId === t.id" class="w-3.5 h-3.5 ml-2 shrink-0 text-[#00A3C4]" />
                </button>
              </div>
            </div>
          </div>

          <!-- Suggestions when note text matches tasks (max 2 suggestions) -->
          <div v-if="suggestedTasks.length > 0 && !selectedTaskId" class="p-2.5 rounded-xl bg-amber-50/90 border border-amber-200 space-y-1.5 mt-1.5">
            <div class="text-[10px] font-bold text-amber-900 flex items-center gap-1">
              <span>✨</span>
              <span>Passende Aufgabe erkannt (Klick zum Zuweisen):</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="sug in suggestedTasks"
                :key="sug.task.id"
                type="button"
                @click="selectTask(sug.task.id)"
                class="text-left px-2.5 py-1 rounded-lg bg-white border border-amber-300 text-xs font-semibold text-slate-800 hover:bg-amber-100 hover:border-amber-400 transition cursor-pointer flex items-center gap-1.5 shadow-2xs"
              >
                <span>☑️ {{ sug.task.title }}</span>
                <span class="text-[10px] text-amber-700 font-normal">({{ sug.reason }})</span>
              </button>
            </div>
          </div>
        </div>

        <!-- 4. Category & Date/Time -->
        <div class="grid grid-cols-2 gap-2 pt-1">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kategorie</label>
            <select
              v-model="category"
              class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
            >
              <option value="notiz">📝 Notiz</option>
              <option value="telefonat">📞 Telefonat</option>
              <option value="baufortschritt">📋 Baufortschritt</option>
              <option value="mangel">⚠️ Mangel / Behinderung</option>
              <option value="allgemein">📖 Allgemein</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Datum</label>
            <input
              v-model="entryDate"
              type="date"
              class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
            />
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
          :disabled="saving || !content.trim()"
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
import { ChevronDown, Search, Check } from 'lucide-vue-next'
import { useAuth } from '~/composables/useAuth'
import { cleanOleResidue } from '~/utils/emailParser'

const props = defineProps<{
  show: boolean
  folderId?: string
  projectId?: string
  folders?: Array<{ id: string; name: string }>
  projects?: Array<{ id: string; title: string; folder_id?: string; custom_data?: any }>
  tasks?: Array<{ id: string; title: string; list_title?: string; list_id?: string; project_id?: string }>
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

const content = ref('')
const category = ref('notiz')
const entryDate = ref(new Date().toISOString().substring(0, 10))

const selectedProjectId = ref(props.projectId || 'auto')
const isProjectDropdownOpen = ref(false)
const projectSearchTerm = ref('')

const selectedTaskId = ref('')
const isTaskDropdownOpen = ref(false)
const taskSearchTerm = ref('')

const saving = ref(false)
const error = ref('')

watch(() => props.show, (newVal) => {
  if (newVal) {
    content.value = ''
    category.value = 'notiz'
    entryDate.value = new Date().toISOString().substring(0, 10)
    selectedProjectId.value = props.projectId || 'auto'
    selectedTaskId.value = ''
    isProjectDropdownOpen.value = false
    isTaskDropdownOpen.value = false
    projectSearchTerm.value = ''
    taskSearchTerm.value = ''
    error.value = ''
  }
})

// Filter projects by folder (if folderId is given) and search term
const filteredProjects = computed(() => {
  let list = props.projects || []
  if (props.folderId) {
    list = list.filter(p => !p.folder_id || p.folder_id === props.folderId)
  }
  if (projectSearchTerm.value.trim()) {
    const q = projectSearchTerm.value.toLowerCase().trim()
    list = list.filter(p => {
      const matchTitle = (p.title || '').toLowerCase().includes(q)
      if (matchTitle) return true
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

// Filter tasks by search term and selected project
const filteredTasks = computed(() => {
  let list = props.tasks || []
  if (selectedProjectId.value && selectedProjectId.value !== 'auto') {
    list = list.filter(t => !t.project_id || t.project_id === selectedProjectId.value)
  }
  if (taskSearchTerm.value.trim()) {
    const q = taskSearchTerm.value.toLowerCase().trim()
    list = list.filter(t => (t.title || '').toLowerCase().includes(q))
  }
  return list
})

// Suggest matching tasks from content text (max 2 suggestions, no forced auto-assign)
const suggestedTasks = computed(() => {
  if (selectedTaskId.value) return []
  const text = (content.value || '').toLowerCase().trim()
  if (!text || text.length < 3) return []

  const availableTasks = filteredTasks.value || []
  if (availableTasks.length === 0) return []

  const matches: Array<{ task: any, reason: string, score: number }> = []

  for (const t of availableTasks) {
    let score = 0
    let reason = ''
    const title = (t.title || '').trim()
    const lowerTitle = title.toLowerCase()

    if (lowerTitle && text.includes(lowerTitle)) {
      score += 60
      reason = `Titel: "${title}"`
    } else {
      const words = lowerTitle.split(/[\s\-_,./]+/).filter((w: string) => w.length >= 4)
      const matched = words.filter((w: string) => text.includes(w))
      if (matched.length >= 2) {
        score += matched.length * 20
        reason = `Stichworte: ${matched.join(', ')}`
      }
    }

    if (score >= 30) {
      matches.push({ task: t, reason, score })
    }
  }

  return matches.sort((a, b) => b.score - a.score).slice(0, 2)
})

const getTaskDisplay = (tId: string) => {
  const t = (props.tasks || []).find(item => item.id === tId)
  return t ? t.title : tId
}

const selectTask = (id: string) => {
  selectedTaskId.value = id
  isTaskDropdownOpen.value = false
}

const handleSubmit = async () => {
  if (!content.value.trim()) {
    error.value = 'Bitte gib einen Notiztext ein.'
    return
  }

  saving.value = true
  error.value = ''

  try {
    const cleanedContent = cleanOleResidue(content.value.trim())
    const firstLine = cleanedContent.split('\n')[0].trim()
    const autoTitle = firstLine.substring(0, 60) || `Notiz (${new Date().toLocaleDateString('de-CH')})`

    const payload = {
      folder_id: fixedFolderId.value || null,
      project_id: fixedProjectId.value || (selectedProjectId.value === 'auto' ? 'auto' : (selectedProjectId.value || null)),
      task_id: selectedTaskId.value || null,
      type: 'note',
      category: category.value,
      title: autoTitle,
      content: cleanedContent,
      visibility: 'all',
      entry_date: entryDate.value
    }

    const res = await $fetch<any>('/api/journals', {
      method: 'POST',
      headers: authHeaders(),
      body: payload
    })

    emit('saved', res.entry || res)
    emit('close')
  } catch (err: any) {
    error.value = err.data?.statusMessage || err.message || 'Fehler beim Speichern der Notiz'
  } finally {
    saving.value = false
  }
}
</script>
