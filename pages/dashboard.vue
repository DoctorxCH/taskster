<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
          Willkommen zurück, {{ user?.name }} 👋
        </h1>
        <p class="text-sm text-slate-400 mt-1">
          <span v-if="user?.company_name" class="text-emerald-400 font-semibold">{{ user.company_name }}</span>
          <span v-else class="text-slate-300 font-medium">Privater Arbeitsbereich</span>
          – Übersicht deiner Projektordner & Initiativen
        </p>
      </div>

      <div class="flex items-center space-x-3">
        <button
          @click="openNewFolderModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg"
        >
          <span>+ Neuer Projektordner</span>
        </button>
      </div>
    </div>

    <!-- Free-Plan Info Box if applicable -->
    <div
      v-if="!user?.is_pro && !user?.company_id && !user?.is_superadmin"
      class="mb-8 p-4 rounded-xl bg-amber-950/40 border border-amber-800/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
    >
      <div class="flex items-center space-x-3">
        <span class="text-2xl">⚡</span>
        <div>
          <h4 class="text-sm font-bold text-amber-200">Du nutzt aktuell den Taskster Free Plan</h4>
          <p class="text-xs text-amber-300/80 mt-0.5">
            Limitierungen: Maximal 1 Projektordner, max. in 3 Projekten gleichzeitig mitarbeiten, max. 5 Teammitglieder pro Projekt.
          </p>
        </div>
      </div>
      <NuxtLink
        to="/admin"
        v-if="user?.is_superadmin"
        class="taskster_button px-6 text-xs h-[42px] rounded-lg"
      >
        Plan verwalten
      </NuxtLink>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
      <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Projektordner</div>
        <div class="text-3xl font-black text-white mt-2">{{ folders.length }}</div>
        <div class="text-xs text-slate-500 mt-1">Übergeordnete Ordner</div>
      </div>

      <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Aktive Projekte</div>
        <div class="text-3xl font-black text-emerald-400 mt-2">{{ totalProjects }}</div>
        <div class="text-xs text-slate-500 mt-1">In deinen Arbeitsbereichen</div>
      </div>

      <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800">
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</div>
        <div class="text-sm font-bold text-emerald-400 mt-3 flex items-center space-x-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
          <span>Synchronisiert & Bereit</span>
        </div>
        <div class="text-xs text-slate-500 mt-1">Echtzeit-Datenbank aktiv</div>
      </div>
    </div>

    <!-- Folders List Section -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-white tracking-wide flex items-center space-x-2">
          <span>📁</span>
          <span>Deine Projektordner</span>
        </h2>
        <span class="text-xs text-slate-400">{{ folders.length }} Ordner verfügbar</span>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12 text-slate-500">
        Lade Arbeitsbereiche...
      </div>

      <!-- Empty State -->
      <div v-else-if="folders.length === 0" class="text-center py-16 bg-slate-900/50 rounded-2xl border border-dashed border-slate-800">
        <span class="text-4xl">📁</span>
        <h3 class="text-base font-bold text-slate-200 mt-3">Noch kein Projektordner vorhanden</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1 mb-4">
          Erstelle deinen ersten Ordner, um Unterprojekte, Bauvorhaben und Meilensteine zu organisieren.
        </p>
        <button
          @click="openNewFolderModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg"
        >
          <span>+ Ordner erstellen</span>
        </button>
      </div>

      <!-- Folders Cards -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="folder in folders"
          :key="folder.id"
          class="bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-2xl p-6 transition flex flex-col justify-between group shadow-lg"
        >
          <div>
            <div class="flex items-start justify-between mb-3">
              <span class="text-2xl">{{ folder.icon || '📁' }}</span>
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                {{ folder.project_count }} {{ folder.project_count === 1 ? 'Projekt' : 'Projekte' }}
              </span>
            </div>

            <h3 class="text-base font-bold text-white group-hover:text-blue-400 transition mb-1">
              {{ folder.name }}
            </h3>
            <p class="text-xs text-slate-400 flex items-center space-x-1">
              <span>Owner:</span>
              <span class="text-slate-300 font-medium">{{ folder.owner_name }}</span>
              <span v-if="user?.id === folder.owner_id" class="text-[10px] px-1.5 py-0.2 rounded bg-blue-950 text-blue-300 border border-blue-800 font-bold ml-1">
                Du (Owner)
              </span>
            </p>
            <p v-if="folder.company_name" class="text-[11px] text-emerald-400/80 mt-1">
              🏢 {{ folder.company_name }}
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">
              Erstellt {{ new Date(folder.created_at).toLocaleDateString('de-CH') }}
            </span>
            <div class="flex items-center space-x-2">
              <button
                v-if="user?.id === folder.owner_id || user?.is_superadmin"
                @click="openEditFolderModal(folder)"
                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition flex items-center space-x-1"
                title="Projektordner anpassen (Name & Icon)"
              >
                <span>✏️</span>
                <span>Bearbeiten</span>
              </button>
              <NuxtLink
                :to="`/folders/${folder.id}`"
                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-800 hover:bg-blue-600 hover:text-white text-slate-200 transition"
              >
                Öffnen →
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: New Folder -->
    <div v-if="showNewFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neuen Projektordner anlegen</h3>
        <p class="text-xs text-slate-400 mb-4">
          Projektordner bilden die oberste Organisationsebene für Bauträger, Standorte oder Großvorhaben.
        </p>

        <div v-if="folderModalError" class="mb-4 p-3 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
          {{ folderModalError }}
        </div>

        <form @submit.prevent="createFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Name des Projektordners</label>
            <input
              v-model="newFolderName"
              type="text"
              required
              placeholder="z.B. FTTH Glasfaserausbau Region Nord"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1.5">Icon auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-36 overflow-y-auto p-2 bg-slate-950 rounded-xl border border-slate-800">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="newFolderIcon = item.icon"
                class="w-9 h-9 rounded-lg flex items-center justify-center text-lg transition border"
                :class="newFolderIcon === item.icon ? 'bg-blue-950 border-blue-500 ring-2 ring-blue-500/50 scale-110' : 'border-slate-800 hover:border-slate-700 hover:bg-slate-900'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ausgewählt: <span class="text-white text-sm mr-1">{{ newFolderIcon }}</span></p>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="showNewFolderModal = false; folderModalError = ''"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="creatingFolder || !newFolderName.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ creatingFolder ? 'Erstelle...' : 'Ordner erstellen' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Folder (Owner only) -->
    <div v-if="showEditFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Projektordner bearbeiten</h3>
        <p class="text-xs text-slate-400 mb-4">
          Passe den Namen und das Erkennungs-Icon dieses Projektordners an.
        </p>

        <div v-if="editFolderError" class="mb-4 p-3 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
          {{ editFolderError }}
        </div>

        <form @submit.prevent="updateFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Name des Projektordners</label>
            <input
              v-model="editFolderName"
              type="text"
              required
              placeholder="z.B. Peters Privates Renovationsprojekt"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1.5">Icon aus Liste auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-40 overflow-y-auto p-2 bg-slate-950 rounded-xl border border-slate-800">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="editFolderIcon = item.icon"
                class="w-9 h-9 rounded-lg flex items-center justify-center text-lg transition border"
                :class="editFolderIcon === item.icon ? 'bg-blue-950 border-blue-500 ring-2 ring-blue-500/50 scale-110' : 'border-slate-800 hover:border-slate-700 hover:bg-slate-900'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ausgewähltes Icon: <span class="text-white text-base mr-1">{{ editFolderIcon }}</span></p>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="showEditFolderModal = false; editFolderError = ''"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingFolder || !editFolderName.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ savingFolder ? 'Wird gespeichert...' : 'Änderungen speichern' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, authHeaders } = useAuth()

