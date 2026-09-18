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
              @click="showNewProjectModal = true"
              class="px-4 py-2 rounded-xl text-sm font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition flex items-center space-x-2 shadow-lg shadow-emerald-500/10"
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
              @click="showNewProjectModal = true"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition flex items-center space-x-1.5 shadow-lg shadow-emerald-500/10"
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
            @click="showNewProjectModal = true"
            class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400"
          >
            + Projekt anlegen
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

    <!-- Modal: New Project -->
    <div v-if="showNewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neues Projekt anlegen</h3>
        <p class="text-xs text-slate-400 mb-4">
          Das Projekt wird innerhalb des Ordners "{{ folder?.name }}" erstellt.
        </p>

        <div v-if="projectModalError" class="mb-4 p-3 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
          {{ projectModalError }}
        </div>

        <form @submit.prevent="createProject" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Projekttitel / Name</label>
            <input
              v-model="newProjectTitle"
              type="text"
              required
              placeholder="z.B. Website Relaunch Q3"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Project Custom Fields if any exist -->
          <div v-if="projectFields.length > 0" class="pt-3 border-t border-slate-800 space-y-3">
            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">
              Projekt-Felder
            </h4>
            <div v-for="f in projectFields" :key="f.id">
              <label class="block text-xs font-medium text-slate-300 mb-1">{{ f.label }}</label>
              <select
                v-if="f.field_type === 'select'"
                v-model="newProjectCustomData[f.field_key]"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-emerald-500"
              >
                <option value="">-- Nicht ausgewählt --</option>
                <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
              </select>
              <input
                v-else
                v-model="newProjectCustomData[f.field_key]"
                :type="f.field_type === 'number' ? 'number' : 'text'"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showNewProjectModal = false; projectModalError = ''"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="creatingProject"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition disabled:opacity-50"
            >
              {{ creatingProject ? 'Wird erstellt...' : 'Projekt erstellen' }}
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

const showNewProjectModal = ref(false)
const newProjectTitle = ref('')
const newProjectCustomData = ref<Record<string, any>>({})
const creatingProject = ref(false)
const projectModalError = ref('')

const projectFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type === 'project')
})

const getFieldLabel = (key: string) => {
  const f = fields.value.find((item: any) => item.field_key === key)
  return f ? f.label : key
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
    await $fetch('/api/projects', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        folder_id: folderId,
        title: newProjectTitle.value,
        custom_data: newProjectCustomData.value
      }
    })
    showNewProjectModal.value = false
    newProjectTitle.value = ''
    newProjectCustomData.value = {}
    await loadFolderData()
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
