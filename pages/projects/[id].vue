<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-400 mb-6">
      <NuxtLink to="/dashboard" class="hover:text-emerald-400 transition">Dashboard</NuxtLink>
      <span>/</span>
      <NuxtLink :to="`/folders/${project?.folder_id}`" class="hover:text-emerald-400 transition">
        {{ project?.folder_name || 'Ordner' }}
      </NuxtLink>
      <span>/</span>
      <span class="text-slate-200 font-medium">{{ project?.title || 'Projekt' }}</span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-500">
      Lade Projektdaten...
    </div>

    <div v-else-if="project">
      <!-- Project Header -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex flex-wrap items-center gap-3 mb-1">
              <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ project.title }}</h1>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                :class="userRole === 'viewer' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'"
              >
                {{ userRole }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700"
              >
                Status: {{ project.status }}
              </span>
            </div>

            <p class="text-xs text-slate-400 flex items-center space-x-3 mb-2">
              <span>Ordner: <NuxtLink :to="`/folders/${project.folder_id}`" class="text-emerald-400 hover:underline">{{ project.folder_name }}</NuxtLink></span>
              <span v-if="project.company_name">• {{ project.company_name }}</span>
            </p>

            <!-- Project-level custom fields display in header -->
            <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-2 pt-1">
              <span
                v-for="(val, key) in project.custom_data"
                :key="key"
                class="inline-flex items-center text-xs px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300"
              >
                <span class="text-emerald-400 font-medium mr-1.5">{{ getFieldLabel(key) }}:</span>
                <span class="text-white font-semibold">{{ val }}</span>
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
        <div class="flex border-b border-slate-800 mt-6 -mb-6 space-x-6 overflow-x-auto">
          <button
            @click="currentView = 'tasks'"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap"
            :class="currentView === 'tasks' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>📋</span>
            <span>Aufgaben & Abschnitte ({{ totalTasks }})</span>
          </button>

          <button
            @click="currentView = 'journal'; loadJournals()"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap"
            :class="currentView === 'journal' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>📝</span>
            <span>Aktivitätsjournal & Notizen ({{ journalEntries.length }})</span>
          </button>

          <button
            @click="currentView = 'team'"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap"
            :class="currentView === 'team' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>👥</span>
            <span>Team & Berechtigungen ({{ members.length + 1 }})</span>
          </button>

          <button
            v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin"
            @click="currentView = 'settings'; initSettingsTab()"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5 whitespace-nowrap"
            :class="currentView === 'settings' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>⚙️</span>
            <span>Projekt-Einstellungen</span>
          </button>
        </div>
      </div>

      <!-- VIEW 1: TASKS & ABSCHNITTE -->
      <div v-if="currentView === 'tasks'">
        <!-- View controls: Board vs Table/List -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2">
            <span class="text-xs font-semibold text-slate-400">Ansicht:</span>
            <div class="bg-slate-900 border border-slate-800 rounded-lg p-0.5 flex items-center space-x-1">
              <button
                @click="taskViewMode = 'board'"
                class="px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center space-x-1"
                :class="taskViewMode === 'board' ? 'bg-slate-800 text-emerald-400 shadow' : 'text-slate-400 hover:text-white'"
              >
                <span>▦</span>
                <span>Kacheln (Board)</span>
              </button>
              <button
                @click="taskViewMode = 'table'"
                class="px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center space-x-1"
                :class="taskViewMode === 'table' ? 'bg-slate-800 text-emerald-400 shadow' : 'text-slate-400 hover:text-white'"
              >
                <span>☰</span>
                <span>Liste</span>
              </button>
            </div>
          </div>

          <span class="text-xs text-slate-500">
            {{ lists.length }} Abschnitte • {{ totalTasks }} Aufgaben
          </span>
        </div>

        <!-- Viewer Notice Banner -->
        <div
          v-if="userRole === 'viewer'"
          class="mb-6 p-3 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-300 text-xs flex items-center space-x-2"
        >
          <span>👁️</span>
          <span><strong>Viewer-Modus:</strong> Du besitzt Leserechte für dieses Projekt.</span>
        </div>

        <!-- Empty state -->
        <div v-if="lists.length === 0" class="text-center py-16 bg-slate-900/50 rounded-2xl border border-dashed border-slate-800">
          <span class="text-3xl">📋</span>
          <h3 class="text-base font-bold text-slate-200 mt-2">Noch keine Abschnitte in diesem Projekt</h3>
          <p class="text-xs text-slate-400 mt-1 mb-4">Erstelle den ersten Abschnitt (z.B. "Geplant", "In Bearbeitung", "Abgeschlossen").</p>
          <button
            v-if="userRole !== 'viewer'"
            @click="showNewListModal = true"
            class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400"
          >
            + Ersten Abschnitt erstellen
          </button>
        </div>

        <!-- MODE A: BOARD (KANBAN KACHELN MIT DRAG & DROP) -->
        <div v-else-if="taskViewMode === 'board'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
          <div
            v-for="list in lists"
            :key="list.id"
            class="bg-slate-900 border rounded-2xl p-5 flex flex-col transition-colors"
            :class="[
              dragOverListId === list.id ? 'border-emerald-500 bg-slate-900/90 ring-2 ring-emerald-500/20' : 'border-slate-800',
              draggedBoardSection?.id === list.id ? 'opacity-40 border-dashed border-blue-500 scale-[0.99]' : ''
            ]"
            @dragover.prevent="onDragOverList(list.id)"
            @dragleave="onDragLeaveList(list.id)"
            @drop="onDropToList(list.id)"
          >
            <!-- Section Header (Draggable for reordering columns) -->
            <div
              class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800 select-none group/hdr"
              :draggable="userRole !== 'viewer'"
              @dragstart="onSectionDragStart(list, $event)"
              @dragover.prevent="onSectionDragOver(list, $event)"
              @drop.stop="onSectionDrop(list, $event)"
            >
              <div class="flex items-center space-x-2">
                <span
                  v-if="userRole !== 'viewer'"
                  class="text-slate-600 hover:text-blue-400 cursor-grab active:cursor-grabbing text-xs transition"
                  title="Abschnitt ziehen, um Spalte zu verschieben"
                >
                  ⋮⋮
                </span>
                <h3 class="text-sm font-bold text-white">{{ list.title }}</h3>
                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-slate-800 text-slate-400">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>

              <div class="flex items-center space-x-1.5">
                <button
                  v-if="userRole !== 'viewer'"
                  @click.stop="openManageSectionsModal"
                  class="p-1 rounded text-slate-500 hover:text-white hover:bg-slate-800 text-xs transition"
                  title="Abschnitte bearbeiten & sortieren"
                >
                  ✏️
                </button>
                <!-- Access Mode Badge -->
                <span
                  v-if="list.access_mode === 'custom'"
                  class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-950/60 text-purple-300 border border-purple-800/60"
                >
                  🔒 Eingeschränkt
                </span>
                <span
                  v-else
                  class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-slate-800 text-slate-400"
                >
                  Abschnitt
                </span>
              </div>
            </div>

            <!-- Tasks in this section -->
            <div class="space-y-3 min-h-[60px] p-1 rounded-xl transition-colors" :class="dragOverListId === list.id ? 'bg-emerald-950/20' : ''">
              <div
                v-for="task in list.tasks"
                :key="task.id"
                :draggable="userRole !== 'viewer'"
                class="bg-slate-950 border rounded-xl p-4 transition shadow-sm group select-none"
                :class="[
                  draggedTask?.id === task.id ? 'opacity-40 border-dashed border-emerald-400 scale-[0.98]' : 'border-slate-800/90 hover:border-slate-700',
                  userRole !== 'viewer' ? 'cursor-grab active:cursor-grabbing hover:shadow-md' : 'cursor-pointer'
                ]"
                @dragstart="onDragStart(task, list.id)"
                @dragend="onDragEnd"
                @click="openEditTaskModal(task)"
              >
                <!-- Drag handle & Task Header -->
                <div class="flex items-start justify-between gap-2 mb-2">
                  <div class="flex items-start space-x-2">
                    <span
                      v-if="userRole !== 'viewer'"
                      class="text-slate-600 hover:text-slate-300 text-xs mt-0.5"
                      title="Ziehen zum Verschieben"
                    >
                      ⋮⋮
                    </span>
                    <span class="text-xs font-bold text-slate-100 group-hover:text-emerald-400 transition leading-snug">
                      {{ task.title }}
                    </span>
                  </div>
                  <span
                    class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded whitespace-nowrap"
                    :class="{
                      'bg-emerald-950 text-emerald-400 border border-emerald-800': task.status === 'done',
                      'bg-teal-950 text-teal-400 border border-teal-800': task.status === 'in_progress',
                      'bg-amber-950 text-amber-400 border border-amber-800': task.status === 'review',
                      'bg-slate-800 text-slate-400 border border-slate-700': task.status === 'todo'
                    }"
                  >
                    {{ task.status }}
                  </span>
                </div>

                <p v-if="task.description" class="text-[11px] text-slate-400 line-clamp-2 mb-3 leading-relaxed">
                  {{ task.description }}
                </p>

                <!-- Task Custom Fields Chips -->
                <div v-if="task.custom_data && Object.keys(task.custom_data).length > 0" class="flex flex-wrap gap-1.5 mb-3">
                  <span
                    v-for="(val, key) in task.custom_data"
                    :key="key"
                    class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-slate-300"
                  >
                    {{ getFieldLabel(key) }}: {{ val }}
                  </span>
                </div>

                <div class="flex items-center justify-between text-[10px] text-slate-500 pt-2 border-t border-slate-900">
                  <span v-if="task.due_date" class="flex items-center space-x-1">
                    <span>📅</span>
                    <span>{{ new Date(task.due_date).toLocaleDateString('de-CH') }}</span>
                  </span>
                  <span v-else>Keine Frist</span>
                  <span class="text-slate-600 group-hover:text-emerald-400 transition">Details →</span>
                </div>
              </div>

              <div
                v-if="!list.tasks || list.tasks.length === 0"
                class="py-6 text-center text-[11px] text-slate-600 border border-dashed border-slate-800/80 rounded-xl"
              >
                Hier ablegen oder Aufgabe hinzufügen
              </div>
            </div>

            <!-- Add Task Button in Section -->
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(list.id)"
              class="mt-4 py-2 px-3 rounded-lg border border-dashed border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/20 text-xs font-semibold text-slate-400 hover:text-emerald-400 transition text-center"
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
            class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl"
          >
            <div class="px-5 py-3.5 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
              <div class="flex items-center space-x-2">
                <span class="text-sm font-bold text-white">{{ list.title }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>
              <button
                v-if="userRole !== 'viewer'"
                @click="openNewTaskModal(list.id)"
                class="text-xs font-semibold text-emerald-400 hover:text-emerald-300"
              >
                + Aufgabe erfassen
              </button>
            </div>

            <div v-if="!list.tasks || list.tasks.length === 0" class="p-4 text-center text-xs text-slate-500">
              Keine Aufgaben in diesem Abschnitt.
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px] border-b border-slate-800">
                  <tr>
                    <th class="py-2.5 px-4">Titel & Beschreibung</th>
                    <th class="py-2.5 px-4">Status</th>
                    <th class="py-2.5 px-4">Fälligkeit</th>
                    <th class="py-2.5 px-4">Felder</th>
                    <th class="py-2.5 px-4 text-right">Aktion</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-300">
                  <tr
                    v-for="task in list.tasks"
                    :key="task.id"
                    class="hover:bg-slate-800/40 transition cursor-pointer"
                    @click="openEditTaskModal(task)"
                  >
                    <td class="py-3 px-4">
                      <div class="font-bold text-slate-100">{{ task.title }}</div>
                      <div v-if="task.description" class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">
                        {{ task.description }}
                      </div>
                    </td>
                    <td class="py-3 px-4">
                      <span
                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                        :class="{
                          'bg-emerald-950 text-emerald-400 border border-emerald-800': task.status === 'done',
                          'bg-teal-950 text-teal-400 border border-teal-800': task.status === 'in_progress',
                          'bg-amber-950 text-amber-400 border border-amber-800': task.status === 'review',
                          'bg-slate-800 text-slate-400': task.status === 'todo'
                        }"
                      >
                        {{ task.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4">
                      <span v-if="task.due_date" class="text-slate-300">
                        {{ new Date(task.due_date).toLocaleDateString('de-CH') }}
                      </span>
                      <span v-else class="text-slate-600">-</span>
                    </td>
                    <td class="py-3 px-4">
                      <div v-if="task.custom_data && Object.keys(task.custom_data).length > 0" class="flex flex-wrap gap-1">
                        <span
                          v-for="(val, key) in task.custom_data"
                          :key="key"
                          class="text-[9px] px-1.5 py-0.5 rounded bg-slate-950 border border-slate-800 text-slate-400"
                        >
                          {{ getFieldLabel(key) }}: {{ val }}
                        </span>
                      </div>
                      <span v-else class="text-slate-600">-</span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <span class="text-xs text-emerald-400 hover:underline">Öffnen →</span>
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
        <div class="flex items-center justify-between bg-slate-900 border border-slate-800 p-4 rounded-xl">
          <div>
            <h3 class="text-sm font-bold text-white">Projektjournal & Notizen</h3>
            <p class="text-xs text-slate-400">Chronologische Protokollierung, Besprechungsnotizen und wichtige Updates.</p>
          </div>
          <button
            v-if="userRole !== 'viewer'"
            @click="showNewJournalModal = true"
            class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 transition"
          >
            + Neue Notiz erfassen
          </button>
        </div>

        <div v-if="journalEntries.length === 0" class="text-center py-12 text-slate-500 text-xs">
          Noch keine Journaleinträge vorhanden.
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="entry in journalEntries"
            :key="entry.id"
            class="bg-slate-900 border border-slate-800 rounded-2xl p-6"
          >
            <div class="flex items-start justify-between gap-4 mb-2">
              <div class="flex items-center space-x-2">
                <span class="text-xl">
                  {{ entry.entry_type === 'voice' ? '🎙️' : entry.entry_type === 'system' ? '⚙️' : entry.entry_type === 'email' ? '✉️' : '📝' }}
                </span>
                <div>
                  <h4 class="text-sm font-bold text-white">{{ entry.title }}</h4>
                  <div class="text-[11px] text-slate-400">
                    Von <strong class="text-slate-300">{{ entry.author_name }}</strong> am {{ new Date(entry.created_at).toLocaleString('de-CH') }}
                  </div>
                </div>
              </div>
              <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">
                {{ entry.entry_type }}
              </span>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed bg-slate-950 p-4 rounded-xl border border-slate-800/80 my-3 whitespace-pre-wrap">
              {{ entry.content }}
            </p>

            <div v-if="entry.task_title" class="text-[11px] text-emerald-400/90 flex items-center space-x-1">
              <span>Verknüpft mit Aufgabe:</span>
              <strong class="text-slate-200">{{ entry.task_title }}</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW 3: TEAM & BERECHTIGUNGEN -->
      <div v-else-if="currentView === 'team'" class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white">Projektteam & Berechtigungen</h3>
            <p class="text-xs text-slate-400">
              Steuerung von Editor- und Viewer-Rollen für dieses Projekt.
            </p>
          </div>
          <button
            v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin"
            @click="showInviteMemberModal = true"
            class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400 transition"
          >
            + Mitglied einladen
          </button>
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between p-4 rounded-xl bg-slate-950 border border-slate-800">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-full bg-emerald-950 text-emerald-300 font-bold flex items-center justify-center text-xs border border-emerald-800">
                PW
              </div>
              <div>
                <div class="text-xs font-bold text-white">{{ project.folder_name }} Owner</div>
                <div class="text-[11px] text-slate-400">Projektinhaber (Voller administrativer Zugriff)</div>
              </div>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800">
              PROJECT OWNER
            </span>
          </div>

          <div
            v-for="m in members"
            :key="m.id"
            class="flex items-center justify-between p-4 rounded-xl bg-slate-950 border border-slate-800"
          >
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-full bg-slate-800 text-slate-300 font-bold flex items-center justify-center text-xs">
                {{ m.name.charAt(0) }}
              </div>
              <div>
                <div class="text-xs font-bold text-white">{{ m.name }}</div>
                <div class="text-[11px] text-slate-400">{{ m.email }}</div>
              </div>
            </div>
            <span
              class="text-xs font-bold uppercase px-2 py-0.5 rounded"
              :class="m.role === 'editor' ? 'bg-teal-950 text-teal-300 border border-teal-800' : 'bg-amber-950 text-amber-300 border border-amber-800'"
            >
              {{ m.role }}
            </span>
          </div>
        </div>
      </div>

      <!-- VIEW 4: PROJEKT-EINSTELLUNGEN & BENUTZERDEFINIERTE FELDER -->
      <div v-else-if="currentView === 'settings'" class="space-y-8">
        <!-- Card 1: Projekt-Stammdaten & Projekt-Felder -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
            <div>
              <h3 class="text-base font-bold text-white">Allgemeine Projekt-Einstellungen</h3>
              <p class="text-xs text-slate-400">Passe den Projektnamen, den Status und projektweite Eigenschaften an.</p>
            </div>
          </div>

          <form @submit.prevent="saveProjectSettings" class="space-y-4 max-w-xl">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Projekttitel</label>
              <input
                v-model="settingsForm.title"
                type="text"
                required
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Projekt-Status</label>
              <select
                v-model="settingsForm.status"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
              >
                <option value="active">Aktiv (Active)</option>
                <option value="on_hold">Pausiert (On Hold)</option>
                <option value="completed">Abgeschlossen (Completed)</option>
              </select>
            </div>

            <!-- Project-level Custom Fields Input -->
            <div v-if="projectCustomFields.length > 0" class="pt-4 border-t border-slate-800 space-y-3">
              <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">
                Projekt-Felder (Werte für dieses Projekt)
              </h4>
              <div v-for="f in projectCustomFields" :key="f.id">
                <label class="block text-xs font-medium text-slate-300 mb-1">{{ f.label }}</label>
                <select
                  v-if="f.field_type === 'select'"
                  v-model="settingsForm.custom_data[f.field_key]"
                  class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                >
                  <option value="">-- Nicht ausgewählt --</option>
                  <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <input
                  v-else
                  v-model="settingsForm.custom_data[f.field_key]"
                  :type="f.field_type === 'number' ? 'number' : 'text'"
                  class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
                />
              </div>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="savingProjectSettings"
                class="px-5 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition disabled:opacity-50"
              >
                {{ savingProjectSettings ? 'Speichern...' : 'Projekt-Einstellungen speichern' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Card 2: Benutzerdefinierte Felder verwalten (Versteckt in Projekt-Einstellungen) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-800">
            <div>
              <h3 class="text-base font-bold text-white flex items-center space-x-2">
                <span>⚙️</span>
                <span>Benutzerdefinierte Felder & Logik</span>
              </h3>
              <p class="text-xs text-slate-400 mt-0.5">
                Definiere eigene Attribute für Aufgaben oder für Projekte. Felder können auch bedingt voneinander abhängig gemacht werden.
              </p>
            </div>
            <button
              @click="showNewFieldModal = true"
              class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white transition shadow-sm"
            >
              + Neues Feld anlegen
            </button>
          </div>

          <!-- Fields Table -->
          <div v-if="fields.length === 0" class="text-center py-8 text-xs text-slate-500">
            Noch keine benutzerdefinierten Felder angelegt.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px] border-b border-slate-800">
                <tr>
                  <th class="py-2.5 px-4">Feld-Bezeichnung</th>
                  <th class="py-2.5 px-4">Schlüssel (Key)</th>
                  <th class="py-2.5 px-4">Bereich / Typ</th>
                  <th class="py-2.5 px-4">Bedingte Logik</th>
                  <th class="py-2.5 px-4 text-right">Aktion</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/80 text-slate-300">
                <tr v-for="f in fields" :key="f.id" class="hover:bg-slate-800/40 transition">
                  <td class="py-3 px-4 font-bold text-white">
                    {{ f.label }}
                  </td>
                  <td class="py-3 px-4 font-mono text-emerald-400 text-[11px]">
                    {{ f.field_key }}
                  </td>
                  <td class="py-3 px-4">
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-bold uppercase mr-1.5"
                      :class="f.entity_type === 'project' ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-emerald-950 text-emerald-300 border border-emerald-800'"
                    >
                      {{ f.entity_type === 'project' ? 'Projekt-Feld' : 'Aufgaben-Feld' }}
                    </span>
                    <span class="text-slate-400 text-[11px]">({{ f.field_type }})</span>
                  </td>
                  <td class="py-3 px-4">
                    <span v-if="f.logic_rules && f.logic_rules.depends_on_field" class="text-[11px] text-amber-300 font-mono">
                      Nur wenn {{ f.logic_rules.depends_on_field }} == "{{ f.logic_rules.depends_on_value }}"
                    </span>
                    <span v-else class="text-slate-600">-</span>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <button
                      @click="deleteField(f.id)"
                      class="text-rose-400 hover:text-rose-300 text-xs font-semibold"
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
    <div v-if="showNewListModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neuen Abschnitt anlegen</h3>
        <p class="text-xs text-slate-400 mb-4">
          Abschnitte gliedern dein Projekt in Phasen, Kategorien oder Workflow-Schritte.
        </p>

        <form @submit.prevent="createList" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Titel des Abschnitts</label>
            <input
              v-model="newListTitle"
              type="text"
              required
              placeholder="z.B. Vorbereitung, In Bearbeitung oder Abnahme"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Sichtbarkeits-Modus</label>
            <select
              v-model="newListAccessMode"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            >
              <option value="inherit">Standard (Alle Projektmitglieder haben Zugriff)</option>
              <option value="custom">Eingeschränkt (Nur Owner & explizit berechtigte Personen)</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
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
    <div v-if="showManageSectionsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm overflow-y-auto">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col my-8">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-800 flex items-start justify-between bg-slate-950/50">
          <div>
            <div class="flex items-center space-x-2">
              <span class="text-xl">📋</span>
              <h3 class="text-lg font-bold text-white">Projekt-Abschnitte verwalten</h3>
            </div>
            <p class="text-xs text-slate-400 mt-1">
              Passe die Reihenfolge per Drag & Drop oder Pfeiltasten an, benenne Abschnitte um oder entferne Phasen.
            </p>
          </div>
          <button
            type="button"
            @click="showManageSectionsModal = false"
            class="text-slate-400 hover:text-white text-lg p-1 rounded-lg hover:bg-slate-800 transition"
          >
            ✕
          </button>
        </div>

        <div v-if="manageSectionsError" class="mx-6 mt-4 p-3 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
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
              class="p-3.5 rounded-xl border bg-slate-950/90 transition flex items-center justify-between gap-3 group"
              :class="draggedSectionModalIdx === idx ? 'border-blue-500 bg-blue-950/30 opacity-50' : 'border-slate-800 hover:border-slate-700'"
            >
              <!-- Drag Handle & Index -->
              <div class="flex items-center space-x-3">
                <span class="text-slate-500 hover:text-slate-300 cursor-grab active:cursor-grabbing text-sm select-none" title="Ziehen zum Verschieben">⋮⋮</span>
                <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-300 font-bold text-xs flex items-center justify-center select-none">
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
                  class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs font-semibold text-slate-100 focus:outline-none focus:border-blue-500"
                />
              </div>

              <!-- Task Count Badge -->
              <span class="text-[11px] text-slate-400 bg-slate-900 px-2.5 py-1 rounded border border-slate-800 whitespace-nowrap">
                {{ sec.tasks?.length || sec.task_count || 0 }} Aufgaben
              </span>

              <!-- Action Controls: Up, Down, Delete -->
              <div class="flex items-center space-x-1">
                <button
                  type="button"
                  @click="moveSectionUp(idx)"
                  :disabled="idx === 0"
                  class="p-1.5 rounded hover:bg-slate-800 text-slate-400 hover:text-white disabled:opacity-25 disabled:cursor-not-allowed transition"
                  title="Nach oben verschieben"
                >
                  ⬆️
                </button>
                <button
                  type="button"
                  @click="moveSectionDown(idx)"
                  :disabled="idx === managingSections.length - 1"
                  class="p-1.5 rounded hover:bg-slate-800 text-slate-400 hover:text-white disabled:opacity-25 disabled:cursor-not-allowed transition"
                  title="Nach unten verschieben"
                >
                  ⬇️
                </button>
                <button
                  type="button"
                  @click="deleteSectionInModal(idx)"
                  class="p-1.5 rounded hover:bg-rose-950 text-rose-400 hover:text-rose-300 transition ml-1"
                  title="Abschnitt löschen"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>

          <!-- Quick Add Section Row inside Modal -->
          <div class="pt-4 border-t border-slate-800 flex items-center gap-2">
            <input
              v-model="newSectionTitleInModal"
              type="text"
              placeholder="+ Weiterer Abschnitt (z.B. Zwischenprüfung, Abnahme)..."
              class="flex-1 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500"
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
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
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
              <span>{{ savingSections ? 'Wird gespeichert...' : 'Reihenfolge & Namen speichern' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: New Task / Edit Task -->
    <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold text-white">
            {{ isEditingTask ? 'Aufgabe bearbeiten' : 'Neue Aufgabe erfassen' }}
          </h3>
          <span v-if="userRole === 'viewer'" class="text-xs font-bold text-amber-400 bg-amber-950/60 px-2 py-0.5 rounded">
            Viewer Read-Only
          </span>
        </div>

        <form @submit.prevent="saveTask" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Aufgabentitel</label>
            <input
              v-model="taskForm.title"
              :disabled="userRole === 'viewer'"
              type="text"
              required
              placeholder="z.B. Konzeptentwurf finalisieren"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Beschreibung</label>
            <textarea
              v-model="taskForm.description"
              :disabled="userRole === 'viewer'"
              rows="3"
              placeholder="Detaillierte Aufgabenbeschreibung, Anforderungen oder Zwischenziele..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Status</label>
              <select
                v-model="taskForm.status"
                :disabled="userRole === 'viewer'"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
              >
                <option value="todo">Zu erledigen (Todo)</option>
                <option value="in_progress">In Arbeit (In Progress)</option>
                <option value="review">In Prüfung (Review)</option>
                <option value="done">Abgeschlossen (Done)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Fälligkeitsdatum</label>
              <input
                v-model="taskForm.due_date"
                :disabled="userRole === 'viewer'"
                type="date"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
              />
            </div>
          </div>

          <!-- Dynamic Task Custom Fields with Conditional Logic -->
          <div v-if="taskCustomFields.length > 0" class="pt-4 border-t border-slate-800 space-y-3">
            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">
              Zusatzfelder
            </h4>
            <div
              v-for="f in taskCustomFields"
              :key="f.id"
              v-show="isFieldVisibleForTask(f)"
              class="transition-all"
            >
              <label class="block text-xs font-medium text-slate-300 mb-1">{{ f.label }}</label>

              <!-- Select dropdown -->
              <select
                v-if="f.field_type === 'select'"
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
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
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
              />

              <!-- Default Text -->
              <input
                v-else
                v-model="taskForm.custom_data[f.field_key]"
                :disabled="userRole === 'viewer'"
                type="text"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500 disabled:opacity-60"
              />
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <button
              v-if="isEditingTask && userRole !== 'viewer'"
              type="button"
              @click="deleteTask"
              class="px-3 py-2 rounded-lg text-xs font-bold text-rose-400 hover:bg-rose-950/40 border border-rose-900/60 transition"
            >
              Löschen
            </button>
            <div v-else></div>

            <div class="flex items-center space-x-3">
              <button
                type="button"
                @click="showTaskModal = false"
                class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
              >
                Schliessen
              </button>
              <button
                v-if="userRole !== 'viewer'"
                type="submit"
                class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition"
              >
                Speichern
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: New Custom Field (Inside Settings) -->
    <div v-if="showNewFieldModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-white mb-2">Neues benutzerdefiniertes Feld</h3>
        <p class="text-xs text-slate-400 mb-4">
          Definiere ein Attribut für Aufgaben oder das Projekt.
        </p>

        <form @submit.prevent="createField" class="space-y-4">
          <!-- Entity Type Distinction: Project vs Task -->
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Gültigkeitsbereich</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="newFieldEntityType = 'task'"
                class="py-2 px-3 rounded-lg text-xs font-semibold border transition text-center"
                :class="newFieldEntityType === 'task' ? 'bg-emerald-950 text-emerald-300 border-emerald-500' : 'bg-slate-950 text-slate-400 border-slate-700'"
              >
                Aufgaben-Feld
              </button>
              <button
                type="button"
                @click="newFieldEntityType = 'project'"
                class="py-2 px-3 rounded-lg text-xs font-semibold border transition text-center"
                :class="newFieldEntityType === 'project' ? 'bg-purple-950 text-purple-300 border-purple-500' : 'bg-slate-950 text-slate-400 border-slate-700'"
              >
                Projekt-Feld
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Feld-Bezeichnung (Label)</label>
            <input
              v-model="newFieldLabel"
              type="text"
              required
              placeholder="z.B. Kostenstelle oder Priorität"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Feldtyp</label>
            <select
              v-model="newFieldType"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            >
              <option value="text">Textzeile</option>
              <option value="select">Auswahlliste (Dropdown)</option>
              <option value="number">Zahl / Währung</option>
              <option value="date">Datum</option>
            </select>
          </div>

          <div v-if="newFieldType === 'select'">
            <label class="block text-xs font-medium text-slate-300 mb-1">Optionen (Komma-getrennt)</label>
            <input
              v-model="newFieldOptionsRaw"
              type="text"
              placeholder="z.B. Niedrig, Mittel, Hoch, Dringend"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <!-- Conditional Logic Builder -->
          <div class="pt-3 border-t border-slate-800 space-y-3">
            <div class="flex items-center space-x-2">
              <input
                id="enableLogic"
                v-model="enableFieldLogic"
                type="checkbox"
                class="rounded border-slate-700 bg-slate-950 text-emerald-500 focus:ring-0"
              />
              <label for="enableLogic" class="text-xs font-medium text-slate-300 cursor-pointer">
                Bedingte Logik (Feld nur unter Bedingung anzeigen)
              </label>
            </div>

            <div v-if="enableFieldLogic" class="space-y-2 p-3 bg-slate-950 rounded-xl border border-slate-800">
              <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Abhängig von Feld</label>
                <select
                  v-model="logicDependsOnField"
                  class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                >
                  <option value="">-- Feld auswählen --</option>
                  <option v-for="other in fields" :key="other.id" :value="other.field_key">
                    {{ other.label }} ({{ other.field_key }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Nur anzeigen wenn Wert gleich:</label>
                <input
                  v-model="logicDependsOnValue"
                  type="text"
                  placeholder="z.B. Hoch oder Freigegeben"
                  class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showNewFieldModal = false"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition"
            >
              Feld speichern
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: New Journal Entry -->
    <div v-if="showNewJournalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neuer Journaleintrag / Notiz</h3>

        <form @submit.prevent="createJournalEntry" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Eintrags-Typ</label>
            <select
              v-model="journalForm.entry_type"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            >
              <option value="manual">📝 Besprechung / Notiz</option>
              <option value="voice">🎙️ Sprachnotiz</option>
              <option value="email">✉️ E-Mail Ablage</option>
              <option value="system">⚙️ Systemnotiz</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Titel / Betreff</label>
            <input
              v-model="journalForm.title"
              type="text"
              required
              placeholder="z.B. Zwischenstand Meeting mit Kunden"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Inhalt / Notiz</label>
            <textarea
              v-model="journalForm.content"
              required
              rows="4"
              placeholder="Genaue Beschreibung oder Zusammenfassung..."
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showNewJournalModal = false"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition"
            >
              Eintrag speichern
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Invite Member -->
    <div v-if="showInviteMemberModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Teammitglied ins Projekt einladen</h3>
        <p class="text-xs text-slate-400 mb-4">
          Im Free Plan sind maximal 5 Mitglieder pro Projekt erlaubt.
        </p>

        <form @submit.prevent="inviteMember" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">E-Mail des Nutzers</label>
            <input
              v-model="inviteEmail"
              type="email"
              required
              placeholder="kollege@domain.ch"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Rolle im Projekt</label>
            <select
              v-model="inviteRole"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            >
              <option value="editor">Editor (Darf Aufgaben erstellen & bearbeiten)</option>
              <option value="viewer">Viewer (Nur Leserechte)</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showInviteMemberModal = false"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition"
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

const openEditTaskModal = (task: any) => {
  isEditingTask.value = true
  currentEditingTaskId.value = task.id
  targetListId.value = task.list_id
  taskForm.value = {
    title: task.title,
    description: task.description,
    status: task.status,
    due_date: task.due_date ? task.due_date.substring(0, 10) : '',
    custom_data: { ...(task.custom_data || {}) }
  }
  showTaskModal.value = true
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
