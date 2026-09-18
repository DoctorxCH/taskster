<template>
  <header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-40 text-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Brand Logo -->
      <div class="flex items-center space-x-6">
        <NuxtLink to="/dashboard" class="flex items-center space-x-3 group">
          <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-white shadow-md shadow-emerald-500/20 text-lg group-hover:scale-105 transition-transform">
            T
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-black tracking-wider text-white">TASKSTER</span>
            <span class="text-[10px] text-emerald-400 font-medium tracking-widest -mt-1 uppercase">Infrastructure & Projects</span>
          </div>
        </NuxtLink>

        <!-- Navigation Links -->
        <nav v-if="user" class="hidden md:flex items-center space-x-1 pl-4 border-l border-slate-800">
          <NuxtLink
            to="/dashboard"
            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
            :class="$route.path === '/dashboard' ? 'bg-slate-800 text-emerald-400' : 'text-slate-300 hover:text-white hover:bg-slate-800/60'"
          >
            Dashboard
          </NuxtLink>

          <NuxtLink
            v-if="user.is_superadmin || (user.company_id && user.company_role === 'admin')"
            to="/admin"
            class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors flex items-center space-x-1"
            :class="$route.path.startsWith('/admin') ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/50' : 'text-slate-300 hover:text-white hover:bg-slate-800/60'"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Admin-Bereich</span>
          </NuxtLink>
        </nav>
      </div>

      <!-- User & Status Area -->
      <div v-if="user" class="flex items-center space-x-4">
        <!-- Plan Badge -->
        <div class="hidden sm:flex items-center space-x-2">
          <span
            v-if="user.is_superadmin"
            class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/30"
          >
            SUPERADMIN
          </span>
          <span
            v-else-if="user.company_name"
            class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
          >
            {{ user.company_name }} ({{ user.company_role === 'admin' ? 'Company Admin' : 'Mitglied' }})
          </span>
          <span
            v-else-if="user.is_pro"
            class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30"
          >
            PRO USER
          </span>
          <span
            v-else
            class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-700 text-slate-300 border border-slate-600"
          >
            FREE PLAN
          </span>
        </div>

        <!-- User profile & Logout -->
        <div class="flex items-center space-x-3">
          <div class="w-8 h-8 rounded-full bg-slate-700 border border-slate-600 flex items-center justify-center text-xs font-bold text-slate-200">
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
          <span class="hidden lg:inline text-sm font-medium text-slate-200">{{ user.name }}</span>
          <button
            @click="logout"
            class="px-2.5 py-1 text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-950/30 rounded border border-slate-800 hover:border-rose-900 transition-colors"
            title="Abmelden"
          >
            Abmelden
          </button>
        </div>
      </div>

      <!-- If not logged in -->
      <div v-else class="flex items-center space-x-3">
        <NuxtLink
          to="/login"
          class="px-4 py-1.5 rounded-lg text-sm font-semibold text-slate-200 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition"
        >
          Anmelden
        </NuxtLink>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
const { user, logout } = useAuth()
</script>
