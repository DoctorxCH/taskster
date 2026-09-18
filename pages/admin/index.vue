<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header in Liquid Glass Card for guaranteed legibility on any wallpaper -->
    <div class="liquid_glass rounded-3xl p-6 sm:p-8 mb-8 shadow-xl">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-300/50 text-purple-900 text-xs font-bold mb-2">
            <span>⚙️</span>
            <span>Zentrale Site-Administration</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Taskster Plattform-Administration
          </h1>
          <p class="text-xs text-slate-600 font-medium mt-1">
            Kundenübersicht, Benutzerverwaltung, Company-Pläne, Zugriffsregeln und Systemgrenzen.
          </p>
        </div>

        <div class="flex items-center space-x-3">
          <button
            @click="showCreateCompanyModal = true"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm"
          >
            <span>+ Neues Unternehmen anlegen</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Admin Metrics (Liquid Glass Pills) -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
      <div class="p-4 rounded-2xl liquid_glass_card text-center">
        <div class="text-[11px] font-bold text-slate-600 uppercase">Kunden & User</div>
        <div class="text-2xl font-black text-slate-900 mt-1">{{ overview?.metrics?.users || 0 }}</div>
        <div class="text-[10px] text-slate-500 font-medium">Registriert</div>
      </div>

      <div class="p-4 rounded-2xl liquid_glass_card text-center">
        <div class="text-[11px] font-bold text-purple-700 uppercase">Unternehmen</div>
        <div class="text-2xl font-black text-purple-900 mt-1">{{ overview?.metrics?.companies || 0 }}</div>
        <div class="text-[10px] text-slate-500 font-medium">Organisationen</div>
      </div>

      <div class="p-4 rounded-2xl liquid_glass_card text-center">
        <div class="text-[11px] font-bold text-emerald-700 uppercase">Projekte</div>
        <div class="text-2xl font-black text-emerald-900 mt-1">{{ overview?.metrics?.projects || 0 }}</div>
        <div class="text-[10px] text-slate-500 font-medium">Aktiv</div>
      </div>

      <div class="p-4 rounded-2xl liquid_glass_card text-center">
        <div class="text-[11px] font-bold text-cyan-700 uppercase">Aufgaben</div>
        <div class="text-2xl font-black text-[#00A3C4] mt-1">{{ overview?.metrics?.tasks || 0 }}</div>
        <div class="text-[10px] text-slate-500 font-medium">In Listen gepflegt</div>
      </div>

      <div class="p-4 rounded-2xl liquid_glass_card text-center">
        <div class="text-[11px] font-bold text-amber-700 uppercase">Journal-Einträge</div>
        <div class="text-2xl font-black text-amber-900 mt-1">{{ overview?.metrics?.journals || 0 }}</div>
        <div class="text-[10px] text-slate-500 font-medium">Aktivitätsnotizen</div>
      </div>
    </div>

    <!-- Admin Tabs inside Liquid Glass Bar for 100% visibility -->
    <div class="liquid_glass_pill rounded-2xl px-4 py-1.5 mb-6 flex items-center space-x-3 overflow-x-auto shadow-sm">
      <button
        @click="activeTab = 'users'"
        class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
        :class="activeTab === 'users' ? 'bg-white text-purple-800 shadow-sm' : 'text-slate-700 hover:text-slate-900 hover:bg-white/50'"
      >
        <span>👤</span>
        <span>Kunden- & Benutzerverwaltung</span>
      </button>

      <button
        @click="activeTab = 'companies'"
        class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
        :class="activeTab === 'companies' ? 'bg-white text-purple-800 shadow-sm' : 'text-slate-700 hover:text-slate-900 hover:bg-white/50'"
      >
        <span>🏢</span>
        <span>Unternehmen & Organisationen</span>
      </button>

      <button
        @click="activeTab = 'policies'"
        class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
        :class="activeTab === 'policies' ? 'bg-white text-purple-800 shadow-sm' : 'text-slate-700 hover:text-slate-900 hover:bg-white/50'"
      >
        <span>🛡️</span>
        <span>Zugriffsregeln & Tarif-Limits</span>
      </button>

      <button
        @click="activeTab = 'templates'"
        class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
        :class="activeTab === 'templates' ? 'bg-white text-purple-800 shadow-sm' : 'text-slate-700 hover:text-slate-900 hover:bg-white/50'"
      >
        <span>📋</span>
        <span>Projekt-Vorlagen (Job & Privat)</span>
      </button>
    </div>


    <!-- TAB 1: USERS & CUSTOMERS (Liquid Glass Table Card) -->
    <div v-if="activeTab === 'users'" class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
      <div class="p-4 sm:p-6 border-b border-slate-200/80 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Alle registrierten Kunden und Benutzer</h3>
          <p class="text-xs text-slate-600 font-medium">Verwalte Berechtigungen, Pro-Status und Firmenzuweisungen.</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
            <tr>
              <th class="py-3.5 px-4">Name & E-Mail</th>
              <th class="py-3.5 px-4">Unternehmen / Organisation</th>
              <th class="py-3.5 px-4">Plan & Status</th>
              <th class="py-3.5 px-4">Superadmin</th>
              <th class="py-3.5 px-4 text-right">Aktionen</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/60 text-slate-800">
            <tr v-for="u in users" :key="u.id" class="hover:bg-white/60 transition">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ u.name }}</div>
                <div class="text-[11px] text-slate-500 font-mono">{{ u.email }}</div>
              </td>
              <td class="py-3.5 px-4">
                <span v-if="u.company_name" class="font-bold text-emerald-800">
                  {{ u.company_name }}
                  <span class="text-[10px] text-slate-600 font-medium">({{ u.company_role }})</span>
                </span>
                <span v-else class="text-slate-500 italic">Privatkunde (Einzelbenutzer)</span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                  :class="u.is_pro || u.company_name ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-200 text-slate-700'"
                >
                  {{ u.company_plan || (u.is_pro ? 'PRO' : 'FREE PLAN') }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  v-if="u.is_superadmin"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-300"
                >
                  SUPERADMIN
                </span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <!-- User Settings Dialog Trigger Button instead of direct 1-click upgrade -->
                <button
                  @click="openEditUserModal(u)"
                  class="taskster_button_light px-4 text-xs h-[34px] rounded-lg shadow-xs"
                  title="Benutzer-Einstellungen bearbeiten (Plan, Rolle, Firma)"
                >
                  <span>⚙️</span>
                  <span>Einstellungen</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: COMPANIES & CLIENTS (Liquid Glass Table Card) -->
    <div v-else-if="activeTab === 'companies'" class="space-y-6">
      <div class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
        <div class="p-4 sm:p-6 border-b border-slate-200/80 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Unternehmen, Mandanten & B2B-Kunden</h3>
            <p class="text-xs text-slate-600 font-medium">Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien.</p>
          </div>
          <button
            @click="showCreateCompanyModal = true"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm"
          >
            <span>+ Neues Unternehmen anlegen</span>
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
              <tr>
                <th class="py-3.5 px-4">Unternehmen</th>
                <th class="py-3.5 px-4">Abo-Plan</th>
                <th class="py-3.5 px-4">Nutzer & Ordner</th>
                <th class="py-3.5 px-4">Dateiuploads (Zero Trust)</th>
                <th class="py-3.5 px-4 text-right">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/60 text-slate-800">
              <tr v-for="c in companies" :key="c.id" class="hover:bg-white/60 transition">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900 text-sm">{{ c.name }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">ID: {{ c.id }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <select
                    v-model="c.subscription_plan"
                    @change="updateCompanyPlan(c)"
                    class="bg-white/80 border border-slate-300 rounded-lg px-2.5 py-1 text-xs text-slate-900 font-semibold focus:outline-none focus:border-purple-500 shadow-xs"
                  >
                    <option value="starter">Starter Plan</option>
                    <option value="pro">Pro Plan</option>
                    <option value="enterprise">Enterprise Plan</option>
                  </select>
                </td>
                <td class="py-3.5 px-4">
                  <span class="text-slate-800 font-bold">{{ c.user_count }} Mitarbeiter</span>
                  <span class="text-slate-600 font-medium"> / {{ c.folder_count }} Ordner</span>
                </td>
                <td class="py-3.5 px-4">
                  <button
                    @click="toggleCompanyUploads(c)"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition flex items-center space-x-1.5 cursor-pointer"
                    :class="c.settings?.allow_document_upload ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-100 text-rose-900 border-rose-300'"
                  >
                    <span>{{ c.settings?.allow_document_upload ? '✓ Erlaubt' : '🚫 Upload gesperrt (Policy)' }}</span>
                  </button>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <span class="text-[11px] text-purple-700 font-bold px-2 py-0.5 rounded-full bg-purple-100 border border-purple-200">Aktiv</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB: COMPANY INVITES (Für Company Admins) -->
    <div v-if="activeTab === 'invites'" class="space-y-6">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200/80">
          <div>
            <h3 class="text-base font-bold text-slate-900">Mitarbeiter zu {{ user?.company_name }} einladen</h3>
            <p class="text-xs text-slate-600 font-medium mt-1">
              Bereits registrierte Nutzer werden sofort dem Unternehmen zugewiesen. Nicht registrierte Nutzer erhalten einen Registrierungslink und treten nach der Registrierung automatisch bei.
            </p>
          </div>
        </div>

        <!-- Invite Form -->
        <form @submit.prevent="sendCompanyInvite" class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mb-8">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
            <input
              v-model="inviteEmail"
              type="email"
              required
              placeholder="mitarbeiter@domain.ch"
              class="w-full px-3.5 py-2.5 bg-white/80 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-purple-600 shadow-xs"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Rolle im Unternehmen</label>
            <select
              v-model="inviteRole"
              class="w-full px-3.5 py-2.5 bg-white/80 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-purple-600 shadow-xs"
            >
              <option value="member">Mitglied (Member)</option>
              <option value="admin">Company Administrator</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
              type="submit"
              :disabled="sendingInvite"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm w-full"
            >
              {{ sendingInvite ? 'Sende...' : 'Einladung absenden' }}
            </button>
          </div>
        </form>

        <!-- Success link box -->
        <div v-if="lastInviteLink" class="p-4 rounded-2xl bg-purple-100/80 border border-purple-300 text-xs mb-6">
          <div class="font-bold text-purple-900 mb-1">Einladung erfolgreich generiert!</div>
          <div class="text-slate-700 mb-2">Für nicht registrierte Nutzer kann dieser direkte Registrierungslink weitergegeben werden:</div>
          <div class="flex items-center space-x-2">
            <input
              readonly
              :value="lastInviteLink"
              class="flex-1 px-3.5 py-2 bg-white border border-purple-300 rounded-xl text-xs font-mono text-emerald-800 font-bold select-all shadow-xs"
            />
            <button
              type="button"
              @click="copyInviteLink"
              class="taskster_button px-6 text-xs h-[38px] rounded-lg shadow-sm"
            >
              Kopieren
            </button>
          </div>
        </div>

        <!-- Pending Invites List -->
        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Offene Einladungen</h4>
        <div v-if="pendingInvites.length === 0" class="text-xs text-slate-600 font-medium py-6 text-center border border-dashed border-slate-300 rounded-2xl bg-white/40">
          Keine offenen Einladungen vorhanden.
        </div>
        <div v-else class="divide-y divide-slate-200/60 border border-slate-200/80 rounded-2xl overflow-hidden bg-white/40 shadow-xs">
          <div v-for="inv in pendingInvites" :key="inv.id" class="p-3.5 flex items-center justify-between text-xs hover:bg-white/60 transition">
            <div>
              <div class="font-bold text-slate-900">{{ inv.email }}</div>
              <div class="text-[10px] text-slate-600 font-medium">Rolle: {{ inv.role }} • Erstellt: {{ new Date(inv.created_at).toLocaleDateString('de-CH') }}</div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-900 border border-amber-300">
              {{ inv.status }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: TASK LOGIC & ZERO-TRUST COMPLIANCE (Liquid Glass Cards) -->
    <div v-else-if="activeTab === 'policies'" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 4-Stage Pipeline Card -->
        <div class="liquid_glass_card rounded-3xl p-6 sm:p-7 shadow-xl">
          <div class="flex items-center space-x-2 text-purple-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>🛡️</span>
            <span>Rollen- & Berechtigungsmodell</span>
          </div>
          <h3 class="text-lg font-black text-slate-900 mb-2">Aktive Sicherheitsarchitektur</h3>
          <p class="text-xs text-slate-600 font-medium leading-relaxed mb-6">
            Jeder API-Zugriff durchläuft serverseitig diese 4 Prüfstufen:
          </p>

          <ol class="space-y-3 text-xs">
            <li class="p-3.5 rounded-2xl bg-white/60 border border-white/80 flex items-start space-x-3 shadow-xs">
              <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-800 border border-purple-300 flex items-center justify-center font-bold text-[11px] shrink-0">1</span>
              <div>
                <strong class="text-slate-900">Company Policy Check:</strong>
                <p class="text-slate-600 mt-0.5 font-medium">Prüft globale Unternehmensrichtlinien (z.B. Dokumenten-Upload-Sperre). Verstoß liefert 403 Forbidden.</p>
              </div>
            </li>
            <li class="p-3.5 rounded-2xl bg-white/60 border border-white/80 flex items-start space-x-3 shadow-xs">
              <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-800 border border-purple-300 flex items-center justify-center font-bold text-[11px] shrink-0">2</span>
              <div>
                <strong class="text-slate-900">Project Membership Check:</strong>
                <p class="text-slate-600 mt-0.5 font-medium">Prüft Ordnerinhaber oder Projektmitgliedschaft. Nicht berechtigte Anfragen erhalten 404 Not Found.</p>
              </div>
            </li>
            <li class="p-3.5 rounded-2xl bg-white/60 border border-white/80 flex items-start space-x-3 shadow-xs">
              <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-800 border border-purple-300 flex items-center justify-center font-bold text-[11px] shrink-0">3</span>
              <div>
                <strong class="text-slate-900">List Scope Check:</strong>
                <p class="text-slate-600 mt-0.5 font-medium">Eingeschränkte Listen erfordern explizite Listensichtbarkeit.</p>
              </div>
            </li>
            <li class="p-3.5 rounded-2xl bg-white/60 border border-white/80 flex items-start space-x-3 shadow-xs">
              <span class="w-6 h-6 rounded-full bg-purple-100 text-purple-800 border border-purple-300 flex items-center justify-center font-bold text-[11px] shrink-0">4</span>
              <div>
                <strong class="text-slate-900">Role Action Check:</strong>
                <p class="text-slate-600 mt-0.5 font-medium">Viewer dürfen nur Lesemethoden (GET) nutzen. Schreibzugriffe werden serverseitig abgewiesen.</p>
              </div>
            </li>
          </ol>
        </div>

        <!-- Freemium Rules & App Logic -->
        <div class="liquid_glass_card rounded-3xl p-6 sm:p-7 shadow-xl">
          <div class="flex items-center space-x-2 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>⚙️</span>
            <span>Tarifregeln & Systemgrenzen</span>
          </div>
          <h3 class="text-lg font-black text-slate-900 mb-2">Durchgesetzte Systemgrenzen</h3>
          <p class="text-xs text-slate-600 font-medium leading-relaxed mb-6">
            Folgende Systemgrenzen sind im Backend aktiv durchgesetzt:
          </p>

          <div class="space-y-3 text-xs">
            <div class="p-3.5 rounded-2xl bg-white/60 border border-white/80 shadow-xs">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-slate-900">Free-Plan Ordner-Limit</strong>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 font-mono font-bold text-[11px]">1 Ordner</span>
              </div>
              <p class="text-slate-600 font-medium">Ein Kunde im Free-Plan hat maximal 1 Projektordner zur Verfügung.</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-white/60 border border-white/80 shadow-xs">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-slate-900">Gleichzeitige Projekt-Mitarbeit</strong>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 font-mono font-bold text-[11px]">Max. 3</span>
              </div>
              <p class="text-slate-600 font-medium">Free-User dürfen maximal in 3 Projekten gleichzeitig aktiv mitarbeiten.</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-white/60 border border-white/80 shadow-xs">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-slate-900">Teammitglieder pro Projekt</strong>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300 font-mono font-bold text-[11px]">Max. 5</span>
              </div>
              <p class="text-slate-600 font-medium">Pro Projekt können im Free-Plan maximal 5 Teammitglieder inkl. Owner teilnehmen.</p>
            </div>

            <div class="p-3.5 rounded-2xl bg-white/60 border border-white/80 shadow-xs">
              <div class="flex items-center justify-between mb-1">
                <strong class="text-slate-900">Company Plan Vererbung</strong>
                <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-900 border border-purple-300 font-mono font-bold text-[11px]">Automatisch</span>
              </div>
              <p class="text-slate-600 font-medium">Eingeladene Mitarbeiter einer Company erben automatisch den bezahlten Company-Plan.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 4: PROJECT TEMPLATES (Liquid Glass Cards) -->
    <div v-if="activeTab === 'templates'" class="space-y-6">
      <div class="liquid_glass rounded-3xl p-6 sm:p-7 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-black text-slate-900 flex items-center space-x-2">
            <span>📋</span>
            <span>Projekt-Vorlagen (Gewerbe, Jobs & Privat)</span>
          </h3>
          <p class="text-xs text-slate-600 font-medium mt-1 max-w-2xl">
            Verwalte strukturierte Vorlagen mit Standard-Listen und benutzerdefinierten Feldern inklusive bedingter IF-THEN-Logik. Benutzer können diese beim Erstellen eines neuen Projekts auswählen.
          </p>
        </div>

        <button
          @click="openCreateTemplateModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm"
        >
          <span>+ Neue Vorlage erstellen</span>
        </button>
      </div>

      <!-- Filter and Search Bar -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-2 w-full sm:w-auto overflow-x-auto pb-1">
          <button
            @click="templateCategoryFilter = 'all'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer"
            :class="templateCategoryFilter === 'all' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-slate-700 border border-white/70 shadow-xs'"
          >
            Alle Vorlagen ({{ templates.length }})
          </button>
          <button
            @click="templateCategoryFilter = 'job'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer"
            :class="templateCategoryFilter === 'job' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-slate-700 border border-white/70 shadow-xs'"
          >
            <span>💼</span>
            <span>Job & Gewerbe ({{ templates.filter(t => t.category === 'job').length }})</span>
          </button>
          <button
            @click="templateCategoryFilter = 'private'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer"
            :class="templateCategoryFilter === 'private' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-slate-700 border border-white/70 shadow-xs'"
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
            class="w-full px-3.5 py-2.5 bg-white/70 focus:bg-white border border-white/80 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-cyan-600 shadow-xs backdrop-blur-sm"
          />
        </div>
      </div>

      <!-- Templates Grid -->
      <div v-if="filteredTemplates.length === 0" class="p-12 text-center liquid_glass rounded-3xl shadow-xl">
        <div class="text-4xl mb-3">🔍</div>
        <h4 class="text-sm font-bold text-slate-900 mb-1">Keine Vorlagen gefunden</h4>
        <p class="text-xs text-slate-600 mb-4">Erstelle deine erste Vorlage oder passe den Suchfilter an.</p>
        <button @click="openCreateTemplateModal" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
          + Jetzt Vorlage anlegen
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div
          v-for="tmpl in filteredTemplates"
          :key="tmpl.id"
          class="liquid_glass_card hover:border-[#00A3C4] rounded-3xl p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between space-y-4"
        >
          <div>
            <div class="flex items-start justify-between gap-3 mb-2">
              <div class="flex items-center space-x-2">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                  :class="tmpl.category === 'job' ? 'bg-cyan-100 text-cyan-900 border border-cyan-300' : 'bg-emerald-100 text-emerald-900 border border-emerald-300'"
                >
                  {{ tmpl.category === 'job' ? '💼 Job / Gewerbe' : '🏡 Privat' }}
                </span>
                <span v-if="tmpl.subcategory" class="px-2 py-0.5 rounded-lg text-[10px] font-mono text-slate-600 bg-white/80 border border-slate-200">
                  {{ tmpl.subcategory }}
                </span>
              </div>
              <span v-if="tmpl.is_system" class="text-[10px] text-purple-700 font-bold bg-purple-100 border border-purple-300 px-2 py-0.5 rounded-full">
                System-Vorlage
              </span>
            </div>

            <h4 class="text-base font-black text-slate-900 mb-1">{{ tmpl.name }}</h4>
            <p class="text-xs text-slate-600 font-medium leading-relaxed line-clamp-2 mb-4">
              {{ tmpl.description || 'Keine Beschreibung angegeben.' }}
            </p>

            <!-- Pre-configured Lists -->
            <div class="mb-3">
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                <span>Vordefinierte Abschnitte / Listen ({{ tmpl.lists?.length || 0 }})</span>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="(lst, i) in tmpl.lists"
                  :key="i"
                  class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white/70 border border-white/80 text-slate-800 shadow-xs"
                >
                  {{ lst }}
                </span>
              </div>
            </div>

            <!-- Custom Fields & Logic -->
            <div>
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                <span>Benutzerdefinierte Felder ({{ tmpl.fields?.length || 0 }})</span>
              </div>
              <div class="space-y-1.5">
                <div
                  v-for="(f, i) in tmpl.fields"
                  :key="i"
                  class="flex items-center justify-between p-2.5 rounded-xl bg-white/60 border border-white/80 text-xs shadow-xs"
                >
                  <div class="flex items-center space-x-2 min-w-0">
                    <span class="font-bold text-slate-900 truncate">{{ f.label }}</span>
                    <span class="text-[10px] font-mono text-slate-400">({{ f.field_key }})</span>
                    <span class="text-slate-500 font-mono text-[11px]">[{{ f.field_type }}]</span>
                    <span
                      class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded"
                      :class="f.entity_type === 'project' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-cyan-100 text-cyan-800 border border-cyan-200'"
                    >
                      {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                    </span>
                  </div>

                  <span
                    v-if="f.logic_rules && f.logic_rules.depends_on_field"
                    class="text-[10px] text-amber-900 font-mono bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-lg shrink-0 ml-2 font-bold"
                    :title="`Nur sichtbar wenn ${f.logic_rules.depends_on_field} == ${f.logic_rules.depends_on_value}`"
                  >
                    ⚡ Wenn {{ f.logic_rules.depends_on_field }} == "{{ f.logic_rules.depends_on_value }}"
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-200/70">
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
    <div v-if="showTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl my-8 border border-white/80">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">
            {{ editingTemplate ? 'Projekt-Vorlage bearbeiten' : 'Neue Projekt-Vorlage erstellen' }}
          </h3>
          <button @click="showTemplateModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">✕</button>
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
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Kategorie *</label>
              <select
                v-model="tmplForm.category"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-purple-500"
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
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-300 mb-1">Beschreibung</label>
              <input
                v-model="tmplForm.description"
                type="text"
                placeholder="Kurze Zusammenfassung des Einsatzbereichs"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>
          </div>

          <!-- Lists / Sections Editor -->
          <div class="pt-3 border-t border-slate-200">
            <label class="block text-xs font-bold text-slate-900 uppercase tracking-wider mb-1">
              Vordefinierte Abschnitte / Listen
            </label>
            <p class="text-[11px] text-slate-500 mb-2">
              Diese Listen werden automatisch angelegt, wenn ein Projekt mit dieser Vorlage erstellt wird.
            </p>

            <div class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="(lst, idx) in tmplForm.lists"
                :key="idx"
                class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-xs text-slate-200"
              >
                <span>{{ lst }}</span>
                <button type="button" @click="tmplForm.lists.splice(idx, 1)" class="text-rose-400 hover:text-rose-700 text-xs">✕</button>
              </span>
            </div>

            <div class="flex items-center space-x-2">
              <input
                v-model="newTmplListInput"
                type="text"
                placeholder="Neuen Abschnitt eingeben (z.B. In Prüfung)..."
                class="flex-1 px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-purple-500"
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
          <div class="pt-3 border-t border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <label class="block text-xs font-bold text-slate-900 uppercase tracking-wider">
                  Benutzerdefinierte Felder mit Logik
                </label>
                <p class="text-[11px] text-slate-500">
                  Felder für Aufgaben oder das gesamte Projekt inkl. bedingter Abhängigkeiten.
                </p>
              </div>
            </div>

            <!-- Existing fields list -->
            <div v-if="tmplForm.fields.length > 0" class="space-y-2">
              <div
                v-for="(f, idx) in tmplForm.fields"
                :key="idx"
                class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between text-xs"
              >
                <div class="space-y-0.5">
                  <div class="flex items-center space-x-2">
                    <strong class="text-slate-900">{{ f.label }}</strong>
                    <span class="text-slate-500 font-mono text-[11px]">({{ f.field_key }})</span>
                    <span class="text-slate-500 font-mono">[{{ f.field_type }}]</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase" :class="f.entity_type === 'project' ? 'bg-purple-50 text-purple-700' : 'bg-emerald-950 text-emerald-700'">
                      {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                    </span>
                  </div>
                  <div v-if="f.options && f.options.length > 0" class="text-[11px] text-slate-500">
                    Optionen: {{ f.options.join(', ') }}
                  </div>
                  <div v-if="f.logic_rules && f.logic_rules.depends_on_field" class="text-[11px] text-amber-800 font-mono">
                    ⚡ Nur sichtbar wenn {{ f.logic_rules.depends_on_field }} == "{{ f.logic_rules.depends_on_value }}"
                  </div>
                </div>

                <button
                  type="button"
                  @click="tmplForm.fields.splice(idx, 1)"
                  class="text-rose-400 hover:text-rose-700 text-xs px-2 py-1"
                >
                  Löschen
                </button>
              </div>
            </div>

            <!-- New field inputs -->
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200 space-y-3">
              <h5 class="text-xs font-bold text-slate-300">+ Neues Feld zur Vorlage hinzufügen</h5>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                  <label class="block text-[10px] text-slate-500 mb-1">Feldbezeichnung</label>
                  <input
                    v-model="newField.label"
                    type="text"
                    placeholder="z.B. OTDR Messung"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800"
                  />
                </div>
                <div>
                  <label class="block text-[10px] text-slate-500 mb-1">Feldtyp</label>
                  <select
                    v-model="newField.field_type"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800"
                  >
                    <option value="text">Textzeile</option>
                    <option value="number">Zahl / Währung</option>
                    <option value="select">Auswahlliste (Dropdown)</option>
                    <option value="date">Datum</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] text-slate-500 mb-1">Ebene</label>
                  <select
                    v-model="newField.entity_type"
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800"
                  >
                    <option value="task">Aufgaben-Feld</option>
                    <option value="project">Projekt-Feld</option>
                  </select>
                </div>
              </div>

              <div v-if="newField.field_type === 'select'">
                <label class="block text-[10px] text-slate-500 mb-1">Dropdown-Optionen (Komma-getrennt)</label>
                <input
                  v-model="newFieldOptionsInput"
                  type="text"
                  placeholder="Ja, Nein, Ausstehend"
                  class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800"
                />
              </div>

              <!-- Conditional Logic Controls -->
              <div class="pt-2 border-t border-slate-200">
                <label class="flex items-center space-x-2 cursor-pointer mb-2">
                  <input
                    type="checkbox"
                    v-model="newFieldHasLogic"
                    class="rounded border-slate-300 text-purple-600 focus:ring-0"
                  />
                  <span class="text-xs text-amber-800 font-semibold">⚡ Bedingte Sichtbarkeit (Abhängig von anderem Feld)</span>
                </label>

                <div v-if="newFieldHasLogic" class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-2.5 rounded-lg bg-amber-950/20 border border-amber-900/40">
                  <div>
                    <label class="block text-[10px] text-amber-200 mb-1">Abhängig von Feld-Key</label>
                    <select
                      v-model="newFieldLogicDepField"
                      class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800"
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
                      class="w-full px-2 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800"
                    />
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="button"
                  @click="addFieldToTemplate"
                  class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-slate-900 rounded text-xs font-bold transition"
                >
                  + Feld hinzufügen
                </button>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
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
    <div v-if="showCreateCompanyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-white/80">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-black text-slate-900">Neues Unternehmen anlegen</h3>
          <button @click="showCreateCompanyModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">✕</button>
        </div>
        <p class="text-xs text-slate-600 font-medium mb-4">
          Erstellt ein Unternehmens-Profil mit Company Admin und initialem Hauptordner.
        </p>

        <form @submit.prevent="createCompany" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Name des Unternehmens</label>
            <input
              v-model="newCompanyName"
              type="text"
              required
              placeholder="z.B. Acme Solutions AG"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Subscription-Plan</label>
            <select
              v-model="newCompanyPlan"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option value="starter">Starter Plan</option>
              <option value="pro">Pro Plan</option>
              <option value="enterprise">Enterprise Plan</option>
            </select>
          </div>

          <div class="pt-2 border-t border-slate-200/80">
            <h4 class="text-xs font-bold text-purple-900 mb-2">Company Admin Zugangsdaten</h4>
            <div class="space-y-3">
              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Name des Admins</label>
                <input
                  v-model="newCompanyAdminName"
                  type="text"
                  required
                  placeholder="Beat Meier"
                  class="w-full px-3.5 py-2 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
                />
              </div>

              <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">E-Mail des Admins</label>
                <input
                  v-model="newCompanyAdminEmail"
                  type="email"
                  required
                  placeholder="beat.meier@firma.ch"
                  class="w-full px-3.5 py-2 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showCreateCompanyModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              Unternehmen erstellen
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Edit User Settings -->
    <div
      v-if="showEditUserModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
    >
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl my-8 border border-white/80">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 mb-6">
          <div>
            <div class="inline-flex items-center space-x-1 text-xs font-bold text-cyan-800 px-2.5 py-0.5 rounded-full bg-cyan-100 border border-cyan-200 mb-1">
              <span>⚙️</span>
              <span>Benutzer-Einstellungen</span>
            </div>
            <h3 class="text-lg font-black text-slate-900">
              {{ editUserForm.name }} bearbeiten
            </h3>
          </div>
          <button
            type="button"
            @click="showEditUserModal = false"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="saveUserChanges" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Name</label>
            <input
              v-model="editUserForm.name"
              type="text"
              required
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">E-Mail-Adresse</label>
            <input
              v-model="editUserForm.email"
              type="email"
              required
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <!-- Plan Selection -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Benutzer-Plan (Tarif)</label>
            <select
              v-model="editUserForm.is_pro"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option :value="false">Taskster Free Plan (Basis: max. 1 Ordner, 3 Projekte)</option>
              <option :value="true">Taskster PRO Plan (Unbegrenzte Ordner & Projekte)</option>
            </select>
          </div>

          <!-- Company Assignment -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Unternehmen / Organisation zuweisen</label>
            <select
              v-model="editUserForm.company_id"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option value="">Keine (Privatkunde / Einzelnutzer)</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.subscription_plan ? c.subscription_plan.toUpperCase() : 'STANDARD' }})
              </option>
            </select>
          </div>

          <!-- Company Role (only visible if company assigned) -->
          <div v-if="editUserForm.company_id">
            <label class="block text-xs font-bold text-slate-800 mb-1">Rolle im Unternehmen</label>
            <select
              v-model="editUserForm.company_role"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option value="member">Mitarbeiter (member)</option>
              <option value="admin">Unternehmens-Administrator (admin)</option>
            </select>
          </div>

          <!-- Superadmin Checkbox -->
          <div class="pt-3 pb-2 border-t border-slate-200/80">
            <label class="flex items-start space-x-3 cursor-pointer select-none">
              <input
                v-model="editUserForm.is_superadmin"
                type="checkbox"
                class="w-4 h-4 mt-0.5 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
              />
              <div>
                <div class="text-xs font-bold text-purple-950">Superadmin-Berechtigung</div>
                <div class="text-[11px] text-slate-600">
                  Ermöglicht Vollzugriff auf diesen Administrationsbereich und alle Plattformdaten.
                </div>
              </div>
            </label>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showEditUserModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingUser"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ savingUser ? 'Speichern...' : 'Änderungen speichern' }}
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

const showEditUserModal = ref(false)
const editUserForm = ref({
  id: '',
  name: '',
  email: '',
  is_pro: false,
  is_superadmin: false,
  company_id: '',
  company_role: 'member'
})
const savingUser = ref(false)

const openEditUserModal = (u: any) => {
  editUserForm.value = {
    id: u.id,
    name: u.name || '',
    email: u.email || '',
    is_pro: Boolean(u.is_pro),
    is_superadmin: Boolean(u.is_superadmin),
    company_id: u.company_id || '',
    company_role: u.company_role || 'member'
  }
  showEditUserModal.value = true
}

const saveUserChanges = async () => {
  savingUser.value = true
  try {
    await $fetch(`/api/admin/users/${editUserForm.value.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: editUserForm.value.name,
        email: editUserForm.value.email,
        is_pro: editUserForm.value.is_pro,
        is_superadmin: editUserForm.value.is_superadmin,
        company_id: editUserForm.value.company_id || null,
        company_role: editUserForm.value.company_role
      }
    })
    showEditUserModal.value = false
    await loadAdminData()
    alert('Benutzer-Einstellungen erfolgreich gespeichert!')
  } catch (err: any) {
    alert(err.data?.statusMessage || 'Fehler beim Speichern der Benutzer-Einstellungen')
  } finally {
    savingUser.value = false
  }
}

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