const folders = ref<any[]>([])
const loading = ref(true)

// New Folder
const showNewFolderModal = ref(false)
const newFolderName = ref('')
const newFolderIcon = ref('📁')
const creatingFolder = ref(false)
const folderModalError = ref('')

// Edit Folder
const showEditFolderModal = ref(false)
const editFolderId = ref('')
const editFolderName = ref('')
const editFolderIcon = ref('📁')
const savingFolder = ref(false)
const editFolderError = ref('')

const availableFolderIcons = [
  // Job & Gewerbe
  { icon: '📁', label: 'Standard Ordner' },
  { icon: '🏗️', label: 'Bau & Tiefbau' },
  { icon: '💻', label: 'IT & Software' },
  { icon: '⚡', label: 'Elektro & Handwerk' },
  { icon: '🌐', label: 'Netzwerk & LWL' },
  { icon: '🏢', label: 'Unternehmen & B2B' },
  { icon: '📊', label: 'Finanzen & Analyse' },
  { icon: '🛠️', label: 'Werkstatt & Service' },
  { icon: '🚀', label: 'Projekte & Launch' },
  { icon: '🚚', label: 'Logistik & Transport' },
  { icon: '🔒', label: 'Sicherheit & Audit' },
  // Privat & Freizeit
  { icon: '🏠', label: 'Haus & Umbau' },
  { icon: '🏡', label: 'Garten & Aussen' },
  { icon: '🛋️', label: 'Wohnen & Interior' },
  { icon: '🎂', label: 'Event & Feier' },
  { icon: '✈️', label: 'Reisen & Urlaub' },
  { icon: '🚗', label: 'Fahrzeuge & Garage' },
  { icon: '📑', label: 'Privat & Steuern' },
  { icon: '🎯', label: 'Ziele & Pläne' },
  { icon: '📦', label: 'Umzug & Lager' },
  { icon: '🎨', label: 'Kreativ & Hobby' }
]

