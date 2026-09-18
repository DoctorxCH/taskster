<template>
  <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40 text-slate-800 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Brand Logo -->
      <div class="flex items-center space-x-6">
        <NuxtLink to="/dashboard" class="flex items-center space-x-2 group">
          <img
            src="/logo.png"
            alt="Taskster"
            class="h-8 w-auto object-contain group-hover:scale-105 transition-transform"
          />
          <div class="hidden sm:flex flex-col">
            <span class="text-[10px] text-cyan-600 font-bold tracking-widest uppercase">Workspace</span>
          </div>
        </NuxtLink>

        <!-- Navigation Links -->
        <nav v-if="user" class="hidden md:flex items-center space-x-1 pl-4 border-l border-slate-200">
          <NuxtLink
            to="/dashboard"
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="$route.path === '/dashboard' ? 'bg-cyan-50 text-cyan-700 shadow-sm font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70'"
          >
            Dashboard
          </NuxtLink>

          <NuxtLink
            v-if="user.is_superadmin"
            to="/admin"
            class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center space-x-1.5"
            :class="$route.path.startsWith('/admin') ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'text-slate-600 hover:text-purple-700 hover:bg-purple-50/50'"
          >
            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            <span>Admin-Bereich</span>
          </NuxtLink>
        </nav>
      </div>

      <!-- User & Status Area -->
      <div v-if="user" class="flex items-center space-x-3">
        <!-- Plan Badge -->
        <div class="hidden sm:flex items-center space-x-2">
          <span
            v-if="user.is_superadmin"
            class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 border border-purple-200"
          >
            SUPERADMIN
          </span>
          <span
            v-else-if="user.company_name"
            class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
          >
            {{ user.company_name }}
          </span>
          <span
            v-else-if="user.is_pro"
            class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200"
          >
            PRO PLAN
          </span>
          <span
            v-else
            class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
          >
            FREE PLAN
          </span>
        </div>

        <!-- User profile, Settings & Logout -->
        <div class="flex items-center space-x-2">
          <NuxtLink
            to="/settings"
            class="flex items-center space-x-2 p-1.5 rounded-lg text-slate-700 hover:bg-slate-100 transition group"
            title="Benutzer-Einstellungen"
          >
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-xs font-bold shadow-sm">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <span class="hidden lg:inline text-xs font-semibold text-slate-800">{{ user.name }}</span>
          </NuxtLink>

          <NuxtLink
            to="/settings"
            class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition"
            title="Einstellungen"
          >
            ⚙️
          </NuxtLink>

          <button
            @click="logout"
            class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg border border-slate-200 hover:border-rose-200 transition-colors"
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
          class="taskster_button px-6 text-xs h-[42px] rounded-lg"
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
