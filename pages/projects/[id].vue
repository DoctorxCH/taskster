<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
      <NuxtLink to="/dashboard" class="hover:text-[#0891B2] transition-colors flex items-center gap-1">
        <LayoutDashboard class="w-3.5 h-3.5" />
        <span>Dashboard</span>
      </NuxtLink>
      <span>/</span>
      <template v-if="!isFreeUser">
        <NuxtLink :to="`/folders/${project?.folder_id}`" class="hover:text-[#0891B2] transition-colors flex items-center gap-1">
          <Folder class="w-3.5 h-3.5" />
          <span>{{ project?.folder_name || 'Ordner' }}</span>
        </NuxtLink>
        <span>/</span>
      </template>
      <span class="text-slate-800 font-semibold flex items-center gap-1">
        <ClipboardList class="w-3.5 h-3.5 text-[#0891B2]" />
        <span>{{ project?.title || 'Projekt' }}</span>
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-600 font-medium text-sm bg-white border border-slate-200 rounded-lg max-w-sm mx-auto">
      Lade Projektdaten...
    </div>

    <div v-else-if="project" class="space-y-6">
      <!-- Project Header -->
      <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <!-- Left: Title & Badges -->
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
              <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ project.title }}</h1>

              <!-- Segmented Control: Kanban vs. Projektjournal -->
              <div class="inline-flex items-center p-0.5 bg-slate-100 border border-slate-200 rounded-lg ml-2 shadow-2xs">
                <button
                  type="button"
                  @click="currentView = 'tasks'"
                  class="flex items-center space-x-1.5 px-3 py-1 rounded-md text-xs font-bold transition cursor-pointer"
                  :class="currentView === 'tasks' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  <LayoutGrid class="w-3.5 h-3.5" />
                  <span>{{ $t('journal.tab_kanban') }}</span>
                </button>
                <button
                  type="button"
                  @click="currentView = 'journal'; loadJournals()"
                  class="flex items-center space-x-1.5 px-3 py-1 rounded-md text-xs font-bold transition cursor-pointer"
                  :class="currentView === 'journal' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  <BookOpen class="w-3.5 h-3.5" />
                  <span>{{ $t('journal.tab_journal') }}</span>
                  <span
                    v-if="journalEntries.length > 0"
                    class="px-1.5 py-0.2 rounded-full text-[10px] font-black"
                    :class="currentView === 'journal' ? 'bg-cyan-100 text-[#00A3C4]' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ journalEntries.length }}
                  </span>
                </button>
              </div>

              <span
                class="px-2 py-0.5 rounded text-xs font-semibold border capitalize"
                :class="userRole === 'viewer' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-cyan-50 text-[#0891B2] border-cyan-200'"
              >
                {{ userRole }}
              </span>
              <span
                class="px-2 py-0.5 rounded text-xs font-semibold border flex items-center space-x-1"
                :class="project.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-700'"
              >
                <Building2 v-if="project.visibility === 'company'" class="w-3 h-3 text-emerald-600 inline mr-0.5" />
                <Lock v-else class="w-3 h-3 text-slate-500 inline mr-0.5" />
                <span>{{ project.visibility === 'company' ? 'Unternehmen' : 'Privat' }}</span>
              </span>
              <span
                class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200"
              >
                Status: {{ project.status }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 uppercase"
              >
                {{ project.currency || 'CHF' }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded text-xs font-semibold bg-cyan-50 text-[#0891B2] border border-cyan-200 flex items-center space-x-1"
                :title="`Erfasste Zeit: ${project.tracked_hours || 0} Std. ${project.budget_hours ? `/ Budget: ${project.budget_hours} Std.` : ''}`"
              >
                <Clock class="w-3.5 h-3.5 text-[#0891B2]" />
                <span>{{ project.tracked_hours || 0 }}h</span>
                <span v-if="project.budget_hours" class="text-slate-500 font-normal">/ {{ project.budget_hours }}h</span>
                <span v-if="project.budget_hours > 0" class="text-[10px] px-1.5 py-0.2 rounded font-bold ml-1"
                  :class="(project.tracked_hours || 0) > project.budget_hours ? 'bg-rose-100 text-rose-700' : ((project.tracked_hours || 0) / project.budget_hours >= 0.8 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')"
                >
                  {{ Math.round(((project.tracked_hours || 0) / project.budget_hours) * 100) }}%
                </span>
              </span>
            </div>

            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1">
              <span v-if="!isFreeUser">Ordner: <NuxtLink :to="`/folders/${project.folder_id}`" class="text-[#0891B2] font-semibold hover:underline">{{ project.folder_name }}</NuxtLink></span>
              <span v-if="project.company_name" class="text-slate-700 font-medium">• {{ project.company_name }}</span>
            </p>

            <!-- Project-level custom fields display in header -->
            <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-2 pt-2">
              <span
                v-for="(val, key) in project.custom_data"
                :key="key"
                class="inline-flex items-center text-xs px-2.5 py-1 rounded bg-slate-50 border border-slate-200 text-slate-800"
              >
                <span class="text-[#0891B2] font-semibold mr-1.5">{{ getFieldLabel(key) }}:</span>
                <span class="text-slate-900 font-semibold">{{ val }}</span>
              </span>
            </div>
          </div>

          <!-- Right: Actions (View, Stopwatch, Import, Voice, Section collapsed into More menu) -->
          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- Running Project Stopwatch (always visible so it can be stopped) -->
            <div
              v-if="userRole !== 'viewer' && stopwatchState.isRunning && stopwatchState.projectId === project?.id"
              class="flex items-center space-x-2 px-3 py-1 bg-slate-900 text-white rounded-md border border-cyan-400/60 shadow-xs h-9 select-none"
            >
              <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
              <div class="flex flex-col text-left leading-tight">
                <span class="text-[9px] font-semibold text-cyan-300 uppercase tracking-wider truncate max-w-[120px]">
                  {{ stopwatchState.taskId ? stopwatchState.taskTitle : 'Projekt' }}
                </span>
                <span class="font-mono font-bold text-xs text-white">
                  {{ formatSeconds(stopwatchState.elapsedSeconds) }}
                </span>
              </div>
              <button
                @click="openStopModal"
                type="button"
                class="ml-1 px-2 py-0.5 bg-rose-600 hover:bg-rose-500 text-white rounded text-[10px] font-bold cursor-pointer"
                title="Stoppuhr stoppen & buchen"
              >
                Stopp
              </button>
            </div>

            <!-- More Actions Dropdown -->
            <div v-if="userRole !== 'viewer'" class="relative">
              <button
                @click="showActionsMenu = !showActionsMenu"
                class="taskster_button_light px-3 text-xs h-9 rounded-md flex items-center space-x-1.5 cursor-pointer"
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
                  @click="taskViewMode = 'board'; showActionsMenu = false"
                  class="w-full text-left px-3 py-2 text-xs font-semibold flex items-center space-x-2 cursor-pointer"
                  :class="taskViewMode === 'board' ? 'text-[#0891B2] bg-cyan-50' : 'text-slate-700 hover:bg-slate-50'"
                >
                  <LayoutGrid class="w-4 h-4" />
                  <span>Kacheln</span>
                  <Check v-if="taskViewMode === 'board'" class="w-3.5 h-3.5 ml-auto" />
                </button>
                <button
                  @click="taskViewMode = 'table'; showActionsMenu = false"
                  class="w-full text-left px-3 py-2 text-xs font-semibold flex items-center space-x-2 cursor-pointer"
                  :class="taskViewMode === 'table' ? 'text-[#0891B2] bg-cyan-50' : 'text-slate-700 hover:bg-slate-50'"
                >
                  <List class="w-4 h-4" />
                  <span>Liste</span>
                  <Check v-if="taskViewMode === 'table'" class="w-3.5 h-3.5 ml-auto" />
                </button>

                <div class="my-1 border-t border-slate-100"></div>

                <!-- Aktionen -->
                <button
                  v-if="!(stopwatchState.isRunning && stopwatchState.projectId === project?.id)"
                  @click="showActionsMenu = false; startProjectTimer()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Clock class="w-4 h-4 text-slate-500" />
                  <span>Projekt-Stoppuhr starten</span>
                </button>
                <button
                  @click="showActionsMenu = false; openImportModal()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Upload class="w-4 h-4 text-slate-500" />
                  <span>Aufgaben importieren</span>
                </button>
                <button
                  @click="showActionsMenu = false; showVoiceModal = true"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Mic class="w-4 h-4 text-slate-500" />
                  <span>Sprachnotiz</span>
                </button>
                <button
                  @click="showActionsMenu = false; showNewListModal = true"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Plus class="w-4 h-4 text-slate-500" />
                  <span>Neuer Abschnitt</span>
                </button>
                <div v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin" class="my-1 border-t border-slate-100"></div>
                <button
                  v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin"
                  @click="showActionsMenu = false; openDeleteProjectModal()"
                  class="w-full text-left px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center space-x-2 cursor-pointer"
                >
                  <Trash2 class="w-4 h-4 text-rose-500" />
                  <span>{{ $t('dashboard.projekt_loeschen') }}</span>
                </button>
              </div>
            </div>

            <!-- New Task -->
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(lists[0]?.id)"
              :disabled="lists.length === 0"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>Aufgabe erfassen</span>
            </button>
          </div>
        </div>

        <!-- Navigation Tabs (Cleaned up, Lucide Icons, Single location for Settings) -->
        <div class="flex border-b border-slate-200 space-x-6 overflow-x-auto pt-1">
          <button
            @click="currentView = 'tasks'"
            class="py-2.5 text-xs border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'tasks' ? 'border-[#0891B2] text-[#0891B2] font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-semibold'"
          >
            <ClipboardList class="w-4 h-4" />
            <span>{{ $t('journal.tab_kanban') }} ({{ totalTasks }})</span>
          </button>

          <button
            @click="currentView = 'journal'; loadJournals()"
            class="py-2.5 text-xs border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'journal' ? 'border-[#0891B2] text-[#0891B2] font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-semibold'"
          >
            <BookOpen class="w-4 h-4" />
            <span>{{ $t('journal.tab_journal') }} ({{ journalEntries.length }})</span>
          </button>

          <button
            @click="currentView = 'time'; loadProjectTimeEntries()"
            class="py-2.5 text-xs border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'time' ? 'border-[#0891B2] text-[#0891B2] font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-semibold'"
          >
            <Clock class="w-4 h-4" />
            <span>Zeiterfassung ({{ projectTimeEntries.length || project.time_entry_count || 0 }})</span>
          </button>

          <button
            @click="currentView = 'team'"
            class="py-2.5 text-xs border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'team' ? 'border-[#0891B2] text-[#0891B2] font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-semibold'"
          >
            <Users class="w-4 h-4" />
            <span>Team & Berechtigungen ({{ members.length + 1 }})</span>
          </button>

          <button
            @click="currentView = 'contacts'; loadProjectContacts()"
            class="py-2.5 text-xs border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'contacts' ? 'border-[#0891B2] text-[#0891B2] font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-semibold'"
          >
            <Contact class="w-4 h-4" />
            <span>Kontakte ({{ projectContacts.length }})</span>
          </button>

          <button
            v-if="userRole === 'owner' || userRole === 'admin'"
            @click="currentView = 'settings'; initSettingsTab()"
            class="py-2.5 text-xs border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'settings' ? 'border-[#0891B2] text-[#0891B2] font-bold' : 'border-transparent text-slate-600 hover:text-slate-900 font-semibold'"
          >
            <Settings class="w-4 h-4" />
            <span>Projekt-Einstellungen</span>
          </button>
        </div>
      </div>

      <!-- VIEW 1: TASKS & ABSCHNITTE -->
      <div v-if="currentView === 'tasks'">
        <!-- Viewer Notice Banner -->
        <div
          v-if="userRole === 'viewer'"
          class="mb-6 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center space-x-2"
        >
          <span>👁️</span>
          <span><strong>Viewer-Modus:</strong> Du besitzt Leserechte für dieses Projekt.</span>
        </div>

        <!-- Empty state -->
        <div v-if="lists.length === 0" class="text-center py-16 px-6 bg-white rounded-3xl border border-dashed border-slate-300 shadow-sm max-w-lg mx-auto">
          <span class="text-3xl">📋</span>
          <h3 class="text-base font-bold text-slate-800 mt-2">Noch keine Abschnitte in diesem Projekt</h3>
          <p class="text-xs text-slate-500 mt-1 mb-5">Erstelle den ersten Abschnitt (z.B. "Geplant", "In Bearbeitung", "Abgeschlossen").</p>
          <button
            v-if="userRole !== 'viewer'"
            @click="showNewListModal = true"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg"
          >
            + Ersten Abschnitt erstellen
          </button>
        </div>

        <!-- MODE A: BOARD (KANBAN MEISTERTASK-STYLE COLUMNS) -->
        <div v-else-if="taskViewMode === 'board'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 min-[2200px]:grid-cols-6 gap-6 items-start">
          <div
            v-for="(list, listIdx) in lists"
            :key="list.id"
            :ref="el => { if (el) sectionCols[listIdx] = el }"
            class="liquid_glass rounded-3xl p-4 flex flex-col transition-all duration-150 shadow-lg"
            :style="list.color ? { backgroundColor: list.color } : {}"
            :class="[
              dragOverListId === list.id ? 'border-cyan-500 ring-2 ring-cyan-500/30' : '',
              draggedBoardSection?.id === list.id ? 'opacity-40 border-dashed border-cyan-600 scale-[0.99]' : ''
            ]"
            @dragover.prevent="onDragOverList(list.id)"
            @dragleave="onDragLeaveList(list.id)"
            @drop="onDropToList(list.id)"
          >
            <!-- Column Header Color Bar / Title -->
            <div
              class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200/80 select-none group/hdr"
              :draggable="userRole !== 'viewer'"
              @dragstart="onSectionDragStart(list, $event)"
              @dragover.prevent="onSectionDragOver(list, $event)"
              @drop.stop="onSectionDrop(list, $event)"
            >
              <div class="flex items-center space-x-2">
                <span
                  v-if="userRole !== 'viewer'"
                  class="text-slate-400 hover:text-cyan-600 cursor-grab active:cursor-grabbing text-xs transition"
                  title="Abschnitt ziehen, um Spalte zu verschieben"
                >
                  ⋮⋮
                </span>
                <span class="w-3 h-3 rounded-full shadow-xs" :class="[
                  listIdx % 4 === 0 ? 'bg-[#00A3C4]' :
                  listIdx % 4 === 1 ? 'bg-amber-400' :
                  listIdx % 4 === 2 ? 'bg-purple-500' : 'bg-emerald-500'
                ]"></span>
                <h3 class="text-sm font-black text-slate-900">{{ getSectionTitle(list.title) }}</h3>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/90 text-slate-700 shadow-xs border border-slate-200/60">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>

              <div class="flex items-center space-x-1">
                <span
                  v-if="list.access_mode === 'custom'"
                  class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 border border-purple-200"
                >
                  🔒
                </span>
              </div>
            </div>

            <!-- Tasks in this section -->
            <div class="space-y-3 min-h-[60px] p-1 rounded-2xl transition-colors" :class="dragOverListId === list.id ? 'bg-cyan-50/70' : ''">
              <div
                v-for="task in list.tasks"
                :key="task.id"
                :draggable="userRole !== 'viewer'"
                class="relative bg-white border rounded-2xl p-4 transition-all duration-300 shadow-sm group select-none hover:shadow-md overflow-hidden"
                :class="[
                  highlightedTaskId === task.id ? 'ring-4 ring-cyan-400 border-cyan-500 shadow-xl bg-cyan-50/80 scale-[1.02]' : (draggedTask?.id === task.id ? 'opacity-40 border-dashed border-cyan-500 scale-[0.98]' : (stopwatchState.isRunning && stopwatchState.taskId === task.id ? 'ring-2 ring-cyan-500 border-cyan-400 shadow-md bg-cyan-50/20' : 'border-slate-200/90 hover:border-cyan-400')),
                  userRole !== 'viewer' ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer'
                ]"
                @dragstart="onDragStart(task, list.id)"
                @dragend="onDragEnd"
                @click="openTaskDrawer(task)"
              >
                <!-- Live Running Stopwatch on this card -->
                <div
                  v-if="stopwatchState.isRunning && stopwatchState.taskId === task.id"
                  class="mb-2.5 px-2.5 py-1.5 rounded-xl bg-slate-950 text-white flex items-center justify-between shadow-sm animate-in fade-in"
                >
                  <div class="flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-[9px] uppercase font-bold text-cyan-400">Läuft:</span>
                    <span class="font-mono font-black text-xs text-cyan-200">{{ formatSeconds(stopwatchState.elapsedSeconds) }}</span>
                  </div>
                  <button
                    type="button"
                    @click.stop="openStopModal"
                    class="px-2 py-0.5 bg-rose-600 hover:bg-rose-500 text-white rounded text-[9px] font-bold shadow-xs transition"
                    title="Stoppen & Zeit buchen"
                  >
                    ⏹️ Stoppen
                  </button>
                </div>

                <!-- Drag handle & Task Header -->
                <div class="flex items-start justify-between gap-2 mb-2">
                  <div class="flex items-start space-x-2">
                    <!-- Checkbox to toggle done (Viewer & everyone can toggle!) -->
                    <button
                      type="button"
                      @click.stop="toggleTaskCompleted(task)"
                      class="w-4 h-4 rounded border flex items-center justify-center transition cursor-pointer shrink-0 mt-0.5"
                      :class="task.status === 'done' ? 'bg-emerald-500 border-emerald-600 text-white shadow-xs' : 'border-slate-300 hover:border-cyan-500 bg-white'"
                      title="Aufgabe abhaken / Status ändern"
                    >
                      <span v-if="task.status === 'done'" class="text-[10px] font-black leading-none">✓</span>
                    </button>

                    <span
                      v-if="userRole !== 'viewer'"
                      class="text-slate-300 group-hover:text-slate-500 text-xs mt-0.5 cursor-grab"
                      title="Ziehen zum Verschieben"
                    >
                      ⋮⋮
                    </span>
                    <span
                      class="text-xs font-bold group-hover:text-cyan-700 transition leading-snug"
                      :class="task.status === 'done' ? 'line-through text-slate-400' : 'text-slate-800'"
                    >
                      {{ task.title }}
                    </span>
                  </div>
                  <span
                    class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full whitespace-nowrap"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border border-emerald-200': task.status === 'done',
                      'bg-cyan-50 text-cyan-700 border border-cyan-200': task.status === 'in_progress',
                      'bg-amber-50 text-amber-700 border border-amber-200': task.status === 'review',
                      'bg-slate-100 text-slate-600': task.status === 'todo'
                    }"
                  >
                    {{ task.status }}
                  </span>
                </div>

                <p v-if="task.description" class="text-[11px] text-slate-500 line-clamp-2 mb-3 leading-relaxed">
                  {{ task.description }}
                </p>

                <!-- Task Custom Fields Chips -->
                <div v-if="task.custom_data && Object.keys(task.custom_data).length > 0" class="flex flex-wrap gap-1 mb-2.5">
                  <span
                    v-for="(val, key) in task.custom_data"
                    :key="key"
                    class="text-[9px] font-medium px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200 text-slate-600"
                  >
                    {{ getFieldLabel(key) }}: <strong class="text-slate-800">{{ formatCustomFieldValue(val) }}</strong>
                  </span>
                </div>

                <div class="flex items-center justify-between text-[10px] text-slate-400 pt-2 border-t border-slate-100">
                  <div class="flex items-center gap-2">
                    <span v-if="task.due_date" class="flex items-center space-x-1 font-medium text-slate-600">
                      <span>📅</span>
                      <span>{{ new Date(task.due_date).toLocaleDateString('de-CH') }}</span>
                    </span>
                    <!-- Assignee Avatar Stack (Mehrfachzuweisung) -->
                    <div v-if="getTaskAssignees(task).length > 0" class="flex -space-x-1.5 overflow-hidden">
                      <div
                        v-for="u in getTaskAssignees(task).slice(0, 3)"
                        :key="u.user_id"
                        class="inline-block w-5 h-5 rounded-full ring-1 ring-white bg-gradient-to-tr from-cyan-600 to-teal-500 text-white text-[9px] font-black flex items-center justify-center shrink-0 overflow-hidden"
                        :title="u.name || u.email"
                      >
                        <img v-if="user?.avatar || u.avatar" :src="user?.avatar || u.avatar" class="w-full h-full object-cover" />
                        <span v-else>{{ (user?.name || user?.email || '?').charAt(0).toUpperCase() }}</span>
                      </div>
                      <span
                        v-if="getTaskAssignees(task).length > 3"
                        class="inline-block w-5 h-5 rounded-full ring-1 ring-white bg-slate-200 text-slate-700 text-[8px] font-black flex items-center justify-center shrink-0"
                      >
                        +{{ getTaskAssignees(task).length - 3 }}
                      </span>
                    </div>
                    <!-- Priority Badge -->
                    <span
                      v-if="task.priority && task.priority !== 'normal'"
                      class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded"
                      :class="{
                        'bg-rose-100 text-rose-700': task.priority === 'dringend',
                        'bg-amber-100 text-amber-700': task.priority === 'hoch',
                        'bg-slate-100 text-slate-500': task.priority === 'niedrig'
                      }"
                    >
                      {{ task.priority }}
                    </span>

                    <!-- Time & Budget Badge -->
                    <span
                      v-if="(task.tracked_hours || 0) > 0 || (task.budget_hours || 0) > 0"
                      class="inline-flex items-center space-x-0.5 text-[9px] font-bold px-1.5 py-0.5 rounded border"
                      :class="(task.tracked_hours || 0) > (task.budget_hours || 0) && task.budget_hours > 0 ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-cyan-50 text-cyan-800 border-cyan-200'"
                      :title="`Erfasst: ${task.tracked_hours || 0} Std. ${task.budget_hours ? `/ Budget: ${task.budget_hours} Std.` : ''}`"
                    >
                      <span>⏱️</span>
                      <span>{{ task.tracked_hours || 0 }}h</span>
                      <span v-if="task.budget_hours" class="text-slate-400">/{{ task.budget_hours }}h</span>
                    </span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      v-if="userRole !== 'viewer' && (!stopwatchState.isRunning || stopwatchState.taskId !== task.id)"
                      type="button"
                      @click.stop="startTaskTimer(task)"
                      class="text-slate-400 hover:text-cyan-700 font-bold flex items-center space-x-1 px-1.5 py-0.5 rounded hover:bg-cyan-50 transition"
                      title="Stoppuhr auf diese Aufgabe starten"
                    >
                      <span>⏱️</span>
                      <span class="text-[10px]">Start</span>
                    </button>
                    <span class="text-cyan-600 font-bold group-hover:translate-x-0.5 transition-transform">Details →</span>
                  </div>
                </div>

                <!-- Color stripe at bottom of card -->
                <div
                  v-if="task.color"
                  class="absolute bottom-0 left-0 right-0 h-1 rounded-b-2xl"
                  :style="{backgroundColor: task.color}"
                ></div>
              </div>

              <div
                v-if="!list.tasks || list.tasks.length === 0"
                class="p-6 text-center text-xs font-semibold text-slate-400 border-2 border-dashed border-slate-200/90 rounded-2xl bg-white/70"
              >
                Noch keine Aufgaben
              </div>
            </div>

            <!-- Add Task Button in Section -->
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(list.id)"
              class="mt-3 py-2 px-3 rounded-xl border border-dashed border-slate-300 hover:border-cyan-500 bg-white/70 hover:bg-white text-xs font-black text-slate-700 hover:text-cyan-800 transition text-center shadow-xs"
            >
              + Aufgabe hinzufügen
            </button>
          </div>
        </div>

        <!-- MODE B: TABLE / LIST (TABULAR MEISTERTASK VIEW) -->
        <div v-else-if="taskViewMode === 'table'" class="space-y-6">
          <div
            v-for="list in lists"
            :key="list.id"
            class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm"
          >
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
              <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-cyan-500"></span>
                <h3 class="text-sm font-black text-slate-900">{{ getSectionTitle(list.title) }}</h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-white text-slate-600 font-bold border border-slate-200">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>
              <button
                v-if="userRole !== 'viewer'"
                @click="openNewTaskModal(list.id)"
                class="text-xs font-bold text-[#00A3C4] hover:text-[#008ba8] hover:underline"
              >
                + Aufgabe erfassen
              </button>
            </div>

            <div v-if="!list.tasks || list.tasks.length === 0" class="p-4 text-center text-xs text-slate-400">
              Keine Aufgaben in diesem Abschnitt.
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b border-slate-200">
                  <tr>
                    <th class="py-2.5 px-4">Titel & Beschreibung</th>
                    <th class="py-2.5 px-4">Status</th>
                    <th class="py-2.5 px-4">Aufwand & Budget</th>
                    <th class="py-2.5 px-4">Fälligkeit</th>
                    <th class="py-2.5 px-4">Felder</th>
                    <th class="py-2.5 px-4 text-right">Aktion</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                  <tr
                    v-for="task in list.tasks"
                    :key="task.id"
                    class="hover:bg-slate-50 transition cursor-pointer"
                    @click="openEditTaskModal(task)"
                  >
                    <td class="py-3 px-4">
                      <div class="flex items-center space-x-2.5">
                        <button
                          type="button"
                          @click.stop="toggleTaskCompleted(task)"
                          class="w-4 h-4 rounded border flex items-center justify-center transition cursor-pointer shrink-0"
                          :class="task.status === 'done' ? 'bg-emerald-500 border-emerald-600 text-white shadow-xs' : 'border-slate-300 hover:border-cyan-500 bg-white'"
                          title="Aufgabe abhaken / Status ändern"
                        >
                          <span v-if="task.status === 'done'" class="text-[10px] font-black leading-none">✓</span>
                        </button>
                        <div class="min-w-0">
                          <div class="font-bold truncate" :class="task.status === 'done' ? 'line-through text-slate-400' : 'text-slate-900'">
                            {{ task.title }}
                          </div>
                          <div v-if="task.description" class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                            {{ task.description }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                        :class="{
                          'bg-emerald-50 text-emerald-700 border border-emerald-200': task.status === 'done',
                          'bg-cyan-50 text-cyan-700 border border-cyan-200': task.status === 'in_progress',
                          'bg-amber-50 text-amber-700 border border-amber-200': task.status === 'review',
                          'bg-slate-100 text-slate-600': task.status === 'todo'
                        }"
                      >
                        {{ task.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4">
                      <div class="flex items-center space-x-1.5">
                        <span class="font-bold text-slate-900">⏱️ {{ task.tracked_hours || 0 }} Std.</span>
                        <span v-if="task.budget_hours" class="text-[10px] text-slate-500 font-medium">/ {{ task.budget_hours }} Std.</span>
                      </div>
                      <div v-if="task.budget_hours > 0" class="w-20 bg-slate-200 rounded-full h-1.5 mt-1 overflow-hidden">
                        <div
                          class="h-1.5 rounded-full"
                          :class="(task.tracked_hours || 0) > task.budget_hours ? 'bg-rose-500' : 'bg-cyan-600'"
                          :style="{ width: Math.min(100, Math.round(((task.tracked_hours || 0) / task.budget_hours) * 100)) + '%' }"
                        ></div>
                      </div>
                      <!-- Live Timer in Table row if active -->
                      <div v-if="stopwatchState.isRunning && stopwatchState.taskId === task.id" class="mt-1.5 inline-flex items-center space-x-1.5 px-2 py-0.5 rounded-md bg-slate-950 text-white text-[10px] font-mono font-bold shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                        <span class="text-cyan-300">{{ formatSeconds(stopwatchState.elapsedSeconds) }}</span>
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <span v-if="task.due_date" class="text-slate-800 font-medium">
                        {{ new Date(task.due_date).toLocaleDateString('de-CH') }}
                      </span>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    <td class="py-3 px-4">
                      <div v-if="task.custom_data && Object.keys(task.custom_data).length > 0" class="flex flex-wrap gap-1">
                        <span
                          v-for="(val, key) in task.custom_data"
                          :key="key"
                          class="text-[9px] px-1.5 py-0.5 rounded-md bg-slate-50 border border-slate-200 text-slate-600"
                        >
                          {{ getFieldLabel(key) }}: <strong class="text-slate-800">{{ formatCustomFieldValue(val) }}</strong>
                        </span>
                      </div>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <div class="inline-flex items-center space-x-2">
                        <button
                          v-if="userRole !== 'viewer' && stopwatchState.isRunning && stopwatchState.taskId === task.id"
                          type="button"
                          @click.stop="openStopModal"
                          class="px-2.5 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-[10px] font-bold shadow-xs transition"
                          title="Stoppuhr anhalten & Zeit buchen"
                        >
                          ⏹️ Stoppen
                        </button>
                        <button
                          v-else-if="userRole !== 'viewer'"
                          type="button"
                          @click.stop="startTaskTimer(task)"
                          class="p-1 rounded text-slate-400 hover:text-cyan-700 hover:bg-cyan-50 text-xs font-bold transition"
                          title="Stoppuhr auf diese Aufgabe starten"
                        >
                          ⏱️
                        </button>
                        <span class="text-xs text-cyan-700 font-bold hover:underline">Öffnen →</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW 2: JOURNAL & NOTIZEN -->
      <div v-else-if="currentView === 'journal'" class="space-y-6">
        <!-- Top Action Card -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 p-6 rounded-3xl shadow-sm">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-xl">📖</span>
              <h3 class="text-base font-black text-slate-900">{{ $t('journal.title') }}</h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ $t('journal.subtitle') }}</p>
          </div>
          <div v-if="userRole !== 'viewer'" class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button
              @click="openNewEntryModal"
              type="button"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5 cursor-pointer shadow-xs"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>{{ $t('journal.new_entry') }}</span>
            </button>
            <button
              @click="openNewNoteModal"
              type="button"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5 cursor-pointer"
            >
              <FileText class="w-3.5 h-3.5 text-slate-600" />
              <span>{{ $t('journal.new_note') }}</span>
            </button>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white border border-slate-200 rounded-2xl p-3.5 flex flex-col md:flex-row items-center justify-between gap-3 shadow-xs">
          <!-- Type Filter Tabs -->
          <div class="flex items-center p-1 bg-slate-100 rounded-xl space-x-1 w-full md:w-auto">
            <button
              type="button"
              @click="journalFilterType = 'all'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex-1 md:flex-initial"
              :class="journalFilterType === 'all' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              {{ $t('journal.filter_all') }} ({{ journalEntries.length }})
            </button>
            <button
              type="button"
              @click="journalFilterType = 'entry'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex-1 md:flex-initial"
              :class="journalFilterType === 'entry' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              {{ $t('journal.filter_entries') }} ({{ entriesCount }})
            </button>
            <button
              type="button"
              @click="journalFilterType = 'note'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer flex-1 md:flex-initial"
              :class="journalFilterType === 'note' ? 'bg-white text-[#00A3C4] shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              {{ $t('journal.filter_notes') }} ({{ notesCount }})
            </button>
          </div>

          <!-- Category dropdown & Search -->
          <div class="flex items-center gap-2 w-full md:w-auto">
            <select
              v-model="journalFilterCategory"
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-[#00A3C4]"
            >
              <option value="all">Alle Kategorien</option>
              <option value="bausitzung">{{ $t('journal.category_bausitzung') }}</option>
              <option value="bautagebuch">{{ $t('journal.category_bautagebuch') }}</option>
              <option value="abnahmebegehung">{{ $t('journal.category_abnahmebegehung') }}</option>
              <option value="wetter_behinderung">{{ $t('journal.category_wetter_behinderung') }}</option>
              <option value="regie">{{ $t('journal.category_regie') }}</option>
              <option value="email">{{ $t('journal.category_email') }}</option>
              <option value="allgemein">{{ $t('journal.category_allgemein') }}</option>
            </select>

            <div class="relative flex-1 md:w-56">
              <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" />
              <input
                v-model="journalSearchQuery"
                type="text"
                :placeholder="$t('journal.search_placeholder')"
                class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
              />
              <button
                v-if="journalSearchQuery"
                @click="journalSearchQuery = ''"
                class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 text-xs font-bold"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredJournals.length === 0" class="text-center py-16 px-6 bg-white rounded-3xl border border-dashed border-slate-300 shadow-xs max-w-lg mx-auto">
          <span class="text-4xl">📖</span>
          <h3 class="text-base font-bold text-slate-800 mt-2">{{ $t('journal.no_entries') }}</h3>
          <p class="text-xs text-slate-500 mt-1 mb-5">Erfasse eine Bausitzung, ein Bautagebuch oder importiere eine E-Mail mit automatischer KI-Aktionserkennung.</p>
          <div v-if="userRole !== 'viewer'" class="flex items-center justify-center gap-3">
            <button
              @click="openNewEntryModal"
              type="button"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ $t('journal.new_entry') }}
            </button>
            <button
              @click="openNewNoteModal"
              type="button"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              {{ $t('journal.new_note') }}
            </button>
          </div>
        </div>

        <!-- Feed Timeline Cards -->
        <div v-else class="space-y-4">
          <div
            v-for="entry in filteredJournals"
            :key="entry.id"
            class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-7 shadow-xs hover:shadow-md transition-all relative overflow-hidden"
            :class="[
              entry.type === 'entry' ? 'border-l-4 border-l-[#00A3C4]' : (entry.category === 'email' ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-indigo-400')
            ]"
          >
            <!-- Card Header -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-3 pb-3 border-b border-slate-100">
              <div class="flex items-start space-x-3">
                <div class="text-2xl shrink-0 mt-0.5">
                  {{ getCategoryBadge(entry.category, entry.type).icon }}
                </div>
                <div>
                  <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h4 class="text-base font-bold text-slate-900">{{ entry.title }}</h4>
                    <span
                      class="text-[10px] font-bold uppercase px-2.5 py-0.5 rounded-full border"
                      :class="getCategoryBadge(entry.category, entry.type).bg"
                    >
                      {{ getCategoryBadge(entry.category, entry.type).label }}
                    </span>
                  </div>
                  <div class="text-xs text-slate-400 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                    <span>Von <strong class="text-slate-700 font-semibold">{{ entry.author_name }}</strong></span>
                    <span>•</span>
                    <span>{{ new Date(entry.created_at).toLocaleString('de-CH', { dateStyle: 'medium', timeStyle: 'short' }) }}</span>
                    <span v-if="entry.task_title" class="text-[#00A3C4] font-semibold flex items-center space-x-1">
                      <span>• Verknüpft: {{ entry.task_title }}</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Right: Visibility Badge, AI Trigger, Bulk Apply & Delete -->
              <div class="flex items-center space-x-2 shrink-0 self-end sm:self-start">
                <button
                  v-if="userRole !== 'viewer'"
                  type="button"
                  @click="triggerAiAnalysis(entry)"
                  :disabled="analyzingEntryId === entry.id"
                  class="text-[11px] font-bold px-2.5 py-1 rounded-lg border flex items-center space-x-1 transition cursor-pointer"
                  :class="entry.metadata?.ai_summary
                    ? 'bg-slate-50 hover:bg-cyan-50 border-slate-200 text-slate-700 hover:text-[#00A3C4]'
                    : 'bg-gradient-to-r from-cyan-500 to-teal-500 hover:from-cyan-600 hover:to-teal-600 text-white shadow-xs border-transparent'"
                  :title="entry.metadata?.ai_summary ? 'KI-Analyse erneut ausführen' : 'Mit KI analysieren'"
                >
                  <Sparkles class="w-3 h-3" :class="{ 'animate-spin': analyzingEntryId === entry.id }" />
                  <span>{{ analyzingEntryId === entry.id ? 'Analysiere...' : (entry.metadata?.ai_summary ? 'KI aktualisieren' : '⚡ KI-Analyse') }}</span>
                </button>

                <!-- Direct Sync Button if pending actions exist -->
                <button
                  v-if="userRole !== 'viewer' && entry.metadata?.action_items && entry.metadata.action_items.some(it => !it.applied)"
                  type="button"
                  @click="applyAllTaskActions(entry)"
                  :disabled="isApplyingAllId === entry.id"
                  class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs flex items-center space-x-1 transition cursor-pointer"
                  title="Alle erkannten Aufgaben ins Kanban-Board übertragen"
                >
                  <CheckCircle2 class="w-3 h-3" :class="{ 'animate-spin': isApplyingAllId === entry.id }" />
                  <span>{{ isApplyingAllId === entry.id ? 'Synchronisiere...' : '⚡ Aktionen ins Board (' + entry.metadata.action_items.filter(it => !it.applied).length + ')' }}</span>
                </button>

                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full border flex items-center space-x-1"
                  :class="getVisibilityBadge(entry.visibility, entry.allowed_group_id).bg"
                  :title="entry.visibility"
                >
                  <span>{{ getVisibilityBadge(entry.visibility, entry.allowed_group_id).icon }}</span>
                  <span>{{ getVisibilityBadge(entry.visibility, entry.allowed_group_id).label }}</span>
                </span>
                <button
                  v-if="userRole === 'owner' || userRole === 'admin' || entry.user_id === user?.id || entry.author_id === user?.id || user?.is_superadmin"
                  @click="deleteJournalEntry(entry)"
                  type="button"
                  class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                  title="Eintrag löschen"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <!-- TYPE A: ATTENDEES BADGES (for Official Meeting Protocols) -->
            <div v-if="entry.type === 'entry' && entry.attendees && entry.attendees.length > 0" class="mb-4 p-3.5 bg-slate-50/80 rounded-2xl border border-slate-200/80">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center space-x-1.5">
                <Users class="w-3.5 h-3.5 text-[#00A3C4]" />
                <span>Teilnehmer ({{ entry.attendees.filter(a => a.present).length }} anwesend / {{ entry.attendees.length }} geladen):</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="atd in entry.attendees"
                  :key="atd.id || atd.name"
                  class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold border shadow-2xs"
                  :class="atd.present ? 'bg-white border-emerald-300 text-slate-800' : 'bg-slate-100/70 border-slate-200 text-slate-400 line-through'"
                >
                  <span
                    class="w-2 h-2 rounded-full shrink-0"
                    :class="atd.present ? 'bg-emerald-500' : 'bg-slate-300'"
                  ></span>
                  <span>{{ atd.name }}</span>
                  <span v-if="atd.role" class="text-[10px] text-slate-500 font-normal no-underline">({{ atd.role }})</span>
                  <span v-if="!atd.present" class="text-[9px] font-bold uppercase text-slate-400 no-underline ml-1">Abwesend</span>
                </div>
              </div>
            </div>

            <!-- EMAIL SENDER DETAILS (if sender info exists) -->
            <div v-if="entry.metadata?.sender?.email" class="mb-3 p-3 bg-amber-50/60 rounded-xl border border-amber-200/70 text-xs text-amber-950 flex flex-wrap items-center gap-x-4 gap-y-1">
              <span class="font-bold flex items-center space-x-1">
                <Mail class="w-3.5 h-3.5 text-amber-700" />
                <span>Von: {{ entry.metadata.sender.name || entry.metadata.sender.email }} &lt;{{ entry.metadata.sender.email }}&gt;</span>
              </span>
              <span v-if="entry.metadata.sender.role" class="text-amber-800 font-normal">• {{ entry.metadata.sender.role }}</span>
            </div>

            <!-- AI SUMMARY & INTERACTIVE ACTION CARDS (available for ALL journal entries!) -->
            <div v-if="entry.metadata?.ai_summary || (entry.metadata?.action_items && entry.metadata.action_items.length > 0)" class="mb-4 space-y-3">
              <!-- AI Summary Box -->
              <div v-if="entry.metadata?.ai_summary" class="p-4 rounded-2xl bg-gradient-to-r from-cyan-50/90 via-teal-50/60 to-blue-50/80 border border-cyan-200/90 shadow-2xs">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                  <div class="flex items-center space-x-1.5 text-xs font-black text-cyan-950">
                    <Sparkles class="w-4 h-4 text-[#00A3C4] shrink-0" />
                    <span>{{ $t('journal.ai_summary') }}</span>
                    <span class="text-[10px] font-semibold px-2 py-0.2 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-300 ml-1">KI-Agent</span>
                  </div>
                  <button
                    v-if="userRole !== 'viewer'"
                    type="button"
                    @click="triggerAiAnalysis(entry)"
                    :disabled="analyzingEntryId === entry.id"
                    class="text-[10px] font-bold text-cyan-800 hover:text-cyan-950 hover:underline flex items-center space-x-1 cursor-pointer"
                  >
                    <Sparkles class="w-3 h-3" :class="{ 'animate-spin': analyzingEntryId === entry.id }" />
                    <span>{{ analyzingEntryId === entry.id ? 'Aktualisiere...' : 'Neu analysieren' }}</span>
                  </button>
                </div>
                <p class="text-xs text-slate-800 leading-relaxed">
                  {{ entry.metadata.ai_summary }}
                </p>
              </div>

              <!-- Notice if no action items were generated -->
              <div v-if="entry.metadata?.ai_summary && (!entry.metadata?.action_items || entry.metadata.action_items.length === 0)" class="p-3 bg-slate-50/90 rounded-2xl border border-slate-200/80 text-[11px] text-slate-500 flex items-center justify-between">
                <span>ℹ️ Keine direkten Aufgabenaktionen im Text erkannt. Du kannst oben auf „KI aktualisieren“ klicken oder Aufgaben manuell anlegen.</span>
              </div>

              <!-- Interactive AI Action Cards -->
              <div v-if="entry.metadata?.action_items && entry.metadata.action_items.length > 0" class="space-y-2.5 pt-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="text-[11px] font-bold uppercase tracking-wider text-slate-600 flex items-center space-x-1.5">
                    <span>⚡</span>
                    <span>{{ $t('journal.ai_suggested_actions') }} ({{ entry.metadata.action_items.length }}):</span>
                  </div>
                  <button
                    v-if="userRole !== 'viewer' && entry.metadata.action_items.some(it => !it.applied)"
                    type="button"
                    @click="applyAllTaskActions(entry)"
                    :disabled="isApplyingAllId === entry.id"
                    class="text-xs font-bold px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-xs flex items-center space-x-1.5 transition cursor-pointer"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5" :class="{ 'animate-spin': isApplyingAllId === entry.id }" />
                    <span>{{ isApplyingAllId === entry.id ? 'Wende an...' : '⚡ Alle ' + entry.metadata.action_items.filter(it => !it.applied).length + ' Aktionen ins Kanban-Board übernehmen' }}</span>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                  <div
                    v-for="(item, idx) in entry.metadata.action_items"
                    :key="idx"
                    class="p-3.5 rounded-2xl border transition shadow-xs flex flex-col justify-between"
                    :class="[
                      item.applied ? 'bg-slate-50 border-slate-200 opacity-80' : (
                        item.type === 'create_task' ? 'bg-emerald-50/70 border-emerald-200 hover:border-emerald-400' :
                        item.type === 'update_task' ? 'bg-amber-50/70 border-amber-200 hover:border-amber-400' :
                        'bg-purple-50/70 border-purple-200 hover:border-purple-400'
                      )
                    ]"
                  >
                    <div>
                      <div class="flex items-center justify-between gap-1 mb-1.5">
                        <span
                          class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full border"
                          :class="[
                            item.type === 'create_task' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                            item.type === 'update_task' ? 'bg-amber-100 text-amber-800 border-amber-300' :
                            'bg-purple-100 text-purple-800 border-purple-300'
                          ]"
                        >
                          {{ item.type === 'create_task' ? '+ Neue Aufgabe' : (item.type === 'update_task' ? '✏️ Aktualisierung' : '✓ Abschliessen') }}
                        </span>

                        <span v-if="item.applied" class="text-[10px] font-bold text-emerald-700 flex items-center space-x-1">
                          <CheckCircle2 class="w-3 h-3" />
                          <span>{{ $t('journal.action_applied') }}</span>
                        </span>
                      </div>

                      <h5 class="text-xs font-bold text-slate-900 leading-snug mb-1">
                        {{ item.title || getTaskTitle(item.task_id) }}
                      </h5>

                      <p v-if="item.description || item.reason" class="text-[11px] text-slate-600 line-clamp-2 mb-2 leading-relaxed">
                        {{ item.description || item.reason }}
                      </p>

                      <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-semibold text-slate-500 mb-2">
                        <span v-if="item.section_id" class="px-1.5 py-0.5 rounded bg-white/90 border border-slate-200">
                          📁 {{ getSectionTitle(item.section_id) }}
                        </span>
                        <span v-if="item.due_date || item.suggested_due_date" class="px-1.5 py-0.5 rounded bg-white/90 border border-slate-200">
                          📅 {{ item.due_date || item.suggested_due_date }}
                        </span>
                        <span v-if="item.priority" class="px-1.5 py-0.5 rounded bg-white/90 border border-slate-200 uppercase">
                          ⚡ {{ item.priority }}
                        </span>
                      </div>
                    </div>

                    <div v-if="!item.applied && userRole !== 'viewer'" class="pt-2 border-t border-slate-200/60 mt-auto">
                      <button
                        v-if="item.type === 'create_task'"
                        type="button"
                        :disabled="item.applying"
                        @click="applyCreateTaskAction(entry, item, idx)"
                        class="w-full py-1.5 px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition flex items-center justify-center space-x-1 cursor-pointer"
                      >
                        <Plus class="w-3 h-3" />
                        <span>{{ item.applying ? 'Wird angelegt...' : $t('journal.action_create_task') }}</span>
                      </button>

                      <button
                        v-else-if="item.type === 'update_task'"
                        type="button"
                        :disabled="item.applying"
                        @click="applyUpdateTaskAction(entry, item, idx)"
                        class="w-full py-1.5 px-3 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-xs transition flex items-center justify-center space-x-1 cursor-pointer"
                      >
                        <Pencil class="w-3 h-3" />
                        <span>{{ item.applying ? 'Wird aktualisiert...' : $t('journal.action_update_task') }}</span>
                      </button>

                      <button
                        v-else-if="item.type === 'complete_task'"
                        type="button"
                        :disabled="item.applying"
                        @click="applyCompleteTaskAction(entry, item, idx)"
                        class="w-full py-1.5 px-3 rounded-lg text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white shadow-xs transition flex items-center justify-center space-x-1 cursor-pointer"
                      >
                        <Check class="w-3 h-3" />
                        <span>{{ item.applying ? 'Wird erledigt...' : $t('journal.action_complete_task') }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Content Area (Text) -->
            <div class="mt-2">
              <div v-if="entry.type === 'note' && entry.category === 'email'" class="mb-2">
                <button
                  type="button"
                  @click="expandedMailIds[entry.id] = !expandedMailIds[entry.id]"
                  class="text-[11px] font-bold text-[#00A3C4] hover:underline flex items-center space-x-1 cursor-pointer"
                >
                  <span>{{ expandedMailIds[entry.id] ? 'E-Mail Text verbergen ▲' : 'Vollständigen E-Mail Text anzeigen ▼' }}</span>
                </button>
              </div>

              <div
                v-if="entry.type !== 'note' || entry.category !== 'email' || expandedMailIds[entry.id]"
                class="text-xs text-slate-700 leading-relaxed bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80 whitespace-pre-wrap font-sans"
              >
                {{ entry.content }}
              </div>
            </div>

            <!-- Attachments Gallery -->
            <div v-if="entry.attachments && entry.attachments.length > 0" class="mt-3.5 pt-3 border-t border-slate-100">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center space-x-1">
                <Paperclip class="w-3 h-3 text-slate-400" />
                <span>Dateianhänge ({{ entry.attachments.length }}):</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <div
                  v-for="att in entry.attachments"
                  :key="att.id"
                  class="flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-cyan-50/60 border border-slate-200 hover:border-cyan-300 transition text-xs group"
                >
                  <span class="text-sm">{{ getFileIcon(att.file_type) }}</span>
                  <div class="min-w-0 max-w-[160px]">
                    <a
                      :href="att.file_path"
                      :download="att.file_name"
                      target="_blank"
                      class="font-semibold text-slate-800 hover:text-[#00A3C4] truncate block"
                      :title="att.file_name"
                    >
                      {{ att.file_name }}
                    </a>
                    <span class="text-[10px] text-slate-400">{{ formatFileSize(att.file_size) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW 3: TEAM & BERECHTIGUNGEN -->
      <div v-else-if="currentView === 'team'" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900">Projektteam & Berechtigungen</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Steuerung von Editor- und Viewer-Rollen für dieses Projekt.
            </p>
          </div>
          <button
            v-if="userRole === 'owner' || userRole === 'admin'"
            @click="showInviteMemberModal = true"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg"
          >
            + Mitglied einladen
          </button>
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-200">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-full bg-cyan-100 text-cyan-800 font-bold flex items-center justify-center text-xs border border-cyan-200">
                PO
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">{{ project.folder_name }} Owner</div>
                <div class="text-[11px] text-slate-500">Projektinhaber (Voller administrativer Zugriff)</div>
              </div>
            </div>
            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-200">
              PROJECT OWNER
            </span>
          </div>

          <div
            v-for="m in members"
            :key="m.id"
            class="flex items-center justify-between p-4 rounded-2xl bg-white border border-slate-200 shadow-sm"
          >
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs">
                {{ m.name.charAt(0) }}
              </div>
              <div>
                <div class="text-xs font-bold text-slate-900">{{ m.name }}</div>
                <div class="text-[11px] text-slate-500">{{ m.email }}</div>
              </div>
            </div>
            <span
              class="text-xs font-bold uppercase px-2.5 py-0.5 rounded-full"
              :class="m.role === 'editor' ? 'bg-cyan-50 text-cyan-800 border border-cyan-200' : 'bg-amber-50 text-amber-800 border border-amber-200'"
            >
              {{ m.role }}
            </span>
          </div>
        </div>
      </div>

      <!-- VIEW 4: PROJEKT-EINSTELLUNGEN & BENUTZERDEFINIERTE FELDER -->
      <div v-else-if="currentView === 'settings'" class="space-y-8">
        <!-- Card 1: Projekt-Stammdaten & Projekt-Felder -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-black text-slate-900">Allgemeine Projekt-Einstellungen</h3>
              <p class="text-xs text-slate-500">Passe den Projektnamen, den Status und projektweite Eigenschaften an.</p>
            </div>
          </div>

          <form @submit.prevent="saveProjectSettings" class="space-y-4 max-w-xl">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Projekttitel</label>
              <input
                v-model="settingsForm.title"
                type="text"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Projekt-Status</label>
              <select
                v-model="settingsForm.status"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
              >
                <option value="active">Aktiv (Active)</option>
                <option value="on_hold">Pausiert (On Hold)</option>
                <option value="completed">Abgeschlossen (Completed)</option>
              </select>
            </div>

            <!-- Sichtbarkeit im Unternehmen (Default: Privat) -->
            <div v-if="user?.company_id" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
              <label class="block text-xs font-bold text-slate-800">Sichtbarkeit des Projekts</label>
              <div class="grid grid-cols-2 gap-2">
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition text-xs font-semibold"
                  :class="settingsForm.visibility === 'private' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
                >
                  <input type="radio" value="private" v-model="settingsForm.visibility" class="sr-only" />
                  <span>🔒 Privat (Standard)</span>
                </label>
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition text-xs font-semibold"
                  :class="settingsForm.visibility === 'company' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
                >
                  <input type="radio" value="company" v-model="settingsForm.visibility" class="sr-only" />
                  <span>🏢 Unternehmen</span>
                </label>
              </div>
              <p class="text-[11px] text-slate-500">
                {{ settingsForm.visibility === 'private' ? 'Privates Projekt. Nur für dich und explizit zugewiesene Mitglieder sichtbar (auch Admins sehen dieses Projekt nicht).' : 'Für alle Mitglieder im Unternehmen sichtbar.' }}
              </p>
            </div>

            <!-- Währung & Budget-Einstellungen -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Projekt-Währung</label>
                <select
                  v-model="settingsForm.currency"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                >
                  <option value="CHF">CHF (Schweizer Franken)</option>
                  <option value="EUR">EUR (€)</option>
                  <option value="USD">USD ($)</option>
                  <option value="GBP">GBP (£)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Budget (Stunden)</label>
                <input
                  v-model="settingsForm.budget_hours"
                  type="number"
                  step="0.5"
                  min="0"
                  placeholder="z.B. 40"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Budget (Betrag)</label>
                <input
                  v-model="settingsForm.budget_amount"
                  type="number"
                  step="10"
                  min="0"
                  :placeholder="'z.B. 5000 ' + (settingsForm.currency || 'CHF')"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />
              </div>
            </div>

            <!-- Project-level Custom Fields Input -->
            <div v-if="projectCustomFields.length > 0" class="pt-4 border-t border-slate-100 space-y-3">
              <h4 class="text-xs font-bold text-cyan-800 uppercase tracking-wider">
                Projekt-Felder (Werte für dieses Projekt)
              </h4>
              <div v-for="f in projectCustomFields" :key="f.id">
                <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label_key ? $t(f.label_key) : f.label }}</label>

                <!-- Select -->
                <select
                  v-if="f.field_type === 'select'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
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
                  v-model="settingsForm.custom_data[f.field_key]"
                  rows="3"
                  placeholder="Details, Notizen oder Beschreibung..."
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 resize-y"
                ></textarea>

                <!-- Checkbox -->
                <div v-else-if="f.field_type === 'checkbox'" class="pt-1">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="settingsForm.custom_data[f.field_key]"
                      class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0 cursor-pointer"
                    />
                    <span class="text-xs font-medium text-slate-700">
                      {{ settingsForm.custom_data[f.field_key] ? '✓ Ja / Aktiv' : 'Nein / Inaktiv' }}
                    </span>
                  </label>
                </div>

                <!-- Date -->
                <input
                  v-else-if="f.field_type === 'date'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  type="date"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Number -->
                <input
                  v-else-if="f.field_type === 'number'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  type="number"
                  step="any"
                  placeholder="0.00"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- URL -->
                <input
                  v-else-if="f.field_type === 'url'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  type="url"
                  placeholder="https://..."
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Email -->
                <input
                  v-else-if="f.field_type === 'email'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  type="email"
                  placeholder="kontakt@firma.ch"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Phone -->
                <input
                  v-else-if="f.field_type === 'phone'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  type="tel"
                  placeholder="+41 79 123 45 67"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Default Text -->
                <input
                  v-else
                  v-model="settingsForm.custom_data[f.field_key]"
                  type="text"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />
              </div>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="savingProjectSettings"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg"
              >
                {{ savingProjectSettings ? 'Speichern...' : 'Projekt-Einstellungen speichern' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Card 2: Benutzerdefinierte Felder verwalten -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
            <div>
              <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
                <span>⚙️</span>
                <span>Benutzerdefinierte Felder & Logik</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Definiere eigene Attribute für Aufgaben oder für Projekte mit bedingter Sichtbarkeit.
              </p>
            </div>
            <button
              @click="openNewFieldModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              + Neues Feld anlegen
            </button>
          </div>

          <!-- Fields Table -->
          <div v-if="fields.length === 0" class="text-center py-8 text-xs text-slate-400">
            Noch keine benutzerdefinierten Felder angelegt.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-4">Feld-Bezeichnung</th>
                  <th class="py-2.5 px-4">Schlüssel (Key)</th>
                  <th class="py-2.5 px-4">Bereich / Typ</th>
                  <th class="py-2.5 px-4">Bedingte Logik</th>
                  <th class="py-2.5 px-4 text-right">Aktion</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="f in fields" :key="f.id" class="hover:bg-slate-50 transition">
                  <td class="py-3 px-4 font-bold text-slate-900">
                    {{ f.label_key ? $t(f.label_key) : f.label }}
                  </td>
                  <td class="py-3 px-4 font-mono text-cyan-700 text-[11px]">
                    {{ f.field_key }}
                  </td>
                  <td class="py-3 px-4">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase mr-1.5"
                      :class="f.entity_type === 'project' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-cyan-50 text-cyan-700 border border-cyan-200'"
                    >
                      {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                    </span>
                    <span class="text-slate-500 text-[11px]">({{ f.field_type }})</span>
                  </td>
                  <td class="py-3 px-4">
                    <span v-if="f.logic_rules && f.logic_rules.depends_on_field" class="text-[11px] text-amber-700 font-medium">
                      Nur wenn {{ f.logic_rules.depends_on_field }} == "{{ f.logic_rules.depends_on_value }}"
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="py-3 px-4 text-right whitespace-nowrap">
                    <button
                      @click="editField(f)"
                      class="text-cyan-600 hover:text-cyan-700 text-xs font-bold mr-3"
                    >
                      Bearbeiten
                    </button>
                    <button
                      @click="deleteField(f.id)"
                      class="text-rose-600 hover:text-rose-700 text-xs font-bold"
                    >
                      Löschen
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Card 3: Gefahrenzone / Projekt löschen -->
        <div v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin" class="bg-rose-50/50 border border-rose-200 rounded-3xl p-6 sm:p-8 shadow-xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-base font-black text-rose-900 flex items-center space-x-2">
                <AlertTriangle class="w-5 h-5 text-rose-600 shrink-0" />
                <span>{{ $t('dashboard.gefahrenzone') }}: {{ $t('dashboard.projekt_loeschen') }}</span>
              </h3>
              <p class="text-xs text-rose-800/90 mt-1 max-w-xl leading-relaxed">
                {{ $t('dashboard.gefahrenzone_projekt_desc') }}
              </p>
            </div>
            <button
              type="button"
              @click="openDeleteProjectModal"
              class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg shrink-0 flex items-center space-x-1.5 cursor-pointer"
            >
              <Trash2 class="w-4 h-4" />
              <span>{{ $t('dashboard.projekt_loeschen') }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- VIEW 5: ZEITERFASSUNG & AUDIT-PROTOKOLL -->
      <div v-else-if="currentView === 'time'" class="space-y-6">
      <!-- Header & Action Card -->
      <div class="bg-white border border-slate-200 p-6 sm:p-8 rounded-3xl shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span>⏱️</span>
              <span>Zeiterfassung & Controlling</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Erfasse Arbeitszeiten per Live-Stoppuhr oder manuell auf das Gesamtprojekt oder einzelne Aufgaben. Manuelle Einträge werden mit einem Stern (*) gekennzeichnet.
            </p>
          </div>
          <div v-if="userRole !== 'viewer'" class="flex flex-wrap items-center gap-2.5">
            <!-- Active Stopwatch Pill if running for this project -->
            <div
              v-if="stopwatchState.isRunning && stopwatchState.projectId === project?.id"
              class="flex items-center space-x-2 px-3 py-1 bg-slate-900 text-white rounded-lg border border-cyan-400/60 shadow-md h-[42px] select-none"
            >
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
              <div class="flex flex-col text-left leading-tight">
                <span class="text-[9px] font-bold text-cyan-300 uppercase tracking-wider truncate max-w-[130px]">
                  {{ stopwatchState.taskId ? ('📋 ' + stopwatchState.taskTitle) : '🏢 Projekt' }}
                </span>
                <span class="font-mono font-black text-xs text-white">
                  {{ formatSeconds(stopwatchState.elapsedSeconds) }}
                </span>
              </div>
              <button
                @click="openStopModal"
                type="button"
                class="ml-1 px-2.5 py-1 bg-rose-600 hover:bg-rose-500 text-white rounded text-[11px] font-black shadow-xs transition"
                title="Stoppuhr stoppen & buchen"
              >
                ⏹️ Stoppen
              </button>
            </div>

            <button
              v-else
              type="button"
              @click="startProjectTimer"
              class="taskster_button_light px-4 text-xs h-[42px] rounded-lg flex items-center space-x-2"
              title="Stoppuhr für dieses Projekt starten"
            >
              <span>⏱️</span>
              <span>Stoppuhr starten</span>
            </button>

            <button
              @click="openProjectTimeModal()"
              class="taskster_button px-5 text-xs h-[42px] rounded-lg flex items-center space-x-2"
            >
              <span>+</span>
              <span>Manuell erfassen</span>
            </button>
          </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
          <div class="p-4 rounded-2xl bg-cyan-50/50 border border-cyan-100">
            <span class="text-[10px] font-black uppercase tracking-wider text-cyan-800">Gesamtaufwand</span>
            <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
              {{ projectTimeSummary.totalHours || 0 }} <span class="text-xs font-bold text-slate-500">Std.</span>
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">
              {{ projectTimeSummary.totalMinutes || 0 }} Min. rapportiert
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">Stunden-Budget</span>
            <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
              {{ project?.budget_hours ? project.budget_hours + ' Std.' : 'Kein Limit' }}
            </div>
            <div v-if="project?.budget_hours" class="w-full bg-slate-200 rounded-full h-1.5 mt-2 overflow-hidden">
              <div
                class="h-1.5 rounded-full transition-all"
                :class="(projectTimeSummary.totalHours || 0) > project.budget_hours ? 'bg-rose-500' : 'bg-cyan-600'"
                :style="{ width: Math.min(100, Math.round(((projectTimeSummary.totalHours || 0) / project.budget_hours) * 100)) + '%' }"
              ></div>
            </div>
            <div v-if="project?.budget_hours" class="text-[10px] text-slate-500 mt-1">
              {{ Math.round(((projectTimeSummary.totalHours || 0) / project.budget_hours) * 100) }}% verbraucht
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-emerald-50/50 border border-emerald-100">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800">Gesamtkosten</span>
            <div class="text-xl sm:text-2xl font-black text-emerald-900 mt-1">
              {{ projectTimeSummary.totalCost?.toFixed(2) || '0.00' }} <span class="text-xs font-bold text-emerald-700">{{ project?.currency || 'CHF' }}</span>
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">
              Basierend auf Stundensätzen
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">Kosten-Budget</span>
            <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
              {{ project?.budget_amount ? project.budget_amount.toFixed(2) + ' ' + (project.currency || 'CHF') : 'Kein Limit' }}
            </div>
            <div v-if="project?.budget_amount" class="w-full bg-slate-200 rounded-full h-1.5 mt-2 overflow-hidden">
              <div
                class="h-1.5 rounded-full transition-all"
                :class="(projectTimeSummary.totalCost || 0) > project.budget_amount ? 'bg-rose-500' : 'bg-emerald-600'"
                :style="{ width: Math.min(100, Math.round(((projectTimeSummary.totalCost || 0) / project.budget_amount) * 100)) + '%' }"
              ></div>
            </div>
            <div v-if="project?.budget_amount" class="text-[10px] text-slate-500 mt-1">
              {{ Math.round(((projectTimeSummary.totalCost || 0) / project.budget_amount) * 100) }}% verbraucht
            </div>
          </div>
        </div>
      </div>

      <!-- Filter & Audit Protocol Table Card -->
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
        <!-- Filter bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100">
          <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Filter:</span>
            <select
              v-model="timeFilterTask"
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:border-cyan-600"
            >
              <option value="">Alle Buchungen (Projekt & Aufgaben)</option>
              <option value="__project__">Nur Gesamtprojekt (ohne Aufgabe)</option>
              <option v-for="t in allProjectTasks" :key="t.id" :value="t.id">
                Aufgabe: {{ t.title }}
              </option>
            </select>

            <select
              v-model="timeFilterUser"
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:outline-none focus:border-cyan-600"
            >
              <option value="">Alle Mitarbeiter</option>
              <option :value="user?.id">Ich ({{ user?.name || user?.email }})</option>
              <option v-for="m in members" :key="m.user_id" :value="m.user_id">
                {{ m.name || m.email }}
              </option>
            </select>
          </div>

          <div class="text-xs text-slate-500 font-bold">
            {{ filteredTimeEntries.length }} {{ filteredTimeEntries.length === 1 ? 'Eintrag' : 'Einträge' }}
          </div>
        </div>

        <!-- Entries Table -->
        <div v-if="loadingTimeEntries" class="text-center py-12 text-xs text-slate-400">
          Lade Zeiterfassungsdaten...
        </div>
        <div v-else-if="filteredTimeEntries.length === 0" class="text-center py-12 text-xs text-slate-400">
          Keine Zeiteinträge für diesen Filter vorhanden.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Datum</th>
                <th class="py-3 px-4">Wer</th>
                <th class="py-3 px-4">Rapportiert auf</th>
                <th class="py-3 px-4">Dauer</th>
                <th class="py-3 px-4">Stundensatz</th>
                <th class="py-3 px-4">Kosten</th>
                <th class="py-3 px-4">Tätigkeit / Notiz</th>
                <th v-if="userRole !== 'viewer'" class="py-3 px-4 text-right">Aktionen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="e in filteredTimeEntries" :key="e.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-semibold text-slate-900 whitespace-nowrap">
                  {{ e.entry_date ? new Date(e.entry_date).toLocaleDateString('de-CH') : '-' }}
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 rounded-full bg-cyan-100 text-cyan-800 text-[10px] font-black flex items-center justify-center">
                      {{ (e.user_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <span class="font-medium text-slate-800">{{ e.user_name }}</span>
                  </div>
                </td>
                <td class="py-3 px-4">
                  <span v-if="e.task_title" class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                    Aufgabe: {{ e.task_title }}
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                    Gesamtprojekt
                  </span>
                </td>
                <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-900">
                  <span>{{ (e.duration_minutes / 60).toFixed(1) }} Std.</span>
                  <span class="text-[10px] text-slate-500 font-normal ml-1">({{ e.duration_minutes }}m)</span>
                  <span v-if="e.is_manual" class="text-rose-600 font-black text-sm ml-0.5 select-none" title="Manuell erfasst oder angepasst">*</span>
                </td>
                <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                  {{ e.hourly_rate ? e.hourly_rate.toFixed(2) + ' ' + (project?.currency || 'CHF') : '-' }}
                </td>
                <td class="py-3 px-4 whitespace-nowrap font-bold text-emerald-800">
                  {{ e.cost ? e.cost.toFixed(2) + ' ' + (project?.currency || 'CHF') : '-' }}
                </td>
                <td class="py-3 px-4 max-w-xs truncate text-slate-600">
                  {{ e.description || '-' }}
                </td>
                <td v-if="userRole !== 'viewer'" class="py-3 px-4 text-right whitespace-nowrap space-x-2">
                  <button
                    v-if="e.user_id === user?.id || userRole === 'owner' || userRole === 'admin'"
                    @click="openEditTimeModal(e)"
                    class="text-cyan-700 hover:text-cyan-900 font-bold hover:underline"
                  >
                    Bearbeiten
                  </button>
                  <button
                    v-if="e.user_id === user?.id || userRole === 'owner' || userRole === 'admin'"
                    @click="deleteTimeEntry(e.id)"
                    class="text-rose-600 hover:text-rose-800 font-bold hover:underline"
                  >
                    Löschen
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VIEW 6: PROJEKT-KONTAKTE & BAUSTELLEN-ANSPRECHPARTNER -->
    <div v-else-if="currentView === 'contacts'" class="space-y-6">
      <div class="bg-white border border-slate-200 p-6 sm:p-8 rounded-3xl shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span>📇</span>
              <span>Kontakte & Ansprechpartner für dieses Projekt</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Handwerker, Bauleiter, Behörden und Planer, die diesem Projekt zugeordnet sind. Alle Projekt- und Ordnermitglieder haben automatisch Zugriff.
            </p>
          </div>
          <div class="flex items-center space-x-3 shrink-0">
            <button
              v-if="userRole !== 'viewer'"
              @click="openAddProjectContactModal"
              type="button"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-md"
            >
              <span>+ Kontakt anlegen</span>
            </button>
          </div>
        </div>

        <!-- Contact List / Cards -->
        <div v-if="loadingProjectContacts" class="py-16 text-center">
          <div class="inline-block animate-spin text-2xl mb-2">📇</div>
          <p class="text-xs font-bold text-slate-500">Lade Projektkontakte...</p>
        </div>

        <div v-else-if="projectContacts.length === 0" class="py-16 px-6 text-center">
          <div class="w-14 h-14 rounded-2xl bg-cyan-50 text-[#00A3C4] flex items-center justify-center text-2xl font-black mx-auto mb-3 shadow-xs">
            📇
          </div>
          <h4 class="text-sm font-black text-slate-800">Noch keine Kontakte für dieses Projekt</h4>
          <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">
            Hinterlege Poliere, Architekten oder Subunternehmer direkt für dieses Projekt. Sobald du das Projekt teilst, sehen alle Projektmitglieder diese Kontakte.
          </p>
          <button
            v-if="userRole !== 'viewer'"
            @click="openAddProjectContactModal"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg inline-flex items-center space-x-2"
          >
            <span>+ Kontakt anlegen</span>
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-4">
          <div
            v-for="c in projectContacts"
            :key="c.id"
            class="bg-slate-50/80 border border-slate-200/90 rounded-2xl p-4 flex flex-col justify-between hover:bg-white hover:shadow-md transition group"
          >
            <div>
              <div class="flex items-start justify-between gap-2 mb-2">
                <div class="flex items-start space-x-2.5 min-w-0">
                  <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#00A3C4] to-teal-500 text-white flex items-center justify-center font-black text-xs shadow-xs shrink-0">
                    {{ (c.first_name?.charAt(0) || '') + (c.last_name?.charAt(0) || '') }}
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs font-black text-slate-900 truncate">
                      {{ (c.first_name ? c.first_name + ' ' : '') + c.last_name }}
                    </h4>
                    <p v-if="c.company_name" class="text-[11px] font-bold text-cyan-800 truncate">
                      🏢 {{ c.company_name }}
                    </p>
                    <p v-if="c.role_function" class="text-[10px] font-semibold text-slate-600 truncate">
                      👷 {{ c.role_function }}
                    </p>
                  </div>
                </div>

                <span
                  class="shrink-0 px-2 py-0.5 rounded-full text-[9px] font-bold border"
                  :class="c.share_scope === 'company' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                >
                  {{ c.share_scope === 'company' ? '🏢 Team' : '🔒 Projekt' }}
                </span>
              </div>

              <!-- Details (Phone, Mobile, Email) -->
              <div class="space-y-1 text-[11px] bg-white p-2.5 rounded-xl border border-slate-100 mb-2">
                <div v-if="c.mobile" class="flex items-center space-x-1.5">
                  <span class="text-slate-400">📱</span>
                  <a :href="`tel:${c.mobile}`" class="font-bold text-[#00A3C4] hover:underline truncate">
                    {{ c.mobile }}
                  </a>
                </div>
                <div v-if="c.phone" class="flex items-center space-x-1.5">
                  <span class="text-slate-400">📞</span>
                  <a :href="`tel:${c.phone}`" class="text-slate-700 hover:underline truncate">
                    {{ c.phone }}
                  </a>
                </div>
                <div v-if="c.email" class="flex items-center space-x-1.5">
                  <span class="text-slate-400">✉️</span>
                  <a :href="`mailto:${c.email}`" class="text-cyan-800 font-semibold hover:underline truncate">
                    {{ c.email }}
                  </a>
                </div>
                <div v-if="!c.mobile && !c.phone && !c.email" class="text-[10px] text-slate-400 italic">
                  Keine Kontaktdaten hinterlegt
                </div>
              </div>

              <!-- Website & Address -->
              <div v-if="c.website || c.address" class="space-y-1 text-[11px] bg-white p-2.5 rounded-xl border border-slate-100 mb-2">
                <div v-if="c.website" class="flex items-center space-x-1.5">
                  <span class="text-slate-400">🌐</span>
                  <a :href="formatProjectContactUrl(c.website)" target="_blank" rel="noopener noreferrer" class="font-bold text-[#00A3C4] hover:underline truncate">
                    {{ c.website.replace(/^https?:\/\//i, '').replace(/\/$/, '') }}
                  </a>
                </div>
                <div v-if="c.address" class="flex items-start justify-between gap-1 pt-0.5">
                  <div class="flex items-start space-x-1.5 min-w-0">
                    <span class="text-slate-400 shrink-0 mt-0.5">📍</span>
                    <span class="text-slate-700 font-medium truncate">{{ c.address }}</span>
                  </div>
                  <a
                    :href="`https://www.openstreetmap.org/search?query=${encodeURIComponent(c.address)}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-[9px] font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 shrink-0 hover:bg-emerald-100"
                    title="In OpenStreetMap öffnen"
                  >
                    🗺️ Karte
                  </a>
                </div>
              </div>

              <p v-if="c.notes" class="text-[10px] text-slate-500 line-clamp-2 italic mb-2">
                "{{ c.notes }}"
              </p>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
              <button
                @click="exportContactVCard(c)"
                type="button"
                class="text-[10px] font-bold text-slate-600 hover:text-[#00A3C4] flex items-center space-x-1 py-0.5 px-1.5 rounded hover:bg-cyan-50"
                title="vCard herunterladen"
              >
                <span>📥</span>
                <span>vCard</span>
              </button>
              <div v-if="c.can_edit && userRole !== 'viewer'" class="flex items-center space-x-1">
                <button
                  @click="openEditProjectContactModal(c)"
                  type="button"
                  class="p-1 text-slate-400 hover:text-[#00A3C4] rounded transition text-xs font-bold"
                  title="Bearbeiten"
                >
                  ✏️
                </button>
                <button
                  @click="deleteProjectContact(c)"
                  type="button"
                  class="p-1 text-slate-400 hover:text-rose-600 rounded transition text-xs font-bold"
                  title="Löschen"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

    <!-- Modal: New Section (Abschnitt) -->
    <div v-if="showNewListModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-black text-slate-900 mb-1">Neuen Abschnitt anlegen</h3>
        <p class="text-xs text-slate-500 mb-4">
          Abschnitte gliedern dein Projekt in Phasen, Kategorien oder Workflow-Schritte.
        </p>

        <form @submit.prevent="createList" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Titel des Abschnitts</label>
            <input
              v-model="newListTitle"
              type="text"
              required
              placeholder="z.B. Vorbereitung, In Bearbeitung oder Abnahme"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Sichtbarkeits-Modus</label>
            <select
              v-model="newListAccessMode"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            >
              <option value="inherit">Standard (Alle Projektmitglieder haben Zugriff)</option>
              <option value="custom">Eingeschränkt (Nur Owner & explizit berechtigte Personen)</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showNewListModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Abschnitt anlegen
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Manage Sections (Drag & Drop, Rename, Delete, Reorder) -->
    <div v-if="showManageSectionsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col my-8">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-200 flex items-start justify-between bg-slate-50">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-xl">📋</span>
              <h3 class="text-lg font-black text-slate-900">Projekt-Abschnitte verwalten</h3>
            </div>
            <p class="text-xs text-slate-500 mt-1">
              Passe die Reihenfolge per Drag & Drop oder Pfeiltasten an, benenne Abschnitte um oder entferne Phasen.
            </p>
          </div>
          <button
            type="button"
            @click="showManageSectionsModal = false"
            class="text-slate-400 hover:text-slate-600 text-lg p-1 rounded-lg hover:bg-slate-200 transition"
          >
            ✕
          </button>
        </div>

        <div v-if="manageSectionsError" class="mx-6 mt-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ manageSectionsError }}
        </div>

        <div class="p-6 space-y-6">
          <!-- Sections List (Drag & Drop) -->
          <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
            <div
              v-for="(sec, idx) in managingSections"
              :key="sec.id || idx"
              draggable="true"
              @dragstart="onModalDragStart(idx, $event)"
              @dragover.prevent="onModalDragOver(idx, $event)"
              @drop="onModalDrop(idx, $event)"
              class="p-3.5 rounded-2xl border bg-white transition flex items-center justify-between gap-3 group shadow-sm"
              :class="draggedSectionModalIdx === idx ? 'border-cyan-500 bg-cyan-50/50 opacity-50' : 'border-slate-200 hover:border-slate-300'"
            >
              <!-- Drag Handle & Index -->
              <div class="flex items-center space-x-3">
                <span class="text-slate-400 hover:text-cyan-600 cursor-grab active:cursor-grabbing text-sm select-none" title="Ziehen zum Verschieben">⋮⋮</span>
                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center select-none">
                  {{ idx + 1 }}
                </span>
              </div>

              <!-- Title Input -->
              <div class="flex-1 flex items-center gap-2">
                <input
                  v-model="sec.title"
                  type="text"
                  required
                  placeholder="Abschnittsbezeichnung"
                  class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />

                <!-- Pastel Color Picker for Section -->
                <div class="flex items-center space-x-1 shrink-0">
                  <button
                    v-for="pc in sectionPastelColors"
                    :key="pc.value"
                    type="button"
                    @click="sec.color = sec.color === pc.value ? null : pc.value"
                    class="w-5 h-5 rounded-full border transition-transform hover:scale-115"
                    :style="{ backgroundColor: pc.value }"
                    :class="sec.color === pc.value ? 'border-slate-800 ring-2 ring-cyan-500 ring-offset-1 scale-110' : 'border-slate-300'"
                    :title="pc.label"
                  />
                  <button
                    v-if="sec.color"
                    type="button"
                    @click="sec.color = null"
                    class="text-[10px] text-slate-400 hover:text-slate-700 px-1"
                    title="Farbe zurücksetzen"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <!-- Task Count Badge -->
              <span class="text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full whitespace-nowrap">
                {{ sec.tasks?.length || sec.task_count || 0 }} Aufgaben
              </span>

              <!-- Action Controls: Up, Down, Delete -->
              <div class="flex items-center space-x-1">
                <button
                  type="button"
                  @click="moveSectionUp(idx)"
                  :disabled="idx === 0"
                  class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 disabled:opacity-20 disabled:cursor-not-allowed transition"
                  title="Nach oben verschieben"
                >
                  ⬆️
                </button>
                <button
                  type="button"
                  @click="moveSectionDown(idx)"
                  :disabled="idx === managingSections.length - 1"
                  class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 disabled:opacity-20 disabled:cursor-not-allowed transition"
                  title="Nach unten verschieben"
                >
                  ⬇️
                </button>
                <button
                  type="button"
                  @click="deleteSectionInModal(idx)"
                  class="p-1.5 rounded-lg hover:bg-rose-50 text-rose-600 hover:text-rose-700 transition ml-1"
                  title="Abschnitt löschen"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>

          <!-- Quick Add Section Row inside Modal -->
          <div class="pt-4 border-t border-slate-100 flex items-center gap-2">
            <input
              v-model="newSectionTitleInModal"
              type="text"
              placeholder="+ Weiterer Abschnitt (z.B. Zwischenprüfung, Abnahme)..."
              class="flex-1 px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
              @keyup.enter="addSectionInModal"
            />
            <button
              type="button"
              @click="addSectionInModal"
              :disabled="!newSectionTitleInModal.trim()"
              class="taskster_button px-4 text-xs h-[38px] rounded-lg"
            >
              + Hinzufügen
            </button>
          </div>

          <!-- Modal Footer Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showManageSectionsModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="button"
              @click="saveSectionsReorder"
              :disabled="savingSections"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ savingSections ? 'Wird gespeichert...' : 'Reihenfolge speichern' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: New Task / Edit Task -->
    <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">
            {{ isEditingTask ? 'Aufgabe bearbeiten' : 'Neue Aufgabe erfassen' }}
          </h3>
          <span v-if="userRole === 'viewer'" class="text-xs font-bold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
            Viewer Read-Only
          </span>
        </div>

        <form @submit.prevent="saveTask" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Aufgabentitel</label>
            <input
              v-model="taskForm.title"
              :disabled="userRole === 'viewer'"
              type="text"
              required
              placeholder="z.B. Konzeptentwurf finalisieren"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Beschreibung</label>
            <textarea
              v-model="taskForm.description"
              :disabled="userRole === 'viewer'"
              rows="3"
              placeholder="Detaillierte Aufgabenbeschreibung, Anforderungen oder Zwischenziele..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
              <select
                v-model="taskForm.status"
                :disabled="userRole === 'viewer'"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              >
                <option value="todo">Zu erledigen (Todo)</option>
                <option value="in_progress">In Arbeit (In Progress)</option>
                <option value="review">In Prüfung (Review)</option>
                <option value="done">Abgeschlossen (Done)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Fälligkeitsdatum</label>
              <input
                v-model="taskForm.due_date"
                :disabled="userRole === 'viewer'"
                type="date"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />
            </div>
          </div>

          <!-- Dynamic Task Custom Fields with Conditional Logic -->
          <div v-if="taskCustomFields.length > 0" class="pt-4 border-t border-slate-100 space-y-3">
            <h4 class="text-xs font-bold text-cyan-800 uppercase tracking-wider">
              Zusatzfelder
            </h4>
            <div
              v-for="f in taskCustomFields"
              :key="f.id"
              v-show="isFieldVisibleForTask(f)"
              class="transition-all"
            >
              <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label_key ? $t(f.label_key) : f.label }}</label>

              <!-- Select dropdown -->
              <select
                v-if="f.field_type === 'select'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
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
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                rows="3"
                placeholder="Längeren Text / Notizen eingeben..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60 resize-y"
              ></textarea>

              <!-- Checkbox (Ja / Nein) -->
              <div v-else-if="f.field_type === 'checkbox'" class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="taskForm.custom_data[f.field_key]"
                    :disabled="userRole === 'viewer'"
                    class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0 cursor-pointer"
                  />
                  <span class="text-xs font-semibold" :class="taskForm.custom_data[f.field_key] ? 'text-emerald-700' : 'text-slate-500'">
                    {{ taskForm.custom_data[f.field_key] ? '✓ Ja' : 'Nein' }}
                  </span>
                </label>
              </div>

              <!-- Date -->
              <input
                v-else-if="f.field_type === 'date'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="date"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />

              <!-- Number -->
              <input
                v-else-if="f.field_type === 'number'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="number"
                step="any"
                placeholder="0.00"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />

              <!-- URL -->
              <input
                v-else-if="f.field_type === 'url'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="url"
                placeholder="https://..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />

              <!-- Email -->
              <input
                v-else-if="f.field_type === 'email'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="email"
                placeholder="kontakt@firma.ch"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />

              <!-- Phone -->
              <input
                v-else-if="f.field_type === 'phone'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="tel"
                placeholder="+41 79 123 45 67"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />

              <!-- Default Text -->
              <input
                v-else
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="text"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              />
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <button
              v-if="isEditingTask && (userRole === 'owner' || userRole === 'admin')"
              type="button"
              @click="deleteTask"
              class="taskster_button_accent px-4 text-xs h-[38px] rounded-lg"
            >
              Löschen
            </button>
            <div v-else></div>

            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="showTaskModal = false"
                class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
              >
                Schliessen
              </button>
              <button
                v-if="userRole !== 'viewer'"
                type="submit"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg"
              >
                Speichern
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================
         TASK DETAIL MODAL (Großes zentriertes Popup – MeisterTask Style)
         ============================================================ -->
    <div
      v-if="showTaskDrawer"
      class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-slate-950/60 backdrop-blur-md overflow-hidden"
      @click.self="closeTaskDrawer"
    >
      <div
        class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-[94vw] max-w-[94vw] h-[94vh] max-h-[94vh] flex flex-col overflow-hidden relative my-auto animate-in fade-in zoom-in-95 duration-150"
      >
        <!-- Top color accent bar -->
        <div
          v-if="drawerTask?.color"
          class="h-2.5 w-full shrink-0 transition-colors"
          :style="{ backgroundColor: drawerTask.color }"
        ></div>

        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-200 bg-white flex flex-col gap-3 shrink-0">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <!-- Breadcrumbs -->
              <div class="flex items-center space-x-1.5 text-[11px] font-bold text-slate-500 mb-1.5 flex-wrap">
                <template v-if="!isFreeUser">
                  <span>📁 {{ project?.folder_name || 'Ordner' }}</span>
                  <span>/</span>
                </template>
                <span>📋 {{ project?.title || 'Projekt' }}</span>
                <span v-if="getTaskSectionTitle(drawerTask?.list_id)">/</span>
                <span v-if="getTaskSectionTitle(drawerTask?.list_id)" class="text-cyan-800 font-extrabold">
                  🏷️ {{ getTaskSectionTitle(drawerTask?.list_id) }}
                </span>
                <span v-if="isCreatingTaskInDrawer" class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-300">
                  Neu
                </span>
              </div>

              <!-- Title (Inline editierbar) -->
              <input
                v-if="drawerTask"
                v-model="drawerTask.title"
                @blur="autoSaveDrawer"
                @keyup.enter="autoSaveDrawer"
                :disabled="userRole === 'viewer'"
                class="w-full text-xl sm:text-2xl font-black text-slate-900 bg-transparent hover:bg-slate-50 focus:bg-white rounded-xl px-2 -mx-2 py-1 placeholder-slate-400 border border-transparent focus:border-[#00A3C4] focus:outline-none transition disabled:cursor-default"
                placeholder="Aufgabentitel eingeben..."
              />
            </div>

            <!-- Top-Right Actions: Tracked Hours + Stopwatch Start/Stop + Close -->
            <div class="flex items-center gap-2 shrink-0 self-start pt-1">
              <!-- Bisher erfasster Gesamtaufwand -->
              <div
                v-if="drawerTask"
                class="flex items-center space-x-1.5 px-3 py-1.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 shadow-2xs select-none"
                title="Bisher erfasster Gesamtaufwand auf dieser Aufgabe"
              >
                <span>⏱️</span>
                <span>{{ drawerTask.tracked_hours || 0 }} Std.</span>
                <span v-if="drawerTask.budget_hours" class="text-slate-400 font-normal">/ {{ drawerTask.budget_hours }}h</span>
              </div>

              <!-- Live Stoppuhr Steuerung (Start / Stopp) -->
              <div v-if="userRole !== 'viewer' && drawerTask?.id">
                <!-- Stoppuhr läuft aktiv auf DIESER Aufgabe -->
                <div
                  v-if="stopwatchState.isRunning && stopwatchState.taskId === drawerTask.id"
                  class="flex items-center space-x-1.5 px-2.5 py-1 bg-slate-950 text-white rounded-xl border border-cyan-400/60 shadow-xs h-[38px] select-none"
                >
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                  <span class="font-mono font-bold text-xs text-cyan-200">{{ formatSeconds(stopwatchState.elapsedSeconds) }}</span>
                  <button
                    type="button"
                    @click="openStopModal"
                    class="taskster_button_accent px-2.5 text-[11px] h-[28px] rounded-lg ml-1 font-bold shadow-xs flex items-center gap-1"
                    title="Stoppen & Buchen"
                  >
                    <span>⏹️</span>
                    <span>Stopp</span>
                  </button>
                  <button
                    type="button"
                    @click="discardTimer"
                    class="text-slate-400 hover:text-rose-400 p-1 text-xs transition"
                    title="Timer verwerfen"
                  >
                    ✕
                  </button>
                </div>

                <!-- Stoppuhr läuft auf ANDERER Aufgabe / Projekt -->
                <button
                  v-else-if="stopwatchState.isRunning"
                  type="button"
                  @click="startTaskTimer(drawerTask)"
                  class="px-3 text-xs h-[38px] rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 font-bold flex items-center gap-1.5 shadow-2xs transition"
                  title="Stoppuhr auf diese Aufgabe umschalten"
                >
                  <span>⚠️</span>
                  <span class="hidden sm:inline">Hierher wechseln</span>
                  <span class="sm:hidden">Wechseln</span>
                </button>

                <!-- Stoppuhr inaktiv: Start-Button -->
                <button
                  v-else
                  type="button"
                  @click="startTaskTimer(drawerTask)"
                  class="taskster_button px-3.5 text-xs h-[38px] rounded-xl flex items-center gap-1.5 shadow-xs"
                  title="Stoppuhr für diese Aufgabe starten"
                >
                  <span>⏱️</span>
                  <span>Start</span>
                </button>
              </div>

              <!-- Close button -->
              <button
                type="button"
                @click="closeTaskDrawer"
                class="text-slate-400 hover:text-slate-800 w-9 h-9 rounded-xl hover:bg-slate-100 flex items-center justify-center shrink-0 transition text-lg font-bold ml-1"
                title="Schliessen"
              >
                ✕
              </button>
            </div>
          </div>

        </div>

        <!-- Body: 2 Columns Grid -->
        <div v-if="drawerTask" class="flex-1 overflow-y-auto flex flex-col lg:flex-row divide-y lg:divide-y-0 lg:divide-x divide-slate-200">
          
          <!-- LEFT COLUMN: Main Content (Description, Checklist, Subtasks, Comments) -->
          <div class="flex-1 p-6 sm:p-8 space-y-6 min-w-0">
            
            <!-- Zusatzfelder im mitscrollenden Bereich -->
            <div v-if="drawerTask && visibleDrawerFields.length > 0" class="p-4 rounded-2xl bg-cyan-50/40 border border-cyan-100">
              <div class="text-[10px] font-black text-cyan-800 uppercase tracking-wider mb-2 flex items-center gap-1">
                <span>⚙️</span>
                <span>Zusatzfelder</span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                <div
                  v-for="f in visibleDrawerFields"
                  :key="f.id"
                  class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-2xs"
                  :class="f.field_type === 'textarea' ? 'sm:col-span-2 md:col-span-3' : ''"
                >
                  <label class="block text-[11px] font-bold text-slate-700 mb-1 truncate" :title="f.label_key ? $t(f.label_key) : f.label">
                    {{ f.label_key ? $t(f.label_key) : f.label }}<span v-if="f.is_required" class="text-rose-500 ml-0.5">*</span>
                  </label>

                  <!-- Select dropdown -->
                  <select
                    v-if="f.field_type === 'select'"
                    v-model="drawerTask.custom_data[f.field_key]"
                    @change="autoSaveDrawer"
                    :disabled="userRole === 'viewer'"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                  >
                    <option value="">-- Keine Auswahl --</option>
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
                    v-model="drawerTask.custom_data[f.field_key]"
                    @blur="autoSaveDrawer"
                    rows="3"
                    :disabled="userRole === 'viewer'"
                    placeholder="Details, Notizen oder Beschreibung..."
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs resize-y"
                  ></textarea>

                  <!-- Checkbox (Ja / Nein) -->
                  <div v-else-if="f.field_type === 'checkbox'" class="pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        v-model="drawerTask.custom_data[f.field_key]"
                        @change="autoSaveDrawer"
                        :disabled="userRole === 'viewer'"
                        class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0 cursor-pointer"
                      />
                      <span class="text-xs font-semibold" :class="drawerTask.custom_data[f.field_key] ? 'text-emerald-700' : 'text-slate-500'">
                        {{ drawerTask.custom_data[f.field_key] ? '✓ Ja' : 'Nein' }}
                      </span>
                    </label>
                  </div>

                  <!-- Date -->
                  <input
                    v-else-if="f.field_type === 'date'"
                    v-model="drawerTask.custom_data[f.field_key]"
                    @change="autoSaveDrawer"
                    type="date"
                    :disabled="userRole === 'viewer'"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                  />

                  <!-- Number -->
                  <input
                    v-else-if="f.field_type === 'number'"
                    v-model="drawerTask.custom_data[f.field_key]"
                    @blur="autoSaveDrawer"
                    type="number"
                    step="any"
                    :disabled="userRole === 'viewer'"
                    placeholder="0.00"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                  />

                  <!-- URL -->
                  <div v-else-if="f.field_type === 'url'" class="flex items-center gap-1.5">
                    <input
                      v-model="drawerTask.custom_data[f.field_key]"
                      @blur="autoSaveDrawer"
                      type="url"
                      placeholder="https://..."
                      :disabled="userRole === 'viewer'"
                      class="w-full min-w-0 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                    />
                    <a
                      v-if="drawerTask.custom_data[f.field_key]"
                      :href="drawerTask.custom_data[f.field_key]"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="p-1.5 rounded-lg bg-cyan-50 text-[#0891B2] hover:bg-cyan-100 transition shrink-0"
                      title="Link öffnen"
                    >
                      <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                  </div>

                  <!-- Email -->
                  <div v-else-if="f.field_type === 'email'" class="flex items-center gap-1.5">
                    <input
                      v-model="drawerTask.custom_data[f.field_key]"
                      @blur="autoSaveDrawer"
                      type="email"
                      placeholder="kontakt@beispiel.ch"
                      :disabled="userRole === 'viewer'"
                      class="w-full min-w-0 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                    />
                    <a
                      v-if="drawerTask.custom_data[f.field_key]"
                      :href="'mailto:' + drawerTask.custom_data[f.field_key]"
                      class="p-1.5 rounded-lg bg-cyan-50 text-[#0891B2] hover:bg-cyan-100 transition shrink-0"
                      title="E-Mail senden"
                    >
                      <Mail class="w-3.5 h-3.5" />
                    </a>
                  </div>

                  <!-- Phone -->
                  <div v-else-if="f.field_type === 'phone'" class="flex items-center gap-1.5">
                    <input
                      v-model="drawerTask.custom_data[f.field_key]"
                      @blur="autoSaveDrawer"
                      type="tel"
                      placeholder="+41 79 123 45 67"
                      :disabled="userRole === 'viewer'"
                      class="w-full min-w-0 px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                    />
                    <a
                      v-if="drawerTask.custom_data[f.field_key]"
                      :href="'tel:' + drawerTask.custom_data[f.field_key]"
                      class="p-1.5 rounded-lg bg-cyan-50 text-[#0891B2] hover:bg-cyan-100 transition shrink-0"
                      title="Anrufen"
                    >
                      <Phone class="w-3.5 h-3.5" />
                    </a>
                  </div>

                  <!-- Default Text -->
                  <input
                    v-else
                    v-model="drawerTask.custom_data[f.field_key]"
                    @blur="autoSaveDrawer"
                    :disabled="userRole === 'viewer'"
                    type="text"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-2xs"
                  />
                </div>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2 flex items-center space-x-1.5">
                <span>📋</span>
                <span>Beschreibung</span>
              </label>
              <textarea
                v-model="drawerTask.description"
                @blur="autoSaveDrawer"
                :disabled="userRole === 'viewer'"
                rows="4"
                placeholder="Detaillierte Aufgabenbeschreibung, Anforderungen oder Zwischenziele..."
                class="w-full px-4 py-3 bg-slate-50 hover:bg-slate-50/80 focus:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#00A3C4] focus:ring-2 focus:ring-cyan-500/20 transition resize-none disabled:cursor-default"
              ></textarea>
            </div>

            <!-- Checkliste & Unteraufgaben (integrierte Hierarchie) -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 space-y-4">
              <div class="flex items-center justify-between">
                <label class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                  <span>✅</span>
                  <span>Checkliste & Unteraufgaben</span>
                </label>
                <span v-if="drawerTask.checklist?.length || drawerSubtasks?.length" class="text-xs font-bold text-slate-600 bg-white px-2.5 py-0.5 rounded-full border border-slate-200 shadow-xs">
                  {{ (drawerTask.checklist || []).filter((c:any) => c.done).length + (drawerSubtasks || []).filter((s:any) => s.is_done).length }} /
                  {{ (drawerTask.checklist || []).length + (drawerSubtasks || []).length }} erledigt
                </span>
              </div>

              <!-- Overall progress bar -->
              <div v-if="(drawerTask.checklist?.length || 0) + (drawerSubtasks?.length || 0) > 0" class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                <div
                  class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                  :style="{
                    width: Math.round(
                      (((drawerTask.checklist || []).filter((c:any) => c.done).length + (drawerSubtasks || []).filter((s:any) => s.is_done).length) /
                      ((drawerTask.checklist || []).length + (drawerSubtasks || []).length)) * 100
                    ) + '%'
                  }"
                ></div>
              </div>

              <!-- Checklist Items & nested Subtasks -->
              <div class="space-y-2">
                <div v-if="!drawerTask.checklist?.length && !drawerSubtasks?.length" class="text-xs text-slate-400 italic text-center py-3 border border-dashed border-slate-200 rounded-xl">
                  Keine Checklisten-Punkte oder Unteraufgaben vorhanden.
                </div>

                <div
                  v-for="(item, i) in drawerTask.checklist"
                  :key="item.id || i"
                  class="space-y-1.5"
                >
                  <!-- Main Checklist Parent Item -->
                  <div class="flex items-center gap-2 p-2.5 rounded-xl bg-white border border-slate-200 group/cl shadow-xs hover:border-slate-300 transition">
                    <input
                      type="checkbox"
                      :checked="item.done"
                      @change="toggleChecklistItem(i)"
                      :disabled="userRole === 'viewer'"
                      class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-0 shrink-0 cursor-pointer"
                    />
                    <input
                      v-model="item.text"
                      @blur="autoSaveDrawer"
                      :disabled="userRole === 'viewer'"
                      class="flex-1 text-xs sm:text-sm bg-transparent focus:outline-none focus:bg-slate-50 rounded px-1 disabled:cursor-default"
                      :class="item.done ? 'line-through text-slate-400 font-normal' : 'text-slate-800 font-bold'"
                    />
                    <button
                      v-if="userRole !== 'viewer'"
                      @click="removeChecklistItem(i)"
                      class="opacity-0 group-hover/cl:opacity-100 text-slate-400 hover:text-rose-600 p-1 rounded-lg hover:bg-rose-50 transition text-xs"
                      title="Hauptpunkt löschen"
                    >
                      ✕
                    </button>
                  </div>

                  <!-- Subtasks as Dependent Child Items under Checklist Item -->
                  <div class="pl-6 space-y-1.5 border-l-2 border-cyan-200 ml-3">
                    <div
                      v-for="sub in (drawerSubtasks || []).filter((s:any) => s.checklist_item_id === item.id || (!s.checklist_item_id && i === 0))"
                      :key="sub.id"
                      class="flex items-center gap-2 p-2 rounded-xl bg-cyan-50/50 border border-cyan-100 group/sub text-xs"
                    >
                      <span class="text-cyan-600 font-bold">↳</span>
                      <input
                        type="checkbox"
                        :checked="Boolean(sub.is_done)"
                        @change="toggleSubtask(sub)"
                        :disabled="userRole === 'viewer'"
                        class="w-3.5 h-3.5 rounded border-cyan-300 text-cyan-600 focus:ring-0 shrink-0 cursor-pointer"
                      />
                      <span
                        class="flex-1 text-xs"
                        :class="sub.is_done ? 'line-through text-slate-400' : 'text-slate-700 font-semibold'"
                      >
                        {{ sub.title }}
                      </span>
                      <button
                        v-if="userRole !== 'viewer'"
                        @click="deleteSubtask(sub.id)"
                        class="opacity-0 group-hover/sub:opacity-100 text-slate-400 hover:text-rose-600 p-1 rounded hover:bg-rose-50 transition text-[11px]"
                        title="Unterpunkt löschen"
                      >
                        ✕
                      </button>
                    </div>

                    <!-- Quick Add Subtask to this Checklist Item -->
                    <div v-if="userRole !== 'viewer'" class="flex items-center gap-1.5 pt-0.5">
                      <input
                        v-model="itemSubtaskInputs[item.id || i]"
                        @keyup.enter="addSubtaskToItem(item)"
                        type="text"
                        placeholder="+ Unterpunkt zur Checkliste hinzufügen..."
                        class="flex-1 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-[#00A3C4]"
                      />
                      <button
                        type="button"
                        @click="addSubtaskToItem(item)"
                        class="taskster_button px-3 text-[11px] h-[30px] rounded-lg"
                      >
                        + Unterpunkt
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Main Checklist Item Creator -->
              <div v-if="userRole !== 'viewer'" class="flex items-center gap-2 pt-2 border-t border-slate-200">
                <input
                  v-model="newChecklistInput"
                  @keyup.enter="addChecklistItem"
                  type="text"
                  placeholder="+ Neuer Haupt-Checklistenpunkt..."
                  class="flex-1 px-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm focus:outline-none focus:border-[#00A3C4] focus:ring-2 focus:ring-cyan-500/20"
                />
                <button
                  @click="addChecklistItem"
                  type="button"
                  class="taskster_button px-4 text-xs h-[36px] rounded-lg"
                >
                  + Hauptpunkt
                </button>
              </div>
            </div>

            <!-- Zeiterfassung & Budget für diese Aufgabe (Kompaktes Menü) -->
            <div class="p-4 rounded-2xl bg-cyan-50/40 border border-cyan-200 transition-all">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <span class="text-sm">⏱️</span>
                  <span class="text-xs font-black text-slate-800 uppercase tracking-wider">Zeiterfassung & Budget</span>
                  <span class="text-xs font-bold text-cyan-900 bg-cyan-100/80 px-2.5 py-0.5 rounded-full border border-cyan-300/60 shadow-2xs">
                    {{ drawerTask.tracked_hours || 0 }} Std.
                    <span v-if="drawerTask.budget_hours" class="text-cyan-700 font-medium"> / {{ drawerTask.budget_hours }} Std.</span>
                  </span>
                </div>

                <button
                  type="button"
                  @click="showDrawerTimeMenu = !showDrawerTimeMenu"
                  class="text-xs font-bold text-cyan-800 hover:text-cyan-950 flex items-center space-x-1 px-3 py-1.5 rounded-xl hover:bg-cyan-100/70 bg-white border border-cyan-200 shadow-2xs transition cursor-pointer"
                >
                  <span>{{ showDrawerTimeMenu ? 'Menü verbergen' : 'Budget & Manuell buchen' }}</span>
                  <span class="text-[10px] transform transition-transform" :class="{ 'rotate-180': showDrawerTimeMenu }">▼</span>
                </button>
              </div>

              <!-- Progress bar if budget exists and collapsed -->
              <div v-if="drawerTask.budget_hours > 0" class="w-full bg-slate-200 rounded-full h-1.5 mt-3 overflow-hidden">
                <div
                  class="h-1.5 rounded-full transition-all"
                  :class="(drawerTask.tracked_hours || 0) > drawerTask.budget_hours ? 'bg-rose-500' : 'bg-cyan-600'"
                  :style="{ width: Math.min(100, Math.round(((drawerTask.tracked_hours || 0) / drawerTask.budget_hours) * 100)) + '%' }"
                ></div>
              </div>

              <!-- Collapsible Section: Budget + Manuelle Buchung + Liste -->
              <div v-if="showDrawerTimeMenu" class="mt-4 pt-3.5 border-t border-cyan-200/80 space-y-3.5 animate-in fade-in duration-150">
                <!-- Budget Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Aufgaben-Budget (Stunden)</label>
                    <input
                      v-model="drawerTask.budget_hours"
                      @blur="autoSaveDrawer"
                      type="number"
                      step="0.5"
                      min="0"
                      placeholder="z.B. 8"
                      :disabled="userRole === 'viewer'"
                      class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-cyan-600 disabled:opacity-60"
                    />
                  </div>
                  <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Aufgaben-Budget (Betrag in {{ project?.currency || 'CHF' }})</label>
                    <input
                      v-model="drawerTask.budget_amount"
                      @blur="autoSaveDrawer"
                      type="number"
                      step="10"
                      min="0"
                      placeholder="z.B. 1000"
                      :disabled="userRole === 'viewer'"
                      class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:border-cyan-600 disabled:opacity-60"
                    />
                  </div>
                </div>

                <!-- Quick Time Logging Form (Manuell) -->
                <div v-if="userRole !== 'viewer'" class="bg-white p-3.5 rounded-xl border border-slate-200 space-y-2.5">
                  <div class="text-[11px] font-black uppercase tracking-wider text-slate-600 flex items-center justify-between">
                    <span>Manuell Zeit auf diese Aufgabe buchen</span>
                    <span class="text-[10px] text-slate-400 font-normal">Wird mit * markiert</span>
                  </div>
                  <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <div>
                      <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Dauer (Std.)</label>
                      <input
                        v-model="drawerTimeForm.duration_hours"
                        type="number"
                        step="0.25"
                        min="0.05"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold focus:outline-none focus:border-cyan-600"
                      />
                    </div>
                    <div>
                      <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Datum</label>
                      <input
                        v-model="drawerTimeForm.entry_date"
                        type="date"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-cyan-600"
                      />
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                      <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Stundensatz ({{ project?.currency || 'CHF' }})</label>
                      <input
                        v-model="drawerTimeForm.hourly_rate"
                        type="number"
                        step="5"
                        min="0"
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-cyan-600"
                      />
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <input
                      v-model="drawerTimeForm.description"
                      placeholder="Beschreibung / Notiz..."
                      class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-cyan-600"
                    />
                    <button
                      @click="addTaskTimeEntry"
                      type="button"
                      class="taskster_button px-4 text-xs h-[34px] rounded-lg"
                    >
                      + Buchen
                    </button>
                  </div>
                </div>

                <!-- List of recorded entries for this task -->
                <div v-if="drawerTimeEntries.length > 0" class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                  <div
                    v-for="te in drawerTimeEntries"
                    :key="te.id"
                    class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200 text-xs shadow-xs"
                  >
                    <div class="flex items-center space-x-2">
                      <span class="font-bold text-slate-900">
                        {{ (te.duration_minutes / 60).toFixed(1) }} Std.
                        <span v-if="te.is_manual" class="text-rose-600 font-black text-sm ml-0.5" title="Manuell erfasst oder angepasst">*</span>
                      </span>
                      <span class="text-slate-400">·</span>
                      <span class="text-slate-600">{{ new Date(te.entry_date).toLocaleDateString('de-CH') }}</span>
                      <span class="text-slate-400">·</span>
                      <span class="text-slate-700 font-medium">{{ te.user_name }}</span>
                      <span v-if="te.description" class="text-slate-500 italic max-w-[160px] truncate">({{ te.description }})</span>
                    </div>
                    <div v-if="userRole !== 'viewer'" class="flex items-center space-x-2">
                      <button
                        v-if="te.user_id === user?.id || userRole === 'owner' || userRole === 'admin'"
                        @click="openEditTimeModal(te)"
                        class="text-cyan-700 hover:text-cyan-900 font-bold text-[11px]"
                      >
                        Ändern
                      </button>
                      <button
                        v-if="te.user_id === user?.id || userRole === 'owner' || userRole === 'admin'"
                        @click="deleteTimeEntry(te.id)"
                        class="text-rose-600 hover:text-rose-800 font-bold text-[11px]"
                      >
                        ✕
                      </button>
                    </div>
                  </div>
                </div>
                <div v-else class="text-[11px] text-slate-500 italic text-center py-2">
                  Noch keine Zeiten auf diese Aufgabe gebucht.
                </div>
              </div>
            </div>

            <!-- Comments & Feed -->
            <div>
              <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                <span>💬</span>
                <span>Kommentare & Besprechungsnotizen</span>
              </label>

              <!-- Feed of comments -->
              <div class="space-y-3 mb-4">
                <div v-if="drawerComments.length === 0" class="text-xs text-slate-500 italic text-center py-6 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                  Noch keine Kommentare oder Notizen vorhanden.
                </div>
                <div v-for="c in drawerComments" :key="c.id" class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#00A3C4] to-teal-500 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-sm overflow-hidden">
                    <img v-if="user?.avatar || c.author_avatar" :src="user?.avatar || c.author_avatar" class="w-full h-full object-cover" />
                    <span v-else>{{ (c.author_name || '?').charAt(0).toUpperCase() }}</span>
                  </div>
                  <div class="flex-1 bg-slate-50 rounded-2xl rounded-tl-sm p-3.5 border border-slate-200 shadow-xs">
                    <div class="flex items-baseline justify-between gap-2 mb-1.5">
                      <span class="text-xs font-black text-slate-900">{{ c.author_name }}</span>
                      <span class="text-[10px] text-slate-500 font-medium">
                        {{ new Date(c.created_at).toLocaleString('de-CH', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'}) }}
                      </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ c.content }}</p>
                  </div>
                </div>
              </div>

              <!-- New comment textarea -->
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#00A3C4] to-teal-500 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-sm overflow-hidden">
                  <img v-if="user?.avatar" :src="user.avatar" class="w-full h-full object-cover" />
                  <span v-else>{{ (user?.name || '?').charAt(0).toUpperCase() }}</span>
                </div>
                <div class="flex-1">
                  <textarea
                    v-model="newCommentInput"
                    @keydown.ctrl.enter="addComment"
                    rows="2"
                    placeholder="Kommentar schreiben... (Strg+Enter zum Senden)"
                    class="w-full px-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#00A3C4] focus:ring-2 focus:ring-cyan-500/20 resize-none transition"
                  ></textarea>
                  <div class="flex items-center justify-between mt-1.5">
                    <span class="text-[10px] text-slate-500 font-medium hidden sm:inline">Tipp: Mit Strg+Enter absenden</span>
                    <button
                      @click="addComment"
                      :disabled="!newCommentInput.trim()"
                      type="button"
                      class="taskster_button px-4 text-xs h-[34px] rounded-lg"
                    >
                      Senden
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Documents / Attachments -->
            <div>
              <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                <span>📎</span>
                <span>Dateianhänge</span>
              </label>

              <!-- Upload zone -->
              <div v-if="userRole !== 'viewer'" class="mb-4 p-4 rounded-2xl border-2 border-dashed border-slate-300 hover:border-cyan-400 hover:bg-cyan-50/50 transition cursor-pointer" @click="triggerFileInput" @dragover.prevent="onDragOverFiles" @dragleave="onDragLeaveFiles" @drop.prevent="onDropFiles" :class="{ 'border-cyan-400 bg-cyan-50/50': dragOverFiles }">
                <input ref="fileInput" type="file" multiple @change="onFileSelected" class="hidden" />
                <div class="flex flex-col items-center text-center">
                  <span class="text-3xl mb-2">📤</span>
                  <p class="text-xs font-bold text-slate-700">Dateien hierher ziehen oder klicken zum Auswählen</p>
                  <p class="text-[10px] text-slate-500 mt-1">Max. 10 MB pro Datei · Bilder, PDFs, Office-Dokumente</p>
                </div>
              </div>

              <!-- Upload progress -->
              <div v-if="uploadingFiles.length > 0" class="space-y-2 mb-4">
                <div v-for="uf in uploadingFiles" :key="uf.id" class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                  <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-bold text-slate-800 truncate pr-2">{{ uf.name }}</span>
                    <span class="text-slate-500">{{ Math.round(uf.progress) }}%</span>
                  </div>
                  <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-cyan-500 h-1.5 rounded-full transition-all" :style="{width: uf.progress + '%'}"></div>
                  </div>
                </div>
              </div>

              <!-- Documents list -->
              <div class="space-y-2">
                <div v-if="drawerDocuments.length === 0 && uploadingFiles.length === 0" class="text-xs text-slate-500 italic text-center py-4 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                  Noch keine Dateien angehängt.
                </div>
                <div v-for="doc in drawerDocuments" :key="doc.id" class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl shadow-xs hover:border-slate-300 transition group/doc">
                  <!-- File icon based on mime type -->
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="getFileIconClass(doc.mime_type)">
                    <span class="text-xl">{{ getFileIcon(doc.mime_type) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-900 truncate">{{ doc.file_name }}</p>
                    <p class="text-[10px] text-slate-500 flex items-center gap-2">
                      <span>{{ formatFileSize(doc.file_size) }}</span>
                      <span>·</span>
                      <span>{{ new Date(doc.created_at).toLocaleDateString('de-CH') }}</span>
                      <span v-if="doc.uploaded_by_name" class="text-cyan-700">· von {{ doc.uploaded_by_name }}</span>
                    </p>
                  </div>
                  <div class="flex items-center gap-1">
                    <a :href="doc.storage_path" target="_blank" class="p-2 rounded-lg text-slate-500 hover:text-cyan-700 hover:bg-cyan-50 transition" title="Herunterladen">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                    <button v-if="userRole !== 'viewer'" @click="deleteDocument(doc.id)" class="opacity-0 group-hover/doc:opacity-100 p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Löschen">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v10m4-10v10M10 7v10"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: Sidebar (Status, Prio, Assignee, Date, Color, Tags, Fields, Delete) -->
          <div class="w-full lg:w-96 xl:w-[420px] bg-slate-50/90 p-6 sm:p-7 space-y-5 shrink-0">
            
            <!-- Section / List mover -->
            <div>
              <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Abschnitt</label>
              <select
                v-model="drawerTask.list_id"
                @change="onDrawerSectionChange"
                :disabled="userRole === 'viewer'"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-xs"
              >
                <option v-for="l in lists" :key="l.id" :value="l.id">
                  {{ getSectionTitle(l.title) }}
                </option>
              </select>
            </div>

            <!-- Status Dropdown (Viewer darf abhaken!) -->
            <div>
              <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Status</label>
              <select
                v-model="drawerTask.status"
                @change="autoSaveDrawer"
                class="w-full px-3 py-2 rounded-xl text-xs font-bold border focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-xs cursor-pointer"
                :class="{
                  'bg-emerald-50 text-emerald-800 border-emerald-300': drawerTask.status === 'done',
                  'bg-cyan-50 text-cyan-800 border-cyan-300': drawerTask.status === 'in_progress',
                  'bg-amber-50 text-amber-800 border-amber-300': drawerTask.status === 'review',
                  'bg-white text-slate-800 border-slate-300': drawerTask.status === 'todo'
                }"
              >
                <option value="todo">📋 Zu erledigen (Todo)</option>
                <option value="in_progress">🔄 In Arbeit (In Progress)</option>
                <option value="review">🔍 In Prüfung (Review)</option>
                <option value="done">✅ Abgeschlossen (Done)</option>
              </select>
            </div>

            <!-- Priority Dropdown -->
            <div>
              <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">Priorität</label>
              <select
                v-model="drawerTask.priority"
                @change="autoSaveDrawer"
                :disabled="userRole === 'viewer'"
                class="w-full px-3 py-2 rounded-xl text-xs font-bold border focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:cursor-default shadow-xs"
                :class="{
                  'bg-rose-50 text-rose-800 border-rose-300': drawerTask.priority === 'dringend',
                  'bg-amber-50 text-amber-800 border-amber-300': drawerTask.priority === 'hoch',
                  'bg-white text-slate-800 border-slate-300': drawerTask.priority === 'normal',
                  'bg-emerald-50 text-emerald-800 border-emerald-300': drawerTask.priority === 'niedrig'
                }"
              >
                <option value="niedrig">🟢 Niedrig</option>
                <option value="normal">🔵 Normal</option>
                <option value="hoch">🟠 Hoch</option>
                <option value="dringend">🔴 Dringend</option>
              </select>
            </div>

            <!-- Mehrfach-Zuweisung (Aus den Eingeladenen im Ordner/Projekt) -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider">
                  👥 Zuweisung ({{ drawerTaskAssignedUsers.length }})
                </label>
                <span class="text-[10px] text-slate-400 font-semibold">Mehrfachauswahl möglich</span>
              </div>

              <!-- Selected assignees chips -->
              <div v-if="drawerTaskAssignedUsers.length > 0" class="flex flex-wrap gap-1.5 mb-2">
                <span
                  v-for="uId in drawerTaskAssignedUsers"
                  :key="uId"
                  class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-xl bg-cyan-50 border border-cyan-200 text-cyan-900 text-xs font-bold"
                >
                  <span class="w-4 h-4 rounded-full bg-cyan-700 text-white text-[9px] flex items-center justify-center font-black overflow-hidden shrink-0">
                    <img v-if="getMember(uId)?.avatar" :src="getMember(uId)?.avatar" class="w-full h-full object-cover" />
                    <span v-else>{{ (getMemberName(uId) || 'U').charAt(0).toUpperCase() }}</span>
                  </span>
                  <span>{{ getMemberName(uId) }}</span>
                  <button
                    v-if="userRole !== 'viewer'"
                    type="button"
                    @click="removeAssignee(uId)"
                    class="text-cyan-600 hover:text-cyan-900 ml-0.5 text-xs font-black cursor-pointer"
                  >
                    ✕
                  </button>
                </span>
              </div>

              <!-- Dropdown selector to toggle members -->
              <div v-if="userRole !== 'viewer'" class="relative">
                <button
                  type="button"
                  @click="showAssigneeDropdown = !showAssigneeDropdown"
                  class="w-full px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 flex items-center justify-between shadow-xs transition"
                >
                  <span>+ Mitglied zuweisen / ändern...</span>
                  <span class="text-xs">▼</span>
                </button>

                <div
                  v-if="showAssigneeDropdown"
                  class="absolute left-0 right-0 mt-1 z-30 bg-white border border-slate-200 rounded-2xl shadow-xl max-h-56 overflow-y-auto p-1.5 space-y-1 divide-y divide-slate-100"
                >
                  <div
                    v-for="m in members"
                    :key="m.user_id"
                    @click="toggleAssignee(m.user_id)"
                    class="flex items-center justify-between p-2 rounded-xl hover:bg-cyan-50 transition cursor-pointer text-xs"
                  >
                    <div class="flex items-center space-x-2">
                      <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-[10px] font-black shrink-0 overflow-hidden">
                        <img v-if="m.avatar" :src="m.avatar" class="w-full h-full object-cover" />
                        <span v-else>{{ (m.name || m.email || '?').charAt(0).toUpperCase() }}</span>
                      </div>
                      <div class="min-w-0">
                        <div class="font-bold text-slate-900 truncate">
                          {{ m.name || m.email }} {{ m.user_id === user?.id ? '(Du)' : '' }}
                        </div>
                        <div class="text-[10px] text-slate-500">
                          {{ m.role === 'owner' ? 'Inhaber' : (m.role === 'editor' ? 'Editor' : 'Viewer') }}
                        </div>
                      </div>
                    </div>
                    <span
                      class="w-5 h-5 rounded-md border flex items-center justify-center text-xs font-black"
                      :class="drawerTaskAssignedUsers.includes(m.user_id) ? 'bg-[#00A3C4] border-[#00A3C4] text-white' : 'border-slate-300 bg-white text-transparent'"
                    >
                      ✓
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Due Date -->
            <div>
              <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">📅 Fälligkeitsdatum</label>
              <input
                v-model="drawerTask.due_date"
                @change="autoSaveDrawer"
                type="date"
                :disabled="userRole === 'viewer'"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-[#00A3C4] disabled:cursor-default shadow-xs"
              />
            </div>

            <!-- Color Palette Chips -->
            <div>
              <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-2">🎨 Farbmarkierung</label>
              <div class="flex items-center flex-wrap gap-2">
                <button
                  v-for="col in taskColors"
                  :key="col.value"
                  type="button"
                  @click="setTaskColor(col.value)"
                  :disabled="userRole === 'viewer'"
                  class="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110 disabled:cursor-default"
                  :style="{ backgroundColor: col.value }"
                  :class="drawerTask.color === col.value ? 'border-slate-900 ring-2 ring-offset-2 ring-slate-400 scale-110' : 'border-white shadow-sm'"
                  :title="col.label"
                />
                <button
                  v-if="drawerTask.color"
                  type="button"
                  @click="setTaskColor('')"
                  class="text-[11px] text-slate-500 hover:text-slate-800 underline ml-1 font-bold"
                >
                  Entfernen
                </button>
              </div>
            </div>

            <!-- Tags -->
            <div>
              <label class="block text-[11px] font-black text-slate-600 uppercase tracking-wider mb-1.5">🏷️ Tags</label>
              <div class="flex flex-wrap gap-1.5 mb-2">
                <span
                  v-for="(tag, i) in drawerTask.tags"
                  :key="i"
                  class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-300 shadow-xs"
                >
                  <span>{{ tag }}</span>
                  <button v-if="userRole !== 'viewer'" @click="removeTag(i)" class="text-cyan-600 hover:text-cyan-950 ml-0.5">✕</button>
                </span>
                <span v-if="!drawerTask.tags?.length" class="text-[11px] text-slate-400 italic">Keine Tags</span>
              </div>
              <div v-if="userRole !== 'viewer'" class="flex items-center gap-1.5">
                <input
                  v-model="newTagInput"
                  @keyup.enter="addTag"
                  type="text"
                  placeholder="Tag + Enter..."
                  class="flex-1 px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs focus:outline-none focus:border-[#00A3C4] shadow-xs"
                />
                <button @click="addTag" type="button" class="taskster_button px-3 text-xs h-[30px] rounded-lg">+</button>
              </div>
            </div>



            <!-- Delete Button (Only owner or admin) -->
            <div v-if="userRole === 'owner' || userRole === 'admin'" class="pt-4 border-t border-slate-200">
              <button
                @click="deleteTaskFromDrawer"
                type="button"
                class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-rose-600 hover:text-white hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition flex items-center justify-center space-x-1.5"
              >
                <span>🗑️</span>
                <span>Aufgabe löschen</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3.5 bg-slate-100/90 border-t border-slate-200 flex items-center justify-between shrink-0">
          <div class="text-[11px] text-slate-600 font-semibold flex items-center space-x-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>{{ isCreatingTaskInDrawer ? 'Aufgabe wird beim Speichern/Schliessen angelegt' : 'Änderungen werden automatisch gespeichert' }}</span>
          </div>
          <div class="flex items-center space-x-2">
            <button
              v-if="isCreatingTaskInDrawer && userRole !== 'viewer'"
              @click="saveNewTaskFromDrawer"
              type="button"
              class="taskster_button px-6 text-xs h-[38px] rounded-lg"
            >
              Aufgabe erstellen
            </button>
            <button
              @click="closeTaskDrawer"
              type="button"
              class="taskster_button_light px-6 text-xs h-[38px] rounded-lg"
            >
              Schliessen
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Excel / CSV Import (Komplex mit konfigurierbarem Spalten-Mapping) -->
    <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl overflow-hidden flex flex-col my-8 max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-6 shrink-0">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-2xl">📊</span>
              <h3 class="text-lg font-black text-slate-900">Aufgaben aus Excel / CSV importieren</h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Lade eine CSV- oder Tabellendatei hoch und weise die Spalten flexibel den Feldern in Taskster zu.
            </p>
          </div>
          <button
            type="button"
            @click="closeImportModal"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <!-- Step 1: File Upload -->
        <div v-if="importStep === 1" class="space-y-4">
          <div
            class="p-8 border-2 border-dashed border-slate-300 hover:border-cyan-500 rounded-3xl bg-slate-50 hover:bg-cyan-50/30 transition text-center cursor-pointer flex flex-col items-center justify-center"
            @click="csvFileInput?.click()"
            @dragover.prevent
            @drop.prevent="onCsvDrop"
          >
            <input
              ref="csvFileInput"
              type="file"
              accept=".xlsx,.xls,.csv,.txt,.tsv"
              class="hidden"
              @change="onCsvFileSelected"
            />
            <span class="text-4xl mb-3">📁</span>
            <p class="text-sm font-bold text-slate-800">Excel- (.xlsx, .xls) oder CSV-Datei auswählen oder hierher ziehen</p>
            <p class="text-xs text-slate-500 mt-1">Unterstützt Formate: Excel (.xlsx, .xls) sowie CSV, TSV (Trennzeichen: Komma, Semikolon, Tab)</p>
          </div>

          <div v-if="importError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-semibold">
            {{ importError }}
          </div>
        </div>

        <!-- Step 2: Column Mapping & Section Target -->
        <div v-else-if="importStep === 2" class="space-y-6 overflow-y-auto pr-1">
          <div class="p-3 bg-cyan-50 border border-cyan-200 rounded-2xl flex items-center justify-between text-xs">
            <span class="text-cyan-900 font-bold">
              📄 Datei erkannt: <strong>{{ importFileName }}</strong> ({{ importParsedRows.length }} Zeilen gefunden)
            </span>
            <button @click="importStep = 1" type="button" class="text-cyan-700 underline font-bold hover:text-cyan-950">
              Andere Datei wählen
            </button>
          </div>

          <!-- Target section -->
          <div>
            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">
              Ziel-Abschnitt für importierte Aufgaben:
            </label>
            <select
              v-model="importTargetListId"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            >
              <option v-for="l in lists" :key="l.id" :value="l.id">{{ getSectionTitle(l.title) }}</option>
            </select>
          </div>

          <!-- Column Mapping Table -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-black text-slate-800 uppercase tracking-wider">
                Spaltenzuweisung (Mapping):
              </label>
              <div class="flex items-center space-x-2">
                <span class="text-[11px] text-slate-500 font-medium">Titel-Spalte ist Pflichtfeld</span>
                <button
                  type="button"
                  @click="openNewFieldModal"
                  class="text-[11px] font-bold text-[#0891B2] hover:underline"
                >
                  + Eigenes Feld anlegen
                </button>
              </div>
            </div>

            <div class="border border-slate-200 rounded-2xl overflow-hidden">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-bold text-[10px] border-b border-slate-200">
                  <tr>
                    <th class="py-2.5 px-4">Spalte in Datei</th>
                    <th class="py-2.5 px-4">Beispielwert (Zeile 1)</th>
                    <th class="py-2.5 px-4">Wird zugewiesen an Feld</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                  <tr v-for="(header, hIdx) in importHeaders" :key="hIdx" class="hover:bg-slate-50">
                    <td class="py-2.5 px-4 font-bold text-slate-900">{{ header }}</td>
                    <td class="py-2.5 px-4 text-slate-500 font-mono text-[11px] truncate max-w-xs">
                      {{ importParsedRows[0]?.[hIdx] || '-' }}
                    </td>
                    <td class="py-2.5 px-4">
                      <select
                        v-model="importColumnMapping[hIdx]"
                        class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold focus:outline-none focus:border-cyan-600"
                        :class="importColumnMapping[hIdx] === 'title' ? 'border-cyan-500 bg-cyan-50/50 text-cyan-900 font-bold' : (importColumnMapping[hIdx]?.startsWith('custom:') ? 'border-amber-400 bg-amber-50/40 text-amber-900 font-semibold' : '')"
                      >
                        <option value="">-- Ignorieren --</option>
                        <optgroup label="Standard-Felder">
                          <option value="title">📌 Aufgabentitel (Pflicht)</option>
                          <option value="description">📋 Beschreibung</option>
                          <option value="status">Status (todo/in_progress/done)</option>
                          <option value="due_date">📅 Fälligkeitsdatum</option>
                          <option value="priority">Priorität (niedrig/normal/hoch/dringend)</option>
                          <option value="tags">🏷️ Tags</option>
                        </optgroup>

                        <!-- Bestehende Zusatzfelder -->
                        <optgroup v-if="fields.length > 0" label="Bestehende Zusatzfelder">
                          <option
                            v-for="f in fields"
                            :key="f.id"
                            :value="'custom:' + f.field_key"
                          >
                            ⚙️ {{ f.label_key ? $t(f.label_key) : f.label }} ({{ f.field_key }})
                          </option>
                        </optgroup>

                        <!-- Als neues Feld aus dieser Spalte anlegen -->
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

          <!-- Preview of First 3 Rows -->
          <div>
            <label class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-1.5">
              Vorschau der ersten Zeilen:
            </label>
            <div class="border border-slate-200 rounded-2xl overflow-x-auto max-h-40 bg-slate-50 p-2 text-[11px] font-mono">
              <div v-for="(row, rIdx) in importParsedRows.slice(0, 3)" :key="rIdx" class="py-1 border-b border-slate-200 last:border-0 flex gap-2">
                <span class="text-slate-400 font-bold">#{{ rIdx + 1 }}:</span>
                <span class="text-slate-800 truncate">{{ row.join(' | ') }}</span>
              </div>
            </div>
          </div>

          <div v-if="importError" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-semibold">
            {{ importError }}
          </div>
        </div>

        <!-- Step 3: Success -->
        <div v-else-if="importStep === 3" class="py-8 text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-3xl font-black flex items-center justify-center mx-auto">
            ✓
          </div>
          <h4 class="text-lg font-black text-slate-900">Import erfolgreich abgeschlossen!</h4>
          <p class="text-xs text-slate-600">
            Es wurden <strong>{{ importSuccessCount }}</strong> Aufgaben erfolgreich in den Abschnitt eingepflegt.
          </p>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-5 mt-4 border-t border-slate-200 shrink-0">
          <button
            type="button"
            @click="closeImportModal"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
          >
            {{ importStep === 3 ? 'Schliessen' : 'Abbrechen' }}
          </button>

          <button
            v-if="importStep === 2"
            type="button"
            @click="executeImport"
            :disabled="importingTasks"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg flex items-center space-x-2"
          >
            <span>{{ importingTasks ? 'Importiere...' : 'Import starten (' + importParsedRows.length + ' Aufgaben)' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: New Custom Field (Inside Settings) -->

    <div v-if="showNewFieldModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-black text-slate-900 mb-1">{{ editingFieldId ? 'Feld bearbeiten' : 'Neues benutzerdefiniertes Feld' }}</h3>
        <p class="text-xs text-slate-500 mb-4">
          Definiere ein Attribut für Aufgaben oder das Projekt.
        </p>

        <form @submit.prevent="saveField" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Gültigkeitsbereich</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                :disabled="!!editingFieldId"
                @click="newFieldEntityType = 'task'"
                class="py-2 px-3 rounded-xl text-xs font-bold border transition text-center disabled:opacity-60"
                :class="newFieldEntityType === 'task' ? 'bg-cyan-50 text-cyan-800 border-cyan-500' : 'bg-slate-50 text-slate-600 border-slate-200'"
              >
                Aufgaben-Feld
              </button>
              <button
                type="button"
                :disabled="!!editingFieldId"
                @click="newFieldEntityType = 'project'"
                class="py-2 px-3 rounded-xl text-xs font-bold border transition text-center disabled:opacity-60"
                :class="newFieldEntityType === 'project' ? 'bg-purple-50 text-purple-800 border-purple-500' : 'bg-slate-50 text-slate-600 border-slate-200'"
              >
                Projekt-Feld
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Feld-Bezeichnung (Label)</label>
            <input
              v-model="newFieldLabel"
              type="text"
              required
              placeholder="z.B. Kostenstelle oder Priorität"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Feldtyp</label>
            <select
              v-model="newFieldType"
              :disabled="!!editingFieldId"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 cursor-pointer disabled:opacity-60"
            >
              <option value="text">Textzeile (kurz)</option>
              <option value="textarea">Längerer Text / Notizfeld (mehrzeilig)</option>
              <option value="number">Zahl / Währung / Messwert</option>
              <option value="select">Auswahlliste (Dropdown)</option>
              <option value="date">Datum</option>
              <option value="checkbox">Checkbox (Ja / Nein)</option>
              <option value="url">Weblink / URL (z.B. Plan, Dokument)</option>
              <option value="email">E-Mail-Adresse</option>
              <option value="phone">Telefonnummer</option>
            </select>
          </div>

          <div v-if="newFieldType === 'select'">
            <label class="block text-xs font-bold text-slate-700 mb-1">Optionen (Komma-getrennt)</label>
            <input
              v-model="newFieldOptionsRaw"
              type="text"
              placeholder="z.B. Niedrig, Mittel, Hoch, Dringend"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <!-- Conditional Logic Builder -->
          <div class="pt-3 border-t border-slate-100 space-y-3">
            <div class="flex items-center space-x-2">
              <input
                id="enableLogic"
                v-model="enableFieldLogic"
                type="checkbox"
                class="rounded border-slate-300 text-cyan-600 focus:ring-0"
              />
              <label for="enableLogic" class="text-xs font-bold text-slate-700 cursor-pointer">
                Bedingte Logik (Feld nur unter Bedingung anzeigen)
              </label>
            </div>

            <div v-if="enableFieldLogic" class="space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Abhängig von Feld</label>
                <select
                  v-model="logicDependsOnField"
                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-cyan-600"
                >
                  <option value="">-- Feld auswählen --</option>
                  <option v-for="other in fields" :key="other.id" :value="other.field_key">
                    {{ other.label }} ({{ other.field_key }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-600 mb-1">Nur anzeigen wenn Wert gleich:</label>
                <input
                  v-model="logicDependsOnValue"
                  type="text"
                  placeholder="z.B. Hoch oder Freigegeben"
                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-cyan-600"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showNewFieldModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Feld speichern
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 1: NEUER JOURNALEINTRAG (Bausitzung / Protokoll / Bautagebuch) -->
    <div
      v-if="showNewEntryModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4 shrink-0">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span class="text-xl">📖</span>
              <span>{{ $t('journal.modal_entry_title') }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ $t('journal.modal_entry_desc') }}
            </p>
          </div>
          <button
            @click="showNewEntryModal = false"
            type="button"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="saveNewEntry" class="space-y-4 overflow-y-auto pr-1 flex-1">
          <div v-if="journalError" class="p-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-900 text-xs font-bold">
            {{ journalError }}
          </div>

          <!-- Title -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">
              {{ $t('journal.field_title') }} <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="newEntryForm.title"
              type="text"
              required
              :placeholder="$t('journal.field_title_placeholder')"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>

          <!-- Category & Date -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.field_category') }}</label>
              <select
                v-model="newEntryForm.category"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              >
                <option value="bausitzung">🏛️ {{ $t('journal.category_bausitzung') }}</option>
                <option value="bautagebuch">📋 {{ $t('journal.category_bautagebuch') }}</option>
                <option value="abnahmebegehung">🔍 {{ $t('journal.category_abnahmebegehung') }}</option>
                <option value="wetter_behinderung">⛈️ {{ $t('journal.category_wetter_behinderung') }}</option>
                <option value="regie">⏱️ {{ $t('journal.category_regie') }}</option>
                <option value="allgemein">📖 {{ $t('journal.category_allgemein') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.field_date') }}</label>
              <input
                v-model="newEntryForm.entry_date"
                type="date"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <!-- Visibility & Allowed Group -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.visibility_label') }}</label>
              <select
                v-model="newEntryForm.visibility"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              >
                <option value="all">🌐 {{ $t('journal.visibility_all') }}</option>
                <option value="company">🏢 {{ $t('journal.visibility_company') }}</option>
                <option value="group">👥 {{ $t('journal.visibility_group') }}</option>
                <option value="only_me">🔒 {{ $t('journal.visibility_only_me') }}</option>
              </select>
            </div>
            <div v-if="newEntryForm.visibility === 'group'">
              <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.group_label') }}</label>
              <select
                v-model="newEntryForm.allowed_group_id"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              >
                <option :value="null">{{ $t('journal.select_group') }}</option>
                <option v-for="g in userGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
            </div>
          </div>

          <!-- Linked Task (optional) -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Verknüpfte Aufgabe (optional)</label>
            <select
              v-model="newEntryForm.task_id"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option :value="null">-- Keine Verknüpfung --</option>
              <option v-for="t in allProjectTasks" :key="t.id" :value="t.id">
                {{ t.title }} ({{ getSectionTitle(t.list_id) }})
              </option>
            </select>
          </div>

          <!-- Attendees Management -->
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <label class="block text-xs font-black text-slate-900">{{ $t('journal.attendees') }}</label>
                <p class="text-[11px] text-slate-500">{{ $t('journal.attendees_desc') }}</p>
              </div>
              <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-700">
                {{ newEntryForm.attendees.length }} Teilnehmer
              </span>
            </div>

            <!-- Quick Add Attendee from Contacts or Custom -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 pt-1">
              <div class="sm:col-span-4">
                <select
                  v-model="selectedContactToAdd"
                  @change="addContactToAttendees"
                  class="w-full px-2.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4]"
                >
                  <option value="">{{ $t('journal.select_contact') }} ({{ attendeeContactOptions.length }})</option>
                  <option v-for="c in attendeeContactOptions" :key="c.id" :value="c.id">
                    {{ (c.first_name ? c.first_name + ' ' : '') + c.last_name }}{{ c.company_name ? ` (${c.company_name})` : (c.role_function ? ` (${c.role_function})` : '') }}
                  </option>
                </select>
              </div>
              <div class="sm:col-span-3">
                <input
                  v-model="newEntryAttendeeName"
                  type="text"
                  placeholder="Name"
                  class="w-full px-2.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4]"
                />
              </div>
              <div class="sm:col-span-3">
                <input
                  v-model="newEntryAttendeeRole"
                  type="text"
                  :placeholder="$t('journal.role_placeholder')"
                  class="w-full px-2.5 py-2 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-[#00A3C4]"
                />
              </div>
              <div class="sm:col-span-2">
                <button
                  type="button"
                  @click="addCustomAttendee"
                  class="w-full h-full py-2 px-2 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white transition cursor-pointer"
                >
                  + Add
                </button>
              </div>
            </div>

            <!-- List of Attendees with Presence Checkboxes -->
            <div v-if="newEntryForm.attendees.length > 0" class="space-y-1.5 pt-2 max-h-40 overflow-y-auto">
              <div
                v-for="(atd, atdIdx) in newEntryForm.attendees"
                :key="atdIdx"
                class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-200 text-xs"
              >
                <div class="flex items-center space-x-2 min-w-0">
                  <label class="flex items-center space-x-1.5 cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="atd.present"
                      class="rounded text-[#00A3C4] focus:ring-[#00A3C4] w-3.5 h-3.5"
                    />
                    <span :class="atd.present ? 'font-bold text-slate-900' : 'text-slate-400 line-through'">
                      {{ atd.name }}
                    </span>
                  </label>
                  <span v-if="atd.role" class="text-[10px] text-slate-500">({{ atd.role }})</span>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                  <span
                    class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded"
                    :class="atd.present ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ atd.present ? $t('journal.present') : $t('journal.absent') }}
                  </span>
                  <button
                    type="button"
                    @click="removeAttendee(atdIdx)"
                    class="text-slate-400 hover:text-rose-600 font-bold px-1"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Protocol Content -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">
              {{ $t('journal.field_content') }}
            </label>
            <textarea
              v-model="newEntryForm.content"
              rows="6"
              :placeholder="$t('journal.field_content_placeholder')"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            ></textarea>
          </div>

          <!-- Attachments Section -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.attachments') }}</label>
            <div class="p-3 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 text-center hover:bg-slate-100/60 transition cursor-pointer relative">
              <input
                type="file"
                multiple
                @change="handleEntryAttachments"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              />
              <UploadCloud class="w-6 h-6 text-slate-400 mx-auto mb-1" />
              <p class="text-xs font-semibold text-slate-700">{{ $t('journal.drop_files') }}</p>
            </div>

            <!-- Uploaded files preview list -->
            <div v-if="newEntryForm.attachments.length > 0" class="flex flex-wrap gap-2 mt-2">
              <div
                v-for="(att, attIdx) in newEntryForm.attachments"
                :key="attIdx"
                class="flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-cyan-50 border border-cyan-200 text-xs text-cyan-950"
              >
                <span>📎</span>
                <span class="truncate max-w-[140px] font-medium">{{ att.file_name }}</span>
                <span class="text-[10px] text-cyan-700 font-normal">({{ formatFileSize(att.file_size) }})</span>
                <button
                  type="button"
                  @click="removeEntryAttachment(attIdx)"
                  class="text-rose-500 hover:text-rose-700 font-bold ml-1"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
          <!-- AI Analysis Toggle Switch -->
          <div class="p-3.5 rounded-2xl bg-gradient-to-r from-cyan-50/90 via-teal-50/70 to-blue-50/90 border border-cyan-200 shadow-2xs">
            <label class="flex items-start space-x-3 cursor-pointer">
              <input
                type="checkbox"
                v-model="newEntryForm.analyzeWithAi"
                class="mt-1 rounded text-[#00A3C4] focus:ring-[#00A3C4] w-4 h-4"
              />
              <div>
                <div class="flex items-center space-x-1.5">
                  <Sparkles class="w-4 h-4 text-[#00A3C4]" />
                  <span class="text-xs font-black text-cyan-950">{{ $t('journal.analyze_with_ai') }}</span>
                  <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-300">DeepSeek Engine</span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">
                  Generiert eine prägnante Zusammenfassung des Protokolls und schlägt neue Aufgaben & Termine im Kanban-Board vor.
                </p>
                <label v-if="newEntryForm.analyzeWithAi" class="flex items-center space-x-2 mt-2 pt-2 border-t border-cyan-200/60 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="newEntryForm.autoApply"
                    class="rounded text-[#00A3C4] focus:ring-[#00A3C4] w-3.5 h-3.5"
                  />
                  <span class="text-[11px] font-bold text-cyan-900">Vorgeschlagene Aufgaben automatisch direkt im Kanban-Board anlegen</span>
                </label>
              </div>
            </label>
          </div>

          <!-- Footer Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 shrink-0">
            <button
              type="button"
              @click="showNewEntryModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              :disabled="savingJournal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ savingJournal ? $t('journal.saving') : $t('journal.save_entry') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: NEUE NOTIZ & E-MAIL IMPORT (mit KI DeepSeek Engine) -->
    <div
      v-if="showNewNoteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4 shrink-0">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span class="text-xl">✉️</span>
              <span>{{ $t('journal.modal_note_title') }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ $t('journal.modal_note_desc') }}
            </p>
          </div>
          <button
            @click="showNewNoteModal = false"
            type="button"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="saveNewNote" class="space-y-4 overflow-y-auto pr-1 flex-1">
          <div v-if="journalError" class="p-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-900 text-xs font-bold">
            {{ journalError }}
          </div>

          <!-- E-Mail Dropzone & Ingestion Banner -->
          <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-50/90 via-orange-50/70 to-amber-50/90 border border-amber-200 shadow-2xs relative">
            <div class="flex items-start space-x-2.5 mb-2">
              <span class="text-xl">⚡</span>
              <div>
                <h4 class="text-xs font-black text-amber-950">{{ $t('journal.email_ingestion_title') }}</h4>
                <p class="text-[11px] text-amber-900 leading-snug mt-0.5">
                  {{ $t('journal.email_ingestion_desc') }}
                </p>
              </div>
            </div>

            <div class="relative border-2 border-dashed border-amber-300/80 rounded-xl p-3 bg-white/80 text-center hover:bg-white transition cursor-pointer">
              <input
                type="file"
                accept=".eml,.msg,.txt"
                @change="handleNoteDropEml"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              />
              <Mail class="w-5 h-5 text-amber-700 mx-auto mb-1" />
              <p class="text-xs font-bold text-amber-900">.eml oder Textdatei hier ablegen zum automatischen Auslesen</p>
            </div>
          </div>

          <!-- Title -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">
              {{ $t('journal.field_title') }} <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="newNoteForm.title"
              type="text"
              required
              placeholder="z.B. Bauherrenentscheid Farbe Fassade"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>

          <!-- Category & Visibility -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.field_category') }}</label>
              <select
                v-model="newNoteForm.category"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              >
                <option value="email">✉️ {{ $t('journal.category_email') }}</option>
                <option value="allgemein">📝 {{ $t('journal.category_allgemein') }}</option>
                <option value="wetter_behinderung">⛈️ {{ $t('journal.category_wetter_behinderung') }}</option>
                <option value="regie">⏱️ {{ $t('journal.category_regie') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.visibility_label') }}</label>
              <select
                v-model="newNoteForm.visibility"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              >
                <option value="all">🌐 {{ $t('journal.visibility_all') }}</option>
                <option value="company">🏢 {{ $t('journal.visibility_company') }}</option>
                <option value="group">👥 {{ $t('journal.visibility_group') }}</option>
                <option value="only_me">🔒 {{ $t('journal.visibility_only_me') }}</option>
              </select>
            </div>
          </div>

          <!-- Allowed Group if visibility === group -->
          <div v-if="newNoteForm.visibility === 'group'">
            <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.group_label') }}</label>
            <select
              v-model="newNoteForm.allowed_group_id"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            >
              <option :value="null">{{ $t('journal.select_group') }}</option>
              <option v-for="g in userGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>

          <!-- Email Sender Info (if category is email) -->
          <div v-if="newNoteForm.category === 'email'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-amber-50/40 rounded-xl border border-amber-200/60">
            <div>
              <label class="block text-xs font-bold text-amber-950 mb-1">Absender Name</label>
              <input
                v-model="newNoteForm.sender_name"
                type="text"
                placeholder="z.B. Max Muster"
                class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-amber-950 mb-1">Absender E-Mail</label>
              <input
                v-model="newNoteForm.sender_email"
                type="email"
                placeholder="z.B. m.muster@partner.ch"
                class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <!-- Content / Email Body -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">
              {{ $t('journal.field_content') }} <span class="text-rose-500">*</span>
            </label>
            <textarea
              v-model="newNoteForm.content"
              rows="6"
              required
              placeholder="Notiz oder Inhalt der E-Mail hier einfügen..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            ></textarea>
          </div>

          <!-- AI Analysis Toggle Switch -->
          <div class="p-3.5 rounded-2xl bg-gradient-to-r from-cyan-50/90 via-teal-50/70 to-blue-50/90 border border-cyan-200 shadow-2xs">
            <label class="flex items-start space-x-3 cursor-pointer">
              <input
                type="checkbox"
                v-model="newNoteForm.analyzeWithAi"
                class="mt-1 rounded text-[#00A3C4] focus:ring-[#00A3C4] w-4 h-4"
              />
              <div>
                <div class="flex items-center space-x-1.5">
                  <Sparkles class="w-4 h-4 text-[#00A3C4]" />
                  <span class="text-xs font-black text-cyan-950">{{ $t('journal.analyze_with_ai') }}</span>
                  <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-300">DeepSeek Engine</span>
                </div>
                <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">
                  Generiert eine prägnante Zusammenfassung und schlägt interaktive Aktionskarten (neue Aufgaben, Fristverschiebungen, Erledigungen) vor.
                </p>
                <label v-if="newNoteForm.analyzeWithAi" class="flex items-center space-x-2 mt-2 pt-2 border-t border-cyan-200/60 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="newNoteForm.autoApply"
                    class="rounded text-[#00A3C4] focus:ring-[#00A3C4] w-3.5 h-3.5"
                  />
                  <span class="text-[11px] font-bold text-cyan-900">Vorgeschlagene Aufgaben automatisch direkt im Kanban-Board anlegen</span>
                </label>
              </div>
            </label>
          </div>

          <!-- Attachments -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">{{ $t('journal.attachments') }}</label>
            <div class="p-3 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 text-center hover:bg-slate-100/60 transition cursor-pointer relative">
              <input
                type="file"
                multiple
                @change="handleNoteAttachments"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              />
              <UploadCloud class="w-6 h-6 text-slate-400 mx-auto mb-1" />
              <p class="text-xs font-semibold text-slate-700">{{ $t('journal.drop_files') }}</p>
            </div>

            <!-- Uploaded files preview list -->
            <div v-if="newNoteForm.attachments.length > 0" class="flex flex-wrap gap-2 mt-2">
              <div
                v-for="(att, attIdx) in newNoteForm.attachments"
                :key="attIdx"
                class="flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-950"
              >
                <span>📎</span>
                <span class="truncate max-w-[140px] font-medium">{{ att.file_name }}</span>
                <span class="text-[10px] text-amber-700 font-normal">({{ formatFileSize(att.file_size) }})</span>
                <button
                  type="button"
                  @click="removeNoteAttachment(attIdx)"
                  class="text-rose-500 hover:text-rose-700 font-bold ml-1"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 shrink-0">
            <button
              type="button"
              @click="showNewNoteModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              :disabled="savingJournal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg flex items-center space-x-1.5"
            >
              <Sparkles v-if="newNoteForm.analyzeWithAi && !savingJournal" class="w-3.5 h-3.5" />
              <span>
                {{ savingJournal ? (newNoteForm.analyzeWithAi ? $t('journal.analyzing') : $t('journal.saving')) : (newNoteForm.analyzeWithAi ? 'Mit KI analysieren & speichern' : $t('journal.save_note')) }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Invite Member -->
    <div v-if="showInviteMemberModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-black text-slate-900 mb-1">Teammitglied ins Projekt einladen</h3>
        <p class="text-xs text-slate-500 mb-4">
          Im Free Plan sind maximal 5 Mitglieder pro Projekt erlaubt.
        </p>

        <form @submit.prevent="inviteMember" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail des Nutzers</label>
            <input
              v-model="inviteEmail"
              type="email"
              required
              placeholder="kollege@domain.ch"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Rolle im Projekt</label>
            <select
              v-model="inviteRole"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            >
              <option value="editor">Editor (Darf Aufgaben erstellen & bearbeiten)</option>
              <option value="viewer">Viewer (Nur Leserechte)</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showInviteMemberModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Einladen
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Zeit erfassen (Projekt oder Aufgabe) -->
    <div v-if="showProjectTimeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-black text-slate-900 mb-1">⏱️ Zeit erfassen</h3>
        <p class="text-xs text-slate-500 mb-4">
          Buche geleistete Stunden auf dieses Gesamtprojekt oder auf eine konkrete Aufgabe.
        </p>

        <form @submit.prevent="saveProjectTime" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Rapportieren auf</label>
            <select
              v-model="projectTimeForm.task_id"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            >
              <option value="">🏢 Gesamtprojekt (ohne Aufgabe)</option>
              <option v-for="t in allProjectTasks" :key="t.id" :value="t.id">
                📋 Aufgabe: {{ t.title }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Dauer (Stunden)</label>
              <input
                v-model="projectTimeForm.duration_hours"
                type="number"
                step="0.25"
                min="0.05"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Datum</label>
              <input
                v-model="projectTimeForm.entry_date"
                type="date"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Stundensatz ({{ project?.currency || 'CHF' }})</label>
            <input
              v-model="projectTimeForm.hourly_rate"
              type="number"
              step="5"
              min="0"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tätigkeit / Beschreibung</label>
            <textarea
              v-model="projectTimeForm.description"
              rows="3"
              placeholder="Was wurde erledigt?"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showProjectTimeModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Zeit buchen
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Zeiteintrag bearbeiten -->
    <div v-if="showEditTimeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-black text-slate-900 mb-1">✏️ Zeiteintrag anpassen</h3>
        <p class="text-xs text-slate-500 mb-4">
          Manuell angepasste Einträge werden im Protokoll mit einem Stern (*) gekennzeichnet.
        </p>

        <form @submit.prevent="saveEditTime" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Dauer (Stunden)</label>
              <input
                v-model="editTimeForm.duration_hours"
                type="number"
                step="0.25"
                min="0.05"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Datum</label>
              <input
                v-model="editTimeForm.entry_date"
                type="date"
                required
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Stundensatz ({{ project?.currency || 'CHF' }})</label>
            <input
              v-model="editTimeForm.hourly_rate"
              type="number"
              step="5"
              min="0"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tätigkeit / Beschreibung</label>
            <textarea
              v-model="editTimeForm.description"
              rows="3"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showEditTimeModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Änderungen speichern
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Projektkontakt anlegen / bearbeiten -->
    <div
      v-if="showProjectContactModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm animate-in fade-in"
    >
      <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4 shrink-0">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span>{{ isEditingProjectContact ? '✏️' : '📇' }}</span>
              <span>{{ isEditingProjectContact ? 'Projektkontakt bearbeiten' : 'Neuen Kontakt für dieses Projekt' }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Dieser Kontakt ist automatisch für alle Projekt- und Ordnermitglieder sichtbar.
            </p>
          </div>
          <button
            @click="showProjectContactModal = false"
            type="button"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="saveProjectContact" class="space-y-3.5 overflow-y-auto pr-1 flex-1">
          <div v-if="projectContactError" class="p-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-900 text-xs font-bold">
            {{ projectContactError }}
          </div>

          <!-- KI-Autofill Assistent Box -->
          <div class="p-3.5 rounded-2xl bg-gradient-to-r from-cyan-50 via-teal-50 to-blue-50 border border-cyan-200 shadow-xs">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center space-x-2">
                <span class="text-base">✨</span>
                <span class="text-xs font-black text-slate-900">KI-Autofill Assistent</span>
              </div>
              <button
                @click="showAiInputProject = !showAiInputProject"
                type="button"
                class="text-[11px] font-bold text-cyan-800 hover:text-cyan-950 underline cursor-pointer"
              >
                {{ showAiInputProject ? 'Eingabe schließen' : 'Öffnen' }}
              </button>
            </div>

            <div v-if="showAiInputProject" class="space-y-2.5 mt-3 pt-3 border-t border-cyan-200/60">
              <p class="text-[11px] text-slate-600">
                Füge Text, Visitenkartendaten oder eine E-Mail-Signatur ein. Die KI trägt die Daten automatisch ins Formular ein:
              </p>
              <textarea
                v-model="aiRawTextProject"
                rows="3"
                placeholder="Beispiel: Hans Peter, Bauleiter bei Steiner Tiefbau AG in Zürich, Tel 044 123 45 67, Mobile 079 987 65 43, h.peter@steiner.ch"
                class="w-full px-3 py-2 bg-white border border-cyan-300 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              ></textarea>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span v-if="aiStatusMessageProject" class="text-[11px] font-bold" :class="aiStatusSuccessProject ? 'text-emerald-700' : 'text-rose-600'">
                  {{ aiStatusMessageProject }}
                </span>
                <span v-else class="text-[10px] text-slate-400">Texte werden sicher verarbeitet</span>

                <button
                  @click="runAiExtractionProject"
                  type="button"
                  :disabled="aiLoadingProject || !aiRawTextProject.trim()"
                  class="taskster_button px-4 text-xs h-[36px] rounded-lg self-end sm:self-auto"
                >
                  <span v-if="aiLoadingProject" class="animate-spin text-sm">⏳</span>
                  <span v-else>✨</span>
                  <span>{{ aiLoadingProject ? 'KI analysiert...' : 'Automatisch ausfüllen' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Duplikate-Warnung Banner -->
          <div
            v-if="projectDuplicateCandidate"
            class="p-4 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 shadow-xs animate-in fade-in"
          >
            <div class="flex items-start space-x-2.5">
              <span class="text-xl shrink-0">⚠️</span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <h4 class="text-xs font-black text-amber-950">
                    Duplikat-Schutz: Ähnlicher Kontakt existiert bereits
                  </h4>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-200 text-amber-900 border border-amber-300">
                    Bereits vorhanden
                  </span>
                </div>
                <p class="text-xs text-amber-900 mt-1 leading-snug">
                  Ein Kontakt mit ähnlichen Merkmalen (Name, E-Mail oder Telefon) existiert bereits in diesem Projekt:
                  <strong class="font-black text-slate-900">{{ (projectDuplicateCandidate.first_name ? projectDuplicateCandidate.first_name + ' ' : '') + projectDuplicateCandidate.last_name }}</strong>
                  <span v-if="projectDuplicateCandidate.company_name" class="font-bold text-amber-950"> ({{ projectDuplicateCandidate.company_name }})</span>
                </p>
                <div class="text-[11px] text-amber-800 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                  <span v-if="projectDuplicateCandidate.email">✉️ {{ projectDuplicateCandidate.email }}</span>
                  <span v-if="projectDuplicateCandidate.mobile">📱 {{ projectDuplicateCandidate.mobile }}</span>
                  <span v-if="projectDuplicateCandidate.phone">📞 {{ projectDuplicateCandidate.phone }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2 mt-3 pt-2.5 border-t border-amber-200/80">
                  <button
                    @click="openEditProjectContactModal(projectDuplicateCandidate)"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white hover:bg-amber-100 text-amber-950 border border-amber-300 transition cursor-pointer"
                  >
                    ✏️ Bestehenden Kontakt bearbeiten
                  </button>
                  <button
                    @click="mergeIntoExistingProjectContact(projectDuplicateCandidate)"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white transition cursor-pointer"
                  >
                    ⚡ Daten zusammenführen (Merge)
                  </button>
                  <button
                    @click="allowDuplicateProject = true"
                    type="button"
                    class="text-[11px] font-bold text-amber-900 hover:underline ml-auto cursor-pointer"
                  >
                    Trotzdem neu anlegen
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">
                Nachname / Name <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="projectContactForm.last_name"
                type="text"
                required
                placeholder="z.B. Keller"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Vorname</label>
              <input
                v-model="projectContactForm.first_name"
                type="text"
                placeholder="z.B. Stefan"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Firma / Unternehmen</label>
              <input
                v-model="projectContactForm.company_name"
                type="text"
                placeholder="z.B. Elektro Meier AG"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Funktion / Gewerk</label>
              <input
                v-model="projectContactForm.role_function"
                type="text"
                placeholder="z.B. Polier, Vorarbeiter, Bauleiter"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Mobile (Handy)</label>
              <input
                v-model="projectContactForm.mobile"
                type="tel"
                placeholder="+41 79 ..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Telefon Festnetz</label>
              <input
                v-model="projectContactForm.phone"
                type="tel"
                placeholder="+41 44 ..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">E-Mail</label>
            <input
              v-model="projectContactForm.email"
              type="email"
              placeholder="keller@elektromeier.ch"
              class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Geschäftsadresse</label>
              <input
                v-model="projectContactForm.address"
                type="text"
                placeholder="z.B. Bahnhofstrasse 12, 8001 Zürich"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Webseite</label>
              <input
                v-model="projectContactForm.website"
                type="text"
                placeholder="z.B. https://www.elektromeier.ch"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Gruppe / Kategorie</label>
            <input
              v-model="projectContactForm.category_group"
              type="text"
              placeholder="z.B. Handwerker, Planer, Behörde"
              class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Notizen</label>
            <textarea
              v-model="projectContactForm.notes"
              rows="2"
              placeholder="Notizen zur Baustelle, Schlüsselzugang, etc."
              class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#00A3C4]"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 shrink-0">
            <button
              @click="showProjectContactModal = false"
              type="button"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingProjectContact"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ savingProjectContact ? 'Speichert...' : (isEditingProjectContact ? 'Änderungen speichern' : 'Kontakt speichern') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Voice Recorder (Whisper v3 Turbo) -->
  <VoiceRecorderModal
    v-model="showVoiceModal"
    :default-project-id="project?.id"
    :projects="[project].filter(Boolean)"
    @saved="onVoiceNoteSaved"
  />

  <!-- DELETE PROJECT CONFIRMATION MODAL -->
  <div v-if="showDeleteProjectModal && project" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
      <div class="flex items-center space-x-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
          <AlertTriangle class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-black text-slate-900">{{ $t('dashboard.projekt_loeschen_titel') }}</h3>
          <p class="text-xs text-slate-500 font-medium">{{ project.title }}</p>
        </div>
      </div>

      <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed mb-5">
        {{ $t('dashboard.projekt_loeschen_confirm', { title: project.title }) }}
      </div>

      <div v-if="deleteProjectError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
        {{ deleteProjectError }}
      </div>

      <div class="flex items-center justify-end space-x-3">
        <button
          type="button"
          @click="showDeleteProjectModal = false; deleteProjectError = ''"
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
</template>

<script setup lang="ts">
import {
  LayoutDashboard,
  Folder,
  ClipboardList,
  Plus,
  Pencil,
  Trash2,
  Clock,
  UserPlus,
  FileText,
  Tag,
  Paperclip,
  Search,
  X,
  Check,
  ChevronRight,
  BookUser,
  Building2,
  HardHat,
  Lock,
  Phone,
  Mail,
  Globe,
  MapPin,
  Mic,
  ExternalLink,
  MoreVertical,
  AlertTriangle,
  BookOpen,
  Sparkles,
  CheckCircle2,
  Users,
  Contact,
  Settings,
  LayoutGrid,
  UploadCloud,
  Download,
  Send,
  ListFilter
} from 'lucide-vue-next'
import * as XLSX from 'xlsx'
import { TEMPLATE_CUSTOM_FIELDS } from '~/composables/useProjectTemplates'

const { t, te } = useI18n()

const route = useRoute()
const { user, authHeaders } = useAuth()
const projectId = route.params.id as string
const showVoiceModal = ref(false)

const onVoiceNoteSaved = (payload: any) => {
  if (payload.type === 'journal') {
    loadJournals()
  } else if (payload.type === 'task') {
    loadProjectData()
  }
}

const project = ref<any>(null)
const userRole = ref<'owner' | 'admin' | 'editor' | 'viewer'>('viewer')
const lists = ref<any[]>([])
const fields = ref<any[]>([])
const members = ref<any[]>([])
const journalEntries = ref<any[]>([])
const loading = ref(true)

const currentView = ref<'tasks' | 'journal' | 'team' | 'settings' | 'time' | 'contacts'>('tasks')
const taskViewMode = ref<'board' | 'table'>('board')
const showActionsMenu = ref(false)

// Free-/Single-User (ohne Company) sehen die Ordner-Ebene nicht.
const isFreeUser = computed(() => !user.value?.is_pro && !user.value?.company_id && !user.value?.is_superadmin)

// Projekt-Kontakte State
const projectContacts = ref<any[]>([])
const loadingProjectContacts = ref(false)
const showProjectContactModal = ref(false)
const isEditingProjectContact = ref(false)
const savingProjectContact = ref(false)
const projectContactError = ref('')
const showAiInputProject = ref(false)
const aiRawTextProject = ref('')
const aiLoadingProject = ref(false)
const aiStatusMessageProject = ref('')
const aiStatusSuccessProject = ref(false)

const projectContactForm = ref({
  id: '',
  first_name: '',
  last_name: '',
  company_name: '',
  role_function: '',
  phone: '',
  mobile: '',
  email: '',
  address: '',
  website: '',
  category_group: 'Handwerker',
  notes: ''
})

const allowDuplicateProject = ref(false)

const projectDuplicateCandidate = computed(() => {
  if (isEditingProjectContact.value || allowDuplicateProject.value) return null

  const ln = projectContactForm.value.last_name.trim().toLowerCase()
  const fn = projectContactForm.value.first_name.trim().toLowerCase()
  const em = projectContactForm.value.email.trim().toLowerCase()
  const mobDigits = projectContactForm.value.mobile.replace(/\D+/g, '')

  if (!ln && !em && mobDigits.length < 6) return null

  return projectContacts.value.find((c: any) => {
    if (c.id === projectContactForm.value.id) return false
    if (em && c.email && c.email.trim().toLowerCase() === em) return true
    if (mobDigits.length >= 6) {
      const cMob = (c.mobile || '').replace(/\D+/g, '')
      const cPh = (c.phone || '').replace(/\D+/g, '')
      if (cMob.length >= 6 && (cMob.endsWith(mobDigits.slice(-7)) || mobDigits.endsWith(cMob.slice(-7)))) return true
      if (cPh.length >= 6 && (cPh.endsWith(mobDigits.slice(-7)) || mobDigits.endsWith(cPh.slice(-7)))) return true
    }
    if (ln && fn && c.last_name && c.first_name) {
      if (c.last_name.trim().toLowerCase() === ln && c.first_name.trim().toLowerCase() === fn) {
        return true
      }
    }
    return false
  }) || null
})

const mergeIntoExistingProjectContact = async (target: any) => {
  if (!target) return
  savingProjectContact.value = true
  projectContactError.value = ''

  const merged = {
    first_name: projectContactForm.value.first_name.trim() || target.first_name || '',
    last_name: projectContactForm.value.last_name.trim() || target.last_name || '',
    company_name: projectContactForm.value.company_name.trim() || target.company_name || '',
    role_function: projectContactForm.value.role_function.trim() || target.role_function || '',
    phone: projectContactForm.value.phone.trim() || target.phone || '',
    mobile: projectContactForm.value.mobile.trim() || target.mobile || '',
    email: projectContactForm.value.email.trim() || target.email || '',
    address: projectContactForm.value.address.trim() || target.address || '',
    website: projectContactForm.value.website.trim() || target.website || '',
    project_id: projectId,
    category_group: projectContactForm.value.category_group || target.category_group || 'Handwerker',
    notes: [target.notes, projectContactForm.value.notes.trim()].filter(Boolean).join('\n'),
    share_scope: 'private'
  }

  try {
    await $fetch(`/api/contacts/${target.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: merged
    })
    showProjectContactModal.value = false
    await loadProjectContacts()
  } catch (err: any) {
    projectContactError.value = err.data?.statusMessage || 'Fehler beim Zusammenführen des Kontakts'
  } finally {
    savingProjectContact.value = false
  }
}

const formatProjectContactUrl = (url?: string) => {
  if (!url) return ''
  const trimmed = url.trim()
  return /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`
}

// Live Stopwatch Integration
const {
  state: stopwatchState,
  startTimer,
  openStopModal,
  discardTimer,
  formatSeconds
} = useStopwatch()

const startProjectTimer = () => {
  if (!project.value) return
  startTimer({
    projectId: project.value.id,
    projectTitle: project.value.title,
    projectCurrency: project.value.currency || 'CHF'
  })
}

const startTaskTimer = (task: any) => {
  if (!project.value || !task) return
  startTimer({
    projectId: project.value.id,
    projectTitle: project.value.title,
    projectCurrency: project.value.currency || 'CHF',
    taskId: task.id,
    taskTitle: task.title
  })
}

const onGlobalTimeEntrySaved = async () => {
  await loadProjectTimeEntries()
  try {
    const pRes = await $fetch<any>(`/api/projects/${projectId}`, { headers: authHeaders() })
    if (pRes.project) {
      project.value.tracked_hours = pRes.project.tracked_hours
    }
  } catch (e) {}

  if (showTaskDrawer.value && drawerTask.value?.id) {
    try {
      const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}`, { headers: authHeaders() })
      if (res.task) {
        drawerTask.value.tracked_hours = res.task.tracked_hours
        for (const l of lists.value) {
          const found = l.tasks?.find((t: any) => t.id === drawerTask.value.id)
          if (found) {
            found.tracked_hours = res.task.tracked_hours
            break
          }
        }
      }
      drawerTimeEntries.value = res.timeEntries || []
    } catch (e) {}
  } else {
    try {
      await loadProjectData()
    } catch (e) {}
  }
}

// Zeiterfassung State
const projectTimeEntries = ref<any[]>([])
const projectTimeSummary = ref<{ totalMinutes: number; totalHours: number; totalCost: number }>({ totalMinutes: 0, totalHours: 0, totalCost: 0 })
const loadingTimeEntries = ref(false)
const timeFilterTask = ref('')
const timeFilterUser = ref('')

// Direct project time modal
const showProjectTimeModal = ref(false)
const projectTimeForm = ref({
  task_id: '',
  duration_hours: 1,
  entry_date: new Date().toISOString().substring(0, 10),
  description: '',
  hourly_rate: 0
})

// Edit time entry modal (used both for project & task drawer)
const showEditTimeModal = ref(false)
const editingTimeEntry = ref<any>(null)
const editTimeForm = ref({
  id: '',
  duration_hours: 0,
  entry_date: '',
  description: '',
  hourly_rate: 0
})

// Task drawer quick time form
const drawerTimeForm = ref({
  duration_hours: 1,
  entry_date: new Date().toISOString().substring(0, 10),
  description: '',
  hourly_rate: 0
})
const drawerTimeEntries = ref<any[]>([])

// Drag & Drop
const draggedTask = ref<any>(null)
const sourceListId = ref<string>('')
const dragOverListId = ref<string>('')

// Modals
const showNewListModal = ref(false)
const newListTitle = ref('')
const newListAccessMode = ref('inherit')

const showTaskModal = ref(false)
const isEditingTask = ref(false)
const targetListId = ref('')
const currentEditingTaskId = ref('')
const taskForm = ref<any>({
  title: '',
  description: '',
  status: 'todo',
  due_date: '',
  custom_data: {}
})

// Custom Field Creation Modal
const showNewFieldModal = ref(false)
const editingFieldId = ref('')
const newFieldLabel = ref('')
const newFieldType = ref('text')
const newFieldEntityType = ref<'task' | 'project'>('task')
const newFieldOptionsRaw = ref('')
const enableFieldLogic = ref(false)
const logicDependsOnField = ref('')
const logicDependsOnValue = ref('')

const openNewFieldModal = () => {
  editingFieldId.value = ''
  newFieldLabel.value = ''
  newFieldType.value = 'text'
  newFieldEntityType.value = 'task'
  newFieldOptionsRaw.value = ''
  enableFieldLogic.value = false
  logicDependsOnField.value = ''
  logicDependsOnValue.value = ''
  showNewFieldModal.value = true
}

const editField = (f: any) => {
  editingFieldId.value = f.id
  newFieldLabel.value = f.label
  newFieldType.value = f.field_type
  newFieldEntityType.value = f.entity_type || 'task'
  newFieldOptionsRaw.value = f.options ? f.options.join(', ') : ''
  if (f.logic_rules && f.logic_rules.depends_on_field) {
    enableFieldLogic.value = true
    logicDependsOnField.value = f.logic_rules.depends_on_field
    logicDependsOnValue.value = f.logic_rules.depends_on_value
  } else {
    enableFieldLogic.value = false
    logicDependsOnField.value = ''
    logicDependsOnValue.value = ''
  }
  showNewFieldModal.value = true
}

const saveCustomField = async () => {
  if (!newFieldLabel.value.trim()) return
  const optionsList = newFieldType.value === 'select'
    ? newFieldOptionsRaw.value.split(/[,;\n]/).map((o: string) => o.trim()).filter(Boolean)
    : []

  const payload: any = {
    label: newFieldLabel.value.trim(),
    field_type: newFieldType.value,
    entity_type: newFieldEntityType.value,
    options: optionsList
  }

  if (enableFieldLogic.value && logicDependsOnField.value) {
    payload.logic_rules = {
      depends_on_field: logicDependsOnField.value,
      depends_on_value: logicDependsOnValue.value
    }
  } else {
    payload.logic_rules = null
  }

  try {
    if (editingFieldId.value) {
      await $fetch(`/api/folders/${project.value.folder_id}/fields/${editingFieldId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: payload
      })
    } else {
      await $fetch(`/api/folders/${project.value.folder_id}/fields`, {
        method: 'POST',
        headers: authHeaders(),
        body: payload
      })
    }
    showNewFieldModal.value = false
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern des Zusatzfeldes')
  }
}

const deleteCustomField = async (fieldId: string) => {
  const f = fields.value.find((item: any) => item.id === fieldId)
  if (!f) return

  // Prüfe ob Feld in aktiven Aufgaben oder im Projekt genutzt wird
  const isUsedInTasks = allProjectTasks.value.some((t: any) => t.custom_data && t.custom_data[f.field_key] !== undefined && t.custom_data[f.field_key] !== '' && t.custom_data[f.field_key] !== null)
  const isUsedInProject = project.value?.custom_data && project.value.custom_data[f.field_key] !== undefined && project.value.custom_data[f.field_key] !== '' && project.value.custom_data[f.field_key] !== null

  if (isUsedInTasks || isUsedInProject) {
    alert(`Das Zusatzfeld "${f.label}" ist in aktiven Aufgaben oder im Projekt ausgefüllt und kann solange nicht gelöscht werden.`)
    return
  }

  if (!confirm(`Möchtest du das Zusatzfeld "${f.label}" wirklich löschen?`)) return

  try {
    await $fetch(`/api/folders/${project.value.folder_id}/fields/${fieldId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    showNewFieldModal.value = false
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Zusatzfeldes')
  }
}

// Project Settings form
const settingsForm = ref<any>({
  title: '',
  status: 'active',
  currency: 'CHF',
  budget_hours: null,
  budget_amount: null,
  visibility: 'private',
  custom_data: {}
})
const savingProjectSettings = ref(false)

// Projektjournal State & Filter
const showNewEntryModal = ref(false)
const showNewNoteModal = ref(false)
const showNewJournalModal = showNewEntryModal // backwards compatibility
const savingJournal = ref(false)
const journalError = ref('')
const journalFilterType = ref<'all' | 'entry' | 'note'>('all')
const journalFilterCategory = ref('all')
const journalSearchQuery = ref('')
const expandedMailIds = ref<Record<string, boolean>>({})
const userGroups = ref<any[]>([])

// Attendees Helper State
const newEntryAttendeeName = ref('')
const newEntryAttendeeEmail = ref('')
const newEntryAttendeeRole = ref('')
const selectedContactToAdd = ref('')
const availableContacts = ref<any[]>([])

const attendeeContactOptions = computed(() => {
  const map = new Map<string, any>()
  for (const c of projectContacts.value || []) {
    map.set(c.id, { ...c, isProject: true })
  }
  for (const c of availableContacts.value || []) {
    if (!map.has(c.id)) {
      map.set(c.id, { ...c, isProject: false })
    }
  }
  return Array.from(map.values())
})

const loadAvailableContacts = async () => {
  try {
    const res = await $fetch<any>('/api/contacts', { headers: authHeaders() })
    availableContacts.value = res.contacts || []
  } catch (err) {
    availableContacts.value = []
  }
}

const analyzingEntryId = ref<string | null>(null)
const isApplyingAllId = ref<string | null>(null)

// Form Models
const newEntryForm = ref<any>({
  title: '',
  type: 'entry',
  category: 'bausitzung',
  visibility: 'all',
  allowed_group_id: null,
  task_id: null,
  entry_date: new Date().toISOString().slice(0, 10),
  content: '',
  analyzeWithAi: true,
  autoApply: false,
  attendees: [] as any[],
  attachments: [] as any[]
})

const newNoteForm = ref<any>({
  title: '',
  type: 'note',
  category: 'email',
  visibility: 'all',
  allowed_group_id: null,
  sender_name: '',
  sender_email: '',
  content: '',
  analyzeWithAi: true,
  autoApply: false,
  attachments: [] as any[]
})

const journalForm = newEntryForm // backwards compatibility

const entriesCount = computed(() => {
  return (journalEntries.value || []).filter((e: any) => e.type === 'entry').length
})

const notesCount = computed(() => {
  return (journalEntries.value || []).filter((e: any) => e.type === 'note').length
})

const filteredJournals = computed(() => {
  let list = [...(journalEntries.value || [])]
  if (journalFilterType.value !== 'all') {
    list = list.filter((e: any) => e.type === journalFilterType.value)
  }
  if (journalFilterCategory.value !== 'all') {
    list = list.filter((e: any) => e.category === journalFilterCategory.value)
  }
  if (journalSearchQuery.value.trim()) {
    const q = journalSearchQuery.value.trim().toLowerCase()
    list = list.filter((e: any) =>
      (e.title && e.title.toLowerCase().includes(q)) ||
      (e.content && e.content.toLowerCase().includes(q)) ||
      (e.author_name && e.author_name.toLowerCase().includes(q)) ||
      (e.metadata?.ai_summary && e.metadata.ai_summary.toLowerCase().includes(q))
    )
  }
  return list
})

const showInviteMemberModal = ref(false)
const inviteEmail = ref('')
const inviteRole = ref('editor')

// Task Detail Drawer / Modal
const showTaskDrawer = ref(false)
const isCreatingTaskInDrawer = ref(false)
const drawerTask = ref<any>(null)
const drawerSubtasks = ref<any[]>([])
const drawerComments = ref<any[]>([])
const drawerDocuments = ref<any[]>([])
const uploadingFiles = ref<any[]>([])
const dragOverFiles = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const newTagInput = ref('')
const newChecklistInput = ref('')
const newSubtaskInput = ref('')
const itemSubtaskInputs = ref<Record<string, string>>({})
const newCommentInput = ref('')
const showDrawerTimeMenu = ref(false)
const highlightedTaskId = ref<string | null>(null)

// Section Pastel Colors
const sectionPastelColors = [
  { value: 'rgba(238, 242, 255, 0.95)', label: 'Indigo Soft' },
  { value: 'rgba(236, 253, 245, 0.95)', label: 'Mint Soft' },
  { value: 'rgba(254, 243, 199, 0.95)', label: 'Amber Soft' },
  { value: 'rgba(255, 241, 242, 0.95)', label: 'Rose Soft' },
  { value: 'rgba(243, 232, 255, 0.95)', label: 'Lila Soft' },
  { value: 'rgba(240, 253, 250, 0.95)', label: 'Cyan Soft' },
  { value: 'rgba(241, 245, 249, 0.95)', label: 'Slate Soft' },
]

// CSV / Excel Import State
const showImportModal = ref(false)
const importStep = ref(1)
const importFileName = ref('')
const importHeaders = ref<string[]>([])
const importParsedRows = ref<string[][]>([])
const importColumnMapping = ref<Record<number, string>>({})
const importTargetListId = ref('')
const importingTasks = ref(false)
const importError = ref('')
const importSuccessCount = ref(0)
const csvFileInput = ref<HTMLInputElement | null>(null)

const taskColors = [
  { value: '#00A3C4', label: 'Cyan' },
  { value: '#8B5CF6', label: 'Lila' },
  { value: '#F59E0B', label: 'Amber' },
  { value: '#10B981', label: 'Smaragd' },
  { value: '#EF4444', label: 'Rot' },
  { value: '#EC4899', label: 'Pink' },
  { value: '#6366F1', label: 'Indigo' },
  { value: '#64748B', label: 'Slate' },
]

const visibleDrawerFields = computed(() => {
  if (!drawerTask.value) return []
  return taskCustomFields.value.filter((f: any) => {
    if (!f.logic_rules || !f.logic_rules.depends_on_field) return true
    const depVal = drawerTask.value.custom_data?.[f.logic_rules.depends_on_field]
    return depVal === f.logic_rules.depends_on_value
  })
})

const totalTasks = computed(() => {
  return lists.value.reduce((acc: number, l: any) => acc + (l.tasks?.length || 0), 0)
})

const taskCustomFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type !== 'project')
})

const projectCustomFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type === 'project')
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
  return commonCustomFieldTemplates.filter((t: any) => !existingKeys.has(t.key))
}

const getSectionTitle = (titleOrId: string) => {
  if (!titleOrId) return ''
  const foundList = lists.value.find((l: any) => l.id === titleOrId)
  const title = foundList ? foundList.title : titleOrId
  if (title.startsWith('sections.') || te(title)) {
    return t(title)
  }
  return title
}

const allProjectTasks = computed(() => {
  const arr: any[] = []
  for (const l of lists.value) {
    if (l.tasks) {
      for (const t of l.tasks) {
        arr.push(t)
      }
    }
  }
  return arr
})

const filteredTimeEntries = computed(() => {
  return projectTimeEntries.value.filter((entry: any) => {
    if (timeFilterTask.value === '__project__' && entry.task_id) return false
    if (timeFilterTask.value && timeFilterTask.value !== '__project__' && entry.task_id !== timeFilterTask.value) return false
    if (timeFilterUser.value && entry.user_id !== timeFilterUser.value) return false
    return true
  })
})

const getFieldLabel = (key: string) => {
  const f = fields.value.find((item: any) => item.field_key === key)
  if (f) return f.label_key ? t(f.label_key) : f.label
  const tpl = commonCustomFieldTemplates.find((item: any) => item.key === key)
  if (tpl) return tpl.label_key ? t(tpl.label_key) : tpl.label
  return key
}

const formatCustomFieldValue = (val: any, fieldKey?: string) => {
  if (val === true || val === 'true') return '✓ Ja'
  if (val === false || val === 'false') return 'Nein'
  if (val === null || val === undefined || val === '') return '-'
  if (fieldKey) {
    const f = fields.value.find((item: any) => item.field_key === fieldKey)
    if (f && f.options && f.options.length) {
      const opt = f.options.find((o: any) => (typeof o === 'object' ? o.value : o) === String(val))
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

// Check conditional visibility of a task field
const isFieldVisibleForTask = (f: any) => {
  if (!f.logic_rules || !f.logic_rules.depends_on_field) return true
  const depField = f.logic_rules.depends_on_field
  const expectedVal = f.logic_rules.depends_on_value
  const currentVal = taskForm.value.custom_data[depField]
  return currentVal == expectedVal
}

const onDragStart = (task: any, listId: string) => {
  if (userRole.value === 'viewer') return
  draggedTask.value = task
  sourceListId.value = listId
}

const onDragEnd = () => {
  draggedTask.value = null
  sourceListId.value = ''
  dragOverListId.value = ''
}

const onDragOverList = (listId: string) => {
  if (userRole.value === 'viewer' || !draggedTask.value) return
  dragOverListId.value = listId
}

const onDragLeaveList = (listId: string) => {
  if (dragOverListId.value === listId) {
    dragOverListId.value = ''
  }
}

const onDropToList = async (targetListId: string) => {
  if (userRole.value === 'viewer' || !draggedTask.value) return
  const taskToMove = draggedTask.value
  const fromListId = sourceListId.value
  dragOverListId.value = ''
  draggedTask.value = null
  sourceListId.value = ''

  if (fromListId === targetListId) return

  const sourceList = lists.value.find((l: any) => l.id === fromListId)
  const targetList = lists.value.find((l: any) => l.id === targetListId)
  if (!sourceList || !targetList) return

  sourceList.tasks = (sourceList.tasks || []).filter((t: any) => t.id !== taskToMove.id)
  taskToMove.list_id = targetListId
  targetList.tasks = targetList.tasks || []
  targetList.tasks.push(taskToMove)

  try {
    await $fetch(`/api/tasks/${taskToMove.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        list_id: targetListId,
        sort_order: targetList.tasks.length
      }
    })
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Konnte Aufgabe nicht verschieben')
    await loadProjectData()
  }
}

const draggedBoardSection = ref<any>(null)
// Referenzen auf die Spalten-Container (für das Drag-Ghost-Image)
const sectionCols = ref<any[]>([])

const onSectionDragStart = (section: any, e: DragEvent) => {
  if (userRole.value === 'viewer') return
  draggedBoardSection.value = section
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', section.id)

    // Ganze Spalte als Drag-Vorschau verwenden (statt nur des schmalen Headers)
    const idx = lists.value.findIndex((l: any) => l.id === section.id)
    const colEl = idx >= 0 ? sectionCols.value[idx] : null
    if (colEl && typeof e.dataTransfer.setDragImage === 'function') {
      const rect = colEl.getBoundingClientRect()
      const offsetX = e.clientX - rect.left
      const offsetY = e.clientY - rect.top
      e.dataTransfer.setDragImage(colEl, offsetX, offsetY)
    }
  }
}

const onSectionDragOver = (section: any, e: DragEvent) => {
  if (userRole.value === 'viewer' || !draggedBoardSection.value) return
  if (draggedBoardSection.value.id !== section.id) {
    e.preventDefault()
    dragOverListId.value = section.id
  }
}

const onSectionDrop = async (targetSection: any, e: DragEvent) => {
  if (userRole.value === 'viewer' || !draggedBoardSection.value) return
  const src = draggedBoardSection.value
  draggedBoardSection.value = null
  if (src.id === targetSection.id) return

  const srcIdx = lists.value.findIndex((l: any) => l.id === src.id)
  const targetIdx = lists.value.findIndex((l: any) => l.id === targetSection.id)
  if (srcIdx === -1 || targetIdx === -1) return

  // Reorder locally
  const [removed] = lists.value.splice(srcIdx, 1)
  lists.value.splice(targetIdx, 0, removed)

  try {
    const payloadLists = lists.value.map((l: any, idx: number) => ({ id: l.id, sort_order: idx }))
    await $fetch(`/api/projects/${projectId}/lists/reorder`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { lists: payloadLists }
    })
  } catch (err: any) {
    console.error('Failed to save section reorder:', err)
    await loadProjectData()
  }
}

const loadProjectData = async () => {
  loading.value = true
  try {
    const res = await $fetch<any>(`/api/projects/${projectId}`, {
      headers: authHeaders()
    })
    project.value = res.project
    userRole.value = res.userRole
    lists.value = res.lists || []
    fields.value = res.fields || []
    members.value = res.members || []
    if (currentView.value === 'time') {
      await loadProjectTimeEntries()
    }
    loadProjectContacts()
    loadAvailableContacts()
  } catch (err: any) {
    if (err.statusCode === 404) {
      alert('Zugriff verweigert oder Projekt nicht gefunden.')
      navigateTo('/dashboard')
    }
  } finally {
    loading.value = false
  }
}

const loadProjectContacts = async () => {
  loadingProjectContacts.value = true
  try {
    const res = await $fetch<any>(`/api/contacts?project_id=${projectId}`, { headers: authHeaders() })
    projectContacts.value = res.contacts || []
  } catch (err) {
    projectContacts.value = []
  } finally {
    loadingProjectContacts.value = false
  }
}

const openAddProjectContactModal = () => {
  isEditingProjectContact.value = false
  allowDuplicateProject.value = false
  projectContactError.value = ''
  showAiInputProject.value = false
  aiRawTextProject.value = ''
  aiStatusMessageProject.value = ''
  aiStatusSuccessProject.value = false
  projectContactForm.value = {
    id: '',
    first_name: '',
    last_name: '',
    company_name: '',
    role_function: '',
    phone: '',
    mobile: '',
    email: '',
    address: '',
    website: '',
    category_group: 'Handwerker',
    notes: ''
  }
  showProjectContactModal.value = true
}

const openEditProjectContactModal = (contact: any) => {
  isEditingProjectContact.value = true
  projectContactError.value = ''
  showAiInputProject.value = false
  aiRawTextProject.value = ''
  aiStatusMessageProject.value = ''
  aiStatusSuccessProject.value = false
  projectContactForm.value = {
    id: contact.id,
    first_name: contact.first_name || '',
    last_name: contact.last_name || '',
    company_name: contact.company_name || '',
    role_function: contact.role_function || '',
    phone: contact.phone || '',
    mobile: contact.mobile || '',
    email: contact.email || '',
    address: contact.address || '',
    website: contact.website || '',
    category_group: contact.category_group || 'Handwerker',
    notes: contact.notes || ''
  }
  showProjectContactModal.value = true
}

const runAiExtractionProject = async () => {
  if (!aiRawTextProject.value.trim()) return
  aiLoadingProject.value = true
  aiStatusMessageProject.value = 'KI extrahiert Daten...'
  aiStatusSuccessProject.value = false

  try {
    const res = await $fetch<{ success: boolean; text?: string; response?: string }>('/api/ai/chat', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        prompt: `Extrahiere alle Kontaktdaten aus folgendem Text und gib ein valides JSON-Objekt mit genau folgenden Feldern zurück:
- first_name: string (Vorname, falls vorhanden)
- last_name: string (Nachname oder Firmenname falls kein Personenname)
- company_name: string (Firma/Organisation)
- role_function: string (Funktion/Gewerk/Berufsbezeichnung)
- phone: string (Festnetznummer)
- mobile: string (Mobilfunknummer)
- email: string (E-Mail)
- address: string (Geschäftsadresse mit Strasse/Nr/PLZ/Ort)
- website: string (Webseite / URL)
- category_group: string (wähle passend aus: 'Handwerker', 'Bauleitung', 'Planer & Architekten', 'Ingenieure & Geometer', 'Behörden & Ämter', 'Bauträger & Eigentümer', 'Lieferanten & Logistik', 'Sicherheitsbeauftragte', 'Sonstige')
- notes: string (Zusätzliche nützliche Notizen)

Text:
"""
${aiRawTextProject.value.trim()}
"""`,
        json: true,
        system: 'Du bist ein intelligenter Assistent für Baudokumentation und Kontaktmanagement. Antworte ausschliesslich mit einem JSON-Objekt ohne Markdown-Formatierung.'
      }
    })

    const replyText = (res?.text || res?.response || '').trim()
    if (!res || !replyText) {
      throw new Error('Keine Antwort von der KI erhalten.')
    }

    let parsed: any = null
    try {
      const clean = replyText.replace(/^```(?:json)?\s*/i, '').replace(/```$/, '').trim()
      parsed = JSON.parse(clean)
    } catch {
      throw new Error('KI-Rückgabe konnte nicht als JSON interpretiert werden.')
    }

    if (parsed) {
      if (parsed.first_name) projectContactForm.value.first_name = String(parsed.first_name).trim()
      if (parsed.last_name) projectContactForm.value.last_name = String(parsed.last_name).trim()
      if (parsed.company_name) projectContactForm.value.company_name = String(parsed.company_name).trim()
      if (parsed.role_function) projectContactForm.value.role_function = String(parsed.role_function).trim()
      if (parsed.phone) projectContactForm.value.phone = String(parsed.phone).trim()
      if (parsed.mobile) projectContactForm.value.mobile = String(parsed.mobile).trim()
      if (parsed.email) projectContactForm.value.email = String(parsed.email).trim()
      if (parsed.address) projectContactForm.value.address = String(parsed.address).trim()
      if (parsed.website) projectContactForm.value.website = String(parsed.website).trim()
      if (parsed.category_group) projectContactForm.value.category_group = String(parsed.category_group).trim()
      if (parsed.notes) {
        projectContactForm.value.notes = projectContactForm.value.notes
          ? `${projectContactForm.value.notes}\n${parsed.notes}`
          : parsed.notes
      }

      aiStatusSuccessProject.value = true
      aiStatusMessageProject.value = '✓ Daten erfolgreich erkannt und ins Formular übertragen!'
    }
  } catch (err: any) {
    aiStatusSuccessProject.value = false
    aiStatusMessageProject.value = err.data?.statusMessage || err.message || 'Fehler bei der KI-Erkennung.'
  } finally {
    aiLoadingProject.value = false
  }
}

const saveProjectContact = async () => {
  if (!projectContactForm.value.last_name.trim()) {
    projectContactError.value = 'Bitte mindestens einen Nachnamen eingeben.'
    return
  }

  savingProjectContact.value = true
  projectContactError.value = ''

  const payload = {
    first_name: projectContactForm.value.first_name.trim(),
    last_name: projectContactForm.value.last_name.trim(),
    company_name: projectContactForm.value.company_name.trim(),
    role_function: projectContactForm.value.role_function.trim(),
    phone: projectContactForm.value.phone.trim(),
    mobile: projectContactForm.value.mobile.trim(),
    email: projectContactForm.value.email.trim(),
    address: projectContactForm.value.address.trim(),
    website: projectContactForm.value.website.trim(),
    project_id: projectId,
    category_group: projectContactForm.value.category_group,
    notes: projectContactForm.value.notes.trim(),
    share_scope: 'private',
    force_duplicate: allowDuplicateProject.value || isEditingProjectContact.value
  }

  try {
    if (isEditingProjectContact.value) {
      await $fetch(`/api/contacts/${projectContactForm.value.id}`, {
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
    showProjectContactModal.value = false
    await loadProjectContacts()
  } catch (err: any) {
    if (err.statusCode === 409 || err.status === 409) {
      projectContactError.value = err.data?.message || 'Duplikat erkannt: Ein ähnlicher Kontakt existiert bereits in diesem Projekt.'
    } else {
      projectContactError.value = err.data?.statusMessage || 'Fehler beim Speichern des Kontakts'
    }
  } finally {
    savingProjectContact.value = false
  }
}

const deleteProjectContact = async (c: any) => {
  const name = (c.first_name ? c.first_name + ' ' : '') + c.last_name
  if (!confirm(`Möchtest du den Kontakt "${name}" wirklich löschen?`)) return
  try {
    await $fetch(`/api/contacts/${c.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    projectContacts.value = projectContacts.value.filter((item: any) => item.id !== c.id)
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Kontakts')
  }
}

const exportContactVCard = (c: any) => {
  const fullName = (c.first_name ? c.first_name + ' ' : '') + c.last_name
  const vcardLines = [
    'BEGIN:VCARD',
    'VERSION:3.0',
    `N:${c.last_name || ''};${c.first_name || ''};;;`,
    `FN:${fullName}`,
    c.company_name ? `ORG:${c.company_name}` : '',
    c.role_function ? `TITLE:${c.role_function}` : '',
    c.phone ? `TEL;TYPE=WORK,VOICE:${c.phone}` : '',
    c.mobile ? `TEL;TYPE=CELL,VOICE:${c.mobile}` : '',
    c.email ? `EMAIL;TYPE=WORK,INTERNET:${c.email}` : '',
    c.address ? `ADR;TYPE=WORK:;;${c.address.replace(/\n/g, ' ')};;;;` : '',
    c.website ? `URL:${formatProjectContactUrl(c.website)}` : '',
    c.notes ? `NOTE:${c.notes.replace(/\n/g, '\\n')}` : '',
    'END:VCARD'
  ].filter(Boolean)

  const vcfContent = vcardLines.join('\r\n')
  const blob = new Blob([vcfContent], { type: 'text/vcard;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${fullName.replace(/\s+/g, '_')}.vcf`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const loadProjectTimeEntries = async () => {
  loadingTimeEntries.value = true
  try {
    const res = await $fetch<any>(`/api/time-entries?project_id=${projectId}`, { headers: authHeaders() })
    projectTimeEntries.value = res.entries || []
    projectTimeSummary.value = res.summary || { totalMinutes: 0, totalHours: 0, totalCost: 0 }
  } catch (err) {
    console.error('Failed to load time entries', err)
  } finally {
    loadingTimeEntries.value = false
  }
}

const openProjectTimeModal = (preselectedTaskId = '') => {
  projectTimeForm.value = {
    task_id: preselectedTaskId,
    duration_hours: 1,
    entry_date: new Date().toISOString().substring(0, 10),
    description: '',
    hourly_rate: user.value?.hourly_rate || 0
  }
  showProjectTimeModal.value = true
}

const saveProjectTime = async () => {
  try {
    const durationMinutes = Math.round(Number(projectTimeForm.value.duration_hours || 0) * 60)
    if (durationMinutes <= 0) {
      alert('Bitte eine Dauer grösser als 0 angeben.')
      return
    }
    await $fetch('/api/time-entries', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        task_id: projectTimeForm.value.task_id || null,
        duration_minutes: durationMinutes,
        entry_date: projectTimeForm.value.entry_date,
        description: projectTimeForm.value.description,
        hourly_rate: Number(projectTimeForm.value.hourly_rate || 0)
      }
    })
    showProjectTimeModal.value = false
    await loadProjectTimeEntries()
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erfassen der Zeit')
  }
}

const openEditTimeModal = (entry: any) => {
  editingTimeEntry.value = entry
  editTimeForm.value = {
    id: entry.id,
    duration_hours: Math.round((entry.duration_minutes / 60) * 100) / 100,
    entry_date: entry.entry_date ? entry.entry_date.substring(0, 10) : '',
    description: entry.description || '',
    hourly_rate: entry.hourly_rate || 0
  }
  showEditTimeModal.value = true
}

const saveEditTime = async () => {
  try {
    const durationMinutes = Math.round(Number(editTimeForm.value.duration_hours || 0) * 60)
    if (durationMinutes <= 0) {
      alert('Bitte eine Dauer grösser als 0 angeben.')
      return
    }
    await $fetch(`/api/time-entries/${editTimeForm.value.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        duration_minutes: durationMinutes,
        entry_date: editTimeForm.value.entry_date,
        description: editTimeForm.value.description,
        hourly_rate: Number(editTimeForm.value.hourly_rate || 0)
      }
    })
    showEditTimeModal.value = false
    if (showTaskDrawer.value && drawerTask.value?.id) {
      const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}`, { headers: authHeaders() })
      drawerTimeEntries.value = res.timeEntries || []
      drawerTask.value.tracked_hours = res.task.tracked_hours
    }
    await loadProjectTimeEntries()
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Aktualisieren der Zeit')
  }
}

const deleteTimeEntry = async (id: string) => {
  if (!confirm('Möchtest du diesen Zeiteintrag wirklich löschen?')) return
  try {
    await $fetch(`/api/time-entries/${id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    if (showTaskDrawer.value && drawerTask.value?.id) {
      const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}`, { headers: authHeaders() })
      drawerTimeEntries.value = res.timeEntries || []
      drawerTask.value.tracked_hours = res.task.tracked_hours
    }
    await loadProjectTimeEntries()
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Zeiteintrags')
  }
}

const addTaskTimeEntry = async () => {
  if (!drawerTask.value?.id) return
  const durationMinutes = Math.round(Number(drawerTimeForm.value.duration_hours || 0) * 60)
  if (durationMinutes <= 0) {
    alert('Bitte eine Dauer grösser als 0 angeben.')
    return
  }
  try {
    await $fetch('/api/time-entries', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        task_id: drawerTask.value.id,
        duration_minutes: durationMinutes,
        entry_date: drawerTimeForm.value.entry_date,
        description: drawerTimeForm.value.description,
        hourly_rate: Number(drawerTimeForm.value.hourly_rate || user.value?.hourly_rate || 0)
      }
    })
    drawerTimeForm.value.description = ''
    drawerTimeForm.value.duration_hours = 1
    const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}`, { headers: authHeaders() })
    drawerTimeEntries.value = res.timeEntries || []
    drawerTask.value.tracked_hours = res.task.tracked_hours
    await loadProjectTimeEntries()
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erfassen der Zeit')
  }
}

const initSettingsTab = () => {
  if (project.value) {
    settingsForm.value = {
      title: project.value.title,
      status: project.value.status,
      currency: project.value.currency || 'CHF',
      budget_hours: project.value.budget_hours ?? null,
      budget_amount: project.value.budget_amount ?? null,
      visibility: project.value.visibility || 'private',
      custom_data: { ...(project.value.custom_data || {}) }
    }
  }
}

const saveProjectSettings = async () => {
  savingProjectSettings.value = true
  try {
    await $fetch(`/api/projects/${projectId}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        title: settingsForm.value.title,
        status: settingsForm.value.status,
        currency: settingsForm.value.currency || 'CHF',
        budget_hours: settingsForm.value.budget_hours ? Number(settingsForm.value.budget_hours) : null,
        budget_amount: settingsForm.value.budget_amount ? Number(settingsForm.value.budget_amount) : null,
        visibility: settingsForm.value.visibility || 'private',
        custom_data: settingsForm.value.custom_data
      }
    })
    await loadProjectData()
    alert('Projekt-Einstellungen erfolgreich gespeichert!')
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern der Einstellungen')
  } finally {
    savingProjectSettings.value = false
  }
}

// Delete Project State & Handlers
const showDeleteProjectModal = ref(false)
const deletingProject = ref(false)
const deleteProjectError = ref('')

const openDeleteProjectModal = () => {
  deleteProjectError.value = ''
  showDeleteProjectModal.value = true
}

const confirmDeleteProject = async () => {
  if (!project.value?.id) return
  deletingProject.value = true
  deleteProjectError.value = ''
  try {
    const targetFolderId = project.value.folder_id
    await $fetch(`/api/projects/${project.value.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    showDeleteProjectModal.value = false
    if (targetFolderId) {
      navigateTo(`/folders/${targetFolderId}`)
    } else {
      navigateTo('/dashboard')
    }
  } catch (err: any) {
    deleteProjectError.value = err.data?.statusMessage || err.message || 'Fehler beim Löschen des Projekts'
  } finally {
    deletingProject.value = false
  }
}

const saveField = async () => {
  try {
    const options = newFieldType.value === 'select'
      ? newFieldOptionsRaw.value.split(',').map((s: string) => s.trim()).filter(Boolean)
      : []

    const logicRules = enableFieldLogic.value && logicDependsOnField.value
      ? { depends_on_field: logicDependsOnField.value, depends_on_value: logicDependsOnValue.value }
      : null

    if (editingFieldId.value) {
      await $fetch(`/api/folders/${project.value.folder_id}/fields/${editingFieldId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: {
          label: newFieldLabel.value,
          options,
          logic_rules: logicRules
        }
      })
    } else {
      await $fetch(`/api/folders/${project.value.folder_id}/fields`, {
        method: 'POST',
        headers: authHeaders(),
        body: {
          label: newFieldLabel.value,
          field_type: newFieldType.value,
          entity_type: newFieldEntityType.value,
          options,
          logic_rules: logicRules
        }
      })
    }
    showNewFieldModal.value = false
    newFieldLabel.value = ''
    newFieldOptionsRaw.value = ''
    newFieldType.value = 'text'
    newFieldEntityType.value = 'task'
    enableFieldLogic.value = false
    logicDependsOnField.value = ''
    logicDependsOnValue.value = ''
    editingFieldId.value = ''
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern des Feldes')
  }
}

const deleteField = async (fieldId: string) => {
  if (!confirm('Möchtest du dieses benutzerdefinierte Feld wirklich löschen?')) return
  try {
    await $fetch(`/api/folders/${project.value.folder_id}/fields/${fieldId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Feldes')
  }
}

const loadJournals = async () => {
  try {
    const res = await $fetch<any>(`/api/projects/${projectId}/journal`, {
      headers: authHeaders()
    })
    journalEntries.value = res.entries || []
  } catch {
    try {
      const fallback = await $fetch<any>(`/api/journals?project_id=${projectId}`, {
        headers: authHeaders()
      })
      journalEntries.value = fallback.entries || []
    } catch {}
  }
}

const createList = async () => {
  try {
    await $fetch('/api/lists', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        title: newListTitle.value,
        access_mode: newListAccessMode.value
      }
    })
    showNewListModal.value = false
    newListTitle.value = ''
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erstellen des Abschnitts')
  }
}

// Section / List Management State & Methods
const showManageSectionsModal = ref(false)
const managingSections = ref<any[]>([])
const newSectionTitleInModal = ref('')
const savingSections = ref(false)
const manageSectionsError = ref('')
const draggedSectionModalIdx = ref<number | null>(null)

const openManageSectionsModal = () => {
  managingSections.value = JSON.parse(JSON.stringify(lists.value))
  newSectionTitleInModal.value = ''
  manageSectionsError.value = ''
  showManageSectionsModal.value = true
}

const moveSectionUp = (idx: number) => {
  if (idx <= 0) return
  const temp = managingSections.value[idx]
  managingSections.value[idx] = managingSections.value[idx - 1]
  managingSections.value[idx - 1] = temp
}

const moveSectionDown = (idx: number) => {
  if (idx >= managingSections.value.length - 1) return
  const temp = managingSections.value[idx]
  managingSections.value[idx] = managingSections.value[idx + 1]
  managingSections.value[idx + 1] = temp
}

const onModalDragStart = (idx: number, e: DragEvent) => {
  draggedSectionModalIdx.value = idx
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', String(idx))
  }
}

const onModalDragOver = (idx: number, e: DragEvent) => {
  e.preventDefault()
  if (e.dataTransfer) {
    e.dataTransfer.dropEffect = 'move'
  }
}

const onModalDrop = (targetIdx: number, e: DragEvent) => {
  e.preventDefault()
  if (draggedSectionModalIdx.value === null || draggedSectionModalIdx.value === targetIdx) return
  const item = managingSections.value.splice(draggedSectionModalIdx.value, 1)[0]
  managingSections.value.splice(targetIdx, 0, item)
  draggedSectionModalIdx.value = null
}

const addSectionInModal = async () => {
  const t = newSectionTitleInModal.value.trim()
  if (!t) return
  try {
    await $fetch<any>('/api/lists', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        title: t,
        access_mode: 'inherit'
      }
    })
    newSectionTitleInModal.value = ''
    await loadProjectData()
    managingSections.value = JSON.parse(JSON.stringify(lists.value))
  } catch (err: any) {
    manageSectionsError.value = err.data?.statusMessage || 'Fehler beim Hinzufügen'
  }
}

const deleteSectionInModal = async (idx: number) => {
  const sec = managingSections.value[idx]
  const count = sec.tasks?.length || sec.task_count || 0
  const msg = count > 0
    ? `Abschnitt "${sec.title}" enthält ${count} Aufgabe(n). Möchtest du diesen Abschnitt und alle darin enthaltenen Aufgaben wirklich unwiderruflich löschen?`
    : `Möchtest du den Abschnitt "${sec.title}" wirklich löschen?`
  if (!confirm(msg)) return

  try {
    if (sec.id) {
      await $fetch(`/api/lists/${sec.id}`, {
        method: 'DELETE',
        headers: authHeaders()
      })
    }
    managingSections.value.splice(idx, 1)
    await loadProjectData()
  } catch (err: any) {
    manageSectionsError.value = err.data?.statusMessage || 'Fehler beim Löschen des Abschnitts'
  }
}

const saveSectionsReorder = async () => {
  savingSections.value = true
  manageSectionsError.value = ''
  try {
    const payload = managingSections.value.map((sec: any, idx: number) => ({
      id: sec.id,
      title: sec.title ? sec.title.trim() : `Abschnitt ${idx + 1}`,
      sort_order: idx + 1,
      color: sec.color || null
    }))
    await $fetch('/api/lists/reorder', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        lists: payload
      }
    })
    showManageSectionsModal.value = false
    await loadProjectData()
  } catch (err: any) {
    manageSectionsError.value = err.data?.statusMessage || 'Fehler beim Speichern der Abschnitte'
  } finally {
    savingSections.value = false
  }
}



const drawerTaskAssignedUsers = ref<string[]>([])
const showAssigneeDropdown = ref(false)

const parseAssignedUsers = (assignedTo: any): string[] => {
  if (!assignedTo) return []
  if (Array.isArray(assignedTo)) return assignedTo
  if (typeof assignedTo === 'string') {
    const trimmed = assignedTo.trim()
    if (trimmed.startsWith('[')) {
      try {
        const parsed = JSON.parse(trimmed)
        if (Array.isArray(parsed)) return parsed
      } catch (_) {}
    }
    return [trimmed]
  }
  return []
}

const toggleAssignee = (userId: string) => {
  const idx = drawerTaskAssignedUsers.value.indexOf(userId)
  if (idx === -1) {
    drawerTaskAssignedUsers.value.push(userId)
  } else {
    drawerTaskAssignedUsers.value.splice(idx, 1)
  }
  autoSaveDrawer()
}

const removeAssignee = (userId: string) => {
  const idx = drawerTaskAssignedUsers.value.indexOf(userId)
  if (idx !== -1) {
    drawerTaskAssignedUsers.value.splice(idx, 1)
    autoSaveDrawer()
  }
}

const getMember = (userId: string): any => {
  return members.value.find((mem: any) => mem.user_id === userId) || null
}

const getMemberName = (userId: string): string => {
  const m = getMember(userId)
  return m ? (m.name || m.email) : 'Benutzer'
}

const getTaskAssignees = (task: any): any[] => {
  const userIds = parseAssignedUsers(task.assigned_users || task.assigned_to)
  return userIds.map(uid => {
    const m = members.value.find((mem: any) => mem.user_id === uid)
    return m || { user_id: uid, name: 'Zugewiesen', email: '' }
  })
}

const toggleTaskCompleted = async (task: any) => {
  const previousStatus = task.status
  const newStatus = previousStatus === 'done' ? 'todo' : 'done'
  task.status = newStatus
  try {
    await $fetch(`/api/tasks/${task.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { status: newStatus }
    })
  } catch (err: any) {
    task.status = previousStatus
    alert(err.data?.statusMessage || 'Fehler beim Ändern des Aufgabenstatus')
  }
}

const openNewTaskModal = (listId: string) => {
  const chosenListId = listId || lists.value[0]?.id || ''
  isCreatingTaskInDrawer.value = true
  showDrawerTimeMenu.value = false
  drawerTaskAssignedUsers.value = []
  showAssigneeDropdown.value = false
  drawerTask.value = {
    id: null,
    list_id: chosenListId,
    title: '',
    description: '',
    status: 'todo',
    due_date: '',
    custom_data: {},
    assigned_to: '',
    priority: 'normal',
    color: '',
    tags: [],
    checklist: []
  }
  drawerSubtasks.value = []
  drawerComments.value = []
  drawerDocuments.value = []
  uploadingFiles.value = []
  showTaskDrawer.value = true
}

const saveNewTaskFromDrawer = async () => {
  if (!drawerTask.value?.title?.trim()) {
    alert('Bitte gib mindestens einen Aufgabentitel ein.')
    return
  }
  try {
    const res = await $fetch<any>('/api/tasks', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        list_id: drawerTask.value.list_id,
        title: drawerTask.value.title.trim(),
        description: drawerTask.value.description || '',
        status: drawerTask.value.status || 'todo',
        due_date: drawerTask.value.due_date || null,
        custom_data: drawerTask.value.custom_data || {},
        assigned_to: drawerTaskAssignedUsers.value.length > 0 ? drawerTaskAssignedUsers.value : null,
        priority: drawerTask.value.priority || 'normal',
        color: drawerTask.value.color || null,
        tags: drawerTask.value.tags || [],
        checklist: drawerTask.value.checklist || []
      }
    })
    isCreatingTaskInDrawer.value = false
    const newId = res.task.id
    drawerTask.value.id = newId
    showTaskDrawer.value = false
    await loadProjectData()
    highlightedTaskId.value = newId
    setTimeout(() => {
      if (highlightedTaskId.value === newId) {
        highlightedTaskId.value = null
      }
    }, 3000)
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erstellen der Aufgabe')
  }
}

const getTaskSectionTitle = (listId?: string) => {
  if (!listId) return ''
  const l = lists.value.find((item: any) => item.id === listId)
  return l ? l.title : ''
}

const onDrawerSectionChange = async () => {
  if (!drawerTask.value?.id || userRole.value === 'viewer') return
  const targetListId = drawerTask.value.list_id
  const taskId = drawerTask.value.id
  
  // Optimistisches Umhängen in lokaler Liste
  let targetTaskObj: any = null
  for (const l of lists.value) {
    if (l.tasks) {
      const idx = l.tasks.findIndex((t: any) => t.id === taskId)
      if (idx !== -1) {
        targetTaskObj = l.tasks.splice(idx, 1)[0]
        break
      }
    }
  }
  if (targetTaskObj) {
    targetTaskObj.list_id = targetListId
    const targetList = lists.value.find((l: any) => l.id === targetListId)
    if (targetList) {
      targetList.tasks = targetList.tasks || []
      targetList.tasks.push(targetTaskObj)
    }
  }

  await autoSaveDrawer()
  await loadProjectData()
}

const openTaskDrawer = async (task: any) => {
  isCreatingTaskInDrawer.value = false
  showDrawerTimeMenu.value = false
  showAssigneeDropdown.value = false
  drawerTaskAssignedUsers.value = parseAssignedUsers(task.assigned_users || task.assigned_to)
  drawerTask.value = {
    ...task,
    due_date: task.due_date ? task.due_date.substring(0, 10) : '',
    tags: Array.isArray(task.tags) ? [...task.tags] : [],
    checklist: Array.isArray(task.checklist) ? JSON.parse(JSON.stringify(task.checklist)) : [],
    custom_data: { ...(task.custom_data || {}) },
    assigned_to: task.assigned_to || '',
    priority: task.priority || 'normal',
    color: task.color || ''
  }
  drawerSubtasks.value = []
  drawerComments.value = []
  drawerDocuments.value = []
  uploadingFiles.value = []
  showTaskDrawer.value = true

  // Load full detail from API
  try {
    const res = await $fetch<any>(`/api/tasks/${task.id}`, { headers: authHeaders() })
    const t = res.task
    drawerTaskAssignedUsers.value = parseAssignedUsers(t.assigned_users || t.assigned_to)
    drawerTask.value = {
      ...t,
      due_date: t.due_date ? t.due_date.substring(0, 10) : '',
      tags: Array.isArray(t.tags) ? [...t.tags] : [],
      checklist: Array.isArray(t.checklist) ? JSON.parse(JSON.stringify(t.checklist)) : [],
      custom_data: { ...(t.custom_data || {}) },
      assigned_to: t.assigned_to || '',
      priority: t.priority || 'normal',
      color: t.color || '',
      budget_hours: t.budget_hours ?? null,
      budget_amount: t.budget_amount ?? null,
      tracked_hours: t.tracked_hours ?? 0
    }
    drawerSubtasks.value = res.subtasks || []
    drawerComments.value = res.comments || []
    drawerDocuments.value = res.documents || []
    drawerTimeEntries.value = res.timeEntries || []
    drawerTimeForm.value = {
      duration_hours: 1,
      entry_date: new Date().toISOString().substring(0, 10),
      description: '',
      hourly_rate: user.value?.hourly_rate || 0
    }
  } catch (err) {
    console.error('Failed to load task detail', err)
  }
}

// Keep backward compat for new task modal
const openEditTaskModal = (task: any) => openTaskDrawer(task)

const closeTaskDrawer = () => {
  showTaskDrawer.value = false
  // ?task= aus der URL entfernen, damit der Drawer beim Neuladen nicht wieder aufgeht
  if (route.query.task) {
    const query = { ...route.query }
    delete query.task
    navigateTo({ path: route.path, query }, { replace: true })
  }
  loadProjectData()
}

const autoSaveDrawer = async () => {
  if (!drawerTask.value?.id) return
  if (userRole.value === 'viewer') {
    // Viewer darf Status abhaken
    try {
      await $fetch(`/api/tasks/${drawerTask.value.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: { status: drawerTask.value.status }
      })
    } catch (err) {
      console.error('Status update failed', err)
    }
    return
  }
  try {
    await $fetch(`/api/tasks/${drawerTask.value.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        title: drawerTask.value.title,
        description: drawerTask.value.description,
        status: drawerTask.value.status,
        due_date: drawerTask.value.due_date || null,
        custom_data: drawerTask.value.custom_data,
        assigned_to: drawerTaskAssignedUsers.value.length > 0 ? drawerTaskAssignedUsers.value : null,
        priority: drawerTask.value.priority,
        color: drawerTask.value.color || null,
        tags: drawerTask.value.tags,
        checklist: drawerTask.value.checklist,
        budget_hours: drawerTask.value.budget_hours ? Number(drawerTask.value.budget_hours) : null,
        budget_amount: drawerTask.value.budget_amount ? Number(drawerTask.value.budget_amount) : null
      }
    })
  } catch (err) {
    console.error('AutoSave failed', err)
  }
}

const setTaskColor = (color: string) => {
  if (userRole.value === 'viewer') return
  drawerTask.value.color = color
  autoSaveDrawer()
}

const addTag = () => {
  const t = newTagInput.value.trim()
  if (!t || drawerTask.value.tags.includes(t)) return
  drawerTask.value.tags.push(t)
  newTagInput.value = ''
  autoSaveDrawer()
}

const removeTag = (i: number) => {
  drawerTask.value.tags.splice(i, 1)
  autoSaveDrawer()
}

const addChecklistItem = () => {
  const t = newChecklistInput.value.trim()
  if (!t) return
  drawerTask.value.checklist.push({ id: 'cl_' + Date.now(), text: t, done: false })
  newChecklistInput.value = ''
  autoSaveDrawer()
}

const removeChecklistItem = (i: number) => {
  drawerTask.value.checklist.splice(i, 1)
  autoSaveDrawer()
}

const toggleChecklistItem = (i: number) => {
  drawerTask.value.checklist[i].done = !drawerTask.value.checklist[i].done
  autoSaveDrawer()
}

const addSubtask = async () => {
  const t = newSubtaskInput.value.trim()
  if (!t || !drawerTask.value?.id) return
  try {
    const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}/subtasks`, {
      method: 'POST',
      headers: authHeaders(),
      body: { title: t }
    })
    drawerSubtasks.value.push(res.subtask)
    newSubtaskInput.value = ''
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erstellen der Unteraufgabe')
  }
}

const addSubtaskToItem = async (item: any) => {
  const key = item.id
  const val = (itemSubtaskInputs.value[key] || '').trim()
  if (!val || !drawerTask.value?.id) return
  try {
    const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}/subtasks`, {
      method: 'POST',
      headers: authHeaders(),
      body: { title: val, checklist_item_id: item.id }
    })
    drawerSubtasks.value.push(res.subtask)
    itemSubtaskInputs.value[key] = ''
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erstellen der Unteraufgabe')
  }
}

const toggleSubtask = async (sub: any) => {
  try {
    const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}/subtasks/${sub.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { is_done: !sub.is_done }
    })
    const idx = drawerSubtasks.value.findIndex((s: any) => s.id === sub.id)
    if (idx !== -1) drawerSubtasks.value[idx] = res.subtask
  } catch (err) {
    console.error('Toggle subtask failed', err)
  }
}

const deleteSubtask = async (subId: string) => {
  try {
    await $fetch(`/api/tasks/${drawerTask.value.id}/subtasks/${subId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    drawerSubtasks.value = drawerSubtasks.value.filter((s: any) => s.id !== subId)
  } catch (err) {
    console.error('Delete subtask failed', err)
  }
}

const addComment = async () => {
  const c = newCommentInput.value.trim()
  if (!c || !drawerTask.value?.id) return
  try {
    const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}/comments`, {
      method: 'POST',
      headers: authHeaders(),
      body: { content: c }
    })
    if (res?.comment) {
      drawerComments.value.push(res.comment)
    }
    newCommentInput.value = ''
  } catch (err: any) {
    alert(err.data?.statusMessage || err.message || 'Fehler beim Senden des Kommentars')
  }
}

const deleteTaskFromDrawer = async () => {
  if (userRole.value === 'editor' || userRole.value === 'viewer') {
    alert('Als ' + (userRole.value === 'editor' ? 'Editor' : 'Viewer') + ' hast du keine Berechtigung, Aufgaben zu löschen.')
    return
  }
  if (!confirm('Möchtest du diese Aufgabe wirklich löschen?')) return
  try {
    await $fetch(`/api/tasks/${drawerTask.value.id}`, { method: 'DELETE', headers: authHeaders() })
    closeTaskDrawer()
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen')
  }
}

// File Attachment Upload & Management
const triggerFileInput = () => {
  fileInput.value?.click()
}

const onDragOverFiles = () => {
  dragOverFiles.value = true
}

const onDragLeaveFiles = () => {
  dragOverFiles.value = false
}

const onDropFiles = (e: DragEvent) => {
  dragOverFiles.value = false
  if (e.dataTransfer?.files) {
    handleFiles(Array.from(e.dataTransfer.files))
  }
}

const onFileSelected = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files) {
    handleFiles(Array.from(target.files))
  }
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const handleFiles = async (files: File[]) => {
  if (!drawerTask.value?.id || userRole.value === 'viewer') return

  for (const file of files) {
    if (file.size > 10 * 1024 * 1024) {
      alert(`Die Datei "${file.name}" ist größer als 10 MB.`)
      continue
    }

    const uploadItem = {
      id: 'up_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
      name: file.name,
      progress: 10
    }
    uploadingFiles.value.push(uploadItem)

    try {
      // Convert file to Base64 Data URL for robust persistence without requiring external S3/FTP server
      const base64Data = await readFileAsDataUrl(file)
      uploadItem.progress = 60

      const res = await $fetch<any>(`/api/tasks/${drawerTask.value.id}/documents`, {
        method: 'POST',
        headers: authHeaders(),
        body: {
          file_name: file.name,
          mime_type: file.type || 'application/octet-stream',
          file_size: file.size,
          storage_path: base64Data
        }
      })

      uploadItem.progress = 100
      drawerDocuments.value.unshift(res.document)
    } catch (err: any) {
      alert(err.data?.statusMessage || `Fehler beim Hochladen von "${file.name}"`)
    } finally {
      uploadingFiles.value = uploadingFiles.value.filter((u: any) => u.id !== uploadItem.id)
    }
  }
}

const readFileAsDataUrl = (file: File): Promise<string> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result as string)
    reader.onerror = error => reject(error)
    reader.readAsDataURL(file)
  })
}

const deleteDocument = async (docId: string) => {
  if (!confirm('Möchtest du diese Datei wirklich entfernen?')) return
  try {
    await $fetch(`/api/tasks/${drawerTask.value.id}/documents/${docId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    drawerDocuments.value = drawerDocuments.value.filter((d: any) => d.id !== docId)
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen der Datei')
  }
}

const formatFileSize = (bytes: number) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const getFileIcon = (mime: string) => {
  if (!mime) return '📄'
  if (mime.startsWith('image/')) return '🖼️'
  if (mime.includes('pdf')) return '📕'
  if (mime.includes('word') || mime.includes('document')) return '📝'
  if (mime.includes('sheet') || mime.includes('excel') || mime.includes('csv')) return '📊'
  if (mime.includes('zip') || mime.includes('tar') || mime.includes('compressed')) return '📦'
  return '📄'
}

const getFileIconClass = (mime: string) => {
  if (!mime) return 'bg-slate-100 text-slate-700'
  if (mime.startsWith('image/')) return 'bg-cyan-50 text-cyan-700'
  if (mime.includes('pdf')) return 'bg-rose-50 text-rose-700'
  if (mime.includes('word') || mime.includes('document')) return 'bg-blue-50 text-blue-700'
  if (mime.includes('sheet') || mime.includes('excel') || mime.includes('csv')) return 'bg-emerald-50 text-emerald-700'
  return 'bg-slate-100 text-slate-700'
}

// CSV / Excel Import Functions
const openImportModal = () => {
  importStep.value = 1
  importFileName.value = ''
  importHeaders.value = []
  importParsedRows.value = []
  importColumnMapping.value = {}
  importTargetListId.value = lists.value[0]?.id || ''
  importError.value = ''
  importSuccessCount.value = 0
  showImportModal.value = true
}

const closeImportModal = () => {
  showImportModal.value = false
  if (importStep.value === 3) {
    loadProjectData()
  }
}

const onCsvDrop = (e: DragEvent) => {
  if (e.dataTransfer?.files?.[0]) {
    parseCsvFile(e.dataTransfer.files[0])
  }
}

const onCsvFileSelected = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files?.[0]) {
    parseCsvFile(target.files[0])
  }
}

const parseCsvFile = async (file: File) => {
  importFileName.value = file.name
  importError.value = ''

  try {
    const isExcel = /\.(xlsx|xls)$/i.test(file.name)
    let headers: string[] = []
    let rows: string[][] = []

    if (isExcel) {
      const data = await file.arrayBuffer()
      const wb = XLSX.read(data, { type: 'array' })
      const sheetName = wb.SheetNames[0]
      if (!sheetName) throw new Error('Kein Tabellenblatt in der Datei gefunden.')
      const sheet = wb.Sheets[sheetName]
      const rawRows: any[][] = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' })
      if (rawRows.length < 2) throw new Error('Die Datei muss mindestens eine Kopfzeile und eine Datenzeile enthalten.')
      headers = rawRows[0].map((h: any) => String(h || '').trim())
      rows = rawRows.slice(1).map(r => r.map((c: any) => String(c || '').trim())).filter(r => r.some(c => c.length > 0))
    } else {
      const text = await file.text()
      if (!text || !text.trim()) {
        importError.value = 'Die ausgewählte Datei ist leer.'
        return
      }

      // Detect delimiter: comma, semicolon, tab
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

      headers = parsed[0].map(h => h.trim())
      rows = parsed.slice(1).filter(r => r.some(cell => cell.trim().length > 0))
    }

    importHeaders.value = headers
    importParsedRows.value = rows

    // Auto-guess mapping
    const mapping: Record<number, string> = {}
    importHeaders.value.forEach((header: string, idx: number) => {
      const hLow = header.toLowerCase().trim()
      if (!Object.values(mapping).includes('title') && (hLow.includes('titel') || hLow.includes('title') || hLow.includes('aufgabe') || hLow.includes('task') || hLow.includes('name'))) {
        mapping[idx] = 'title'
      } else if (!Object.values(mapping).includes('description') && (hLow.includes('beschreib') || hLow.includes('desc') || hLow.includes('notiz') || hLow.includes('detail') || hLow.includes('kommentar'))) {
        mapping[idx] = 'description'
      } else if (!Object.values(mapping).includes('status') && (hLow.includes('status') || hLow.includes('zustand') || hLow.includes('state'))) {
        mapping[idx] = 'status'
      } else if (!Object.values(mapping).includes('due_date') && (hLow.includes('fällig') || hLow.includes('due') || hLow.includes('datum') || hLow.includes('date') || hLow.includes('termin') || hLow.includes('frist'))) {
        mapping[idx] = 'due_date'
      } else if (!Object.values(mapping).includes('priority') && (hLow.includes('prio') || hLow.includes('dring') || hLow.includes('wichtig'))) {
        mapping[idx] = 'priority'
      } else if (!Object.values(mapping).includes('tags') && (hLow.includes('tag') || hLow.includes('label') || hLow.includes('kategorie') || hLow.includes('schlagwort'))) {
        mapping[idx] = 'tags'
      } else {
        // 1. Check existing custom fields
        const matchField = fields.value.find((f: any) => {
          const fLbl = f.label_key ? t(f.label_key).toLowerCase() : (f.label || '').toLowerCase()
          return fLbl === hLow || (f.label || '').toLowerCase() === hLow || f.field_key?.toLowerCase() === hLow
        })
        if (matchField) {
          mapping[idx] = 'custom:' + matchField.field_key
        } else {
          // 2. Check common template fields
          const matchTpl = commonCustomFieldTemplates.find((t: any) => {
            const tLbl = t.label_key ? t(t.label_key).toLowerCase() : t.label.toLowerCase()
            return t.key.toLowerCase() === hLow || tLbl === hLow || t.label.toLowerCase() === hLow || hLow.includes(t.key)
          })
          if (matchTpl) {
            mapping[idx] = 'custom:' + matchTpl.key
          } else {
            // 3. Auto-suggest as new custom field
            mapping[idx] = 'custom:' + getHeaderKey(header)
          }
        }
      }
    })

    importColumnMapping.value = mapping
    importStep.value = 2
  } catch (err: any) {
    importError.value = 'Fehler beim Parsen der Datei: ' + (err.message || err)
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

const executeImport = async () => {
  // Validate that title is mapped
  const hasTitle = Object.values(importColumnMapping.value).includes('title')
  if (!hasTitle) {
    importError.value = 'Bitte weise mindestens einer Spalte das Pflichtfeld "Aufgabentitel" zu.'
    return
  }

  if (!importTargetListId.value) {
    importError.value = 'Bitte wähle einen Ziel-Abschnitt für die Aufgaben aus.'
    return
  }

  importingTasks.value = true
  importError.value = ''
  let successCount = 0

  try {
    // 1. Alle neu gemappten Zusatzfelder automatisch in den Felddefinitionen des Ordners anlegen
    const existingFieldKeys = new Set(fields.value.map((f: any) => f.field_key))
    for (const [colIdxStr, targetField] of Object.entries(importColumnMapping.value) as [string, string][]) {
      if (targetField && targetField.startsWith('custom:')) {
        const key = targetField.replace('custom:', '')
        if (!existingFieldKeys.has(key)) {
          const colIdx = parseInt(colIdxStr)
          const headerName = importHeaders.value[colIdx] || key
          const matchedTpl = commonCustomFieldTemplates.find((t: any) => t.key === key)
          try {
            await $fetch(`/api/folders/${project.value.folder_id}/fields`, {
              method: 'POST',
              headers: authHeaders(),
              body: {
                field_key: key,
                label: matchedTpl?.label || headerName,
                field_type: matchedTpl?.type || 'text',
                entity_type: 'task'
              }
            })
            existingFieldKeys.add(key)
          } catch (e) {
            console.warn('Could not auto-create custom field:', key, e)
          }
        }
      }
    }

    for (const row of importParsedRows.value) {
      const taskPayload: any = {
        list_id: importTargetListId.value,
        title: '',
        description: '',
        status: 'todo',
        due_date: null,
        priority: 'normal',
        tags: [],
        custom_data: {}
      }

      const mappingEntries = Object.entries(importColumnMapping.value) as [string, string][]
      for (const [colIdxStr, targetField] of mappingEntries) {
        const colIdx = parseInt(colIdxStr)
        const cellVal = row[colIdx]?.trim() || ''
        if (!targetField || !cellVal) continue

        if (targetField === 'title') {
          taskPayload.title = cellVal
        } else if (targetField === 'description') {
          taskPayload.description = cellVal
        } else if (targetField === 'status') {
          const s = cellVal.toLowerCase()
          if (s.includes('done') || s.includes('erledigt') || s.includes('abgeschlossen')) taskPayload.status = 'done'
          else if (s.includes('prog') || s.includes('arbeit') || s.includes('lauf')) taskPayload.status = 'in_progress'
          else if (s.includes('rev') || s.includes('prüf')) taskPayload.status = 'review'
          else taskPayload.status = 'todo'
        } else if (targetField === 'priority') {
          const p = cellVal.toLowerCase()
          if (p.includes('dring') || p.includes('urgent')) taskPayload.priority = 'dringend'
          else if (p.includes('hoch') || p.includes('high')) taskPayload.priority = 'hoch'
          else if (p.includes('niedrig') || p.includes('low')) taskPayload.priority = 'niedrig'
          else taskPayload.priority = 'normal'
        } else if (targetField === 'due_date') {
          // Normalise Date DD.MM.YYYY to YYYY-MM-DD if applicable
          if (/^\d{1,2}\.\d{1,2}\.\d{4}$/.test(cellVal)) {
            const parts = cellVal.split('.')
            taskPayload.due_date = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`
          } else {
            taskPayload.due_date = cellVal
          }
        } else if (targetField === 'tags') {
          taskPayload.tags = cellVal.split(/[,;|]/).map((t: string) => t.trim()).filter(Boolean)
        } else if (targetField.startsWith('custom:')) {
          const key = targetField.replace('custom:', '')
          taskPayload.custom_data[key] = cellVal
        }
      }

      if (taskPayload.title) {
        await $fetch('/api/tasks', {
          method: 'POST',
          headers: authHeaders(),
          body: taskPayload
        })
        successCount++
      }
    }

    importSuccessCount.value = successCount
    importStep.value = 3
    await loadProjectData()
  } catch (err: any) {
    importError.value = 'Fehler während des Imports: ' + (err.data?.statusMessage || err.message || err)
  } finally {
    importingTasks.value = false
  }
}

