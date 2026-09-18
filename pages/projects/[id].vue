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
      Lade Projektdaten und Berechtigungen...
    </div>

    <div v-else-if="project">
      <!-- Project Header -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center space-x-3 mb-1">
              <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ project.title }}</h1>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider"
                :class="userRole === 'viewer' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'"
              >
                Rolle: {{ userRole }}
              </span>
            </div>
            <p class="text-xs text-slate-400 flex items-center space-x-3">
              <span>Ordner: <NuxtLink :to="`/folders/${project.folder_id}`" class="text-emerald-400 hover:underline">{{ project.folder_name }}</NuxtLink></span>
              <span v-if="project.company_name">• {{ project.company_name }}</span>
              <span>• Status: <strong class="text-slate-200 uppercase">{{ project.status }}</strong></span>
            </p>
          </div>

          <!-- Actions -->
          <div class="flex items-center space-x-2">
            <button
              v-if="userRole !== 'viewer'"
              @click="showNewListModal = true"
              class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
            >
              + Neue Liste
            </button>
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(lists[0]?.id)"
              :disabled="lists.length === 0"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition shadow-lg shadow-emerald-500/10 disabled:opacity-50"
            >
              + Aufgabe erfassen
            </button>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-800 mt-6 -mb-6 space-x-6">
          <button
            @click="currentView = 'board'"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5"
            :class="currentView === 'board' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>📋</span>
            <span>Aufgaben & Listen ({{ totalTasks }})</span>
          </button>

          <button
            @click="currentView = 'journal'; loadJournals()"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5"
            :class="currentView === 'journal' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>📝</span>
            <span>Aktivitätsjournal & Notizen ({{ journalEntries.length }})</span>
          </button>

          <button
            @click="currentView = 'team'"
            class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-1.5"
            :class="currentView === 'team' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
          >
            <span>👥</span>
            <span>Team & Berechtigungen ({{ members.length + 1 }})</span>
          </button>
        </div>
      </div>

      <!-- VIEW 1: BOARD / LISTS -->
      <div v-if="currentView === 'board'">
        <!-- Viewer Notice Banner -->
        <div
          v-if="userRole === 'viewer'"
          class="mb-6 p-3 rounded-xl bg-amber-950/40 border border-amber-800/60 text-amber-300 text-xs flex items-center space-x-2"
        >
          <span>👁️</span>
          <span><strong>Viewer-Modus aktiv:</strong> Du hast Leserechte für dieses Projekt.</span>
        </div>

        <!-- Lists Container -->
        <div v-if="lists.length === 0" class="text-center py-16 bg-slate-900/50 rounded-2xl border border-dashed border-slate-800">
          <span class="text-3xl">📋</span>
          <h3 class="text-base font-bold text-slate-200 mt-2">Noch keine Listen in diesem Projekt</h3>
          <p class="text-xs text-slate-400 mt-1 mb-4">Erstelle jetzt die erste Aufgabenliste (z.B. "Geplant", "In Bearbeitung", "Erledigt").</p>
          <button
            v-if="userRole !== 'viewer'"
            @click="showNewListModal = true"
            class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 text-slate-950 hover:bg-emerald-400"
          >
            + Erste Liste erstellen
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
          <div
            v-for="list in lists"
            :key="list.id"
            class="bg-slate-900 border rounded-2xl p-5 flex flex-col transition-colors"
            :class="dragOverListId === list.id ? 'border-emerald-500 bg-slate-900/90 ring-2 ring-emerald-500/20' : 'border-slate-800'"
            @dragover.prevent="onDragOverList(list.id)"
            @dragleave="onDragLeaveList(list.id)"
            @drop="onDropToList(list.id)"
          >
            <!-- List Header -->
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-800">
              <div class="flex items-center space-x-2">
                <h3 class="text-sm font-bold text-white">{{ list.title }}</h3>
                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-slate-800 text-slate-400">
                  {{ list.tasks?.length || 0 }}
                </span>
              </div>

              <!-- Access Mode Badge -->
              <span
                v-if="list.access_mode === 'custom'"
                class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-950/60 text-purple-300 border border-purple-800/60"
                title="Vertrauliche Liste mit eingeschränkter Sichtbarkeit"
              >
                🔒 Vertraulich
              </span>
              <span
                v-else
                class="text-[9px] font-medium px-1.5 py-0.5 rounded bg-slate-800 text-slate-400"
                title="Sichtbarkeit wird vom Projekt geerbt"
              >
                Geerbt
              </span>
            </div>

            <!-- Tasks in this list -->
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
                      title="Karte ziehen zum Verschieben"
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

                <!-- Description excerpt -->
                <p v-if="task.description" class="text-[11px] text-slate-400 line-clamp-2 mb-3 leading-relaxed">
                  {{ task.description }}
                </p>

                <!-- Custom Fields Chips -->
                <div v-if="task.custom_data && Object.keys(task.custom_data).length > 0" class="flex flex-wrap gap-1.5 mb-3">
                  <span
                    v-for="(val, key) in task.custom_data"
                    :key="key"
                    class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-slate-300"
                  >
                    {{ key }}: {{ val }}
                  </span>
                </div>

                <!-- Footer: Due date -->
                <div class="flex items-center justify-between text-[10px] text-slate-500 pt-2 border-t border-slate-900">
                  <span v-if="task.due_date" class="flex items-center space-x-1">
                    <span>📅</span>
                    <span>{{ new Date(task.due_date).toLocaleDateString('de-CH') }}</span>
                  </span>
                  <span v-else>Keine Frist</span>
                  <span class="text-slate-600 group-hover:text-emerald-400 transition">Details →</span>
                </div>
              </div>

              <!-- Empty drop zone hint -->
              <div
                v-if="!list.tasks || list.tasks.length === 0"
                class="py-6 text-center text-[11px] text-slate-600 border border-dashed border-slate-800/80 rounded-xl"
              >
                Hier ablegen oder Aufgabe hinzufügen
              </div>
            </div>

            <!-- Add Task Button in List -->
            <button
              v-if="userRole !== 'viewer'"
              @click="openNewTaskModal(list.id)"
              class="mt-4 py-2 px-3 rounded-lg border border-dashed border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/20 text-xs font-semibold text-slate-400 hover:text-emerald-400 transition text-center"
            >
              + Aufgabe hinzufügen
            </button>
          </div>
        </div>
      </div>

      <!-- VIEW 2: JOURNAL / AKTIVITÄTSNOTIZEN -->
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

            <p class="text-xs text-slate-300 leading-relaxed bg-slate-950 p-4 rounded-xl border border-slate-800/80 my-3">
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
            <h3 class="text-base font-bold text-white">Projektteam & Berechtigungsmatrix</h3>
            <p class="text-xs text-slate-400">
              Steuerung von Editor- und Viewer-Rollen (Stufe 4 des Berechtigungsmodells).
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
          <!-- Owner row -->
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

          <!-- Members rows -->
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
    </div>

    <!-- Modal: New List -->
    <div v-if="showNewListModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neue Aufgabenliste anlegen</h3>
        <form @submit.prevent="createList" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Listentitel</label>
            <input
              v-model="newListTitle"
              type="text"
              required
              placeholder="z.B. 4. Qualitätskontrolle & Abnahme"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Zugriffsmodus</label>
            <select
              v-model="newListAccessMode"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            >
              <option value="inherit">Standard (Alle Projektmitglieder haben Zugriff)</option>
              <option value="custom">Eingeschränkt (Nur Owner & explizit berechtigte Personen)</option>
            </select>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showNewListModal = false"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition"
            >
              Liste anlegen
            </button>
          </div>
        </form>
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

          <!-- Dynamic Folder Custom Fields -->
          <div v-if="fields.length > 0" class="pt-4 border-t border-slate-800 space-y-3">
            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">
              ⚙️ Benutzerdefinierte Felder (Ordner-Vererbung)
            </h4>
            <div v-for="f in fields" :key="f.id">
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