const totalProjects = computed(() => {
  return folders.value.reduce((acc, f) => acc + (f.project_count || 0), 0)
})

const openNewFolderModal = () => {
  newFolderName.value = ''
  newFolderIcon.value = '📁'
  folderModalError.value = ''
  showNewFolderModal.value = true
}

const openEditFolderModal = (folder: any) => {
  editFolderId.value = folder.id
  editFolderName.value = folder.name
  editFolderIcon.value = folder.icon || '📁'
  editFolderError.value = ''
  showEditFolderModal.value = true
}

const loadFolders = async () => {
  loading.value = true
  try {
    const res = await $fetch<{ folders: any[] }>('/api/folders', {
      headers: authHeaders()
    })
    folders.value = res.folders || []
  } catch (err: any) {
    if (err.statusCode === 401) {
      navigateTo('/login')
    }
  } finally {
    loading.value = false
  }
}

const createFolder = async () => {
  folderModalError.value = ''
  creatingFolder.value = true
  try {
    await $fetch('/api/folders', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        name: newFolderName.value,
        icon: newFolderIcon.value
      }
    })
    showNewFolderModal.value = false
    newFolderName.value = ''
    newFolderIcon.value = '📁'
    await loadFolders()
  } catch (err: any) {
    folderModalError.value = err.data?.statusMessage || 'Ordner konnte nicht erstellt werden'
  } finally {
    creatingFolder.value = false
  }
}

const updateFolder = async () => {
  editFolderError.value = ''
  savingFolder.value = true
  try {
    await $fetch(`/api/folders/${editFolderId.value}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        name: editFolderName.value,
        icon: editFolderIcon.value
      }
    })
    showEditFolderModal.value = false
    await loadFolders()
  } catch (err: any) {
    editFolderError.value = err.data?.statusMessage || 'Ordner konnte nicht aktualisiert werden'
  } finally {
    savingFolder.value = false
  }
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
  await loadFolders()
})
</script>