const saveTask = async () => {
  try {
    if (isEditingTask.value) {
      await $fetch(`/api/tasks/${currentEditingTaskId.value}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: {
          title: taskForm.value.title,
          description: taskForm.value.description,
          status: taskForm.value.status,
          due_date: taskForm.value.due_date,
          custom_data: taskForm.value.custom_data,
          list_id: targetListId.value
        }
      })
    } else {
      await $fetch('/api/tasks', {
        method: 'POST',
        headers: authHeaders(),
        body: {
          list_id: targetListId.value,
          title: taskForm.value.title,
          description: taskForm.value.description,
          status: taskForm.value.status,
          due_date: taskForm.value.due_date,
          custom_data: taskForm.value.custom_data
        }
      })
    }
    showTaskModal.value = false
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern der Aufgabe')
  }
}

const deleteTask = async () => {
  if (userRole.value === 'editor' || userRole.value === 'viewer') {
    alert('Als ' + (userRole.value === 'editor' ? 'Editor' : 'Viewer') + ' hast du keine Berechtigung, Aufgaben zu löschen.')
    return
  }
  if (!confirm('Möchtest du diese Aufgabe wirklich löschen?')) return
  try {
    await $fetch(`/api/tasks/${currentEditingTaskId.value}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    showTaskModal.value = false
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen der Aufgabe')
  }
}

const loadUserGroups = async () => {
  try {
    const res = await $fetch<any>('/api/groups', {
      headers: authHeaders()
    })
    userGroups.value = res.groups || []
  } catch {}
}

const openNewEntryModal = async () => {
  newEntryForm.value = {
    title: '',
    type: 'entry',
    category: 'bausitzung',
    visibility: 'all',
    allowed_group_id: null,
    task_id: null,
    entry_date: new Date().toISOString().slice(0, 10),
    content: '',
    analyzeWithAi: true,
    attendees: [] as any[],
    attachments: [] as any[]
  }
  newEntryAttendeeName.value = ''
  newEntryAttendeeEmail.value = ''
  newEntryAttendeeRole.value = ''
  selectedContactToAdd.value = ''
  journalError.value = ''
  showNewEntryModal.value = true
  if (userGroups.value.length === 0) {
    loadUserGroups()
  }
  if (projectContacts.value.length === 0) {
    loadProjectContacts()
  }
  loadAvailableContacts()
}

const openNewNoteModal = async () => {
  newNoteForm.value = {
    title: '',
    type: 'note',
    category: 'email',
    visibility: 'all',
    allowed_group_id: null,
    sender_name: '',
    sender_email: '',
    content: '',
    analyzeWithAi: true,
    attachments: [] as any[]
  }
  journalError.value = ''
  showNewNoteModal.value = true
  if (userGroups.value.length === 0) {
    loadUserGroups()
  }
}

const addContactToAttendees = () => {
  if (!selectedContactToAdd.value) return
  const contact = attendeeContactOptions.value.find((c: any) => c.id === selectedContactToAdd.value)
    || projectContacts.value.find((c: any) => c.id === selectedContactToAdd.value)
    || availableContacts.value.find((c: any) => c.id === selectedContactToAdd.value)
  if (!contact) return
  const fullName = [contact.first_name, contact.last_name].filter(Boolean).join(' ') || 'Kontakt'
  if (!newEntryForm.value.attendees.some((a: any) => a.contact_id === contact.id)) {
    newEntryForm.value.attendees.push({
      contact_id: contact.id,
      name: fullName,
      email: contact.email || null,
      role: contact.role_function || contact.category_group || '',
      present: true
    })
  }
  selectedContactToAdd.value = ''
}

const addCustomAttendee = () => {
  const name = newEntryAttendeeName.value.trim()
  if (!name) return
  newEntryForm.value.attendees.push({
    contact_id: null,
    name,
    email: newEntryAttendeeEmail.value.trim() || null,
    role: newEntryAttendeeRole.value.trim() || '',
    present: true
  })
  newEntryAttendeeName.value = ''
  newEntryAttendeeEmail.value = ''
  newEntryAttendeeRole.value = ''
}

const removeAttendee = (idx: number) => {
  newEntryForm.value.attendees.splice(idx, 1)
}

const handleEntryAttachments = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  for (let i = 0; i < target.files.length; i++) {
    const file = target.files[i]
    const base64 = await readFileAsDataUrl(file)
    newEntryForm.value.attachments.push({
      file_name: file.name,
      file_type: file.type || 'application/octet-stream',
      file_size: file.size,
      file_path: base64
    })
  }
}

const removeEntryAttachment = (idx: number) => {
  newEntryForm.value.attachments.splice(idx, 1)
}

const handleNoteAttachments = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  for (let i = 0; i < target.files.length; i++) {
    const file = target.files[i]
    const base64 = await readFileAsDataUrl(file)
    newNoteForm.value.attachments.push({
      file_name: file.name,
      file_type: file.type || 'application/octet-stream',
      file_size: file.size,
      file_path: base64
    })
  }
}

