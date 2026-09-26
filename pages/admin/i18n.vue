<template>
  <div class="h-full flex flex-col min-h-0">
    <!-- Header -->
    <div class="mb-6 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Translations (CMS)</h1>
        <p class="text-slate-500 mt-1">Manage system translations and custom company terminology.</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="relative">
          <Icon name="lucide:search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search keys or text..."
            class="pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm w-64 focus:outline-none focus:border-blue-500"
          >
        </div>
        <button @click="openCreateModal" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
          <Icon name="lucide:plus" class="w-4 h-4 mr-2" />
          Add Override
        </button>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col min-h-0 overflow-hidden">
      
      <!-- Table Header -->
      <div class="bg-slate-50 border-b border-slate-200 grid grid-cols-12 gap-4 px-6 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider shrink-0">
        <div class="col-span-2">Locale</div>
        <div class="col-span-4">Key</div>
        <div class="col-span-5">Value</div>
        <div class="col-span-1 text-right">Actions</div>
      </div>
      
      <!-- Table Body -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="pending" class="flex justify-center p-12">
          <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
        </div>
        
        <div v-else-if="error" class="p-6 text-red-500 text-sm text-center">
          Error loading translations.
        </div>
        
        <div v-else-if="filteredTranslations.length === 0" class="p-12 text-center text-slate-500 text-sm">
          No translations found matching your criteria.
        </div>
        
        <div v-else class="divide-y divide-slate-100">
          <div 
            v-for="t in filteredTranslations" 
            :key="t.id"
            class="grid grid-cols-12 gap-4 px-6 py-4 text-sm items-start hover:bg-slate-50/50 transition group"
          >
            <div class="col-span-2 flex items-center">
              <span class="px-2 py-1 bg-blue-50 text-blue-700 font-mono text-xs rounded border border-blue-100">
                {{ t.locale }}
              </span>
            </div>
            <div class="col-span-4 font-mono text-xs text-slate-600 break-words pr-4">
              {{ t.key }}
            </div>
            <div class="col-span-5 text-slate-800">
              <!-- Edit Mode -->
              <div v-if="editingId === t.id" class="flex flex-col gap-2">
                <textarea 
                  v-model="editValue" 
                  class="w-full p-2 border border-blue-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-100 resize-y min-h-[60px] text-sm"
                ></textarea>
                <div class="flex gap-2">
                  <button @click="saveEdit(t)" class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-semibold" :disabled="saving">Save</button>
                  <button @click="cancelEdit" class="px-3 py-1 bg-slate-100 text-slate-600 rounded text-xs font-semibold" :disabled="saving">Cancel</button>
                </div>
              </div>
              <!-- View Mode -->
              <div v-else class="whitespace-pre-wrap">{{ t.value }}</div>
            </div>
            <div class="col-span-1 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
              <button 
                v-if="editingId !== t.id"
                @click="startEdit(t)" 
                class="text-slate-400 hover:text-blue-600 p-1 rounded hover:bg-blue-50"
                title="Edit"
              >
                <Icon name="lucide:pencil" class="w-4 h-4" />
              </button>
              <button 
                v-if="editingId !== t.id"
                @click="deleteTranslation(t.id)" 
                class="text-slate-400 hover:text-red-600 p-1 rounded hover:bg-red-50"
                title="Delete"
              >
                <Icon name="lucide:trash-2" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Modal Placeholder -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-lg font-bold text-slate-800">Add Translation Override</h2>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 p-2">
            <Icon name="lucide:x" class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Locale (e.g., de, en, de-CH)</label>
            <input v-model="newOverride.locale" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Translation Key</label>
            <input v-model="newOverride.key" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Text Value</label>
            <textarea v-model="newOverride.value" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm min-h-[100px] focus:outline-none focus:border-blue-500"></textarea>
          </div>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
          <button @click="showCreateModal = false" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">
            Cancel
          </button>
          <button @click="createOverride" :disabled="saving" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
            {{ saving ? 'Saving...' : 'Create Override' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

const { authHeaders } = useAuth()
const { bootstrapSystem } = useAdmin()

const { data: response, pending, error, refresh } = await useFetch<{ translations: any[] }>('/api/admin/i18n', {
  headers: authHeaders()
})

const searchQuery = ref('')
const filteredTranslations = computed(() => {
  if (!response.value?.translations) return []
  
  const q = searchQuery.value.toLowerCase()
  if (!q) return response.value.translations
  
  return response.value.translations.filter(t => 
    t.key.toLowerCase().includes(q) || 
    t.value.toLowerCase().includes(q) ||
    t.locale.toLowerCase().includes(q)
  )
})

// Edit logic
const editingId = ref<string | null>(null)
const editValue = ref('')
const saving = ref(false)

const startEdit = (t: any) => {
  editingId.value = t.id
  editValue.value = t.value
}

const cancelEdit = () => {
  editingId.value = null
  editValue.value = ''
}

const saveEdit = async (t: any) => {
  saving.value = true
  try {
    await $fetch(`/api/admin/i18n`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        locale: t.locale,
        key: t.key,
        value: editValue.value
      }
    })
    // Re-fetch translations and update UI
    await refresh()
    // Re-bootstrap to update the active translations in the UI
    await bootstrapSystem(true)
    editingId.value = null
  } catch (e) {
    console.error('Failed to update translation', e)
    alert('Failed to update translation')
  } finally {
    saving.value = false
  }
}

const deleteTranslation = async (id: string) => {
  if (!confirm('Are you sure you want to delete this custom translation? The system default will be used instead.')) return
  
  try {
    await $fetch(`/api/admin/i18n/${id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await refresh()
    await bootstrapSystem(true)
  } catch (e) {
    console.error('Failed to delete translation', e)
    alert('Failed to delete translation')
  }
}

// Create Logic
const showCreateModal = ref(false)
const newOverride = ref({ locale: 'de', key: '', value: '' })

const openCreateModal = () => {
  newOverride.value = { locale: 'de', key: '', value: '' }
  showCreateModal.value = true
}

const createOverride = async () => {
  if (!newOverride.value.key || !newOverride.value.value) return
  
  saving.value = true
  try {
    await $fetch(`/api/admin/i18n`, {
      method: 'POST',
      headers: authHeaders(),
      body: newOverride.value
    })
    await refresh()
    await bootstrapSystem(true)
    showCreateModal.value = false
  } catch (e) {
    console.error('Failed to create override', e)
    alert('Failed to create override')
  } finally {
    saving.value = false
  }
}
</script>
