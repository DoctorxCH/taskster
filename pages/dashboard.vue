<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
    <!-- MeisterTask-Style Hero Section (Centered Date, Greeting, and Floating Search) -->
    <div class="flex flex-col items-center justify-center text-center mb-10 select-none">
      <!-- Formatted German Date in Liquid Glass Pill -->
      <div class="inline-flex items-center space-x-2 text-xs sm:text-sm font-bold tracking-wide text-slate-900 mb-3 px-4 py-1.5 rounded-full liquid_glass_pill shadow-md">
        <span>📅</span>
        <span>{{ formattedDate }}</span>
      </div>

      <!-- Personalized MeisterTask Motivational Greeting with subtle glass backing for 100% legibility -->
      <div class="px-6 py-2 rounded-3xl liquid_glass_pill mb-2 shadow-lg max-w-2xl">
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight flex items-center justify-center flex-wrap gap-2.5">
          <span>{{ greetingPrefix }}, {{ user?.name || 'Martin' }}</span>
          <span class="inline-block animate-bounce text-xl sm:text-3xl">🫡</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-700 font-semibold mt-1">
          <span v-if="user?.company_name" class="font-extrabold text-cyan-800">{{ user.company_name }}</span>
          <span v-else>Privater Workspace</span>
          – Deine aktuellen Aufgaben, Projektordner und Meilensteine im Überblick.
        </p>
      </div>

      <!-- Centered Floating Search Pill (MeisterTask Style) -->
      <div class="w-full max-w-xl mt-6 relative">
        <div class="relative flex items-center">
          <span class="absolute left-4 text-slate-400 text-sm">🔍</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Suchen Sie nach Aufgaben, Projekten und Ordnern..."
            class="w-full pl-11 pr-20 py-3 rounded-full bg-white/95 hover:bg-white focus:bg-white text-xs sm:text-sm text-slate-800 placeholder-slate-400 shadow-2xl border border-white/60 focus:outline-none focus:ring-4 focus:ring-cyan-500/30 transition-all backdrop-blur-md"
          />
          <div class="absolute right-3.5 hidden sm:flex items-center space-x-1">
            <kbd class="px-2 py-0.5 text-[10px] font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded-md">Strg</kbd>
            <kbd class="px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded-md">K</kbd>
          </div>
        </div>
      </div>
    </div>

    <!-- Free-Plan Info Alert if applicable -->
    <div
      v-if="!user?.is_pro && !user?.company_id && !user?.is_superadmin"
      class="mb-8 p-4 rounded-3xl liquid_glass border border-amber-300/50 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
    >
      <div class="flex items-center space-x-3">
        <span class="text-2xl">⚡</span>
        <div>
          <h4 class="text-xs font-black text-amber-900">Taskster Free Plan aktiv</h4>
          <p class="text-[11px] text-slate-700 font-medium mt-0.5">
            Maximal 1 Projektordner, max. in 3 Projekten gleichzeitig mitarbeiten.
          </p>
        </div>
      </div>
      <NuxtLink
        to="/settings"
        class="taskster_button px-6 text-xs h-[42px] rounded-lg"
      >
        Auf PRO upgraden
      </NuxtLink>
    </div>

    <!-- Main MeisterTask 2-Column Dashboard Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- LEFT / CENTER COLUMN: Aufgaben & Projekte (8 Cols on LG) -->
      <div class="lg:col-span-8 space-y-6">
        <!-- WIDGET 1: Aufgaben & Tages-Todos (Daily Focus & MeisterTask Overview) -->
        <section class="liquid_glass rounded-3xl p-6 sm:p-7 shadow-xl transition-all">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-200/70 mb-5 gap-3">
            <div class="flex items-center space-x-3">
              <span class="text-xl">📋</span>
              <h2 class="text-base font-black text-slate-900 tracking-tight">Aufgaben</h2>
              
              <!-- Tab Switcher: Mein Tag vs. Projekt-Aufgaben -->
              <div class="flex items-center p-1 bg-white/70 border border-slate-200/80 rounded-xl shadow-xs">
                <button
                  type="button"
                  @click="activeTaskTab = 'daily'"
                  class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                  :class="activeTaskTab === 'daily' ? 'bg-[#00A3C4] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  <span>☀️ Mein Tag</span>
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
                  class="flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                  :class="activeTaskTab === 'assigned' ? 'bg-[#00A3C4] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                >
                  <span>📁 Projekte</span>
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
                @click="activeTaskTab === 'daily' ? loadDailyTodos() : loadTasks()"
                class="p-1.5 rounded-xl hover:bg-white/80 text-slate-500 hover:text-slate-800 transition text-xs cursor-pointer ml-auto"
                title="Aktualisieren"
              >
                🔄
              </button>
            </div>
          </div>

          <!-- TAB 1: MEIN TAG (Tages-Todos mit automatischem Rollover) -->
          <div v-if="activeTaskTab === 'daily'" class="space-y-4">
            <!-- Quick Add Bar for Today -->
            <form @submit.prevent="createDailyTodo" class="p-2.5 rounded-2xl bg-white/80 border border-white/90 shadow-xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
              <input
                v-model="newDailyTodoTitle"
                type="text"
                placeholder="+ Was steht heute an? (Todo eingeben & Enter drücken)..."
                class="flex-1 px-3.5 py-2 bg-white/90 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:border-[#00A3C4] shadow-xs"
              />

              <div class="flex items-center space-x-2">
                <select
                  v-model="newDailyTodoProjectId"
                  class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:border-[#00A3C4] shadow-xs shrink-0 max-w-[190px] truncate"
                >
                  <option value="">🏷️ Ohne Projekt (Persönlich)</option>
                  <option v-for="p in availableProjects" :key="p.id" :value="p.id">
                    {{ p.folder_icon || '📁' }} {{ p.title }}
                  </option>
                </select>

                <button
                  type="submit"
                  :disabled="creatingDailyTodo || !newDailyTodoTitle.trim()"
                  class="taskster_button px-4 text-xs h-[38px] rounded-lg shrink-0"
                >
                  <span>{{ creatingDailyTodo ? '...' : '+ Hinzufügen' }}</span>
                </button>
              </div>
            </form>

            <!-- Progress Bar for Today -->
            <div v-if="dailyTodos.length > 0" class="px-3 py-2 rounded-xl bg-white/40 border border-white/60 flex items-center justify-between text-[11px] text-slate-600 font-semibold gap-3">
              <div class="flex items-center space-x-2 min-w-0">
                <span>🎯 Heute erledigt:</span>
                <span class="text-slate-900 font-bold">{{ completedDailyTodosCount }} von {{ dailyTodos.length }}</span>
                <span class="text-slate-400 font-normal">({{ completionPercentage }}%)</span>
              </div>
              <div class="w-36 sm:w-48 bg-slate-200 rounded-full h-2 overflow-hidden shrink-0">
                <div
                  class="bg-gradient-to-r from-[#00A3C4] to-teal-500 h-2 rounded-full transition-all duration-500"
                  :style="{ width: completionPercentage + '%' }"
                ></div>
              </div>
            </div>

            <!-- Loading Daily Todos -->
            <div v-if="loadingDailyTodos" class="py-10 text-center text-xs text-slate-500 font-medium">
              Lade Tages-Todos...
            </div>

            <!-- Empty Daily Todos State -->
            <div
              v-else-if="dailyTodos.length === 0"
              class="py-10 px-4 text-center flex flex-col items-center justify-center border border-dashed border-slate-300 rounded-2xl bg-white/50"
            >
              <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-700 flex items-center justify-center text-2xl mb-3 shadow-xs border border-amber-200">
                ☀️
              </div>
              <h3 class="text-sm font-bold text-slate-900">Starte deinen Tag mit klarem Fokus</h3>
              <p class="text-xs text-slate-600 mt-1 max-w-md">
                Tages-Todos gelten immer für den heutigen Tag. Bleibt ein Todo am Abend unerledigt, wandert es automatisch als Übertrag auf den nächsten Tag!
              </p>
            </div>

            <!-- Daily Todos List -->
            <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="todo in dailyTodos"
                :key="todo.id"
                class="group/todo flex items-center justify-between p-3 rounded-2xl border transition-all duration-200"
                :class="todo.is_completed ? 'bg-white/40 border-slate-200/60 opacity-60' : 'bg-white/80 hover:bg-white border-white/80 hover:border-cyan-300 shadow-xs hover:shadow-sm'"
              >
                <div class="flex items-center space-x-3 min-w-0">
                  <!-- Checkbox -->
                  <button
                    type="button"
                    @click="toggleDailyTodo(todo)"
                    class="w-5 h-5 rounded-lg border flex items-center justify-center transition shrink-0 cursor-pointer"
                    :class="todo.is_completed ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 bg-white hover:border-[#00A3C4]'"
                  >
                    <span v-if="todo.is_completed" class="text-xs font-black">✓</span>
                  </button>

                  <div class="min-w-0">
                    <p
                      class="text-xs sm:text-sm font-bold transition block truncate"
                      :class="todo.is_completed ? 'line-through text-slate-500' : 'text-slate-900'"
                    >
                      {{ todo.title }}
                    </p>

                    <!-- Badges: Rollover & Project -->
                    <div class="flex items-center space-x-2 text-[11px] mt-0.5 flex-wrap gap-y-1">
                      <!-- Rollover Warning Pill -->
                      <span
                        v-if="todo.rollover_count > 0 && !todo.is_completed"
                        class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-amber-100/90 text-amber-900 font-bold border border-amber-300 text-[10px]"
                        title="Automatisch vom Vortag übertragen, da noch nicht abgeschlossen"
                      >
                        <span>🔁</span>
                        <span>Übertrag {{ todo.rollover_count === 1 ? 'von gestern' : `von vor ${todo.rollover_count} Tagen` }}</span>
                      </span>

                      <!-- Project Link Pill (if assigned) -->
                      <NuxtLink
                        v-if="todo.project_id"
                        :to="`/projects/${todo.project_id}`"
                        class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-md bg-cyan-50 hover:bg-cyan-100 text-[#00A3C4] font-semibold border border-cyan-200 transition text-[10px]"
                        title="Zum Projekt öffnen"
                      >
                        <span>{{ todo.folder_icon || '📁' }}</span>
                        <span class="truncate max-w-[130px]">{{ todo.project_title }}</span>
                      </NuxtLink>

                      <span v-else class="text-[10px] text-slate-400">
                        Persönlich
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-1.5 shrink-0 ml-3">
                  <button
                    type="button"
                    @click="deleteDailyTodo(todo.id)"
                    class="opacity-0 group-hover/todo:opacity-100 p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition text-xs"
                    title="Tages-Todo löschen"
                  >
                    🗑️
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: PROJEKT-AUFGABEN (Zugewiesene Aufgaben) -->
          <div v-else class="space-y-4">
            <!-- Loading Tasks -->
            <div v-if="loadingTasks" class="py-10 text-center text-xs text-slate-500 font-medium">
              Lade offene Aufgaben...
            </div>

            <!-- Empty Tasks State -->
            <div
              v-else-if="filteredTasks.length === 0"
              class="py-10 px-4 text-center flex flex-col items-center justify-center"
            >
              <div class="w-12 h-12 rounded-2xl bg-cyan-500/15 text-cyan-800 flex items-center justify-center text-xl mb-3 shadow-xs border border-cyan-200">
                ✨
              </div>
              <h3 class="text-sm font-bold text-slate-900">Du hast keine anstehenden Aufgaben</h3>
              <p class="text-xs text-slate-600 mt-1 mb-5">
                Deine zugewiesenen Aufgaben aus den Projekten erscheinen hier.
              </p>
              <NuxtLink
                v-if="folders.length > 0"
                :to="`/folders/${folders[0]?.id}`"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg"
              >
                Aufgabe in Projekten anzeigen
              </NuxtLink>
              <button
                v-else
                @click="openNewFolderModal"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg"
              >
                + Ersten Ordner erstellen
              </button>
            </div>

            <!-- Tasks List -->
            <div v-else class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
              <div
                v-for="task in filteredTasks"
                :key="task.id"
                class="group/task flex items-center justify-between p-3.5 rounded-2xl border border-white/60 hover:border-cyan-300 bg-white/70 hover:bg-white shadow-xs hover:shadow-md transition-all"
              >
                <div class="flex items-center space-x-3 min-w-0">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#00A3C4] shrink-0"></span>
                  <div class="min-w-0">
                    <NuxtLink
                      :to="`/projects/${task.project_id}`"
                      class="text-xs sm:text-sm font-bold text-slate-900 group-hover/task:text-[#00A3C4] transition block truncate"
                    >
                      {{ task.title }}
                    </NuxtLink>
                    <div class="flex items-center space-x-2 text-[11px] text-slate-500 mt-0.5">
                      <span>{{ task.folder_icon || '📁' }} {{ task.folder_name }}</span>
                      <span>/</span>
                      <span class="font-medium text-slate-700">{{ task.project_title }}</span>
                      <span class="text-slate-300">•</span>
                      <span class="px-2 py-0.2 rounded-md bg-white border border-slate-200 text-slate-600 text-[10px] font-semibold">
                        {{ task.list_title }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-2 shrink-0 ml-3">
                  <!-- Live Stopwatch running badge on Dashboard -->
                  <div
                    v-if="stopwatchState.isRunning && stopwatchState.taskId === task.id"
                    class="flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-slate-950 text-white font-mono font-bold text-[10px] shadow-sm animate-in fade-in"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <span class="text-cyan-300">{{ formatSeconds(stopwatchState.elapsedSeconds) }}</span>
                    <button
                      type="button"
                      @click.stop="openStopModal"
                      class="ml-1 px-1.5 py-0.5 bg-rose-600 hover:bg-rose-500 text-white rounded font-black text-[9px] shadow-xs"
                      title="Stoppen & buchen"
                    >
                      ⏹️
                    </button>
                  </div>

                  <button
                    v-else
                    type="button"
                    @click.stop="startTaskTimer(task)"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-cyan-700 hover:bg-cyan-50 transition text-xs font-bold"
                    title="Stoppuhr auf diese Aufgabe starten"
                  >
                    ⏱️
                  </button>

                  <span
                    v-if="task.due_date"
                    class="text-[10px] font-bold px-2 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200"
                  >
                    📅 {{ task.due_date }}
                  </span>
                  <NuxtLink
                    :to="`/projects/${task.project_id}`"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-cyan-700 hover:bg-cyan-50 transition text-xs"
                    title="Projekt öffnen"
                  >
                    →
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- WIDGET 2: Projekte & Projektordner (MeisterTask Style) -->
        <section class="liquid_glass rounded-3xl p-6 sm:p-7 shadow-xl transition-all">
          <div class="flex items-center justify-between pb-4 border-b border-slate-200/70 mb-6">
            <div class="flex items-center space-x-3">
              <span class="text-xl">📁</span>
              <div>
                <h2 class="text-base font-black text-slate-900 tracking-tight">Projektordner & Initiativen</h2>
                <p class="text-[11px] text-slate-600 font-medium">Übergeordnete Bereiche für Teams und Projekte</p>
              </div>
            </div>

            <button
              @click="openNewFolderModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm"
            >
              <span>+ Neuer Ordner</span>
            </button>
          </div>

          <!-- Loading Folders -->
          <div v-if="loadingFolders" class="py-10 text-center text-xs text-slate-500 font-medium">
            Lade Projektordner...
          </div>

          <!-- Empty Folders State -->
          <div
            v-else-if="filteredFolders.length === 0"
            class="py-10 px-4 text-center flex flex-col items-center justify-center border border-dashed border-slate-300 rounded-2xl bg-white/50"
          >
            <div class="w-14 h-14 rounded-2xl bg-cyan-500/15 flex items-center justify-center text-2xl mb-3 shadow-xs border border-cyan-200">
              📁
            </div>
            <h3 class="text-sm font-bold text-slate-900">Keine Projektordner gefunden</h3>
            <p class="text-xs text-slate-600 mt-1 mb-5 max-w-sm">
              {{ searchQuery ? 'Keine Treffer für deine Suche.' : 'Erstelle deinen ersten Ordner, um Projekte und Teams zu strukturieren.' }}
            </p>
            <button
              @click="openNewFolderModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              + Jetzt Ordner anlegen
            </button>
          </div>

          <!-- Folders Grid (MeisterTask Clean Liquid Glass Tiles) -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="folder in filteredFolders"
              :key="folder.id"
              class="group/card liquid_glass_card hover:border-[#00A3C4] rounded-2xl p-5 transition-all duration-200 flex flex-col justify-between shadow-sm hover:shadow-lg"
            >
              <div>
                <div class="flex items-start justify-between mb-3">
                  <div class="w-11 h-11 rounded-xl bg-cyan-500/15 border border-cyan-200 flex items-center justify-center text-2xl group-hover/card:scale-105 transition-transform shadow-xs">
                    {{ folder.icon || '📁' }}
                  </div>
                  <div class="flex items-center space-x-1.5">
                    <span
                      class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
                      :class="folder.visibility === 'company' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-slate-100 border-slate-200 text-slate-700'"
                    >
                      {{ folder.visibility === 'company' ? '🏢 Unternehmen' : '🔒 Privat' }}
                    </span>
                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-white/80 border border-slate-200 text-slate-700">
                      {{ folder.project_count }} {{ folder.project_count === 1 ? 'Projekt' : 'Projekte' }}
                    </span>
                  </div>
                </div>

                <h3 class="text-sm font-black text-slate-900 group-hover/card:text-[#00A3C4] transition mb-1">
                  {{ folder.name }}
                </h3>
                <p class="text-xs text-slate-600 flex items-center space-x-1">
                  <span>Inhaber:</span>
                  <span class="text-slate-800 font-bold">{{ folder.owner_name }}</span>
                  <span v-if="user?.id === folder.owner_id" class="text-[10px] px-1.5 py-0.5 rounded-full bg-cyan-100 text-cyan-800 font-bold ml-1">
                    Du
                  </span>
                </p>
                <p v-if="folder.company_name" class="text-[11px] text-teal-800 mt-1 font-semibold">
                  🏢 {{ folder.company_name }}
                </p>
              </div>

              <div class="mt-5 pt-3.5 border-t border-slate-200/80 flex items-center justify-between">
                <span class="text-[11px] text-slate-500 font-semibold">
                  {{ new Date(folder.created_at).toLocaleDateString('de-CH') }}
                </span>
                <div class="flex items-center space-x-2">
                  <button
                    v-if="user?.id === folder.owner_id || user?.is_superadmin"
                    @click="openEditFolderModal(folder)"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white/80 hover:bg-white border border-slate-200 text-slate-700 transition cursor-pointer"
                    title="Projektordner anpassen (Name & Icon)"
                  >
                    ✏️
                  </button>
                  <NuxtLink
                    :to="`/folders/${folder.id}`"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold bg-[#00A3C4] hover:bg-[#008ba8] text-white shadow-xs transition"
                  >
                    Öffnen →
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- RIGHT COLUMN: Benachrichtigungen & Schnellzugriff (4 Cols on LG) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- WIDGET 3: Benachrichtigungen / Activity Feed (5 Event-Typen) -->
        <section class="liquid_glass rounded-3xl p-6 sm:p-7 shadow-xl transition-all">
          <div class="flex items-center justify-between pb-4 border-b border-slate-200/70 mb-4">
            <div class="flex items-center space-x-2.5">
              <span class="text-xl">🔔</span>
              <h2 class="text-base font-black text-slate-900 tracking-tight">Benachrichtigungen</h2>
            </div>
            <div class="flex items-center space-x-2">
              <span
                class="text-xs font-bold px-2 py-0.5 rounded-full transition"
                :class="unreadCount > 0 ? 'bg-[#00A3C4] text-white shadow-xs animate-pulse' : 'bg-slate-200 text-slate-700'"
              >
                {{ unreadCount }}
              </span>
              <button
                type="button"
                @click="loadNotifications"
                class="p-1 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-white/80 transition text-xs cursor-pointer"
                title="Aktualisieren"
              >
                🔄
              </button>
            </div>
          </div>

          <!-- Notification Filter Tabs -->
          <div class="flex items-center space-x-3 text-xs font-bold border-b border-slate-200/70 pb-2 mb-4 text-slate-500">
            <button
              @click="activeNotificationTab = 'all'"
              class="pb-1 transition border-b-2 cursor-pointer"
              :class="activeNotificationTab === 'all' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent hover:text-slate-800'"
            >
              Alle
            </button>
            <button
              @click="activeNotificationTab = 'mentions'"
              class="pb-1 transition border-b-2 cursor-pointer"
              :class="activeNotificationTab === 'mentions' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent hover:text-slate-800'"
            >
              Kommentare & Einladungen
            </button>
            <button
              @click="activeNotificationTab = 'projects'"
              class="pb-1 transition border-b-2 cursor-pointer"
              :class="activeNotificationTab === 'projects' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent hover:text-slate-800'"
            >
              Fälligkeiten & Budget
            </button>
          </div>

          <!-- Loading State -->
          <div v-if="loadingNotifications" class="py-8 text-center text-xs text-slate-500 font-medium">
            Lade Benachrichtigungen...
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredNotifications.length === 0" class="py-8 text-center">
            <span class="text-3xl block mb-2">🎉</span>
            <p class="text-xs text-slate-700 font-bold mb-1">
              Keine ungelesenen Benachrichtigungen
            </p>
            <p class="text-[11px] text-slate-500">
              Du bist in diesem Bereich komplett auf dem neuesten Stand!
            </p>
          </div>

          <!-- Notification Feed -->
          <div v-else class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
            <div
              v-for="notif in filteredNotifications"
              :key="notif.id"
              class="p-3 rounded-2xl border transition-all text-xs flex items-start justify-between gap-2.5"
              :class="notif.is_read ? 'bg-white/40 border-white/60 text-slate-600' : 'bg-white border-cyan-300 shadow-xs text-slate-900 ring-1 ring-cyan-500/10'"
            >
              <div class="flex items-start space-x-2.5 min-w-0">
                <span class="text-base shrink-0 mt-0.5">
                  {{ notif.type === 'due_soon' ? '⏰' : (notif.type === 'new_comment' ? '💬' : (notif.type === 'task_updated' ? '✏️' : (notif.type === 'invitation' ? '📩' : '💰'))) }}
                </span>
                <div class="min-w-0">
                  <div class="flex items-center space-x-2">
                    <p class="font-bold truncate" :class="notif.is_read ? 'text-slate-700' : 'text-slate-900'">
                      {{ notif.title }}
                    </p>
                    <span v-if="!notif.is_read" class="w-2 h-2 rounded-full bg-[#00A3C4] shrink-0 animate-pulse"></span>
                  </div>
                  <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">
                    {{ notif.message }}
                  </p>
                  <div class="flex items-center space-x-3 mt-1.5 text-[10px] text-slate-400 font-medium">
                    <span>{{ formatRelativeTime(notif.created_at) }}</span>
                    <NuxtLink
                      v-if="notif.project_id"
                      :to="`/projects/${notif.project_id}`"
                      class="text-[#00A3C4] hover:underline font-bold"
                    >
                      Zum Projekt →
                    </NuxtLink>
                    <NuxtLink
                      v-else-if="notif.reference_type === 'folder' && notif.reference_id"
                      :to="`/folders/${notif.reference_id}`"
                      class="text-[#00A3C4] hover:underline font-bold"
                    >
                      Zum Ordner →
                    </NuxtLink>
                  </div>
                </div>
              </div>

              <button
                v-if="!notif.is_read"
                @click="markNotificationRead(notif)"
                type="button"
                class="shrink-0 p-1 text-slate-400 hover:text-cyan-700 hover:bg-cyan-50 rounded-lg transition text-xs font-black cursor-pointer"
                title="Als gelesen markieren"
              >
                ✓
              </button>
            </div>
          </div>

          <!-- Mark all as read footer -->
          <div v-if="unreadCount > 0" class="pt-4 border-t border-slate-200/80 mt-4 text-center">
            <button
              type="button"
              @click="markAllNotificationsRead"
              class="taskster_button_light px-6 text-xs h-[38px] rounded-lg mx-auto"
            >
              Alle als gelesen markieren
            </button>
          </div>
        </section>

        <!-- WIDGET 4: Quick Summary / Stats (MeisterTask Style Compact Card) -->
        <section class="liquid_glass rounded-3xl p-6 shadow-xl transition-all">
          <h3 class="text-xs font-black text-slate-600 uppercase tracking-wider mb-4">
            System & Workspace Status
          </h3>

          <div class="space-y-3.5">
            <div class="flex items-center justify-between p-3 rounded-xl liquid_glass_pill">
              <span class="text-xs text-slate-700 font-bold">Projektordner</span>
              <span class="text-sm font-black text-slate-900">{{ folders.length }}</span>
            </div>

            <div class="flex items-center justify-between p-3 rounded-xl liquid_glass_pill">
              <span class="text-xs text-slate-700 font-bold">Aktive Projekte</span>
              <span class="text-sm font-black text-[#00A3C4]">{{ totalProjects }}</span>
            </div>

            <div class="flex items-center justify-between p-3 rounded-xl liquid_glass_pill">
              <span class="text-xs text-slate-700 font-bold">Offene Aufgaben</span>
              <span class="text-sm font-black text-slate-900">{{ tasks.length }}</span>
            </div>

            <div class="p-3 rounded-xl bg-emerald-100/80 border border-emerald-300 flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-emerald-950">Zero-Trust Pipeline</span>
              </div>
              <span class="text-[10px] font-bold text-emerald-800">Aktiv</span>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Modal: New Folder -->
    <div v-if="showNewFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-white/80">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">Neuen Projektordner anlegen</h3>
          <button @click="showNewFolderModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">✕</button>
        </div>
        <p class="text-xs text-slate-600 font-medium mb-5">
          Projektordner bilden die oberste Organisationsebene für Bauträger, Standorte oder Großvorhaben.
        </p>

        <div v-if="folderModalError" class="mb-4 p-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-900 text-xs font-bold">
          {{ folderModalError }}
        </div>

        <form @submit.prevent="createFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Name des Projektordners</label>
            <input
              v-model="newFolderName"
              type="text"
              required
              placeholder="z.B. FTTH Glasfaserausbau Region Nord"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs transition"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1.5">Icon auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-36 overflow-y-auto p-2.5 bg-white/60 rounded-2xl border border-white/80">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="newFolderIcon = item.icon"
                class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition border cursor-pointer"
                :class="newFolderIcon === item.icon ? 'bg-cyan-100 border-cyan-500 ring-2 ring-cyan-500/40 scale-105' : 'border-slate-200 bg-white/80 hover:bg-white'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-600 mt-1 font-medium">Ausgewählt: <span class="text-slate-900 text-sm font-bold mr-1">{{ newFolderIcon }}</span></p>
          </div>

          <!-- Sichtbarkeit im Unternehmen (Default: Privat) -->
          <div v-if="user?.company_id || user?.is_superadmin" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
            <label class="block text-xs font-bold text-slate-800">Sichtbarkeit des Ordners</label>
            <div class="grid grid-cols-2 gap-2">
              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer transition text-xs font-semibold"
                :class="newFolderVisibility === 'private' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
              >
                <input type="radio" value="private" v-model="newFolderVisibility" class="sr-only" />
                <span>🔒 Privat (Standard)</span>
              </label>
              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer transition text-xs font-semibold"
                :class="newFolderVisibility === 'company' ? 'bg-white border-[#00A3C4] text-[#00A3C4] ring-1 ring-[#00A3C4]' : 'bg-white/60 border-slate-200 text-slate-700'"
              >
                <input type="radio" value="company" v-model="newFolderVisibility" class="sr-only" />
                <span>🏢 Unternehmen</span>
              </label>
            </div>
            <p class="text-[11px] text-slate-500">
              {{ newFolderVisibility === 'private' ? 'Privater Ordner. Nur für dich und gezielt eingeladene Mitglieder sichtbar (auch Admins sehen diesen Ordner nicht automatisch).' : `Für alle Mitglieder des Unternehmens (${user?.company_name || 'Firma'}) sichtbar.` }}
            </p>
          </div>
          <div v-else class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs text-amber-900">
            <span class="font-bold">💡 Einzelnutzer-Konto:</span> Dieser Ordner ist standardmäßig privat. Du kannst ihn im Ordner selbst über <strong>"👥 Ordner teilen"</strong> für Kollegen freigeben.
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showNewFolderModal = false; folderModalError = ''"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="creatingFolder || !newFolderName.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ creatingFolder ? 'Erstelle...' : 'Ordner erstellen' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Folder (Owner only) -->
    <div v-if="showEditFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-white/80">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">Projektordner anpassen</h3>
          <button @click="showEditFolderModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">✕</button>
        </div>
        <p class="text-xs text-slate-600 font-medium mb-5">
          Passe den Namen und das Erkennungs-Icon dieses Projektordners an.
        </p>

        <div v-if="editFolderError" class="mb-4 p-3 rounded-xl bg-rose-100 border border-rose-300 text-rose-900 text-xs font-bold">
          {{ editFolderError }}
        </div>

        <form @submit.prevent="updateFolder" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Name des Projektordners</label>
            <input
              v-model="editFolderName"
              type="text"
              required
              placeholder="z.B. Peters Privates Renovationsprojekt"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs transition"
            />
          </div>

          <!-- Icon Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1.5">Icon aus Liste auswählen</label>
            <div class="grid grid-cols-7 gap-2 max-h-40 overflow-y-auto p-2.5 bg-white/60 rounded-2xl border border-white/80">
              <button
                v-for="item in availableFolderIcons"
                :key="item.icon"
                type="button"
                @click="editFolderIcon = item.icon"
                class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition border cursor-pointer"
                :class="editFolderIcon === item.icon ? 'bg-cyan-100 border-cyan-500 ring-2 ring-cyan-500/40 scale-105' : 'border-slate-200 bg-white/80 hover:bg-white'"
                :title="item.label"
              >
                {{ item.icon }}
              </button>
            </div>
            <p class="text-[11px] text-slate-600 mt-1 font-medium">Ausgewähltes Icon: <span class="text-slate-900 text-sm font-bold mr-1">{{ editFolderIcon }}</span></p>
          </div>

          <!-- Sichtbarkeit im Unternehmen (Default: Privat) -->
          <div v-if="user?.company_id || editFolderCompanyId || user?.is_superadmin" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
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
              {{ editFolderVisibility === 'private' ? 'Privater Ordner. Nur für dich und gezielt eingeladene Mitglieder sichtbar.' : `Für alle Mitglieder des Unternehmens (${editFolderCompanyName || user?.company_name || 'Firma'}) sichtbar.` }}
            </p>
          </div>
          <div v-else class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs text-amber-900">
            <span class="font-bold">💡 Einzelnutzer-Konto:</span> Dieser Ordner ist standardmäßig privat. Du kannst ihn im Ordner selbst über <strong>"👥 Ordner teilen"</strong> für Kollegen freigeben.
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showEditFolderModal = false; editFolderError = ''"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingFolder || !editFolderName.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ savingFolder ? 'Speichern...' : 'Änderungen speichern' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, authHeaders } = useAuth()
const { state: stopwatchState, startTimer, openStopModal, formatSeconds } = useStopwatch()

const startTaskTimer = (task: any) => {
  if (!task) return
  startTimer({
    projectId: task.project_id,
    projectTitle: task.project_title || 'Projekt',
    projectCurrency: 'CHF',
    taskId: task.id,
    taskTitle: task.title
  })
}

const folders = ref<any[]>([])
const tasks = ref<any[]>([])
const loadingFolders = ref(true)
const loadingTasks = ref(true)
const searchQuery = ref('')

// Tasks Tab Switcher: 'daily' (Mein Tag) vs 'assigned' (Projekt-Aufgaben)
const activeTaskTab = ref<'daily' | 'assigned'>('daily')

// Daily Todos State ("Mein Tag" mit Rollover)
const dailyTodos = ref<any[]>([])
const loadingDailyTodos = ref(true)
const newDailyTodoTitle = ref('')
const newDailyTodoProjectId = ref('')
const availableProjects = ref<any[]>([])
const creatingDailyTodo = ref(false)

const completedDailyTodosCount = computed(() => dailyTodos.value.filter(t => t.is_completed).length)
const uncompletedDailyTodosCount = computed(() => dailyTodos.value.filter(t => !t.is_completed).length)
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
  return now.toLocaleDateString('de-DE', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
})

// MeisterTask dynamic motivational greeting
const greetingPrefix = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 5 && hour < 11) return 'Guten Morgen'
  if (hour >= 11 && hour < 14) return 'Mahlzeit'
  if (hour >= 14 && hour < 18) return 'Sich anstrengen'
  return 'Guten Abend'
})