const removeNoteAttachment = (idx: number) => {
  newNoteForm.value.attachments.splice(idx, 1)
}

const handleNoteDropEml = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  const file = target.files[0]
  const text = await file.text()

  const subjectMatch = text.match(/^Subject:\s*(.+)$/im)
  if (subjectMatch && subjectMatch[1]) {
    newNoteForm.value.title = subjectMatch[1].trim()
  } else {
    newNoteForm.value.title = file.name.replace(/\.[^/.]+$/, '')
  }

  const fromMatch = text.match(/^From:\s*(.+)$/im)
  if (fromMatch && fromMatch[1]) {
    const rawFrom = fromMatch[1].trim()
    const emailMatch = rawFrom.match(/<([^>]+)>/)
    if (emailMatch) {
      newNoteForm.value.sender_email = emailMatch[1].trim()
      newNoteForm.value.sender_name = rawFrom.replace(/<[^>]+>/, '').replace(/["']/g, '').trim()
    } else {
      newNoteForm.value.sender_email = rawFrom
      newNoteForm.value.sender_name = rawFrom.split('@')[0]
    }
  }

  const split = text.split(/\r?\n\r?\n/)
  if (split.length > 1) {
    newNoteForm.value.content = split.slice(1).join('\n\n').trim()
  } else {
    newNoteForm.value.content = text.trim()
  }

  newNoteForm.value.category = 'email'
  newNoteForm.value.analyzeWithAi = true

  const base64 = await readFileAsDataUrl(file)
  newNoteForm.value.attachments.push({
    file_name: file.name,
    file_type: file.type || 'message/rfc822',
    file_size: file.size,
    file_path: base64
  })
}

const saveNewEntry = async () => {
  if (!newEntryForm.value.title?.trim()) {
    journalError.value = 'Bitte gib einen Titel für den Journaleintrag an.'
    return
  }
  savingJournal.value = true
  journalError.value = ''
  try {
    const created = await $fetch<any>(`/api/projects/${projectId}/journal`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        type: 'entry',
        title: newEntryForm.value.title.trim(),
        category: newEntryForm.value.category || 'bausitzung',
        visibility: newEntryForm.value.visibility || 'all',
        allowed_group_id: newEntryForm.value.visibility === 'group' ? newEntryForm.value.allowed_group_id : null,
        task_id: newEntryForm.value.task_id || null,
        content: newEntryForm.value.content?.trim() || '',
        attendees: newEntryForm.value.attendees || [],
        attachments: newEntryForm.value.attachments || [],
        metadata: {
          entry_date: newEntryForm.value.entry_date
        }
      }
    })

    if (newEntryForm.value.analyzeWithAi && created.entry?.id && newEntryForm.value.content?.trim()) {
      try {
        const aiRes = await $fetch<any>(`/api/projects/${projectId}/journal/parse-email`, {
          method: 'POST',
          headers: authHeaders(),
          body: {
            journal_id: created.entry.id,
            subject: newEntryForm.value.title.trim(),
            content: newEntryForm.value.content.trim(),
            category: newEntryForm.value.category || 'bausitzung',
            visibility: newEntryForm.value.visibility || 'all',
            allowed_group_id: newEntryForm.value.visibility === 'group' ? newEntryForm.value.allowed_group_id : null
          }
        })
        if (newEntryForm.value.autoApply && aiRes?.metadata?.action_items?.length) {
          created.entry.metadata = aiRes.metadata
          await applyAllTaskActions(created.entry)
        }
      } catch (_) {}
    }

    showNewEntryModal.value = false
    await loadJournals()
  } catch (err: any) {
    journalError.value = err.data?.statusMessage || err.message || 'Fehler beim Speichern des Journaleintrags'
  } finally {
    savingJournal.value = false
  }
}

