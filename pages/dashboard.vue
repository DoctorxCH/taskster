<template>
  <div class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <!-- MeisterTask-Style Hero Section (Centered Date, Greeting, and Floating Search) -->
    <div class="flex flex-col items-center justify-center text-center select-none py-2">
      <!-- Formatted German Date Pill -->
      <div class="inline-flex items-center space-x-2 text-xs sm:text-sm font-semibold text-slate-700 mb-3 px-3.5 py-1.5 rounded-full bg-slate-100 border border-slate-200/80 shadow-2xs">
        <Calendar class="w-4 h-4 text-[#0891B2]" />
        <span>{{ formattedDate }}</span>
      </div>

      <!-- Personalized MeisterTask Motivational Greeting -->
      <div class="px-6 py-3 rounded-xl bg-white border border-slate-200/80 mb-2 shadow-xs max-w-2xl w-full">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight flex items-center justify-center flex-wrap gap-2">
          <span>{{ greetingPrefix }}, {{ user?.name || 'Martin' }}</span>
          <Sparkles class="w-5 h-5 text-amber-500 inline-block animate-pulse" />
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 font-medium mt-1">
          <span v-if="user?.company_name" class="font-bold text-[#0891B2]">{{ user.company_name }}</span>
          <span v-else>{{ $t('dashboard.privater_workspace') }}</span>
          – {{ $t('dashboard.deine_aktuellen_aufgaben') }}
        </p>
      </div>

      <!-- Command-Palette Trigger (öffnet die globale Suche) -->
      <div class="w-full max-w-xl mt-4 relative">
        <button
          type="button"
          @click="openCommandPalette"
          class="w-full flex items-center gap-3 pl-3.5 pr-3 py-2.5 rounded-lg bg-white text-left shadow-xs border border-slate-300 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0891B2]/30 focus:border-[#0891B2] transition-all cursor-pointer"
        >
          <Search class="w-4 h-4 text-slate-400 shrink-0" />
          <span class="flex-1 text-xs sm:text-sm text-slate-400 truncate">
            {{ $t('dashboard.suche_placeholder') }}
          </span>
          <span class="hidden sm:flex items-center gap-1 shrink-0">
            <kbd class="px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 rounded">{{ $t('dashboard.strg') }}</kbd>
            <kbd class="px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 rounded">K</kbd>
          </span>
        </button>
      </div>
    </div>

    <!-- Free-Plan Info Alert if applicable -->
    <div
      v-if="!user?.is_pro && !user?.company_id && !user?.is_superadmin"
      class="p-4 rounded-lg bg-amber-50/80 border border-amber-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
    >
      <div class="flex items-center space-x-3">
        <div class="w-9 h-9 rounded-lg bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-600 shrink-0">
          <Zap class="w-5 h-5" />
        </div>
        <div>
          <h4 class="text-xs font-bold text-amber-900">{{ $t('dashboard.free_plan_aktiv') }}</h4>
          <p class="text-xs text-amber-800/90 font-medium mt-0.5">
            {{ $t('dashboard.free_plan_hinweis') }}
          </p>
        </div>
      </div>
      <NuxtLink
        to="/settings"
        class="taskster_button px-4 text-xs h-9 rounded-md shrink-0"
      >
        {{ $t('dashboard.auf_pro_upgraden') }}
      </NuxtLink>
    </div>

    <!-- Main MeisterTask 2-Column Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- LEFT / CENTER COLUMN: Aufgaben & Projekte (8 Cols on LG) -->
      <div class="lg:col-span-8 space-y-6">
        <!-- WIDGET 1: Aufgaben & Tages-Todos (Daily Focus & MeisterTask Overview) -->
        <section class="bg-white border border-slate-200 rounded-lg p-5 sm:p-6 shadow-xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200 mb-5 gap-3">
            <div class="flex items-center space-x-3 flex-wrap gap-y-2">
              <div class="flex items-center space-x-2">
                <ClipboardList class="w-5 h-5 text-[#0891B2]" />
                <h2 class="text-base font-bold text-slate-900 tracking-tight">{{ $t('dashboard.aufgaben') }}</h2>
              </div>
              
              <!-- Tab Switcher: Mein Tag vs. Projekt-Aufgaben -->
              <div class="flex items-center p-1 bg-slate-100 border border-slate-200 rounded-md">
                <button
                  type="button"
                  @click="activeTaskTab = 'daily'"
                  class="flex items-center space-x-1.5 px-3 py-1 rounded text-xs font-semibold transition cursor-pointer"
                  :class="activeTaskTab === 'daily' ? 'bg-[#0891B2] text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  <Sun class="w-3.5 h-3.5" />
                  <span>{{ $t('dashboard.mein_tag') }}</span>
                  <span
                    class="text-[10px] px-1.5 py-0.2 rounded-full"
                    :class="activeTaskTab === 'daily' ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ uncompletedDailyTodosCount }}
                  </span>
                </button>

                <button
                  type="button"
                  @click="activeTaskTab = 'assigned'"
                  class="flex items-center space-x-1.5 px-3 py-1 rounded text-xs font-semibold transition cursor-pointer"
                  :class="activeTaskTab === 'assigned' ? 'bg-[#0891B2] text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  <Folder class="w-3.5 h-3.5" />
                  <span>{{ $t('dashboard.alle_projekte') }}</span>
                  <span
                    class="text-[10px] px-1.5 py-0.2 rounded-full"
                    :class="activeTaskTab === 'assigned' ? 'bg-white/25 text-white' : 'bg-slate-200 text-slate-700'"
                  >
                    {{ filteredTasks.length }}
                  </span>
                </button>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button
                type="button"
                @click="showVoiceModal = true"
                class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md bg-cyan-50 hover:bg-cyan-100 text-[#0891B2] border border-cyan-200 text-xs font-semibold transition shadow-2xs cursor-pointer"
                :title="$t('dashboard.sprachaufnahme_via_openaiwhisperlar')"
              >
                <Mic class="w-3.5 h-3.5 text-[#0891B2]" />
                <span>{{ $t('dashboard.neue_sprachnotiz') }}</span>
              </button>

              <NuxtLink
                to="/time"
                class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-md bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition shadow-2xs"
                :title="$t('dashboard.zur_globalen_zeitrapportierung')"
              >
                <Clock class="w-3.5 h-3.5 text-[#0891B2]" />
                <span>{{ $t('dashboard.zeitrapporte') }}</span>
              </NuxtLink>

              <button
                @click="activeTaskTab === 'daily' ? loadDailyTodos() : loadTasks()"
                class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition text-xs cursor-pointer ml-auto border border-slate-200"
                :title="$t('common.aktualisieren')"
              >
                <RotateCcw class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- TAB 1: MEIN TAG (Tages-Todos mit automatischem Rollover) -->
          <div v-if="activeTaskTab === 'daily'" class="space-y-4">
            <!-- Quick Add Bar for Today -->
            <form @submit.prevent="createDailyTodo" class="p-2 rounded-lg bg-slate-50 border border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
              <input
                v-model="newDailyTodoTitle"
                type="text"
                :placeholder="$t('dashboard.todo_today')"
                class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-md text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#0891B2] shadow-2xs"
              />

              <div class="flex items-center space-x-2">
                <select
                  v-model="newDailyTodoProjectId"
                  class="px-3 py-2 bg-white border border-slate-300 rounded-md text-xs font-medium text-slate-700 focus:outline-none focus:border-[#0891B2] shadow-2xs shrink-0 max-w-[190px] truncate"
                >
                  <option value="">{{ $t('dashboard.ohne_projekt_persönlich') }}</option>
                  <option v-for="p in availableProjects" :key="p.id" :value="p.id">
                    {{ p.title }}
                  </option>
                </select>

                <button
                  type="submit"
                  :disabled="creatingDailyTodo || !newDailyTodoTitle.trim()"
                  class="taskster_button px-3 text-xs h-[34px] rounded-md shrink-0 flex items-center space-x-1"
                >
                  <Plus class="w-3.5 h-3.5" />
                  <span>{{ creatingDailyTodo ? '...' : ($t('company.hinzufügen') || 'Hinzufügen') }}</span>
                </button>
              </div>
            </form>

            <!-- Progress Bar for Today -->
            <div v-if="dailyTodos.length > 0" class="px-3 py-2 rounded-md bg-slate-50 border border-slate-200 flex items-center justify-between text-xs text-slate-600 font-semibold gap-3">
              <div class="flex items-center space-x-2 min-w-0">
                <Target class="w-4 h-4 text-[#0891B2]" />
                <span>{{ $t('dashboard.heute_erledigt') }}</span>
                <span class="text-slate-900 font-bold">{{ completedDailyTodosCount }} {{ $t('dashboard.von') }} {{ dailyTodos.length }}</span>
                <span class="text-slate-400 font-normal">({{ completionPercentage }}%)</span>
              </div>
              <div class="w-36 sm:w-48 bg-slate-200 rounded-full h-2 overflow-hidden shrink-0">
                <div
                  class="bg-[#0891B2] h-2 rounded-full transition-all duration-500"
                  :style="{ width: completionPercentage + '%' }"
                ></div>
              </div>
            </div>

            <!-- Loading Daily Todos -->
            <div v-if="loadingDailyTodos" class="py-10 text-center text-xs text-slate-500 font-medium">
              ...
            </div>

            <!-- Empty Daily Todos State -->
            <div
              v-else-if="dailyTodos.length === 0"
              class="py-10 px-4 text-center flex flex-col items-center justify-center border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
              <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center mb-2 border border-amber-200">
                <Sun class="w-5 h-5" />
              </div>
              <h3 class="text-sm font-bold text-slate-900">{{ $t('dashboard.starte_tag_fokus') }}</h3>
              <p class="text-xs text-slate-500 mt-1 max-w-md">
                {{ $t('dashboard.starte_tag_fokus_desc') }}
              </p>
            </div>

            <!-- Daily Todos List -->
            <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="todo in dailyTodos"
                :key="todo.id"
                class="group/todo flex items-center justify-between p-3 rounded-lg border transition-all duration-200"
                :class="todo.is_completed ? 'bg-slate-50/80 border-slate-200 opacity-75' : 'bg-white hover:bg-slate-50/50 border-slate-200 hover:border-[#0891B2] shadow-2xs'"
              >
                <div class="flex items-center space-x-3 min-w-0">
                  <!-- Checkbox -->
                  <button
                    type="button"
                    @click="toggleDailyTodo(todo)"
                    class="w-5 h-5 rounded border flex items-center justify-center transition shrink-0 cursor-pointer"
                    :class="todo.is_completed ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 bg-white hover:border-[#0891B2]'"
                  >
                    <Check v-if="todo.is_completed" class="w-3.5 h-3.5 text-white stroke-[3]" />
                  </button>

                  <div class="min-w-0">
                    <p
                      class="text-xs sm:text-sm font-semibold transition block truncate"
                      :class="todo.is_completed ? 'line-through text-slate-400' : 'text-slate-900'"
                    >
                      {{ todo.title }}
                    </p>

                    <!-- Badges: Rollover & Project -->
                    <div class="flex items-center space-x-2 text-[11px] mt-0.5 flex-wrap gap-y-1">
                      <!-- Rollover Warning Pill -->
                      <span
                        v-if="todo.rollover_count > 0 && !todo.is_completed"
                        class="inline-flex items-center space-x-1 px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-semibold border border-amber-300 text-[10px]"
                        :title="$t('dashboard.automatisch_vom_vortag_übertragen_d')"
                      >
                        <RotateCcw class="w-3 h-3 text-amber-700" />
                        <span>{{ $t('dashboard.übertrag') }} {{ todo.rollover_count === 1 ? $t('dashboard.von_gestern') : $t('dashboard.von_vor_tagen', { days: todo.rollover_count }) }}</span>
                      </span>

                      <!-- Project Link Pill (if assigned) -->
                      <NuxtLink
                        v-if="todo.project_id"
                        :to="`/projects/${todo.project_id}`"
                        class="inline-flex items-center space-x-1 px-2 py-0.5 rounded bg-cyan-50 hover:bg-cyan-100 text-[#0891B2] font-semibold border border-cyan-200 transition text-[10px]"
                        :title="$t('dashboard.zum_projekt_öffnen')"
                      >
                        <Folder class="w-3 h-3" />
                        <span class="truncate max-w-[130px]">{{ todo.project_title }}</span>
                      </NuxtLink>

                      <span v-else class="text-[10px] text-slate-400">
                        {{ $t('dashboard.persönlich') }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-1 shrink-0 ml-3">
                  <button
                    type="button"
                    @click="deleteDailyTodo(todo.id)"
                    class="opacity-0 group-hover/todo:opacity-100 p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition text-xs cursor-pointer"
                    :title="$t('dashboard.tagestodo_löschen')"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: PROJEKT-AUFGABEN (Zugewiesene Aufgaben) -->
          <div v-else class="space-y-4">
            <!-- Loading Tasks -->
            <div v-if="loadingTasks" class="py-10 text-center text-xs text-slate-500 font-medium">
              {{ $t('dashboard.lade_offene_aufgaben') }}
            </div>

            <!-- Empty Tasks State -->
            <div
              v-else-if="filteredTasks.length === 0"
              class="py-10 px-4 text-center flex flex-col items-center justify-center border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
              <div class="w-10 h-10 rounded-lg bg-cyan-100 text-[#0891B2] flex items-center justify-center mb-2 border border-cyan-200">
                <Sparkles class="w-5 h-5" />
              </div>
              <h3 class="text-sm font-bold text-slate-900">{{ $t('dashboard.du_hast_keine_anstehenden_aufgaben') }}</h3>
              <p class="text-xs text-slate-500 mt-1 mb-4">
                {{ $t('dashboard.deine_zugewiesenen_aufgaben_aus_den') }}
              </p>
              <NuxtLink
                v-if="!isFreeUser && folders.length > 0"
                :to="`/folders/${folders[0]?.id}`"
                class="taskster_button px-4 text-xs h-9 rounded-md"
              >
                {{ $t('dashboard.aufgabe_in_projekten_anzeigen') }}
              </NuxtLink>
              <NuxtLink
                v-else-if="isFreeUser && projects.length > 0"
                :to="`/projects/${projects[0]?.id}`"
                class="taskster_button px-4 text-xs h-9 rounded-md"
              >
                {{ $t('dashboard.aufgabe_in_projekten_anzeigen') }}
              </NuxtLink>
              <button
                v-else-if="isFreeUser"
                @click="openNewProjectModal"
                class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $t('dashboard.erstes_projekt_erstellen') }}</span>
              </button>
              <button
                v-else
                @click="openNewFolderModal"
                class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $t('dashboard.ersten_ordner_erstellen') }}</span>
              </button>
            </div>

            <!-- Tasks List -->
            <div v-else class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="task in filteredTasks"
                :key="task.id"
                class="group/task flex items-center justify-between p-3.5 rounded-lg border border-slate-200 hover:border-[#0891B2] bg-white hover:bg-slate-50/50 shadow-2xs transition-all"
              >
                <div class="flex items-center space-x-3 min-w-0">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#0891B2] shrink-0"></span>
                  <div class="min-w-0">
                    <NuxtLink
                      :to="`/projects/${task.project_id}?task=${task.id}`"
                      class="text-xs sm:text-sm font-bold text-slate-900 group-hover/task:text-[#0891B2] transition block truncate"
                    >
                      {{ task.title }}
                    </NuxtLink>
                    <div class="flex items-center space-x-2 text-[11px] text-slate-500 mt-0.5">
                      <span class="flex items-center space-x-1">
                        <Folder class="w-3 h-3 text-slate-400 inline" />
                        <span>{{ task.folder_name }}</span>
                      </span>
                      <span>/</span>
                      <span class="font-medium text-slate-700">{{ task.project_title }}</span>
                      <span class="text-slate-300">•</span>
                      <span class="px-2 py-0.2 rounded bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-semibold">
                        {{ task.list_title }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-2 shrink-0 ml-3">
                  <!-- Live Stopwatch running badge on Dashboard -->
                  <div
                    v-if="stopwatchState.isRunning && stopwatchState.taskId === task.id"
                    class="flex items-center space-x-1.5 px-2 py-1 rounded bg-slate-900 text-white font-mono font-bold text-[10px] shadow-xs"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-cyan-300">{{ formatSeconds(stopwatchState.elapsedSeconds) }}</span>
                    <button
                      type="button"
                      @click.stop="openStopModal"
                      class="ml-1 px-1.5 py-0.5 bg-rose-600 hover:bg-rose-500 text-white rounded font-bold text-[9px] shadow-2xs cursor-pointer"
                      :title="$t('dashboard.stoppen_buchen')"
                    >
                      {{ $t('dashboard.stopp') }}
                    </button>
                  </div>

                  <button
                    v-else
                    type="button"
                    @click.stop="startTaskTimer(task)"
                    class="p-1.5 rounded text-slate-400 hover:text-[#0891B2] hover:bg-cyan-50 transition text-xs font-bold cursor-pointer"
                    :title="$t('dashboard.stoppuhr_auf_diese_aufgabe_starten')"
                  >
                    <Clock class="w-4 h-4" />
                  </button>

                  <span
                    v-if="task.due_date"
                    class="text-[10px] font-semibold px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 flex items-center space-x-1"
                  >
                    <Calendar class="w-3 h-3 text-amber-600" />
                    <span>{{ task.due_date }}</span>
                  </span>
                  <NuxtLink
                    :to="`/projects/${task.project_id}?task=${task.id}`"
                    class="p-1.5 rounded text-slate-400 hover:text-[#0891B2] hover:bg-slate-100 transition text-xs"
                    :title="$t('dashboard.aufgabe_öffnen')"
                  >
                    <ArrowRight class="w-4 h-4" />
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- WIDGET 2: Projekte & Projektordner (MeisterTask Style) -->
        <section class="bg-white border border-slate-200 rounded-lg p-5 sm:p-6 shadow-xs">
          <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-5">
            <div class="flex items-center space-x-3">
              <Folder class="w-5 h-5 text-[#0891B2]" />
              <div>
                <h2 class="text-base font-bold text-slate-900 tracking-tight">
                  {{ isFreeUser ? $t('dashboard.projekte') : $t('dashboard.projektordner_initiativen') }}
                </h2>
                <p class="text-xs text-slate-500 font-medium">
                  {{ isFreeUser ? $t('dashboard.deine_projekte_und_aufgaben') : $t('dashboard.übergeordnete_bereiche_für_teams_un') }}
                </p>
              </div>
            </div>

            <button
              v-if="isFreeUser"
              @click="openNewProjectModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>{{ $t('dashboard.neues_projekt') }}</span>
            </button>
            <button
              v-else
              @click="openNewFolderModal"
              class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>{{ $t('dashboard.neuer_ordner') }}</span>
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loadingFolders" class="py-10 text-center text-xs text-slate-500 font-medium">
            {{ isFreeUser ? $t('dashboard.lade_projekte') : $t('dashboard.lade_projektordner') }}
          </div>

          <!-- FREE-USER: Direct Project List (no folder level) -->
          <template v-else-if="isFreeUser">
            <div
              v-if="filteredProjects.length === 0"
              class="py-10 px-4 text-center flex flex-col items-center justify-center border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
              <div class="w-12 h-12 rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-3 border border-cyan-200">
                <Folder class="w-6 h-6" />
              </div>
              <h3 class="text-sm font-bold text-slate-900">{{ $t('dashboard.keine_projekte_gefunden') }}</h3>
              <p class="text-xs text-slate-500 mt-1 mb-4 max-w-sm">
                {{ $t('dashboard.erstelle_dein_erstes_projekt') }}
              </p>
              <button
                @click="openNewProjectModal"
                class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $t('dashboard.jetzt_projekt_anlegen') }}</span>
              </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="project in filteredProjects"
                :key="project.id"
                class="group/card bg-white border border-slate-200 hover:border-[#0891B2] rounded-lg p-4 transition-all duration-200 flex flex-col justify-between shadow-2xs hover:shadow-xs"
              >
                <div>
                  <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 border border-cyan-200 flex items-center justify-center text-[#0891B2] text-xl font-bold group-hover/card:scale-105 transition-transform">
                      <ClipboardList class="w-5 h-5" />
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded border bg-slate-100 border-slate-200 text-slate-700 capitalize">
                      {{ project.status }}
                    </span>
                  </div>

                  <h3 class="text-sm font-bold text-slate-900 group-hover/card:text-[#0891B2] transition mb-1">
                    {{ project.title }}
                  </h3>
                  <p class="text-xs text-slate-500 flex items-center space-x-1">
                    <span>{{ project.currency || 'CHF' }}</span>
                  </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                  <button
                    v-if="project.owner_id === user?.id || user?.is_superadmin"
                    type="button"
                    @click.stop="openDeleteProjectModal(project)"
                    class="p-1.5 rounded bg-slate-50 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-400 hover:text-rose-600 transition cursor-pointer text-xs"
                    :title="$t('dashboard.projekt_loeschen')"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                  <NuxtLink
                    :to="`/projects/${project.id}`"
                    class="taskster_button px-3 text-xs h-7 rounded-md inline-flex items-center"
                  >
                    <span>{{ $t('dashboard.öffnen') }}</span>
                    <ArrowRight class="w-3.5 h-3.5 ml-1" />
                  </NuxtLink>
                </div>
              </div>
            </div>
          </template>

          <!-- COMPANY / PRO: Folder Grid -->
          <template v-else>
            <!-- Empty Folders State -->
            <div
              v-if="filteredFolders.length === 0"
              class="py-10 px-4 text-center flex flex-col items-center justify-center border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
              <div class="w-12 h-12 rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-3 border border-cyan-200">
                <Folder class="w-6 h-6" />
              </div>
              <h3 class="text-sm font-bold text-slate-900">{{ $t('dashboard.keine_projektordner_gefunden') }}</h3>
              <p class="text-xs text-slate-500 mt-1 mb-4 max-w-sm">
                {{ $t('dashboard.keine_projektordner_gefunden') }}
              </p>
              <button
                @click="openNewFolderModal"
                class="taskster_button px-4 text-xs h-9 rounded-md flex items-center space-x-1"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ $t('dashboard.jetzt_ordner_anlegen') }}</span>
              </button>
            </div>

            <!-- Folders Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="folder in filteredFolders"
                :key="folder.id"
                class="group/card bg-white border border-slate-200 hover:border-[#0891B2] rounded-lg p-4 transition-all duration-200 flex flex-col justify-between shadow-2xs hover:shadow-xs"
              >
                <div>
                  <div class="flex items-start justify-between mb-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 border border-cyan-200 flex items-center justify-center text-[#0891B2] text-xl font-bold group-hover/card:scale-105 transition-transform">
                      <span v-if="folder.icon">{{ folder.icon }}</span>
                      <Folder v-else class="w-5 h-5" />
                    </div>
                    <div class="flex items-center space-x-1.5">
                      <span
                        class="text-[10px] font-semibold px-2 py-0.5 rounded border flex items-center space-x-1"
                        :class="folder.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-700'"
                      >
                        <Building2 v-if="folder.visibility === 'company'" class="w-3 h-3 text-emerald-600 inline mr-0.5" />
                        <Lock v-else class="w-3 h-3 text-slate-500 inline mr-0.5" />
                        <span>{{ folder.visibility === 'company' ? $t('common.unternehmen') : $t('dashboard.privat') }}</span>
                      </span>
                      <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700">
                        {{ folder.project_count }} {{ folder.project_count === 1 ? $t('dashboard.projekt') : $t('dashboard.projekte') }}
                      </span>
                    </div>
                  </div>

                  <h3 class="text-sm font-bold text-slate-900 group-hover/card:text-[#0891B2] transition mb-1">
                    {{ folder.name }}
                  </h3>
                  <p class="text-xs text-slate-500 flex items-center space-x-1">
                    <span>{{ $t('dashboard.inhaber') }}</span>
                    <span class="text-slate-800 font-semibold">{{ folder.owner_name }}</span>
                    <span v-if="user?.id === folder.owner_id" class="text-[10px] px-1.5 py-0.2 rounded bg-cyan-100 text-[#0891B2] font-bold ml-1">
                      {{ $t('settings.du') }}
                    </span>
                  </p>
                  <p v-if="folder.company_name" class="text-[11px] text-slate-600 mt-1 font-medium flex items-center space-x-1">
                    <Building2 class="w-3 h-3 text-slate-400" />
                    <span>{{ folder.company_name }}</span>
                  </p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                  <span class="text-[11px] text-slate-400 font-medium">
                    {{ new Date(folder.created_at).toLocaleDateString(locale === 'sk' ? 'sk-SK' : (locale === 'en' ? 'en-US' : 'de-CH')) }}
                  </span>
                  <div class="flex items-center space-x-2">
                    <button
                      v-if="user?.id === folder.owner_id"
                      @click="openEditFolderModal(folder)"
                      class="p-1.5 rounded bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 transition cursor-pointer text-xs"
                      :title="$t('dashboard.projektordner_anpassen_name')"
                    >
                      <Pencil class="w-3.5 h-3.5" />
                    </button>
                    <button
                      v-if="user?.id === folder.owner_id || user?.is_superadmin"
                      @click.stop="openDeleteFolderModal(folder)"
                      class="p-1.5 rounded bg-slate-50 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-400 hover:text-rose-600 transition cursor-pointer text-xs"
                      :title="$t('dashboard.ordner_loeschen')"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                    <NuxtLink
                      :to="`/folders/${folder.id}`"
                      class="taskster_button px-3 text-xs h-7 rounded-md inline-flex items-center"
                    >
                      <span>{{ $t('dashboard.öffnen') }}</span>
                      <ArrowRight class="w-3.5 h-3.5 ml-1" />
                    </NuxtLink>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </section>
      </div>

      <!-- RIGHT COLUMN: Benachrichtigungen & Schnellzugriff (4 Cols on LG) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- WIDGET 3: Benachrichtigungen / Activity Feed (5 Event-Typen) -->
        <section class="bg-white border border-slate-200 rounded-lg p-5 sm:p-6 shadow-xs">
          <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-4">
            <div class="flex items-center space-x-2.5">
              <Bell class="w-5 h-5 text-[#0891B2]" />
              <h2 class="text-base font-bold text-slate-900 tracking-tight">{{ $t('Navbar.benachrichtigungen') }}</h2>
            </div>
            <div class="flex items-center space-x-2">
              <span
                class="text-xs font-bold px-2 py-0.5 rounded-full transition"
                :class="unreadCount > 0 ? 'bg-[#0891B2] text-white shadow-2xs' : 'bg-slate-200 text-slate-700'"
              >
                {{ unreadCount }}
              </span>
              <button
                type="button"
                @click="loadNotifications"
                class="p-1.5 rounded hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition text-xs cursor-pointer border border-slate-200"
                :title="$t('common.aktualisieren')"
              >
                <RotateCcw class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Notification Filter Tabs -->
          <div class="flex items-center space-x-3 text-xs font-semibold border-b border-slate-200 pb-2 mb-4 text-slate-500">
            <button
              @click="activeNotificationTab = 'all'"
              class="pb-1 transition border-b-2 cursor-pointer"
              :class="activeNotificationTab === 'all' ? 'border-[#0891B2] text-[#0891B2]' : 'border-transparent hover:text-slate-800'"
            >
              {{ $t('dashboard.alle') }}
            </button>
            <button
              @click="activeNotificationTab = 'mentions'"
              class="pb-1 transition border-b-2 cursor-pointer"
              :class="activeNotificationTab === 'mentions' ? 'border-[#0891B2] text-[#0891B2]' : 'border-transparent hover:text-slate-800'"
            >
              {{ $t('dashboard.kommentare_einladungen') }}
            </button>
            <button
              @click="activeNotificationTab = 'projects'"
              class="pb-1 transition border-b-2 cursor-pointer"
              :class="activeNotificationTab === 'projects' ? 'border-[#0891B2] text-[#0891B2]' : 'border-transparent hover:text-slate-800'"
            >
              {{ $t('dashboard.fälligkeiten_budget') }}
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loadingNotifications" class="py-8 text-center text-xs text-slate-500 font-medium">
            ...
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredNotifications.length === 0" class="py-8 text-center flex flex-col items-center">
            <div class="w-10 h-10 rounded-lg bg-cyan-50 text-[#0891B2] flex items-center justify-center mb-2 border border-cyan-200">
              <PartyPopper class="w-5 h-5" />
            </div>
            <p class="text-xs text-slate-900 font-bold mb-0.5">
              {{ $t('dashboard.keine_benachrichtigungen') }}
            </p>
            <p class="text-[11px] text-slate-500">
              {{ $t('dashboard.keine_benachrichtigungen_desc') }}
            </p>
          </div>

          <!-- Notification Feed -->
          <div v-else class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
            <div
              v-for="notif in filteredNotifications"
              :key="notif.id"
              class="p-3 rounded-lg border transition-all text-xs flex items-start justify-between gap-2.5"
              :class="notif.is_read ? 'bg-slate-50 border-slate-200 text-slate-600' : 'bg-white border-cyan-300 shadow-2xs text-slate-900 ring-1 ring-cyan-500/10'"
            >
              <div class="flex items-start space-x-2.5 min-w-0">
                <span class="shrink-0 mt-0.5 text-[#0891B2]">
                  <Clock v-if="notif.type === 'due_soon'" class="w-4 h-4 text-amber-600" />
                  <MessageSquare v-else-if="notif.type === 'new_comment'" class="w-4 h-4 text-blue-600" />
                  <Pencil v-else-if="notif.type === 'task_updated'" class="w-4 h-4 text-emerald-600" />
                  <Mail v-else-if="notif.type === 'invitation'" class="w-4 h-4 text-purple-600" />
                  <CreditCard v-else class="w-4 h-4 text-rose-600" />
                </span>
                <div class="min-w-0">
                  <div class="flex items-center space-x-2">
                    <p class="font-bold truncate" :class="notif.is_read ? 'text-slate-700' : 'text-slate-900'">
                      {{ notif.title }}
                    </p>
                    <span v-if="!notif.is_read" class="w-2 h-2 rounded-full bg-[#0891B2] shrink-0"></span>
                  </div>
                  <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                    {{ notif.message }}
                  </p>
                  <div class="flex items-center space-x-3 mt-1.5 text-[10px] text-slate-400 font-medium">
                    <span>{{ formatRelativeTime(notif.created_at) }}</span>
                    <!-- Aufgabe: direkt mit ?task= verlinken, damit der Drawer aufgeht -->
                    <NuxtLink
                      v-if="notif.reference_type === 'task' && notif.reference_id && notif.project_id"
                      :to="`/projects/${notif.project_id}?task=${notif.reference_id}`"
                      class="text-[#0891B2] hover:underline font-semibold"
                    >
                      {{ $t('dashboard.zur_aufgabe') }}
                    </NuxtLink>
                    <NuxtLink
                      v-else-if="notif.project_id"
                      :to="`/projects/${notif.project_id}`"
                      class="text-[#0891B2] hover:underline font-semibold"
                    >
                      {{ $t('dashboard.zum_projekt') }}
                    </NuxtLink>
                    <NuxtLink
                      v-else-if="notif.reference_type === 'folder' && notif.reference_id"
                      :to="`/folders/${notif.reference_id}`"
                      class="text-[#0891B2] hover:underline font-semibold"
                    >
                      {{ $t('dashboard.zum_ordner') }}
                    </NuxtLink>
                  </div>
                </div>
              </div>

              <button
                v-if="!notif.is_read"
                @click="markNotificationRead(notif)"
                type="button"
                class="shrink-0 p-1 text-slate-400 hover:text-[#0891B2] hover:bg-slate-100 rounded transition text-xs font-bold cursor-pointer"
                :title="$t('dashboard.als_gelesen_markieren')"
              >
                <Check class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Mark all as read footer -->
          <div v-if="unreadCount > 0" class="pt-4 border-t border-slate-200 mt-4 text-center">
            <button
              type="button"
              @click="markAllNotificationsRead"
              class="taskster_button_light px-4 text-xs h-8 rounded-md mx-auto"
            >
              {{ $t('dashboard.alle_als_gelesen') }}
            </button>
          </div>
        </section>

        <!-- WIDGET 4: Quick Summary / Stats (MeisterTask Style Compact Card) -->
        <section class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs">
          <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">
            {{ $t('dashboard.system_workspace_status') }}
          </h3>

          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 rounded-md bg-slate-50 border border-slate-200">
              <span class="text-xs text-slate-700 font-medium">{{ $t('dashboard.projekt_ordner') }}</span>
              <span class="text-sm font-bold text-slate-900">{{ folders.length }}</span>
            </div>

            <div class="flex items-center justify-between p-3 rounded-md bg-slate-50 border border-slate-200">
              <span class="text-xs text-slate-700 font-medium">{{ $t('dashboard.aktive_projekte') }}</span>
              <span class="text-sm font-bold text-[#0891B2]">{{ totalProjects }}</span>
            </div>

            <div class="flex items-center justify-between p-3 rounded-md bg-slate-50 border border-slate-200">
              <span class="text-xs text-slate-700 font-medium">{{ $t('dashboard.offene_aufgaben') }}</span>
              <span class="text-sm font-bold text-slate-900">{{ tasks.length }}</span>
            </div>

            <div class="p-3 rounded-md bg-emerald-50 border border-emerald-200 flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-xs font-semibold text-emerald-950">{{ $t('dashboard.zerotrust_pipeline') }}</span>
              </div>
              <span class="text-[10px] font-bold text-emerald-800">{{ $t('common.aktiv') }}</span>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Modal: New Folder -->
    <div v-if="showNewFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
      <div class="bg-white rounded-lg p-6 max-w-lg w-full shadow-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-bold text-slate-900">{{ $t('dashboard.neuen_projektordner_anlegen') }}</h3>
          <button @click="showNewFolderModal = false" class="text-slate-400 hover:text-slate-700 p-1">
            <X class="w-4 h-4" />
          </button>
        </div>
        <p class="text-xs text-slate-500 font-medium mb-4">
          {{ $t('dashboard.projektordner_bilden_die_oberste_or') }}
        </p>

        <div v-if="folderModalError" class="mb-4 p-3 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
          {{ folderModalError }}
        </div>

        <form @submit.prevent="createFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">{{ $t('dashboard.name_des_projektordners') }}</label>
            <input
              v-model="newFolderName"
              type="text"
              required
              :placeholder="$t('dashboard.zb_ftth_glasfaserausbau_region_nord')"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-md text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#0891B2] shadow-2xs transition"
            />
          </div>

          <!-- Sichtbarkeit im Unternehmen (Default: Privat) -->
          <div v-if="user?.company_id" class="p-3 bg-slate-50 border border-slate-200 rounded-md space-y-2">
            <label class="block text-xs font-bold text-slate-700">{{ $t('dashboard.sichtbarkeit_des_ordners') }}</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="flex items-center space-x-2 p-2 rounded-md border cursor-pointer transition text-xs font-semibold"
                :class="newFolderVisibility === 'private' ? 'bg-white border-[#0891B2] text-[#0891B2] ring-1 ring-[#0891B2]' : 'bg-white border-slate-200 text-slate-700'"
              >
                <input type="radio" value="private" v-model="newFolderVisibility" class="sr-only" />
                <Lock class="w-3.5 h-3.5" />
                <span>{{ $t('dashboard.privat_standard') }}</span>
              </label>
              <label
                class="flex items-center space-x-2 p-2 rounded-md border cursor-pointer transition text-xs font-semibold"
                :class="newFolderVisibility === 'company' ? 'bg-white border-[#0891B2] text-[#0891B2] ring-1 ring-[#0891B2]' : 'bg-white border-slate-200 text-slate-700'"
              >
                <input type="radio" value="company" v-model="newFolderVisibility" class="sr-only" />
                <Building2 class="w-3.5 h-3.5" />
                <span>{{ $t('common.unternehmen') }}</span>
              </label>
            </div>
            <p class="text-[11px] text-slate-500">
              {{ newFolderVisibility === 'private' ? $t('dashboard.privater_ordner_desc') : $t('dashboard.unternehmens_ordner_desc', { company: user?.company_name || $t('common.firma') }) }}
            </p>
          </div>
          <div v-else class="p-3 bg-amber-50 border border-amber-200 rounded-md text-xs text-amber-900">
            <span class="font-bold">{{ $t('dashboard.hinweis') }}</span> {{ $t('dashboard.dieser_ordner_ist_standardmäßig_pri') }} <strong>{{ $t('dashboard.ordner_teilen') }}</strong> {{ $t('dashboard.für_kollegen_freigeben') }}
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
            <button
              type="button"
              @click="showNewFolderModal = false; folderModalError = ''"
              class="taskster_button_light px-4 text-xs h-9 rounded-md"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              :disabled="creatingFolder || !newFolderName.trim()"
              class="taskster_button px-4 text-xs h-9 rounded-md"
            >
              <span>{{ creatingFolder ? $t('dashboard.erstelle') : $t('dashboard.ordner_erstellen') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Folder (Owner only) -->
    <div v-if="showEditFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
      <div class="bg-white rounded-lg p-6 max-w-lg w-full shadow-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-bold text-slate-900">{{ $t('dashboard.projektordner_anpassen') }}</h3>
          <button @click="showEditFolderModal = false" class="text-slate-400 hover:text-slate-700 p-1">
            <X class="w-4 h-4" />
          </button>
        </div>
        <p class="text-xs text-slate-500 font-medium mb-4">
          {{ $t('dashboard.passe_den_namen_und_die_sichtbarkei') }}
        </p>

        <div v-if="editFolderError" class="mb-4 p-3 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
          {{ editFolderError }}
        </div>

        <form @submit.prevent="updateFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">{{ $t('dashboard.name_des_projektordners') }}</label>
            <input
              v-model="editFolderName"
              type="text"
              required
              :placeholder="$t('dashboard.zb_privates_renovationsprojekt')"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-md text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#0891B2] shadow-2xs transition"
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

          <!-- Sichtbarkeit im Unternehmen -->
          <div v-if="user?.company_id || editFolderCompanyId" class="p-3 bg-slate-50 border border-slate-200 rounded-md space-y-2">
            <label class="block text-xs font-bold text-slate-700">{{ $t('dashboard.sichtbarkeit_des_ordners') }}</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="flex items-center space-x-2 p-2 rounded-md border cursor-pointer transition text-xs font-semibold"
                :class="editFolderVisibility === 'private' ? 'bg-white border-[#0891B2] text-[#0891B2] ring-1 ring-[#0891B2]' : 'bg-white border-slate-200 text-slate-700'"
              >
                <input type="radio" value="private" v-model="editFolderVisibility" class="sr-only" />
                <Lock class="w-3.5 h-3.5" />
                <span>{{ $t('dashboard.privat_standard') }}</span>
              </label>
              <label
                class="flex items-center space-x-2 p-2 rounded-md border cursor-pointer transition text-xs font-semibold"
                :class="editFolderVisibility === 'company' ? 'bg-white border-[#0891B2] text-[#0891B2]' : 'bg-white border-slate-200 text-slate-700'"
              >
                <input type="radio" value="company" v-model="editFolderVisibility" class="sr-only" />
                <Building2 class="w-3.5 h-3.5" />
                <span>{{ $t('common.unternehmen') }}</span>
              </label>
            </div>
            <p class="text-[11px] text-slate-500">
              {{ editFolderVisibility === 'private' ? $t('dashboard.privater_ordner_desc') : $t('dashboard.unternehmens_ordner_desc', { company: editFolderCompanyName || user?.company_name || $t('common.firma') }) }}
            </p>
          </div>

          <div class="flex items-center justify-between pt-3 border-t border-slate-200">
            <button
              v-if="user?.id === editFolderOwnerId || user?.is_superadmin"
              type="button"
              @click="showEditFolderModal = false; openDeleteFolderModal({ id: editFolderId, name: editFolderName, project_count: editFolderProjectCount })"
              class="taskster_button_accent px-3 text-xs h-9 rounded-md flex items-center space-x-1 cursor-pointer"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>{{ $t('dashboard.ordner_loeschen') }}</span>
            </button>
            <div class="flex items-center space-x-3 ml-auto">
              <button
                type="button"
                @click="showEditFolderModal = false; editFolderError = ''"
                class="taskster_button_light px-4 text-xs h-9 rounded-md cursor-pointer"
              >
                {{ $t('common.abbrechen') }}
              </button>
              <button
                type="submit"
                :disabled="savingFolder || !editFolderName.trim()"
                class="taskster_button px-4 text-xs h-9 rounded-md cursor-pointer"
              >
                <span>{{ savingFolder ? $t('dashboard.speichern_dot') : $t('dashboard.änderungen_speichern') }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: New Project (nur Free-/Single-User, ohne Ordner-Ebene) -->
    <div v-if="showNewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40">
      <div class="bg-white rounded-lg p-6 max-w-lg w-full shadow-xl border border-slate-200">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-bold text-slate-900">{{ $t('dashboard.neues_projekt') }}</h3>
          <button @click="showNewProjectModal = false" class="text-slate-400 hover:text-slate-700 p-1">
            <X class="w-4 h-4" />
          </button>
        </div>
        <p class="text-xs text-slate-500 font-medium mb-4">
          {{ $t('dashboard.erstelle_ein_neues_projekt') }}
        </p>

        <div v-if="projectModalError" class="mb-4 p-3 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
          {{ projectModalError }}
        </div>

        <form @submit.prevent="createProject" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">{{ $t('dashboard.projekttitel') }}</label>
            <input
              v-model="newProjectTitle"
              type="text"
              required
              :placeholder="$t('dashboard.zb_privates_renovationsprojekt')"
              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-md text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#0891B2] shadow-2xs transition"
            />
          </div>

          <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
            <button
              type="button"
              @click="showNewProjectModal = false; projectModalError = ''"
              class="taskster_button_light px-4 text-xs h-9 rounded-md"
            >
              {{ $t('common.abbrechen') }}
            </button>
            <button
              type="submit"
              :disabled="creatingProject || !newProjectTitle.trim()"
              class="taskster_button px-4 text-xs h-9 rounded-md"
            >
              <span>{{ creatingProject ? $t('dashboard.erstelle') : $t('dashboard.projekt_erstellen') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Voice Recorder (Whisper v3 Turbo) -->
  <VoiceRecorderModal
    v-model="showVoiceModal"
    :projects="availableProjects"
    @saved="onVoiceNoteSaved"
  />

  <!-- DELETE FOLDER CONFIRMATION MODAL -->
  <div v-if="showDeleteFolderModal && folderToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
      <div class="flex items-center space-x-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-rose-100 border border-rose-200 flex items-center justify-center text-rose-600 shrink-0">
          <AlertTriangle class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-black text-slate-900">{{ $t('dashboard.ordner_loeschen_titel') }}</h3>
          <p class="text-xs text-slate-500 font-medium">{{ folderToDelete.name }}</p>
        </div>
      </div>

      <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed mb-5">
        {{ $t('dashboard.ordner_loeschen_confirm', { name: folderToDelete.name, count: folderToDelete.project_count || 0 }) }}
      </div>

      <div v-if="deleteFolderError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
        {{ deleteFolderError }}
      </div>

      <div class="flex items-center justify-end space-x-3">
        <button
          type="button"
          @click="showDeleteFolderModal = false; folderToDelete = null; deleteFolderError = ''"
          :disabled="deletingFolder"
          class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
        >
          {{ $t('common.abbrechen') }}
        </button>
        <button
          type="button"
          @click="confirmDeleteFolder"
          :disabled="deletingFolder"
          class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg cursor-pointer flex items-center space-x-1.5"
        >
          <Trash2 v-if="!deletingFolder" class="w-3.5 h-3.5" />
          <span>{{ deletingFolder ? 'Wird gelöscht...' : $t('dashboard.ordner_loeschen_button') }}</span>
        </button>
      </div>
    </div>
  </div>

  <!-- DELETE PROJECT CONFIRMATION MODAL (FREE USER) -->
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
</template>

<script setup lang="ts">
import {
  Calendar,
  Sparkles,
  Search,
  Zap,
  ClipboardList,
  Sun,
  Folder,
  Clock,
  RotateCcw,
  Plus,
  Target,
  Check,
  Trash2,
  ArrowRight,
  Building2,
  Lock,
  Pencil,
  Bell,
  PartyPopper,
  MessageSquare,
  Mail,
  CreditCard,
  X,
  Mic,
  AlertTriangle
} from 'lucide-vue-next'

const { user, authHeaders } = useAuth()
const route = useRoute()
const { t, locale } = useI18n()
const { state: stopwatchState, startTimer, openStopModal, formatSeconds } = useStopwatch()
const showVoiceModal = ref(false)

const onVoiceNoteSaved = (payload: any) => {
  if (payload.type === 'task') {
    loadTasks()
  }
}

const startTaskTimer = (task: any) => {
  if (!task) return
  startTimer({
    projectId: task.project_id,
    projectTitle: task.project_title || t('dashboard.projekt'),
    projectCurrency: 'CHF',
    taskId: task.id,
    taskTitle: task.title
  })
}

const folders = ref<any[]>([])
const projects = ref<any[]>([])
const tasks = ref<any[]>([])
const loadingFolders = ref(true)
const loadingTasks = ref(true)
const searchQuery = ref('')

// Free-/Single-User (ohne Company) sehen die Ordner-Ebene nicht.
// Projekte werden direkt auf dem Dashboard angezeigt.
const isFreeUser = computed(() => !user.value?.is_pro && !user.value?.company_id && !user.value?.is_superadmin)

// Öffnet die globale Command-Palette (Strg+K)
const openCommandPalette = () => {
  window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true, bubbles: true }))
}

// Tasks Tab Switcher: 'daily' (Mein Tag) vs 'assigned' (Projekt-Aufgaben)
const activeTaskTab = ref<'daily' | 'assigned'>('daily')

// Daily Todos State ("Mein Tag" mit Rollover)
const dailyTodos = ref<any[]>([])
const loadingDailyTodos = ref(true)
const newDailyTodoTitle = ref('')
const newDailyTodoProjectId = ref('')
const availableProjects = ref<any[]>([])
const creatingDailyTodo = ref(false)

const completedDailyTodosCount = computed(() => dailyTodos.value.filter((t: any) => t.is_completed).length)
const uncompletedDailyTodosCount = computed(() => dailyTodos.value.filter((t: any) => !t.is_completed).length)
const completionPercentage = computed(() => {
  if (dailyTodos.value.length === 0) return 0
  return Math.round((completedDailyTodosCount.value / dailyTodos.value.length) * 100)
})

// Notifications State (5 Event-Typen)
const notifications = ref<any[]>([])
const loadingNotifications = ref(true)
const activeNotificationTab = ref<'all' | 'mentions' | 'projects'>('all')
const unreadCount = ref(0)

// Formatted Date matching MeisterTask screenshot (e.g. "Freitag, 18. September")
const formattedDate = computed(() => {
  const now = new Date()
  const loc = locale.value === 'sk' ? 'sk-SK' : (locale.value === 'en' ? 'en-US' : 'de-DE')
  return now.toLocaleDateString(loc, {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
})

// MeisterTask dynamic motivational greeting
const greetingPrefix = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 11) return t('dashboard.guten_morgen')
  if (hour >= 11 && hour < 14) return t('dashboard.guten_tag')
  if (hour >= 14 && hour < 18) return t('dashboard.guten_tag')
  return t('dashboard.guten_abend')
})

// New Folder Modal
const showNewFolderModal = ref(false)
const newFolderName = ref('')
const newFolderVisibility = ref('private')
const creatingFolder = ref(false)
const folderModalError = ref('')

// Edit Folder Modal
const showEditFolderModal = ref(false)
const editFolderId = ref('')
const editFolderName = ref('')
const editFolderIcon = ref('📁')
const editFolderVisibility = ref('private')
const savingFolder = ref(false)
const editFolderError = ref('')

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

const totalProjects = computed(() => {
  if (isFreeUser.value) return projects.value.length
  return folders.value.reduce((acc: number, f: any) => acc + (f.project_count || 0), 0)
})

const filteredFolders = computed(() => folders.value)

const filteredProjects = computed(() => projects.value)

// New Project Modal (nur für Free-/Single-User, ohne Ordner-Ebene)
const showNewProjectModal = ref(false)
const newProjectTitle = ref('')
const creatingProject = ref(false)
const projectModalError = ref('')

const openNewProjectModal = () => {
  newProjectTitle.value = ''
  projectModalError.value = ''
  showNewProjectModal.value = true
}

const createProject = async () => {
  projectModalError.value = ''
  if (!newProjectTitle.value.trim()) {
    projectModalError.value = t('dashboard.projekttitel_erforderlich')
    return
  }
  creatingProject.value = true
  try {
    await $fetch('/api/projects', {
      method: 'POST',
      headers: authHeaders(),
      body: { title: newProjectTitle.value.trim() }
    })
    showNewProjectModal.value = false
    newProjectTitle.value = ''
    await loadProjects()
  } catch (err: any) {
    projectModalError.value = err.data?.statusMessage || t('dashboard.projekt_konnte_nicht_erstellt_werden')
  } finally {
    creatingProject.value = false
  }
}

const filteredTasks = computed(() => tasks.value)

const openNewFolderModal = () => {
  newFolderName.value = ''
  newFolderVisibility.value = 'private'
  folderModalError.value = ''
  showNewFolderModal.value = true
}

const editFolderCompanyId = ref<string | null>(null)
const editFolderCompanyName = ref<string | null>(null)

const editFolderOwnerId = ref<string | null>(null)
const editFolderProjectCount = ref<number>(0)

const openEditFolderModal = (folder: any) => {
  editFolderId.value = folder.id
  editFolderName.value = folder.name
  editFolderIcon.value = folder.icon || '📁'
  editFolderVisibility.value = folder.visibility || 'private'
  editFolderCompanyId.value = folder.company_id || null
  editFolderCompanyName.value = folder.company_name || null
  editFolderOwnerId.value = folder.owner_id || null
  editFolderProjectCount.value = folder.project_count || 0
  editFolderError.value = ''
  showEditFolderModal.value = true
}

// Delete Folder & Project State & Handlers
const showDeleteFolderModal = ref(false)
const folderToDelete = ref<any>(null)
const deletingFolder = ref(false)
const deleteFolderError = ref('')

const openDeleteFolderModal = (folder: any) => {
  folderToDelete.value = folder
  deleteFolderError.value = ''
  showDeleteFolderModal.value = true
}

const confirmDeleteFolder = async () => {
  if (!folderToDelete.value?.id) return
  deletingFolder.value = true
  deleteFolderError.value = ''
  try {
    await $fetch(`/api/folders/${folderToDelete.value.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    showDeleteFolderModal.value = false
    folderToDelete.value = null
    await loadFolders()
  } catch (err: any) {
    deleteFolderError.value = err.data?.statusMessage || err.message || 'Fehler beim Löschen des Ordners'
  } finally {
    deletingFolder.value = false
  }
}

const showDeleteProjectModal = ref(false)
const projectToDelete = ref<any>(null)
const deletingProject = ref(false)
const deleteProjectError = ref('')

const openDeleteProjectModal = (project: any) => {
  projectToDelete.value = project
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
    await loadProjects()
  } catch (err: any) {
    deleteProjectError.value = err.data?.statusMessage || err.message || 'Fehler beim Löschen des Projekts'
  } finally {
    deletingProject.value = false
  }
}

const loadFolders = async () => {
  loadingFolders.value = true
  try {
    const res = await $fetch<{ folders: any[] }>('/api/folders', {
      headers: authHeaders()
    })
    folders.value = res.folders || []
  } catch (err: any) {
    if (err.statusCode === 401) {
      navigateTo('/login')
    }
  } finally {
    loadingFolders.value = false
  }
}

const loadProjects = async () => {
  loadingFolders.value = true
  try {
    const res = await $fetch<{ projects: any[] }>('/api/projects', {
      headers: authHeaders()
    })
    projects.value = res.projects || []
  } catch (err: any) {
    if (err.statusCode === 401) {
      navigateTo('/login')
    }
  } finally {
    loadingFolders.value = false
  }
}

const loadTasks = async () => {
  loadingTasks.value = true
  try {
    const res = await $fetch<{ tasks: any[] }>('/api/tasks', {
      headers: authHeaders()
    })
    tasks.value = res.tasks || []
  } catch (err: any) {
    tasks.value = []
  } finally {
    loadingTasks.value = false
  }
}

// Daily Todos Methods ("Mein Tag" mit automatischem Rollover)
const loadDailyTodos = async () => {
  loadingDailyTodos.value = true
  try {
    const res = await $fetch<{ todos: any[], availableProjects: any[] }>('/api/daily-todos', {
      headers: authHeaders()
    })
    dailyTodos.value = res.todos || []
    availableProjects.value = res.availableProjects || []
  } catch (err) {
    dailyTodos.value = []
  } finally {
    loadingDailyTodos.value = false
  }
}

const createDailyTodo = async () => {
  const title = newDailyTodoTitle.value.trim()
  if (!title) return
  creatingDailyTodo.value = true
  try {
    const res = await $fetch<{ success: boolean, todo: any }>('/api/daily-todos', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        title,
        project_id: newDailyTodoProjectId.value || null
      }
    })
    if (res.todo) {
      dailyTodos.value.unshift(res.todo)
    }
    newDailyTodoTitle.value = ''
  } catch (err: any) {
    alert(err.data?.statusMessage || t('dashboard.tagestodo_konnte_nicht_erstellt_wer'))
  } finally {
    creatingDailyTodo.value = false
  }
}

const toggleDailyTodo = async (todo: any) => {
  const previousState = todo.is_completed
  todo.is_completed = !previousState
  try {
    await $fetch(`/api/daily-todos/${todo.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        is_completed: todo.is_completed
      }
    })
    dailyTodos.value.sort((a: any, b: any) => Number(a.is_completed) - Number(b.is_completed))
  } catch (err) {
    todo.is_completed = previousState
  }
}

