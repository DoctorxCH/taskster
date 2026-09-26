<template>
  <div class="h-full flex flex-col min-h-0">
    <!-- Header -->
    <div class="mb-6 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Companies & Tenants</h1>
        <p class="text-slate-500 mt-1">Manage B2B subscriptions, limits, and security policies.</p>
      </div>
      <button class="taskster_button px-6 text-xs h-[42px] rounded-lg">
        <Icon name="lucide:plus" class="w-4 h-4 mr-2" />
        New Company
      </button>
    </div>

    <!-- Data Grid -->
    <div class="flex-1 bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col min-h-0 overflow-hidden">
      
      <div class="bg-slate-50 border-b border-slate-200 grid grid-cols-12 gap-4 px-6 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider shrink-0">
        <div class="col-span-4">Company Name</div>
        <div class="col-span-2">Plan</div>
        <div class="col-span-2">Users Limit</div>
        <div class="col-span-2">Storage</div>
        <div class="col-span-2 text-right">Status</div>
      </div>
      
      <div class="flex-1 overflow-y-auto">
        <div v-if="pending" class="flex justify-center p-12">
          <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
        </div>
        
        <div v-else-if="error" class="p-6 text-red-500 text-sm text-center">
          Error loading companies.
        </div>
        
        <div v-else class="divide-y divide-slate-100">
          <div 
            v-for="c in response?.companies" 
            :key="c.id"
            class="grid grid-cols-12 gap-4 px-6 py-4 text-sm items-center hover:bg-slate-50/50 cursor-pointer transition"
            @click="openSettings(c.id)"
          >
            <div class="col-span-4">
              <div class="font-bold text-slate-900">{{ c.name }}</div>
              <div class="text-[10px] text-slate-400 font-mono">ID: {{ c.id }}</div>
            </div>
            
            <div class="col-span-2">
              <span class="px-2 py-1 rounded text-xs font-bold uppercase tracking-wider"
                :class="{
                  'bg-purple-100 text-purple-700 border border-purple-200': c.subscription_plan === 'enterprise',
                  'bg-blue-100 text-blue-700 border border-blue-200': c.subscription_plan === 'pro',
                  'bg-slate-100 text-slate-600 border border-slate-200': c.subscription_plan === 'basic' || !c.subscription_plan
                }"
              >
                {{ c.subscription_plan || 'basic' }}
              </span>
            </div>
            
            <div class="col-span-2 text-slate-600 font-medium">
              {{ c.max_users || 'Unlimited' }}
            </div>
            
            <div class="col-span-2 text-slate-600 font-medium">
              {{ c.max_storage_gb ? `${c.max_storage_gb} GB` : 'Unlimited' }}
            </div>
            
            <div class="col-span-2 flex justify-end">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              >
                {{ c.status === 'active' ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Edit Modal -->
    <div v-if="selectedCompany" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-bold text-slate-800">Edit Company: {{ selectedCompanyInfo?.name }}</h2>
            <p class="text-xs text-slate-500">Configure quotas and security policies.</p>
          </div>
          <button @click="selectedCompany = null" class="text-slate-400 hover:text-slate-600 p-2">
            <Icon name="lucide:x" class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-6 flex-1 overflow-y-auto bg-slate-50/50">
          <div v-if="loadingDetails" class="flex justify-center p-12">
            <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
          </div>
          
          <div v-else-if="selectedCompanyInfo" class="space-y-6">
            
            <!-- Quotas -->
            <div class="bg-white p-5 rounded-xl border border-slate-200">
              <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                <Icon name="lucide:pie-chart" class="w-4 h-4 text-blue-500" />
                Subscription & Quotas
              </h3>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Plan</label>
                  <select v-model="selectedCompanyInfo.subscription_plan" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:border-blue-500 outline-none">
                    <option value="basic">Basic</option>
                    <option value="pro">Pro</option>
                    <option value="enterprise">Enterprise</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Max Users</label>
                  <input v-model="selectedCompanyInfo.max_users" type="number" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-blue-500" placeholder="Unlimited">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Storage (GB)</label>
                  <input v-model="selectedCompanyInfo.max_storage_gb" type="number" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-blue-500" placeholder="Unlimited">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                  <select v-model="selectedCompanyInfo.status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:border-blue-500 outline-none">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                  </select>
                </div>
              </div>
            </div>
            
            <!-- Security Policies -->
            <div class="bg-white p-5 rounded-xl border border-slate-200">
              <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                <Icon name="lucide:shield-alert" class="w-4 h-4 text-emerald-500" />
                Security & SSO
              </h3>
              
              <div class="space-y-4">
                <label class="flex items-center gap-3">
                  <input type="checkbox" v-model="selectedCompanyInfo.auth_policy.require_mfa" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                  <span class="text-sm font-medium text-slate-700">Enforce Multi-Factor Authentication (MFA)</span>
                </label>
                <label class="flex items-center gap-3">
                  <input type="checkbox" v-model="selectedCompanyInfo.sso_config.enabled" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                  <span class="text-sm font-medium text-slate-700">Enable Single Sign-On (SAML/OIDC)</span>
                </label>
              </div>
            </div>
            
          </div>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-100 bg-white flex justify-end gap-3">
          <button @click="selectedCompany = null" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">
            Cancel
          </button>
          <button @click="saveCompany" :disabled="saving" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
            {{ saving ? 'Saving...' : 'Save Changes' }}
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

const { data: response, pending, error, refresh } = await useFetch<{ companies: any[] }>('/api/admin/companies', {
  headers: authHeaders()
})

const selectedCompany = ref<string | null>(null)
const selectedCompanyInfo = ref<any>(null)
const loadingDetails = ref(false)
const saving = ref(false)

const openSettings = async (id: string) => {
  selectedCompany.value = id
  loadingDetails.value = true
  
  try {
    const res = await $fetch<{ company: any }>(`/api/admin/companies/${id}`, {
      headers: authHeaders()
    })
    
    // Ensure nested objects exist for v-model binding
    if (res.company) {
      res.company.auth_policy = res.company.auth_policy || {}
      res.company.sso_config = res.company.sso_config || {}
    }
    
    selectedCompanyInfo.value = res.company
  } catch (e) {
    console.error('Failed to fetch company', e)
  } finally {
    loadingDetails.value = false
  }
}

const saveCompany = async () => {
  saving.value = true
  try {
    await $fetch(`/api/admin/companies/${selectedCompany.value}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: selectedCompanyInfo.value
    })
    await refresh()
    selectedCompany.value = null
  } catch (e) {
    console.error('Failed to save company', e)
    alert('Failed to save company configuration')
  } finally {
    saving.value = false
  }
}
</script>
