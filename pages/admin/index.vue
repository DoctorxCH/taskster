<template>
  <div>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-slate-800 tracking-tight">System Dashboard</h1>
      <p class="text-slate-500 mt-1">Overview of your Taskster instance</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Dummy Stats for now -->
      <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
            <Icon name="lucide:building-2" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Active Companies</p>
            <p class="text-2xl font-bold text-slate-800">12</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <Icon name="lucide:users" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Total Users</p>
            <p class="text-2xl font-bold text-slate-800">845</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
            <Icon name="lucide:check-square" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Tasks Completed</p>
            <p class="text-2xl font-bold text-slate-800">14.2k</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
            <Icon name="lucide:languages" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-slate-500">Custom Locales</p>
            <p class="text-2xl font-bold text-slate-800">{{ Object.keys(locales || {}).length }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Features / Config state -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-xl overflow-hidden mb-6">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <h2 class="text-lg font-semibold text-slate-800">System State</h2>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <div class="flex items-center justify-between py-2 border-b border-slate-50">
            <span class="text-sm text-slate-600">Design Tokens (v2) Active</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Enabled</span>
          </div>
          <div class="flex items-center justify-between py-2 border-b border-slate-50">
            <span class="text-sm text-slate-600">RBAC (Roles & Permissions) Active</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Enabled</span>
          </div>
          <div class="flex items-center justify-between py-2">
            <span class="text-sm text-slate-600">Custom Workflow Rules</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Enabled</span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="p-6 bg-amber-50 rounded-xl border border-amber-200">
      <div class="flex items-start gap-4">
        <Icon name="lucide:alert-triangle" class="w-6 h-6 text-amber-600 flex-shrink-0" />
        <div>
          <h3 class="font-bold text-amber-800">Legacy Dashboard Available</h3>
          <p class="text-sm text-amber-700 mt-1">
            The old monolith admin dashboard has been preserved. You can access it via the Legacy Dashboard link in the sidebar or directly at <NuxtLink to="/admin/legacy" class="underline font-medium">/admin/legacy</NuxtLink>.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: 'admin'
})

const { locales } = useAdmin()
</script>
