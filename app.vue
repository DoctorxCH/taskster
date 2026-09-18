<template>
  <div class="min-h-screen relative flex flex-col font-sans antialiased text-slate-900 selection:bg-cyan-500 selection:text-white">
    <!-- Dynamic MeisterTask-Style Background Wallpaper with Smooth Transition -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
      <img
        :src="currentWallpaper"
        alt="Taskster Wallpaper"
        class="w-full h-full object-cover object-center filter brightness-[0.92] contrast-[1.03] transition-all duration-700 ease-out"
      />
      <!-- Soft subtle gradient overlay so light cards and text pop crisp and readable -->
      <div class="absolute inset-0 bg-slate-950/25 backdrop-blur-[1px]"></div>
    </div>

    <!-- Main App Container (Navbar + Views + Right Sidebar) -->
    <div class="relative z-10 flex flex-col min-h-screen">
      <Navbar @toggle-wallpaper="showWallpaperPicker = !showWallpaperPicker" />

      <!-- Content Area with Right Sidebar Layout -->
      <div class="flex-1 flex w-full">
        <!-- Main Content Area -->
        <main class="flex-1 min-w-0 transition-all">
          <NuxtPage />
        </main>

        <!-- MeisterTask Right-Side Quick Action Rail / Sidebar -->
        <aside
          v-if="user && !isLoginPage"
          class="hidden md:flex flex-col w-16 hover:w-56 bg-white/80 hover:bg-white/95 backdrop-blur-xl border-l border-white/50 shadow-2xl transition-all duration-300 ease-in-out group/sidebar z-30 sticky top-16 h-[calc(100vh-4rem)] select-none shrink-0"
        >
          <div class="p-3 border-b border-slate-200/60 flex items-center justify-between">
            <span class="hidden group-hover/sidebar:inline text-[11px] font-black text-slate-500 uppercase tracking-wider">
              Navigation
            </span>
            <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-sm font-bold mx-auto group-hover/sidebar:mx-0 shadow-xs">
              ⚡
            </span>
          </div>

          <nav class="flex-1 py-4 space-y-1.5 px-2">
            <NuxtLink
              to="/dashboard"
              class="flex items-center space-x-3 px-2.5 py-2.5 rounded-2xl transition text-slate-700 hover:text-[#00A3C4] hover:bg-cyan-50/80"
              :class="$route.path === '/dashboard' ? 'bg-cyan-50 text-[#00A3C4] font-bold shadow-xs' : ''"
              title="Start / Dashboard"
            >
              <span class="text-xl">🏠</span>
              <span class="hidden group-hover/sidebar:inline text-xs font-bold whitespace-nowrap">Startseite</span>
            </NuxtLink>

            <NuxtLink
              to="/dashboard"
              class="flex items-center space-x-3 px-2.5 py-2.5 rounded-2xl transition text-slate-700 hover:text-[#00A3C4] hover:bg-cyan-50/80"
              title="Projektordner"
            >
              <span class="text-xl">📁</span>
              <span class="hidden group-hover/sidebar:inline text-xs font-bold whitespace-nowrap">Projektordner</span>
            </NuxtLink>

            <NuxtLink
              v-if="user?.is_superadmin"
              to="/admin"
              class="flex items-center space-x-3 px-2.5 py-2.5 rounded-2xl transition text-slate-700 hover:text-purple-700 hover:bg-purple-50/80"
              :class="$route.path.startsWith('/admin') ? 'bg-purple-50 text-purple-700 font-bold shadow-xs' : ''"
              title="Site-Admin"
            >
              <span class="text-xl">⚙️</span>
              <span class="hidden group-hover/sidebar:inline text-xs font-bold whitespace-nowrap">Administration</span>
            </NuxtLink>

            <NuxtLink
              to="/settings"
              class="flex items-center space-x-3 px-2.5 py-2.5 rounded-2xl transition text-slate-700 hover:text-[#00A3C4] hover:bg-cyan-50/80"
              :class="$route.path === '/settings' ? 'bg-cyan-50 text-[#00A3C4] font-bold shadow-xs' : ''"
              title="Mein Profil & Tarif"
            >
              <span class="text-xl">👤</span>
              <span class="hidden group-hover/sidebar:inline text-xs font-bold whitespace-nowrap">Mein Profil</span>
            </NuxtLink>

            <!-- MeisterTask Wallpaper Switcher Trigger Button in Sidebar -->
            <button
              @click="showWallpaperPicker = true"
              type="button"
              class="w-full flex items-center space-x-3 px-2.5 py-2.5 rounded-2xl transition text-slate-700 hover:text-[#00A3C4] hover:bg-cyan-50/80 text-left"
              title="Hintergrundbild anpassen (Wallpaper)"
            >
              <span class="text-xl">🖼️</span>
              <span class="hidden group-hover/sidebar:inline text-xs font-bold whitespace-nowrap">Hintergrund</span>
            </button>
          </nav>

          <!-- User Footer in Right Sidebar -->
          <div class="p-3 border-t border-slate-200/60 flex items-center justify-center group-hover/sidebar:justify-start space-x-2.5">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-xs font-black shadow-sm shrink-0">
              {{ user?.name?.charAt(0).toUpperCase() }}
            </div>
            <div class="hidden group-hover/sidebar:block min-w-0">
              <p class="text-xs font-bold text-slate-800 truncate">{{ user?.name }}</p>
              <p class="text-[10px] text-slate-500 truncate">{{ user?.company_name || 'Privater Workspace' }}</p>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- Wallpaper Picker Modal (MeisterTask Style Customizer) -->
    <div
      v-if="showWallpaperPicker"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl overflow-hidden flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
          <div>
            <h3 class="text-lg font-black text-slate-900 flex items-center space-x-2">
              <span>🖼️</span>
              <span>Hintergrundbild auswählen</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Wähle dein Lieblingsmotiv für deine persönliche Taskster-Atmosphäre.
            </p>
          </div>
          <button
            type="button"
            @click="showWallpaperPicker = false"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <!-- Wallpapers Grid -->
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 max-h-80 overflow-y-auto pr-1">
          <button
            v-for="wp in wallpapers"
            :key="wp.id"
            type="button"
            @click="selectWallpaper(wp.file)"
            class="group/wp relative aspect-video rounded-2xl overflow-hidden border-2 transition-all hover:scale-105"
            :class="currentWallpaper === wp.file ? 'border-[#00A3C4] ring-2 ring-cyan-500/40' : 'border-slate-200 hover:border-slate-400'"
          >
            <img :src="wp.file" :alt="wp.name" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent flex items-end p-1.5 opacity-90 group-hover/wp:opacity-100">
              <span class="text-[9px] font-bold text-white truncate w-full text-left">{{ wp.name }}</span>
            </div>
            <div
              v-if="currentWallpaper === wp.file"
              class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[#00A3C4] text-white flex items-center justify-center text-[10px] font-bold shadow-sm"
            >
              ✓
            </div>
          </button>
        </div>

        <div class="flex items-center justify-end pt-6 mt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showWallpaperPicker = false"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg"
          >
            Fertig
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { user, initAuth } = useAuth()
const { wallpapers, currentWallpaper, initWallpaper, setWallpaper } = useWallpaper()

const showWallpaperPicker = ref(false)

const isLoginPage = computed(() => route.path === '/login')

const selectWallpaper = (file: string) => {
  setWallpaper(file)
}

onMounted(async () => {
  initWallpaper()
  await initAuth()
})
</script>

<style>
/* Taskster Button Standards (per User Global Rule) */
.taskster_button {
  @apply bg-[#00A3C4] hover:bg-[#008ba8] text-white font-semibold transition inline-flex items-center justify-center space-x-2 shadow-sm shadow-cyan-900/10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
.taskster_button_accent {
  @apply bg-rose-600 hover:bg-rose-500 text-white font-semibold transition inline-flex items-center justify-center space-x-2 shadow-sm shadow-rose-900/10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
.taskster_button_light {
  @apply bg-white hover:bg-slate-50 text-slate-800 border-[3px] border-[#00A3C4] font-semibold transition inline-flex items-center justify-center space-x-2 shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}

/* Liquid Glass Design System */
.liquid_glass {
  background: rgba(255, 255, 255, 0.42);
  backdrop-filter: blur(24px) saturate(190%);
  -webkit-backdrop-filter: blur(24px) saturate(190%);
  border: 1px solid rgba(255, 255, 255, 0.45);
  box-shadow: 0 10px 32px 0 rgba(0, 0, 0, 0.08), 0 1px 0 0 rgba(255, 255, 255, 0.6) inset;
}

.liquid_glass_pill {
  background: rgba(255, 255, 255, 0.45);
  backdrop-filter: blur(16px) saturate(180%);
  -webkit-backdrop-filter: blur(16px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.45);
  box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.06), 0 1px 0 0 rgba(255, 255, 255, 0.6) inset;
}

.liquid_glass_card {
  background: rgba(255, 255, 255, 0.48);
  backdrop-filter: blur(26px) saturate(200%);
  -webkit-backdrop-filter: blur(26px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 14px 34px 0 rgba(0, 0, 0, 0.08), 0 1px 0 0 rgba(255, 255, 255, 0.65) inset;
}
</style>
