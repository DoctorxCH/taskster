<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-600 font-medium text-sm bg-white border border-slate-200 rounded-lg max-w-sm mx-auto">
      Lade Ordnerdetails und Projekte...
    </div>

    <div v-else-if="folder" class="space-y-6">
      <!-- Single Unified Header & Folder Dashboard Card -->
      <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs space-y-4">
        <!-- Breadcrumb inside white card -->
        <div class="flex items-center gap-1.5 text-xs text-slate-500 pb-3 border-b border-slate-100">
          <NuxtLink to="/dashboard" class="hover:text-[#0891B2] transition-colors flex items-center gap-1 font-medium">
            <LayoutDashboard class="w-3.5 h-3.5" />
            <span>Dashboard</span>
          </NuxtLink>
          <span>/</span>
          <span class="text-slate-800 font-semibold flex items-center gap-1">
            <span v-if="folder?.icon" class="text-sm">{{ folder.icon }}</span>
            <Folder v-else class="w-3.5 h-3.5 text-[#0891B2]" />
            <span>{{ folder?.name || 'Ordner' }}</span>
          </span>
        </div>

        <!-- Row 1: Folder Title, Meta & Main Action Buttons -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2.5 mb-1">
              <div class="w-9 h-9 flex items-center justify-center shrink-0 text-xl">
                <span v-if="folder.icon">{{ folder.icon }}</span>
                <Folder v-else class="w-5 h-5" />
              </div>
              <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ folder.name }}</h1>
              <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-semibold border border-slate-200 ml-1">
                {{ projects.length }} {{ projects.length === 1 ? 'Projekt' : 'Projekte' }}
              </span>
            </div>
            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1">
              <span>Owner: <strong class="text-slate-900 font-semibold">{{ folder.owner_name }}</strong></span>
              <span v-if="user?.id === folder.owner_id" class="px-2 py-0.5 rounded bg-cyan-50 text-[#0891B2] border border-cyan-200 text-xs font-semibold">
                Du (Owner)
              </span>
              <span
                class="px-2 py-0.5 rounded border text-xs font-semibold flex items-center space-x-1"
                :class="folder.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-700'"
              >
                <Building2 v-if="folder.visibility === 'company'" class="w-3 h-3 text-emerald-600 inline mr-0.5" />
                <Lock v-else class="w-3 h-3 text-slate-500 inline mr-0.5" />
                <span>{{ folder.visibility === 'company' ? 'Unternehmen' : 'Privat' }}</span>
              </span>
              <span v-if="folder.company_name" class="text-slate-700 font-medium">• {{ folder.company_name }}</span>
              <span>• {{ new Date(folder.created_at).toLocaleDateString('de-CH') }}</span>
              <span
                v-if="currentFolderTemplate"
                class="px-2.5 py-0.5 rounded-full border border-cyan-200 bg-cyan-50 text-[#0891B2] text-xs font-semibold flex items-center gap-1 cursor-pointer hover:bg-cyan-100 transition"
                @click="openEditFolderModal"
                :title="`Projektordner-Vorlage: ${currentFolderTemplate.name}. Klicken zum Anpassen.`"
              >
                <BookOpen class="w-3.5 h-3.5 text-[#0891B2]" />
                <span>Vorlage: <strong>{{ currentFolderTemplate.name }}</strong></span>
              </span>
              <button
                v-else-if="user?.id === folder.owner_id"
                @click="openEditFolderModal"
                class="px-2.5 py-0.5 rounded-full border border-dashed border-slate-300 text-slate-500 hover:text-[#0891B2] hover:border-cyan-300 text-xs font-medium flex items-center gap-1 cursor-pointer transition"
                title="Branchen-Vorlage für diesen Projektordner zuweisen"
              >
                <Plus class="w-3 h-3" />
                <span>Ordner-Vorlage zuweisen</span>
              </button>
            </p>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <!-- Quick Journal Entry Button -->
            <button
              @click="openQuickFolderJournalModal"
              class="taskster_button_light px-3.5 text-xs h-9 rounded-md cursor-pointer flex items-center space-x-1.5"
              title="Projektjournal-Eintrag erfassen"
            >
              <BookOpen class="w-3.5 h-3.5 text-[#0891B2]" />
              <span class="font-semibold">+ PJ erfassen</span>
            </button>

            <!-- More Actions Dropdown -->
            <div class="relative">
              <button
                @click="showActionsMenu = !showActionsMenu"
                class="taskster_button_light px-3.5 text-xs h-9 rounded-md cursor-pointer flex items-center space-x-1.5"
                :class="showActionsMenu ? 'ring-2 ring-cyan-200' : ''"
                title="Weitere Aktionen"
              >
                <MoreVertical class="w-4 h-4 text-slate-600" />
                <span class="hidden sm:inline">Mehr</span>
              </button>
              <div v-if="showActionsMenu" class="fixed inset-0 z-40" @click="showActionsMenu = false"></div>
              <div v-if="showActionsMenu" class="absolute right-0 top-full mt-1 w-56 bg-white border border-slate-200 rounded-lg shadow-lg z-50 py-1">
                <!-- Ansicht -->
                <div class="px-3 pt-1.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Ansicht</div>
                <button
                  @click="projectViewMode = 'grid'; showActionsMenu = false"
                  class="w-full text-left px-3 py-2 text-xs font-semibold flex items-center space-x-2 cursor-pointer"
                  :class="projectViewMode === 'grid' ? 'text-[#0891B2] bg-cyan-50' : 'text-slate-700 hover:bg-slate-50'"
                >
                  <LayoutGrid class="w-4 h-4" />
                  <span>Kacheln</span>
                  <Check v-if="projectViewMode === 'grid'" class="w-3.5 h-3.5 ml-auto" />
                </button>
                <button
                  @click="projectViewMode = 'list'; showActionsMenu = false"
                  class="w-full text-left px-3 py-2 text-xs font-semibold flex items-center space-x-2 cursor-pointer"
                  :class="projectViewMode === 'list' ? 'text-[#0891B2] bg-cyan-50' : 'text-slate-700 hover:bg-slate-50'"
                >
                  <List class="w-4 h-4" />
                  <span>Liste</span>
                  <Check v-if="projectViewMode === 'list'" class="w-3.5 h-3.5 ml-auto" />
                </button>

                <div class="my-1 border-t border-slate-100"></div>

                <!-- Aktionen -->
                <button
                  v-if="user?.id === folder.owner_id"
                  @click="showActionsMenu = false; openShareFolderModal()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Users class="w-4 h-4 text-slate-500" />
                  <span>Ordner teilen</span>
                </button>
                <button
                  v-if="user?.id === folder.owner_id"
                  @click="showActionsMenu = false; openEditFolderModal()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Pencil class="w-4 h-4 text-slate-500" />
                  <span>Ordner anpassen</span>
                </button>
                <button
                  @click="showActionsMenu = false; openImportProjectModal()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <FileUp class="w-4 h-4 text-slate-500" />
                  <span>Projekt importieren</span>
                </button>
                <div v-if="user?.id === folder.owner_id || user?.is_superadmin" class="my-1 border-t border-slate-100"></div>
                <button
                  v-if="user?.id === folder.owner_id || user?.is_superadmin"
                  @click="showActionsMenu = false; openDeleteFolderModal()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Trash2 class="w-4 h-4 text-rose-500" />
                  <span>{{ $t('dashboard.ordner_loeschen') }}</span>
                </button>
              </div>
            </div>
            <button
              @click="openNewProjectModal"
              class="taskster_button px-4 text-xs h-9 rounded-md cursor-pointer flex items-center space-x-1"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Neues Projekt</span>
            </button>
          </div>
        </div>

        <!-- Row 2: Controlling Summary Bar inside same Header Card -->
        <div v-if="projects.length > 0" class="pt-3 border-t border-slate-200">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 text-xs text-slate-700">
              <BarChart3 class="w-4 h-4 text-[#0891B2] shrink-0" />
              <span class="font-semibold text-slate-900">Controlling:</span>
              <span class="text-[#0891B2] font-bold">{{ timeSummary?.total_hours || 0 }} Std. Gesamtaufwand</span>
              <span v-if="timeSummary?.total_cost > 0" class="text-slate-600 hidden sm:inline">• {{ Number(timeSummary.total_cost).toLocaleString('de-CH') }} CHF</span>
            </div>

            <button
              type="button"
              @click="showControllingDetails = !showControllingDetails"
              class="px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-md transition flex items-center space-x-1 shrink-0 cursor-pointer"
            >
              <span>{{ showControllingDetails ? 'Details einklappen' : 'Details anzeigen' }}</span>
              <ChevronUp v-if="showControllingDetails" class="w-3.5 h-3.5" />
              <ChevronDown v-else class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Collapsible Breakdown per Project -->
          <div v-if="showControllingDetails" class="mt-3 pt-3 border-t border-slate-200 space-y-3">
            <div
              v-for="p in sortedProjects"
              :key="p.id"
              class="p-3 rounded-md bg-slate-50 border border-slate-200 text-xs"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 mb-1.5">
                <div class="flex items-center space-x-2">
                  <NuxtLink :to="`/projects/${p.id}`" class="font-bold text-slate-900 hover:text-[#0891B2] transition hover:underline">
                    {{ p.title }}
                  </NuxtLink>
                  <span class="text-[10px] px-2 py-0.2 rounded font-semibold uppercase" :class="p.status === 'completed' ? 'bg-slate-200 text-slate-700' : 'bg-cyan-100 text-[#0891B2] border border-cyan-200'">
                    {{ p.status }}
                  </span>
                </div>

                <div class="flex items-center space-x-4 text-xs font-medium text-slate-700">
                  <span>
                    Ist: <strong class="text-slate-900">{{ p.tracked_hours || 0 }} Std.</strong>
                    <span v-if="p.budget_hours" class="text-slate-500 font-normal"> / {{ p.budget_hours }} Std.</span>
                  </span>
                  <span v-if="p.tracked_cost > 0" class="text-slate-600">
                    {{ Number(p.tracked_cost).toLocaleString('de-CH') }} {{ p.currency || 'CHF' }}
                  </span>
                  <NuxtLink :to="`/projects/${p.id}`" class="text-[#0891B2] hover:underline text-xs font-semibold flex items-center space-x-0.5">
                    <span>Öffnen</span>
                    <ArrowRight class="w-3 h-3" />
                  </NuxtLink>
                </div>
              </div>

              <!-- Visual Progress Bar for Project Budget -->
              <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                <div
                  v-if="p.budget_hours > 0"
                  class="h-1.5 rounded-full transition-all duration-300"
                  :class="(p.tracked_hours || 0) > p.budget_hours ? 'bg-rose-500' : ((p.tracked_hours || 0) / p.budget_hours >= 0.8 ? 'bg-amber-500' : 'bg-[#0891B2]')"
                  :style="{ width: Math.min(100, Math.round(((p.tracked_hours || 0) / p.budget_hours) * 100)) + '%' }"
                ></div>
                <div
                  v-else
                  class="h-1.5 bg-cyan-500/60 rounded-full transition-all"
                  :style="{ width: Math.min(100, (p.tracked_hours || 0) * 5) + '%' }"
                ></div>
              </div>
              
              <div class="flex items-center justify-between text-[10px] text-slate-500 font-medium mt-1">
                <span v-if="p.budget_hours > 0">
                  Auslastung: {{ Math.round(((p.tracked_hours || 0) / p.budget_hours) * 100) }}%
                  <span v-if="(p.tracked_hours || 0) > p.budget_hours" class="text-rose-600 font-semibold ml-1">Budget überschritten (+{{ ((p.tracked_hours || 0) - p.budget_hours).toFixed(1) }} Std.)</span>
                  <span v-else class="text-emerald-700 font-semibold ml-1">({{ (p.budget_hours - (p.tracked_hours || 0)).toFixed(1) }} Std. verbleibend)</span>
                </span>
                <span v-else class="italic text-slate-400">Kein Stunden-Budget festgelegt</span>
                <span v-if="p.budget_amount > 0">Kostenbudget: {{ p.budget_amount.toLocaleString('de-CH') }} {{ p.currency || 'CHF' }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Row 3: Folder Navigation Tabs -->
        <div class="flex items-center gap-1.5 border-t border-slate-200 pt-3 overflow-x-auto text-xs font-semibold">
          <button
            type="button"
            @click="currentFolderTab = 'projects'"
            class="py-2 px-3.5 rounded-lg flex items-center gap-1.5 cursor-pointer transition whitespace-nowrap"
            :class="currentFolderTab === 'projects' ? 'bg-[#0891B2] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
          >
            <LayoutGrid class="w-4 h-4" />
            <span>Projekte ({{ projects.length }})</span>
          </button>
          <button
            type="button"
            @click="currentFolderTab = 'journal'"
            class="py-2 px-3.5 rounded-lg flex items-center gap-1.5 cursor-pointer transition whitespace-nowrap"
            :class="currentFolderTab === 'journal' ? 'bg-[#0891B2] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
          >
            <BookOpen class="w-4 h-4" />
            <span>Projektjournal ({{ folderJournals.length }})</span>
          </button>
          <button
            type="button"
            @click="currentFolderTab = 'fields'"
            class="py-2 px-3.5 rounded-lg flex items-center gap-1.5 cursor-pointer transition whitespace-nowrap"
            :class="currentFolderTab === 'fields' ? 'bg-[#0891B2] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
          >
            <SlidersHorizontal class="w-4 h-4" />
            <span>Benutzerdefinierte Felder ({{ fields.length }})</span>
          </button>
          <button
            type="button"
            @click="currentFolderTab = 'contacts'"
            class="py-2 px-3.5 rounded-lg flex items-center gap-1.5 cursor-pointer transition whitespace-nowrap"
            :class="currentFolderTab === 'contacts' ? 'bg-[#0891B2] text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
          >
            <Contact class="w-4 h-4" />
            <span>Kontakte ({{ folderContacts.length }})</span>
          </button>
        </div>
      </div>

        <!-- TAB 1: PROJEKTE -->
        <div v-if="currentFolderTab === 'projects'" class="space-y-4">
          <!-- Search & Filter Toolbar -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white border border-slate-200 rounded-lg p-3 shadow-2xs">
            <div class="flex items-center gap-2 flex-1 max-w-md">
              <div class="relative w-full">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="projectSearchQuery"
                  type="text"
                  placeholder="Projekte durchsuchen (Titel, Adresse, Ref-Nr...)"
                  class="w-full pl-9 pr-8 py-1.5 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0891B2]"
                />
                <button
                  v-if="projectSearchQuery"
                  @click="projectSearchQuery = ''"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap justify-between sm:justify-end">
              <!-- Status Filter Pills -->
              <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg text-xs font-semibold">
                <button
                  type="button"
                  @click="projectStatusFilter = 'all'"
                  class="px-2.5 py-1 rounded-md transition cursor-pointer"
                  :class="projectStatusFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                >
                  Alle ({{ projects.length }})
                </button>
                <button
                  type="button"
                  @click="projectStatusFilter = 'active'"
                  class="px-2.5 py-1 rounded-md transition cursor-pointer"
                  :class="projectStatusFilter === 'active' ? 'bg-white text-[#0891B2] shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                >
                  Aktiv ({{ activeProjectsCount }})
                </button>
                <button
                  type="button"
                  @click="projectStatusFilter = 'completed'"
                  class="px-2.5 py-1 rounded-md transition cursor-pointer"
                  :class="projectStatusFilter === 'completed' ? 'bg-white text-emerald-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                >
                  Erledigt ({{ completedProjectsCount }})
                </button>
              </div>

              <!-- View Switcher -->
              <div class="flex items-center gap-1 border border-slate-200 rounded-lg p-0.5 bg-slate-50">
                <button
                  type="button"
                  @click="projectViewMode = 'grid'"
                  class="p-1.5 rounded text-xs transition cursor-pointer"
                  :class="projectViewMode === 'grid' ? 'bg-white text-[#0891B2] shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                  title="Kachelansicht"
                >
                  <LayoutGrid class="w-4 h-4" />
                </button>
                <button
                  type="button"
                  @click="projectViewMode = 'list'"
                  class="p-1.5 rounded text-xs transition cursor-pointer"
                  :class="projectViewMode === 'list' ? 'bg-white text-[#0891B2] shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                  title="Listenansicht"
                >
                  <List class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredProjects.length === 0" class="text-center py-16 px-6 bg-white border border-dashed border-slate-300 rounded-lg max-w-lg mx-auto">
            <div class="w-12 h-12 mx-auto rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-3 border border-cyan-200">
              <Folder class="w-6 h-6" />
            </div>
            <h3 class="text-base font-bold text-slate-900">
              {{ projectSearchQuery || projectStatusFilter !== 'all' ? 'Keine passenden Projekte gefunden' : 'Noch keine Projekte in diesem Ordner' }}
            </h3>
            <p class="text-xs text-slate-500 mt-1 mb-5 leading-relaxed">
              {{ projectSearchQuery || projectStatusFilter !== 'all' ? 'Passe die Suchkriterien oder Filter an, um Projekte anzuzeigen.' : 'Erstelle jetzt dein erstes Projekt – z.B. aus einer unserer Vorlagen oder per Excel/CSV Import.' }}
            </p>
            <button
              v-if="!projectSearchQuery && projectStatusFilter === 'all'"
              @click="openNewProjectModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 mx-auto"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Neues Projekt anlegen</span>
            </button>
            <button
              v-else
              @click="projectSearchQuery = ''; projectStatusFilter = 'all'"
              class="taskster_button_light px-4 text-xs h-9 rounded-md flex items-center space-x-1 mx-auto"
            >
              <span>Filter zurücksetzen</span>
            </button>
          </div>

          <!-- VIEW MODE 1: GRID / KACHELN -->
          <div v-else-if="projectViewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="project in filteredProjects"
              :key="project.id"
              class="border rounded-lg p-5 transition-all duration-200 flex flex-col justify-between group shadow-2xs hover:shadow-xs"
              :class="project.status === 'completed'
                ? 'bg-emerald-500/10 border-emerald-300 ring-1 ring-emerald-400/20'
                : 'bg-white border-slate-200 hover:border-[#0891B2]'"
            >
              <div>
                <div class="flex items-start justify-between mb-3">
                  <div
                    class="w-9 h-9 rounded flex items-center justify-center border"
                    :class="project.status === 'completed' ? 'bg-emerald-100 border-emerald-300 text-emerald-700' : 'bg-cyan-50 border-cyan-200 text-[#0891B2]'"
                  >
                    <ClipboardList class="w-5 h-5" />
                  </div>
                  <div class="flex items-center space-x-1 flex-wrap gap-1">
                    <span
                      v-if="project.is_default"
                      class="text-[10px] font-semibold px-2 py-0.5 rounded bg-amber-100 border border-amber-300 text-amber-900 flex items-center space-x-1"
                      title="Standard-Projekt dieses Ordners"
                    >
                      <Star class="w-3 h-3 text-amber-600" />
                      <span>Standard</span>
                    </span>
                    <span
                      class="text-[10px] font-semibold px-2 py-0.5 rounded border flex items-center space-x-1"
                      :class="project.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-700'"
                    >
                      <Building2 v-if="project.visibility === 'company'" class="w-3 h-3 text-emerald-600 inline mr-0.5" />
                      <Lock v-else class="w-3 h-3 text-slate-500 inline mr-0.5" />
                      <span>{{ project.visibility === 'company' ? 'Unternehmen' : 'Privat' }}</span>
                    </span>
                    <span
                      class="text-[10px] font-semibold px-2 py-0.5 rounded uppercase"
                      :class="project.status === 'completed' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold' : 'bg-cyan-50 text-[#0891B2] border border-cyan-200'"
                    >
                      {{ project.status === 'completed' ? '✓ Erledigt' : project.status }}
                    </span>
                  </div>
                </div>

                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#0891B2] transition mb-2">
                  {{ project.title }}
                </h3>

                <!-- Compact Project Custom Fields Pills -->
                <div v-if="getCompactCustomData(project.custom_data).length > 0" class="flex flex-wrap gap-1.5 mb-2.5">
                  <span
                    v-for="f in getCompactCustomData(project.custom_data)"
                    :key="f.key"
                    class="text-[10px] px-2 py-0.5 rounded bg-slate-50 border border-slate-200 text-slate-700 font-medium"
                  >
                    <strong class="text-[#0891B2]">{{ f.label }}:</strong> {{ f.value }}
                  </span>
                </div>

                <!-- Structured Multi-line Custom Field Box (e.g. Info, Notiz, Beschreibung) -->
                <div v-if="getMultiLineCustomData(project.custom_data).length > 0" class="space-y-1.5 mb-3">
                  <div
                    v-for="f in getMultiLineCustomData(project.custom_data)"
                    :key="f.key"
                    class="p-2.5 rounded-lg bg-amber-50/70 border border-amber-200 text-xs"
                  >
                    <div class="text-[10px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1 mb-1">
                      <StickyNote class="w-3 h-3 text-amber-600 shrink-0" />
                      <span>{{ f.label }}</span>
                    </div>
                    <p class="text-slate-800 font-medium whitespace-pre-wrap leading-relaxed">
                      {{ f.value }}
                    </p>
                  </div>
                </div>

                <div class="grid grid-cols-4 gap-1.5 py-2.5 border-y border-slate-100 my-3 text-center">
                  <div>
                    <div class="text-[9px] text-slate-500 uppercase font-semibold">Abschnitte</div>
                    <div class="text-xs font-bold text-slate-900">{{ project.list_count }}</div>
                  </div>
                  <div>
                    <div class="text-[9px] text-slate-500 uppercase font-semibold">Aufgaben</div>
                    <div class="text-xs font-bold text-slate-900">{{ project.task_count }}</div>
                  </div>
                  <div>
                    <div class="text-[9px] text-slate-500 uppercase font-semibold">Team</div>
                    <div class="text-xs font-bold text-slate-900">{{ project.member_count }}</div>
                  </div>
                  <div>
                    <div class="text-[9px] text-[#0891B2] uppercase font-semibold">Aufwand</div>
                    <div class="text-xs font-bold" :class="(project.tracked_hours || 0) > (project.budget_hours || 0) && project.budget_hours > 0 ? 'text-rose-600' : 'text-slate-900'">
                      {{ project.tracked_hours || 0 }}h
                    </div>
                  </div>
                </div>
              </div>

              <div class="pt-2 flex items-center gap-2">
                <NuxtLink
                  :to="`/projects/${project.id}`"
                  class="taskster_button flex-1 px-4 text-xs h-8 rounded-md flex items-center justify-center space-x-1"
                >
                  <span>Projekt öffnen</span>
                  <ArrowRight class="w-3.5 h-3.5" />
                </NuxtLink>
                <button
                  v-if="canManageProject(project)"
                  type="button"
                  @click.stop="openDeleteProjectModal(project)"
                  class="p-2 rounded-md hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200 hover:border-rose-200 transition cursor-pointer"
                  :title="$t('dashboard.projekt_loeschen')"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- VIEW MODE 2: LIST / TABELLE -->
          <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-2xs">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-200">
                  <tr>
                    <th class="py-3 px-4">Projekttitel</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Abschnitte & Aufgaben</th>
                    <th class="py-3 px-4">Aufwand & Budget</th>
                    <th class="py-3 px-4">Projekt-Felder</th>
                    <th class="py-3 px-4 text-right">Aktion</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                  <tr
                    v-for="project in filteredProjects"
                    :key="project.id"
                    class="transition"
                    :class="project.status === 'completed' ? 'bg-emerald-50/50 hover:bg-emerald-100/50' : 'hover:bg-slate-50/50'"
                  >
                    <td class="py-3 px-4">
                      <NuxtLink :to="`/projects/${project.id}`" class="font-bold text-slate-900 hover:text-[#0891B2] transition text-sm">
                        {{ project.title }}
                      </NuxtLink>
                    </td>
                    <td class="py-3 px-4">
                      <div class="flex items-center space-x-1.5">
                        <span
                          class="px-2 py-0.5 rounded text-[10px] font-semibold border flex items-center space-x-1"
                          :class="project.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-700'"
                        >
                          <Building2 v-if="project.visibility === 'company'" class="w-3 h-3 text-emerald-600 inline mr-0.5" />
                          <Lock v-else class="w-3 h-3 text-slate-500 inline mr-0.5" />
                          <span>{{ project.visibility === 'company' ? 'Unternehmen' : 'Privat' }}</span>
                        </span>
                        <span
                          class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase"
                          :class="project.status === 'completed' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold' : 'bg-cyan-50 text-[#0891B2] border border-cyan-200'"
                        >
                          {{ project.status === 'completed' ? '✓ Erledigt' : project.status }}
                        </span>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <span class="text-slate-900 font-bold">{{ project.task_count }} Aufgaben</span>
                      <span class="text-slate-500"> in {{ project.list_count }} Abschnitten</span>
                    </td>
                    <td class="py-3 px-4">
                      <div class="flex items-center space-x-1.5">
                        <Clock class="w-3.5 h-3.5 text-[#0891B2]" />
                        <span class="font-bold text-slate-900">{{ project.tracked_hours || 0 }} Std.</span>
                        <span v-if="project.budget_hours" class="text-[10px] text-slate-500 font-normal">/ {{ project.budget_hours }} Std.</span>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="space-y-1">
                        <div class="flex flex-wrap gap-1">
                          <span
                            v-for="f in getCompactCustomData(project.custom_data)"
                            :key="f.key"
                            class="text-[10px] px-2 py-0.5 rounded bg-slate-50 border border-slate-200 text-slate-800 font-medium"
                          >
                            {{ f.label }}: <strong class="text-slate-900">{{ f.value }}</strong>
                          </span>
                        </div>
                        <div v-if="getMultiLineCustomData(project.custom_data).length > 0" class="pt-0.5">
                          <span
                            v-for="f in getMultiLineCustomData(project.custom_data)"
                            :key="f.key"
                            class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded bg-amber-50 border border-amber-200 text-amber-900 font-semibold"
                            :title="`${f.label}: ${f.value}`"
                          >
                            <StickyNote class="w-3 h-3 text-amber-600" />
                            <span>{{ f.label }}: {{ f.value.length > 25 ? f.value.slice(0, 25) + '...' : f.value }}</span>
                          </span>
                        </div>
                      </div>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <div class="flex items-center justify-end space-x-2">
                        <NuxtLink
                          :to="`/projects/${project.id}`"
                          class="taskster_button px-3 text-xs h-7 rounded-md inline-flex items-center space-x-1"
                        >
                          <span>Öffnen</span>
                          <ArrowRight class="w-3.5 h-3.5" />
                        </NuxtLink>
                        <button
                          v-if="canManageProject(project)"
                          type="button"
                          @click.stop="openDeleteProjectModal(project)"
                          class="p-1.5 rounded-md hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200 hover:border-rose-200 transition cursor-pointer"
                          :title="$t('dashboard.projekt_loeschen')"
                        >
                          <Trash2 class="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- TAB 2: PROJEKTJOURNAL -->
        <div v-else-if="currentFolderTab === 'journal'" class="space-y-4">
          <div class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
            <div>
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                <BookOpen class="w-4 h-4 text-[#0891B2]" />
                <span>Projektjournal in diesem Ordner</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Alle Journal- und Bautagebucheinträge über die Projekte in «{{ folder.name }}».
              </p>
            </div>
            <button
              @click="openQuickFolderJournalModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 shrink-0"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Neuen PJ-Eintrag erfassen</span>
            </button>
          </div>

          <!-- Empty State Journals -->
          <div v-if="folderJournals.length === 0" class="text-center py-16 px-6 bg-white border border-dashed border-slate-300 rounded-lg max-w-lg mx-auto">
            <div class="w-12 h-12 mx-auto rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-3 border border-cyan-200">
              <BookOpen class="w-6 h-6" />
            </div>
            <h3 class="text-base font-bold text-slate-900">Noch keine Journal-Einträge vorhanden</h3>
            <p class="text-xs text-slate-500 mt-1 mb-5 leading-relaxed">
              Erfasse Notizen, Mängel oder Baufortschritte direkt für diesen Ordner oder weise sie automatisch einem Projekt zu.
            </p>
            <button
              @click="openQuickFolderJournalModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 mx-auto"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Ersten Eintrag erfassen</span>
            </button>
          </div>

          <!-- Journal Entries List -->
          <div v-else class="space-y-3">
            <div
              v-for="entry in folderJournals"
              :key="entry.id"
              class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs hover:shadow-xs transition"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-bold text-slate-900">{{ entry.user_name || 'Benutzer' }}</span>
                  <span class="text-slate-300">•</span>
                  <span class="text-xs text-slate-500">{{ new Date(entry.entry_date || entry.created_at).toLocaleDateString('de-CH') }}</span>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase"
                    :class="entry.category === 'mangel' ? 'bg-rose-50 text-rose-700 border-rose-200' : (entry.category === 'baufortschritt' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-cyan-50 text-cyan-700 border-cyan-200')"
                  >
                    {{ entry.category || 'Notiz' }}
                  </span>
                </div>

                <div class="flex items-center gap-2">
                  <NuxtLink
                    v-if="entry.project_id"
                    :to="`/projects/${entry.project_id}`"
                    class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-[#0891B2] border border-slate-200 flex items-center gap-1 transition"
                  >
                    <Folder class="w-3 h-3" />
                    <span>Projekt: {{ getProjectTitle(entry.project_id) }}</span>
                  </NuxtLink>
                  <span v-else class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                    Ordner-Journal
                  </span>
                  <button
                    v-if="entry.can_edit || user?.id === folder.owner_id || user?.is_superadmin"
                    @click="deleteFolderJournal(entry.id)"
                    class="p-1 text-slate-400 hover:text-rose-600 rounded transition cursor-pointer"
                    title="Eintrag löschen"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>

              <h4 v-if="entry.title" class="text-sm font-bold text-slate-900 mb-1.5">
                {{ entry.title }}
              </h4>

              <p class="text-xs text-slate-700 whitespace-pre-wrap leading-relaxed">
                {{ entry.content }}
              </p>
            </div>
          </div>
        </div>

        <!-- TAB 3: BENUTZERDEFINIERTE FELDER -->
        <div v-else-if="currentFolderTab === 'fields'" class="space-y-4">
          <div class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
            <div>
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                <SlidersHorizontal class="w-4 h-4 text-[#0891B2]" />
                <span>Benutzerdefinierte Felder für diesen Ordner</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Definiere eigene Attribute für Projekte und Aufgaben. Diese stehen allen Projekten dieses Ordners zur Verfügung.
              </p>
            </div>
            <button
              @click="openCreateFieldModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 shrink-0"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Neues Feld anlegen</span>
            </button>
          </div>

          <!-- Empty State Fields -->
          <div v-if="fields.length === 0" class="text-center py-16 px-6 bg-white border border-dashed border-slate-300 rounded-lg max-w-lg mx-auto">
            <div class="w-12 h-12 mx-auto rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-3 border border-cyan-200">
              <SlidersHorizontal class="w-6 h-6" />
            </div>
            <h3 class="text-base font-bold text-slate-900">Noch keine Zusatzfelder definiert</h3>
            <p class="text-xs text-slate-500 mt-1 mb-5 leading-relaxed">
              Erstelle strukturierte Attribute wie Bauleiter, Vorgangsnummer, Fertigstellungstermin oder mehrzeilige Notizfelder.
            </p>
            <button
              @click="openCreateFieldModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 mx-auto"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Erstes Feld anlegen</span>
            </button>
          </div>

          <!-- Fields Table -->
          <div v-else class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-2xs">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-600 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4">Feld-Bezeichnung (Label)</th>
                  <th class="py-3 px-4">Bereich</th>
                  <th class="py-3 px-4">Feldtyp</th>
                  <th class="py-3 px-4">Pflichtfeld</th>
                  <th class="py-3 px-4">Details / Optionen</th>
                  <th class="py-3 px-4 text-right">Aktionen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                <tr v-for="f in fields" :key="f.id" class="hover:bg-slate-50/50 transition">
                  <td class="py-3 px-4">
                    <div class="font-bold text-slate-900">{{ getFieldLabel(f) }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ f.field_key }}</div>
                  </td>
                  <td class="py-3 px-4">
                    <span
                      class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border"
                      :class="f.entity_type === 'project' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-cyan-50 text-cyan-700 border-cyan-200'"
                    >
                      {{ f.entity_type === 'project' ? 'Projekt-Feld' : 'Aufgaben-Feld' }}
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                      {{ getFieldTypeLabel(f.field_type) }}
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="text-xs" :class="f.is_required ? 'text-rose-600 font-bold' : 'text-slate-400'">
                      {{ f.is_required ? '✓ Ja' : 'Nein' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-slate-500 text-xs">
                    <span v-if="f.options && (Array.isArray(f.options) ? f.options.length : true)" class="text-cyan-700">
                      {{ formatFieldOptions(f.options) }}
                    </span>
                    <span v-else-if="f.field_type === 'textarea'" class="italic text-slate-400">
                      Mehrzeiliges Notizfeld
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <button
                        type="button"
                        @click="openEditFieldModal(f)"
                        class="taskster_button_light px-2.5 text-xs h-7 rounded-md inline-flex items-center space-x-1 cursor-pointer"
                        title="Feld bearbeiten"
                      >
                        <Pencil class="w-3 h-3 text-[#0891B2]" />
                        <span>Bearbeiten</span>
                      </button>
                      <button
                        type="button"
                        @click="deleteFolderField(f.id)"
                        class="p-1.5 rounded-md hover:bg-rose-50 text-slate-400 hover:text-rose-600 border border-slate-200 hover:border-rose-200 transition cursor-pointer"
                        title="Feld löschen"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: KONTAKTE -->
        <div v-else-if="currentFolderTab === 'contacts'" class="space-y-4">
          <div class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
            <div>
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                <Contact class="w-4 h-4 text-[#0891B2]" />
                <span>Kontakte in diesem Ordner</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Kontakte gelten pro Projektordner und stehen in allen zugehörigen Projekten zur Verfügung.
              </p>
            </div>
            <button
              @click="openCreateContactModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 shrink-0"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Neuer Kontakt</span>
            </button>
          </div>

          <!-- Empty State Contacts -->
          <div v-if="folderContacts.length === 0" class="text-center py-16 px-6 bg-white border border-dashed border-slate-300 rounded-lg max-w-lg mx-auto">
            <div class="w-12 h-12 mx-auto rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-3 border border-cyan-200">
              <Contact class="w-6 h-6" />
            </div>
            <h3 class="text-base font-bold text-slate-900">Noch keine Kontakte in diesem Ordner</h3>
            <p class="text-xs text-slate-500 mt-1 mb-5 leading-relaxed">
              Erfasse Bauleiter, Handwerker, Ingenieure oder Eigentümer für diesen Ordner.
            </p>
            <button
              @click="openCreateContactModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1 mx-auto"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Ersten Kontakt anlegen</span>
            </button>
          </div>

          <!-- Unified Contact Cards Grid (1:1 with pages/contacts/index.vue) -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="c in folderContacts"
              :key="c.id"
              class="bg-white border border-slate-200 rounded-lg p-5 flex flex-col justify-between hover:border-slate-300 hover:shadow-xs transition"
            >
              <div>
                <!-- Card Top: Avatar, Name, Company, Function -->
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="flex items-start gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-md bg-[#0891B2] text-white flex items-center justify-center font-bold text-sm shrink-0">
                      {{ getContactInitials(c) }}
                    </div>
                    <div class="min-w-0">
                      <h3 class="text-sm font-semibold text-slate-900 truncate leading-tight">
                        {{ formatContactFullName(c) }}
                      </h3>
                      <p v-if="c.company_name" class="text-xs font-medium text-cyan-800 truncate mt-0.5 flex items-center gap-1">
                        <Building2 class="w-3 h-3 text-cyan-600 shrink-0" />
                        <span>{{ c.company_name }}</span>
                      </p>
                      <p v-if="c.role_function" class="text-xs text-slate-600 truncate mt-0.5 flex items-center gap-1">
                        <HardHat class="w-3 h-3 text-slate-400 shrink-0" />
                        <span>{{ c.role_function }}</span>
                      </p>
                    </div>
                  </div>

                  <!-- Scope Badge -->
                  <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                    Ordner-Kontakt
                  </span>
                </div>

                <!-- Group & Tags Badges -->
                <div v-if="c.category_group || (c.tags && c.tags.length > 0)" class="flex flex-wrap items-center gap-1.5 mb-3">
                  <span v-if="c.category_group" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ c.category_group }}
                  </span>
                  <span
                    v-for="(tag, idx) in c.tags"
                    :key="idx"
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-800 border border-cyan-200"
                  >
                    #{{ tag }}
                  </span>
                </div>

                <!-- Contact Details (Phone, Mobile, Email, Address) -->
                <div class="space-y-1.5 text-xs text-slate-700 bg-slate-50 p-3 rounded-md border border-slate-200 mb-3">
                  <div v-if="c.mobile" class="flex items-center gap-2">
                    <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <a :href="`tel:${c.mobile}`" class="font-medium text-[#0891B2] hover:underline truncate">
                      {{ c.mobile }}
                    </a>
                    <a :href="`https://wa.me/${cleanPhoneForWhatsApp(c.mobile)}`" target="_blank" rel="noopener" class="text-[10px] text-emerald-700 hover:text-emerald-900 font-bold ml-auto" title="WhatsApp Chat öffnen">
                      WhatsApp
                    </a>
                  </div>

                  <div v-if="c.phone && !c.mobile" class="flex items-center gap-2">
                    <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <a :href="`tel:${c.phone}`" class="font-medium text-[#0891B2] hover:underline truncate">
                      {{ c.phone }}
                    </a>
                  </div>

                  <div v-if="c.email" class="flex items-center gap-2">
                    <Mail class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <a :href="`mailto:${c.email}`" class="font-medium text-slate-800 hover:text-[#0891B2] hover:underline truncate">
                      {{ c.email }}
                    </a>
                  </div>

                  <div v-if="c.address || c.city" class="flex items-center gap-2">
                    <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <a
                      :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent([c.address, c.zip_code, c.city].filter(Boolean).join(', '))}`"
                      target="_blank"
                      rel="noopener"
                      class="text-slate-600 hover:text-[#0891B2] hover:underline truncate"
                    >
                      {{ [c.address, [c.zip_code, c.city].filter(Boolean).join(' ')].filter(Boolean).join(', ') }}
                    </a>
                  </div>
                </div>

                <!-- Notes -->
                <p v-if="c.notes" class="text-xs text-slate-500 italic mb-3 line-clamp-2">
                  "{{ c.notes }}"
                </p>
              </div>

              <!-- Footer: Actions -->
              <div class="flex items-center justify-between pt-3 border-t border-slate-200 text-xs">
                <button
                  @click="exportContactVCard(c)"
                  type="button"
                  class="text-xs font-semibold text-slate-600 hover:text-[#0891B2] flex items-center gap-1 py-1 px-2 rounded hover:bg-cyan-50 cursor-pointer"
                  title="vCard herunterladen"
                >
                  <Download class="w-3.5 h-3.5" />
                  <span>vCard</span>
                </button>
                <div class="flex items-center gap-1">
                  <button
                    @click="openEditContactModal(c)"
                    type="button"
                    class="p-1.5 text-slate-400 hover:text-[#0891B2] hover:bg-slate-100 rounded transition cursor-pointer"
                    title="Bearbeiten"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="deleteFolderContact(c)"
                    type="button"
                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                    title="Löschen"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- Modal: New Project (with Templates support) -->
    <div v-if="showNewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col my-8">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-200 flex items-start justify-between bg-slate-50">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-xl">📁</span>
              <h3 class="text-lg font-black text-slate-900">Neues Projekt erstellen</h3>
            </div>
            <p class="text-xs text-slate-500 mt-1">
              Erstelle ein Projekt im Ordner <span class="text-slate-800 font-bold">«{{ folder?.name }}»</span>
            </p>
          </div>
          <button
            type="button"
            @click="closeNewProjectModal"
            class="text-slate-400 hover:text-slate-600 text-lg p-1 rounded-lg hover:bg-slate-200 transition"
          >
            ✕
          </button>
        </div>

        <div v-if="projectModalError" class="mx-6 mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center justify-between">
          <span>{{ projectModalError }}</span>
          <button type="button" @click="projectModalError = ''" class="text-rose-500 hover:text-rose-700 font-bold ml-2">✕</button>
        </div>

        <form @submit.prevent="createProject" class="p-6 space-y-6">
          <!-- Creation Mode Selector (Template vs Import vs Blank) -->
          <div class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 rounded-2xl">
            <button
              type="button"
              @click="projectCreationMode = 'template'"
              class="flex items-center justify-center space-x-2 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-center"
              :class="projectCreationMode === 'template' ? 'bg-white text-[#00A3C4] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>📋</span>
              <span class="truncate">Aus Vorlage (Empfohlen)</span>
            </button>
            <button
              type="button"
              @click="projectCreationMode = 'import'"
              class="flex items-center justify-center space-x-2 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-center"
              :class="projectCreationMode === 'import' ? 'bg-white text-[#00A3C4] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>📊</span>
              <span class="truncate">Excel / CSV Import</span>
            </button>
            <button
              type="button"
              @click="projectCreationMode = 'blank'; selectedTemplateId = null"
              class="flex items-center justify-center space-x-2 py-2.5 px-3 rounded-xl text-xs font-bold transition cursor-pointer text-center"
              :class="projectCreationMode === 'blank' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>📝</span>
              <span class="truncate">Leeres Projekt (Blanko)</span>
            </button>
          </div>

          <!-- SECTION A: TEMPLATE BROWSER -->
          <div v-if="projectCreationMode === 'template'" class="space-y-4">
            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
              <!-- Category Pills -->
              <div class="flex items-center space-x-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                <button
                  type="button"
                  @click="templateFilterCategory = 'all'"
                  class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap cursor-pointer"
                  :class="templateFilterCategory === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:text-slate-900'"
                >
                  Alle Vorlagen ({{ templates.length }})
                </button>
                <button
                  type="button"
                  @click="templateFilterCategory = 'job'"
                  class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                  :class="templateFilterCategory === 'job' ? 'bg-[#00A3C4] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:text-cyan-700'"
                >
                  <span>💼</span>
                  <span>Job & Gewerbe ({{ templates.filter(t => t.category === 'job').length }})</span>
                </button>
                <button
                  type="button"
                  @click="templateFilterCategory = 'private'"
                  class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                  :class="templateFilterCategory === 'private' ? 'bg-purple-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:text-purple-700'"
                >
                  <span>🏡</span>
                  <span>Privat & Familie ({{ templates.filter(t => t.category === 'private').length }})</span>
                </button>
              </div>

              <!-- Search -->
              <div class="w-full sm:w-64">
                <input
                  v-model="templateSearchQuery"
                  type="text"
                  placeholder="🔍 Vorlage suchen..."
                  class="w-full px-3.5 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
                />
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loadingTemplates" class="py-12 text-center text-xs text-slate-400">
              Vorlagen werden geladen...
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredTemplates.length === 0" class="py-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-500">
              Keine passenden Vorlagen gefunden.
            </div>

            <!-- Templates Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-1">
              <div
                v-for="tmpl in filteredTemplates"
                :key="tmpl.id"
                @click="selectTemplate(tmpl)"
                class="p-4 rounded-2xl border transition cursor-pointer flex flex-col justify-between text-left"
                :class="selectedTemplateId === tmpl.id
                  ? 'bg-cyan-50/70 border-[#00A3C4] ring-2 ring-[#00A3C4]/30'
                  : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
              >
                <div>
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2.5">
                      <span class="text-2xl">{{ getTemplateIcon(tmpl.icon, tmpl.category) }}</span>
                      <div>
                        <h4 class="text-xs font-bold text-slate-900 leading-snug">{{ tmpl.name_key ? $t(tmpl.name_key) : tmpl.name }}</h4>
                        <span v-if="tmpl.subcategory" class="text-[10px] text-slate-500 font-medium">{{ tmpl.subcategory }}</span>
                      </div>
                    </div>
                    <span
                      class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider shrink-0 ml-2"
                      :class="tmpl.category === 'job' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-purple-50 text-purple-700 border border-purple-200'"
                    >
                      {{ tmpl.category === 'job' ? 'Gewerbe' : 'Privat' }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-600 line-clamp-2 mb-3 leading-relaxed">
                    {{ tmpl.description_key ? $t(tmpl.description_key) : tmpl.description }}
                  </p>
                </div>

                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2.5 border-t border-slate-100 text-[10px]">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold">
                    📂 {{ (tmpl.lists || []).length }} Abschnitte
                  </span>
                  <span v-if="(tmpl.fields || []).length > 0" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold">
                    🏷️ {{ (tmpl.fields || []).length }} Zusatzfelder
                  </span>
                  <span
                    v-if="(tmpl.fields || []).some((f: any) => f.logic_rules && f.logic_rules.depends_on_field)"
                    class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-semibold"
                  >
                    ⚡ Mit Bedingungs-Logik
                  </span>
                </div>
              </div>
            </div>

            <!-- Preview of Selected Template Features & Customization -->
            <div v-if="selectedTemplate" class="p-4 rounded-2xl bg-cyan-50/50 border border-cyan-200 space-y-4">
              <div class="flex items-center justify-between border-b border-cyan-200/60 pb-2.5">
                <div class="flex items-center space-x-2">
                  <span class="text-cyan-700 font-bold text-sm">✓ Gewählte Vorlage:</span>
                  <span class="text-slate-900 font-black text-sm">{{ selectedTemplate.name_key ? $t(selectedTemplate.name_key) : selectedTemplate.name }}</span>
                </div>
                <span class="text-[11px] font-medium text-slate-500">Konfiguration anpassen</span>
              </div>

              <!-- Included Lists with Interactive Adjustments -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-800">
                    Projektphasen / Abschnitte ({{ selectedTemplateLists.length }}):
                  </span>
                  <span class="text-[10px] text-slate-500">Du kannst Phasen vor der Erstellung anpassen oder entfernen</span>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 p-2.5 bg-white rounded-xl border border-slate-200 shadow-2xs">
                  <div
                    v-for="(listName, idx) in selectedTemplateLists"
                    :key="idx"
                    class="flex items-center space-x-1.5 px-2.5 py-1 rounded-lg text-xs bg-cyan-50 border border-cyan-200 text-cyan-900 font-bold shadow-2xs"
                  >
                    <span class="text-cyan-600 text-[10px] font-mono">{{ idx + 1 }}.</span>
                    <span>{{ listName.startsWith("sections.") ? $t(listName) : listName }}</span>
                    <button
                      v-if="selectedTemplateLists.length > 1"
                      type="button"
                      @click="removeTemplatePhase(idx)"
                      class="text-cyan-600 hover:text-rose-600 ml-1 font-bold text-xs"
                      title="Phase entfernen"
                    >
                      ✕
                    </button>
                  </div>

                  <!-- Inline Add Phase -->
                  <div class="flex items-center space-x-1 pl-1">
                    <input
                      v-model="newTemplatePhaseInput"
                      @keydown.enter.prevent="addTemplatePhase"
                      type="text"
                      placeholder="+ Phase hinzufügen..."
                      class="px-2.5 py-1 text-xs bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:outline-none focus:border-[#00A3C4] w-36"
                    />
                    <button
                      v-if="newTemplatePhaseInput.trim()"
                      type="button"
                      @click="addTemplatePhase"
                      class="px-2 py-1 bg-[#00A3C4] text-white text-[11px] font-bold rounded-lg hover:opacity-90 cursor-pointer"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>

              <!-- Included Custom Fields -->
              <div v-if="(selectedTemplate.fields || []).length > 0" class="space-y-2">
                <span class="text-xs font-bold text-slate-800 block">Zusatzfelder dieser Vorlage:</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div
                    v-for="cf in selectedTemplate.fields"
                    :key="cf.field_key"
                    class="p-2.5 rounded-xl bg-white border border-slate-200 text-xs shadow-2xs"
                  >
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-slate-800">{{ cf.label_key ? $t(cf.label_key) : cf.label }}</span>
                      <span class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold">
                        {{ getFieldTypeLabel(cf.field_type) }}
                      </span>
                    </div>
                    <!-- Conditional Logic Badge -->
                    <div v-if="cf.logic_rules && cf.logic_rules.depends_on_value" class="mt-1 text-[10px] text-amber-800 flex items-center space-x-1 font-medium bg-amber-50/80 px-2 py-0.5 rounded border border-amber-200/60">
                      <span>⚡</span>
                      <span>Sichtbar bei: <strong>{{ cf.logic_rules.depends_on_value }}</strong></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION IMPORT: EXCEL / CSV IMPORT -->
          <div v-else-if="projectCreationMode === 'import'" class="space-y-5">
            <!-- Step 1: Upload or sample download -->
            <div
              class="p-7 border-2 border-dashed rounded-3xl transition text-center cursor-pointer flex flex-col items-center justify-center"
              :class="isImportDragging ? 'border-[#00A3C4] bg-cyan-50/50' : 'border-slate-300 hover:border-[#00A3C4] bg-slate-50 hover:bg-cyan-50/20'"
              @click="importFileInput?.click()"
              @dragover.prevent="isImportDragging = true"
              @dragleave.prevent="isImportDragging = false"
              @drop.prevent="onImportFileDrop"
            >
              <input
                ref="importFileInput"
                type="file"
                accept=".xlsx,.xls,.csv,.tsv"
                class="hidden"
                @change="onImportFileSelected"
              />
              <span class="text-4xl mb-2">📊</span>
              <p class="text-sm font-bold text-slate-800">
                Excel (.xlsx, .xls) oder CSV / TSV Datei auswählen oder hier ablegen
              </p>
              <p class="text-xs text-slate-500 mt-1 max-w-md">
                Importiert mehrere Projekte auf einen Klick direkt in den Ordner «{{ folder?.name }}».
              </p>

              <div class="mt-4 flex items-center space-x-3" @click.stop>
                <button
                  type="button"
                  @click="downloadSampleExcel"
                  class="taskster_button_light px-4 text-xs h-[36px] rounded-lg shadow-xs flex items-center space-x-1.5"
                >
                  <span>📥</span>
                  <span>Muster-Excel herunterladen</span>
                </button>
              </div>
            </div>

            <div v-if="importError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-semibold">
              {{ importError }}
            </div>

            <!-- Step 2: Mapping & Preview -->
            <div v-if="importHeaders.length > 0" class="space-y-4 pt-2 border-t border-slate-100">
              <div class="p-3 bg-cyan-50/80 border border-cyan-200 rounded-2xl flex items-center justify-between text-xs">
                <span class="text-cyan-950 font-bold">
                  📄 Datei erkannt: <strong>{{ importFileName }}</strong> ({{ importParsedRows.length }} Projekt(e) gefunden, {{ importHeaders.length }} Spalten)
                </span>
                <span class="text-cyan-800 font-semibold">
                  Ordner: {{ folder?.name }}
                </span>
              </div>

              <!-- Column Mapping -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-black text-slate-800 uppercase tracking-wider">
                    Spaltenzuweisung (Mapping):
                  </label>
                  <span class="text-[11px] text-slate-500 font-medium">Projekttitel ist Pflichtfeld</span>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
                  <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 uppercase font-bold text-[10px] border-b border-slate-200">
                      <tr>
                        <th class="py-2.5 px-4">Spalte in Excel / CSV</th>
                        <th class="py-2.5 px-4">Beispielwert (Zeile 1)</th>
                        <th class="py-2.5 px-4">Zuweisung an Taskster Projekt-Feld</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                      <tr v-for="(header, hIdx) in importHeaders" :key="hIdx" class="hover:bg-slate-50/80">
                        <td class="py-2.5 px-4 font-bold text-slate-900">{{ header }}</td>
                        <td class="py-2.5 px-4 text-slate-500 font-mono text-[11px] truncate max-w-xs">
                          {{ importParsedRows[0]?.[hIdx] || '-' }}
                        </td>
                        <td class="py-2.5 px-4">
                          <select
                            v-model="importColumnMapping[hIdx]"
                            class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-[#0891B2]"
                            :class="importColumnMapping[hIdx] === 'title' ? 'border-[#0891B2] bg-cyan-50/50 text-cyan-950 font-bold' : (importColumnMapping[hIdx] === 'action:create_task' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-950 font-bold' : (importColumnMapping[hIdx]?.startsWith('custom:') ? 'border-amber-400 bg-amber-50/40 text-amber-900 font-semibold' : ''))"
                          >
                            <option value="">-- Nicht importieren --</option>
                            <optgroup label="Aktionen & Aufgaben">
                              <option value="action:create_task">✅ [Aktion] Neue Aufgabe erstellen</option>
                            </optgroup>
                            <optgroup label="Standard Projekt-Felder">
                              <option value="title">📌 Projekttitel (Pflicht)</option>
                              <option value="status">🔄 Status (active/archived/completed)</option>
                              <option value="visibility">🔒 Sichtbarkeit (private/company)</option>
                              <option value="currency">💰 Währung (CHF, EUR, USD)</option>
                              <option value="budget_hours">⏱️ Budget Stunden</option>
                              <option value="budget_amount">💵 Budget Betrag</option>
                            </optgroup>

                            <!-- Bestehende benutzerdefinierte Felder -->
                            <optgroup v-if="fields.length > 0" label="Bestehende Zusatzfelder dieses Ordners">
                              <option
                                v-for="f in fields"
                                :key="f.id"
                                :value="'custom:' + f.field_key"
                              >
                                ⚙️ {{ f.label }} ({{ f.field_key }}) {{ f.entity_type === 'project' ? '[Projekt]' : '' }}
                              </option>
                            </optgroup>

                            <!-- Als neues Zusatzfeld aus Spalte anlegen -->
                            <optgroup label="✨ Als neues Zusatzfeld anlegen">
                              <option :value="'custom:' + getHeaderKey(header)">
                                ✨ Neues Feld: "{{ header }}" ({{ getHeaderKey(header) }})
                              </option>
                            </optgroup>

                            <!-- Häufige Vorlagen-Felder -->
                            <optgroup label="📋 Vorlagen-Zusatzfelder" v-if="getAvailableTemplateFields(header).length > 0">
                              <option
                                v-for="tf in getAvailableTemplateFields(header)"
                                :key="tf.key"
                                :value="'custom:' + tf.key"
                              >
                                {{ tf.icon }} {{ tf.label_key ? $t(tf.label_key) : tf.label }} ({{ tf.key }})
                              </option>
                            </optgroup>
                          </select>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Preview Table (first 3 rows) -->
              <div class="space-y-1.5">
                <span class="text-[11px] font-bold text-slate-600 block uppercase tracking-wider">
                  Vorschau der ersten Datenzeilen:
                </span>
                <div class="border border-slate-200 rounded-xl overflow-x-auto max-h-36 bg-slate-50 p-2 text-[11px] font-mono">
                  <div
                    v-for="(row, rIdx) in importParsedRows.slice(0, 3)"
                    :key="rIdx"
                    class="py-1 border-b border-slate-200 last:border-0 flex gap-2"
                  >
                    <span class="text-slate-400 font-bold">#{{ rIdx + 1 }}:</span>
                    <span class="text-slate-700 truncate">{{ row.join(' | ') }}</span>
                  </div>
                </div>
              </div>

              <!-- Workflow-Abschnitte (Phasen) für importierte Projekte -->
              <div class="space-y-2 p-3.5 bg-cyan-50/60 border border-cyan-200 rounded-2xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                  <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <span>📋</span>
                    <span>Workflow-Abschnitte (Phasen) für importierte Projekte:</span>
                  </label>
                  <button
                    type="button"
                    @click="resetImportSectionsToDefault"
                    class="text-[11px] font-semibold text-[#0891B2] hover:underline cursor-pointer text-left"
                  >
                    ↺ Auf Standard (Offen, In Arbeit, Abgeschlossen)
                  </button>
                </div>
                <p class="text-[11px] text-slate-500">
                  Diese Phasen werden für alle importierten Projekte erstellt und als Standard-Vorlage für diesen Ordner gespeichert.
                </p>

                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                  <div
                    v-for="(sec, sIdx) in importWorkflowSections"
                    :key="sIdx"
                    class="flex items-center space-x-1.5 px-2.5 py-1 rounded-lg text-xs bg-white border border-slate-300 text-slate-800 font-bold shadow-2xs"
                  >
                    <span class="text-[#0891B2] text-[10px] font-mono">{{ sIdx + 1 }}.</span>
                    <input
                      v-model="importWorkflowSections[sIdx]"
                      type="text"
                      class="bg-transparent border-0 focus:ring-0 p-0 text-xs font-bold text-slate-800 w-24 sm:w-28 focus:outline-none"
                    />
                    <button
                      v-if="importWorkflowSections.length > 1"
                      type="button"
                      @click="removeImportSection(sIdx)"
                      class="text-slate-400 hover:text-rose-600 ml-1 font-bold text-xs cursor-pointer"
                      title="Abschnitt entfernen"
                    >
                      ✕
                    </button>
                  </div>

                  <!-- Inline Add Section -->
                  <div class="flex items-center space-x-1 pl-1">
                    <input
                      v-model="newImportSectionInput"
                      @keydown.enter.prevent="addImportSection"
                      type="text"
                      placeholder="+ Neuer Abschnitt..."
                      class="px-2.5 py-1 text-xs bg-white border border-slate-300 rounded-lg focus:outline-none focus:border-[#0891B2] w-36"
                    />
                    <button
                      v-if="newImportSectionInput.trim()"
                      type="button"
                      @click="addImportSection"
                      class="px-2 py-1 bg-[#0891B2] text-white text-[11px] font-bold rounded-lg hover:opacity-90 cursor-pointer"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION B: PROJECT TITLE & GENERAL SETTINGS (Nur bei Vorlage oder Blanko-Projekt) -->
          <div v-if="projectCreationMode !== 'import'" class="space-y-4 pt-2 border-t border-slate-100">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Projekttitel / Name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="newProjectTitle"
                type="text"
                required
                placeholder="z.B. FTTH Ausbau Bern Süd oder Wohnzimmer Renovation"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
              />
              <p class="text-[11px] text-slate-500 mt-1">
                Gib dem Projekt eine aussagekräftige Bezeichnung.
              </p>
            </div>

            <!-- Sichtbarkeit des Projekts im Unternehmen (Default: Privat) -->
            <div v-if="user?.company_id" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
              <label class="block text-xs font-bold text-slate-800">Sichtbarkeit des Projekts</label>
              <div class="grid grid-cols-2 gap-2">
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition text-xs font-semibold"
                  :class="newProjectVisibility === 'private' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
                >
                  <input type="radio" value="private" v-model="newProjectVisibility" class="sr-only" />
                  <span>🔒 Privat (Standard)</span>
                </label>
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition text-xs font-semibold"
                  :class="newProjectVisibility === 'company' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
                >
                  <input type="radio" value="company" v-model="newProjectVisibility" class="sr-only" />
                  <span>🏢 Unternehmen</span>
                </label>
              </div>
              <p class="text-[11px] text-slate-500">
                {{ newProjectVisibility === 'private' ? 'Privates Projekt. Nur für dich und explizit hinzugefügte Mitglieder sichtbar (auch Admins sehen dieses Projekt nicht).' : 'Für alle Mitglieder im Unternehmen sichtbar.' }}
              </p>
            </div>

            <!-- If Blanko Mode and folder has existing project fields -->
            <div v-if="projectCreationMode === 'blank' && projectFields.length > 0" class="space-y-3 pt-3 border-t border-slate-100">
              <h4 class="text-xs font-bold text-cyan-700 uppercase tracking-wider">
                Projekt-Felder dieses Ordners
              </h4>
              <div v-for="f in projectFields" :key="f.id">
                <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label_key ? $t(f.label_key) : f.label }}</label>

                <!-- Select -->
                <select
                  v-if="f.field_type === 'select'"
                  v-model="newProjectCustomData[f.field_key]"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                >
                  <option value="">-- Nicht ausgewählt --</option>
                  <option
                    v-for="opt in f.options"
                    :key="typeof opt === 'object' ? opt.value : opt"
                    :value="typeof opt === 'object' ? opt.value : opt"
                  >
                    {{ typeof opt === 'object' ? (opt.label_key ? $t(opt.label_key) : (opt.label || opt.value)) : ($te('fields.options.' + opt) ? $t('fields.options.' + opt) : opt) }}
                  </option>
                </select>

                <!-- Textarea (Längerer Text) -->
                <textarea
                  v-else-if="f.field_type === 'textarea'"
                  v-model="newProjectCustomData[f.field_key]"
                  rows="3"
                  placeholder="Details, Notizen oder Beschreibung..."
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 resize-y"
                ></textarea>

                <!-- Checkbox -->
                <div v-else-if="f.field_type === 'checkbox'" class="pt-1">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="newProjectCustomData[f.field_key]"
                      class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0 cursor-pointer"
                    />
                    <span class="text-xs font-medium text-slate-700">
                      {{ newProjectCustomData[f.field_key] ? '✓ Ja / Aktiv' : 'Nein / Inaktiv' }}
                    </span>
                  </label>
                </div>

                <!-- Date -->
                <input
                  v-else-if="f.field_type === 'date'"
                  v-model="newProjectCustomData[f.field_key]"
                  type="date"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Number -->
                <input
                  v-else-if="f.field_type === 'number'"
                  v-model="newProjectCustomData[f.field_key]"
                  type="number"
                  step="any"
                  placeholder="0.00"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- URL -->
                <input
                  v-else-if="f.field_type === 'url'"
                  v-model="newProjectCustomData[f.field_key]"
                  type="url"
                  placeholder="https://..."
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Email -->
                <input
                  v-else-if="f.field_type === 'email'"
                  v-model="newProjectCustomData[f.field_key]"
                  type="email"
                  placeholder="kontakt@firma.ch"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Phone -->
                <input
                  v-else-if="f.field_type === 'phone'"
                  v-model="newProjectCustomData[f.field_key]"
                  type="tel"
                  placeholder="+41 79 123 45 67"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Default Text -->
                <input
                  v-else
                  v-model="newProjectCustomData[f.field_key]"
                  type="text"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />
              </div>
            </div>
          </div>

          <!-- Bottom Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="closeNewProjectModal"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="creatingProject || (projectCreationMode === 'template' && !selectedTemplateId) || (projectCreationMode === 'import' && (!importParsedRows.length || !Object.values(importColumnMapping).includes('title'))) || (projectCreationMode !== 'import' && !newProjectTitle.trim())"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ creatingProject ? 'Wird verarbeitet...' : (projectCreationMode === 'template' ? 'Projekt aus Vorlage erstellen' : (projectCreationMode === 'import' ? `${importParsedRows.length} Projekt(e) in diesen Ordner importieren` : 'Projekt erstellen')) }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Folder (Owner only) -->
    <div v-if="showEditFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[92vh] my-auto">
        <!-- Sticky Header -->
        <div class="p-6 pb-4 border-b border-slate-100 flex items-start justify-between shrink-0 bg-white">
          <div>
            <h3 class="text-lg font-black text-slate-900">Projektordner anpassen</h3>
            <p class="text-xs text-slate-500 mt-1">
              Passe den Namen, das Icon, die Vorlage und die Phasen dieses Projektordners an.
            </p>
          </div>
          <button @click="showEditFolderModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer transition">✕</button>
        </div>

        <form @submit.prevent="updateFolder" class="flex flex-col flex-1 overflow-hidden min-h-0">
          <!-- Scrollable Body -->
          <div class="p-6 overflow-y-auto flex-1 space-y-4">
            <div v-if="editFolderError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
              {{ editFolderError }}
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Name des Projektordners</label>
              <input
                v-model="editFolderName"
                type="text"
                required
                placeholder="z.B. Peters Privates Renovationsprojekt"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Icon aus Liste auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-40 overflow-y-auto p-2.5 bg-slate-50 rounded-2xl border border-slate-200">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="editFolderIcon = item.icon"
                class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition border cursor-pointer"
                :class="editFolderIcon === item.icon ? 'bg-cyan-50 border-cyan-500 ring-2 ring-cyan-500/40 scale-105' : 'border-slate-200 bg-white hover:bg-slate-100'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-500 mt-1 font-medium">Ausgewähltes Icon: <span class="text-slate-900 text-base font-bold mr-1">{{ editFolderIcon }}</span></p>
          </div>

          <!-- Standard-Projektvorlage & Abschnitte für den gesamten Ordner -->
          <div class="p-3.5 bg-cyan-50/50 border border-cyan-200/80 rounded-2xl space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-900 flex items-center gap-1.5">
                <BookOpen class="w-4 h-4 text-[#0891B2]" />
                <span>Projektvorlage für diesen Ordner</span>
              </label>
              <span v-if="editFolderTemplateId" class="text-[10px] font-bold text-[#0891B2] px-2 py-0.5 bg-white rounded-full border border-cyan-200 shadow-2xs">
                Aktiv
              </span>
            </div>
            <select
              v-model="editFolderTemplateId"
              @change="onEditFolderTemplateChange"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-[#0891B2] font-medium cursor-pointer"
            >
              <option value="">Keine Vorlage (Freie / Manuelle Abschnitte)</option>
              <option v-for="tpl in CONSTRUCTION_TEMPLATES" :key="tpl.id" :value="tpl.id">
                {{ tpl.name }} — {{ tpl.subcategory }}
              </option>
            </select>
            <div v-if="selectedEditTemplate" class="text-[11px] text-slate-600 bg-white p-3 rounded-xl border border-cyan-100 leading-relaxed space-y-1 shadow-2xs">
              <p><strong>Info:</strong> {{ selectedEditTemplate.description }}</p>
            </div>
            <p v-else class="text-[11px] text-slate-500">
              Wähle eine Branchen-Vorlage (z. B. Hochbau, Tiefbau, FTTH, Gebäudeautomation) oder passe die Abschnitte unten manuell an.
            </p>

            <!-- Abschnitte / Phasen anpassen, ändern, hinzufügen -->
            <div class="pt-2 border-t border-cyan-100/80 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1">
                  <span>Workflow-Phasen / Abschnitte ({{ editFolderSections.length }})</span>
                </span>
                <button
                  type="button"
                  v-if="selectedEditTemplate"
                  @click="resetEditFolderSectionsFromTemplate"
                  class="text-[11px] font-semibold text-[#0891B2] hover:underline cursor-pointer"
                >
                  ↺ Aus Vorlage neu laden
                </button>
                <button
                  type="button"
                  v-else
                  @click="resetEditFolderSectionsToStandard"
                  class="text-[11px] font-semibold text-[#0891B2] hover:underline cursor-pointer"
                >
                  ↺ Standard-Phasen
                </button>
              </div>

              <!-- List of editable sections -->
              <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                <div
                  v-for="(sec, sIdx) in editFolderSections"
                  :key="sIdx"
                  class="flex items-center gap-2 p-2 bg-white rounded-xl border border-slate-200 hover:border-slate-300 shadow-2xs transition"
                >
                  <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px] flex items-center justify-center shrink-0">
                    {{ sIdx + 1 }}
                  </span>
                  
                  <!-- Title input -->
                  <input
                    v-model="sec.title"
                    type="text"
                    required
                    placeholder="Phasenname"
                    class="flex-1 px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
                  />

                  <!-- Ziel für Erledigt Toggle -->
                  <button
                    type="button"
                    @click="toggleEditFolderSectionTarget(sIdx)"
                    class="px-2 py-1 rounded-lg text-[10px] font-bold border transition flex items-center gap-1 cursor-pointer shrink-0"
                    :class="sec.is_completed_target == 1
                      ? 'bg-emerald-50 text-emerald-800 border-emerald-300 ring-1 ring-emerald-400/20'
                      : 'bg-slate-50 hover:bg-slate-100 text-slate-500 border-slate-200'"
                    :title="sec.is_completed_target == 1 ? 'Aktueller Ziel-Abschnitt für erledigte Aufgaben' : 'Als Ziel-Abschnitt für erledigte Aufgaben festlegen'"
                  >
                    <span>{{ sec.is_completed_target == 1 ? '✓ Ziel Erledigt' : '○ Ziel Erledigt' }}</span>
                  </button>

                  <!-- Reorder: Up & Down & Remove -->
                  <div class="flex items-center space-x-0.5 shrink-0">
                    <button
                      type="button"
                      @click="moveEditFolderSectionUp(sIdx)"
                      :disabled="sIdx === 0"
                      class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-700 disabled:opacity-20 disabled:cursor-not-allowed transition text-xs"
                      title="Nach oben verschieben"
                    >
                      ▲
                    </button>
                    <button
                      type="button"
                      @click="moveEditFolderSectionDown(sIdx)"
                      :disabled="sIdx === editFolderSections.length - 1"
                      class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-700 disabled:opacity-20 disabled:cursor-not-allowed transition text-xs"
                      title="Nach unten verschieben"
                    >
                      ▼
                    </button>
                    <button
                      type="button"
                      @click="removeEditFolderSection(sIdx)"
                      :disabled="editFolderSections.length <= 1"
                      class="p-1 rounded hover:bg-rose-50 text-rose-500 hover:text-rose-700 disabled:opacity-20 disabled:cursor-not-allowed transition text-xs ml-0.5"
                      title="Abschnitt entfernen"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </div>

              <!-- Inline Add Section Input -->
              <div class="flex items-center gap-2 pt-1">
                <input
                  v-model="newFolderSectionTitle"
                  @keydown.enter.prevent="addEditFolderSection"
                  type="text"
                  placeholder="+ Neuer Abschnitt (z.B. Zwischenprüfung, Abnahme)..."
                  class="flex-1 px-3 py-1.5 text-xs bg-white border border-slate-300 rounded-xl focus:outline-none focus:border-[#0891B2] font-medium"
                />
                <button
                  type="button"
                  @click="addEditFolderSection"
                  :disabled="!newFolderSectionTitle.trim()"
                  class="taskster_button px-3 text-xs h-[34px] rounded-lg shrink-0 disabled:opacity-40"
                >
                  + Hinzufügen
                </button>
              </div>
            </div>
          </div>

          <!-- Sichtbarkeit im Unternehmen (Default: Privat) -->
          <div v-if="user?.company_id || folder?.company_id" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
            <label class="block text-xs font-bold text-slate-800">Sichtbarkeit des Ordners</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer transition text-xs font-semibold"
                :class="editFolderVisibility === 'private' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
              >
                <input type="radio" value="private" v-model="editFolderVisibility" class="sr-only" />
                <span>🔒 Privat (Standard)</span>
              </label>
              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer transition text-xs font-semibold"
                :class="editFolderVisibility === 'company' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
              >
                <input type="radio" value="company" v-model="editFolderVisibility" class="sr-only" />
                <span>🏢 Unternehmen</span>
              </label>
            </div>
            <p class="text-[11px] text-slate-500">
              {{ editFolderVisibility === 'private' ? 'Privater Ordner. Nur für dich und gezielt eingeladene Mitglieder sichtbar.' : `Für alle Mitglieder des Unternehmens (${folder?.company_name || user?.company_name || 'Firma'}) sichtbar.` }}
            </p>
          </div>
          <div v-else class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs text-amber-900">
            <span class="font-bold">💡 Einzelnutzer-Konto:</span> Dieser Ordner ist standardmäßig privat. Nutze den Button <strong>"👥 Ordner teilen"</strong>, um Kollegen oder Partner gezielt per E-Mail einzuladen.
          </div>

          <!-- Standard-Projekt festlegen (1 Projekt muss Standard sein) -->
          <div v-if="projects.length > 0" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
            <label class="block text-xs font-bold text-slate-800">⭐ Standard-Projekt festlegen (1 Projekt muss Standard sein)</label>
            <select
              v-model="editFolderDefaultProjectId"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-[#00A3C4]"
            >
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.title }} {{ p.is_default ? '(Aktuell Standard)' : '' }}
              </option>
            </select>
            <p class="text-[11px] text-slate-500">
              Ein Projekt innerhalb des Ordners muss als Standard definiert sein.
            </p>
          </div>

          <!-- Zusatzfelder verwalten -->
          <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <span>⚙️</span>
                <span>Zusatzfelder in diesem Ordner ({{ fields.length }})</span>
              </span>
              <button
                type="button"
                @click="showEditFolderModal = false; navigateTo(`/projects/${projects[0]?.id}`)"
                v-if="projects.length > 0"
                class="text-[#00A3C4] hover:underline text-[11px] font-bold"
              >
                + Im Projekt verwalten
              </button>
            </div>
            <div v-if="fields.length > 0" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
              <div
                v-for="f in fields"
                :key="f.id"
                class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-200 text-xs shadow-2xs"
              >
                <div>
                  <span class="font-bold text-slate-900">{{ getFieldLabel(f) }}</span>
                  <span class="text-[10px] text-slate-500 ml-1.5 font-mono">({{ f.field_type }})</span>
                  <span v-if="f.options && (Array.isArray(f.options) ? f.options.length : true)" class="text-[10px] text-cyan-700 block italic">Optionen: {{ formatFieldOptions(f.options) }}</span>
                </div>
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                  {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                </span>
              </div>
            </div>
            <div v-else class="text-[11px] text-slate-400 italic">
              Noch keine Zusatzfelder für diesen Ordner definiert.
            </div>
          </div>

          <!-- Teammitglieder einladen -->
          <div class="p-3.5 bg-cyan-50/70 border border-cyan-200 rounded-xl flex items-center justify-between">
            <div>
              <span class="text-xs font-black text-cyan-950 block">👥 Teammitglieder & Berechtigungen</span>
              <span class="text-[11px] text-cyan-800">Kollegen zu diesem Ordner einladen (Editor oder Viewer)</span>
            </div>
            <button
              type="button"
              @click="showEditFolderModal = false; openShareFolderModal()"
              class="taskster_button px-4 text-xs h-[36px] rounded-lg shadow-xs"
            >
              + Einladen
            </button>
          </div>
        </div>

        <!-- Sticky Footer -->
          <div class="p-4 sm:px-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0 rounded-b-3xl">
            <button
              v-if="user?.id === folder?.owner_id || user?.is_superadmin"
              type="button"
              @click="showEditFolderModal = false; openDeleteFolderModal()"
              class="taskster_button_accent px-4 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>{{ $t('dashboard.ordner_loeschen') }}</span>
            </button>
            <div class="flex items-center space-x-3 ml-auto">
              <button
                type="button"
                @click="showEditFolderModal = false; editFolderError = ''"
                class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
              >
                Abbrechen
              </button>
              <button
                type="submit"
                :disabled="savingFolder || !editFolderName.trim()"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg cursor-pointer"
              >
                <span>{{ savingFolder ? 'Wird gespeichert...' : 'Änderungen speichern' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Share Folder & Manage Members -->
    <div v-if="showShareFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-white/80 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 mb-5">
          <div class="flex items-center space-x-2.5">
            <span class="text-2xl">👥</span>
            <div>
              <h3 class="text-base font-black text-slate-900">Projektordner teilen</h3>
              <p class="text-xs text-slate-500 font-medium">{{ folder?.name }}</p>
            </div>
          </div>
          <button @click="showShareFolderModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 cursor-pointer">✕</button>
        </div>

        <!-- Section 1: Company Visibility Toggle -->
        <div class="p-4 rounded-2xl bg-white/70 border border-slate-200/80 mb-5 space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
              <span>🏢</span>
              <span>Sichtbarkeit im Unternehmen</span>
            </label>
            <span
              class="text-[10px] px-2.5 py-0.5 rounded-full font-bold uppercase"
              :class="folder?.visibility === 'company' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600 border border-slate-300'"
            >
              {{ folder?.visibility === 'company' ? '✓ Freigegeben' : '🔒 Privat' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-600 leading-relaxed">
            Wenn für das Unternehmen freigegeben, haben alle Mitglieder des Unternehmens ({{ folder?.company_name || user?.company_name || 'Firma' }}) automatisch Zugriff auf diesen Ordner und die darin enthaltenen Projekte.
          </p>
          <div class="pt-2 flex items-center space-x-2">
            <button
              type="button"
              @click="toggleFolderCompanyVisibility"
              :disabled="savingFolderVisibility"
              class="taskster_button px-4 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              <span>{{ folder?.visibility === 'company' ? '🔒 Auf Privat zurückstellen' : '🏢 Für gesamtes Unternehmen freigeben' }}</span>
            </button>
          </div>
        </div>

        <!-- Section 2: Invite Individual Member -->
        <div class="p-4 rounded-2xl bg-white/70 border border-slate-200/80 mb-5">
          <h4 class="text-xs font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
            <span>➕</span>
            <span>Mitglied zum Ordner hinzufügen</span>
          </h4>

          <form @submit.prevent="addFolderMember" class="space-y-3">
            <div v-if="folderMembersData.companyUsers && folderMembersData.companyUsers.length > 0">
              <label class="block text-[11px] font-bold text-slate-700 mb-1">Kollege aus Unternehmen auswählen</label>
              <select
                v-model="newMemberUserId"
                @change="onSelectCompanyUser"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-cyan-600 shadow-xs"
              >
                <option value="">-- Oder per E-Mail unten eingeben --</option>
                <option v-for="cu in folderMembersData.companyUsers" :key="cu.user_id" :value="cu.user_id">
                  {{ cu.name }} ({{ cu.email }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
              <input
                v-model="newMemberEmail"
                type="email"
                required
                placeholder="kollege@domain.ch"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-cyan-600 shadow-xs"
              />
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Berechtigung</label>
                <select
                  v-model="newMemberRole"
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-cyan-600 shadow-xs"
                >
                  <option value="editor">Editor (Bearbeiten)</option>
                  <option value="viewer">Viewer (Nur Lesen)</option>
                </select>
              </div>
              <div class="flex items-end">
                <button
                  type="submit"
                  :disabled="addingMember || !newMemberEmail"
                  class="taskster_button w-full text-xs h-[38px] rounded-lg cursor-pointer"
                >
                  <span>{{ addingMember ? 'Füge hinzu...' : '+ Hinzufügen' }}</span>
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Section 2b: Assign Group -->
        <div class="p-4 rounded-2xl bg-white/70 border border-slate-200/80 mb-5">
          <h4 class="text-xs font-bold text-slate-900 mb-2 flex items-center space-x-1.5">
            <span>🏷️</span>
            <span>Gruppe zum Ordner berechtigen</span>
          </h4>
          <form @submit.prevent="assignGroupToFolder" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Gruppe auswählen</label>
                <select
                  v-model="selectedAssignGroupId"
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-cyan-600 shadow-xs"
                >
                  <option value="">-- Gruppe wählen --</option>
                  <option v-for="g in availableGroups" :key="g.id" :value="g.id">
                    {{ g.name }} ({{ g.members?.length || 0 }} Mitglieder)
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Rolle für die Gruppe</label>
                <div class="flex items-center space-x-2">
                  <select
                    v-model="selectedAssignGroupRole"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-cyan-600 shadow-xs"
                  >
                    <option value="editor">Editor (Bearbeiten)</option>
                    <option value="viewer">Viewer (Nur Lesen)</option>
                    <option value="admin">Admin (Vollzugriff)</option>
                  </select>
                  <button
                    type="submit"
                    :disabled="assigningGroup || !selectedAssignGroupId"
                    class="taskster_button px-4 text-xs h-[38px] rounded-lg cursor-pointer shrink-0"
                  >
                    <span>{{ assigningGroup ? '...' : '+ Zuweisen' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </form>

          <!-- Assigned Groups List -->
          <div v-if="assignedGroupsForFolder.length > 0" class="mt-3 pt-3 border-t border-slate-200 space-y-1.5">
            <div class="text-[11px] font-bold text-slate-600">Zugewiesene Gruppen:</div>
            <div
              v-for="ag in assignedGroupsForFolder"
              :key="ag.id"
              class="flex items-center justify-between p-2 rounded-lg bg-cyan-50/60 border border-cyan-200 text-xs"
            >
              <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: ag.color || '#0891B2' }"></span>
                <span class="font-bold text-slate-900">{{ ag.name }}</span>
                <span class="text-[10px] text-slate-500">({{ ag.members?.length || 0 }} Mitglieder)</span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-white border border-cyan-300 text-cyan-800">
                  {{ ag.role }}
                </span>
                <button
                  type="button"
                  @click="removeGroupFromFolder(ag.id)"
                  class="text-rose-600 hover:text-rose-800 p-1 text-xs cursor-pointer font-bold"
                  title="Gruppe entfernen"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 3: Current Members List -->
        <div>
          <h4 class="text-xs font-bold text-slate-900 mb-2.5 flex items-center justify-between">
            <span class="flex items-center space-x-1.5">
              <span>📋</span>
              <span>Personen mit Zugriff ({{ folderMembers.length }})</span>
            </span>
          </h4>

          <div v-if="loadingFolderMembers" class="py-6 text-center text-xs text-slate-500">
            Lade Mitglieder...
          </div>

          <div v-else class="space-y-2 max-h-48 overflow-y-auto pr-1">
            <div
              v-for="m in folderMembers"
              :key="m.user_id"
              class="flex items-center justify-between p-2.5 rounded-xl bg-white/80 border border-slate-200/80 shadow-xs text-xs"
            >
              <div class="flex items-center space-x-2.5 min-w-0">
                <span class="w-7 h-7 rounded-full bg-cyan-100 text-cyan-800 font-bold flex items-center justify-center text-xs shrink-0">
                  {{ (m.name || m.email || '?').charAt(0).toUpperCase() }}
                </span>
                <div class="min-w-0">
                  <div class="font-bold text-slate-900 truncate">
                    {{ m.name }}
                    <span v-if="m.user_id === user?.id" class="text-[10px] text-cyan-700 font-normal ml-1">(Du)</span>
                  </div>
                  <div class="text-[10px] text-slate-500 truncate">{{ m.email }}</div>
                </div>
              </div>

              <div class="flex items-center space-x-2 shrink-0">
                <span
                  class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase"
                  :class="m.role === 'owner' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200'"
                >
                  {{ m.role === 'owner' ? 'Inhaber' : (m.role === 'editor' ? 'Editor' : 'Viewer') }}
                </span>

                <button
                  v-if="m.role !== 'owner' && (user?.id === folder?.owner_id)"
                  @click="removeFolderMember(m.user_id)"
                  class="text-rose-500 hover:text-rose-700 font-bold p-1 text-xs cursor-pointer"
                  title="Mitglied entfernen"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DELETE FOLDER CONFIRMATION MODAL -->
    <div v-if="showDeleteFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <div class="flex items-center space-x-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-black text-slate-900">{{ $t('dashboard.ordner_loeschen_titel') }}</h3>
            <p class="text-xs text-slate-500 font-medium">{{ folder?.name }}</p>
          </div>
        </div>

        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed mb-4">
          {{ $t('dashboard.ordner_loeschen_confirm', { name: folder?.name, count: projects.length }) }}
        </div>

        <div class="space-y-1.5 mb-5">
          <label class="block text-xs font-bold text-slate-700">
            Gib den Ordnernamen <strong class="text-rose-700 font-mono">{{ folder?.name }}</strong> zur Bestätigung ein:
          </label>
          <input
            v-model="deleteFolderConfirmName"
            type="text"
            :placeholder="folder?.name"
            class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-rose-500 focus:bg-white"
          />
        </div>

        <div v-if="deleteFolderError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ deleteFolderError }}
        </div>

        <div class="flex items-center justify-end space-x-3">
          <button
            type="button"
            @click="showDeleteFolderModal = false; deleteFolderConfirmName = ''; deleteFolderError = ''"
            :disabled="deletingFolder"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
          >
            {{ $t('common.abbrechen') }}
          </button>
          <button
            type="button"
            @click="confirmDeleteFolder"
            :disabled="deletingFolder || deleteFolderConfirmName.trim() !== folder?.name?.trim()"
            class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Trash2 v-if="!deletingFolder" class="w-3.5 h-3.5" />
            <span>{{ deletingFolder ? 'Wird gelöscht...' : $t('dashboard.ordner_loeschen_button') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- DELETE PROJECT CONFIRMATION MODAL -->
    <div v-if="showDeleteProjectModal && projectToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <div class="flex items-center space-x-3 mb-4">
          <div class="w-10 h-10 rounded-full bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-black text-slate-900">{{ $t('dashboard.projekt_loeschen_titel') }}</h3>
            <p class="text-xs text-slate-500 font-medium">{{ projectToDelete.title }}</p>
          </div>
        </div>

        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed mb-5">
          {{ $t('dashboard.projekt_loeschen_confirm', { title: projectToDelete.title }) }}
        </div>

        <div v-if="deleteProjectError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ deleteProjectError }}
        </div>

        <div class="flex items-center justify-end space-x-3">
          <button
            type="button"
            @click="showDeleteProjectModal = false; projectToDelete = null; deleteProjectError = ''"
            :disabled="deletingProject"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
          >
            {{ $t('common.abbrechen') }}
          </button>
          <button
            type="button"
            @click="confirmDeleteProject"
            :disabled="deletingProject"
            class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5"
          >
            <Trash2 v-if="!deletingProject" class="w-3.5 h-3.5" />
            <span>{{ deletingProject ? 'Wird gelöscht...' : $t('dashboard.projekt_loeschen_button') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Quick Journal Entry -->
    <div v-if="showQuickJournalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 sm:p-7 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <BookOpen class="w-4 h-4 text-[#0891B2]" />
              <span>Journal-Eintrag erfassen</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Ordner: <strong class="text-slate-800">{{ folder?.name }}</strong>
            </p>
          </div>
          <button @click="showQuickJournalModal = false" class="text-slate-400 hover:text-slate-600 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <div v-if="quickJournalError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs">
          {{ quickJournalError }}
        </div>

        <form @submit.prevent="saveQuickJournal" class="space-y-3.5">
          <!-- Project Assignment -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Projekt-Zuweisung
            </label>
            <select
              v-model="quickJournalProjectId"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
            >
              <option value="auto">✨ Automatisch zuweisen (anhand Text/Titel)</option>
              <optgroup v-if="projects.length > 0" label="Spezifisches Projekt auswählen">
                <option v-for="p in projects" :key="p.id" :value="p.id">
                  📁 {{ p.title }}
                </option>
              </optgroup>
              <option value="">General (Nur Ordner-Journal)</option>
            </select>
            <p class="text-[11px] text-slate-500 mt-1">
              {{ quickJournalProjectId === 'auto' ? 'Das System ordnet den Eintrag automatisch dem passenden Projekt zu (z.B. nach Kundennummer, Adresse oder Name im Text).' : '' }}
            </p>
          </div>

          <!-- Category -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kategorie</label>
              <select
                v-model="quickJournalCategory"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              >
                <option value="notiz">Notiz</option>
                <option value="baufortschritt">Baufortschritt</option>
                <option value="mangel">Mangel / Beanstandung</option>
                <option value="abnahme">Abnahme / Übergabe</option>
                <option value="telefonat">Telefonat / Besprechung</option>
                <option value="allgemein">Allgemein</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Betreff / Titel</label>
              <input
                v-model="quickJournalTitle"
                type="text"
                placeholder="z.B. Bauabnahme Keller"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
          </div>

          <!-- Content -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Inhalt / Bericht <span class="text-rose-500">*</span>
            </label>
            <textarea
              v-model="quickJournalContent"
              required
              rows="4"
              placeholder="Bericht, Feststellungen, Beschlüsse oder Notizen..."
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0891B2] resize-y"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showQuickJournalModal = false"
              class="taskster_button_light px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingQuickJournal || !quickJournalContent.trim()"
              class="taskster_button px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              <span>{{ savingQuickJournal ? 'Wird gespeichert...' : 'Eintrag speichern' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Add / Edit Custom Field (Unlocked) -->
    <div v-if="showFieldModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 sm:p-7 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">
              {{ editingFieldId ? 'Feld bearbeiten' : 'Neues benutzerdefiniertes Feld' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Definiere ein Attribut für Aufgaben oder Projekte in «{{ folder?.name }}».
            </p>
          </div>
          <button @click="showFieldModal = false" class="text-slate-400 hover:text-slate-600 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <div v-if="fieldModalError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs">
          {{ fieldModalError }}
        </div>

        <form @submit.prevent="saveFolderField" class="space-y-4">
          <!-- Gültigkeitsbereich: Projekt vs Aufgabe (Always Enabled) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Gültigkeitsbereich</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="newFieldEntityType = 'task'"
                class="py-2 px-3 rounded-xl text-xs font-bold border transition text-center cursor-pointer"
                :class="newFieldEntityType === 'task' ? 'bg-cyan-50 text-cyan-800 border-cyan-500' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
              >
                Aufgaben-Feld
              </button>
              <button
                type="button"
                @click="newFieldEntityType = 'project'"
                class="py-2 px-3 rounded-xl text-xs font-bold border transition text-center cursor-pointer"
                :class="newFieldEntityType === 'project' ? 'bg-purple-50 text-purple-800 border-purple-500' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
              >
                Projekt-Feld
              </button>
            </div>
          </div>

          <!-- Label -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Feld-Bezeichnung (Label) <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="newFieldLabel"
              type="text"
              required
              placeholder="z.B. Info, Bauleiter, Vorgang, Fertigstellung..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
            />
          </div>

          <!-- Field Type (Always Enabled, Unlocked!) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Feldtyp</label>
            <select
              v-model="newFieldType"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2] cursor-pointer"
            >
              <option value="text">Textzeile (kurz)</option>
              <option value="textarea">Mehrzeiliger Text / Notizfeld</option>
              <option value="select">Auswahlliste (Dropdown)</option>
              <option value="number">Zahl</option>
              <option value="date">Datum</option>
              <option value="checkbox">Ja / Nein (Checkbox)</option>
              <option value="url">Link / URL</option>
              <option value="email">E-Mail</option>
              <option value="phone">Telefon</option>
            </select>
          </div>

          <!-- Select Options if select -->
          <div v-if="newFieldType === 'select'" class="space-y-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
            <label class="block text-xs font-bold text-slate-700">Optionen für Auswahlliste</label>
            <div class="flex flex-wrap gap-1.5 mb-2">
              <span
                v-for="(opt, oIdx) in newFieldOptions"
                :key="oIdx"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-white border border-slate-300 text-slate-800 font-semibold"
              >
                <span>{{ opt }}</span>
                <button type="button" @click="newFieldOptions.splice(oIdx, 1)" class="text-rose-500 hover:text-rose-700 font-bold ml-1">✕</button>
              </span>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="newFieldOptionInput"
                @keydown.enter.prevent="addSelectOption"
                type="text"
                placeholder="+ Option eingeben und Enter drücken"
                class="flex-1 px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-[#0891B2]"
              />
              <button
                type="button"
                @click="addSelectOption"
                class="px-3 py-1.5 bg-[#0891B2] text-white text-xs font-bold rounded-lg cursor-pointer"
              >
                Hinzufügen
              </button>
            </div>
          </div>

          <!-- Required Checkbox -->
          <div class="pt-1">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="newFieldIsRequired"
                type="checkbox"
                class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0 cursor-pointer"
              />
              <span class="text-xs font-semibold text-slate-700">Pflichtfeld (Eingabe erforderlich)</span>
            </label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showFieldModal = false"
              class="taskster_button_light px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingField || !newFieldLabel.trim()"
              class="taskster_button px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              <span>{{ savingField ? 'Wird gespeichert...' : 'Feld speichern' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Add / Edit Contact -->
    <div v-if="showContactModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 sm:p-7 space-y-4 my-8">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-bold text-slate-900">
              {{ editingContactId ? 'Kontakt bearbeiten' : 'Neuen Kontakt erfassen' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Gilt für Ordner <strong class="text-slate-800">{{ folder?.name }}</strong> und alle zugehörigen Projekte.
            </p>
          </div>
          <button @click="showContactModal = false" class="text-slate-400 hover:text-slate-600 font-bold p-1 cursor-pointer">✕</button>
        </div>

        <div v-if="contactModalError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs">
          {{ contactModalError }}
        </div>

        <form @submit.prevent="saveFolderContact" class="space-y-3.5">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Name / Vollständiger Name <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="contactForm.name"
              type="text"
              required
              placeholder="z.B. Marco Rossi"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
            />
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Firma / Unternehmen</label>
              <input
                v-model="contactForm.company_name"
                type="text"
                placeholder="z.B. Rossi Bau AG"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Funktion / Rolle</label>
              <input
                v-model="contactForm.role_function"
                type="text"
                placeholder="z.B. Bauleiter, Polier"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Mobiltelefon (WhatsApp)</label>
              <input
                v-model="contactForm.mobile"
                type="tel"
                placeholder="+41 79 123 45 67"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Telefon Festnetz</label>
              <input
                v-model="contactForm.phone"
                type="tel"
                placeholder="+41 41 123 45 67"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
            <input
              v-model="contactForm.email"
              type="email"
              placeholder="m.rossi@firma.ch"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
            />
          </div>

          <div class="grid grid-cols-3 gap-2">
            <div class="col-span-2">
              <label class="block text-xs font-bold text-slate-700 mb-1">Strasse & Hausnr.</label>
              <input
                v-model="contactForm.address"
                type="text"
                placeholder="Hauptstrasse 12"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">PLZ & Ort</label>
              <input
                v-model="contactForm.city"
                type="text"
                placeholder="6000 Luzern"
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2]"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Notizen / Bemerkungen</label>
            <textarea
              v-model="contactForm.notes"
              rows="2"
              placeholder="Wichtige Hinweise oder Erreichbarkeit..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2] resize-y"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showContactModal = false"
              class="taskster_button_light px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingContact || !contactForm.name.trim()"
              class="taskster_button px-5 text-xs h-[38px] rounded-lg cursor-pointer"
            >
              <span>{{ savingContact ? 'Wird gespeichert...' : 'Kontakt speichern' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Folder,
  LayoutDashboard,
  Plus,
  Pencil,
  Trash2,
  Share2,
  Users,
  CheckCircle2,
  Clock,
  Search,
  X,
  Check,
  Building2,
  Lock,
  ClipboardList,
  BarChart3,
  ChevronDown,
  ChevronUp,
  ArrowRight,
  Star,
  LayoutGrid,
  List,
  FileUp,
  MoreVertical,
  AlertTriangle,
  BookOpen,
  Contact,
  SlidersHorizontal,
  HardHat,
  Phone,
  Mail,
  Globe,
  MapPin,
  Download,
  StickyNote
} from 'lucide-vue-next'
import * as XLSX from 'xlsx'
import { CONSTRUCTION_TEMPLATES, TEMPLATE_CUSTOM_FIELDS } from '~/composables/useProjectTemplates'

const { t, te } = useI18n()

const route = useRoute()
const { user, authHeaders } = useAuth()
const folderId = route.params.id as string

const folder = ref<any>(null)
const projects = ref<any[]>([])
const sortedProjects = computed(() => {
  return [...projects.value].sort((a, b) => {
    const aComp = a.status === 'completed' ? 1 : 0
    const bComp = b.status === 'completed' ? 1 : 0
    if (aComp !== bComp) return aComp - bComp
    if (a.is_default && !b.is_default) return -1
    if (!a.is_default && b.is_default) return 1
    return 0
  })
})

// Tab navigation state
const currentFolderTab = ref<'projects' | 'journal' | 'fields' | 'contacts'>('projects')

// Project Search & Filter state
const projectSearchQuery = ref('')
const projectStatusFilter = ref<'all' | 'active' | 'completed'>('all')

const activeProjectsCount = computed(() => {
  return projects.value.filter(p => p.status !== 'completed' && p.status !== 'archived').length
})

const completedProjectsCount = computed(() => {
  return projects.value.filter(p => p.status === 'completed').length
})

const filteredProjects = computed(() => {
  return sortedProjects.value.filter(p => {
    if (projectStatusFilter.value === 'active' && (p.status === 'completed' || p.status === 'archived')) {
      return false
    }
    if (projectStatusFilter.value === 'completed' && p.status !== 'completed') {
      return false
    }
    if (projectSearchQuery.value) {
      const q = projectSearchQuery.value.toLowerCase().trim()
      const titleMatch = (p.title || '').toLowerCase().includes(q)
      const customMatch = p.custom_data && Object.values(p.custom_data).some(v => String(v).toLowerCase().includes(q))
      if (!titleMatch && !customMatch) return false
    }
    return true
  })
})

const isMultiLineCustomField = (key: string, val: any) => {
  const f = fields.value.find((item: any) => item.field_key === key)
  if (f && f.field_type === 'textarea') return true
  const strVal = String(val ?? '')
  if (strVal.includes('\n')) return true
  if (['info', 'notiz', 'bemerkung', 'beschreibung', 'details'].includes(String(key).toLowerCase()) && strVal.length > 25) return true
  return false
}

const getCompactCustomData = (customData?: Record<string, any>) => {
  if (!customData) return []
  return Object.entries(customData)
    .filter(([key, val]) => val !== null && val !== '' && !isMultiLineCustomField(key, val))
    .map(([key, val]) => ({ key, label: getFieldLabel(key), value: formatCustomFieldValue(val, key) }))
}

const getMultiLineCustomData = (customData?: Record<string, any>) => {
  if (!customData) return []
  return Object.entries(customData)
    .filter(([key, val]) => val !== null && val !== '' && isMultiLineCustomField(key, val))
    .map(([key, val]) => ({ key, label: getFieldLabel(key), value: formatCustomFieldValue(val, key) }))
}

// Workflow Abschnitte Vorlage für Import
const importWorkflowSections = ref<string[]>(['Offen', 'In Arbeit', 'Abgeschlossen'])
const newImportSectionInput = ref('')

const resetImportSectionsToDefault = () => {
  importWorkflowSections.value = ['Offen', 'In Arbeit', 'Abgeschlossen']
}

const addImportSection = () => {
  const s = newImportSectionInput.value.trim()
  if (s && !importWorkflowSections.value.includes(s)) {
    importWorkflowSections.value.push(s)
    newImportSectionInput.value = ''
  }
}

const removeImportSection = (idx: number) => {
  if (importWorkflowSections.value.length > 1) {
    importWorkflowSections.value.splice(idx, 1)
  }
}

// Quick Journal state & methods
const showQuickJournalModal = ref(false)
const quickJournalProjectId = ref('auto')
const quickJournalTitle = ref('')
const quickJournalContent = ref('')
const quickJournalCategory = ref('notiz')
const quickJournalError = ref('')
const savingQuickJournal = ref(false)

const openQuickFolderJournalModal = () => {
  quickJournalProjectId.value = 'auto'
  quickJournalTitle.value = ''
  quickJournalContent.value = ''
  quickJournalCategory.value = 'notiz'
  quickJournalError.value = ''
  showQuickJournalModal.value = true
}

const folderJournals = ref<any[]>([])
const loadingFolderJournals = ref(false)

const loadFolderJournals = async () => {
  loadingFolderJournals.value = true
  try {
    const res = await $fetch<any>(`/api/journals?folder_id=${folderId}`, {
      headers: authHeaders()
    })
    folderJournals.value = res.entries || res.journals || []
  } catch (err) {
    console.error('Failed to load folder journals:', err)
  } finally {
    loadingFolderJournals.value = false
  }
}

const getProjectTitle = (pId?: string) => {
  if (!pId) return ''
  const p = projects.value.find(item => item.id === pId)
  return p ? p.title : pId
}

const saveQuickJournal = async () => {
  if (!quickJournalContent.value.trim()) return
  savingQuickJournal.value = true
  quickJournalError.value = ''
  try {
    await $fetch('/api/journals', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        folder_id: folderId,
        project_id: quickJournalProjectId.value === 'auto' ? 'auto' : (quickJournalProjectId.value || null),
        title: quickJournalTitle.value.trim(),
        content: quickJournalContent.value.trim(),
        category: quickJournalCategory.value
      }
    })
    showQuickJournalModal.value = false
    await loadFolderJournals()
    await loadFolderData()
  } catch (err: any) {
    quickJournalError.value = err.data?.statusMessage || err.message || 'Journal-Eintrag konnte nicht gespeichert werden'
  } finally {
    savingQuickJournal.value = false
  }
}

const deleteFolderJournal = async (journalId: string) => {
  if (!confirm('Diesen Journal-Eintrag wirklich löschen?')) return
  try {
    await $fetch(`/api/journals/${journalId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadFolderJournals()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Eintrags')
  }
}

