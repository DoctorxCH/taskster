<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb in Liquid Glass Pill -->
    <div class="mb-6">
      <div class="inline-flex items-center space-x-2 text-xs text-slate-700 font-semibold px-4 py-2 rounded-2xl liquid_glass_pill">
        <NuxtLink to="/dashboard" class="hover:text-cyan-700 transition flex items-center space-x-1">
          <span>🏠</span>
          <span>Dashboard</span>
        </NuxtLink>
        <span class="text-slate-400">/</span>
        <NuxtLink :to="`/folders/${project?.folder_id}`" class="hover:text-cyan-700 transition flex items-center space-x-1">
          <span>📁</span>
          <span>{{ project?.folder_name || 'Ordner' }}</span>
        </NuxtLink>
        <span class="text-slate-400">/</span>
        <span class="text-slate-900 font-bold flex items-center space-x-1">
          <span>📋</span>
          <span>{{ project?.title || 'Projekt' }}</span>
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-700 font-bold text-xs liquid_glass rounded-3xl max-w-sm mx-auto">
      Lade Projektdaten...
    </div>

    <div v-else-if="project">
      <!-- Project Header (Liquid Glass Card) -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 mb-6 shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex flex-wrap items-center gap-2.5 mb-1.5">
              <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ project.title }}</h1>
              <span
                class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                :class="userRole === 'viewer' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-cyan-100 text-cyan-900 border border-cyan-300'"
              >
                {{ userRole }}
              </span>
              <span
                class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/90 text-slate-800 border border-slate-200"
              >
                Status: {{ project.status }}
              </span>
            </div>

            <p class="text-xs text-slate-600 flex flex-wrap items-center gap-x-3 gap-y-1 mb-2">
              <span>Ordner: <NuxtLink :to="`/folders/${project.folder_id}`" class="text-cyan-800 font-bold hover:underline">{{ project.folder_name }}</NuxtLink></span>
              <span v-if="project.company_name" class="text-teal-800 font-semibold">• {{ project.company_name }}</span>
            </p>

            <!-- Project-level custom fields display in header -->
            <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-2 pt-1">
              <span
                v-for="(val, key) in project.custom_data"
                :key="key"
                class="inline-flex items-center text-xs px-2.5 py-1 rounded-xl bg-white/80 border border-slate-200 text-slate-800 shadow-xs"
              >
                <span class="text-cyan-700 font-bold mr-1.5">{{ getFieldLabel(key) }}:</span>
                <span class="text-slate-900 font-bold">{{ val }}</span>
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center space-x-2">
            <button
              v-if="userRole !== 'viewer'"
              @click="openManageSectionsModal"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
              title="Abschnitte bearbeiten, umbenennen, per Drag & Drop sortieren"
            >
              <span>✏️</span>
              <span>Abschnitte bearbeiten</span>
            </button>
            <button
              v-if="userRole !== 'viewer'"
              @click="showNewListModal = true"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              <span>+ Neuer Abschnitt</span>
            </button>
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(lists[0]?.id)"
              :disabled="lists.length === 0"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>+ Aufgabe erfassen</span>
            </button>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-200/80 mt-6 -mb-6 sm:-mb-8 space-x-6 overflow-x-auto">
          <button
            @click="currentView = 'tasks'"
            class="py-3.5 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'tasks' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-600 hover:text-slate-900'"
          >
            <span>📋</span>
            <span>Aufgaben & Abschnitte ({{ totalTasks }})</span>
          </button>

          <button
            @click="currentView = 'journal'; loadJournals()"
            class="py-3.5 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'journal' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-600 hover:text-slate-900'"
          >
            <span>📝</span>
            <span>Aktivitätsjournal ({{ journalEntries.length }})</span>
          </button>

          <button
            @click="currentView = 'team'"
            class="py-3.5 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'team' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-600 hover:text-slate-900'"
          >
            <span>👥</span>
            <span>Team & Berechtigungen ({{ members.length + 1 }})</span>
          </button>

          <button
            v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin"
            @click="currentView = 'settings'; initSettingsTab()"
            class="py-3.5 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap cursor-pointer"
            :class="currentView === 'settings' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-600 hover:text-slate-900'"
          >
            <span>⚙️</span>
            <span>Projekt-Einstellungen</span>
          </button>
        </div>
      </div>

      <!-- VIEW 1: TASKS & ABSCHNITTE -->
      <div v-if="currentView === 'tasks'">
        <!-- View controls in Liquid Glass Pill Bar -->
        <div class="liquid_glass_pill rounded-2xl px-4 py-2.5 flex items-center justify-between mb-5 shadow-sm">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-bold text-slate-600">Ansicht:</span>
            <div class="bg-white/90 border border-slate-200/80 rounded-xl p-0.5 flex items-center space-x-1 shadow-xs">
              <button
                @click="taskViewMode = 'board'"
                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer"
                :class="taskViewMode === 'board' ? 'bg-cyan-50 text-cyan-800 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
              >
                <span>▦</span>
                <span>Kacheln (Board)</span>
              </button>
              <button
                @click="taskViewMode = 'table'"
                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1 cursor-pointer"
                :class="taskViewMode === 'table' ? 'bg-cyan-50 text-cyan-800 font-extrabold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
              >
                <span>☰</span>
                <span>Liste</span>
              </button>
            </div>
          </div>

          <span class="text-xs text-slate-600 font-bold">
            {{ lists.length }} Abschnitte • {{ totalTasks }} Aufgaben
          </span>
        </div>

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
        <div v-else-if="taskViewMode === 'board'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
          <div
            v-for="(list, listIdx) in lists"
            :key="list.id"
            class="liquid_glass rounded-3xl p-4 flex flex-col transition-all duration-150 shadow-lg"
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
              class="flex items-center justify-between mb-3 pb-3 border-b border-slate-200 select-none group/hdr"
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
                <span class="w-3 h-3 rounded-full" :class="[
                  listIdx % 4 === 0 ? 'bg-[#00A3C4]' :
                  listIdx % 4 === 1 ? 'bg-amber-400' :
                  listIdx % 4 === 2 ? 'bg-purple-500' : 'bg-emerald-500'
                ]"></span>
                <h3 class="text-sm font-black text-slate-800">{{ list.title }}</h3>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-slate-600 shadow-sm">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>

              <div class="flex items-center space-x-1">
                <button
                  v-if="userRole !== 'viewer'"
                  @click.stop="openManageSectionsModal"
                  class="p-1 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-white text-xs transition"
                  title="Abschnitte bearbeiten & sortieren"
                >
                  ✏️
                </button>
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
                class="relative bg-white border rounded-2xl p-4 transition-all duration-150 shadow-sm group select-none hover:shadow-md overflow-hidden"
                :class="[
                  draggedTask?.id === task.id ? 'opacity-40 border-dashed border-cyan-500 scale-[0.98]' : 'border-slate-200/90 hover:border-cyan-400',
                  userRole !== 'viewer' ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer'
                ]"
                @dragstart="onDragStart(task, list.id)"
                @dragend="onDragEnd"
                @click="openTaskDrawer(task)"
              >
                <!-- Drag handle & Task Header -->
                <div class="flex items-start justify-between gap-2 mb-2">
                  <div class="flex items-start space-x-2">
                    <span
                      v-if="userRole !== 'viewer'"
                      class="text-slate-300 group-hover:text-slate-500 text-xs mt-0.5"
                      title="Ziehen zum Verschieben"
                    >
                      ⋮⋮
                    </span>
                    <span class="text-xs font-bold text-slate-800 group-hover:text-cyan-700 transition leading-snug">
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
                    {{ getFieldLabel(key) }}: <strong class="text-slate-800">{{ val }}</strong>
                  </span>
                </div>

                <div class="flex items-center justify-between text-[10px] text-slate-400 pt-2 border-t border-slate-100">
                  <div class="flex items-center gap-2">
                    <span v-if="task.due_date" class="flex items-center space-x-1 font-medium text-slate-600">
                      <span>📅</span>
                      <span>{{ new Date(task.due_date).toLocaleDateString('de-CH') }}</span>
                    </span>
                    <!-- Assignee Avatar -->
                    <span
                      v-if="task.assigned_to"
                      class="w-5 h-5 rounded-full bg-gradient-to-tr from-cyan-500 to-teal-400 text-white text-[9px] font-black flex items-center justify-center"
                      :title="task.assignee_name || 'Zugewiesen'"
                    >
                      {{ (task.assignee_name || '?').charAt(0).toUpperCase() }}
                    </span>
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
                  </div>
                  <span class="text-cyan-600 font-bold group-hover:translate-x-0.5 transition-transform">Details →</span>
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
                class="py-6 text-center text-[11px] text-slate-400 border border-dashed border-slate-300 rounded-2xl bg-white/50"
              >
                Hier ablegen oder Aufgabe hinzufügen
              </div>
            </div>

            <!-- Add Task Button in Section -->
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(list.id)"
              class="mt-3 py-2 px-3 rounded-xl border border-dashed border-slate-300 hover:border-cyan-500 hover:bg-white text-xs font-bold text-slate-500 hover:text-cyan-700 transition text-center"
            >
              + Aufgabe hinzufügen
            </button>
          </div>
        </div>

        <!-- MODE B: TABLE / LISTE -->
        <div v-else class="space-y-6">
          <div
            v-for="list in lists"
            :key="list.id"
            class="bg-white border border-slate-200/90 rounded-3xl overflow-hidden shadow-sm"
          >
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="text-sm font-black text-slate-900">{{ list.title }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>
              <button
                v-if="userRole !== 'viewer'"
                @click="openNewTaskModal(list.id)"
                class="text-xs font-bold text-cyan-700 hover:text-cyan-800"
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
                      <div class="font-bold text-slate-900">{{ task.title }}</div>
                      <div v-if="task.description" class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                        {{ task.description }}
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
                          {{ getFieldLabel(key) }}: {{ val }}
                        </span>
                      </div>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <span class="text-xs text-cyan-700 font-bold hover:underline">Öffnen →</span>
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
        <div class="flex items-center justify-between bg-white border border-slate-200 p-6 rounded-3xl shadow-sm">
          <div>
            <h3 class="text-base font-black text-slate-900">Projektjournal & Notizen</h3>
            <p class="text-xs text-slate-500 mt-0.5">Chronologische Protokollierung, Besprechungsnotizen und wichtige Updates.</p>
          </div>
          <button
            v-if="userRole !== 'viewer'"
            @click="showNewJournalModal = true"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg"
          >
            + Neue Notiz erfassen
          </button>
        </div>

        <div v-if="journalEntries.length === 0" class="text-center py-12 text-slate-400 text-xs">
          Noch keine Journaleinträge vorhanden.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="entry in journalEntries"
            :key="entry.id"
            class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm"
          >
            <div class="flex items-start justify-between gap-4 mb-2">
              <div class="flex items-center space-x-2">
                <span class="text-xl">
                  {{ entry.entry_type === 'voice' ? '🎙️' : entry.entry_type === 'system' ? '⚙️' : entry.entry_type === 'email' ? '✉️' : '📝' }}
                </span>
                <div>
                  <h4 class="text-sm font-bold text-slate-900">{{ entry.title }}</h4>
                  <div class="text-[11px] text-slate-400">
                    Von <strong class="text-slate-700">{{ entry.author_name }}</strong> am {{ new Date(entry.created_at).toLocaleString('de-CH') }}
                  </div>
                </div>
              </div>
              <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                {{ entry.entry_type }}
              </span>
            </div>

            <p class="text-xs text-slate-700 leading-relaxed bg-slate-50 p-4 rounded-2xl border border-slate-200/80 my-3 whitespace-pre-wrap">
              {{ entry.content }}
            </p>

            <div v-if="entry.task_title" class="text-[11px] text-cyan-700 font-bold flex items-center space-x-1">
              <span>Verknüpft mit Aufgabe:</span>
              <strong class="text-slate-900">{{ entry.task_title }}</strong>
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
            v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin"
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

            <!-- Project-level Custom Fields Input -->
            <div v-if="projectCustomFields.length > 0" class="pt-4 border-t border-slate-100 space-y-3">
              <h4 class="text-xs font-bold text-cyan-800 uppercase tracking-wider">
                Projekt-Felder (Werte für dieses Projekt)
              </h4>
              <div v-for="f in projectCustomFields" :key="f.id">
                <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label }}</label>
                <select
                  v-if="f.field_type === 'select'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                >
                  <option value="">-- Nicht ausgewählt --</option>
                  <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <input
                  v-else
                  v-model="settingsForm.custom_data[f.field_key]"
                  :type="f.field_type === 'number' ? 'number' : 'text'"
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
              @click="showNewFieldModal = true"
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
                    {{ f.label }}
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
                  <td class="py-3 px-4 text-right">
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
              <div class="flex-1">
                <input
                  v-model="sec.title"
                  type="text"
                  required
                  placeholder="Abschnittsbezeichnung"
                  class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                />
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
              <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label }}</label>

              <!-- Select dropdown -->
              <select
                v-if="f.field_type === 'select'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 disabled:opacity-60"
              >
                <option value="">-- Nicht ausgewählt --</option>
                <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
              </select>

              <!-- Number -->
              <input
                v-else-if="f.field_type === 'number'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="number"
                placeholder="0.00"
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
              v-if="isEditingTask && userRole !== 'viewer'"
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
         TASK DETAIL DRAWER (MeisterTask Style - Slide-in from right)
         ============================================================ -->
    <transition name="drawer">
      <div v-if="showTaskDrawer" class="fixed inset-0 z-50 flex" style="pointer-events:all">
        <!-- Backdrop -->
        <div class="flex-1 bg-slate-900/40 backdrop-blur-sm" @click="closeTaskDrawer"></div>

        <!-- Drawer Panel -->
        <div class="w-full max-w-2xl bg-white flex flex-col shadow-2xl overflow-hidden h-full">
          <!-- Drawer Header -->
          <div
            class="flex-shrink-0 px-6 py-4 border-b border-slate-200 flex items-start justify-between gap-3"
            :style="drawerTask?.color ? {borderTopColor: drawerTask.color, borderTopWidth: '4px', borderTopStyle: 'solid'} : {}"
          >
            <div class="flex-1 min-w-0">
              <!-- Editable title -->
              <input
                v-if="drawerTask"
                v-model="drawerTask.title"
                @blur="autoSaveDrawer"
                @keyup.enter="autoSaveDrawer"
                :disabled="userRole === 'viewer'"
                class="w-full text-xl font-black text-slate-900 bg-transparent focus:outline-none focus:bg-slate-50 rounded-lg px-2 -mx-2 py-1 placeholder-slate-400 disabled:cursor-default"
                placeholder="Aufgabentitel"
              />
              <div v-if="drawerTask" class="flex items-center flex-wrap gap-2 mt-2">
                <!-- Status Dropdown -->
                <select
                  v-model="drawerTask.status"
                  @change="autoSaveDrawer"
                  :disabled="userRole === 'viewer'"
                  class="text-[11px] font-bold rounded-full px-3 py-1 border focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:cursor-default"
                  :class="{
                    'bg-emerald-50 text-emerald-700 border-emerald-300': drawerTask.status === 'done',
                    'bg-cyan-50 text-cyan-700 border-cyan-300': drawerTask.status === 'in_progress',
                    'bg-amber-50 text-amber-700 border-amber-300': drawerTask.status === 'review',
                    'bg-slate-100 text-slate-600 border-slate-300': drawerTask.status === 'todo'
                  }"
                >
                  <option value="todo">📋 Todo</option>
                  <option value="in_progress">🔄 In Arbeit</option>
                  <option value="review">🔍 In Prüfung</option>
                  <option value="done">✅ Erledigt</option>
                </select>
                <!-- Priority -->
                <select
                  v-model="drawerTask.priority"
                  @change="autoSaveDrawer"
                  :disabled="userRole === 'viewer'"
                  class="text-[11px] font-bold rounded-full px-3 py-1 border focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:cursor-default"
                  :class="{
                    'bg-rose-50 text-rose-700 border-rose-300': drawerTask.priority === 'dringend',
                    'bg-amber-50 text-amber-700 border-amber-300': drawerTask.priority === 'hoch',
                    'bg-slate-100 text-slate-600 border-slate-300': drawerTask.priority === 'normal',
                    'bg-emerald-50 text-emerald-700 border-emerald-300': drawerTask.priority === 'niedrig'
                  }"
                >
                  <option value="niedrig">🟢 Niedrig</option>
                  <option value="normal">🔵 Normal</option>
                  <option value="hoch">🟠 Hoch</option>
                  <option value="dringend">🔴 Dringend</option>
                </select>
              </div>
            </div>
            <button @click="closeTaskDrawer" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-100 shrink-0 transition" title="Schliessen">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Drawer Scrollable Body -->
          <div v-if="drawerTask" class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

            <!-- Meta Row: Assignee, Due Date -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">👤 Zuweisung</label>
                <select
                  v-model="drawerTask.assigned_to"
                  @change="autoSaveDrawer"
                  :disabled="userRole === 'viewer'"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-cyan-500 disabled:cursor-default"
                >
                  <option value="">Nicht zugewiesen</option>
                  <option v-for="m in members" :key="m.user_id" :value="m.user_id">
                    {{ m.name || m.email }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">📅 Fälligkeitsdatum</label>
                <input
                  v-model="drawerTask.due_date"
                  @change="autoSaveDrawer"
                  type="date"
                  :disabled="userRole === 'viewer'"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-cyan-500 disabled:cursor-default"
                />
              </div>
            </div>

            <!-- Color + Tags -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">🎨 Farbmarkierung</label>
              <div class="flex items-center flex-wrap gap-2 mb-4">
                <button
                  v-for="col in taskColors"
                  :key="col.value"
                  type="button"
                  @click="setTaskColor(col.value)"
                  :disabled="userRole === 'viewer'"
                  class="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110 disabled:cursor-default"
                  :style="{backgroundColor: col.value}"
                  :class="drawerTask.color === col.value ? 'border-slate-900 ring-2 ring-offset-1 ring-slate-400 scale-110' : 'border-white shadow-sm'"
                  :title="col.label"
                />
                <button v-if="drawerTask.color" type="button" @click="setTaskColor('')" class="text-xs text-slate-400 hover:text-slate-700 underline ml-1">Entfernen</button>
              </div>

              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">🏷️ Tags</label>
              <div class="flex flex-wrap gap-1.5 mb-2">
                <span
                  v-for="(tag, i) in drawerTask.tags"
                  :key="i"
                  class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-cyan-100 text-cyan-800 border border-cyan-200"
                >
                  <span>{{ tag }}</span>
                  <button v-if="userRole !== 'viewer'" @click="removeTag(i)" class="text-cyan-500 hover:text-cyan-900 ml-0.5">✕</button>
                </span>
              </div>
              <div v-if="userRole !== 'viewer'" class="flex items-center gap-2">
                <input v-model="newTagInput" @keyup.enter="addTag" type="text" placeholder="Tag hinzufügen..." class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500" />
                <button @click="addTag" type="button" class="taskster_button px-3 text-xs h-[32px] rounded-lg">+</button>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">📋 Beschreibung</label>
              <textarea
                v-model="drawerTask.description"
                @blur="autoSaveDrawer"
                :disabled="userRole === 'viewer'"
                rows="4"
                placeholder="Detaillierte Aufgabenbeschreibung, Anforderungen oder Zwischenziele..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-500 resize-none disabled:cursor-default"
              ></textarea>
            </div>

            <!-- Checklist -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">✅ Checkliste</label>
                <span v-if="drawerTask.checklist?.length" class="text-[10px] font-bold text-slate-600">
                  {{ drawerTask.checklist.filter((c:any) => c.done).length }} / {{ drawerTask.checklist.length }}
                </span>
              </div>
              <div v-if="drawerTask.checklist?.length" class="w-full bg-slate-200 rounded-full h-1.5 mb-3">
                <div
                  class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500"
                  :style="{width: (drawerTask.checklist.filter((c:any) => c.done).length / drawerTask.checklist.length * 100) + '%'}"
                ></div>
              </div>
              <div class="space-y-1.5 mb-3">
                <div v-for="(item, i) in drawerTask.checklist" :key="item.id" class="flex items-center gap-2 group/cl">
                  <input type="checkbox" :checked="item.done" @change="toggleChecklistItem(i)" :disabled="userRole === 'viewer'" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-0 shrink-0" />
                  <input v-model="item.text" @blur="autoSaveDrawer" :disabled="userRole === 'viewer'" class="flex-1 text-xs bg-transparent focus:outline-none focus:bg-slate-50 rounded px-1 disabled:cursor-default" :class="item.done ? 'line-through text-slate-400' : 'text-slate-800'" />
                  <button v-if="userRole !== 'viewer'" @click="removeChecklistItem(i)" class="opacity-0 group-hover/cl:opacity-100 text-slate-400 hover:text-rose-600 transition text-xs">✕</button>
                </div>
              </div>
              <div v-if="userRole !== 'viewer'" class="flex items-center gap-2">
                <input v-model="newChecklistInput" @keyup.enter="addChecklistItem" type="text" placeholder="+ Neuer Punkt..." class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500" />
                <button @click="addChecklistItem" type="button" class="taskster_button px-3 text-xs h-[32px] rounded-lg">+</button>
              </div>
            </div>

            <!-- Subtasks -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">📎 Unteraufgaben</label>
              <div class="space-y-1.5 mb-3">
                <div v-for="sub in drawerSubtasks" :key="sub.id" class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 group/sub">
                  <input type="checkbox" :checked="Boolean(sub.is_done)" @change="toggleSubtask(sub)" :disabled="userRole === 'viewer'" class="w-4 h-4 rounded-full border-slate-300 text-cyan-600 focus:ring-0 shrink-0" />
                  <span class="flex-1 text-xs" :class="sub.is_done ? 'line-through text-slate-400' : 'text-slate-800'">{{ sub.title }}</span>
                  <button v-if="userRole !== 'viewer'" @click="deleteSubtask(sub.id)" class="opacity-0 group-hover/sub:opacity-100 text-slate-400 hover:text-rose-600 transition text-xs">🗑️</button>
                </div>
              </div>
              <div v-if="userRole !== 'viewer'" class="flex items-center gap-2">
                <input v-model="newSubtaskInput" @keyup.enter="addSubtask" type="text" placeholder="+ Unteraufgabe hinzufügen..." class="flex-1 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500" />
                <button @click="addSubtask" type="button" class="taskster_button px-3 text-xs h-[32px] rounded-lg">+</button>
              </div>
            </div>

            <!-- Custom Fields -->
            <div v-if="taskCustomFields.length > 0">
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-3">⚙️ Zusatzfelder</label>
              <div class="space-y-3">
                <div v-for="f in visibleDrawerFields" :key="f.id">
                  <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label }}<span v-if="f.is_required" class="text-rose-500 ml-0.5">*</span></label>
                  <select v-if="f.field_type === 'select'" v-model="drawerTask.custom_data[f.field_key]" @change="autoSaveDrawer" :disabled="userRole === 'viewer'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-500 disabled:cursor-default">
                    <option value="">-- Nicht ausgewählt --</option>
                    <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                  <input v-else-if="f.field_type === 'date'" v-model="drawerTask.custom_data[f.field_key]" @change="autoSaveDrawer" type="date" :disabled="userRole === 'viewer'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500 disabled:cursor-default" />
                  <input v-else-if="f.field_type === 'number'" v-model="drawerTask.custom_data[f.field_key]" @change="autoSaveDrawer" type="number" :disabled="userRole === 'viewer'" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500 disabled:cursor-default" />
                  <input v-else v-model="drawerTask.custom_data[f.field_key]" @blur="autoSaveDrawer" :disabled="userRole === 'viewer'" type="text" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-cyan-500 disabled:cursor-default" />
                </div>
              </div>
            </div>

            <!-- Comments Feed -->
            <div>
              <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-3">💬 Kommentare & Notizen</label>
              <div class="space-y-3 mb-4">
                <div v-if="drawerComments.length === 0" class="text-xs text-slate-400 italic text-center py-4 border border-dashed border-slate-200 rounded-2xl">
                  Noch keine Kommentare. Sei der Erste!
                </div>
                <div v-for="c in drawerComments" :key="c.id" class="flex items-start gap-3">
                  <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-cyan-600 to-teal-500 text-white text-[10px] font-black flex items-center justify-center shrink-0">
                    {{ (c.author_name || '?').charAt(0).toUpperCase() }}
                  </div>
                  <div class="flex-1 bg-slate-50 rounded-2xl rounded-tl-sm px-3.5 py-2.5 border border-slate-200">
                    <div class="flex items-baseline justify-between gap-2 mb-1">
                      <span class="text-[11px] font-black text-slate-900">{{ c.author_name }}</span>
                      <span class="text-[10px] text-slate-400">{{ new Date(c.created_at).toLocaleString('de-CH', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) }}</span>
                    </div>
                    <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">{{ c.content }}</p>
                  </div>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-cyan-600 to-teal-500 text-white text-[10px] font-black flex items-center justify-center shrink-0">
                  {{ (user?.name || '?').charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1">
                  <textarea v-model="newCommentInput" @keydown.ctrl.enter="addComment" rows="2" placeholder="Kommentar schreiben... (Strg+Enter zum Senden)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl rounded-tl-sm text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-500 resize-none"></textarea>
                  <div class="flex justify-end mt-1.5">
                    <button @click="addComment" :disabled="!newCommentInput.trim()" type="button" class="taskster_button px-4 text-xs h-[34px] rounded-lg">Senden</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Drawer Footer -->
          <div class="flex-shrink-0 px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <button v-if="userRole !== 'viewer'" @click="deleteTaskFromDrawer" type="button" class="taskster_button_accent px-4 text-xs h-[38px] rounded-lg">
              🗑️ Aufgabe löschen
            </button>
            <div v-else></div>
            <button @click="closeTaskDrawer" type="button" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">Schliessen</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Modal: New Custom Field (Inside Settings) -->

    <div v-if="showNewFieldModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-black text-slate-900 mb-1">Neues benutzerdefiniertes Feld</h3>
        <p class="text-xs text-slate-500 mb-4">
          Definiere ein Attribut für Aufgaben oder das Projekt.
        </p>

        <form @submit.prevent="createField" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Gültigkeitsbereich</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="newFieldEntityType = 'task'"
                class="py-2 px-3 rounded-xl text-xs font-bold border transition text-center"
                :class="newFieldEntityType === 'task' ? 'bg-cyan-50 text-cyan-800 border-cyan-500' : 'bg-slate-50 text-slate-600 border-slate-200'"
              >
                Aufgaben-Feld
              </button>
              <button
                type="button"
                @click="newFieldEntityType = 'project'"
                class="py-2 px-3 rounded-xl text-xs font-bold border transition text-center"
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
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            >
              <option value="text">Textzeile</option>
              <option value="select">Auswahlliste (Dropdown)</option>
              <option value="number">Zahl / Währung</option>
              <option value="date">Datum</option>
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

    <!-- Modal: New Journal Entry -->
    <div v-if="showNewJournalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-black text-slate-900 mb-2">Neuer Journaleintrag / Notiz</h3>

        <form @submit.prevent="createJournalEntry" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Eintrags-Typ</label>
            <select
              v-model="journalForm.entry_type"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            >
              <option value="manual">📝 Besprechung / Notiz</option>
              <option value="voice">🎙️ Sprachnotiz</option>
              <option value="email">✉️ E-Mail Ablage</option>
              <option value="system">⚙️ Systemnotiz</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Titel / Betreff</label>
            <input
              v-model="journalForm.title"
              type="text"
              required
              placeholder="z.B. Zwischenstand Meeting mit Kunden"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Inhalt / Notiz</label>
            <textarea
              v-model="journalForm.content"
              required
              rows="4"
              placeholder="Genaue Beschreibung oder Zusammenfassung..."
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="showNewJournalModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Eintrag speichern
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
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { user, authHeaders } = useAuth()
const projectId = route.params.id as string

const project = ref<any>(null)
const userRole = ref<'owner' | 'admin' | 'editor' | 'viewer'>('viewer')
const lists = ref<any[]>([])
const fields = ref<any[]>([])
const members = ref<any[]>([])
const journalEntries = ref<any[]>([])
const loading = ref(true)

const currentView = ref<'tasks' | 'journal' | 'team' | 'settings'>('tasks')
const taskViewMode = ref<'board' | 'table'>('board')

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
const newFieldLabel = ref('')
const newFieldType = ref('text')
const newFieldEntityType = ref<'task' | 'project'>('task')
const newFieldOptionsRaw = ref('')
const enableFieldLogic = ref(false)
const logicDependsOnField = ref('')
const logicDependsOnValue = ref('')

// Project Settings form
const settingsForm = ref<any>({
  title: '',
  status: 'active',
  custom_data: {}
})
const savingProjectSettings = ref(false)

const showNewJournalModal = ref(false)
const journalForm = ref<any>({
  entry_type: 'manual',
  title: '',
  content: ''
})

const showInviteMemberModal = ref(false)
const inviteEmail = ref('')
const inviteRole = ref('editor')

// Task Detail Drawer
const showTaskDrawer = ref(false)
const drawerTask = ref<any>(null)
const drawerSubtasks = ref<any[]>([])
const drawerComments = ref<any[]>([])
const newTagInput = ref('')
const newChecklistInput = ref('')
const newSubtaskInput = ref('')
const newCommentInput = ref('')

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

  return lists.value.reduce((acc, l) => acc + (l.tasks?.length || 0), 0)
})

const taskCustomFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type !== 'project')
})

const projectCustomFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type === 'project')
})

const getFieldLabel = (key: string) => {
  const f = fields.value.find((item: any) => item.field_key === key)
  return f ? f.label : key
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

  const sourceList = lists.value.find(l => l.id === fromListId)
  const targetList = lists.value.find(l => l.id === targetListId)
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
  } catch (err: any) {
    if (err.statusCode === 404) {
      alert('Zugriff verweigert oder Projekt nicht gefunden.')
      navigateTo('/dashboard')
    }
  } finally {
    loading.value = false
  }
}

const initSettingsTab = () => {
  if (project.value) {
    settingsForm.value = {
      title: project.value.title,
      status: project.value.status,
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

const createField = async () => {
  try {
    const options = newFieldType.value === 'select'
      ? newFieldOptionsRaw.value.split(',').map((s) => s.trim()).filter(Boolean)
      : []

    const logicRules = enableFieldLogic.value && logicDependsOnField.value
      ? { depends_on_field: logicDependsOnField.value, depends_on_value: logicDependsOnValue.value }
      : null

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
    showNewFieldModal.value = false
    newFieldLabel.value = ''
    newFieldOptionsRaw.value = ''
    enableFieldLogic.value = false
    logicDependsOnField.value = ''
    logicDependsOnValue.value = ''
    await loadProjectData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Feld konnte nicht hinzugefügt werden')
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
    const res = await $fetch<any>(`/api/journals?project_id=${projectId}`, {
      headers: authHeaders()
    })
    journalEntries.value = res.entries || []
  } catch {}
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
const draggedBoardSection = ref<any>(null)

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
    const payload = managingSections.value.map((sec, idx) => ({
      id: sec.id,
      title: sec.title ? sec.title.trim() : `Abschnitt ${idx + 1}`,
      sort_order: idx + 1
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

// Board-Level Section Drag & Drop
const onSectionDragStart = (list: any, e: DragEvent) => {
  draggedBoardSection.value = list
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', list.id)
  }
}

const onSectionDragOver = (list: any, e: DragEvent) => {
  e.preventDefault()
  if (e.dataTransfer) {
    e.dataTransfer.dropEffect = 'move'
  }
}

const onSectionDrop = async (targetList: any, e: DragEvent) => {
  e.preventDefault()
  if (!draggedBoardSection.value || draggedBoardSection.value.id === targetList.id) {
    draggedBoardSection.value = null
    return
  }
  const fromIdx = lists.value.findIndex(l => l.id === draggedBoardSection.value.id)
  const toIdx = lists.value.findIndex(l => l.id === targetList.id)
  if (fromIdx === -1 || toIdx === -1) {
    draggedBoardSection.value = null
    return
  }
  const moved = lists.value.splice(fromIdx, 1)[0]
  lists.value.splice(toIdx, 0, moved)
  draggedBoardSection.value = null

  try {
    const payload = lists.value.map((sec, idx) => ({
      id: sec.id,
      sort_order: idx + 1
    }))
    await $fetch('/api/lists/reorder', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        lists: payload
      }
    })
    await loadProjectData()
  } catch (err) {
    console.error('Failed to save section reorder on board', err)
  }
}

const openNewTaskModal = (listId: string) => {
  isEditingTask.value = false
  targetListId.value = listId
  taskForm.value = {
    title: '',
    description: '',
    status: 'todo',
    due_date: '',
    custom_data: {}
  }
  showTaskModal.value = true
}

const openTaskDrawer = async (task: any) => {
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
  showTaskDrawer.value = true

  // Load full detail from API
  try {
    const res = await $fetch<any>(`/api/tasks/${task.id}`, { headers: authHeaders() })
    const t = res.task
    drawerTask.value = {
      ...t,
      due_date: t.due_date ? t.due_date.substring(0, 10) : '',
      tags: Array.isArray(t.tags) ? [...t.tags] : [],
      checklist: Array.isArray(t.checklist) ? JSON.parse(JSON.stringify(t.checklist)) : [],
      custom_data: { ...(t.custom_data || {}) },
      assigned_to: t.assigned_to || '',
      priority: t.priority || 'normal',
      color: t.color || ''
    }
    drawerSubtasks.value = res.subtasks || []
    drawerComments.value = res.comments || []
  } catch (err) {
    console.error('Failed to load task detail', err)
  }
}

// Keep backward compat for new task modal
const openEditTaskModal = (task: any) => openTaskDrawer(task)

const closeTaskDrawer = () => {
  showTaskDrawer.value = false
  loadProjectData()
}

const autoSaveDrawer = async () => {
  if (!drawerTask.value?.id || userRole.value === 'viewer') return
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
        assigned_to: drawerTask.value.assigned_to || null,
        priority: drawerTask.value.priority,
        color: drawerTask.value.color || null,
        tags: drawerTask.value.tags,
        checklist: drawerTask.value.checklist
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
    drawerComments.value.push(res.comment)
    newCommentInput.value = ''
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Senden des Kommentars')
  }
}

const deleteTaskFromDrawer = async () => {
  if (!confirm('Möchtest du diese Aufgabe wirklich löschen?')) return
  try {
    await $fetch(`/api/tasks/${drawerTask.value.id}`, { method: 'DELETE', headers: authHeaders() })
    closeTaskDrawer()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen')
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

const createJournalEntry = async () => {
  try {
    await $fetch('/api/journals', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: projectId,
        entry_type: journalForm.value.entry_type,
        title: journalForm.value.title,
        content: journalForm.value.content
      }
    })
    showNewJournalModal.value = false
    journalForm.value = { entry_type: 'manual', title: '', content: '' }
    await loadJournals()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern der Notiz')
  }
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

onMounted(async () => {
  await loadProjectData()
})
</script>

<style scoped>
/* Drawer slide-in animation from right */
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.25s ease;
}
.drawer-enter-active > div:last-child,
.drawer-leave-active > div:last-child {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.drawer-enter-from {
  opacity: 0;
}
.drawer-enter-from > div:last-child {
  transform: translateX(100%);
}
.drawer-leave-to {
  opacity: 0;
}
.drawer-leave-to > div:last-child {
  transform: translateX(100%);
}
</style>