const currentView = ref<'board' | 'journal' | 'team'>('board')

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

const showNewJournalModal = ref(false)
const journalForm = ref<any>({
  entry_type: 'voice',
  title: '',
  content: ''
})

const showInviteMemberModal = ref(false)
const inviteEmail = ref('')
const inviteRole = ref('editor')

// Drag & Drop state
const draggedTask = ref<any>(null)
const sourceListId = ref<string>('')
const dragOverListId = ref<string>('')

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

  if (fromListId === targetListId) {
    return
  }

  // Optimistic UI update
  const sourceList = lists.value.find(l => l.id === fromListId)
  const targetList = lists.value.find(l => l.id === targetListId)

  if (!sourceList || !targetList) return

  sourceList.tasks = (sourceList.tasks || []).filter((t: any) => t.id !== taskToMove.id)
  taskToMove.list_id = targetListId
  targetList.tasks = targetList.tasks || []
  targetList.tasks.push(taskToMove)

  // Persist to backend
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
    alert(err.data?.statusMessage || 'Konnte Aufgabe nicht verschieben (Berechtigung prüfen)')
    await loadProjectData()
  }
}

const totalTasks = computed(() => {
  return lists.value.reduce((acc, l) => acc + (l.tasks?.length || 0), 0)
})

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
    alert(err.data?.statusMessage || 'Fehler beim Erstellen der Liste')
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
    journalForm.value = { entry_type: 'voice', title: '', content: '' }
    await loadJournals()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern des Eintrags')
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
  await loadJournals()
})
</script>
