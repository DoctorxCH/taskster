<template>
  <header class="liquid_glass sticky top-0 z-40 text-slate-900 shadow-md transition-colors border-b border-white/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Brand Logo & Quick Action -->
      <div class="flex items-center space-x-6">
        <NuxtLink to="/dashboard" class="flex items-center space-x-2.5 group">
          <img
            src="/logo.png"
            alt="Taskster"
            class="h-8 w-auto object-contain group-hover:scale-105 transition-transform"
          />
          <div class="hidden sm:flex flex-col">
            <span class="text-[10px] text-[#00A3C4] font-black tracking-widest uppercase">Workspace</span>
          </div>
        </NuxtLink>

        <!-- Navigation Links -->
        <nav v-if="user" class="hidden md:flex items-center space-x-1 pl-4 border-l border-slate-200/80">
          <NuxtLink
            to="/dashboard"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all"
            :class="$route.path === '/dashboard' ? 'bg-white text-[#00A3C4] shadow-sm font-extrabold' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'"
          >
            Dashboard
          </NuxtLink>

          <NuxtLink
            v-if="user.is_superadmin || user.company_role === 'admin' || (user.admin_permissions && user.admin_permissions.length > 0)"
            to="/admin"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5"
            :class="$route.path.startsWith('/admin') ? 'bg-white text-purple-700 shadow-sm border border-purple-200' : 'text-slate-600 hover:text-purple-700 hover:bg-white/60'"
          >
            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            <span>Admin-Bereich</span>
          </NuxtLink>
        </nav>
      </div>

      <!-- Center: Live Running Stopwatch Widget -->
      <div
        v-if="user && stopwatchState.isRunning"
        class="flex items-center space-x-2.5 px-3.5 py-1.5 rounded-2xl bg-slate-900/90 border border-cyan-500/50 text-white shadow-lg backdrop-blur-md animate-in fade-in slide-in-from-top-2 duration-200 select-none shrink-0"
      >
        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
        <span class="font-mono font-black text-xs sm:text-sm tracking-wider text-cyan-300">
          {{ formatSeconds(stopwatchState.elapsedSeconds) }}
        </span>

        <span class="text-slate-500 hidden sm:inline">|</span>

        <!-- Task / Project label & navigation -->
        <NuxtLink
          :to="'/projects/' + stopwatchState.projectId"
          class="text-xs font-bold truncate max-w-[130px] sm:max-w-[220px] hover:text-cyan-300 transition flex items-center space-x-1"
          :title="stopwatchState.taskTitle ? ('Aufgabe: ' + stopwatchState.taskTitle + ' in ' + stopwatchState.projectTitle) : ('Projekt: ' + stopwatchState.projectTitle)"
        >
          <span v-if="stopwatchState.taskTitle" class="truncate">
            <span class="text-cyan-400 font-normal">Aufgabe:</span> {{ stopwatchState.taskTitle }}
          </span>
          <span v-else class="truncate">
            <span class="text-cyan-400 font-normal">Projekt:</span> {{ stopwatchState.projectTitle }}
          </span>
        </NuxtLink>

        <!-- Stop Button -->
        <button
          type="button"
          @click="openStopModal"
          class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-black text-[11px] flex items-center space-x-1 shadow-xs transition transform hover:scale-105 cursor-pointer"
          title="Stoppuhr anhalten & Zeit buchen"
        >
          <span>⏹️</span>
          <span class="hidden md:inline">Stoppen</span>
        </button>
      </div>

      <!-- User & Status Area -->
      <div v-if="user" class="flex items-center space-x-3">
        <!-- MeisterTask-style "Anpassen" (Customize Wallpaper) Button -->
        <button
          @click="$emit('toggle-wallpaper')"
          type="button"
          class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white/90 hover:bg-white text-slate-700 hover:text-cyan-700 border border-slate-200/80 shadow-xs transition cursor-pointer"
          title="Hintergrundbild wechseln"
        >
          <span>🎨</span>
          <span>Anpassen</span>
        </button>

        <!-- Plan Badge -->
        <div class="hidden sm:flex items-center space-x-2">
          <span
            v-if="user.is_superadmin"
            class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 border border-purple-200"
          >
            SUPERADMIN
          </span>
          <span
            v-else-if="user.company_role === 'admin' || (user.admin_permissions && user.admin_permissions.length > 0)"
            class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 border border-purple-200"
          >
            ADMIN
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
            class="flex items-center space-x-2 p-1.5 rounded-xl text-slate-700 hover:bg-white/80 transition group"
            title="Benutzer-Einstellungen"
          >
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-xs font-bold shadow-sm">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <span class="hidden lg:inline text-xs font-bold text-slate-800">{{ user.name }}</span>
          </NuxtLink>

          <NuxtLink
            to="/settings"
            class="p-2 text-slate-500 hover:text-slate-800 hover:bg-white/80 rounded-xl transition"
            title="Einstellungen"
          >
            ⚙️
          </NuxtLink>

          <button
            @click="logout"
            class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-xl border border-slate-200/80 hover:border-rose-200 transition-colors"
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

    <!-- Stopwatch Completion Modal -->
    <StopwatchModal />
  </header>
</template>

<script setup lang="ts">
defineEmits<{
  (e: 'toggle-wallpaper'): void
}>()

const { user, logout } = useAuth()
const { state: stopwatchState, initStopwatch, openStopModal, formatSeconds } = useStopwatch()

onMounted(() => {
  initStopwatch()
})
</script>
