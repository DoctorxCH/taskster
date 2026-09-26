<template>
  <div class="h-full flex flex-col min-h-0">
    <div class="mb-6 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Finance & Invoicing</h1>
        <p class="text-slate-500 mt-1">Configure hourly rates and view billable revenue reports.</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Date Range Filter Mockup -->
        <div class="px-4 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-600 flex items-center gap-2 cursor-pointer hover:bg-slate-50">
          <Icon name="lucide:calendar" class="w-4 h-4" />
          <span>Last 30 Days</span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 min-h-0">
      
      <!-- Revenue Report Column -->
      <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl shadow-sm flex flex-col min-h-0 overflow-hidden">
        <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0">
          <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <Icon name="lucide:bar-chart-3" class="w-4 h-4 text-emerald-500" />
            Revenue Report
          </h2>
          <span class="text-xs font-semibold px-2 py-1 bg-emerald-100 text-emerald-800 rounded">
            Total: {{ Number(response?.total_revenue || 0).toLocaleString('de-CH', { style: 'currency', currency: 'CHF' }) }}
          </span>
        </div>
        
        <div class="flex-1 overflow-y-auto">
          <div v-if="pending" class="flex justify-center p-12">
            <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
          </div>
          
          <div v-else-if="error" class="p-6 text-red-500 text-sm text-center">
            Error loading revenue report.
          </div>
          
          <div v-else-if="!response?.projects || response.projects.length === 0" class="p-12 text-center text-slate-500 text-sm">
            No billable time entries found for this period.
          </div>
          
          <div v-else class="divide-y divide-slate-100">
            <div 
              v-for="(proj, idx) in response.projects" 
              :key="idx"
              class="px-6 py-4 hover:bg-slate-50/50 transition flex items-center justify-between"
            >
              <div>
                <div class="font-bold text-slate-900">{{ proj.project_id || 'Global / Unassigned' }}</div>
                <div class="text-xs text-slate-500 mt-1">
                  {{ (proj.total_minutes / 60).toFixed(1) }} hours tracked
                </div>
              </div>
              <div class="text-right">
                <div class="font-black text-slate-800">
                  {{ Number(proj.total_revenue).toLocaleString('de-CH', { style: 'currency', currency: 'CHF' }) }}
                </div>
                <div class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider">
                  Billable
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Settings Column -->
      <div class="lg:col-span-1 space-y-6 overflow-y-auto pr-2">
        
        <!-- Company Default Rate -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
          <div class="bg-slate-50 border-b border-slate-200 px-6 py-4">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
              <Icon name="lucide:building" class="w-4 h-4 text-blue-500" />
              Company Base Rate
            </h2>
          </div>
          <div class="p-6">
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
              This hourly rate is used as a fallback if no specific project or user rate is defined for a time entry.
            </p>
            
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Hourly Rate</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">CHF</span>
                  <input v-model="companyRate" type="number" class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                </div>
              </div>
              
              <button @click="saveCompanyRate" :disabled="saving" class="taskster_button w-full px-6 text-xs h-[42px] rounded-lg">
                {{ saving ? 'Saving...' : 'Update Base Rate' }}
              </button>
            </div>
          </div>
        </div>
        
        <!-- Help Box -->
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
          <h3 class="text-sm font-bold text-blue-900 mb-2 flex items-center gap-2">
            <Icon name="lucide:info" class="w-4 h-4" />
            Rate Cascading Rules
          </h3>
          <ul class="text-xs text-blue-800 space-y-2 list-disc pl-4">
            <li>When time is logged, the engine looks for a specific <strong>User Rate</strong> first.</li>
            <li>If none exists, it looks for a <strong>Project Rate</strong>.</li>
            <li>Finally, it falls back to the <strong>Company Base Rate</strong> configured above.</li>
          </ul>
        </div>
        
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

const { authHeaders, user } = useAuth()

const { data: response, pending, error } = await useFetch<any>('/api/admin/finance/revenue-report', {
  headers: authHeaders()
})

const companyRate = ref(150.00) // Dummy default, normally loaded from DB
const saving = ref(false)

const saveCompanyRate = async () => {
  saving.value = true
  try {
    await $fetch('/api/admin/finance/invoice-rules', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        entity_type: 'company',
        entity_id: user.value?.company_id || 'system',
        hourly_rate: companyRate.value,
        currency: 'CHF'
      }
    })
    alert('Company base rate updated successfully!')
  } catch (e) {
    console.error('Failed to update rate', e)
    alert('Failed to update rate')
  } finally {
    saving.value = false
  }
}
</script>