const saveNewNote = async () => {
  if (!newNoteForm.value.title?.trim() && !newNoteForm.value.content?.trim()) {
    journalError.value = 'Bitte gib einen Titel oder Inhalt für die Notiz an.'
    return
  }
  savingJournal.value = true
  journalError.value = ''
  try {
    if (newNoteForm.value.analyzeWithAi) {
      const aiRes = await $fetch<any>(`/api/projects/${projectId}/journal/parse-email`, {
        method: 'POST',
        headers: authHeaders(),
        body: {
          subject: newNoteForm.value.title?.trim() || 'E-Mail Import',
          content: newNoteForm.value.content?.trim() || '',
          sender_name: newNoteForm.value.sender_name?.trim() || '',
          sender_email: newNoteForm.value.sender_email?.trim() || '',
          category: newNoteForm.value.category || 'email',
          visibility: newNoteForm.value.visibility || 'all',
          allowed_group_id: newNoteForm.value.visibility === 'group' ? newNoteForm.value.allowed_group_id : null,
          attachments: newNoteForm.value.attachments || []
        }
      })
      if (newNoteForm.value.autoApply && aiRes?.entry?.id && aiRes?.metadata?.action_items?.length) {
        aiRes.entry.metadata = aiRes.metadata
        await applyAllTaskActions(aiRes.entry)
      }
    } else {
      await $fetch(`/api/projects/${projectId}/journal`, {
        method: 'POST',
        headers: authHeaders(),
        body: {
          project_id: projectId,
          type: 'note',
          title: newNoteForm.value.title?.trim() || 'Notiz',
          category: newNoteForm.value.category || 'allgemein',
          visibility: newNoteForm.value.visibility || 'all',
          allowed_group_id: newNoteForm.value.visibility === 'group' ? newNoteForm.value.allowed_group_id : null,
          content: newNoteForm.value.content?.trim() || '',
          attachments: newNoteForm.value.attachments || [],
          metadata: newNoteForm.value.category === 'email' ? {
            sender: {
              name: newNoteForm.value.sender_name?.trim() || '',
              email: newNoteForm.value.sender_email?.trim() || ''
            }
          } : null
        }
      })
    }
    showNewNoteModal.value = false
    await loadJournals()
  } catch (err: any) {
    journalError.value = err.data?.statusMessage || err.message || 'Fehler beim Speichern der Notiz'
  } finally {
    savingJournal.value = false
  }
}