// New Folder Modal
const showNewFolderModal = ref(false)
const newFolderName = ref('')
const newFolderIcon = ref('📁')
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
  { icon: '⚡', label: 'Elektro & Handwerk' },
  { icon: '🌐', label: 'Netzwerk & LWL' },
  { icon: '🏢', label: 'Unternehmen & B2B' },
  { icon: '📊', label: 'Finanzen & Analyse' },
  { icon: '🛠️', label: 'Werkstatt & Service' },
  { icon: '🚀', label: 'Projekte & Launch' },
  { icon: '🚚', label: 'Logistik & Transport' },
  { icon: '🔒', label: 'Sicherheit & Audit' },
  // Privat & Freizeit
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
  return folders.value.reduce((acc, f) => acc + (f.project_count || 0), 0)
})

const filteredFolders = computed(() => {
  if (!searchQuery.value.trim()) return folders.value
  const q = searchQuery.value.toLowerCase()
  return folders.value.filter(f => f.name?.toLowerCase().includes(q) || f.owner_name?.toLowerCase().includes(q))
})

const filteredTasks = computed(() => {
  if (!searchQuery.value.trim()) return tasks.value
  const q = searchQuery.value.toLowerCase()
  return tasks.value.filter(t => 
    t.title?.toLowerCase().includes(q) ||
    t.project_title?.toLowerCase().includes(q) ||
    t.folder_name?.toLowerCase().includes(q)
  )
})

