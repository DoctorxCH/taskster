<template>
  <div class="min-h-screen relative flex flex-col font-sans antialiased text-slate-900 bg-[#F8FAFC] selection:bg-[#0891B2] selection:text-white">
    <!-- Dynamic Background Wallpaper (Optional with strong opacity overlay for high legibility) -->
    <div v-if="currentWallpaper" class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
      <img
        :src="currentWallpaper"
        :alt="$t('app.wallpaper_alt')"
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
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('app.menu') }}</span>
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
              <span>{{ $t('common.dashboard') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/time"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/time' ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <Clock class="w-4 h-4 shrink-0" />
              <span>{{ $t('common.zeitrapporte') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/contacts"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/contacts') ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <BookUser class="w-4 h-4 shrink-0" />
              <span>{{ $t('common.kontakte') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/calendar"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/calendar') ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <CalendarDays class="w-4 h-4 shrink-0" />
              <span>{{ $t('common.kalender') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/journal"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/journal') ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <BookOpen class="w-4 h-4 shrink-0" />
              <span>{{ $t('journal.tab_journal') || 'Projektjournal' }}</span>
            </NuxtLink>

            <NuxtLink
              v-if="isPlatformAdmin"
              to="/admin"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/admin') ? 'bg-purple-50 text-purple-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <ShieldCheck class="w-4 h-4 shrink-0" />
              <span>{{ $t('common.administration') }}</span>
            </NuxtLink>

            <!-- Admin Sub-Menu (Mobile) -->
            <div v-if="isPlatformAdmin && $route.path.startsWith('/admin')" class="ml-4 pl-2.5 border-l-2 border-purple-200 space-y-0.5 my-1">
              <NuxtLink
                v-if="hasAdminPermission('manage_users')"
                to="/admin?tab=users"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'users' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('admin.benutzerverwaltung')"
              >
                <Users class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('admin.benutzerverwaltung') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('company_settings')"
                to="/admin?tab=companies"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'companies' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('admin.unternehmen')"
              >
                <Building2 class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('admin.unternehmen') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('finance')"
                to="/admin?tab=finance"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'finance' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('admin.finanzen_lizenzen')"
              >
                <CreditCard class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('admin.finanzen_lizenzen') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('manage_templates')"
                to="/admin?tab=templates"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'templates' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('admin.projekt_vorlagen')"
              >
                <ClipboardList class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('admin.projekt_vorlagen') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('company_settings')"
                to="/admin?tab=email"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'email' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('admin.email_versand')"
              >
                <Mail class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('admin.email_versand') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('manage_users')"
                to="/admin?tab=invites"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'invites' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('app.einladungen')"
              >
                <Send class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>Einladungen</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('company_settings')"
                to="/admin?tab=website"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'website' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('app.webseite_cms')"
              >
                <Globe class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('app.webseite_cms') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('company_settings')"
                to="/admin?tab=ai"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'ai' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('app.ai_plaene')"
              >
                <Sparkles class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('app.ai_plaene') }}</span>
              </NuxtLink>

              <NuxtLink
                v-if="hasAdminPermission('any_admin')"
                to="/admin?tab=audit"
                @click="mobileMenuOpen = false"
                class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                :class="currentAdminTab === 'audit' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                :title="$t('app.security_audit')"
              >
                <ShieldCheck class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                <span>{{ $t('app.audit_logs') }}</span>
              </NuxtLink>
            </div>

            <NuxtLink
              v-else-if="isCompanyAdmin"
              to="/company"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path.startsWith('/company') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <Building2 class="w-4 h-4 shrink-0" />
              <span>{{ $t('common.firmen_admin') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/settings"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium transition-colors"
              :class="$route.path === '/settings' ? 'bg-cyan-50 text-cyan-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <Settings class="w-4 h-4 shrink-0" />
              <span>{{ $t('common.mein_profil') }}</span>
            </NuxtLink>
          </nav>

          <div class="pt-4 border-t border-slate-200 mt-auto">
            <button
              @click="showWallpaperPicker = true; mobileMenuOpen = false"
              type="button"
              class="w-full flex items-center gap-3 px-3 h-9 rounded-md text-sm font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
            >
              <Image class="w-4 h-4" />
              <span>{{ $t('common.hintergrund') }}</span>
            </button>
          </div>
        </aside>
      </div>

      <!-- Main Layout with Left Desktop Sidebar -->
      <div class="flex-1 flex w-full">
        <!-- Left Fixed Desktop Sidebar (Design v2 Standard, Collapsible) -->
        <aside
          v-if="user && !isLoginPage"
          class="hidden lg:flex flex-col shrink-0 bg-white border-r border-slate-200 h-[calc(100vh-3.5rem)] sticky top-14 select-none z-30 transition-all duration-300 ease-in-out"
          :class="sidebarCollapsed ? 'w-16' : 'w-60'"
        >
          <!-- Sidebar Header: Toggle Button & Title -->
          <div
            class="p-2 border-b border-slate-100 flex items-center shrink-0"
            :class="sidebarCollapsed ? 'justify-center' : 'justify-between'"
          >
            <span v-if="!sidebarCollapsed" class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-2 truncate">
              {{ $t('app.navigation') }}
            </span>
            <button
              type="button"
              @click="toggleSidebar"
              class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer border border-transparent hover:border-slate-200"
              :title="sidebarCollapsed ? $t('app.sidebar_ausklappen') : $t('app.sidebar_einklappen')"
            >
              <PanelLeftOpen v-if="sidebarCollapsed" class="w-4 h-4 text-[#00A3C4]" />
              <PanelLeftClose v-else class="w-4 h-4 text-slate-500" />
            </button>
          </div>

          <!-- Main Navigation Links -->
          <nav
            class="space-y-1 flex-1 overflow-y-auto overflow-x-hidden"
            :class="sidebarCollapsed ? 'p-2' : 'p-3'"
          >
            <NuxtLink
              to="/dashboard"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path === '/dashboard' ? 'bg-cyan-50 text-[#00A3C4] font-semibold border border-cyan-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.dashboard') : ''"
            >
              <LayoutDashboard class="w-4 h-4 shrink-0" :class="$route.path === '/dashboard' ? 'text-[#00A3C4]' : 'text-slate-500 group-hover:text-slate-700'" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.dashboard') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/time"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path === '/time' ? 'bg-cyan-50 text-[#00A3C4] font-semibold border border-cyan-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.zeitrapporte') : ''"
            >
              <Clock class="w-4 h-4 shrink-0" :class="$route.path === '/time' ? 'text-[#00A3C4]' : 'text-slate-500 group-hover:text-slate-700'" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.zeitrapporte') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/contacts"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path.startsWith('/contacts') ? 'bg-cyan-50 text-[#00A3C4] font-semibold border border-cyan-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.kontakte') : ''"
            >
              <BookUser class="w-4 h-4 shrink-0" :class="$route.path.startsWith('/contacts') ? 'text-[#00A3C4]' : 'text-slate-500 group-hover:text-slate-700'" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.kontakte') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/calendar"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path.startsWith('/calendar') ? 'bg-cyan-50 text-[#00A3C4] font-semibold border border-cyan-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.kalender') : ''"
            >
              <CalendarDays class="w-4 h-4 shrink-0" :class="$route.path.startsWith('/calendar') ? 'text-[#00A3C4]' : 'text-slate-500 group-hover:text-slate-700'" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.kalender') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/journal"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path.startsWith('/journal') ? 'bg-cyan-50 text-[#00A3C4] font-semibold border border-cyan-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? ($t('journal.tab_journal') || 'Projektjournal') : ''"
            >
              <BookOpen class="w-4 h-4 shrink-0" :class="$route.path.startsWith('/journal') ? 'text-[#00A3C4]' : 'text-slate-500 group-hover:text-slate-700'" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('journal.tab_journal') || 'Projektjournal' }}</span>
            </NuxtLink>

            <NuxtLink
              v-if="isPlatformAdmin"
              to="/admin"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path.startsWith('/admin') ? 'bg-purple-50 text-purple-800 font-semibold border border-purple-200 shadow-2xs' : 'text-slate-600 hover:bg-purple-50/60 hover:text-purple-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.administration') : ''"
            >
              <ShieldCheck class="w-4 h-4 shrink-0 text-purple-600" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.administration') }}</span>
            </NuxtLink>

            <!-- Admin Sub-Menu (Desktop) -->
            <div v-if="isPlatformAdmin && $route.path.startsWith('/admin')">
              <!-- Expanded: indented sub-menu -->
              <div v-if="!sidebarCollapsed" class="ml-4 pl-2.5 border-l-2 border-purple-200 space-y-0.5 my-1">
                <NuxtLink
                  v-if="hasAdminPermission('manage_users')"
                  to="/admin?tab=users"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'users' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('admin.benutzerverwaltung')"
                >
                  <Users class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('admin.benutzerverwaltung') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=companies"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'companies' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('admin.unternehmen')"
                >
                  <Building2 class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('admin.unternehmen') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('finance')"
                  to="/admin?tab=finance"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'finance' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('admin.finanzen_lizenzen')"
                >
                  <CreditCard class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('admin.finanzen_lizenzen') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('manage_templates')"
                  to="/admin?tab=templates"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'templates' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('admin.projekt_vorlagen')"
                >
                  <ClipboardList class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('admin.projekt_vorlagen') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=email"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'email' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('admin.email_versand')"
                >
                  <Mail class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('admin.email_versand') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('manage_users')"
                  to="/admin?tab=invites"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'invites' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('app.einladungen')"
                >
                  <Send class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>Einladungen</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=website"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'website' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('app.webseite_cms')"
                >
                  <Globe class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('app.webseite_cms') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=ai"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'ai' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('app.ai_plaene')"
                >
                  <Sparkles class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('app.ai_plaene') }}</span>
                </NuxtLink>

                <NuxtLink
                  v-if="hasAdminPermission('any_admin')"
                  to="/admin?tab=audit"
                  class="flex items-center gap-2 px-2.5 h-8 rounded-md text-xs font-medium transition-colors"
                  :class="currentAdminTab === 'audit' ? 'bg-purple-100 text-purple-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                  :title="$t('app.security_audit')"
                >
                  <ShieldCheck class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                  <span>{{ $t('app.audit_logs') }}</span>
                </NuxtLink>
              </div>

              <!-- Collapsed: compact sub-item icons -->
              <div v-else class="my-1.5 pt-1.5 border-t border-purple-100 space-y-1">
                <NuxtLink
                  v-if="hasAdminPermission('manage_users')"
                  to="/admin?tab=users"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'users' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('admin.benutzerverwaltung')"
                >
                  <Users class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=companies"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'companies' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('admin.unternehmen')"
                >
                  <Building2 class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('finance')"
                  to="/admin?tab=finance"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'finance' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('admin.finanzen_lizenzen')"
                >
                  <CreditCard class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('manage_templates')"
                  to="/admin?tab=templates"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'templates' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('admin.projekt_vorlagen')"
                >
                  <ClipboardList class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=email"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'email' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('admin.email_versand')"
                >
                  <Mail class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('manage_users')"
                  to="/admin?tab=invites"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'invites' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('app.einladungen')"
                >
                  <Send class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=website"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'website' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('app.webseite_cms')"
                >
                  <Globe class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('company_settings')"
                  to="/admin?tab=ai"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'ai' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('app.ai_plaene')"
                >
                  <Sparkles class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
                <NuxtLink
                  v-if="hasAdminPermission('any_admin')"
                  to="/admin?tab=audit"
                  class="flex items-center justify-center w-8 h-8 mx-auto rounded-md transition-colors"
                  :class="currentAdminTab === 'audit' ? 'bg-purple-100 text-purple-900 shadow-2xs font-semibold' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-800'"
                  :title="$t('app.security_audit')"
                >
                  <ShieldCheck class="w-3.5 h-3.5 shrink-0 text-purple-600" />
                </NuxtLink>
              </div>
            </div>

            <NuxtLink
              v-else-if="isCompanyAdmin"
              to="/company"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path.startsWith('/company') ? 'bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200 shadow-2xs' : 'text-slate-600 hover:bg-emerald-50/60 hover:text-emerald-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.firmen_admin') : ''"
            >
              <Building2 class="w-4 h-4 shrink-0 text-emerald-600" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.firmen_admin') }}</span>
            </NuxtLink>

            <NuxtLink
              to="/settings"
              class="flex items-center rounded-lg text-sm font-medium transition-colors group"
              :class="[
                sidebarCollapsed ? 'justify-center w-10 h-10 mx-auto px-0' : 'gap-3 px-3 h-9',
                $route.path === '/settings' ? 'bg-cyan-50 text-[#00A3C4] font-semibold border border-cyan-200/80 shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-transparent'
              ]"
              :title="sidebarCollapsed ? $t('common.einstellungen') : ''"
            >
              <Settings class="w-4 h-4 shrink-0" :class="$route.path === '/settings' ? 'text-[#00A3C4]' : 'text-slate-500 group-hover:text-slate-700'" />
              <span v-if="!sidebarCollapsed" class="truncate">{{ $t('common.einstellungen') }}</span>
            </NuxtLink>
          </nav>

          <!-- Mini-Kalender (Terminübersicht) -->
          <div v-if="!sidebarCollapsed" class="border-t border-slate-200 pt-3">
            <div class="px-4 pb-1 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CalendarDays class="w-3.5 h-3.5 text-slate-400" />
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">{{ $t('common.kalender') }}</span>
              </div>
              <NuxtLink
                to="/calendar"
                class="text-[11px] text-[#00A3C4] hover:underline font-semibold"
              >
                {{ $t('mini_cal.kalender_oeffnen') }}
              </NuxtLink>
            </div>
            <MiniCalendar :show-events="false" />
          </div>
          <div v-else class="border-t border-slate-200 py-2 flex flex-col items-center">
            <NuxtLink
              to="/calendar"
              class="w-10 h-10 rounded-lg flex items-center justify-center text-slate-500 hover:text-[#00A3C4] hover:bg-cyan-50 transition-colors mx-auto"
              :title="$t('common.kalender')"
            >
              <CalendarDays class="w-5 h-5" />
            </NuxtLink>
          </div>

          <!-- Sidebar Footer Wallpaper Trigger & Version -->
          <div
            class="p-2 border-t border-slate-200 flex items-center shrink-0"
            :class="sidebarCollapsed ? 'flex-col gap-1 justify-center' : 'justify-between px-3 py-2.5'"
          >
            <button
              @click="showWallpaperPicker = true"
              type="button"
              class="flex items-center gap-2 text-xs text-slate-500 hover:text-slate-900 transition-colors rounded-md hover:bg-slate-100 cursor-pointer"
              :class="sidebarCollapsed ? 'w-10 h-10 justify-center p-0' : 'py-1 px-2'"
              :title="$t('common.hintergrund')"
            >
              <Image class="w-4 h-4" />
              <span v-if="!sidebarCollapsed">{{ $t('common.hintergrund') }}</span>
            </button>
            <span v-if="!sidebarCollapsed" class="text-[11px] font-mono text-slate-400">v2.0</span>
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
            <span>{{ $t('wallpaper.titel') }}</span>
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
            {{ $t('wallpaper.beschreibung') }}
          </p>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <!-- No wallpaper option -->
            <button
              type="button"
              @click="selectWallpaper('')"
              class="relative aspect-video rounded-md overflow-hidden border transition-all flex flex-col items-center justify-center bg-slate-50 text-slate-600 hover:bg-slate-100"
              :class="!currentWallpaper ? 'border-[#0891B2] ring-2 ring-[#0891B2]/20 font-semibold' : 'border-slate-200'"
            >
              <span class="text-xs">{{ $t('wallpaper.standard_keins') }}</span>
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
            {{ $t('wallpaper.fertig') }}
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
  CalendarDays,
  BookOpen,
  Users,
  CreditCard,
  ClipboardList,
  Mail,
  Send,
  Globe,
  Sparkles,
  PanelLeftClose,
  PanelLeftOpen
} from 'lucide-vue-next'

const route = useRoute()
const { user, initAuth, authHeaders } = useAuth()
const { wallpapers, currentWallpaper, initWallpaper, setWallpaper } = useWallpaper()
const { fetchSettings } = useWebsiteSettings()
const { setLocale } = useI18n()

const showWallpaperPicker = ref(false)
const mobileMenuOpen = ref(false)
const sidebarCollapsed = ref(false)

function initSidebarState() {
  if (!import.meta.client) return
  const uid = user.value?.id || 'default'
  const saved = localStorage.getItem(`taskster_sidebar_collapsed_${uid}`)
  if (saved !== null) {
    sidebarCollapsed.value = saved === 'true'
  } else if (user.value?.settings?.sidebar_collapsed !== undefined) {
    sidebarCollapsed.value = Boolean(user.value.settings.sidebar_collapsed)
  }
}

const toggleSidebar = async () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
  const uid = user.value?.id || 'default'
  if (import.meta.client) {
    localStorage.setItem(`taskster_sidebar_collapsed_${uid}`, String(sidebarCollapsed.value))
  }
  if (user.value?.id) {
    try {
      const currentSettings = user.value.settings || {}
      await $fetch('/api/auth/profile', {
        method: 'PATCH',
        headers: authHeaders(),
        body: {
          settings: {
            ...currentSettings,
            sidebar_collapsed: sidebarCollapsed.value
          }
        }
      })
      if (user.value.settings) {
        user.value.settings.sidebar_collapsed = sidebarCollapsed.value
      }
    } catch {
      // LocalStorage fallback already set
    }
  }
}

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

const currentAdminTab = computed(() => {
  if (!route.path.startsWith('/admin')) return ''
  return (route.query.tab as string) || 'users'
})

const hasAdminPermission = (perm: string) => {
  if (!user.value) return false
  if (user.value.is_superadmin) return true
  let perms = user.value.admin_permissions
  if (typeof perms === 'string') {
    try { perms = JSON.parse(perms) } catch { perms = [] }
  }
  return Array.isArray(perms) && perms.includes(perm)
}

// Firmen-Admin (company_role === 'admin' mit Unternehmen, kein Plattform-Admin)
const isCompanyAdmin = computed(() => {
  if (!user.value || isPlatformAdmin.value) return false
  return Boolean(user.value.company_id && user.value.company_role === 'admin')
})

const selectWallpaper = (file: string) => {
  setWallpaper(file)
}

function syncUserLanguage() {
  const lang = user.value?.settings?.language
  if (lang && ['de', 'en', 'sk'].includes(lang)) {
    setLocale(lang)
  }
}

onMounted(async () => {
  initWallpaper()
  fetchSettings()
  await initAuth()
  initSidebarState()
  syncUserLanguage()
  startNotificationPolling()
})

// ---------------------------------------------------------------------------
// Benachrichtigungen: regelmässig nachladen und über die aktivierten Kanäle
// (In-App, Browser, Ton) melden. Die Auswahl steuert users.settings.notifications.
// ---------------------------------------------------------------------------
let notificationTimer: ReturnType<typeof setInterval> | null = null

async function startNotificationPolling() {
  if (!import.meta.client) return
  if (!user.value) return
  const { refresh } = useNotifications()
  await refresh(true)
  if (notificationTimer) clearInterval(notificationTimer)
  notificationTimer = setInterval(() => {
    if (document.visibilityState === 'visible') refresh(true)
  }, 60_000)
}

watch(user, (u) => {
  if (u) {
    initSidebarState()
    syncUserLanguage()
    startNotificationPolling()
  } else if (notificationTimer) {
    clearInterval(notificationTimer)
    notificationTimer = null
  }
}, { deep: true })

onBeforeUnmount(() => {
  if (notificationTimer) clearInterval(notificationTimer)
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