const createJournalEntry = saveNewEntry

const deleteJournalEntry = async (entry: any) => {
  if (!confirm(t('journal.delete_entry_confirm') || 'Möchtest du diesen Journaleintrag wirklich löschen?')) return
  try {
    await $fetch(`/api/projects/${projectId}/journal/${entry.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    journalEntries.value = journalEntries.value.filter((e: any) => e.id !== entry.id)
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen des Eintrags')
  }
}

const triggerAiAnalysis = async (entry: any, autoApply = false) => {
  if (!entry.content?.trim() && !entry.title?.trim()) {
    alert('Eintrag hat keinen Text zum Analysieren.')
    return
  }
  analyzingEntryId.value = entry.id
  try {
    const res = await $fetch<any>(`/api/projects/${projectId}/journal/parse-email`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        journal_id: entry.id,
        subject: entry.title,
        content: entry.content,
        category: entry.category,
        visibility: entry.visibility
      }
    })
    if (res.metadata) {
      entry.metadata = res.metadata
    } else if (res.entry?.metadata) {
      entry.metadata = res.entry.metadata
    }

    if (autoApply && entry.metadata?.action_items?.length) {
      await applyAllTaskActions(entry)
    }
  } catch (err: any) {
    alert(err.data?.statusMessage || err.message || 'Fehler bei der KI-Analyse')
  } finally {
    analyzingEntryId.value = null
  }
}

const applyAllTaskActions = async (entry: any) => {
  if (!entry.metadata?.action_items || !entry.metadata.action_items.length) return
  isApplyingAllId.value = entry.id
  try {
    for (let i = 0; i < entry.metadata.action_items.length; i++) {
      const item = entry.metadata.action_items[i]
      if (item.applied) continue
      if (item.type === 'create_task') {
        await applyCreateTaskAction(entry, item, i)
      } else if (item.type === 'update_task') {
        await applyUpdateTaskAction(entry, item, i)
      } else if (item.type === 'complete_task') {
        await applyCompleteTaskAction(entry, item, i)
      }
    }
  } catch (err: any) {
    console.error('Fehler beim Anwenden aller Aktionen:', err)
  } finally {
    isApplyingAllId.value = null
  }
}

const applyCreateTaskAction = async (entry: any, item: any, idx: number) => {
  item.applying = true
  try {
    const listId = item.section_id || (lists.value.length > 0 ? lists.value[0].id : null)
    if (!listId) {
      alert('Kein Abschnitt im Projekt vorhanden, um eine Aufgabe anzulegen.')
      return
    }

    const res = await $fetch<any>('/api/tasks', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        list_id: listId,
        title: item.title || 'Neue Aufgabe aus Journal',
        description: item.description || item.reason || '',
        priority: item.priority || 'normal',
        due_date: item.due_date || item.suggested_due_date || null
      }
    })

    item.applied = true
    item.created_task_id = res.task?.id

    const meta = { ...(entry.metadata || {}) }
    await $fetch(`/api/projects/${projectId}/journal/${entry.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { metadata: meta }
    })

    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Anlegen der Aufgabe')
  } finally {
    item.applying = false
  }
}

