<template>
  <div class="h-full bg-slate-50 flex flex-col md:flex-row">
    <!-- Admin Sidebar -->
    <aside class="w-full md:w-64 bg-white border-r border-slate-200 flex-shrink-0 flex flex-col">
      <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-800">Superadmin</h1>
        <button @click="navigateTo('/')" class="text-slate-400 hover:text-slate-600" title="Back to App">
          <Icon name="lucide:arrow-left" class="w-5 h-5" />
        </button>
      </div>
      
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <NuxtLink 
          v-for="item in navItems" 
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
          active-class="bg-blue-50 text-blue-700"
          :class="[
            $route.path === item.path 
              ? 'bg-blue-50 text-blue-700' 
              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <Icon :name="item.icon" class="w-5 h-5" />
          {{ item.name }}
        </NuxtLink>
      </nav>
      
      <div class="p-4 border-t border-slate-100">
        <div class="flex items-center gap-3 px-4 py-2 text-sm text-slate-500">
          <Icon name="lucide:shield-check" class="w-5 h-5 text-emerald-500" />
          <span>{{ user?.name || 'Admin' }}</span>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-h-0 overflow-hidden">
      <!-- Loading overlay for bootstrap -->
      <div v-if="!isBootstrapped" class="flex-1 flex items-center justify-center bg-slate-50">
        <div class="flex flex-col items-center gap-4">
          <Icon name="lucide:loader-2" class="w-8 h-8 animate-spin text-blue-500" />
          <p class="text-sm text-slate-500">Loading System Configuration...</p>
        </div>
      </div>
      
      <!-- Loaded Content -->
      <div v-else class="flex-1 overflow-y-auto p-6 md:p-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
const { user } = useAuth()
const { isBootstrapped, bootstrapSystem } = useAdmin()

const navItems = [
  { name: 'Dashboard', path: '/admin', icon: 'lucide:layout-dashboard' },
  { name: 'Companies', path: '/admin/companies', icon: 'lucide:building-2' },
  { name: 'Roles & Permissions', path: '/admin/roles', icon: 'lucide:users-2' },
  { name: 'Workflow Engine', path: '/admin/workflow', icon: 'lucide:git-merge' },
  { name: 'Custom Fields', path: '/admin/custom-fields', icon: 'lucide:form-input' },
  { name: 'Translations (CMS)', path: '/admin/i18n', icon: 'lucide:languages' },
  { name: 'Finance & Invoicing', path: '/admin/finance', icon: 'lucide:credit-card' },
  { name: 'Legacy Dashboard', path: '/admin/legacy', icon: 'lucide:archive' }
]

onMounted(async () => {
  await bootstrapSystem()
})
</script>
