<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500 mb-6 font-medium">
      <NuxtLink to="/dashboard" class="hover:text-cyan-600 transition">Dashboard</NuxtLink>
      <span>/</span>
      <span class="text-slate-800 font-semibold">{{ folder?.name || 'Ordner' }}</span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-400 text-xs">
      Lade Ordnerdetails und Projekte...
    </div>

    <div v-else-if="folder">
      <!-- Folder Header Banner (Fresh White Card with subtle cyan tint) -->
      <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 mb-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center space-x-3 mb-2">
              <div class="w-12 h-12 rounded-2xl bg-cyan-50 border border-cyan-100 flex items-center justify-center text-2xl">
                {{ folder.icon || '📁' }}
              </div>
              <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ folder.name }}</h1>
            </div>
            <p class="text-xs text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1">
              <span>Owner: <strong class="text-slate-800">{{ folder.owner_name }}</strong></span>
              <span v-if="user?.id === folder.owner_id" class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200 font-bold">
                Du (Owner)
              </span>
              <span v-if="folder.company_name" class="text-teal-700 font-semibold">• {{ folder.company_name }}</span>
              <span>• Erstellt am {{ new Date(folder.created_at).toLocaleDateString('de-CH') }}</span>
            </p>
          </div>

          <div class="flex items-center space-x-3">
            <button
              v-if="user?.id === folder.owner_id || user?.is_superadmin"
              @click="openEditFolderModal"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
              title="Projektordner anpassen (Name & Icon)"
            >
              <span>✏️</span>
              <span>Ordner anpassen</span>
            </button>
            <button
              @click="openNewProjectModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>+ Neues Projekt</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Projects Section -->
      <div class="mb-12">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div class="flex items-center space-x-3">
            <h2 class="text-lg font-black text-slate-900 tracking-tight flex items-center space-x-2">
              <span>📋</span>
              <span>Projekte in diesem Ordner</span>
            </h2>
            <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-700 font-bold">
              {{ projects.length }}
            </span>
          </div>

          <!-- View Mode Toggle & Actions -->
          <div class="flex items-center space-x-3">
            <div class="bg-white border border-slate-200 rounded-xl p-1 flex items-center space-x-1 shadow-sm">
              <button
                @click="projectViewMode = 'grid'"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5"
                :class="projectViewMode === 'grid' ? 'bg-cyan-50 text-cyan-700 font-extrabold shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                title="Kachel-Ansicht"
              >
                <span>▦</span>
                <span>Kacheln</span>
              </button>
              <button
                @click="projectViewMode = 'list'"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5"
                :class="projectViewMode === 'list' ? 'bg-cyan-50 text-cyan-700 font-extrabold shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                title="Listen- / Tabellenansicht"
              >
                <span>☰</span>
                <span>Liste</span>
              </button>
            </div>

            <button
              @click="openNewProjectModal"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>+ Neues Projekt</span>
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="projects.length === 0" class="text-center py-16 px-6 bg-white rounded-3xl border border-dashed border-slate-300 shadow-sm max-w-lg mx-auto">
          <div class="w-16 h-16 mx-auto rounded-2xl overflow-hidden shadow-sm mb-3">
            <img src="/wallpapers/bamboo-forest.jpg" alt="Keine Projekte" class="w-full h-full object-cover" />
          </div>
          <h3 class="text-base font-bold text-slate-800">Noch keine Projekte in diesem Ordner</h3>
          <p class="text-xs text-slate-500 mt-1 mb-5 leading-relaxed">
            Erstelle jetzt dein erstes Projekt – z.B. aus einer unserer Vorlagen mit vorgefertigten Phasen.
          </p>
          <button
            @click="openNewProjectModal"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg"
          >
            <span>+ Neues Projekt anlegen</span>
          </button>
        </div>

        <!-- VIEW MODE 1: GRID / KACHELN -->
        <div v-else-if="projectViewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="project in projects"
            :key="project.id"
            class="bg-white border border-slate-200/90 hover:border-cyan-400 rounded-3xl p-6 transition-all duration-200 flex flex-col justify-between group shadow-sm hover:shadow-md"
          >
            <div>
              <div class="flex items-start justify-between mb-3">
                <span class="text-2xl">📋</span>
                <span
                  class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
                  :class="project.status === 'completed' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                >
                  {{ project.status }}
                </span>
              </div>

              <h3 class="text-base font-black text-slate-900 group-hover:text-cyan-600 transition mb-2">
                {{ project.title }}
              </h3>

              <!-- Project Custom Fields chips -->
              <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-1.5 mb-3">
                <span
                  v-for="(val, key) in project.custom_data"
                  :key="key"
                  class="text-[10px] px-2 py-0.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 font-medium"
                >
                  <strong class="text-cyan-700">{{ getFieldLabel(key) }}:</strong> {{ val }}
                </span>
              </div>

              <div class="grid grid-cols-3 gap-2 py-3 border-y border-slate-100 my-3 text-center">
                <div>
                  <div class="text-[10px] text-slate-400 uppercase font-bold">Abschnitte</div>
                  <div class="text-sm font-black text-slate-800">{{ project.list_count }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-slate-400 uppercase font-bold">Aufgaben</div>
                  <div class="text-sm font-black text-slate-800">{{ project.task_count }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-slate-400 uppercase font-bold">Team</div>
                  <div class="text-sm font-black text-slate-800">{{ project.member_count }}</div>
                </div>
              </div>
            </div>

            <div class="pt-2 flex items-center justify-end">
              <NuxtLink
                :to="`/projects/${project.id}`"
                class="w-full text-center py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-[#00A3C4] hover:text-white text-slate-800 transition"
              >
                Projekt öffnen →
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- VIEW MODE 2: LIST / TABELLE -->
        <div v-else class="bg-white border border-slate-200/90 rounded-3xl overflow-hidden shadow-sm">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4">Projekttitel</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4">Abschnitte & Aufgaben</th>
                  <th class="py-3.5 px-4">Projekt-Felder</th>
                  <th class="py-3.5 px-4 text-right">Aktion</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="project in projects" :key="project.id" class="hover:bg-slate-50 transition">
                  <td class="py-3.5 px-4">
                    <NuxtLink :to="`/projects/${project.id}`" class="font-bold text-slate-900 hover:text-cyan-600 transition text-sm">
                      {{ project.title }}
                    </NuxtLink>
                  </td>
                  <td class="py-3.5 px-4">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                      :class="project.status === 'completed' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                    >
                      {{ project.status }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="text-slate-800 font-bold">{{ project.task_count }} Aufgaben</span>
                    <span class="text-slate-500"> in {{ project.list_count }} Abschnitten</span>
                  </td>
                  <td class="py-3.5 px-4">
                    <div v-if="project.custom_data && Object.keys(project.custom_data).length > 0" class="flex flex-wrap gap-1">
                      <span
                        v-for="(val, key) in project.custom_data"
                        :key="key"
                        class="text-[10px] px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200 text-slate-700"
                      >
                        {{ getFieldLabel(key) }}: <strong class="text-slate-900">{{ val }}</strong>
                      </span>
                    </div>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <NuxtLink
                      :to="`/projects/${project.id}`"
                      class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-[#00A3C4] hover:bg-[#008ba8] text-white shadow-sm transition inline-block"
                    >
                      Öffnen →
                    </NuxtLink>
                  </td>
                </tr>
              </tbody>
            </table>
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
          <!-- Creation Mode Selector (Template vs Blank) -->
          <div class="grid grid-cols-2 gap-3 p-1 bg-slate-100 rounded-2xl">
            <button
              type="button"
              @click="useTemplateMode = true"
              class="flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="useTemplateMode ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            >
              <span>📋</span>
              <span>Aus Vorlage erstellen (Empfohlen)</span>
            </button>
            <button
              type="button"
              @click="useTemplateMode = false; selectedTemplateId = null"
              class="flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="!useTemplateMode ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
            >
              <span>📝</span>
              <span>Leeres Projekt (Blanko)</span>
            </button>
          </div>

          <!-- SECTION A: TEMPLATE BROWSER -->
          <div v-if="useTemplateMode" class="space-y-4">
            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
              <!-- Category Pills -->
              <div class="flex items-center space-x-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                <button
                  type="button"
                  @click="templateFilterCategory = 'all'"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap cursor-pointer"
                  :class="templateFilterCategory === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:text-slate-900'"
                >
                  Alle Vorlagen ({{ templates.length }})
                </button>
                <button
                  type="button"
                  @click="templateFilterCategory = 'job'"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                  :class="templateFilterCategory === 'job' ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-600 hover:text-cyan-700'"
                >
                  <span>💼</span>
                  <span>Job & Gewerbe</span>
                </button>
                <button
                  type="button"
                  @click="templateFilterCategory = 'private'"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center space-x-1.5 cursor-pointer"
                  :class="templateFilterCategory === 'private' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:text-purple-700'"
                >
                  <span>🏠</span>
                  <span>Privat</span>
                </button>
              </div>

              <!-- Search -->
              <div class="w-full sm:w-64">
                <input
                  v-model="templateSearchQuery"
                  type="text"
                  placeholder="🔍 Vorlage suchen..."
                  class="w-full px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
                />
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loadingTemplates" class="py-12 text-center text-xs text-slate-400">
              Vorlagen werden geladen...
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredTemplates.length === 0" class="py-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-xs text-slate-500">
              Keine Vorlagen gefunden.
            </div>

            <!-- Templates Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
              <div
                v-for="tmpl in filteredTemplates"
                :key="tmpl.id"
                @click="selectTemplate(tmpl)"
                class="p-4 rounded-2xl border transition cursor-pointer flex flex-col justify-between text-left"
                :class="selectedTemplateId === tmpl.id
                  ? 'bg-cyan-50/70 border-cyan-500 ring-2 ring-cyan-500/30'
                  : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
              >
                <div>
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2">
                      <span class="text-xl">{{ tmpl.category === 'private' ? '🏠' : (tmpl.icon === 'Network' ? '🌐' : (tmpl.icon === 'Zap' ? '⚡' : '💼')) }}</span>
                      <div>
                        <h4 class="text-xs font-bold text-slate-900">{{ tmpl.name }}</h4>
                        <span v-if="tmpl.subcategory" class="text-[10px] text-slate-500">{{ tmpl.subcategory }}</span>
                      </div>
                    </div>
                    <span
                      class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                      :class="tmpl.category === 'job' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-purple-50 text-purple-700 border border-purple-200'"
                    >
                      {{ tmpl.category === 'job' ? 'Job' : 'Privat' }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 line-clamp-2 mb-3 leading-relaxed">
                    {{ tmpl.description }}
                  </p>
                </div>

                <!-- Badges -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-[10px]">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium">
                    📂 {{ (tmpl.lists || []).length }} Listen
                  </span>
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium">
                    🏷️ {{ (tmpl.fields || tmpl.custom_fields || []).length }} Custom Fields
                  </span>
                  <span
                    v-if="(tmpl.fields || tmpl.custom_fields || []).some((f: any) => f.logic_rules)"
                    class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-medium"
                  >
                    ⚡ Mit Feld-Logik
                  </span>
                </div>
              </div>
            </div>

            <!-- Preview of Selected Template Features -->
            <div v-if="selectedTemplate" class="p-4 rounded-2xl bg-cyan-50/50 border border-cyan-200 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-cyan-800 uppercase tracking-wider flex items-center space-x-1.5">
                  <span>✓ Ausgewählte Vorlage:</span>
                  <span class="text-slate-900 font-black">{{ selectedTemplate.name }}</span>
                </span>
                <span class="text-[11px] text-slate-500">Automatische Konfiguration</span>
              </div>

              <!-- Included Lists -->
              <div>
                <span class="text-[11px] font-bold text-slate-600 block mb-1">Enthaltene Phasen / Abschnitte:</span>
                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="(listName, idx) in selectedTemplate.lists"
                    :key="idx"
                    class="px-2.5 py-1 rounded-lg text-xs bg-white border border-slate-200 text-slate-800 font-medium shadow-sm"
                  >
                    {{ idx + 1 }}. {{ listName }}
                  </span>
                </div>
              </div>

              <!-- Included Custom Fields with Logic -->
              <div v-if="(selectedTemplate.fields || selectedTemplate.custom_fields || []).length > 0">
                <span class="text-[11px] font-bold text-slate-600 block mb-1">Benutzerdefinierte Felder mit Logik:</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div
                    v-for="cf in (selectedTemplate.fields || selectedTemplate.custom_fields || [])"
                    :key="cf.field_key"
                    class="p-2.5 rounded-xl bg-white border border-slate-200 text-xs"
                  >
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-slate-800">{{ cf.label }}</span>
                      <span class="text-[10px] text-slate-400 font-mono">({{ cf.field_type }})</span>
                    </div>
                    <!-- Conditional Logic Badge -->
                    <div v-if="cf.logic_rules" class="mt-1 text-[10px] text-amber-700 flex items-center space-x-1">
                      <span>⚡</span>
                      <span>Nur sichtbar wenn <code class="text-amber-800 font-bold">{{ cf.logic_rules.depends_on_field }}</code> = "{{ cf.logic_rules.depends_on_value }}"</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION B: PROJECT TITLE & GENERAL SETTINGS -->
          <div class="space-y-4 pt-2 border-t border-slate-100">
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

            <!-- If Blanko Mode and folder has existing project fields -->
            <div v-if="!useTemplateMode && projectFields.length > 0" class="space-y-3 pt-3 border-t border-slate-100">
              <h4 class="text-xs font-bold text-cyan-700 uppercase tracking-wider">
                Projekt-Felder dieses Ordners
              </h4>
              <div v-for="f in projectFields" :key="f.id">
                <label class="block text-xs font-bold text-slate-700 mb-1">{{ f.label }}</label>
                <select
                  v-if="f.field_type === 'select'"
                  v-model="newProjectCustomData[f.field_key]"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
                >
                  <option value="">-- Nicht ausgewählt --</option>
                  <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <input
                  v-else
                  v-model="newProjectCustomData[f.field_key]"
                  :type="f.field_type === 'number' ? 'number' : 'text'"
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
              :disabled="creatingProject || (useTemplateMode && !selectedTemplateId) || !newProjectTitle.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span>{{ creatingProject ? 'Wird erstellt...' : (useTemplateMode ? 'Projekt aus Vorlage erstellen' : 'Projekt erstellen') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit Folder (Owner only) -->
    <div v-if="showEditFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">Projektordner anpassen</h3>
          <button @click="showEditFolderModal = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold">✕</button>
        </div>
        <p class="text-xs text-slate-500 mb-5">
          Passe den Namen und das Erkennungs-Icon dieses Projektordners an.
        </p>

        <div v-if="editFolderError" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ editFolderError }}
        </div>

        <form @submit.prevent="updateFolder" class="space-y-4">
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

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
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
              <span>{{ savingFolder ? 'Wird gespeichert...' : 'Änderungen speichern' }}</span>
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
const folderId = route.params.id as string

const folder = ref<any>(null)
const projects = ref<any[]>([])
const fields = ref<any[]>([])
const loading = ref(true)
const projectViewMode = ref<'grid' | 'list'>('grid')

// Folder edit state
const showEditFolderModal = ref(false)
const editFolderName = ref('')
const editFolderIcon = ref('📁')
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

const openEditFolderModal = () => {
  if (!folder.value) return
  editFolderName.value = folder.value.name
  editFolderIcon.value = folder.value.icon || '📁'
  editFolderError.value = ''
  showEditFolderModal.value = true
}

const updateFolder = async () => {
  editFolderError.value = ''
  savingFolder.value = true
  try {
    const res = await $fetch<any>(`/api/folders/${folderId}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        name: editFolderName.value,
        icon: editFolderIcon.value
      }
    })
    if (res?.folder) {
      folder.value.name = res.folder.name
      folder.value.icon = res.folder.icon
    }
    showEditFolderModal.value = false
    await loadFolderData()
  } catch (err: any) {
    editFolderError.value = err.data?.statusMessage || 'Ordner konnte nicht aktualisiert werden'
  } finally {
    savingFolder.value = false
  }
}

// Project creation & Template state
const showNewProjectModal = ref(false)
const newProjectTitle = ref('')
const newProjectCustomData = ref<Record<string, any>>({})
const creatingProject = ref(false)
const projectModalError = ref('')

const templates = ref<any[]>([])
const loadingTemplates = ref(false)
const useTemplateMode = ref(true)
const selectedTemplateId = ref<string | null>(null)
const templateFilterCategory = ref<'all' | 'job' | 'private'>('all')
const templateSearchQuery = ref('')

const selectedTemplate = computed(() => {
  return templates.value.find((t: any) => t.id === selectedTemplateId.value)
})

const filteredTemplates = computed(() => {
  return templates.value.filter((t: any) => {
    const matchCat = templateFilterCategory.value === 'all' || t.category === templateFilterCategory.value
    const q = templateSearchQuery.value.toLowerCase().trim()
    const matchSearch = !q || (
      t.name?.toLowerCase().includes(q) ||
      t.description?.toLowerCase().includes(q) ||
      t.subcategory?.toLowerCase().includes(q)
    )
    return matchCat && matchSearch
  })
})

const projectFields = computed(() => {
  return fields.value.filter((f: any) => f.entity_type === 'project')
})

const getFieldLabel = (key: string) => {
  const f = fields.value.find((item: any) => item.field_key === key)
  return f ? f.label : key
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
  if (!newProjectTitle.value || templates.value.some((t: any) => t.name === newProjectTitle.value)) {
    newProjectTitle.value = tmpl.name
  }
}

const openNewProjectModal = () => {
  showNewProjectModal.value = true
  projectModalError.value = ''
  fetchTemplates()
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
    const payload: any = {
      folder_id: folderId,
      title: newProjectTitle.value,
      custom_data: newProjectCustomData.value
    }
    if (useTemplateMode.value && selectedTemplateId.value) {
      payload.template_id = selectedTemplateId.value
    }
    const res = await $fetch<any>('/api/projects', {
      method: 'POST',
      headers: authHeaders(),
      body: payload
    })
    showNewProjectModal.value = false
    newProjectTitle.value = ''
    newProjectCustomData.value = {}
    selectedTemplateId.value = null
    await loadFolderData()
    if (res?.project?.id) {
      navigateTo(`/projects/${res.project.id}`)
    }
  } catch (err: any) {
    projectModalError.value = err.data?.statusMessage || 'Projekt konnte nicht erstellt werden'
  } finally {
    creatingProject.value = false
  }
}

onMounted(async () => {
  await loadFolderData()
})
</script>