const applyUpdateTaskAction = async (entry: any, item: any, idx: number) => {
  if (!item.task_id) return
  item.applying = true
  try {
    const updateBody: any = {}
    if (item.suggested_due_date || item.due_date) {
      updateBody.due_date = item.suggested_due_date || item.due_date
    }
    const st = item.suggested_status || item.status
    if (st) {
      updateBody.status = (st === 'completed' ? 'done' : st)
    }
    if (item.priority) {
      updateBody.priority = item.priority
    }

    await $fetch(`/api/tasks/${item.task_id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: updateBody
    })

    item.applied = true

    const meta = { ...(entry.metadata || {}) }
    await $fetch(`/api/projects/${projectId}/journal/${entry.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { metadata: meta }
    })

    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Aktualisieren der Aufgabe')
  } finally {
    item.applying = false
  }
}

const applyCompleteTaskAction = async (entry: any, item: any, idx: number) => {
  if (!item.task_id) return
  item.applying = true
  try {
    await $fetch(`/api/tasks/${item.task_id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { status: 'done' }
    })

    item.applied = true

    const meta = { ...(entry.metadata || {}) }
    await $fetch(`/api/projects/${projectId}/journal/${entry.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { metadata: meta }
    })

    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Abschliessen der Aufgabe')
  } finally {
    item.applying = false
  }
}

