<template>
  <div class="h-full flex flex-col min-h-0">
    <div class="mb-6 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Workflow Engine</h1>
        <p class="text-slate-500 mt-1">Configure task statuses, transitions, and logic rules.</p>
      </div>
      <button class="taskster_button px-6 text-xs h-[42px] rounded-lg">
        <Icon name="lucide:plus" class="w-4 h-4 mr-2" />
        Add Status
      </button>
    </div>

    <div class="flex-1 bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col min-h-0 overflow-hidden">
      <div class="bg-slate-50 border-b border-slate-200 grid grid-cols-12 gap-4 px-6 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider shrink-0">
        <div class="col-span-1">Order</div>
        <div class="col-span-4">Status Label</div>
        <div class="col-span-3">System Key</div>
        <div class="col-span-2">Behavior</div>
        <div class="col-span-2 text-right">Actions</div>
      </div>
      
      <div class="flex-1 overflow-y-auto">
        <div v-if="pending" class="flex justify-center p-12">
          <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
        </div>
        
        <div v-else-if="error" class="p-6 text-red-500 text-sm text-center">
          Error loading workflow configuration.
        </div>
        
        <div v-else class="divide-y divide-slate-100">
          <div 
            v-for="s in response?.statuses" 
            :key="s.id"
            class="grid grid-cols-12 gap-4 px-6 py-4 text-sm items-center hover:bg-slate-50/50 transition group"
          >
            <div class="col-span-1 text-slate-400 font-mono">
              {{ s.sort_order }}
            </div>
            
            <div class="col-span-4 flex items-center gap-3">
              <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: s.color || '#cbd5e1' }"></div>
              <span class="font-bold text-slate-900">{{ s.label_key }}</span> <!-- Should be translated in real UI via $t() -->
            </div>
            
            <div class="col-span-3 font-mono text-xs text-slate-500">
              {{ s.id }}
            </div>
            
            <div class="col-span-2">
              <span v-if="s.is_completed" class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase rounded border border-emerald-200">
                Marks Completed
              </span>
              <span v-else class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold uppercase rounded border border-blue-200">
                Active State
              </span>
            </div>
            
            <div class="col-span-2 flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
              <button 
                @click="editStatus(s)" 
                class="text-slate-400 hover:text-blue-600 p-1 rounded hover:bg-blue-50"
                title="Settings"
                :disabled="s.is_system"
                :class="{ 'opacity-30 cursor-not-allowed': s.is_system }"
              >
                <Icon name="lucide:settings" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Edit Modal -->
    <div v-if="editingStatus" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-lg font-bold text-slate-800">Edit Status</h2>
          <button @click="editingStatus = null" class="text-slate-400 hover:text-slate-600 p-2">
            <Icon name="lucide:x" class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Translation Key</label>
            <input v-model="editForm.label_key" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Color (Hex)</label>
            <div class="flex gap-2">
              <input v-model="editForm.color" type="color" class="w-10 h-10 rounded border-0 p-0 cursor-pointer">
              <input v-model="editForm.color" type="text" class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono focus:outline-none focus:border-blue-500">
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Sort Order</label>
            <input v-model="editForm.sort_order" type="number" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
          </div>
          <label class="flex items-center gap-3 mt-4 pt-4 border-t border-slate-100">
            <input type="checkbox" v-model="editForm.is_completed" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
            <span class="text-sm font-medium text-slate-700">This status marks the entity as completed</span>
          </label>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
          <button @click="editingStatus = null" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">
            Cancel
          </button>
          <button @click="saveStatus" :disabled="saving" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
            {{ saving ? 'Saving...' : 'Save Configuration' }}
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

const { data: response, pending, error, refresh } = await useFetch<{ statuses: any[], priorities: any[] }>('/api/admin/workflow', {
  headers: authHeaders()
})

const editingStatus = ref<any>(null)
const editForm = ref({ id: '', label_key: '', color: '', sort_order: 0, is_completed: false })
const saving = ref(false)

const editStatus = (s: any) => {
  if (s.is_system) return
  editingStatus.value = s
  editForm.value = { 
    id: s.id, 
    label_key: s.label_key, 
    color: s.color || '#000000', 
    sort_order: s.sort_order, 
    is_completed: !!s.is_completed 
  }
}

const saveStatus = async () => {
  saving.value = true
  try {
    await $fetch(`/api/admin/workflow/statuses/${editForm.value.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: editForm.value
    })
    await refresh()
    editingStatus.value = null
  } catch (e) {
    console.error('Failed to update status', e)
    alert('Failed to save status')
  } finally {
    saving.value = false
  }
}
</script>
