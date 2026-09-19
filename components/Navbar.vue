<template>
  <header class="h-14 sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm text-slate-900 select-none">
    <div class="h-full px-4 sm:px-6 flex items-center justify-between gap-4">
      <!-- Left: Mobile Menu Toggle & Brand Logo -->
      <div class="flex items-center gap-3">
        <!-- Mobile & Tablet Hamburger Toggle -->
        <button
          v-if="user"
          type="button"
          @click="$emit('toggle-mobile-menu')"
          class="lg:hidden h-9 w-9 flex items-center justify-center rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors"
          title="Menü öffnen"
        >
          <Menu class="w-5 h-5" />
        </button>

        <!-- Brand Logo -->
        <NuxtLink to="/dashboard" class="flex items-center gap-2.5">
          <img
            src="/logo.png"
            alt="Taskster"
            class="h-7 w-auto object-contain"
          />
          <span class="hidden sm:inline text-xs font-semibold text-slate-500 uppercase tracking-wider border-l border-slate-200 pl-2.5">
            Workspace
          </span>
        </NuxtLink>
      </div>

      <!-- Center: Running Live Stopwatch Widget -->
      <div
        v-if="user && stopwatchState.isRunning"
        class="flex items-center gap-2 px-3 h-8 rounded-md bg-slate-900 text-white shadow-sm shrink-0"
      >
        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
        <span class="font-mono font-bold text-xs tracking-wide text-cyan-300 tabular-nums">
          {{ formatSeconds(stopwatchState.elapsedSeconds) }}
        </span>

        <span class="text-slate-600 hidden sm:inline">|</span>

        <!-- Task / Project Link -->
        <NuxtLink
          :to="'/projects/' + stopwatchState.projectId"
          class="text-xs font-medium truncate max-w-[120px] sm:max-w-[200px] hover:text-cyan-300 transition-colors"
          :title="stopwatchState.taskTitle ? ('Aufgabe: ' + stopwatchState.taskTitle + ' in ' + stopwatchState.projectTitle) : ('Projekt: ' + stopwatchState.projectTitle)"
        >
          <span v-if="stopwatchState.taskTitle" class="truncate">
            <span class="text-slate-400">Aufgabe:</span> {{ stopwatchState.taskTitle }}
          </span>
          <span v-else class="truncate">
            <span class="text-slate-400">Projekt:</span> {{ stopwatchState.projectTitle }}
          </span>
        </NuxtLink>

        <!-- Stop Action Button -->
        <button
          type="button"
          @click="openStopModal"
          class="h-6 px-2 rounded text-[11px] font-semibold bg-rose-600 hover:bg-rose-500 text-white flex items-center gap-1 transition-colors ml-1"
          title="Stoppuhr beenden"
        >
          <Square class="w-3 h-3 fill-current" />
          <span class="hidden md:inline">Stoppen</span>
        </button>
      </div>

      <!-- Right: Role Badges & User Actions -->
      <div v-if="user" class="flex items-center gap-2 sm:gap-3">
        <!-- Role / Plan Badges (Design v2 standard) -->
        <div class="hidden sm:flex items-center gap-1.5">
          <span
            v-if="user.is_superadmin"
            class="inline-flex items-center h-6 px-2 rounded-sm text-xs font-medium bg-slate-900 text-white"
          >
            Superadmin
          </span>
          <span
            v-else-if="user.admin_permissions && user.admin_permissions.length > 0"
            class="inline-flex items-center h-6 px-2 rounded-sm text-xs font-medium bg-slate-800 text-white"
          >
            Plattform-Admin
          </span>
          <span
            v-else-if="user.company_role === 'admin'"
            class="inline-flex items-center h-6 px-2 rounded-sm text-xs font-medium bg-cyan-50 text-cyan-800 border border-cyan-200"
          >
            Company Admin
          </span>
          <span
            v-else-if="user.company_name"
            class="inline-flex items-center h-6 px-2 rounded-sm text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200"
          >
            {{ user.company_name }}
          </span>
          <span
            v-else-if="user.is_pro"
            class="inline-flex items-center h-6 px-2 rounded-sm text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200"
          >
            Pro
          </span>
          <span
            v-else
            class="inline-flex items-center h-6 px-2 rounded-sm text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200"
          >
            Free
          </span>
        </div>

        <!-- User Profile Link -->
        <NuxtLink
          to="/settings"
          class="flex items-center gap-2 p-1 rounded-md text-slate-700 hover:bg-slate-100 transition-colors"
          title="Mein Profil & Einstellungen"
        >
          <div class="w-7 h-7 rounded-md bg-[#0891B2] text-white flex items-center justify-center text-xs font-bold shrink-0">
            {{ user.name?.charAt(0).toUpperCase() }}
          </div>
          <span class="hidden md:inline text-sm font-medium text-slate-800">{{ user.name }}</span>
        </NuxtLink>

        <!-- Logout Action Button -->
        <button
          @click="logout"
          type="button"
          class="h-8 px-2.5 rounded-md text-xs font-semibold text-slate-600 hover:text-rose-700 hover:bg-rose-50 border border-slate-200 transition-colors flex items-center gap-1.5"
          title="Abmelden"
        >
          <LogOut class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">Abmelden</span>
        </button>
      </div>

      <!-- Logged out action -->
      <div v-else class="flex items-center gap-3">
        <NuxtLink
          to="/login"
          class="taskster_button"
        >
          Anmelden
        </NuxtLink>
      </div>
    </div>

    <!-- Stopwatch Modal -->
    <StopwatchModal />
  </header>
</template>

<script setup lang="ts">
import {
  Menu,
  Square,
  LogOut
} from 'lucide-vue-next'

defineEmits<{
  (e: 'toggle-wallpaper'): void
  (e: 'toggle-mobile-menu'): void
}>()

const { user, logout, initAuth } = useAuth()
const { state: stopwatchState, initStopwatch, openStopModal, formatSeconds } = useStopwatch()

onMounted(async () => {
  initStopwatch()
  if (!user.value) {
    await initAuth()
  }
})
</script>

