<template>
  <div>
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Roles & Permissions (RBAC)</h1>
        <p class="text-slate-500 mt-1">Manage zero-trust policies, access levels, and role definitions.</p>
      </div>
      <button class="taskster_button px-6 text-xs h-[42px] rounded-lg">
        <Icon name="lucide:plus" class="w-4 h-4 mr-2" />
        Create New Role
      </button>
    </div>

    <!-- Roles Grid/List -->
    <div v-if="pending" class="flex justify-center p-12">
      <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
    </div>
    
    <div v-else-if="error" class="p-6 bg-red-50 text-red-700 rounded-xl border border-red-200">
      Failed to load roles. Please ensure you have the required permissions.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="role in response?.roles" 
        :key="role.id"
        class="bg-white border border-slate-200 hover:border-blue-300 shadow-sm hover:shadow-md transition-all rounded-xl p-6 flex flex-col"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center font-bold"
                 :class="role.is_system ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-600'">
              {{ role.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <h3 class="font-bold text-slate-900">{{ role.name }}</h3>
              <p class="text-[10px] text-slate-400 font-mono">{{ role.key }}</p>
            </div>
          </div>
          <span v-if="role.is_system" class="px-2.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold uppercase rounded-full tracking-wider">
            System
          </span>
        </div>
        
        <p class="text-sm text-slate-600 mb-6 flex-1">
          {{ role.description || 'No description provided for this role.' }}
        </p>
        
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2 text-xs text-slate-500">
            <Icon name="lucide:shield" class="w-4 h-4" />
            <span>Level: {{ role.level }}</span>
          </div>
          <button 
            @click="viewRoleDetails(role.id)"
            class="text-blue-600 hover:text-blue-800 text-sm font-semibold transition"
          >
            Edit Policies
          </button>
        </div>
      </div>
    </div>
    
    <!-- Placeholder for Role Details Sidebar/Modal -->
    <div v-if="selectedRole" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Edit Role: {{ selectedRoleInfo?.name }}</h2>
            <p class="text-xs text-slate-500 font-mono">{{ selectedRoleInfo?.key }}</p>
          </div>
          <button @click="selectedRole = null" class="text-slate-400 hover:text-slate-600 p-2">
            <Icon name="lucide:x" class="w-5 h-5" />
          </button>
        </div>
        
        <!-- Modal Body (Matrix) -->
        <div class="p-6 flex-1 overflow-y-auto">
          <div v-if="loadingDetails" class="flex justify-center p-12">
            <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
          </div>
          
          <div v-else-if="selectedRoleInfo">
            <div class="mb-6 p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
              <h4 class="text-sm font-bold text-blue-900 mb-2">Permission Matrix</h4>
              <p class="text-xs text-blue-700">
                Configure fine-grained access control. 'Scope' defines data visibility (e.g., own, company, global). 'Conditions' allow ABAC rules.
              </p>
            </div>
            
            <div class="border border-slate-200 rounded-xl overflow-hidden">
              <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs uppercase">
                  <tr>
                    <th class="py-3 px-4 font-semibold">Entity</th>
                    <th class="py-3 px-4 font-semibold">Action</th>
                    <th class="py-3 px-4 font-semibold">Scope</th>
                    <th class="py-3 px-4 font-semibold text-right">Access</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="p in selectedRoleInfo.permissions" :key="p.id" class="hover:bg-slate-50/50">
                    <td class="py-3 px-4 font-medium text-slate-900 capitalize">{{ p.entity }}</td>
                    <td class="py-3 px-4">
                      <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded text-xs font-mono">
                        {{ p.action }}
                      </span>
                    </td>
                    <td class="py-3 px-4">
                      <select v-model="p.scope" class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white focus:outline-none focus:border-blue-500">
                        <option value="none">None</option>
                        <option value="own">Own Only</option>
                        <option value="company">Company Wide</option>
                        <option value="global" v-if="selectedRoleInfo.is_system">Global</option>
                      </select>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <!-- Simple toggle for demo -->
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" :checked="p.scope !== 'none'" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                      </label>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
          <button @click="selectedRole = null" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">
            Cancel
          </button>
          <button class="taskster_button px-6 text-xs h-[42px] rounded-lg">
            Save Policies
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

// Fetch basic roles list
const { data: response, pending, error } = await useFetch<{ roles: any[] }>('/api/admin/roles', {
  headers: authHeaders()
})

// Modal State
const selectedRole = ref<string | null>(null)
const loadingDetails = ref(false)
const selectedRoleInfo = ref<any>(null)

const viewRoleDetails = async (roleId: string) => {
  selectedRole.value = roleId
  loadingDetails.value = true
  
  try {
    const res = await $fetch<{ role: any }>(`/api/admin/roles/${roleId}`, {
      headers: authHeaders()
    })
    
    // Fill in missing scopes to 'none' if they don't exist yet, for the UI dropdown
    if (res.role && res.role.permissions) {
      res.role.permissions = res.role.permissions.map((p: any) => ({
        ...p,
        scope: p.scope || 'none'
      }))
    }
    
    selectedRoleInfo.value = res.role
  } catch (e) {
    console.error('Failed to fetch role details', e)
  } finally {
    loadingDetails.value = false
  }
}
</script>
