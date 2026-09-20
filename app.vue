<template>
  <div class="min-h-screen relative flex flex-col font-sans antialiased text-slate-900 bg-[#F8FAFC] selection:bg-[#0891B2] selection:text-white">
    <!-- Dynamic Background Wallpaper (Optional with strong opacity overlay for high legibility) -->
    <div v-if="currentWallpaper" class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
      <img
        :src="currentWallpaper"
        alt="Taskster Wallpaper"
        class="w-full h-full object-cover object-center filter brightness-[0.95]"
      />
      <div class="absolute inset-0 bg-white/92"></div>
    </div>

    <!-- Main App Container (Navbar + Layout) -->
    <div class="relative z-10 flex flex-col min-h-screen">
      <!-- Navbar Component -->
      <Navbar
        @toggle-wallpaper="showWallpaperPicker = !showWallpaperPicker"
        @toggle-mobile-menu="mobileMenuOpen = !mobileMenuOpen"
      />

      <!-- Mobile & Tablet Sidebar Overlay Drawer -->
      <div
        v-if="mobileMenuOpen && user && !isLoginPage"
        class="fixed inset-0 z-50 lg:hidden flex"
      >
        <div class="fixed inset-0 bg-slate-900/40" @click="mobileMenuOpen = false"></div>
        <aside class="relative z-10 w-64 max-w-[80vw] bg-white h-full border-r border-slate-200 flex flex-col p-4 shadow-xl">
          <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Menü</span>
            <button
              @click="mobileMenuOpen = false"
              class="w-8 h-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <nav class="flex-1 space-y-1">
            <NuxtLink
              to="/dashboard"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/dashboard' ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <LayoutDashboard class="w-4 h-4 shrink-0" />
              <span>Dashboard</span>
            </NuxtLink>

            <NuxtLink
              to="/time"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/time' ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <Clock class="w-4 h-4 shrink-0" />
              <span>Zeitrapporte</span>
            </NuxtLink>

            <NuxtLink
              to="/contacts"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/contacts') ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <BookUser class="w-4 h-4 shrink-0" />
              <span>Kontakte</span>
            </NuxtLink>

            <NuxtLink
              v-if="isPlatformAdmin"
              to="/admin"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/admin') ? 'bg-purple-50 text-purple-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <ShieldCheck class="w-4 h-4 shrink-0" />
              <span>Administration</span>
            </NuxtLink>

            <NuxtLink
              v-else-if="isCompanyAdmin"
              to="/company"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/company') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <Building2 class="w-4 h-4 shrink-0" />
              <span>Firmen-Admin</span>
            </NuxtLink>

            <NuxtLink
              to="/settings"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/settings' ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <Settings class="w-4 h-4 shrink-0" />
              <span>Mein Profil</span>
            </NuxtLink>
          </nav>

          <div class="pt-4 border-t border-slate-200 mt-auto">
            <button
              @click="showWallpaperPicker = true; mobileMenuOpen = false"
              type="button"
              class="w-full flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors"
            >
              <Image class="w-4 h-4" />
              <span>Hintergrund</span>
            </button>
          </div>
        </aside>
      </div>

      <!-- Main Layout with Left Desktop Sidebar -->
      <div class="flex-1 flex w-full">
        <!-- Left Fixed Desktop Sidebar (Design v2 Standard) -->
        <aside
          v-if="user && !isLoginPage"
          class="hidden lg:flex flex-col w-60 shrink-0 bg-white border-r border-slate-200 h-[calc(100vh-3.5rem)] sticky top-14 select-none z-30"
        >
          <nav class="p-3 space-y-0.5 flex-1">
            <NuxtLink
              to="/dashboard"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/dashboard' ? 'bg-cyan-50 text-cyan-800 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
              title="Dashboard"
            >
              <LayoutDashboard class="w-4 h-4 shrink-0 text-slate-500" />
              <span>Dashboard</span>
            </NuxtLink>

            <NuxtLink
              to="/time"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/time' ? 'bg-cyan-50 text-cyan-800 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
              title="Zeitrapporte"
            >
              <Clock class="w-4 h-4 shrink-0 text-slate-500" />
              <span>Zeitrapporte</span>
            </NuxtLink>

            <NuxtLink
              to="/contacts"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/contacts') ? 'bg-cyan-50 text-cyan-800 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
              title="Kontakte"
            >
              <BookUser class="w-4 h-4 shrink-0 text-slate-500" />
              <span>Kontakte</span>
            </NuxtLink>

            <NuxtLink
              v-if="isPlatformAdmin"
              to="/admin"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/admin') ? 'bg-purple-50 text-purple-800 font-semibold' : 'text-slate-600 hover:bg-purple-50/60 hover:text-purple-900'"
              title="Site-Administration"
            >
              <ShieldCheck class="w-4 h-4 shrink-0 text-purple-600" />
              <span>Administration</span>
            </NuxtLink>

            <NuxtLink
              v-else-if="isCompanyAdmin"
              to="/company"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/company') ? 'bg-emerald-50 text-emerald-800 font-semibold' : 'text-slate-600 hover:bg-emerald-50/60 hover:text-emerald-900'"
              title="Firmen-Administration"
            >
              <Building2 class="w-4 h-4 shrink-0 text-emerald-600" />
              <span>Firmen-Admin</span>
            </NuxtLink>

            <NuxtLink
              to="/settings"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/settings' ? 'bg-cyan-50 text-cyan-800 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
              title="Mein Profil & Tarif"
            >
              <Settings class="w-4 h-4 shrink-0 text-slate-500" />
              <span>Einstellungen</span>
            </NuxtLink>
          </nav>

          <!-- Mini-Kalender (Terminübersicht) -->
          <div class="border-t border-slate-200 pt-3">
            <div class="px-4 pb-1 flex items-center gap-2">
              <CalendarDays class="w-3.5 h-3.5 text-slate-400" />
              <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Kalender</span>
            </div>
            <MiniCalendar />
          </div>

          <!-- Sidebar Footer Wallpaper Trigger -->
          <div class="p-3 border-t border-slate-200 flex items-center justify-between">
            <button
              @click="showWallpaperPicker = true"
              type="button"
              class="flex items-center gap-2 text-xs text-slate-500 hover:text-slate-900 transition-colors py-1 px-2 rounded-md hover:bg-slate-100"
            >
              <Image class="w-3.5 h-3.5" />
              <span>Hintergrund</span>
            </button>
            <span class="text-[11px] font-mono text-slate-400">v2.0</span>
          </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 min-w-0 transition-all">
          <NuxtPage />
        </main>
      </div>
    </div>

    <!-- Wallpaper Picker Modal (Design v2 Standard) -->
    <div
      v-if="showWallpaperPicker"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40"
    >
      <div class="bg-white rounded-lg shadow-md w-full max-w-lg flex flex-col border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 h-14 border-b border-slate-200">
          <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
            <Image class="w-4 h-4 text-[#0891B2]" />
            <span>Hintergrundbild (Wallpaper)</span>
          </h2>
          <button
            type="button"
            @click="showWallpaperPicker = false"
            class="h-8 w-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="p-5 overflow-y-auto max-h-[60vh]">
          <p class="text-xs text-slate-500 mb-4">
            Das Design v2 ist für hohe Lesbarkeit auf dezentem neutralem Hintergrund optimiert. Optional kannst du ein Hintergrundmotiv aktivieren.
          </p>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <!-- No wallpaper option -->
            <button
              type="button"
              @click="selectWallpaper('')"
              class="relative aspect-video rounded-md overflow-hidden border transition-all flex flex-col items-center justify-center bg-slate-50 text-slate-600 hover:bg-slate-100"
              :class="!currentWallpaper ? 'border-[#0891B2] ring-2 ring-[#0891B2]/20 font-semibold' : 'border-slate-200'"
            >
              <span class="text-xs">Standard (Keins)</span>
              <Check v-if="!currentWallpaper" class="w-4 h-4 text-[#0891B2] absolute top-1.5 right-1.5" />
            </button>

            <!-- Wallpapers Grid -->
            <button
              v-for="wp in wallpapers"
              :key="wp.id"
              type="button"
              @click="selectWallpaper(wp.file)"
              class="relative aspect-video rounded-md overflow-hidden border transition-all"
              :class="currentWallpaper === wp.file ? 'border-[#0891B2] ring-2 ring-[#0891B2]/20' : 'border-slate-200 hover:border-slate-400'"
            >
              <img :src="wp.file" :alt="wp.name" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-slate-900/40 flex items-end p-1.5">
                <span class="text-[11px] text-white truncate w-full text-left font-medium">{{ wp.name }}</span>
              </div>
              <Check v-if="currentWallpaper === wp.file" class="w-4 h-4 text-white bg-[#0891B2] rounded-full p-0.5 absolute top-1.5 right-1.5 shadow-sm" />
            </button>
          </div>
        </div>

        <div class="flex justify-end gap-2 px-5 h-16 items-center border-t border-slate-200 bg-slate-50/50">
          <button
            type="button"
            @click="showWallpaperPicker = false"
            class="taskster_button"
          >
            Fertig
          </button>
        </div>
      </div>
    </div>

    <!-- Globale Command-Palette (Strg+K) -->
    <CommandPalette v-if="user && !isLoginPage" />
  </div>