// Custom Fields Management in Folder
const showFieldModal = ref(false)
const editingFieldId = ref<string | null>(null)
const newFieldLabel = ref('')
const newFieldKey = ref('')
const newFieldType = ref('text')
const newFieldEntityType = ref<'project' | 'task'>('project')
const newFieldIsRequired = ref(false)
const newFieldOptions = ref<string[]>([])
const newFieldOptionInput = ref('')
const savingField = ref(false)
const fieldModalError = ref('')

const openCreateFieldModal = () => {
  editingFieldId.value = null
  newFieldLabel.value = ''
  newFieldKey.value = ''
  newFieldType.value = 'text'
  newFieldEntityType.value = 'project'
  newFieldIsRequired.value = false
  newFieldOptions.value = []
  newFieldOptionInput.value = ''
  fieldModalError.value = ''
  showFieldModal.value = true
}

const openEditFieldModal = (f: any) => {
  editingFieldId.value = f.id
  newFieldLabel.value = f.label_key && te(f.label_key) ? t(f.label_key) : (f.label || '')
  newFieldKey.value = f.field_key
  newFieldType.value = f.field_type || 'text'
  newFieldEntityType.value = f.entity_type === 'task' ? 'task' : 'project'
  newFieldIsRequired.value = !!f.is_required
  newFieldOptions.value = Array.isArray(f.options) ? [...f.options] : []
  newFieldOptionInput.value = ''
  fieldModalError.value = ''
  showFieldModal.value = true
}