const getCategoryBadge = (category: string, type: string) => {
  if (type === 'entry') {
    switch (category) {
      case 'bausitzung':
        return { label: t('journal.category_bausitzung') || 'Bausitzung', icon: '🏛️', bg: 'bg-cyan-100 text-[#00A3C4] border-cyan-200' }
      case 'bautagebuch':
        return { label: t('journal.category_bautagebuch') || 'Bautagebuch', icon: '📋', bg: 'bg-emerald-100 text-emerald-800 border-emerald-200' }
      case 'abnahmebegehung':
        return { label: t('journal.category_abnahmebegehung') || 'Abnahmebegehung', icon: '🔍', bg: 'bg-purple-100 text-purple-800 border-purple-200' }
      case 'wetter_behinderung':
        return { label: t('journal.category_wetter_behinderung') || 'Wetter / Behinderung', icon: '⛈️', bg: 'bg-amber-100 text-amber-800 border-amber-200' }
      case 'regie':
        return { label: t('journal.category_regie') || 'Regie', icon: '⏱️', bg: 'bg-orange-100 text-orange-800 border-orange-200' }
      default:
        return { label: t('journal.category_allgemein') || 'Journaleintrag', icon: '📖', bg: 'bg-cyan-100 text-[#00A3C4] border-cyan-200' }
    }
  } else {
    switch (category) {
      case 'email':
        return { label: t('journal.category_email') || 'E-Mail', icon: '✉️', bg: 'bg-amber-100 text-amber-900 border-amber-200' }
      case 'wetter_behinderung':
        return { label: t('journal.category_wetter_behinderung') || 'Wetter / Behinderung', icon: '⛈️', bg: 'bg-amber-100 text-amber-800 border-amber-200' }
      case 'regie':
        return { label: t('journal.category_regie') || 'Regie', icon: '⏱️', bg: 'bg-orange-100 text-orange-800 border-orange-200' }
      default:
        return { label: t('journal.category_note') || 'Journalnotiz', icon: '📝', bg: 'bg-indigo-100 text-indigo-800 border-indigo-200' }
    }
  }
}

