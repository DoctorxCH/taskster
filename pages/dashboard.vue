<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Banner with gentle wallpaper backdrop & friendly greeting -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-cyan-950 to-slate-900 text-white p-6 sm:p-8 mb-8 shadow-sm">
      <img
        src="/wallpapers/mountain-lake.jpg"
        alt="Taskster Banner"
        class="absolute inset-0 w-full h-full object-cover opacity-25 mix-blend-overlay"
      />
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 backdrop-blur-md text-cyan-200 mb-2">
            <span>👋</span>
            <span>Guten Tag, {{ user?.name }}</span>
          </span>
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
            Deine Arbeitsbereiche & Initiativen
          </h1>
          <p class="text-xs text-slate-300 mt-1">
            <span v-if="user?.company_name" class="text-cyan-300 font-semibold">{{ user.company_name }}</span>
            <span v-else class="text-slate-200 font-medium">Privater Arbeitsbereich</span>
            – Übersicht aller Projektordner, Phasen und Meilensteine.
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
    </div>

    <!-- Free-Plan Info Box if applicable -->
    <div
      v-if="!user?.is_pro && !user?.company_id && !user?.is_superadmin"
      class="mb-8 p-4 rounded-2xl bg-amber-50 border border-amber-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-amber-900"
    >
      <div class="flex items-center space-x-3">
        <span class="text-2xl">⚡</span>
        <div>
          <h4 class="text-xs font-bold text-amber-900">Taskster Free Plan aktiv</h4>
          <p class="text-[11px] text-amber-700 mt-0.5">
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

    <!-- Quick Stats Grid (Clean White MeisterTask Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
      <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-sm transition">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Projektordner</div>
        <div class="text-3xl font-black text-slate-900 mt-2">{{ folders.length }}</div>
        <div class="text-xs text-slate-500 mt-1">Übergeordnete Ordner</div>
      </div>

      <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-sm transition">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Aktive Projekte</div>
        <div class="text-3xl font-black text-cyan-600 mt-2">{{ totalProjects }}</div>
        <div class="text-xs text-slate-500 mt-1">In deinen Ordnern organisiert</div>
      </div>

      <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-sm transition">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</div>
        <div class="text-sm font-bold text-emerald-600 mt-3 flex items-center space-x-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>Echtzeit-Synchronisiert</span>
        </div>
        <div class="text-xs text-slate-500 mt-1">Sichere Zero-Trust Pipeline aktiv</div>
      </div>
    </div>

    <!-- Folders List Section -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-5">
        <div class="flex items-center space-x-2">
          <h2 class="text-lg font-black text-slate-900 tracking-tight">
            Deine Projektordner
          </h2>
          <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold">
            {{ folders.length }}
          </span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12 text-slate-400 text-xs">
        Lade Arbeitsbereiche...
      </div>

      <!-- Empty State with Nature Artwork -->
      <div
        v-else-if="folders.length === 0"
        class="text-center py-12 px-6 bg-white rounded-3xl border border-dashed border-slate-300 shadow-sm max-w-lg mx-auto"
      >
        <div class="w-20 h-20 mx-auto rounded-2xl overflow-hidden shadow-md mb-4">
          <img
            src="/wallpapers/nordic-hills.jpg"
            alt="Keine Ordner"
            class="w-full h-full object-cover"
          />
        </div>
        <h3 class="text-base font-bold text-slate-800">Noch kein Projektordner vorhanden</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-5 leading-relaxed">
          Erstelle deinen ersten Ordner, um Unterprojekte, Vorlagen und Meilensteine zu organisieren.
        </p>
        <button
          @click="openNewFolderModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg"
        >
          <span>+ Ersten Ordner erstellen</span>
        </button>
      </div>

      <!-- Folders Cards (MeisterTask-Style Light & Friendly) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="folder in folders"
          :key="folder.id"
          class="bg-white border border-slate-200/90 hover:border-cyan-400 rounded-3xl p-6 transition-all duration-200 flex flex-col justify-between group shadow-sm hover:shadow-md"
        >
          <div>
            <div class="flex items-start justify-between mb-3">
              <div class="w-12 h-12 rounded-2xl bg-cyan-50 border border-cyan-100 flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
                {{ folder.icon || '📁' }}
              </div>
              <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                {{ folder.project_count }} {{ folder.project_count === 1 ? 'Projekt' : 'Projekte' }}
              </span>
            </div>

            <h3 class="text-base font-black text-slate-900 group-hover:text-cyan-600 transition mb-1">
              {{ folder.name }}
            </h3>
            <p class="text-xs text-slate-500 flex items-center space-x-1">
              <span>Inhaber:</span>
              <span class="text-slate-700 font-semibold">{{ folder.owner_name }}</span>
              <span v-if="user?.id === folder.owner_id" class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800 font-bold ml-1">
                Du
              </span>
            </p>
            <p v-if="folder.company_name" class="text-xs text-teal-700 mt-1 font-medium">
              🏢 {{ folder.company_name }}
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-400 font-medium">
              {{ new Date(folder.created_at).toLocaleDateString('de-CH') }}
            </span>
            <div class="flex items-center space-x-2">
              <button
                v-if="user?.id === folder.owner_id || user?.is_superadmin"
                @click="openEditFolderModal(folder)"
                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center space-x-1"
                title="Projektordner anpassen (Name & Icon)"
              >
                <span>✏️</span>
                <span>Anpassen</span>
              </button>
              <NuxtLink
                :to="`/folders/${folder.id}`"
                class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#00A3C4] hover:bg-[#008ba8] text-white shadow-sm transition"
              >
                Öffnen →
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: New Folder -->
    <div v-if="showNewFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">Neuen Projektordner anlegen</h3>
          <button @click="showNewFolderModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>
        <p class="text-xs text-slate-500 mb-5">
          Projektordner bilden die oberste Organisationsebene für Bauträger, Standorte oder Großvorhaben.
        </p>

        <div v-if="folderModalError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ folderModalError }}
        </div>

        <form @submit.prevent="createFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Name des Projektordners</label>
            <input
              v-model="newFolderName"
              type="text"
              required
              placeholder="z.B. FTTH Glasfaserausbau Region Nord"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Icon auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-36 overflow-y-auto p-2.5 bg-slate-50 rounded-2xl border border-slate-200">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="newFolderIcon = item.icon"
                class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition border"
                :class="newFolderIcon === item.icon ? 'bg-cyan-50 border-cyan-500 ring-2 ring-cyan-500/40 scale-105' : 'border-slate-200 bg-white hover:bg-slate-100'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">Ausgewählt: <span class="text-slate-900 text-sm font-bold mr-1">{{ newFolderIcon }}</span></p>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
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
    <div v-if="showEditFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">Projektordner anpassen</h3>
          <button @click="showEditFolderModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>
        <p class="text-xs text-slate-500 mb-5">
          Passe den Namen und das Erkennungs-Icon dieses Projektordners an.
        </p>

        <div v-if="editFolderError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ editFolderError }}
        </div>

        <form @submit.prevent="updateFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Name des Projektordners</label>
            <input
              v-model="editFolderName"
              type="text"
              required
              placeholder="z.B. Peters Privates Renovationsprojekt"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Icon aus Liste auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-40 overflow-y-auto p-2.5 bg-slate-50 rounded-2xl border border-slate-200">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="editFolderIcon = item.icon"
                class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition border"
                :class="editFolderIcon === item.icon ? 'bg-cyan-50 border-cyan-500 ring-2 ring-cyan-500/40 scale-105' : 'border-slate-200 bg-white hover:bg-slate-100'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">Ausgewähltes Icon: <span class="text-slate-900 text-sm font-bold mr-1">{{ editFolderIcon }}</span></p>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
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