</template>

<script setup lang="ts">
import {
  LayoutDashboard,
  Clock,
  BookUser,
  Building2,
  Settings,
  ShieldCheck,
  Image,
  Check,
  X,
  CalendarDays
} from 'lucide-vue-next'

const route = useRoute()
const { user, initAuth } = useAuth()
const { wallpapers, currentWallpaper, initWallpaper, setWallpaper } = useWallpaper()

const showWallpaperPicker = ref(false)
const mobileMenuOpen = ref(false)

const isLoginPage = computed(() => route.path === '/login')

// Plattform-Admin (Superadmin oder explizite Plattform-Permissions)
const isPlatformAdmin = computed(() => {
  if (!user.value) return false
  if (user.value.is_superadmin) return true
  let perms = user.value.admin_permissions
  if (typeof perms === 'string') {
    try { perms = JSON.parse(perms) } catch { perms = [] }
  }
  return Array.isArray(perms) && perms.length > 0
})

// Firmen-Admin (company_role === 'admin' mit Unternehmen, kein Plattform-Admin)
const isCompanyAdmin = computed(() => {
  if (!user.value || isPlatformAdmin.value) return false
  return Boolean(user.value.company_id && user.value.company_role === 'admin')
})

const selectWallpaper = (file: string) => {
  setWallpaper(file)
}

onMounted(async () => {
  initWallpaper()
  await initAuth()
})
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

body {
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-feature-settings: 'cv02', 'cv03', 'cv04', 'tnum';
  background-color: #F8FAFC;
  color: #0F172A;
}

/* Taskster Design v2 Button Standards */
.taskster_button {
  @apply bg-[#0891B2] hover:bg-[#0E7490] text-white font-semibold h-9 px-4 text-sm rounded-md transition-colors inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}

.taskster_button_accent {
  @apply bg-[#BE123C] hover:bg-[#9F1239] text-white font-semibold h-9 px-4 text-sm rounded-md transition-colors inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}

.taskster_button_light {
  @apply bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-semibold h-9 px-4 text-sm rounded-md transition-colors inline-flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Clean cards for backward compatibility if liquid_glass classes are referenced */
.liquid_glass,
.liquid_glass_card,
.liquid_glass_pill {
  background-color: #FFFFFF;
  border: 1px solid #E2E8F0;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}
</style>