const addSelectOption = () => {
  const opt = newFieldOptionInput.value.trim()
  if (opt && !newFieldOptions.value.includes(opt)) {
    newFieldOptions.value.push(opt)
    newFieldOptionInput.value = ''
  }
}

const saveFolderField = async () => {
  if (!newFieldLabel.value.trim()) return
  savingField.value = true
  fieldModalError.value = ''
  try {
    if (editingFieldId.value) {
      await $fetch(`/api/folders/${folderId}/fields/${editingFieldId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: {
          label: newFieldLabel.value.trim(),
          field_type: newFieldType.value,
          entity_type: newFieldEntityType.value,
          is_required: newFieldIsRequired.value ? 1 : 0,
          options: newFieldOptions.value
        }
      })
    } else {
      const generatedKey = newFieldKey.value.trim() || newFieldLabel.value.toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '') || 'feld'
      await $fetch(`/api/folders/${folderId}/fields`, {
        method: 'POST',
        headers: authHeaders(),
        body: {
          label: newFieldLabel.value.trim(),
          field_key: generatedKey,
          field_type: newFieldType.value,
          entity_type: newFieldEntityType.value,
          is_required: newFieldIsRequired.value ? 1 : 0,
          options: newFieldOptions.value
        }
      })
    }
    showFieldModal.value = false
    await loadFolderData()
  } catch (err: any) {
    fieldModalError.value = err.data?.statusMessage || err.message || 'Feld konnte nicht gespeichert werden'
  } finally {
    savingField.value = false
  }
}

const deleteFolderField = async (fieldId: string) => {
  if (!confirm('Dieses benutzerdefinierte Feld wirklich löschen?')) return
  try {
    await $fetch(`/api/folders/${folderId}/fields/${fieldId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadFolderData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Felds')
  }
}

// Contacts Management in Folder
const folderContacts = ref<any[]>([])
const loadingFolderContacts = ref(false)
const showContactModal = ref(false)
const editingContactId = ref<string | null>(null)
const contactModalError = ref('')
const savingContact = ref(false)

const contactForm = reactive({
  name: '',
  company_name: '',
  role_function: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  city: '',
  zip_code: '',
  category_group: '',
  notes: '',
  tags: [] as string[]
})

const loadFolderContacts = async () => {
  loadingFolderContacts.value = true
  try {
    const res = await $fetch<any>(`/api/contacts?folder_id=${folderId}`, {
      headers: authHeaders()
    })
    folderContacts.value = res.contacts || []
  } catch (err) {
    console.error('Failed to load folder contacts:', err)
  } finally {
    loadingFolderContacts.value = false
  }
}

const openCreateContactModal = () => {
  editingContactId.value = null
  contactForm.name = ''
  contactForm.company_name = ''
  contactForm.role_function = ''
  contactForm.email = ''
  contactForm.phone = ''
  contactForm.mobile = ''
  contactForm.address = ''
  contactForm.city = ''
  contactForm.zip_code = ''
  contactForm.category_group = ''
  contactForm.notes = ''
  contactForm.tags = []
  contactModalError.value = ''
  showContactModal.value = true
}

const openEditContactModal = (c: any) => {
  editingContactId.value = c.id
  contactForm.name = formatContactFullName(c)
  contactForm.company_name = c.company_name || ''
  contactForm.role_function = c.role_function || ''
  contactForm.email = c.email || ''
  contactForm.phone = c.phone || ''
  contactForm.mobile = c.mobile || ''
  contactForm.address = c.address || ''
  contactForm.city = c.city || ''
  contactForm.zip_code = c.zip_code || ''
  contactForm.category_group = c.category_group || ''
  contactForm.notes = c.notes || ''
  contactForm.tags = Array.isArray(c.tags) ? [...c.tags] : []
  contactModalError.value = ''
  showContactModal.value = true
}

const saveFolderContact = async () => {
  if (!contactForm.name.trim()) return
  savingContact.value = true
  contactModalError.value = ''
  try {
    const payload = {
      folder_id: folderId,
      first_name: contactForm.name.split(' ')[0] || '',
      last_name: contactForm.name.split(' ').slice(1).join(' ') || '',
      company_name: contactForm.company_name,
      role_function: contactForm.role_function,
      email: contactForm.email,
      phone: contactForm.phone,
      mobile: contactForm.mobile,
      address: contactForm.address,
      city: contactForm.city,
      zip_code: contactForm.zip_code,
      category_group: contactForm.category_group,
      notes: contactForm.notes,
      tags: contactForm.tags
    }
    if (editingContactId.value) {
      await $fetch(`/api/contacts/${editingContactId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: payload
      })
    } else {
      await $fetch('/api/contacts', {
        method: 'POST',
        headers: authHeaders(),
        body: payload
      })
    }
    showContactModal.value = false
    await loadFolderContacts()
  } catch (err: any) {
    contactModalError.value = err.data?.statusMessage || err.message || 'Kontakt konnte nicht gespeichert werden'
  } finally {
    savingContact.value = false
  }
}

const deleteFolderContact = async (c: any) => {
  if (!confirm(`Möchtest du den Kontakt "${formatContactFullName(c)}" wirklich löschen?`)) return
  try {
    await $fetch(`/api/contacts/${c.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadFolderContacts()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Kontakts')
  }
}

const formatContactFullName = (c: any) => {
  if (!c) return ''
  const parts = [c.first_name, c.last_name].filter(Boolean)
  return parts.length > 0 ? parts.join(' ') : (c.name || 'Unbekannt')
}

const getContactInitials = (c: any) => {
  const name = formatContactFullName(c) || c.company_name || '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
}

const cleanPhoneForWhatsApp = (num?: string) => {
  return (num || '').replace(/[^0-9]/g, '')
}

const exportContactVCard = (c: any) => {
  const vcard = [
    'BEGIN:VCARD',
    'VERSION:3.0',
    `FN:${formatContactFullName(c)}`,
    c.company_name ? `ORG:${c.company_name}` : '',
    c.role_function ? `TITLE:${c.role_function}` : '',
    c.email ? `EMAIL;TYPE=INTERNET:${c.email}` : '',
    c.mobile ? `TEL;TYPE=CELL:${c.mobile}` : '',
    c.phone ? `TEL;TYPE=WORK:${c.phone}` : '',
    (c.address || c.city) ? `ADR;TYPE=WORK:;;${c.address || ''};${c.city || ''};;${c.zip_code || ''};` : '',
    c.notes ? `NOTE:${c.notes.replace(/\n/g, '\\n')}` : '',
    'END:VCARD'
  ].filter(Boolean).join('\r\n')

  const blob = new Blob([vcard], { type: 'text/vcard;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${(formatContactFullName(c) || 'kontakt').replace(/[^a-z0-9]/gi, '_')}.vcf`
  a.click()
  URL.revokeObjectURL(url)
}

const fields = ref<any[]>([])
const loading = ref(true)
const projectViewMode = ref<'grid' | 'list'>('grid')
const timeSummary = ref<any>(null)
const showControllingDetails = ref(false)
const showActionsMenu = ref(false)

// Folder edit state
const showEditFolderModal = ref(false)
const editFolderName = ref('')
const editFolderIcon = ref('📁')
const editFolderVisibility = ref('private')
const editFolderDefaultProjectId = ref('')
const editFolderTemplateId = ref('')
const savingFolder = ref(false)
const editFolderError = ref('')

interface FolderSectionItem {
  title: string
  is_completed_target: number
}
const editFolderSections = ref<FolderSectionItem[]>([])
const newFolderSectionTitle = ref('')

const onEditFolderTemplateChange = () => {
  if (editFolderTemplateId.value) {
    const matchedTpl = CONSTRUCTION_TEMPLATES.find(t => t.id === editFolderTemplateId.value)
    if (matchedTpl) {
      editFolderSections.value = matchedTpl.lists.map(lKey => ({
        title: resolveText(lKey, lKey.replace('sections.', '')),
        is_completed_target: (lKey.includes('handover') || lKey.includes('abgeschlossen') || lKey.includes('commissioning')) ? 1 : 0
      }))
    }
  }
}

const resetEditFolderSectionsFromTemplate = () => {
  if (selectedEditTemplate.value) {
    editFolderSections.value = selectedEditTemplate.value.lists.map(lKey => ({
      title: resolveText(lKey, lKey.replace('sections.', '')),
      is_completed_target: (lKey.includes('handover') || lKey.includes('abgeschlossen') || lKey.includes('commissioning')) ? 1 : 0
    }))
  }
}

const resetEditFolderSectionsToStandard = () => {
  editFolderSections.value = [
    { title: 'Offen', is_completed_target: 0 },
    { title: 'In Arbeit', is_completed_target: 0 },
    { title: 'Abgeschlossen', is_completed_target: 1 }
  ]
}

const addEditFolderSection = () => {
  const t = newFolderSectionTitle.value.trim()
  if (!t) return
  editFolderSections.value.push({
    title: t,
    is_completed_target: 0
  })
  newFolderSectionTitle.value = ''
}

const removeEditFolderSection = (idx: number) => {
  if (editFolderSections.value.length <= 1) return
  editFolderSections.value.splice(idx, 1)
}

const moveEditFolderSectionUp = (idx: number) => {
  if (idx <= 0) return
  const temp = editFolderSections.value[idx]
  editFolderSections.value[idx] = editFolderSections.value[idx - 1]
  editFolderSections.value[idx - 1] = temp
}

const moveEditFolderSectionDown = (idx: number) => {
  if (idx >= editFolderSections.value.length - 1) return
  const temp = editFolderSections.value[idx]
  editFolderSections.value[idx] = editFolderSections.value[idx + 1]
  editFolderSections.value[idx + 1] = temp
}

const toggleEditFolderSectionTarget = (idx: number) => {
  const current = editFolderSections.value[idx].is_completed_target
  editFolderSections.value.forEach((s, i) => {
    s.is_completed_target = (i === idx && !current) ? 1 : 0
  })
}

const currentFolderTemplate = computed(() => {
  const tId = folder.value?.settings?.template_id
  if (!tId) return null
  return CONSTRUCTION_TEMPLATES.find(t => t.id === tId) || null
})

const selectedEditTemplate = computed(() => {
  if (!editFolderTemplateId.value) return null
  return CONSTRUCTION_TEMPLATES.find(t => t.id === editFolderTemplateId.value) || null
})

const availableFolderIcons = [
  // Job & Gewerbe
  { icon: '📁', label: 'Standard Ordner' },
  { icon: '🏗️', label: 'Bau & Tiefbau' },
  { icon: '💻', label: 'IT & Software' },
  { icon: '📐', label: 'Architektur & Planung' },
  { icon: '⚡', label: 'Elektro & Energie' },
  { icon: '🔧', label: 'Montage & Service' },
  { icon: '🚚', label: 'Logistik & Transport' },
  { icon: '📊', label: 'Finanzen & Controlling' },
  { icon: '⚖️', label: 'Recht & Notariat' },
  { icon: '🏥', label: 'Gesundheit & Praxis' },
  { icon: '🏢', label: 'Immobilien & Liegenschaften' },
  // Privat & Haushalt
  { icon: '🏠', label: 'Haus & Umbau' },
  { icon: '🏡', label: 'Garten & Aussen' },
  { icon: '🛋️', label: 'Wohnen & Interior' },
  { icon: '🎂', label: 'Event & Feier' },
  { icon: '✈️', label: 'Reisen & Urlaub' },
  { icon: '🚗', label: 'Fahrzeuge & Garage' },
  { icon: '📑', label: 'Privat & Steuern' },
  { icon: '🎯', label: 'Ziele & Pläne' },
  { icon: '📦', label: 'Umzug & Lager' },
  { icon: '🎨', label: 'Kreativ & Hobby' }
]

const openEditFolderModal = () => {
  if (!folder.value) return
  editFolderName.value = folder.value.name
  editFolderIcon.value = folder.value.icon || '📁'
  editFolderVisibility.value = folder.value.visibility || 'private'
  editFolderDefaultProjectId.value = projects.value.find(p => p.is_default)?.id || projects.value[0]?.id || ''
  editFolderTemplateId.value = folder.value.settings?.template_id || ''
  editFolderError.value = ''
  newFolderSectionTitle.value = ''

  const rawSections = folder.value.settings?.default_sections
  if (Array.isArray(rawSections) && rawSections.length > 0) {
    editFolderSections.value = rawSections.map((s: any) => ({
      title: typeof s === 'string' ? s : (s.title || ''),
      is_completed_target: typeof s === 'object' && s.is_completed_target ? 1 : 0
    }))
  } else if (folder.value.settings?.template_id) {
    const matchedTpl = CONSTRUCTION_TEMPLATES.find(t => t.id === folder.value.settings.template_id)
    if (matchedTpl) {
      editFolderSections.value = matchedTpl.lists.map(lKey => ({
        title: resolveText(lKey, lKey.replace('sections.', '')),
        is_completed_target: (lKey.includes('handover') || lKey.includes('abgeschlossen') || lKey.includes('commissioning')) ? 1 : 0
      }))
    } else {
      editFolderSections.value = [
        { title: 'Offen', is_completed_target: 0 },
        { title: 'In Arbeit', is_completed_target: 0 },
        { title: 'Abgeschlossen', is_completed_target: 1 }
      ]
    }
  } else {
    editFolderSections.value = [
      { title: 'Offen', is_completed_target: 0 },
      { title: 'In Arbeit', is_completed_target: 0 },
      { title: 'Abgeschlossen', is_completed_target: 1 }
    ]
  }

  showEditFolderModal.value = true
}

const updateFolder = async () => {
  editFolderError.value = ''
  savingFolder.value = true
  try {
    const existingSettings = folder.value?.settings ? (typeof folder.value.settings === 'string' ? JSON.parse(folder.value.settings) : { ...folder.value.settings }) : {}
    existingSettings.template_id = editFolderTemplateId.value || null

    const cleanedSections = editFolderSections.value
      .map((s, idx) => ({
        title: s.title.trim() || `Abschnitt ${idx + 1}`,
        is_completed_target: s.is_completed_target ? 1 : 0
      }))
      .filter(s => s.title.length > 0)

    if (cleanedSections.length > 0) {
      existingSettings.default_sections = cleanedSections
    }

    if (editFolderTemplateId.value) {
      const matchedTpl = CONSTRUCTION_TEMPLATES.find(t => t.id === editFolderTemplateId.value)
      if (matchedTpl) {
        // Auto-create custom fields defined in template for this folder
        if (matchedTpl.fields && Array.isArray(matchedTpl.fields)) {
          for (const f of matchedTpl.fields) {
            const rawKey = f.field_key
            const fieldLabel = resolveText(f.label_key, f.label)
            const normalizedOptions = (f.options || []).map((opt: any) => {
              if (typeof opt === 'string') return opt
              if (opt && typeof opt === 'object') {
                return resolveText(opt.label_key, opt.label || opt.value)
              }
              return String(opt)
            })

            const existingF = fields.value.find((ef: any) => ef.field_key === rawKey)
            if (!existingF) {
              try {
                await $fetch(`/api/folders/${folderId}/fields`, {
                  method: 'POST',
                  headers: authHeaders(),
                  body: {
                    field_key: rawKey,
                    label: fieldLabel,
                    label_key: f.label_key || null,
                    field_type: f.field_type,
                    entity_type: f.entity_type,
                    options: normalizedOptions,
                    is_required: f.is_required ? 1 : 0
                  }
                })
              } catch (e) {
                // Ignore duplicate field error
              }
            } else if (existingF.label === '1' || existingF.label === 1 || !existingF.label) {
              try {
                await $fetch(`/api/folders/${folderId}/fields/${existingF.id}`, {
                  method: 'PUT',
                  headers: authHeaders(),
                  body: {
                    label: fieldLabel,
                    label_key: f.label_key || null,
                    field_type: f.field_type,
                    entity_type: f.entity_type,
                    options: normalizedOptions
                  }
                })
              } catch (e) {}
            }
          }
        }
      }
    }

    const res = await $fetch<any>(`/api/folders/${folderId}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        name: editFolderName.value,
        icon: editFolderIcon.value,
        visibility: editFolderVisibility.value,
        default_project_id: editFolderDefaultProjectId.value || null,
        settings: existingSettings
      }
    })
    if (res?.folder) {
      folder.value.name = res.folder.name
      folder.value.icon = res.folder.icon
      folder.value.visibility = res.folder.visibility
      folder.value.settings = res.folder.settings
    }
    showEditFolderModal.value = false
    await loadFolderData()
  } catch (err: any) {
    editFolderError.value = err.data?.statusMessage || 'Ordner konnte nicht aktualisiert werden'
  } finally {
    savingFolder.value = false
  }
}

// Delete Folder State & Handlers
const showDeleteFolderModal = ref(false)
const deletingFolder = ref(false)
const deleteFolderError = ref('')
const deleteFolderConfirmName = ref('')

const openDeleteFolderModal = () => {
  deleteFolderError.value = ''
  deleteFolderConfirmName.value = ''
  showDeleteFolderModal.value = true
}

const confirmDeleteFolder = async () => {
  if (!folder.value?.id) return
  if (deleteFolderConfirmName.value.trim() !== folder.value.name.trim()) {
    deleteFolderError.value = 'Der eingegebene Ordnername stimmt nicht überein.'
    return
  }
  deletingFolder.value = true
  deleteFolderError.value = ''
  try {
    await $fetch(`/api/folders/${folder.value.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    showDeleteFolderModal.value = false
    navigateTo('/dashboard')
  } catch (err: any) {
    deleteFolderError.value = err.data?.statusMessage || err.message || 'Fehler beim Löschen des Ordners'
  } finally {
    deletingFolder.value = false
  }
}

// Delete Project State & Handlers
const showDeleteProjectModal = ref(false)
const projectToDelete = ref<any>(null)
const deletingProject = ref(false)
const deleteProjectError = ref('')

const canManageProject = (p: any) => {
  if (!user.value) return false
  if (user.value.is_superadmin) return true
  if (folder.value?.owner_id === user.value.id) return true
  return false
}

const openDeleteProjectModal = (p: any) => {
  projectToDelete.value = p
  deleteProjectError.value = ''
  showDeleteProjectModal.value = true
}

const confirmDeleteProject = async () => {
  if (!projectToDelete.value?.id) return
  deletingProject.value = true
  deleteProjectError.value = ''
  try {
    await $fetch(`/api/projects/${projectToDelete.value.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    showDeleteProjectModal.value = false
    projectToDelete.value = null
    await loadFolderData()
  } catch (err: any) {
    deleteProjectError.value = err.data?.statusMessage || err.message || 'Fehler beim Löschen des Projekts'
  } finally {
    deletingProject.value = false
  }
}

// Share Folder & Manage Members State
const showShareFolderModal = ref(false)
const loadingFolderMembers = ref(false)
const folderMembers = ref<any[]>([])
const folderMembersData = ref<any>({})
const newMemberEmail = ref('')
const newMemberUserId = ref('')
const newMemberRole = ref('editor')
const addingMember = ref(false)
const savingFolderVisibility = ref(false)

const openShareFolderModal = async () => {
  showShareFolderModal.value = true
  newMemberEmail.value = ''
  newMemberUserId.value = ''
  newMemberRole.value = 'editor'
  await Promise.all([loadFolderMembers(), loadGroupsForFolder()])
}

const loadFolderMembers = async () => {
  loadingFolderMembers.value = true
  try {
    const res = await $fetch<any>(`/api/folders/${folderId}/members`, {
      headers: authHeaders()
    })
    folderMembersData.value = res || {}
    folderMembers.value = res.members || []
  } catch (err) {
    console.error('Failed to load folder members:', err)
  } finally {
    loadingFolderMembers.value = false
  }
}

const onSelectCompanyUser = () => {
  if (newMemberUserId.value) {
    const found = (folderMembersData.value.companyUsers || []).find((u: any) => u.user_id === newMemberUserId.value)
    if (found) {
      newMemberEmail.value = found.email
    }
  }
}

const toggleFolderCompanyVisibility = async () => {
  if (!folder.value) return
  savingFolderVisibility.value = true
  const newVis = folder.value.visibility === 'company' ? 'private' : 'company'
  try {
    const res = await $fetch<any>(`/api/folders/${folderId}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        name: folder.value.name,
        icon: folder.value.icon,
        visibility: newVis
      }
    })
    if (res?.folder) {
      folder.value.visibility = res.folder.visibility
    } else {
      folder.value.visibility = newVis
    }
    await loadFolderMembers()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Ändern der Sichtbarkeit')
  } finally {
    savingFolderVisibility.value = false
  }
}

const addFolderMember = async () => {
  if (!newMemberEmail.value) return
  addingMember.value = true
  try {
    await $fetch(`/api/folders/${folderId}/members`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        email: newMemberEmail.value,
        user_id: newMemberUserId.value || undefined,
        role: newMemberRole.value
      }
    })
    newMemberEmail.value = ''
    newMemberUserId.value = ''
    await loadFolderMembers()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Hinzufügen des Mitglieds')
  } finally {
    addingMember.value = false
  }
}

const removeFolderMember = async (userId: string) => {
  if (!confirm('Möchtest du dieses Mitglied wirklich aus dem Projektordner entfernen?')) return
  try {
    await $fetch(`/api/folders/${folderId}/members/${userId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadFolderMembers()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Entfernen des Mitglieds')
  }
}

// ---------------------------------------------------------------------------
// Gruppen-Berechtigungen für Ordner
// ---------------------------------------------------------------------------
const availableGroups = ref<any[]>([])
const selectedAssignGroupId = ref('')
const selectedAssignGroupRole = ref('editor')
const assigningGroup = ref(false)

const assignedGroupsForFolder = computed(() => {
  return availableGroups.value
    .filter(g => (g.folders || []).some((f: any) => f.folder_id === folderId))
    .map(g => {
      const f = (g.folders || []).find((x: any) => x.folder_id === folderId)
      return {
        id: g.id,
        name: g.name,
        color: g.color,
        members: g.members,
        role: f?.role || 'editor'
      }
    })
})

const loadGroupsForFolder = async () => {
  try {
    const res = await $fetch<any>('/api/groups', { headers: authHeaders() })
    availableGroups.value = res.groups || []
  } catch {
    availableGroups.value = []
  }
}

const assignGroupToFolder = async () => {
  if (!selectedAssignGroupId.value) return
  assigningGroup.value = true
  try {
    await $fetch(`/api/groups/${selectedAssignGroupId.value}/assign`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        type: 'folder',
        target_id: folderId,
        role: selectedAssignGroupRole.value
      }
    })
    selectedAssignGroupId.value = ''
    await loadGroupsForFolder()
  } catch (err: any) {
    alert(err?.data?.statusMessage || 'Fehler beim Zuweisen der Gruppe')
  } finally {
    assigningGroup.value = false
  }
}

const removeGroupFromFolder = async (groupId: string) => {
  if (!confirm('Gruppe wirklich von diesem Ordner entfernen?')) return
  try {
    await $fetch(`/api/groups/${groupId}/assign`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        type: 'folder',
        target_id: folderId,
        role: 'none'
      }
    })
    await loadGroupsForFolder()
  } catch (err: any) {
    alert(err?.data?.statusMessage || 'Fehler beim Entfernen der Gruppe')
  }
}

// Project creation & Template state
const showNewProjectModal = ref(false)
const newProjectTitle = ref('')
const newProjectVisibility = ref('private')
const newProjectCustomData = ref<Record<string, any>>({})
const creatingProject = ref(false)
const projectModalError = ref('')

const templates = ref<any[]>(CONSTRUCTION_TEMPLATES)
const loadingTemplates = ref(false)
const projectCreationMode = ref<'template' | 'import' | 'blank'>('template')
const selectedTemplateId = ref<string | null>(null)
const selectedTemplateLists = ref<string[]>([])
const newTemplatePhaseInput = ref('')
const templateFilterCategory = ref<'all' | 'job' | 'private'>('all')
const templateSearchQuery = ref('')

// Excel / CSV Project Import State
const importFileInput = ref<HTMLInputElement | null>(null)
const importFileName = ref('')
const importHeaders = ref<string[]>([])
const importParsedRows = ref<any[][]>([])
const importColumnMapping = ref<Record<number, string>>({})
const importError = ref('')
const isImportDragging = ref(false)

const getFieldTypeLabel = (type: string) => {
  switch (type) {
    case 'select': return 'Auswahlfeld'
    case 'text': return 'Textfeld'
    case 'number': return 'Zahlenfeld'
    case 'date': return 'Datum'
    case 'checkbox': return 'Ja/Nein'
    case 'textarea': return 'Langer Text'
    case 'url': return 'Link / URL'
    case 'email': return 'E-Mail'
    case 'phone': return 'Telefon'
    default: return 'Zusatzfeld'
  }
}

const getTemplateIcon = (iconName: string, category: string) => {
  if (category === 'private') {
    if (iconName === 'Sparkles') return '✨'
    if (iconName === 'Truck') return '🚚'
    if (iconName === 'Calculator') return '🧮'
    return '🏡'
  }
  switch (iconName) {
    case 'HardHat': return '👷'
    case 'Laptop': return '💻'
    case 'Wrench': return '🔧'
    case 'Flame': return '🔥'
    case 'Megaphone': return '📣'
    case 'Building': return '🏢'
    case 'Utensils': return '🍽️'
    case 'ShieldCheck': return '🛡️'
    case 'Calculator': return '🧮'
    default: return '💼'
  }
}

const selectedTemplate = computed(() => {
  return templates.value.find((t: any) => t.id === selectedTemplateId.value)
})

const filteredTemplates = computed(() => {
  return templates.value.filter((t: any) => {
    const matchCat = templateFilterCategory.value === 'all' || t.category === templateFilterCategory.value
    const q = templateSearchQuery.value.toLowerCase().trim()
    const matchSearch = !q || (
      t.name?.toLowerCase().includes(q) ||
      (t.name_key && te(t.name_key) && t(t.name_key).toLowerCase().includes(q)) ||
      t.description?.toLowerCase().includes(q) ||
      (t.description_key && te(t.description_key) && t(t.description_key).toLowerCase().includes(q)) ||
      t.subcategory?.toLowerCase().includes(q)
    )
    return matchCat && matchSearch
  })
})

const projectFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type === 'project')
})

const taskCustomFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type !== 'project')
})

// Häufige Vorlagen-Zusatzfelder für den schnellen Import
const commonCustomFieldTemplates = TEMPLATE_CUSTOM_FIELDS

const getHeaderKey = (header: string) => {
  return String(header || '').trim().toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '') || 'feld'
}

const getAvailableTemplateFields = (header: string) => {
  const existingKeys = new Set(fields.value.map((f: any) => f.field_key))
  const colKey = getHeaderKey(header)
  existingKeys.add(colKey)
  return commonCustomFieldTemplates.filter((tpl: any) => !existingKeys.has(tpl.key))
}

const resolveText = (key?: string, fallback?: string) => {
  if (!key) return fallback || ''
  try {
    if (typeof te === 'function' && te(key)) {
      return t(key)
    }
  } catch {}
  return fallback || key.replace(/^(sections|fields|templates)\./, '')
}

const getFieldLabel = (keyOrField: any) => {
  if (!keyOrField) return ''
  let f: any = null
  let key: string = ''
  if (typeof keyOrField === 'string') {
    key = keyOrField
    f = fields.value.find((item: any) => item.field_key === key)
  } else if (typeof keyOrField === 'object') {
    f = keyOrField
    key = f.field_key || ''
  }
  if (f) {
    if (f.label_key && typeof te === 'function' && te(f.label_key)) {
      return t(f.label_key)
    }
    if (f.label && f.label !== '1' && f.label !== 1) return f.label
  }
  const tpl = commonCustomFieldTemplates.find((item: any) => item.key === key)
  if (tpl) {
    if (tpl.label_key && typeof te === 'function' && te(tpl.label_key)) return t(tpl.label_key)
    return tpl.label || key
  }
  return key
}

const formatFieldOptions = (options: any) => {
  if (!options) return ''
  let list = options
  if (typeof list === 'string') {
    try { list = JSON.parse(list) } catch { return list }
  }
  if (!Array.isArray(list)) return ''
  return list.map((opt: any) => {
    if (typeof opt === 'string') {
      const optKey = 'fields.options.' + opt
      return (typeof te === 'function' && te(optKey)) ? t(optKey) : opt
    }
    if (opt && typeof opt === 'object') {
      if (opt.label_key && typeof te === 'function' && te(opt.label_key)) {
        return t(opt.label_key)
      }
      return opt.label || opt.value || ''
    }
    return String(opt)
  }).filter(Boolean).join(', ')
}

const formatCustomFieldValue = (val: any, fieldKey?: string) => {
  if (val === true || val === 'true') return '✓ ' + (te('common.yes') ? t('common.yes') : 'Ja')
  if (val === false || val === 'false') return te('common.no') ? t('common.no') : 'Nein'
  if (val === null || val === undefined || val === '') return '-'
  if (fieldKey) {
    const f = fields.value.find((item: any) => item.field_key === fieldKey)
    if (f && f.options && f.options.length) {
      const opt = f.options.find((o: any) => (typeof o === 'object' ? o.value : o) === String(val))
      if (opt) {
        return typeof opt === 'object' ? (opt.label_key ? t(opt.label_key) : (opt.label || opt.value)) : (te('fields.options.' + opt) ? t('fields.options.' + opt) : opt)
      }
    }
    const tpl = commonCustomFieldTemplates.find((item: any) => item.key === fieldKey)
    if (tpl && tpl.options && tpl.options.length) {
      const opt = tpl.options.find((o: any) => (typeof o === 'object' ? o.value : o) === String(val))
      if (opt) {
        return typeof opt === 'object' ? (opt.label_key ? t(opt.label_key) : (opt.label || opt.value)) : (te('fields.options.' + opt) ? t('fields.options.' + opt) : opt)
      }
    }
  }
  if (typeof val === 'string' && te('fields.options.' + val)) {
    return t('fields.options.' + val)
  }
  return String(val)
}

const fetchTemplates = async () => {
  if (templates.value.length > 0) return
  loadingTemplates.value = true
  try {
    const res = await $fetch<any>('/api/templates', {
      headers: authHeaders()
    })
    templates.value = res.templates || []
    if (templates.value.length > 0 && !selectedTemplateId.value) {
      selectTemplate(templates.value[0])
    }
  } catch (err: any) {
    console.error('Failed to load templates', err)
  } finally {
    loadingTemplates.value = false
  }
}

const selectTemplate = (tmpl: any) => {
  selectedTemplateId.value = tmpl.id
  selectedTemplateLists.value = [...(tmpl.lists || [])]
  const currentTitle = newProjectTitle.value
  const isDefaultOrTemplateTitle = !currentTitle || templates.value.some((t: any) =>
    t.name === currentTitle || (t.name_key && t(t.name_key) === currentTitle)
  )
  if (isDefaultOrTemplateTitle) {
    newProjectTitle.value = tmpl.name_key ? t(tmpl.name_key) : tmpl.name
  }
}

const removeTemplatePhase = (idx: number) => {
  if (selectedTemplateLists.value.length > 1) {
    selectedTemplateLists.value.splice(idx, 1)
  }
}

const addTemplatePhase = () => {
  const p = newTemplatePhaseInput.value.trim()
  if (p && !selectedTemplateLists.value.includes(p)) {
    selectedTemplateLists.value.push(p)
    newTemplatePhaseInput.value = ''
  }
}

// Download Sample Excel Template for Projects
const downloadSampleExcel = () => {
  const sampleData = [
    ['Projekttitel', 'Status', 'Sichtbarkeit', 'Währung', 'Budget Stunden', 'Budget Betrag'],
    ['Neubau Einfamilienhaus Meier', 'active', 'private', 'CHF', '120', '150000'],
    ['Sanierung Bürogebäude Nord', 'active', 'company', 'CHF', '80', '95000'],
    ['Umbau Dachgeschoss', 'completed', 'private', 'CHF', '45', '42000']
  ]
  const ws = XLSX.utils.aoa_to_sheet(sampleData)
  ws['!cols'] = [
    { wch: 35 },
    { wch: 15 },
    { wch: 15 },
    { wch: 12 },
    { wch: 16 },
    { wch: 16 }
  ]
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Projekte')
  XLSX.writeFile(wb, 'Taskster_Projekte_Import_Muster.xlsx')
}

const onImportFileDrop = (e: DragEvent) => {
  isImportDragging.value = false
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    processImportFile(files[0])
  }
}

const onImportFileSelected = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    processImportFile(target.files[0])
  }
}

const parseCSVString = (text: string, delimiter: string): string[][] => {
  const lines = text.split(/\r\n|\n|\r/)
  const result: string[][] = []

  for (const line of lines) {
    if (!line.trim()) continue
    const row: string[] = []
    let inQuotes = false
    let currentCell = ''

    for (let i = 0; i < line.length; i++) {
      const char = line[i]
      if (char === '"' || char === "'") {
        inQuotes = !inQuotes
      } else if (char === delimiter && !inQuotes) {
        row.push(currentCell.trim().replace(/^["']|["']$/g, ''))
        currentCell = ''
      } else {
        currentCell += char
      }
    }
    row.push(currentCell.trim().replace(/^["']|["']$/g, ''))
    result.push(row)
  }
  return result
}

const processImportFile = async (file: File) => {
  importError.value = ''
  importFileName.value = file.name

  try {
    const isExcel = /\.(xlsx|xls)$/i.test(file.name)
    let headers: string[] = []
    let rows: any[][] = []

    if (isExcel) {
      const data = await file.arrayBuffer()
      if (!XLSX || typeof XLSX.read !== 'function') {
        throw new Error('Excel-Bibliothek (XLSX) steht nicht zur Verfügung.')
      }
      const wb = XLSX.read(data, { type: 'array' })
      const sheetName = wb.SheetNames?.[0]
      if (!sheetName) throw new Error('Kein Tabellenblatt in der Datei gefunden.')
      const sheet = wb.Sheets[sheetName]
      if (!XLSX.utils || typeof XLSX.utils.sheet_to_json !== 'function') {
        throw new Error('Excel-Dienstprogramme unvollständig geladen.')
      }
      const rawRows: any[][] = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' })
      if (rawRows.length < 2) {
        throw new Error('Die Datei enthält keine Datenzeilen (mindestens 1 Kopfzeile und 1 Datenzeile erforderlich).')
      }
      headers = rawRows[0].map((h: any) => String(h || '').trim())
      rows = rawRows.slice(1).filter((r: any[]) => r.some((c: any) => String(c || '').trim() !== ''))
    } else {
      const text = await file.text()
      if (!text || !text.trim()) {
        importError.value = 'Die ausgewählte Datei ist leer.'
        return
      }

      // Delimiter-Erkennung: Semikolon, Tab oder Komma
      const firstLine = text.split(/\r\n|\n|\r/)[0] || ''
      let delimiter = ','
      if ((firstLine.match(/;/g) || []).length > (firstLine.match(/,/g) || []).length) {
        delimiter = ';'
      } else if ((firstLine.match(/\t/g) || []).length > (firstLine.match(/,/g) || []).length) {
        delimiter = '\t'
      }

      const parsed = parseCSVString(text, delimiter)
      if (parsed.length < 2) {
        importError.value = 'Die CSV-Datei muss mindestens eine Kopfzeile und eine Datenzeile enthalten.'
        return
      }
      headers = parsed[0].map(h => String(h || '').trim())
      rows = parsed.slice(1).filter(r => r.some(cell => String(cell || '').trim().length > 0))
    }

    importHeaders.value = headers
    importParsedRows.value = rows

    // Auto-detect project columns
    const mapping: Record<number, string> = {}
    importHeaders.value.forEach((h, idx) => {
      const lower = h.toLowerCase().trim()
      if (!Object.values(mapping).includes('title') && (lower.includes('titel') || lower.includes('title') || lower.includes('projekt') || lower.includes('project') || lower.includes('name'))) {
        mapping[idx] = 'title'
      } else if (!Object.values(mapping).includes('status') && (lower.includes('status') || lower.includes('zustand') || lower.includes('state'))) {
        mapping[idx] = 'status'
      } else if (!Object.values(mapping).includes('visibility') && (lower.includes('sichtbar') || lower.includes('visibility') || lower.includes('zugriff'))) {
        mapping[idx] = 'visibility'
      } else if (!Object.values(mapping).includes('currency') && (lower.includes('währung') || lower.includes('waehrung') || lower.includes('currency') || lower.includes('valuta'))) {
        mapping[idx] = 'currency'
      } else if (!Object.values(mapping).includes('budget_hours') && (lower.includes('stunden') || lower.includes('hours') || lower.includes('zeitbudget') || lower.includes('aufwand'))) {
        mapping[idx] = 'budget_hours'
      } else if (!Object.values(mapping).includes('budget_amount') && (lower.includes('betrag') || lower.includes('amount') || lower.includes('budget') || lower.includes('kosten'))) {
        mapping[idx] = 'budget_amount'
      } else if (!Object.values(mapping).includes('action:create_task') && (lower.includes('aufgabe') || lower.includes('aufgaben') || lower.includes('task') || lower.includes('tasks') || lower.includes('todo'))) {
        mapping[idx] = 'action:create_task'
      } else {
        // 1. Benutzerdefinierte Felder dieses Ordners erkennen
        const matchField = fields.value.find((f: any) => {
          const fLbl = f.label_key && typeof t === 'function' ? t(f.label_key).toLowerCase() : (f.label || '').toLowerCase()
          return fLbl === lower || (f.label || '').toLowerCase() === lower || f.field_key?.toLowerCase() === lower
        })
        if (matchField) {
          mapping[idx] = 'custom:' + matchField.field_key
        } else {
          // 2. Häufige Vorlagen-Felder erkennen (Parameter tpl verhindert Shadowing der i18n t-Funktion)
          const matchTpl = commonCustomFieldTemplates.find((tpl: any) => {
            const tLbl = tpl.label_key && typeof t === 'function' ? t(tpl.label_key).toLowerCase() : (tpl.label || '').toLowerCase()
            return tpl.key.toLowerCase() === lower || tLbl === lower || (tpl.label || '').toLowerCase() === lower || lower.includes(tpl.key)
          })
          if (matchTpl) {
            mapping[idx] = 'custom:' + matchTpl.key
          } else {
            // 3. Als neues Zusatzfeld mit Spaltennamen anbieten
            const sanitizeFn = typeof getHeaderKey === 'function' ? getHeaderKey : (s: string) => String(s || '').trim().toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '') || 'feld'
            mapping[idx] = 'custom:' + sanitizeFn(h)
          }
        }
      }
    })
    importColumnMapping.value = mapping
  } catch (err: any) {
    importError.value = 'Fehler beim Lesen der Excel/CSV-Datei: ' + (err.message || err)
  }
}

const resetNewProjectForm = () => {
  newProjectTitle.value = ''
  newProjectVisibility.value = 'private'
  newProjectCustomData.value = {}
  selectedTemplateId.value = currentFolderTemplate.value ? currentFolderTemplate.value.id : null
  selectedTemplateLists.value = currentFolderTemplate.value ? [...currentFolderTemplate.value.lists] : []
  newTemplatePhaseInput.value = ''
  importFileName.value = ''
  importHeaders.value = []
  importParsedRows.value = []
  importColumnMapping.value = {}
  importError.value = ''
  projectCreationMode.value = 'template'
}

const openNewProjectModal = () => {
  showNewProjectModal.value = true
  resetNewProjectForm()
  projectModalError.value = ''
  fetchTemplates()
}

const openImportProjectModal = () => {
  showNewProjectModal.value = true
  resetNewProjectForm()
  projectCreationMode.value = 'import'
  projectModalError.value = ''
}

const closeNewProjectModal = () => {
  showNewProjectModal.value = false
  projectModalError.value = ''
}

const loadFolderData = async () => {
  loading.value = true
  try {
    const res = await $fetch<any>(`/api/folders/${folderId}`, {
      headers: authHeaders()
    })
    folder.value = res.folder
    projects.value = res.projects || []
    fields.value = res.fields || []
    timeSummary.value = res.timeSummary || null
    if (res.folder?.settings?.default_sections && Array.isArray(res.folder.settings.default_sections) && res.folder.settings.default_sections.length > 0) {
      importWorkflowSections.value = res.folder.settings.default_sections.map((s: any) => typeof s === 'string' ? s : (s.title || ''))
    }
    await Promise.all([loadFolderJournals(), loadFolderContacts()])
  } catch (err: any) {
    if (err.statusCode === 404 || err.statusCode === 401) {
      navigateTo('/dashboard')
    }
  } finally {
    loading.value = false
  }
}

const createProject = async () => {
  projectModalError.value = ''
  creatingProject.value = true
  try {
    if (projectCreationMode.value === 'import') {
      const titleColIdxStr = Object.entries(importColumnMapping.value).find(([_, f]) => f === 'title')?.[0]
      if (titleColIdxStr === undefined) {
        throw new Error('Bitte weise mindestens einer Spalte das Pflichtfeld "Projekttitel" zu.')
      }
      const titleColIdx = parseInt(titleColIdxStr)
      const statusColIdxStr = Object.entries(importColumnMapping.value).find(([_, f]) => f === 'status')?.[0]
      const statusColIdx = statusColIdxStr !== undefined ? parseInt(statusColIdxStr) : null
      const visColIdxStr = Object.entries(importColumnMapping.value).find(([_, f]) => f === 'visibility')?.[0]
      const visColIdx = visColIdxStr !== undefined ? parseInt(visColIdxStr) : null
      const currColIdxStr = Object.entries(importColumnMapping.value).find(([_, f]) => f === 'currency')?.[0]
      const currColIdx = currColIdxStr !== undefined ? parseInt(currColIdxStr) : null
      const bhColIdxStr = Object.entries(importColumnMapping.value).find(([_, f]) => f === 'budget_hours')?.[0]
      const bhColIdx = bhColIdxStr !== undefined ? parseInt(bhColIdxStr) : null
      const baColIdxStr = Object.entries(importColumnMapping.value).find(([_, f]) => f === 'budget_amount')?.[0]
      const baColIdx = baColIdxStr !== undefined ? parseInt(baColIdxStr) : null

      const taskColIndices = Object.entries(importColumnMapping.value)
        .filter(([_, f]) => f === 'action:create_task')
        .map(([idx]) => parseInt(idx))

      const projectsToImport: any[] = []
      for (const row of importParsedRows.value) {
        const pTitle = String(row[titleColIdx] || '').trim()
        if (!pTitle) continue

        let pStatus = 'active'
        if (statusColIdx !== null) {
          const s = String(row[statusColIdx] || '').toLowerCase().trim()
          if (s.includes('arch') || s.includes('archiv')) pStatus = 'archived'
          else if (s.includes('comp') || s.includes('erledigt') || s.includes('abgeschlossen') || s.includes('fertig')) pStatus = 'completed'
          else if (s.includes('hold') || s.includes('paus') || s.includes('wart')) pStatus = 'on_hold'
          else pStatus = 'active'
        }

        let pVis = 'private'
        if (visColIdx !== null) {
          const v = String(row[visColIdx] || '').toLowerCase().trim()
          if (v.includes('comp') || v.includes('firm') || v.includes('unternehm') || v.includes('team') || v.includes('publ') || v.includes('öffentlich')) {
            pVis = user.value?.company_id ? 'company' : 'private'
          } else {
            pVis = 'private'
          }
        }

        let pCurr = 'CHF'
        if (currColIdx !== null) {
          const c = String(row[currColIdx] || '').trim().toUpperCase()
          if (c) pCurr = c
        }

        let pBh: number | null = null
        if (bhColIdx !== null) {
          const rawH = String(row[bhColIdx] || '').replace(/[^0-9.,]/g, '').replace(',', '.')
          if (rawH && !isNaN(Number(rawH))) pBh = Number(rawH)
        }

        let pBa: number | null = null
        if (baColIdx !== null) {
          const rawA = String(row[baColIdx] || '').replace(/[^0-9.,]/g, '').replace(',', '.')
          if (rawA && !isNaN(Number(rawA))) pBa = Number(rawA)
        }

        const pCustomData: Record<string, any> = {}
        for (const [colIdxStr, targetField] of Object.entries(importColumnMapping.value)) {
          if (!targetField || !targetField.startsWith('custom:')) continue
          const cellVal = String(row[parseInt(colIdxStr)] || '').trim()
          if (!cellVal) continue
          const key = targetField.replace('custom:', '')
          pCustomData[key] = cellVal
        }

        const projectTasks: string[] = []
        for (const tColIdx of taskColIndices) {
          const rawTaskVal = String(row[tColIdx] || '').trim()
          if (rawTaskVal) {
            const splitLines = rawTaskVal.split(/\r?\n/).map(l => l.trim()).filter(Boolean)
            projectTasks.push(...splitLines)
          }
        }

        projectsToImport.push({
          folder_id: folderId,
          title: pTitle,
          status: pStatus,
          visibility: pVis,
          currency: pCurr,
          budget_hours: pBh,
          budget_amount: pBa,
          custom_data: pCustomData,
          tasks: projectTasks
        })
      }

      if (projectsToImport.length === 0) {
        throw new Error('Keine gültigen Projekte in der Datei gefunden.')
      }

      // Felddefinitionen für neu gemappte Zusatzfelder an Server übermitteln (entity_type: 'project')
      const customFieldDefsToCreate: any[] = []
      for (const [colIdxStr, targetField] of Object.entries(importColumnMapping.value)) {
        if (!targetField || !targetField.startsWith('custom:')) continue
        const colIdx = parseInt(colIdxStr)
        const key = targetField.replace('custom:', '')
        const headerName = importHeaders.value[colIdx] || key
        const alreadyExists = fields.value.some((f: any) => f.field_key === key)
        if (!alreadyExists && !customFieldDefsToCreate.some(f => f.field_key === key)) {
          const matchedTpl = commonCustomFieldTemplates.find((tpl: any) => tpl.key === key)
          customFieldDefsToCreate.push({
            field_key: key,
            label: matchedTpl?.label || headerName,
            label_key: matchedTpl?.label_key || null,
            field_type: matchedTpl?.type || 'text',
            entity_type: 'project',
            options: (matchedTpl as any)?.options || []
          })
        }
      }

      await $fetch<any>('/api/projects', {
        method: 'POST',
        headers: authHeaders(),
        body: {
          folder_id: folderId,
          projects: projectsToImport,
          custom_field_definitions: customFieldDefsToCreate,
          sections: importWorkflowSections.value
        }
      })

      showNewProjectModal.value = false
      resetNewProjectForm()
      await loadFolderData()
      alert(`${projectsToImport.length} Projekt(e) erfolgreich importiert!`)
      return
    }

    // Standard Einzel-Projekt Erstellung (Vorlage / Blanko)
    const payload: any = {
      folder_id: folderId,
      title: newProjectTitle.value.trim(),
      visibility: newProjectVisibility.value,
      custom_data: newProjectCustomData.value
    }

    if (projectCreationMode.value === 'template') {
      if (selectedTemplateId.value) {
        payload.template_id = selectedTemplateId.value
      }
      if (selectedTemplateLists.value.length > 0) {
        payload.custom_lists = selectedTemplateLists.value
      }
    } else if (projectCreationMode.value === 'blank' && folder.value?.settings?.default_sections?.length) {
      payload.custom_lists = folder.value.settings.default_sections
    }

    const res = await $fetch<any>('/api/projects', {
      method: 'POST',
      headers: authHeaders(),
      body: payload
    })

    showNewProjectModal.value = false
    resetNewProjectForm()
    await loadFolderData()
    if (res?.project?.id) {
      navigateTo(`/projects/${res.project.id}`)
    }
  } catch (err: any) {
    projectModalError.value = err.data?.statusMessage || err.message || 'Projekt konnte nicht erstellt werden'
  } finally {
    creatingProject.value = false
  }
}

onMounted(async () => {
  // Free-/Single-User (ohne Company) haben keine Ordner-Ebene.
  // Direkter Aufruf einer Ordner-URL wird auf das Dashboard umgeleitet.
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (user.value && !user.value.is_pro && !user.value.company_id && !user.value.is_superadmin) {
    navigateTo('/dashboard')
    return
  }
  await loadFolderData()
})
</script>
