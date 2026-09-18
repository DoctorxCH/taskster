<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-400 mb-6">
      <NuxtLink to="/dashboard" class="hover:text-emerald-400 transition">Dashboard</NuxtLink>
      <span>/</span>
      <span class="text-slate-200 font-medium">{{ folder?.name || 'Ordner' }}</span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-500">
      Lade Ordnerdetails und Projekte...
    </div>

    <div v-else-if="folder">
      <!-- Folder Header -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center space-x-3 mb-2">
              <span class="text-3xl">📂</span>
              <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ folder.name }}</h1>
            </div>
            <p class="text-xs text-slate-400 flex items-center space-x-3">
              <span>Owner: <strong class="text-slate-200">{{ folder.owner_name }}</strong></span>
              <span v-if="folder.company_name" class="text-emerald-400">• {{ folder.company_name }}</span>
              <span>• Erstellt am {{ new Date(folder.created_at).toLocaleDateString('de-CH') }}</span>
            </p>
          </div>

          <div class="flex items-center space-x-3">
            <button
              @click="openNewProjectModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>+ Neues Projekt anlegen</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Projects Section -->
      <div class="mb-12">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div class="flex items-center space-x-3">
            <h2 class="text-lg font-bold text-white tracking-wide flex items-center space-x-2">
              <span>📋</span>
              <span>Projekte</span>
            </h2>
            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-medium">
              {{ projects.length }}
            </span>
          </div>

          <!-- View Mode Toggle & Actions -->
          <div class="flex items-center space-x-3">
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-1 flex items-center space-x-1">
              <button
                @click="projectViewMode = 'grid'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center space-x-1.5"
                :class="projectViewMode === 'grid' ? 'bg-slate-800 text-emerald-400 shadow-sm' : 'text-slate-400 hover:text-white'"
                title="Kachel-Ansicht"
              >
                <span>▦</span>
                <span>Kacheln</span>
              </button>
              <button
                @click="projectViewMode = 'list'"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center space-x-1.5"
                :class="projectViewMode === 'list' ? 'bg-slate-800 text-emerald-400 shadow-sm' : 'text-slate-400 hover:text-white'"
                title="Listen- / Tabellenansicht"
              >
                <span>☰</span>
                <span>Liste</span>
              </button>
            </div>

            <button
              @click="openNewProjectModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>+ Neues Projekt</span>
            </button>
          </div>
        </div>

        <div v-if="projects.length === 0" class="text-center py-16 bg-slate-900/50 rounded-2xl border border-dashed border-slate-800">
          <span class="text-3xl">📋</span>
          <h3 class="text-sm font-bold text-slate-200 mt-2">Noch keine Projekte in diesem Ordner</h3>
          <p class="text-xs text-slate-400 mt-1 mb-4">Erstelle jetzt das erste Projekt für dein Team.</p>
          <button
            @click="openNewProjectModal"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg"
          >
            <span>+ Neues Projekt anlegen</span>
          </button>
        </div>

        <!-- VIEW MODE 1: GRID / KACHELN -->
        <div v-else-if="projectViewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="project in projects"
            :key="project.id"
            class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-6 transition flex flex-col justify-between group shadow-lg"
          >
            <div>
              <div class="flex items-start justify-between mb-3">
                <span class="text-xl">📁</span>
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                  :class="project.status === 'completed' ? 'bg-slate-800 text-slate-300' : 'bg-emerald-950/60 text-emerald-400 border border-emerald-800/60'"
                >
                  {{ project.status }}
                </span>
              </div>

              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition mb-2">
                {{ project.title }}
              </h3>

              <!-- Project Custom Fields chips -->
              <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-1.5 mb-3">
                <span
                  v-for="(val, key) in project.custom_data"
                  :key="key"
                  class="text-[10px] px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-slate-300 font-medium"
                >
                  <strong class="text-emerald-400">{{ getFieldLabel(key) }}:</strong> {{ val }}
                </span>
              </div>

              <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-800/80 my-3 text-center">
                <div>
                  <div class="text-[10px] text-slate-500 uppercase">Abschnitte</div>
                  <div class="text-sm font-bold text-slate-200">{{ project.list_count }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-slate-500 uppercase">Aufgaben</div>
                  <div class="text-sm font-bold text-slate-200">{{ project.task_count }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-slate-500 uppercase">Team</div>
                  <div class="text-sm font-bold text-slate-200">{{ project.member_count }}</div>
                </div>
              </div>
            </div>

            <div class="pt-2 flex items-center justify-end">
              <NuxtLink
                :to="`/projects/${project.id}`"
                class="w-full text-center py-2 rounded-lg text-xs font-bold bg-slate-800 hover:bg-emerald-500 hover:text-slate-950 text-slate-200 transition"
              >
                Projekt öffnen →
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- VIEW MODE 2: LIST / TABELLE -->
        <div v-else class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-800">
                <tr>
                  <th class="py-3.5 px-4">Projekttitel</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4">Abschnitte & Aufgaben</th>
                  <th class="py-3.5 px-4">Projekt-Felder</th>
                  <th class="py-3.5 px-4 text-right">Aktion</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/80 text-slate-300">
                <tr v-for="project in projects" :key="project.id" class="hover:bg-slate-800/40 transition">
                  <td class="py-3.5 px-4">
                    <NuxtLink :to="`/projects/${project.id}`" class="font-bold text-slate-100 hover:text-emerald-400 transition text-sm">
                      {{ project.title }}
                    </NuxtLink>
                  </td>
                  <td class="py-3.5 px-4">
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                      :class="project.status === 'completed' ? 'bg-slate-800 text-slate-400' : 'bg-emerald-950 text-emerald-400 border border-emerald-800'"
                    >
                      {{ project.status }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="text-slate-200 font-semibold">{{ project.task_count }} Aufgaben</span>
                    <span class="text-slate-500"> in {{ project.list_count }} Abschnitten</span>
                  </td>
                  <td class="py-3.5 px-4">
                    <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-1">
                      <span
                        v-for="(val, key) in project.custom_data"
                        :key="key"
                        class="text-[10px] px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-slate-300"
                      >
                        {{ getFieldLabel(key) }}: <strong class="text-white">{{ val }}</strong>
                      </span>
                    </div>
                    <span v-else class="text-slate-600">-</span>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <NuxtLink
                      :to="`/projects/${project.id}`"
                      class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-emerald-500 hover:text-slate-950 text-slate-200 transition inline-block"
                    >
                      Öffnen →
                    </NuxtLink>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: New Project (with Templates support) -->
    <div v-if="showNewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col my-8">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-800 flex items-start justify-between bg-slate-950/50">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-xl">📁</span>
              <h3 class="text-lg font-bold text-white">Neues Projekt erstellen</h3>
            </div>
            <p class="text-xs text-slate-400 mt-1">
              Erstelle ein Projekt im Ordner <span class="text-slate-200 font-semibold">«{{ folder?.name }}»</span>
            </p>
          </div>
          <button
            type="button"
            @click="closeNewProjectModal"
            class="text-slate-400 hover:text-white text-lg p-1 rounded-lg hover:bg-slate-800 transition"
          >
            ✕
          </button>
        </div>

        <div v-if="projectModalError" class="mx-6 mt-4 p-3 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-300 text-xs flex items-center justify-between">
          <span>{{ projectModalError }}</span>
          <button type="button" @click="projectModalError = ''" class="text-rose-400 hover:text-rose-200 font-bold ml-2">✕</button>
        </div>

        <form @submit.prevent="createProject" class="p-6 space-y-6">
          <!-- Creation Mode Selector (Template vs Blank) -->
          <div class="grid grid-cols-2 gap-3 p-1 bg-slate-950 rounded-xl border border-slate-800">
            <button
              type="button"
              @click="useTemplateMode = true"
              class="flex items-center justify-center space-x-2 py-2.5 px-4 rounded-lg text-xs font-bold transition cursor-pointer"
              :class="useTemplateMode ? 'bg-blue-600 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'"
            >
              <span>📋</span>
              <span>Aus Vorlage erstellen (Empfohlen)</span>
            </button>
            <button
              type="button"
              @click="useTemplateMode = false; selectedTemplateId = null"
              class="flex items-center justify-center space-x-2 py-2.5 px-4 rounded-lg text-xs font-bold transition cursor-pointer"
              :class="!useTemplateMode ? 'bg-slate-800 text-white shadow-md' : 'text-slate-400 hover:text-slate-200'"
            >
              <span>📝</span>
              <span>Leeres Projekt (Blanko)</span>
            </button>
          </div>

          <!-- SECTION A: TEMPLATE BROWSER -->
          <div v-if="useTemplateMode" class="space-y-4">
            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
              <!-- Category Pills -->
              <div class="flex items-center space-x-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                <button
                  type="button"
                  @click="templateFilterCategory = 'all'"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap cursor-pointer"
                  :class="templateFilterCategory === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-950 text-slate-400 hover:text-slate-200 border border-slate-800'"
                >
                  Alle Vorlagen ({{ templates.length }})
                </button>
                <button
                  type="button"
                  @click="templateFilterCategory = 'job'"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                  :class="templateFilterCategory === 'job' ? 'bg-sky-600 text-white' : 'bg-slate-950 text-slate-400 hover:text-sky-300 border border-slate-800'"
                >
                  <span>💼</span>
                  <span>Job & Gewerbe</span>
                </button>
                <button
                  type="button"
                  @click="templateFilterCategory = 'private'"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                  :class="templateFilterCategory === 'private' ? 'bg-purple-600 text-white' : 'bg-slate-950 text-slate-400 hover:text-purple-300 border border-slate-800'"
                >
                  <span>🏠</span>
                  <span>Privat</span>
                </button>
              </div>

              <!-- Search -->
              <div class="w-full sm:w-64">
                <input
                  v-model="templateSearchQuery"
                  type="text"
                  placeholder="🔍 Vorlage suchen..."
                  class="w-full px-3 py-1.5 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loadingTemplates" class="py-12 text-center text-xs text-slate-500">
              Vorlagen werden geladen...
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredTemplates.length === 0" class="py-8 text-center bg-slate-950/60 rounded-xl border border-dashed border-slate-800 text-xs text-slate-400">
              Keine Vorlagen gefunden.
            </div>

            <!-- Templates Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
              <div
                v-for="tmpl in filteredTemplates"
                :key="tmpl.id"
                @click="selectTemplate(tmpl)"
                class="p-4 rounded-xl border transition cursor-pointer flex flex-col justify-between text-left"
                :class="selectedTemplateId === tmpl.id
                  ? 'bg-blue-950/40 border-blue-500 ring-2 ring-blue-500/40'
                  : 'bg-slate-950/60 border-slate-800 hover:border-slate-700 hover:bg-slate-950'"
              >
                <div>
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2">
                      <span class="text-xl">{{ tmpl.category === 'private' ? '🏠' : (tmpl.icon === 'Network' ? '🌐' : (tmpl.icon === 'Zap' ? '⚡' : '💼')) }}</span>
                      <div>
                        <h4 class="text-sm font-bold text-white">{{ tmpl.name }}</h4>
                        <span v-if="tmpl.subcategory" class="text-[10px] text-slate-400">{{ tmpl.subcategory }}</span>
                      </div>
                    </div>
                    <span
                      class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                      :class="tmpl.category === 'job' ? 'bg-sky-950 text-sky-400 border border-sky-800' : 'bg-purple-950 text-purple-400 border border-purple-800'"
                    >
                      {{ tmpl.category === 'job' ? 'Job' : 'Privat' }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-400 line-clamp-2 mb-3 leading-relaxed">
                    {{ tmpl.description }}
                  </p>
                </div>

                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-900 text-[10px]">
                  <span class="px-2 py-0.5 rounded bg-slate-800/80 text-slate-300">
                    📂 {{ (tmpl.lists || []).length }} Listen
                  </span>
                  <span class="px-2 py-0.5 rounded bg-slate-800/80 text-slate-300">
                    🏷️ {{ (tmpl.fields || tmpl.custom_fields || []).length }} Custom Fields
                  </span>
                  <span
                    v-if="(tmpl.fields || tmpl.custom_fields || []).some((f: any) => f.logic_rules)"
                    class="px-2 py-0.5 rounded bg-amber-950/80 text-amber-300 border border-amber-800/50"
                  >
                    ⚡ Mit Feld-Logik
                  </span>
                </div>
              </div>
            </div>

            <!-- Preview of Selected Template Features -->
            <div v-if="selectedTemplate" class="p-4 rounded-xl bg-slate-950 border border-blue-900/40 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-400 uppercase tracking-wider flex items-center space-x-1.5">
                  <span>✓ Ausgewählte Vorlage:</span>
                  <span class="text-white">{{ selectedTemplate.name }}</span>
                </span>
                <span class="text-[11px] text-slate-400">Automatische Konfiguration</span>
              </div>

              <!-- Included Lists -->
              <div>
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">Enthaltene Listen / Projektphasen:</span>
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="(listName, idx) in selectedTemplate.lists"
                    :key="idx"
                    class="px-2.5 py-1 rounded-md text-xs bg-slate-900 border border-slate-700 text-slate-200"
                  >
                    {{ idx + 1 }}. {{ listName }}
                  </span>
                </div>
              </div>

              <!-- Included Custom Fields with Logic -->
              <div v-if="(selectedTemplate.fields || selectedTemplate.custom_fields || []).length > 0">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1">Benutzerdefinierte Felder mit Logik:</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div
                    v-for="cf in (selectedTemplate.fields || selectedTemplate.custom_fields || [])"
                    :key="cf.field_key"
                    class="p-2 rounded bg-slate-900/90 border border-slate-800 text-xs"
                  >
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-slate-200">{{ cf.label }}</span>
                      <span class="text-[10px] text-slate-500 font-mono">({{ cf.field_type }})</span>
                    </div>
                    <!-- Conditional Logic Badge -->
                    <div v-if="cf.logic_rules" class="mt-1 text-[10px] text-amber-300 flex items-center space-x-1">
                      <span>⚡</span>
                      <span>Nur sichtbar wenn <code class="text-amber-200">{{ cf.logic_rules.depends_on_field }}</code> = "{{ cf.logic_rules.depends_on_value }}"</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION B: PROJECT TITLE & GENERAL SETTINGS -->
          <div class="space-y-4 pt-2 border-t border-slate-800">
            <div>
              <label class="block text-xs font-semibold text-slate-200 mb-1">
                Projekttitel / Name <span class="text-rose-400">*</span>
              </label>
              <input
                v-model="newProjectTitle"
                type="text"
                required
                placeholder="z.B. FTTH Ausbau Bern Süd oder Wohnzimmer Renovation"
                class="w-full px-3 py-2.5 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
              />
              <p class="text-[11px] text-slate-500 mt-1">
                Gib dem Projekt eine aussagekräftige Bezeichnung.
              </p>
            </div>

            <!-- If Blanko Mode and folder has existing project fields -->
            <div v-if="!useTemplateMode && projectFields.length > 0" class="space-y-3 pt-3 border-t border-slate-800">
              <h4 class="text-xs font-bold text-blue-400 uppercase tracking-wider">
                Projekt-Felder dieses Ordners
              </h4>
              <div v-for="f in projectFields" :key="f.id">
                <label class="block text-xs font-medium text-slate-300 mb-1">{{ f.label }}</label>
                <select
                  v-if="f.field_type === 'select'"
                  v-model="newProjectCustomData[f.field_key]"
                  class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-blue-500"
                >
                  <option value="">-- Nicht ausgewählt --</option>
                  <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <input
                  v-else
                  v-model="newProjectCustomData[f.field_key]"
                  :type="f.field_type === 'number' ? 'number' : 'text'"
                  class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>
          </div>

          <!-- Bottom Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="closeNewProjectModal"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="creatingProject || (useTemplateMode && !selectedTemplateId) || !newProjectTitle.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ creatingProject ? 'Wird erstellt...' : (useTemplateMode ? 'Projekt aus Vorlage erstellen' : 'Projekt erstellen') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { authHeaders } = useAuth()
const folderId = route.params.id as string

const folder = ref<any>(null)
const projects = ref<any[]>([])
const fields = ref<any[]>([])
const loading = ref(true)
const projectViewMode = ref<'grid' | 'list'>('grid')

// Project creation & Template state
const showNewProjectModal = ref(false)
const newProjectTitle = ref('')
const newProjectCustomData = ref<Record<string, any>>({})
const creatingProject = ref(false)
const projectModalError = ref('')

const templates = ref<any[]>([])
const loadingTemplates = ref(false)
const useTemplateMode = ref(true)
const selectedTemplateId = ref<string | null>(null)
const templateFilterCategory = ref<'all' | 'job' | 'private'>('all')
const templateSearchQuery = ref('')

const selectedTemplate = computed(() => {
  return templates.value.find((t: any) => t.id === selectedTemplateId.value)
})

const filteredTemplates = computed(() => {
  return templates.value.filter((t: any) => {
    const matchCat = templateFilterCategory.value === 'all' || t.category === templateFilterCategory.value
    const q = templateSearchQuery.value.toLowerCase().trim()
    const matchSearch = !q || (
      t.name?.toLowerCase().includes(q) ||
      t.description?.toLowerCase().includes(q) ||
      t.subcategory?.toLowerCase().includes(q)
    )
    return matchCat && matchSearch
  })
})

const projectFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type === 'project')
})

const getFieldLabel = (key: string) => {
  const f = fields.value.find((item: any) => item.field_key === key)
  return f ? f.label : key
}

const fetchTemplates = async () => {
  if (templates.value.length > 0) return
  loadingTemplates.value = true
  try {
    const res = await $fetch<any>('/api/templates', {
      headers: authHeaders()
    })
    templates.value = res.templates || []
    if (templates.value.length > 0 && !selectedTemplateId.value) {
      selectTemplate(templates.value[0])
    }
  } catch (err: any) {
    console.error('Failed to load templates', err)
  } finally {
    loadingTemplates.value = false
  }
}

const selectTemplate = (tmpl: any) => {
  selectedTemplateId.value = tmpl.id
  if (!newProjectTitle.value || templates.value.some((t: any) => t.name === newProjectTitle.value)) {
    newProjectTitle.value = tmpl.name
  }
}

const openNewProjectModal = () => {
  showNewProjectModal.value = true
  projectModalError.value = ''
  fetchTemplates()
}

const closeNewProjectModal = () => {
  showNewProjectModal.value = false
  projectModalError.value = ''
}

const loadFolderData = async () => {
  loading.value = true
  try {
    const res = await $fetch<any>(`/api/folders/${folderId}`, {
      headers: authHeaders()
    })
    folder.value = res.folder
    projects.value = res.projects || []
    fields.value = res.fields || []
  } catch (err: any) {
    if (err.statusCode === 404 || err.statusCode === 401) {
      navigateTo('/dashboard')
    }
  } finally {
    loading.value = false
  }
}

const createProject = async () => {
  projectModalError.value = ''
  creatingProject.value = true
  try {
    const payload: any = {
      folder_id: folderId,
      title: newProjectTitle.value,
      custom_data: newProjectCustomData.value
    }
    if (useTemplateMode.value && selectedTemplateId.value) {
      payload.template_id = selectedTemplateId.value
    }
    const res = await $fetch<any>('/api/projects', {
      method: 'POST',
      headers: authHeaders(),
      body: payload
    })
    showNewProjectModal.value = false
    newProjectTitle.value = ''
    newProjectCustomData.value = {}
    selectedTemplateId.value = null
    await loadFolderData()
    if (res?.project?.id) {
      navigateTo(`/projects/${res.project.id}`)
    }
  } catch (err: any) {
    projectModalError.value = err.data?.statusMessage || 'Projekt konnte nicht erstellt werden'
  } finally {
    creatingProject.value = false
  }
}

onMounted(async () => {
  await loadFolderData()
})
</script>
