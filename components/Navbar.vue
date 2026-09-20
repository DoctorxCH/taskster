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
        <!-- Benachrichtigungen -->
        <div class="relative">
          <button
            type="button"
            class="relative h-9 w-9 flex items-center justify-center rounded-md text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors"
            :title="unreadCount > 0 ? `${unreadCount} ungelesene Benachrichtigungen` : 'Benachrichtigungen'"
            @click="notifOpen = !notifOpen"
          >
            <Bell class="w-4.5 h-4.5" />
            <span
              v-if="unreadCount > 0"
              class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center"
            >
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </button>

          <!-- Dropdown -->
          <div v-if="notifOpen" class="fixed inset-0 z-40" @click="notifOpen = false" />
          <div
            v-if="notifOpen"
            class="absolute right-0 top-full mt-2 w-[340px] max-w-[calc(100vw-2rem)] bg-white border border-slate-200 rounded-lg shadow-lg z-50 overflow-hidden"
          >
            <div class="flex items-center justify-between px-4 h-12 border-b border-slate-200">
              <span class="text-sm font-semibold text-slate-900">Benachrichtigungen</span>
              <button
                v-if="unreadCount > 0"
                type="button"
                class="text-[11px] font-semibold text-[#0891B2] hover:underline"
                @click="markAllRead"
              >
                Alle gelesen
              </button>
            </div>

            <div class="max-h-[360px] overflow-y-auto">
              <div v-if="loading" class="py-8 text-center text-xs text-slate-500">Lade…</div>
              <div v-else-if="recent.length === 0" class="py-8 px-4 text-center">
                <Bell class="w-5 h-5 text-slate-300 mx-auto mb-2" />
                <p class="text-xs text-slate-500">Keine Benachrichtigungen</p>
              </div>
              <button
                v-for="n in recent"
                :key="n.id"
                type="button"
                class="w-full text-left px-4 py-3 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors flex gap-3"
                :class="n.is_read ? 'opacity-60' : ''"
                @click="openNotification(n)"
              >
                <span class="w-1.5 h-1.5 rounded-full shrink-0 mt-1.5" :class="n.is_read ? 'bg-transparent' : 'bg-[#0891B2]'" />
                <span class="min-w-0 flex-1">
                  <span class="block text-xs font-semibold text-slate-900 leading-snug">{{ n.title }}</span>
                  <span v-if="n.message" class="block text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ n.message }}</span>
                  <span class="block text-[10px] text-slate-400 mt-1">{{ relativeTime(n.created_at) }}</span>
                </span>
              </button>
            </div>

            <NuxtLink
              to="/dashboard"
              class="block px-4 h-11 flex items-center justify-center text-xs font-semibold text-slate-600 hover:bg-slate-50 border-t border-slate-200"
              @click="notifOpen = false"
            >
              Alle im Dashboard anzeigen
            </NuxtLink>
          </div>
        </div>

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

        <!-- Language Switcher -->
        <div class="hidden sm:flex items-center mr-1">
          <select
            v-model="$i18n.locale"
            class="h-7 px-1.5 rounded-md text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 cursor-pointer"
            title="Sprache / Language / Jazyk"
          >
            <option value="de">DE</option>
            <option value="en">EN</option>
            <option value="sk">SK</option>
          </select>
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
  LogOut,
  Bell
} from 'lucide-vue-next'

defineEmits<{
  (e: 'toggle-wallpaper'): void
  (e: 'toggle-mobile-menu'): void
}>()

const { user, logout, initAuth } = useAuth()
const { state: stopwatchState, initStopwatch, openStopModal, formatSeconds } = useStopwatch()
const { recent, unreadCount, loading, refresh, markRead, markAllRead } = useNotifications()

const notifOpen = ref(false)

function relativeTime(value: string) {
  if (!value) return ''
  const then = new Date(String(value).replace(' ', 'T')).getTime()
  if (!Number.isFinite(then)) return ''
  const diff = Math.floor((Date.now() - then) / 1000)
  if (diff < 60) return 'gerade eben'
  if (diff < 3600) return `vor ${Math.floor(diff / 60)} Min.`
  if (diff < 86400) return `vor ${Math.floor(diff / 3600)} Std.`
  if (diff < 604800) return `vor ${Math.floor(diff / 86400)} Tg.`
  return new Date(then).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function openNotification(n: any) {
  markRead(n)
  notifOpen.value = false
  if (n.reference_type === 'event') {
    navigateTo('/calendar')
  } else if (n.reference_type === 'task' && n.project_id) {
    navigateTo(`/projects/${n.project_id}?task=${n.reference_id}`)
  } else if (n.reference_type === 'project' && n.reference_id) {
    navigateTo(`/projects/${n.reference_id}`)
  } else {
    navigateTo('/dashboard')
  }
}

onMounted(async () => {
  initStopwatch()
  if (!user.value) {
    await initAuth()
  }
  if (user.value) refresh(true)
})
</script>