const getVisibilityBadge = (visibility: string, allowedGroupId?: string) => {
  switch (visibility) {
    case 'only_me':
      return { label: t('journal.visibility_only_me') || 'Nur ich', icon: '🔒', bg: 'bg-rose-50 text-rose-700 border-rose-200' }
    case 'group': {
      const g = userGroups.value.find((group: any) => group.id === allowedGroupId)
      return { label: g ? g.name : (t('journal.visibility_group') || 'Gruppe'), icon: '👥', bg: 'bg-purple-50 text-purple-700 border-purple-200' }
    }
    case 'company':
      return { label: t('journal.visibility_company') || 'Unternehmen', icon: '🏢', bg: 'bg-emerald-50 text-emerald-800 border-emerald-200' }
    case 'all':
    default:
      return { label: t('journal.visibility_all') || 'Projekt', icon: '🌐', bg: 'bg-slate-100 text-slate-700 border-slate-200' }
  }
}

const getTaskTitle = (taskId?: string) => {
  if (!taskId) return 'Aufgabe'
  for (const l of lists.value) {
    const t = l.tasks?.find((task: any) => task.id === taskId)
    if (t) return t.title
  }
  return `Aufgabe #${taskId}`
}

const inviteMember = async () => {
  try {
    await $fetch(`/api/projects/${projectId}/members`, {
      method: 'POST',
      headers: authHeaders(),
      body: {
        email: inviteEmail.value,
        role: inviteRole.value
      }
    })
    showInviteMemberModal.value = false
    inviteEmail.value = ''
    await loadProjectData()
    alert('Mitglied erfolgreich hinzugefügt!')
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Einladen des Mitglieds')
  }
}

watch(currentView, (val: string) => {
  if (val === 'time') {
    loadProjectTimeEntries()
  } else if (val === 'settings') {
    initSettingsTab()
  }
})

onMounted(async () => {
  await loadProjectData()
  await openTaskFromQuery()
  if (import.meta.client) {
    window.addEventListener('taskster-time-entry-saved', onGlobalTimeEntrySaved)
  }
})

/**
 * Öffnet eine Aufgabe direkt, wenn die URL einen ?task=<id> Parameter enthält.
 * Wird von Kalender, Benachrichtigungen und der Suche verlinkt.
 */
async function openTaskFromQuery() {
  const taskId = route.query.task
  if (!taskId || typeof taskId !== 'string') return

  // Aufgabe in den geladenen Abschnitten suchen
  let found: any = null
  for (const l of lists.value) {
    const t = (l.tasks || []).find((x: any) => x.id === taskId)
    if (t) { found = t; break }
  }

  if (found) {
    await openTaskDrawer(found)
    return
  }

  // Nicht in der Liste (z. B. anderer Abschnitt oder Filter): direkt laden
  try {
    const res = await $fetch<any>(`/api/tasks/${taskId}`, { headers: authHeaders() })
    if (res?.task) await openTaskDrawer(res.task)
  } catch {
    // Aufgabe existiert nicht (mehr) oder kein Zugriff – Seite bleibt normal nutzbar
  }
}

// Reagiert auf ?task= Änderungen, wenn die Projektseite bereits offen ist
watch(() => route.query.task, (id: any) => {
  if (id && typeof id === 'string') openTaskFromQuery()
})

onUnmounted(() => {
  if (import.meta.client) {
    window.removeEventListener('taskster-time-entry-saved', onGlobalTimeEntrySaved)
  }
})
</script>

<style scoped>
/* Scoped styles */
</style>