const openNewFolderModal = () => {
  newFolderName.value = ''
  newFolderIcon.value = '📁'
  newFolderVisibility.value = 'private'
  folderModalError.value = ''
  showNewFolderModal.value = true
}

const editFolderCompanyId = ref<string | null>(null)
const editFolderCompanyName = ref<string | null>(null)

const openEditFolderModal = (folder: any) => {
  editFolderId.value = folder.id
  editFolderName.value = folder.name
  editFolderIcon.value = folder.icon || '📁'
  editFolderVisibility.value = folder.visibility || 'private'
  editFolderCompanyId.value = folder.company_id || null
  editFolderCompanyName.value = folder.company_name || null
  editFolderError.value = ''
  showEditFolderModal.value = true
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

const loadTasks = async () => {
  loadingTasks.value = true
  try {
    const res = await $fetch<{ tasks: any[] }>('/api/tasks', {
      headers: authHeaders()
    })
    tasks.value = res.tasks || []
  } catch (err: any) {
    // Graceful fallback if no tasks yet
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
    alert(err.data?.statusMessage || 'Tages-Todo konnte nicht erstellt werden')
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
    // Re-sort: uncompleted at top, completed at bottom
    dailyTodos.value.sort((a, b) => Number(a.is_completed) - Number(b.is_completed))
  } catch (err) {
    todo.is_completed = previousState
  }
}

const deleteDailyTodo = async (todoId: string) => {
  const idx = dailyTodos.value.findIndex(t => t.id === todoId)
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
    return notifications.value.filter(n => n.type === 'new_comment' || n.type === 'invitation')
  }
  if (activeNotificationTab.value === 'projects') {
    return notifications.value.filter(n => n.type === 'due_soon' || n.type === 'task_updated' || n.type === 'budget_exceeded')
  }
  return notifications.value
})

const formatRelativeTime = (dateStr: string) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr
  const now = new Date()
  const diffSec = Math.floor((now.getTime() - date.getTime()) / 1000)
  if (diffSec < 60) return 'Gerade eben'
  if (diffSec < 3600) return `vor ${Math.floor(diffSec / 60)} Min.`
  if (diffSec < 86400) return `vor ${Math.floor(diffSec / 3600)} Std.`
  return date.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
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
        icon: newFolderIcon.value,
        visibility: newFolderVisibility.value
      }
    })
    showNewFolderModal.value = false
    newFolderName.value = ''
    newFolderIcon.value = '📁'
    newFolderVisibility.value = 'private'
    await loadFolders()
  } catch (err: any) {
    folderModalError.value = err.data?.statusMessage || 'Ordner konnte nicht erstellt werden'
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
    editFolderError.value = err.data?.statusMessage || 'Ordner konnte nicht aktualisiert werden'
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
  await Promise.all([loadFolders(), loadTasks(), loadDailyTodos(), loadNotifications()])
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
