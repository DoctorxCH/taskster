<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
      <div>
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-purple-950/80 border border-purple-800 text-purple-300 text-xs font-semibold mb-2">
          <span>⚙️</span>
          <span>Zentrale Site-Administration</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
          Taskster Plattform-Administration
        </h1>
        <p class="text-xs text-slate-400 mt-1">
          Kundenübersicht, Benutzerverwaltung, Company-Pläne, Zugriffsregeln und Systemgrenzen.
        </p>
      </div>

      <div class="flex items-center space-x-3">
        <button
          @click="showCreateCompanyModal = true"
          class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white transition flex items-center space-x-2 shadow-lg shadow-purple-900/30"
        >
          <span>+ Neues Unternehmen anlegen</span>
        </button>
      </div>
    </div>

    <!-- Admin Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[11px] font-semibold text-slate-400 uppercase">Kunden & User</div>
        <div class="text-2xl font-black text-white mt-1">{{ overview?.metrics?.users || 0 }}</div>
        <div class="text-[10px] text-slate-500">Registriert</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[11px] font-semibold text-slate-400 uppercase">Unternehmen</div>
        <div class="text-2xl font-black text-purple-400 mt-1">{{ overview?.metrics?.companies || 0 }}</div>
        <div class="text-[10px] text-slate-500">Organisationen</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[11px] font-semibold text-slate-400 uppercase">Projekte</div>
        <div class="text-2xl font-black text-emerald-400 mt-1">{{ overview?.metrics?.projects || 0 }}</div>
        <div class="text-[10px] text-slate-500">Aktiv</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[11px] font-semibold text-slate-400 uppercase">Aufgaben</div>
        <div class="text-2xl font-black text-cyan-400 mt-1">{{ overview?.metrics?.tasks || 0 }}</div>
        <div class="text-[10px] text-slate-500">In Listen gepflegt</div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
        <div class="text-[11px] font-semibold text-slate-400 uppercase">Journal-Einträge</div>
        <div class="text-2xl font-black text-amber-400 mt-1">{{ overview?.metrics?.journals || 0 }}</div>
        <div class="text-[10px] text-slate-500">Aktivitätsnotizen</div>
      </div>
    </div>

    <!-- Admin Tabs -->
    <div class="flex border-b border-slate-800 mb-6 space-x-6">
      <button
        @click="activeTab = 'users'"
        class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-2"
        :class="activeTab === 'users' ? 'border-purple-500 text-purple-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>👤</span>
        <span>Kunden- & Benutzerverwaltung</span>
      </button>

      <button
        @click="activeTab = 'companies'"
        class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-2"
        :class="activeTab === 'companies' ? 'border-purple-500 text-purple-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>🏢</span>
        <span>Unternehmen & Organisationen</span>
      </button>

      <button
        @click="activeTab = 'policies'"
        class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-2"
        :class="activeTab === 'policies' ? 'border-purple-500 text-purple-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>🛡️</span>
        <span>Zugriffsregeln & Tarif-Limits</span>
      </button>

      <button
        @click="activeTab = 'templates'"
        class="py-3 text-xs font-bold border-b-2 transition flex items-center space-x-2"
        :class="activeTab === 'templates' ? 'border-purple-500 text-purple-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
      >
        <span>📋</span>
        <span>Projekt-Vorlagen (Job & Privat)</span>
      </button>
    </div>


    <!-- TAB 1: USERS & CUSTOMERS -->
    <div v-if="activeTab === 'users'" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
      <div class="p-4 sm:p-6 border-b border-slate-800 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-white">Alle registrierten Kunden und Benutzer</h3>
          <p class="text-xs text-slate-400">Verwalte Berechtigungen, Pro-Status und Firmenzuweisungen.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-800">
            <tr>
              <th class="py-3.5 px-4">Name & E-Mail</th>
              <th class="py-3.5 px-4">Unternehmen / Organisation</th>
              <th class="py-3.5 px-4">Plan & Status</th>
              <th class="py-3.5 px-4">Superadmin</th>
              <th class="py-3.5 px-4 text-right">Aktionen</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80 text-slate-300">
            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-800/40 transition">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-100">{{ u.name }}</div>
                <div class="text-[11px] text-slate-400 font-mono">{{ u.email }}</div>
              </td>
              <td class="py-3.5 px-4">
                <span v-if="u.company_name" class="font-medium text-emerald-400">
                  {{ u.company_name }}
                  <span class="text-[10px] text-slate-500">({{ u.company_role }})</span>
                </span>
                <span v-else class="text-slate-500 italic">Privatkunde (Einzelbenutzer)</span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                  :class="u.is_pro || u.company_name ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400'"
                >
                  {{ u.company_plan || (u.is_pro ? 'PRO' : 'FREE PLAN') }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  v-if="u.is_superadmin"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-950 text-purple-300 border border-purple-800"
                >
                  SUPERADMIN
                </span>
                <span v-else class="text-slate-600">-</span>
              </td>
              <td class="py-3.5 px-4 text-right space-x-2">
                <button
                  @click="toggleUserPro(u)"
                  class="px-2.5 py-1 rounded text-[11px] font-semibold border transition"
                  :class="u.is_pro ? 'border-amber-800 text-amber-300 hover:bg-amber-950/40' : 'border-emerald-800 text-emerald-300 hover:bg-emerald-950/40'"
                >
                  {{ u.is_pro ? 'Pro entziehen' : 'Zu Pro hochstufen' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: COMPANIES & CLIENTS -->
    <div v-else-if="activeTab === 'companies'" class="space-y-6">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-4 sm:p-6 border-b border-slate-800 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-white">Unternehmen, Mandanten & B2B-Kunden</h3>
            <p class="text-xs text-slate-400">Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien.</p>
          </div>
          <button
            @click="showCreateCompanyModal = true"
            class="px-3 py-1.5 rounded-lg text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white transition"
          >
            + Unternehmen anlegen
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 uppercase font-semibold text-[10px] tracking-wider border-b border-slate-800">
              <tr>
                <th class="py-3.5 px-4">Unternehmen</th>
                <th class="py-3.5 px-4">Abo-Plan</th>
                <th class="py-3.5 px-4">Nutzer & Ordner</th>
                <th class="py-3.5 px-4">Dateiuploads (Zero Trust)</th>
                <th class="py-3.5 px-4 text-right">Aktionen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80 text-slate-300">
              <tr v-for="c in companies" :key="c.id" class="hover:bg-slate-800/40 transition">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-100 text-sm">{{ c.name }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">ID: {{ c.id }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <select
                    v-model="c.subscription_plan"
                    @change="updateCompanyPlan(c)"
                    class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-xs text-slate-200 focus:outline-none focus:border-purple-500"
                  >
                    <option value="starter">Starter Plan</option>
                    <option value="pro">Pro Plan</option>
                    <option value="enterprise">Enterprise Plan</option>
                  </select>
                </td>
                <td class="py-3.5 px-4">
                  <span class="text-slate-300 font-medium">{{ c.user_count }} Mitarbeiter</span>
                  <span class="text-slate-500"> / {{ c.folder_count }} Ordner</span>
                </td>
                <td class="py-3.5 px-4">
                  <button
                    @click="toggleCompanyUploads(c)"
                    class="px-2.5 py-1 rounded text-[11px] font-bold border transition flex items-center space-x-1.5"
                    :class="c.settings?.allow_document_upload ? 'bg-emerald-950/60 text-emerald-300 border-emerald-800' : 'bg-rose-950/60 text-rose-300 border-rose-800'"
                  >
                    <span>{{ c.settings?.allow_document_upload ? '✓ Erlaubt' : '🚫 Upload gesperrt (Policy)' }}</span>
                  </button>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <span class="text-[11px] text-purple-400 font-medium">Aktiv</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB: COMPANY INVITES (Für Company Admins) -->
    <div v-if="activeTab === 'invites'" class="space-y-6">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white">Mitarbeiter zu {{ user?.company_name }} einladen</h3>
            <p class="text-xs text-slate-400 mt-1">
              Bereits registrierte Nutzer werden sofort dem Unternehmen zugewiesen. Nicht registrierte Nutzer erhalten einen Registrierungslink und treten nach der Registrierung automatisch bei.
            </p>
          </div>
        </div>

        <!-- Invite Form -->
        <form @submit.prevent="sendCompanyInvite" class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mb-8">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">E-Mail-Adresse</label>
            <input
              v-model="inviteEmail"
              type="email"
              required
              placeholder="mitarbeiter@domain.ch"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Rolle im Unternehmen</label>
            <select
              v-model="inviteRole"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-purple-500"
            >
              <option value="member">Mitglied (Member)</option>
              <option value="admin">Company Administrator</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              type="submit"
              :disabled="sendingInvite"
              class="w-full py-2 px-4 rounded-lg font-bold text-xs bg-purple-600 hover:bg-purple-500 text-white transition disabled:opacity-50"
            >
              {{ sendingInvite ? 'Sende...' : 'Einladung absenden' }}
            </button>
          </div>
        </form>

        <!-- Success link box -->
        <div v-if="lastInviteLink" class="p-4 rounded-xl bg-purple-950/60 border border-purple-800 text-xs mb-6">
          <div class="font-bold text-purple-300 mb-1">Einladung erfolgreich generiert!</div>
          <div class="text-slate-300 mb-2">Für nicht registrierte Nutzer kann dieser direkte Registrierungslink weitergegeben werden:</div>
          <div class="flex items-center space-x-2">
            <input
              readonly
              :value="lastInviteLink"
              class="flex-1 px-3 py-1.5 bg-slate-950 border border-slate-700 rounded text-xs font-mono text-emerald-400 select-all"
            />
            <button
              type="button"
              @click="copyInviteLink"
              class="px-3 py-1.5 rounded bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold"
            >
              Kopieren
            </button>
          </div>
        </div>

        <!-- Pending Invites List -->
        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Offene Einladungen</h4>
        <div v-if="pendingInvites.length === 0" class="text-xs text-slate-500 py-4 text-center border border-dashed border-slate-800 rounded-xl">
          Keine offenen Einladungen vorhanden.
        </div>
        <div v-else class="divide-y divide-slate-800/80 border border-slate-800 rounded-xl overflow-hidden">
          <div v-for="inv in pendingInvites" :key="inv.id" class="p-3 bg-slate-950 flex items-center justify-between text-xs">
            <div>
              <div class="font-bold text-slate-200">{{ inv.email }}</div>
              <div class="text-[10px] text-slate-500">Rolle: {{ inv.role }} • Erstellt: {{ new Date(inv.created_at).toLocaleDateString('de-CH') }}</div>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-950 text-amber-300 border border-amber-800">
              {{ inv.status }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: TASK LOGIC & ZERO-TRUST COMPLIANCE -->
    <div v-else-if="activeTab === 'policies'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 4-Stage Pipeline Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
          <div class="flex items-center space-x-2 text-purple-400 text-xs font-bold uppercase tracking-wider mb-2">
            <span>🛡️</span>
            <span>Rollen- & Berechtigungsmodell</span>
          </div>
          <h3 class="text-lg font-bold text-white mb-3">Aktive Sicherheitsarchitektur</h3>
          <p class="text-xs text-slate-400 leading-relaxed mb-6">
            Jeder API-Zugriff durchläuft serverseitig diese 4 Prüfstufen:
          </p>

          <ol class="space-y-3 text-xs">
            <li class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-start space-x-3">
              <span class="w-6 h-6 rounded-full bg-purple-950 text-purple-300 flex items-center justify-center font-bold text-[11px]">1</span>
              <div>
                <strong class="text-white">Company Policy Check:</strong>
                <p class="text-slate-400 mt-0.5">Prüft globale Unternehmensrichtlinien (z.B. Dokumenten-Upload-Sperre). Verstoß liefert 403 Forbidden.</p>
              </div>
            </li>
            <li class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-start space-x-3">
              <span class="w-6 h-6 rounded-full bg-purple-950 text-purple-300 flex items-center justify-center font-bold text-[11px]">2</span>
              <div>
                <strong class="text-white">Project Membership Check:</strong>
                <p class="text-slate-400 mt-0.5">Prüft Ordnerinhaber oder Projektmitgliedschaft. Nicht berechtigte Anfragen erhalten 404 Not Found.</p>
              </div>
            </li>
            <li class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-start space-x-3">
              <span class="w-6 h-6 rounded-full bg-purple-950 text-purple-300 flex items-center justify-center font-bold text-[11px]">3</span>
              <div>
                <strong class="text-white">List Scope Check:</strong>
                <p class="text-slate-400 mt-0.5">Eingeschränkte Listen erfordern explizite Listensichtbarkeit.</p>
              </div>
            </li>
            <li class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-start space-x-3">
              <span class="w-6 h-6 rounded-full bg-purple-950 text-purple-300 flex items-center justify-center font-bold text-[11px]">4</span>
              <div>
                <strong class="text-white">Role Action Check:</strong>
                <p class="text-slate-400 mt-0.5">Viewer dürfen nur Lesemethoden (GET) nutzen. Schreibzugriffe werden serverseitig abgewiesen.</p>
              </div>
            </li>
          </ol>
        </div>

        <!-- Freemium Rules & App Logic -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
          <div class="flex items-center space-x-2 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
            <span>⚙️</span>
            <span>Tarifregeln & Systemgrenzen</span>
          </div>
          <h3 class="text-lg font-bold text-white mb-3">Durchgesetzte Systemgrenzen</h3>
          <p class="text-xs text-slate-400 leading-relaxed mb-6">
            Folgende Systemgrenzen sind im Backend aktiv durchgesetzt:
          </p>

          <div class="space-y-3 text-xs">
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-white">Free-Plan Ordner-Limit</strong>
                <span class="px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 font-mono font-bold">1 Ordner</span>
              </div>
              <p class="text-slate-400">Ein Kunde im Free-Plan hat maximal 1 Projektordner zur Verfügung.</p>
            </div>

            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-white">Gleichzeitige Projekt-Mitarbeit</strong>
                <span class="px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 font-mono font-bold">Max. 3</span>
              </div>
              <p class="text-slate-400">Free-User dürfen maximal in 3 Projekten gleichzeitig aktiv mitarbeiten.</p>
            </div>

            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-white">Teammitglieder pro Projekt</strong>
                <span class="px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 font-mono font-bold">Max. 5</span>
              </div>
              <p class="text-slate-400">Pro Projekt können im Free-Plan maximal 5 Teammitglieder inkl. Owner teilnehmen.</p>
            </div>

            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-white">Company Plan Vererbung</strong>
                <span class="px-2 py-0.5 rounded bg-purple-950 text-purple-400 font-mono font-bold">Automatisch</span>
              </div>
              <p class="text-slate-400">Eingeladene Mitarbeiter einer Company erben automatisch den bezahlten Company-Plan.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: PROJECT TEMPLATES -->
    <div v-if="activeTab === 'templates'" class="space-y-6">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-bold text-white flex items-center space-x-2">
            <span>📋</span>
            <span>Projekt-Vorlagen (Gewerbe, Jobs & Privat)</span>
          </h3>
          <p class="text-xs text-slate-400 mt-1 max-w-2xl">
            Verwalte strukturierte Vorlagen mit Standard-Listen und benutzerdefinierten Feldern inklusive bedingter IF-THEN-Logik. Benutzer können diese beim Erstellen eines neuen Projekts auswählen.
          </p>
        </div>

        <button
          @click="openCreateTemplateModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg"
        >
          <span>+ Neue Vorlage erstellen</span>
        </button>
      </div>

      <!-- Filter and Search Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-2 w-full sm:w-auto">
          <button
            @click="templateCategoryFilter = 'all'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition"
            :class="templateCategoryFilter === 'all' ? 'bg-purple-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
          >
            Alle Vorlagen ({{ templates.length }})
          </button>
          <button
            @click="templateCategoryFilter = 'job'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-1.5"
            :class="templateCategoryFilter === 'job' ? 'bg-blue-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
          >
            <span>💼</span>
            <span>Job & Gewerbe ({{ templates.filter(t => t.category === 'job').length }})</span>
          </button>
          <button
            @click="templateCategoryFilter = 'private'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-1.5"
            :class="templateCategoryFilter === 'private' ? 'bg-emerald-600 text-white' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'"
          >
            <span>🏡</span>
            <span>Privat ({{ templates.filter(t => t.category === 'private').length }})</span>
          </button>
        </div>

        <div class="w-full sm:w-72">
          <input
            v-model="templateSearch"
            type="text"
            placeholder="Vorlage suchen..."
            class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
          />
        </div>
      </div>

      <!-- Templates Grid -->
      <div v-if="filteredTemplates.length === 0" class="p-12 text-center bg-slate-900 border border-slate-800 rounded-2xl">
        <div class="text-4xl mb-3">🔍</div>
        <h4 class="text-sm font-bold text-white mb-1">Keine Vorlagen gefunden</h4>
        <p class="text-xs text-slate-400 mb-4">Erstelle deine erste Vorlage oder passe den Suchfilter an.</p>
        <button @click="openCreateTemplateModal" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
          + Jetzt Vorlage anlegen
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="tmpl in filteredTemplates"
          :key="tmpl.id"
          class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between space-y-4 hover:border-slate-700 transition"
        >
          <div>
            <div class="flex items-start justify-between gap-3 mb-2">
              <div class="flex items-center space-x-2">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                  :class="tmpl.category === 'job' ? 'bg-blue-950 text-blue-300 border border-blue-800' : 'bg-emerald-950 text-emerald-300 border border-emerald-800'"
                >
                  {{ tmpl.category === 'job' ? '💼 Job / Gewerbe' : '🏡 Privat' }}
                </span>
                <span v-if="tmpl.subcategory" class="px-2 py-0.5 rounded text-[10px] font-mono text-slate-400 bg-slate-950 border border-slate-800">
                  {{ tmpl.subcategory }}
                </span>
              </div>
              <span v-if="tmpl.is_system" class="text-[10px] text-purple-400 font-semibold bg-purple-950/60 border border-purple-900 px-2 py-0.5 rounded">
                System-Vorlage
              </span>
            </div>

            <h4 class="text-base font-bold text-white mb-1">{{ tmpl.name }}</h4>
            <p class="text-xs text-slate-400 leading-relaxed line-clamp-2 mb-4">
              {{ tmpl.description || 'Keine Beschreibung angegeben.' }}
            </p>

            <!-- Pre-configured Lists -->
            <div class="mb-3">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                <span>Vordefinierte Abschnitte / Listen ({{ tmpl.lists?.length || 0 }})</span>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(lst, i) in tmpl.lists"
                  :key="i"
                  class="px-2 py-0.5 rounded text-[11px] bg-slate-950 border border-slate-800 text-slate-300"
                >
                  {{ lst }}
                </span>
              </div>
            </div>

            <!-- Custom Fields & Logic -->
            <div>
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                <span>Benutzerdefinierte Felder ({{ tmpl.fields?.length || 0 }})</span>
              </div>
              <div class="space-y-1.5">
                <div
                  v-for="(f, i) in tmpl.fields"
                  :key="i"
                  class="flex items-center justify-between p-2 rounded-lg bg-slate-950 border border-slate-800/80 text-xs"
                >
                  <div class="flex items-center space-x-2">
                    <span class="font-medium text-slate-200">{{ f.label }}</span>
                    <span class="text-[10px] font-mono text-slate-500">({{ f.field_key }})</span>
                    <span class="text-slate-400 font-mono">[{{ f.field_type }}]</span>
                    <span
                      class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded"
                      :class="f.entity_type === 'project' ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-emerald-950 text-emerald-300 border border-emerald-800'"
                    >
                      {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                    </span>
                  </div>

                  <span
                    v-if="f.logic_rules && f.logic_rules.depends_on_field"
                    class="text-[10px] text-amber-300 font-mono bg-amber-950/60 border border-amber-900/60 px-2 py-0.5 rounded"
                    :title="`Nur sichtbar wenn ${f.logic_rules.depends_on_field} == ${f.logic_rules.depends_on_value}`"
                  >
                    ⚡ Wenn {{ f.logic_rules.depends_on_field }} == "{{ f.logic_rules.depends_on_value }}"
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-800">
            <button
              @click="openEditTemplateModal(tmpl)"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Bearbeiten
            </button>
            <button
              @click="deleteTemplate(tmpl.id)"
              class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg"
            >
              Löschen
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Create / Edit Template -->
    <div v-if="showTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-sm overflow-y-auto">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-2xl w-full shadow-2xl my-8">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold text-white">
            {{ editingTemplate ? 'Projekt-Vorlage bearbeiten' : 'Neue Projekt-Vorlage erstellen' }}
          </h3>
          <button @click="showTemplateModal = false" class="text-slate-400 hover:text-white text-sm">✕</button>
        </div>

        <form @submit.prevent="saveTemplate" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Vorlagen-Name *</label>
              <input
                v-model="tmplForm.name"
                type="text"
                required
                placeholder="z.B. Bauleitung Tiefbau & LWL"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Kategorie *</label>
              <select
                v-model="tmplForm.category"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-purple-500"
              >
                <option value="job">💼 Job / Gewerbe</option>
                <option value="private">🏡 Privat / Persönlich</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Unterkategorie / Branche</label>
              <input
                v-model="tmplForm.subcategory"
                type="text"
                placeholder="z.B. bau, it, handwerk, renovierung, event"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Beschreibung</label>
              <input
                v-model="tmplForm.description"
                type="text"
                placeholder="Kurze Zusammenfassung des Einsatzbereichs"
                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>
          </div>

          <!-- Lists / Sections Editor -->
          <div class="pt-3 border-t border-slate-800">
            <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1">
              Vordefinierte Abschnitte / Listen
            </label>
            <p class="text-[11px] text-slate-400 mb-2">
              Diese Listen werden automatisch angelegt, wenn ein Projekt mit dieser Vorlage erstellt wird.
            </p>

            <div class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="(lst, idx) in tmplForm.lists"
                :key="idx"
                class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-lg bg-slate-950 border border-slate-700 text-xs text-slate-200"
              >
                <span>{{ lst }}</span>
                <button type="button" @click="tmplForm.lists.splice(idx, 1)" class="text-rose-400 hover:text-rose-300 text-xs">✕</button>
              </span>
            </div>

            <div class="flex items-center space-x-2">
              <input
                v-model="newTmplListInput"
                type="text"
                placeholder="Neuen Abschnitt eingeben (z.B. In Prüfung)..."
                class="flex-1 px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 focus:outline-none focus:border-purple-500"
                @keydown.enter.prevent="addTmplList"
              />
              <button
                type="button"
                @click="addTmplList"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs rounded-lg font-semibold"
              >
                + Hinzufügen
              </button>
            </div>
          </div>

          <!-- Custom Fields Editor -->
          <div class="pt-3 border-t border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <label class="block text-xs font-bold text-white uppercase tracking-wider">
                  Benutzerdefinierte Felder mit Logik
                </label>
                <p class="text-[11px] text-slate-400">
                  Felder für Aufgaben oder das gesamte Projekt inkl. bedingter Abhängigkeiten.
                </p>
              </div>
            </div>

            <!-- Existing fields list -->
            <div v-if="tmplForm.fields.length > 0" class="space-y-2">
              <div
                v-for="(f, idx) in tmplForm.fields"
                :key="idx"
                class="p-2.5 rounded-lg bg-slate-950 border border-slate-800 flex items-center justify-between text-xs"
              >
                <div class="space-y-0.5">
                  <div class="flex items-center space-x-2">
                    <strong class="text-white">{{ f.label }}</strong>
                    <span class="text-slate-500 font-mono text-[11px]">({{ f.field_key }})</span>
                    <span class="text-slate-400 font-mono">[{{ f.field_type }}]</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase" :class="f.entity_type === 'project' ? 'bg-purple-950 text-purple-300' : 'bg-emerald-950 text-emerald-300'">
                      {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                    </span>
                  </div>
                  <div v-if="f.options && f.options.length > 0" class="text-[11px] text-slate-400">
                    Optionen: {{ f.options.join(', ') }}
                  </div>
                  <div v-if="f.logic_rules && f.logic_rules.depends_on_field" class="text-[11px] text-amber-300 font-mono">
                    ⚡ Nur sichtbar wenn {{ f.logic_rules.depends_on_field }} == "{{ f.logic_rules.depends_on_value }}"
                  </div>
                </div>

                <button
                  type="button"
                  @click="tmplForm.fields.splice(idx, 1)"
                  class="text-rose-400 hover:text-rose-300 text-xs px-2 py-1"
                >
                  Löschen
                </button>
              </div>
            </div>

            <!-- New field inputs -->
            <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
              <h5 class="text-xs font-bold text-slate-300">+ Neues Feld zur Vorlage hinzufügen</h5>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                  <label class="block text-[10px] text-slate-400 mb-1">Feldbezeichnung</label>
                  <input
                    v-model="newField.label"
                    type="text"
                    placeholder="z.B. OTDR Messung"
                    class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-100"
                  />
                </div>
                <div>
                  <label class="block text-[10px] text-slate-400 mb-1">Feldtyp</label>
                  <select
                    v-model="newField.field_type"
                    class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-100"
                  >
                    <option value="text">Textzeile</option>
                    <option value="number">Zahl / Währung</option>
                    <option value="select">Auswahlliste (Dropdown)</option>
                    <option value="date">Datum</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] text-slate-400 mb-1">Ebene</label>
                  <select
                    v-model="newField.entity_type"
                    class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-100"
                  >
                    <option value="task">Aufgaben-Feld</option>
                    <option value="project">Projekt-Feld</option>
                  </select>
                </div>
              </div>

              <div v-if="newField.field_type === 'select'">
                <label class="block text-[10px] text-slate-400 mb-1">Dropdown-Optionen (Komma-getrennt)</label>
                <input
                  v-model="newFieldOptionsInput"
                  type="text"
                  placeholder="Ja, Nein, Ausstehend"
                  class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-100"
                />
              </div>

              <!-- Conditional Logic Controls -->
              <div class="pt-2 border-t border-slate-800">
                <label class="flex items-center space-x-2 cursor-pointer mb-2">
                  <input
                    type="checkbox"
                    v-model="newFieldHasLogic"
                    class="rounded border-slate-700 text-purple-600 focus:ring-0"
                  />
                  <span class="text-xs text-amber-300 font-semibold">⚡ Bedingte Sichtbarkeit (Abhängig von anderem Feld)</span>
                </label>

                <div v-if="newFieldHasLogic" class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2.5 rounded-lg bg-amber-950/20 border border-amber-900/40">
                  <div>
                    <label class="block text-[10px] text-amber-200 mb-1">Abhängig von Feld-Key</label>
                    <select
                      v-model="newFieldLogicDepField"
                      class="w-full px-2 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-100"
                    >
                      <option value="">-- Feld auswählen --</option>
                      <option v-for="f in tmplForm.fields" :key="f.field_key" :value="f.field_key">
                        {{ f.label }} ({{ f.field_key }})
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-[10px] text-amber-200 mb-1">Erwarteter Wert</label>
                    <input
                      v-model="newFieldLogicExpectedVal"
                      type="text"
                      placeholder="z.B. LWL / Spleissen"
                      class="w-full px-2 py-1.5 bg-slate-900 border border-slate-700 rounded text-xs text-slate-100"
                    />
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="button"
                  @click="addFieldToTemplate"
                  class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded text-xs font-bold transition"
                >
                  + Feld hinzufügen
                </button>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="showTemplateModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Vorlage speichern
            </button>
          </div>
        </form>
      </div>
    </div>


    <!-- Modal: Create Company -->
    <div v-if="showCreateCompanyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Neues Unternehmen / Mandanten anlegen</h3>
        <p class="text-xs text-slate-400 mb-4">
          Erstellt ein Unternehmens-Profil mit Company Admin und initialem Hauptordner.
        </p>

        <form @submit.prevent="createCompany" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Name des Unternehmens</label>
            <input
              v-model="newCompanyName"
              type="text"
              required
              placeholder="z.B. Acme Solutions AG"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Subscription-Plan</label>
            <select
              v-model="newCompanyPlan"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-purple-500"
            >
              <option value="starter">Starter Plan</option>
              <option value="pro">Pro Plan</option>
              <option value="enterprise">Enterprise Plan</option>
            </select>
          </div>

          <div class="pt-2 border-t border-slate-800">
            <h4 class="text-xs font-bold text-purple-300 mb-2">Company Admin Zugangsdaten</h4>
            <div class="space-y-3">
              <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">Name des Admins</label>
                <input
                  v-model="newCompanyAdminName"
                  type="text"
                  required
                  placeholder="Beat Meier"
                  class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
                />
              </div>

              <div>
                <label class="block text-[11px] font-medium text-slate-300 mb-1">E-Mail des Admins</label>
                <input
                  v-model="newCompanyAdminEmail"
                  type="email"
                  required
                  placeholder="beat.meier@firma.ch"
                  class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:border-purple-500"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showCreateCompanyModal = false"
              class="px-4 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="px-4 py-2 rounded-lg text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white transition"
            >
              Unternehmen erstellen
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, authHeaders } = useAuth()

const activeTab = ref<'users' | 'companies' | 'invites' | 'policies' | 'templates'>('users')
const overview = ref<any>(null)
const users = ref<any[]>([])
const companies = ref<any[]>([])
const loading = ref(true)

const templates = ref<any[]>([])
const templateCategoryFilter = ref('all')
const templateSearch = ref('')
const showTemplateModal = ref(false)
const editingTemplate = ref<any>(null)

const tmplForm = ref({
  name: '',
  category: 'job',
  subcategory: '',
  description: '',
  icon: 'Folder',
  lists: [] as string[],
  fields: [] as any[]
})

const newTmplListInput = ref('')
const newField = ref({
  label: '',
  field_type: 'text',
  entity_type: 'task'
})
const newFieldOptionsInput = ref('')
const newFieldHasLogic = ref(false)
const newFieldLogicDepField = ref('')
const newFieldLogicExpectedVal = ref('')

const filteredTemplates = computed(() => {
  return templates.value.filter((t: any) => {
    const matchCat = templateCategoryFilter.value === 'all' || t.category === templateCategoryFilter.value
    const q = templateSearch.value.toLowerCase().trim()
    const matchSearch = !q || (t.name?.toLowerCase().includes(q) || t.description?.toLowerCase().includes(q) || t.subcategory?.toLowerCase().includes(q))
    return matchCat && matchSearch
  })
})

const openCreateTemplateModal = () => {
  editingTemplate.value = null
  tmplForm.value = {
    name: '',
    category: 'job',
    subcategory: '',
    description: '',
    icon: 'Folder',
    lists: ['Planung', 'In Bearbeitung', 'Abnahme', 'Erledigt'],
    fields: []
  }
  newTmplListInput.value = ''
  resetNewField()
  showTemplateModal.value = true
}

const openEditTemplateModal = (tmpl: any) => {
  editingTemplate.value = tmpl
  tmplForm.value = {
    name: tmpl.name,
    category: tmpl.category,
    subcategory: tmpl.subcategory || '',
    description: tmpl.description || '',
    icon: tmpl.icon || 'Folder',
    lists: [...(tmpl.lists || [])],
    fields: JSON.parse(JSON.stringify(tmpl.fields || []))
  }
  newTmplListInput.value = ''
  resetNewField()
  showTemplateModal.value = true
}

const resetNewField = () => {
  newField.value = { label: '', field_type: 'text', entity_type: 'task' }
  newFieldOptionsInput.value = ''
  newFieldHasLogic.value = false
  newFieldLogicDepField.value = ''
  newFieldLogicExpectedVal.value = ''
}

const addTmplList = () => {
  const l = newTmplListInput.value.trim()
  if (l && !tmplForm.value.lists.includes(l)) {
    tmplForm.value.lists.push(l)
    newTmplListInput.value = ''
  }
}

const addFieldToTemplate = () => {
  const lbl = newField.value.label.trim()
  if (!lbl) return alert('Bitte Feldbezeichnung eingeben')
  const key = lbl.toLowerCase().replace(/[^a-z0-9_]/g, '_')

  let options: string[] = []
  if (newField.value.field_type === 'select') {
    options = newFieldOptionsInput.value.split(',').map(s => s.trim()).filter(Boolean)
  }

  let logicRules: any = null
  if (newFieldHasLogic.value && newFieldLogicDepField.value) {
    logicRules = {
      depends_on_field: newFieldLogicDepField.value,
      depends_on_value: newFieldLogicExpectedVal.value.trim()
    }
  }

  tmplForm.value.fields.push({
    field_key: key,
    label: lbl,
    field_type: newField.value.field_type,
    entity_type: newField.value.entity_type,
    options,
    logic_rules: logicRules,
    is_required: false
  })

  resetNewField()
}

const saveTemplate = async () => {
  try {
    if (editingTemplate.value) {
      await $fetch(`/api/templates/${editingTemplate.value.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: tmplForm.value
      })
      alert('Vorlage erfolgreich aktualisiert!')
    } else {
      await $fetch('/api/templates', {
        method: 'POST',
        headers: authHeaders(),
        body: tmplForm.value
      })
      alert('Vorlage erfolgreich erstellt!')
    }
    showTemplateModal.value = false
    await loadAdminData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern der Vorlage')
  }
}

const deleteTemplate = async (id: string) => {
  if (!confirm('Möchtest du diese Vorlage wirklich löschen?')) return
  try {
    await $fetch(`/api/templates/${id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadAdminData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Löschen der Vorlage')
  }
}

const inviteEmail = ref('')
const inviteRole = ref('member')
const sendingInvite = ref(false)
const lastInviteLink = ref('')
const pendingInvites = ref<any[]>([])

const showCreateCompanyModal = ref(false)
const newCompanyName = ref('')
const newCompanyPlan = ref('starter')
const newCompanyAdminName = ref('')
const newCompanyAdminEmail = ref('')

const loadAdminData = async () => {
  loading.value = true
  try {
    const promises: Promise<any>[] = [
      $fetch<any>('/api/admin/overview', { headers: authHeaders() }),
      $fetch<any>('/api/admin/users', { headers: authHeaders() }),
      $fetch<any>('/api/admin/companies', { headers: authHeaders() }),
      $fetch<any>('/api/templates', { headers: authHeaders() })
    ]
    if (user.value?.company_id && user.value?.company_role === 'admin') {
      promises.push($fetch<any>('/api/companies/invitations', { headers: authHeaders() }))
    }

    const results = await Promise.all(promises)
    overview.value = results[0]
    users.value = results[1].users || []
    companies.value = results[2].companies || []
    templates.value = results[3].templates || []
    if (results[4]) {
      pendingInvites.value = results[4].invitations || []
    }
  } catch (err: any) {
    if (err.statusCode === 403 || err.statusCode === 401) {
      alert('Zugriff nur für autorisierte Administratoren gestattet.')
      navigateTo('/dashboard')
    }
  } finally {
    loading.value = false
  }
}

const sendCompanyInvite = async () => {
  sendingInvite.value = true
  lastInviteLink.value = ''
  try {
    const res = await $fetch<any>('/api/companies/members', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        email: inviteEmail.value,
        role: inviteRole.value
      }
    })
    if (res.action === 'added') {
      alert(`Benutzer ${inviteEmail.value} war bereits registriert und wurde dem Unternehmen sofort hinzugefügt!`)
    } else if (res.action === 'invited') {
      lastInviteLink.value = `${window.location.origin}/login?token=${res.token}`
    }
    inviteEmail.value = ''
    await loadAdminData()
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Senden der Einladung')
  } finally {
    sendingInvite.value = false
  }
}

const copyInviteLink = () => {
  if (lastInviteLink.value && navigator.clipboard) {
    navigator.clipboard.writeText(lastInviteLink.value)
    alert('Einladungslink in die Zwischenablage kopiert!')
  }
}

const toggleUserPro = async (targetUser: any) => {
  try {
    await $fetch(`/api/admin/users/${targetUser.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: { is_pro: !targetUser.is_pro }
    })
    targetUser.is_pro = !targetUser.is_pro
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Ändern des Pro-Status')
  }
}

const updateCompanyPlan = async (c: any) => {
  try {
    await $fetch(`/api/admin/companies/${c.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: { subscription_plan: c.subscription_plan }
    })
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Aktualisieren des Plans')
  }
}

const toggleCompanyUploads = async (c: any) => {
  try {
    const currentSettings = c.settings || {}
    const newAllowed = !currentSettings.allow_document_upload
    const updatedSettings = { ...currentSettings, allow_document_upload: newAllowed }

    await $fetch(`/api/admin/companies/${c.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: { settings: updatedSettings }
    })

    c.settings = updatedSettings
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Aktualisieren der Upload-Policy')
  }
}

const createCompany = async () => {
  try {
    await $fetch('/api/admin/companies', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        name: newCompanyName.value,
        subscription_plan: newCompanyPlan.value,
        admin_name: newCompanyAdminName.value,
        admin_email: newCompanyAdminEmail.value
      }
    })
    showCreateCompanyModal.value = false
    newCompanyName.value = ''
    newCompanyAdminName.value = ''
    newCompanyAdminEmail.value = ''
    await loadAdminData()
    alert('Unternehmen und Admin erfolgreich erstellt!')
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Erstellen des Unternehmens')
  }
}

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (!user.value?.is_superadmin) {
    navigateTo('/dashboard')
    return
  }
  await loadAdminData()
})
</script>
