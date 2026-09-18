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
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-white tracking-wide flex items-center space-x-2">
            <span>🏗️</span>
            <span>Projekte & Bauvorhaben</span>
          </h2>
          <span class="text-xs text-slate-400">{{ projects.length }} Projekte</span>
        </div>

        <div v-if="projects.length === 0" class="text-center py-12 bg-slate-900/50 rounded-2xl border border-dashed border-slate-800">
          <span class="text-3xl">🏗️</span>
          <h3 class="text-sm font-bold text-slate-200 mt-2">Noch keine Projekte in diesem Ordner</h3>
          <p class="text-xs text-slate-400 mt-1 mb-4">Erstelle jetzt das erste Bau- oder Arbeitsprojekt.</p>
          <button
            @click="showNewProjectModal = true"
            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400"
          >
            + Projekt anlegen
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="project in projects"
            :key="project.id"
            class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-6 transition flex flex-col justify-between group shadow-lg"
          >
            <div>
              <div class="flex items-start justify-between mb-3">
                <span class="text-xl">📋</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-950/60 text-emerald-400 border border-emerald-800/60 uppercase tracking-wider">
                  {{ project.status }}
                </span>
              </div>

              <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition mb-2">
                {{ project.title }}
              </h3>

              <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-800/80 my-3 text-center">
                <div>
                  <div class="text-[10px] text-slate-500 uppercase">Listen</div>
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
      </div>

      <!-- Folder Field Definitions Section -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-base font-bold text-white flex items-center space-x-2">
              <span>⚙️</span>
              <span>Benutzerdefinierte Felder (Ordner-Vererbung)</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
              Alle hier definierten Felder werden automatisch an alle Aufgaben in diesem Ordner vererbt (z.B. Priorität, Verantwortlicher, Status, Budget).
            </p>
          </div>
          <button
            @click="showNewFieldModal = true"
            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
          >
            + Feld definieren
          </button>
        </div>

        <div v-if="fields.length === 0" class="text-center py-8 text-xs text-slate-500">
          Noch keine benutzerdefinierten Felder definiert.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
          <div
            v-for="field in fields"
            :key="field.id"
            class="p-4 rounded-xl bg-slate-950 border border-slate-800"
          >
            <div class="flex items-center justify-between mb-1">
              <span class="text-xs font-bold text-slate-200">{{ field.label }}</span>
              <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">
                {{ field.field_type }}
              </span>
            </div>
            <div class="text-[11px] font-mono text-emerald-400/80 mb-2">
              Key: {{ field.field_key }}
            </div>
            <div v-if="field.options && field.options.length > 0" class="flex flex-wrap gap-1">
              <span
                v-for="opt in field.options"
                :key="opt"
                class="text-[9px] px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-slate-400"
              >
                {{ opt }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: New Project -->
    <div v-if="showNewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neues Projekt anlegen</h3>
        <p class="text-xs text-slate-400 mb-4">
          Das Projekt wird innerhalb des Ordners "{{ folder?.name }}" erstellt und erbt dessen Einstellungen und Felddefinitionen.
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

    <!-- Modal: New Field Definition -->
    <div v-if="showNewFieldModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neues Feld für Ordner definieren</h3>

        <form @submit.prevent="createField" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Feldbezeichnung (Label)</label>
            <input
              v-model="newFieldLabel"
              type="text"
              required
              placeholder="z.B. Priorität oder Kostenstelle"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Feldtyp</label>
            <select
              v-model="newFieldType"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            >
              <option value="text">Textzeile</option>
              <option value="select">Auswahlliste (Dropdown)</option>
              <option value="number">Zahl / Währung</option>
              <option value="date">Datum</option>
            </select>
          </div>

          <div v-if="newFieldType === 'select'">
            <label class="block text-xs font-medium text-slate-300 mb-1">Optionen (Komma-getrennt)</label>
            <input
              v-model="newFieldOptionsRaw"
              type="text"
              placeholder="z.B. Offen, In Prüfung, Freigegeben"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showNewFieldModal = false"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition"
            >
              Feld speichern
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

const showNewProjectModal = ref(false)
const newProjectTitle = ref('')
const creatingProject = ref(false)
const projectModalError = ref('')

const showNewFieldModal = ref(false)
const newFieldLabel = ref('')
const newFieldType = ref('text')
const newFieldOptionsRaw = ref('')

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
      body: { folder_id: folderId, title: newProjectTitle.value }
    })
    showNewProjectModal.value = false
    newProjectTitle.value = ''
    await loadFolderData()
  } catch (err: any) {
    projectModalError.value = err.data?.statusMessage || 'Projekt konnte nicht erstellt werden'
  } finally {
    creatingProject.value = false
  }
}

const createField = async () => {
  try {
    const options = newFieldType.value === 'select'
      ? newFieldOptionsRaw.value.split(',').map((s) => s.trim()).filter(Boolean)
      : []

    await $fetch(`/api/folders/${folderId}/fields`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        field_key: newFieldLabel.value,
        label: newFieldLabel.value,
        field_type: newFieldType.value,
        options
      }
    })
    showNewFieldModal.value = false
    newFieldLabel.value = ''
    newFieldOptionsRaw.value = ''
    await loadFolderData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Feld konnte nicht hinzugefügt werden')
  }
}

onMounted(async () => {
  await loadFolderData()
})
</script>