const deleteDailyTodo = async (todoId: string) => {
  const idx = dailyTodos.value.findIndex((t: any) => t.id === todoId)
  if (idx === -1) return
  const [removed] = dailyTodos.value.splice(idx, 1)
  try {
    await $fetch(`/api/daily-todos/${todoId}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
  } catch (err) {
    dailyTodos.value.splice(idx, 0, removed)
  }
}

// Notifications Methods (5 Event-Typen)
const loadNotifications = async () => {
  loadingNotifications.value = true
  try {
    const res = await $fetch<{ notifications: any[], unreadCount: number }>('/api/notifications', {
      headers: authHeaders()
    })
    notifications.value = res.notifications || []
    unreadCount.value = res.unreadCount || 0
  } catch (err) {
    notifications.value = []
    unreadCount.value = 0
  } finally {
    loadingNotifications.value = false
  }
}

const markNotificationRead = async (notif: any) => {
  if (notif.is_read) return
  notif.is_read = true
  if (unreadCount.value > 0) unreadCount.value--
  try {
    await $fetch(`/api/notifications/${notif.id}/read`, {
      method: 'POST',
      headers: authHeaders()
    })
  } catch (err) {
    // Ignore error
  }
}

const markAllNotificationsRead = async () => {
  for (const n of notifications.value) {
    n.is_read = true
  }
  unreadCount.value = 0
  try {
    await $fetch('/api/notifications/read-all', {
      method: 'POST',
      headers: authHeaders()
    })
  } catch (err) {
    // Ignore error
  }
}

const filteredNotifications = computed(() => {
  if (activeNotificationTab.value === 'mentions') {
    return notifications.value.filter((n: any) => n.type === 'new_comment' || n.type === 'invitation')
  }
  if (activeNotificationTab.value === 'projects') {
    return notifications.value.filter((n: any) => n.type === 'due_soon' || n.type === 'task_updated' || n.type === 'budget_exceeded')
  }
  return notifications.value
})

const formatRelativeTime = (dateStr: string) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  const now = new Date()
  const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000)
  if (diffSec < 60) return t('dashboard.gerade_eben')
  if (diffSec < 3600) return t('dashboard.vor_min', { min: Math.floor(diffSec / 60) })
  if (diffSec < 86400) return t('dashboard.vor_std', { std: Math.floor(diffSec / 3600) })
  const loc = locale.value === 'sk' ? 'sk-SK' : (locale.value === 'en' ? 'en-US' : 'de-CH')
  return date.toLocaleDateString(loc, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const createFolder = async () => {
  folderModalError.value = ''
  creatingFolder.value = true
  try {
    await $fetch('/api/folders', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        name: newFolderName.value,
        visibility: newFolderVisibility.value
      }
    })
    showNewFolderModal.value = false
    newFolderName.value = ''
    newFolderVisibility.value = 'private'
    await loadFolders()
  } catch (err: any) {
    folderModalError.value = err.data?.statusMessage || t('dashboard.ordner_konnte_nicht_erstellt_werden')
  } finally {
    creatingFolder.value = false
  }
}

const updateFolder = async () => {
  editFolderError.value = ''
  savingFolder.value = true
  try {
    await $fetch(`/api/folders/${editFolderId.value}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        name: editFolderName.value,
        icon: editFolderIcon.value,
        visibility: editFolderVisibility.value
      }
    })
    showEditFolderModal.value = false
    await loadFolders()
  } catch (err: any) {
    editFolderError.value = err.data?.statusMessage || t('dashboard.ordner_konnte_nicht_aktualisiert_we')
  } finally {
    savingFolder.value = false
  }
}

const onGlobalTimeEntrySaved = async () => {
  await Promise.all([loadTasks(), loadNotifications()])
}

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (!user.value) {
    navigateTo('/login')
    return
  }
  await Promise.all([
    isFreeUser.value ? loadProjects() : loadFolders(),
    loadTasks(),
    loadDailyTodos(),
    loadNotifications()
  ])
  // Deep-Link: /dashboard?new=folder bzw. ?new=project öffnet das passende Modal
  const newParam = route.query.new
  if (newParam === 'folder' && !isFreeUser.value) {
    openNewFolderModal()
  } else if (newParam === 'project' && isFreeUser.value) {
    openNewProjectModal()
  }
  if (import.meta.client) {
    window.addEventListener('taskster-time-entry-saved', onGlobalTimeEntrySaved)
  }
})

onUnmounted(() => {
  if (import.meta.client) {
    window.removeEventListener('taskster-time-entry-saved', onGlobalTimeEntrySaved)
  }
})
</script>

