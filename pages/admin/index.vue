<template>
  <div class="w-full min-h-screen bg-slate-50/50">
    <div v-if="isAnyAdmin" class="w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      
      <!-- Main Content Area (Navigation is located exclusively in the global left sidebar) -->
      <main class="w-full min-w-0">
          <!-- Header with Integrated Stat Pills -->
          <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-purple-50 border border-purple-200 text-purple-800 text-xs font-semibold mb-2">
                  <Users v-if="activeTab === 'users'" class="w-3.5 h-3.5 text-purple-600" />
                  <Building2 v-else-if="activeTab === 'companies'" class="w-3.5 h-3.5 text-purple-600" />
                  <CreditCard v-else-if="activeTab === 'finance'" class="w-3.5 h-3.5 text-purple-600" />
                  <ClipboardList v-else-if="activeTab === 'templates'" class="w-3.5 h-3.5 text-purple-600" />
                  <Mail v-else-if="activeTab === 'email'" class="w-3.5 h-3.5 text-purple-600" />
                  <Globe v-else-if="activeTab === 'website'" class="w-3.5 h-3.5 text-purple-600" />
                  <ShieldCheck v-else class="w-3.5 h-3.5 text-purple-600" />
                  <span>{{ activeSectionBadge }}</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                  {{ activeSectionTitle }}
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                  {{ activeSectionDescription }}
                </p>
              </div>

              <div class="flex items-center gap-3 shrink-0">
                <button
                  v-if="activeTab === 'users' && hasPermission('manage_users')"
                  @click="openCreateUserModal"
                  class="taskster_button px-6 text-xs h-[42px] rounded-lg"
                >
                  <Plus class="w-4 h-4" />
                  <span>Neuen Benutzer anlegen</span>
                </button>

                <button
                  v-else-if="activeTab === 'companies' && hasPermission('company_settings')"
                  @click="showCreateCompanyModal = true"
                  class="taskster_button px-6 text-xs h-[42px] rounded-lg"
                >
                  <Plus class="w-4 h-4" />
                  <span>Neues Unternehmen</span>
                </button>

                <button
                  v-else-if="activeTab === 'finance' && hasPermission('finance')"
                  @click="loadOrdersData()"
                  class="taskster_button px-6 text-xs h-[42px] rounded-lg"
                >
                  <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loadingOrders }" />
                  <span>Aktualisieren</span>
                </button>

                <button
                  v-else-if="activeTab === 'templates' && hasPermission('manage_templates')"
                  @click="openCreateTemplateModal"
                  class="taskster_button px-6 text-xs h-[42px] rounded-lg"
                >
                  <Plus class="w-4 h-4" />
                  <span>Neue Vorlage</span>
                </button>

                <button
                  v-else-if="activeTab === 'email' && hasPermission('company_settings')"
                  @click="openTestEmailModal()"
                  class="taskster_button px-6 text-xs h-[42px] rounded-lg"
                >
                  <Send class="w-4 h-4" />
                  <span>Test-E-Mail</span>
                </button>

                <button
                  v-else-if="activeTab === 'website' && hasPermission('company_settings')"
                  @click="saveWebsiteSettings()"
                  :disabled="savingWebsiteSettings"
                  class="taskster_button px-6 text-xs h-[42px] rounded-lg"
                >
                  <Save class="w-4 h-4" />
                  <span>{{ savingWebsiteSettings ? 'Speichere...' : 'Einstellungen speichern' }}</span>
                </button>
              </div>
            </div>

            <!-- Integrated Stat Pills -->
            <!-- USERS PILLS -->
            <div v-if="activeTab === 'users'" class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-slate-50 border border-slate-200/80 text-center">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Kunden & User</div>
                <div class="text-xl font-bold text-slate-900 mt-0.5 tabular-nums">{{ overview?.metrics?.users || 0 }}</div>
                <div class="text-[10px] text-slate-400">Registriert</div>
              </div>

              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Unternehmen</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5 tabular-nums">{{ overview?.metrics?.companies || 0 }}</div>
                <div class="text-[10px] text-slate-400">Organisationen</div>
              </div>

              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Projekte</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5 tabular-nums">{{ overview?.metrics?.projects || 0 }}</div>
                <div class="text-[10px] text-slate-400">Aktiv</div>
              </div>

              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Aufgaben</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5 tabular-nums">{{ overview?.metrics?.tasks || 0 }}</div>
                <div class="text-[10px] text-slate-400">In Abschnitten gepflegt</div>
              </div>

              <div class="p-3 rounded-lg bg-amber-50/50 border border-amber-200/60 text-center">
                <div class="text-[11px] font-semibold text-amber-700 uppercase tracking-wide">Journal-Einträge</div>
                <div class="text-xl font-bold text-amber-900 mt-0.5 tabular-nums">{{ overview?.metrics?.journals || 0 }}</div>
                <div class="text-[10px] text-slate-400">Aktivitätsnotizen</div>
              </div>
            </div>

            <!-- COMPANIES PILLS -->
            <div v-else-if="activeTab === 'companies'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Unternehmen</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5 tabular-nums">{{ companies.length }}</div>
                <div class="text-[10px] text-slate-400">Organisationen & Mandanten</div>
              </div>

              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Mitarbeiter zugewiesen</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5 tabular-nums">{{ totalCompanyUsers }}</div>
                <div class="text-[10px] text-slate-400">Mitarbeiter-Accounts</div>
              </div>

              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Upload-Richtlinie</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5 tabular-nums">{{ companiesWithUploadAllowed }} / {{ companies.length }}</div>
                <div class="text-[10px] text-slate-400">Uploads freigegeben</div>
              </div>
            </div>

            <!-- FINANCE PILLS -->
            <div v-else-if="activeTab === 'finance'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Monatlicher Umsatz (MRR)</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5 tabular-nums">
                  {{ ordersSummary?.mrr ? ordersSummary.mrr.toLocaleString('de-CH') : '0' }} CHF
                </div>
                <div class="text-[10px] text-slate-400">Wiederkehrender monatlicher Umsatz</div>
              </div>

              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Aktive Abonnements</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5 tabular-nums">
                  {{ ordersSummary?.active_subscriptions || 0 }}
                </div>
                <div class="text-[10px] text-slate-400">Unternehmen & PRO-Nutzer</div>
              </div>

              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Kostenpflichtige Sitze</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5 tabular-nums">
                  {{ ordersSummary?.total_seats || 0 }}
                </div>
                <div class="text-[10px] text-slate-400">Zugewiesene Mitarbeiter-Lizenzen</div>
              </div>
            </div>

            <!-- TEMPLATES PILLS -->
            <div v-else-if="activeTab === 'templates'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Vorlagen Gesamt</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5 tabular-nums">{{ templates.length }}</div>
                <div class="text-[10px] text-slate-400">Systemweite Vorlagen</div>
              </div>

              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Job & Gewerblich</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5 tabular-nums">{{ jobTemplatesCount }}</div>
                <div class="text-[10px] text-slate-400">Baufirmen & Business</div>
              </div>

              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Privat-Vorlagen</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5 tabular-nums">{{ privateTemplatesCount }}</div>
                <div class="text-[10px] text-slate-400">Bauherren & Privatnutzer</div>
              </div>
            </div>

            <!-- EMAIL PILLS -->
            <div v-else-if="activeTab === 'email'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Versand-Methode</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5">
                  {{ smtpConfig.mail_provider === 'resend' ? 'Resend API' : 'SMTP' }}
                </div>
                <div class="text-[10px] text-slate-400">{{ smtpConfig.mail_provider === 'resend' ? 'noreply@kurka.ch' : (smtpConfig.smtp_host || 'Server') }}</div>
              </div>

              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Aktive Vorlagen</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5 tabular-nums">
                  {{ activeEmailTemplatesCount }} / {{ emailTemplates.length }}
                </div>
                <div class="text-[10px] text-slate-400">System-Trigger bereit</div>
              </div>

              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Versendete E-Mails</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5 tabular-nums">{{ emailOutbox.length }}</div>
                <div class="text-[10px] text-slate-400">Protokollierte Einträge</div>
              </div>
            </div>

            <!-- WEBSITE PILLS -->
            <div v-else-if="activeTab === 'website'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Webseite Status</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5">
                  {{ websiteSettings.website_maintenance_mode ? 'Wartungsmodus' : 'Online / Aktiv' }}
                </div>
                <div class="text-[10px] text-slate-400">Öffentliche Landingpage</div>
              </div>

              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Ankündigung</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5">
                  {{ websiteSettings.website_announcement_active ? 'Banner Aktiv' : 'Kein Banner' }}
                </div>
                <div class="text-[10px] text-slate-400">Live In-App Hinweise</div>
              </div>

              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">SEO & Metadaten</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5">Konfiguriert</div>
                <div class="text-[10px] text-slate-400">Google & OpenGraph</div>
              </div>
            </div>

            <!-- AUDIT PILLS -->
            <div v-else-if="activeTab === 'audit'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Audit Log-Einträge</div>
                <div class="text-xl font-bold text-purple-900 mt-0.5 tabular-nums">{{ auditLogsTotal }}</div>
                <div class="text-[10px] text-slate-400">Systemweit protokolliert</div>
              </div>

              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Aktivitäts-Typen</div>
                <div class="text-xl font-bold text-[#0891B2] mt-0.5 tabular-nums">{{ auditLogsActions.length }}</div>
                <div class="text-[10px] text-slate-400">Verschiedene Event-Klassen</div>
              </div>

              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Security Standard</div>
                <div class="text-xl font-bold text-emerald-900 mt-0.5">Zero-Trust Audit</div>
                <div class="text-[10px] text-slate-400">Revisionssicher geloggt</div>
              </div>
            </div>

            <!-- AI PILLS -->
            <div v-else-if="activeTab === 'ai'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100">
              <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-200/60 text-center">
                <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wide">Standard-Modell</div>
                <div class="text-sm font-bold text-purple-900 mt-0.5 truncate">{{ aiSettings.ai_model }}</div>
                <div class="text-[10px] text-slate-400">OpenRouter LLM</div>
              </div>
              <div class="p-3 rounded-lg bg-cyan-50/50 border border-cyan-200/60 text-center">
                <div class="text-[11px] font-semibold text-cyan-700 uppercase tracking-wide">Audio Transkriptor</div>
                <div class="text-sm font-bold text-[#0891B2] mt-0.5 truncate">{{ aiSettings.ai_audio_model }}</div>
                <div class="text-[10px] text-slate-400">Whisper Voice Engine</div>
              </div>
              <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-200/60 text-center">
                <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Pro & Enterprise</div>
                <div class="text-sm font-bold text-emerald-900 mt-0.5">Vollzugriff aktiv</div>
                <div class="text-[10px] text-slate-400">Tarif-Gating aktiv</div>
              </div>
            </div>
          </div>

    <!-- TAB 1: USERS & CUSTOMERS (Liquid Glass Table Card) -->
    <div v-if="activeTab === 'users'" class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
      <div class="p-4 sm:p-6 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Alle registrierten Kunden und Benutzer</h3>
          <p class="text-xs text-slate-600 font-medium">Verwalte Berechtigungen, Pro-Status, Subrollen und Firmenzuweisungen.</p>
        </div>
        <button
          @click="openCreateUserModal"
          class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm shrink-0"
        >
          <span>+ Neuen Benutzer anlegen</span>
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
            <tr>
              <th class="py-3.5 px-4">Name & E-Mail</th>
              <th class="py-3.5 px-4">Unternehmen / Organisation</th>
              <th class="py-3.5 px-4">Plan & Status</th>
              <th class="py-3.5 px-4">Rolle & Berechtigungen</th>
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
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                  :class="{
                    'bg-purple-100 text-purple-900 border-purple-300': (u.plan || u.company_plan) === 'enterprise',
                    'bg-cyan-100 text-cyan-900 border-cyan-300': (u.plan || u.company_plan) === 'pro' || (!u.plan && !u.company_plan && u.is_pro),
                    'bg-slate-100 text-slate-700 border-slate-300': (u.plan || u.company_plan) === 'basic' || (!u.plan && !u.company_plan && !u.is_pro)
                  }"
                >
                  {{ (u.plan || u.company_plan || (u.is_pro ? 'pro' : 'basic')).toUpperCase() }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <div v-if="u.is_superadmin" class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-300">
                  <span>⚡</span>
                  <span>SUPERADMIN</span>
                </div>
                <div v-else-if="u.company_role === 'admin'" class="space-y-1">
                  <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                    Administrator
                  </span>
                  <div v-if="u.admin_permissions && u.admin_permissions.length > 0" class="flex flex-wrap gap-1">
                    <span
                      v-for="pKey in u.admin_permissions"
                      :key="pKey"
                      class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-white/90 border border-slate-200 text-slate-700 shadow-xs"
                      :title="getPermissionLabel(pKey)"
                    >
                      {{ getPermissionBadge(pKey) }}
                    </span>
                  </div>
                  <div v-else class="text-[10px] text-slate-400 italic">Voll-Admin</div>
                </div>
                <span v-else-if="u.company_role === 'member'" class="text-xs text-slate-700 font-semibold">
                  Mitarbeiter
                </span>
                <span v-else class="text-xs text-slate-400 italic">
                  Einzelbenutzer
                </span>
              </td>
              <td class="py-3.5 px-4 text-right">
                <button
                  @click="openEditUserModal(u)"
                  class="taskster_button_light px-4 text-xs h-[34px] rounded-lg shadow-xs"
                  title="Benutzer-Einstellungen bearbeiten (Plan, Rolle, Subrollen, Firma)"
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

    <!-- TAB: FINANCE & ORDERS (Für Superadmins und Admins mit 'finance' Recht) -->
    <div v-if="activeTab === 'finance'" class="space-y-6">
      <!-- Orders & Invoices Table -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200/80">
          <div>
            <h3 class="text-base font-bold text-slate-900">Abonnements & Bestellungen</h3>
            <p class="text-xs text-slate-600 font-medium mt-1">
              Übersicht aller aktiven Firmenabos, Einzellizenzen und Zahlungsmodalitäten.
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="loadOrdersData()"
              class="taskster_button_light px-4 text-xs h-[36px] rounded-lg flex items-center space-x-1.5"
            >
              <span>🔄</span>
              <span>Aktualisieren</span>
            </button>
          </div>
        </div>

        <div v-if="loadingOrders" class="py-12 text-center text-slate-500 text-sm">
          Lade Bestelldaten und Abonnements...
        </div>

        <div v-else-if="orders.length === 0" class="py-12 text-center text-slate-500 text-sm">
          Keine aktiven Bestellungen oder Abonnements hinterlegt.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <th class="py-3 px-4">Kunde / Organisation</th>
                <th class="py-3 px-4">Plan / Tarif</th>
                <th class="py-3 px-4">Lizenzen</th>
                <th class="py-3 px-4">Monatspreis</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Abrechnung</th>
                <th class="py-3 px-4 text-right">Aktionen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/80 font-medium">
              <tr v-for="order in orders" :key="order.id" class="hover:bg-white/40 transition">
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900">{{ order.customer_name }}</div>
                  <div class="text-[11px] text-slate-500">{{ order.customer_email || 'Keine E-Mail' }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider"
                    :class="{
                      'bg-purple-100 text-purple-900 border border-purple-200': order.plan === 'enterprise',
                      'bg-indigo-100 text-indigo-900 border border-indigo-200': order.plan === 'pro',
                      'bg-slate-100 text-slate-800 border border-slate-200': order.plan === 'starter'
                    }"
                  >
                    {{ order.plan_label }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-bold text-slate-800">{{ order.seat_count }} Sitze</span>
                </td>
                <td class="py-3.5 px-4">
                  <span class="font-black text-slate-900">{{ order.amount_monthly }} {{ order.currency }}</span>
                  <span class="text-[10px] text-slate-500 block">/ Monat</span>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="order.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                  >
                    {{ order.payment_status === 'paid' ? '✓ Bezahlt' : 'Ausstehend' }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span class="text-slate-600 text-[11px]">{{ order.next_billing || 'Monatlich automatisch' }}</span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="generateInvoice(order.customer_name)"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold border border-slate-300 bg-white/70 hover:bg-white text-slate-700 transition cursor-pointer"
                  >
                    Rechnung
                  </button>
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



    <!-- TAB 4: PROJECT TEMPLATES (Liquid Glass Cards) -->
    <div v-if="activeTab === 'templates'" class="space-y-6">
      <div class="liquid_glass rounded-3xl p-6 sm:p-7 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-black text-slate-900 flex items-center space-x-2">
            <span>📋</span>
            <span>Projekt-Vorlagen (Gewerbe, Jobs & Privat)</span>
          </h3>
          <p class="text-xs text-slate-600 font-medium mt-1 max-w-2xl">
            Verwalte strukturierte Vorlagen mit Standard-Abschnitten und benutzerdefinierten Feldern inklusive bedingter IF-THEN-Logik. Benutzer können diese beim Erstellen eines neuen Projekts auswählen.
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
            <span>Job & Gewerbe ({{ jobTemplatesCount }})</span>
          </button>
          <button
            @click="templateCategoryFilter = 'private'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 cursor-pointer"
            :class="templateCategoryFilter === 'private' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-white/60 hover:bg-white text-slate-700 border border-white/70 shadow-xs'"
          >
            <span>🏡</span>
            <span>Privat ({{ privateTemplatesCount }})</span>
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
                <span>Vordefinierte Abschnitte ({{ tmpl.lists?.length || 0 }})</span>
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
                    <span class="text-[10px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold">
                      {{ getFieldTypeLabel(f.field_type) }}
                    </span>
                    <span
                      class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded"
                      :class="f.entity_type === 'project' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-cyan-100 text-cyan-800 border border-cyan-200'"
                    >
                      {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                    </span>
                  </div>

                  <span
                    v-if="f.logic_rules && f.logic_rules.depends_on_value"
                    class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-lg shrink-0 ml-2 font-medium"
                    :title="`Nur sichtbar wenn Bedingung erfüllt ist`"
                  >
                    ⚡ Sichtbar bei: "{{ f.logic_rules.depends_on_value }}"
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

    <!-- TAB 5: EMAIL & NOTIFICATIONS MANAGEMENT -->
    <div v-if="activeTab === 'email'" class="space-y-6">
      <!-- Sub-Navigation -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 bg-white border border-slate-200 rounded-xl">
        <div class="flex items-center space-x-2 overflow-x-auto w-full sm:w-auto">
          <button
            @click="emailSubTab = 'settings'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
            :class="emailSubTab === 'settings' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200'"
          >
            <Server class="w-4 h-4" />
            <span>E-Mail & Versand (Resend / SMTP)</span>
          </button>
          <button
            @click="emailSubTab = 'templates'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
            :class="emailSubTab === 'templates' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200'"
          >
            <FileText class="w-4 h-4" />
            <span>Trigger-Vorlagen</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 text-current">{{ emailTemplates.length }}</span>
          </button>
          <button
            @click="emailSubTab = 'outbox'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-2 cursor-pointer"
            :class="emailSubTab === 'outbox' ? 'bg-[#00A3C4] text-white shadow-sm' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200'"
          >
            <Inbox class="w-4 h-4" />
            <span>Versand-Protokoll</span>
          </button>
        </div>

        <div class="flex items-center space-x-2 shrink-0">
          <button
            @click="openTestEmailModal()"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg inline-flex items-center space-x-1.5 shadow-sm"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Test-E-Mail senden</span>
          </button>
        </div>
      </div>

      <!-- Sub-Tab 1: Email Settings -->
      <div v-if="emailSubTab === 'settings'" class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-200/80 mb-6">
          <div>
            <h3 class="text-base font-bold text-slate-900">Zentrale E-Mail-Einstellungen</h3>
            <p class="text-xs text-slate-600 mt-0.5">
              Alle automatischen E-Mails, Kalendereinladungen und Benachrichtigungen werden über diesen Dienst versendet.
            </p>
          </div>
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-50 border border-cyan-200 text-[#00A3C4] text-xs font-bold">
            <span class="w-2 h-2 rounded-full bg-[#00A3C4] animate-pulse"></span>
            <span>Aktiv: {{ smtpConfig.mail_provider === 'resend' ? 'Resend API (noreply@kurka.ch)' : (smtpConfig.smtp_user || 'noreply@kurka.ch') }}</span>
          </div>
        </div>

        <div v-if="smtpSavedMessage" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center space-x-2">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{{ smtpSavedMessage }}</span>
        </div>

        <form @submit.prevent="saveEmailSettings" class="space-y-6">
          <!-- Provider Selection Cards -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-2.5">E-Mail Versand-Methode</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
              <!-- Resend Option -->
              <div
                @click="smtpConfig.mail_provider = 'resend'"
                class="p-4 rounded-2xl border-2 cursor-pointer transition flex items-start space-x-3.5"
                :class="smtpConfig.mail_provider === 'resend' ? 'border-[#00A3C4] bg-cyan-50/50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'"
              >
                <div class="w-4 h-4 mt-0.5 rounded-full border-2 flex items-center justify-center shrink-0" :class="smtpConfig.mail_provider === 'resend' ? 'border-[#00A3C4]' : 'border-slate-300'">
                  <div v-if="smtpConfig.mail_provider === 'resend'" class="w-2 h-2 rounded-full bg-[#00A3C4]"></div>
                </div>
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-xs font-black text-slate-900">Resend API</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Empfohlen & Aktiv</span>
                  </div>
                  <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                    100% Zustellrate zu Outlook, Gmail und Apple Mail mit kryptografischer DKIM/SPF-Signatur. Keine IP-Sperren.
                  </p>
                </div>
              </div>

              <!-- SMTP Option -->
              <div
                @click="smtpConfig.mail_provider = 'smtp'"
                class="p-4 rounded-2xl border-2 cursor-pointer transition flex items-start space-x-3.5"
                :class="smtpConfig.mail_provider === 'smtp' ? 'border-[#00A3C4] bg-cyan-50/50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'"
              >
                <div class="w-4 h-4 mt-0.5 rounded-full border-2 flex items-center justify-center shrink-0" :class="smtpConfig.mail_provider === 'smtp' ? 'border-[#00A3C4]' : 'border-slate-300'">
                  <div v-if="smtpConfig.mail_provider === 'smtp'" class="w-2 h-2 rounded-full bg-[#00A3C4]"></div>
                </div>
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-xs font-black text-slate-900">Eigener SMTP-Server</span>
                  </div>
                  <p class="text-[11px] text-slate-600 mt-1 leading-relaxed">
                    Manuelle SMTP-Verbindung über Postfix/Exim (z.B. mail.kurka.ch oder Firmen-Mailserver).
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Resend Settings Section -->
          <div v-if="smtpConfig.mail_provider === 'resend'" class="p-5 rounded-2xl bg-white border border-slate-200 space-y-4 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <span class="text-xs font-black text-slate-900 flex items-center space-x-1.5">
                <span>Resend Konfiguration</span>
              </span>
              <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 border border-emerald-200 text-emerald-700">
                <CheckCircle2 class="w-3 h-3 text-emerald-600" />
                <span>Domain kurka.ch verifiziert</span>
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="text-xs font-bold text-slate-800">Resend API-Key *</label>
                  <button
                    type="button"
                    @click="showResendKey = !showResendKey"
                    class="text-[11px] font-bold text-[#00A3C4] hover:underline flex items-center space-x-1 cursor-pointer"
                  >
                    <span v-if="showResendKey">Verbergen</span>
                    <span v-else>Anzeigen</span>
                  </button>
                </div>
                <input
                  v-model="smtpConfig.resend_api_key"
                  :type="showResendKey ? 'text' : 'password'"
                  placeholder="re_xxxxxxxxxxxx"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:border-[#00A3C4] focus:bg-white focus:outline-none shadow-xs"
                />
                <p class="text-[11px] text-slate-500 mt-1">Key von <a href="https://resend.com/api-keys" target="_blank" class="text-[#00A3C4] underline">resend.com/api-keys</a></p>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Absender-E-Mail (From Address) *</label>
                <input
                  v-model="smtpConfig.smtp_from_email"
                  type="email"
                  required
                  placeholder="noreply@kurka.ch"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:bg-white focus:outline-none shadow-xs"
                />
                <p class="text-[11px] text-slate-500 mt-1">Muss eine Adresse der verifizierten Domain kurka.ch sein.</p>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Absender-Name (From Name) *</label>
                <input
                  v-model="smtpConfig.smtp_from_name"
                  type="text"
                  required
                  placeholder="Taskster"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:bg-white focus:outline-none shadow-xs"
                />
              </div>
            </div>

            <!-- 5 Dedicated Sender Identities for kurka.ch -->
            <div class="pt-4 border-t border-slate-200/80">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h4 class="text-xs font-black text-slate-900">Dedizierte Absender-Identitäten (@kurka.ch)</h4>
                  <p class="text-[11px] text-slate-600 mt-0.5">
                    Dank verifizierter Domain sofort einsatzbereit ohne separate Postfächer.
                  </p>
                </div>
                <span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 border border-cyan-200 text-[#00A3C4]">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#00A3C4]"></span>
                  <span>5 Adressen aktiv</span>
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <!-- hey@kurka.ch -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-black text-slate-900 font-mono">hey@kurka.ch</span>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-800 border border-purple-200">Onboarding</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-snug">
                      Onboarding, Willkommensnachrichten &amp; direkte Ansprache.
                    </p>
                  </div>
                  <div class="mt-3 pt-2 border-t border-slate-200/60 text-[10px] text-slate-500 flex items-center justify-between">
                    <span class="text-purple-700 font-medium">Reply-To: support@kurka.ch</span>
                    <button type="button" @click="testSpecificSender('hey@kurka.ch')" class="text-[#00A3C4] font-bold hover:underline cursor-pointer">Testen</button>
                  </div>
                </div>

                <!-- updates@kurka.ch -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-black text-slate-900 font-mono">updates@kurka.ch</span>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-800 border border-blue-200">News</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-snug">
                      Changelogs, Produkt-News &amp; Newsletter.
                    </p>
                  </div>
                  <div class="mt-3 pt-2 border-t border-slate-200/60 text-[10px] text-slate-500 flex items-center justify-between">
                    <span>Kein Reply-To</span>
                    <button type="button" @click="testSpecificSender('updates@kurka.ch')" class="text-[#00A3C4] font-bold hover:underline cursor-pointer">Testen</button>
                  </div>
                </div>

                <!-- team@kurka.ch -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-black text-slate-900 font-mono">team@kurka.ch</span>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Team</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-snug">
                      Kollaboration: Zuweisungen, Erwähnungen, Kommentare, Termine.
                    </p>
                  </div>
                  <div class="mt-3 pt-2 border-t border-slate-200/60 text-[10px] text-slate-500 flex items-center justify-between">
                    <span>Kein Reply-To</span>
                    <button type="button" @click="testSpecificSender('team@kurka.ch')" class="text-[#00A3C4] font-bold hover:underline cursor-pointer">Testen</button>
                  </div>
                </div>

                <!-- notify@kurka.ch -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-black text-slate-900 font-mono">notify@kurka.ch</span>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Fristen</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-snug">
                      Benachrichtigungen, Fristen, Statusänderungen &amp; Erinnerungen.
                    </p>
                  </div>
                  <div class="mt-3 pt-2 border-t border-slate-200/60 text-[10px] text-slate-500 flex items-center justify-between">
                    <span>Kein Reply-To</span>
                    <button type="button" @click="testSpecificSender('notify@kurka.ch')" class="text-[#00A3C4] font-bold hover:underline cursor-pointer">Testen</button>
                  </div>
                </div>

                <!-- system@kurka.ch -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-black text-slate-900 font-mono">system@kurka.ch</span>
                      <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Sicherheit</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-snug">
                      Transaktionsmails: Passwort-Resets, Sicherheitswarnungen, Account.
                    </p>
                  </div>
                  <div class="mt-3 pt-2 border-t border-slate-200/60 text-[10px] text-slate-500 flex items-center justify-between">
                    <span>Kein Reply-To</span>
                    <button type="button" @click="testSpecificSender('system@kurka.ch')" class="text-[#00A3C4] font-bold hover:underline cursor-pointer">Testen</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SMTP Settings Section -->
          <div v-else class="p-5 rounded-2xl bg-white border border-slate-200 space-y-4 shadow-xs">
            <div class="pb-3 border-b border-slate-100">
              <span class="text-xs font-black text-slate-900">Manuelle SMTP-Server Konfiguration</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">SMTP Host / Server *</label>
                <input
                  v-model="smtpConfig.smtp_host"
                  type="text"
                  required
                  placeholder="mail.kurka.ch"
                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:border-[#00A3C4] focus:outline-none shadow-xs"
                />
                <p class="text-[11px] text-slate-500 mt-1">z.B. mail.kurka.ch oder smtp.ihredomain.ch</p>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Port & Verschlüsselung *</label>
                <div class="grid grid-cols-2 gap-3">
                  <input
                    v-model.number="smtpConfig.smtp_port"
                    type="number"
                    required
                    placeholder="465"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:border-[#00A3C4] focus:outline-none shadow-xs"
                  />
                  <select
                    v-model="smtpConfig.smtp_secure"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-bold focus:border-[#00A3C4] focus:outline-none shadow-xs"
                  >
                    <option value="ssl">SSL / TLS (Port 465)</option>
                    <option value="tls">STARTTLS (Port 587)</option>
                    <option value="none">Keine Verschlüsselung (Port 25)</option>
                  </select>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Empfohlen: SSL (465) oder STARTTLS (587)</p>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">SMTP Benutzername *</label>
                <input
                  v-model="smtpConfig.smtp_user"
                  type="text"
                  required
                  placeholder="noreply@kurka.ch"
                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:border-[#00A3C4] focus:outline-none shadow-xs"
                />
              </div>

              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label class="text-xs font-bold text-slate-800">SMTP Passwort *</label>
                  <button
                    type="button"
                    @click="showSmtpPassword = !showSmtpPassword"
                    class="text-[11px] font-bold text-[#00A3C4] hover:underline flex items-center space-x-1 cursor-pointer"
                  >
                    <span v-if="showSmtpPassword">Passwort verbergen</span>
                    <span v-else>Passwort anzeigen</span>
                  </button>
                </div>
                <input
                  v-model="smtpConfig.smtp_password"
                  :type="showSmtpPassword ? 'text' : 'password'"
                  placeholder="SMTP Kennwort"
                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:border-[#00A3C4] focus:outline-none shadow-xs"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Absender-E-Mail (From Address) *</label>
                <input
                  v-model="smtpConfig.smtp_from_email"
                  type="email"
                  required
                  placeholder="noreply@kurka.ch"
                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Absender-Name (From Name) *</label>
                <input
                  v-model="smtpConfig.smtp_from_name"
                  type="text"
                  required
                  placeholder="Taskster"
                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs"
                />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between pt-6 border-t border-slate-200/80">
            <button
              type="button"
              @click="openTestEmailModal()"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Test-E-Mail senden
            </button>

            <button
              type="submit"
              :disabled="savingSmtp"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span v-if="savingSmtp">Wird gespeichert...</span>
              <span v-else>Einstellungen speichern</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Sub-Tab 2: Trigger Templates -->
      <div v-if="emailSubTab === 'templates'" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-white border border-slate-200 rounded-xl">
          <div>
            <h3 class="text-sm font-bold text-slate-900">E-Mail Trigger & Vorlagen</h3>
            <p class="text-xs text-slate-600 mt-0.5">
              Automatische Benachrichtigungen für Aktionen wie Zuweisungen, Fristen, Kommentare, Einladungen und Budget-Warnungen.
            </p>
          </div>
          <button
            @click="resetAllEmailTemplates"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg shrink-0"
          >
            Alle auf Standard zurücksetzen
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="tmpl in emailTemplates"
            :key="tmpl.id"
            class="liquid_glass_card rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4 border border-slate-200"
          >
            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <div class="flex items-center space-x-2">
                  <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ tmpl.trigger_event }}
                  </span>
                  <span
                    class="px-2 py-0.5 rounded text-[10px] font-mono font-bold"
                    :class="getTemplateSenderInfo(tmpl.trigger_event).color"
                  >
                    Absender: {{ getTemplateSenderInfo(tmpl.trigger_event).email }}
                  </span>
                </div>
                <label class="inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="tmpl.is_active"
                    @change="toggleEmailTemplateActive(tmpl)"
                    class="sr-only peer"
                  />
                  <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00A3C4]"></div>
                  <span class="ml-2 text-xs font-bold" :class="tmpl.is_active ? 'text-emerald-700' : 'text-slate-400'">
                    {{ tmpl.is_active ? 'Aktiv' : 'Deaktiviert' }}
                  </span>
                </label>
              </div>

              <h4 class="text-sm font-bold text-slate-900 mb-1">{{ tmpl.name }}</h4>
              <p class="text-xs text-slate-600 mb-3">{{ tmpl.description || 'Automatische Systemvorlage' }}</p>

              <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 mb-3">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Betreff</div>
                <div class="text-xs font-mono font-medium text-slate-800 break-all">{{ tmpl.subject }}</div>
              </div>

              <div>
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Verfügbare Variablen</div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="v in tmpl.variables"
                    :key="v"
                    class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-50 text-[#0891B2] border border-cyan-200"
                  >
                    &#123;&#123;{{ v }}&#125;&#125;
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-200/80">
              <button
                @click="resetSingleEmailTemplate(tmpl.id)"
                class="text-xs text-slate-500 hover:text-slate-800 font-medium cursor-pointer"
              >
                Zurücksetzen
              </button>
              <button
                @click="openEditEmailTemplateModal(tmpl)"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg"
              >
                Vorlage bearbeiten
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Sub-Tab 3: Outbox Logs -->
      <div v-if="emailSubTab === 'outbox'" class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
        <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">E-Mail Versand-Protokoll (Outbox)</h3>
            <p class="text-xs text-slate-600 font-medium">Verlauf der letzten E-Mail-Sendungen und Status.</p>
          </div>
          <button
            @click="loadEmailOutbox"
            class="taskster_button_light px-6 text-xs h-[42px] rounded-lg shrink-0"
          >
            Aktualisieren
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
              <tr>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Empfänger</th>
                <th class="py-3 px-4">Betreff</th>
                <th class="py-3 px-4">Erstellt am</th>
                <th class="py-3 px-4">Gesendet am / Fehler</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/60 text-slate-800">
              <tr v-if="emailOutbox.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-500 italic">Noch keine E-Mails im Protokoll vorhanden.</td>
              </tr>
              <tr v-for="item in emailOutbox" :key="item.id" class="hover:bg-white/60 transition">
                <td class="py-3 px-4">
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="{
                      'bg-emerald-100 text-emerald-900 border border-emerald-300': item.status === 'sent',
                      'bg-amber-100 text-amber-900 border border-amber-300': item.status === 'pending',
                      'bg-rose-100 text-rose-900 border border-rose-300': item.status === 'error'
                    }"
                  >
                    {{ item.status }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900">{{ item.to_name || item.to_email }}</div>
                  <div class="text-[11px] text-slate-500 font-mono">{{ item.to_email }}</div>
                </td>
                <td class="py-3 px-4 font-medium text-slate-800 max-w-xs truncate">
                  {{ item.subject }}
                </td>
                <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                  {{ item.created_at ? new Date(item.created_at).toLocaleString('de-CH') : '-' }}
                </td>
                <td class="py-3 px-4">
                  <span v-if="item.status === 'sent'" class="text-emerald-700 font-medium">
                    {{ item.sent_at ? new Date(item.sent_at).toLocaleString('de-CH') : 'Gesendet' }}
                  </span>
                  <span v-else-if="item.error" class="text-rose-600 font-mono text-[11px]" :title="item.error">
                    {{ item.error.substring(0, 45) }}{{ item.error.length > 45 ? '...' : '' }}
                  </span>
                  <span v-else class="text-slate-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 7: SECURITY & AUDIT LOGS -->
    <div v-if="activeTab === 'audit'" class="space-y-6">
      <div class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
        <div class="p-4 sm:p-6 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Sicherheits- & Revisionsprotokoll (Audit Trail)</h3>
            <p class="text-xs text-slate-600 font-medium">Vollständige Aufzeichnung aller Benutzeraktionen, Berechtigungsänderungen und System-Events.</p>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:w-64">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="auditSearch"
                @input="fetchAuditLogs"
                type="text"
                placeholder="Suche (Aktion, User, IP...)"
                class="w-full pl-9 pr-3 py-2 bg-white/80 border border-slate-200 rounded-lg text-xs font-medium focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
              />
            </div>

            <select
              v-model="auditActionFilter"
              @change="fetchAuditLogs"
              class="w-full sm:w-48 py-2 px-3 bg-white/80 border border-slate-200 rounded-lg text-xs font-medium focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
            >
              <option value="">Alle Aktionen</option>
              <option v-for="act in auditLogsActions" :key="act" :value="act">{{ act }}</option>
            </select>

            <button
              @click="fetchAuditLogs"
              class="taskster_button_light px-4 text-xs h-[38px] rounded-lg shadow-xs flex items-center gap-1.5 shrink-0"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loadingAuditLogs }" />
              <span>Neu laden</span>
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
              <tr>
                <th class="py-3.5 px-4">Zeitstempel</th>
                <th class="py-3.5 px-4">Aktion</th>
                <th class="py-3.5 px-4">Benutzer & Organisation</th>
                <th class="py-3.5 px-4">Entität</th>
                <th class="py-3.5 px-4">IP-Adresse & Client</th>
                <th class="py-3.5 px-4 text-right">Details</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/60 text-slate-800">
              <tr v-if="loadingAuditLogs && auditLogs.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                  Protokolle werden geladen...
                </td>
              </tr>
              <tr v-else-if="auditLogs.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-500 text-xs italic">
                  Keine Audit-Logs gefunden.
                </td>
              </tr>
              <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-white/60 transition">
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <div class="font-bold text-slate-900">{{ new Date(log.created_at).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ log.id }}</div>
                </td>
                <td class="py-3.5 px-4">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-200">
                    {{ log.action }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <div v-if="log.user_name" class="font-bold text-slate-900">{{ log.user_name }}</div>
                  <div v-if="log.user_email" class="text-[10px] text-slate-500 font-mono">{{ log.user_email }}</div>
                  <div v-if="log.company_name" class="text-[10px] text-emerald-700 font-semibold">{{ log.company_name }}</div>
                  <div v-if="!log.user_name && !log.user_email" class="text-slate-400 italic">System / Anonym</div>
                </td>
                <td class="py-3.5 px-4">
                  <div v-if="log.entity_type" class="font-semibold text-slate-700">
                    {{ log.entity_type }}
                    <span v-if="log.entity_id" class="text-[10px] text-slate-400 font-mono">({{ log.entity_id }})</span>
                  </div>
                  <div v-else class="text-slate-400 italic">-</div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-mono text-xs text-slate-800">{{ log.ip_address || '127.0.0.1' }}</div>
                  <div class="text-[10px] text-slate-400 truncate max-w-[180px]" :title="log.user_agent">{{ log.user_agent || 'Client' }}</div>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="selectedAuditLog = log"
                    class="taskster_button_light px-3 py-1 text-[11px] rounded-lg shadow-xs"
                  >
                    <span>🔍 Details</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- AUDIT LOG DETAIL MODAL -->
    <div v-if="selectedAuditLog" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
          <div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-900 border border-purple-200">
              {{ selectedAuditLog.action }}
            </span>
            <h3 class="text-base font-bold text-slate-900 mt-1">Audit Log Details</h3>
          </div>
          <button @click="selectedAuditLog = null" class="p-1 rounded-lg hover:bg-slate-100 text-slate-500">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="py-4 space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
            <div>
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Log-ID:</span>
              <span class="font-mono font-bold text-slate-900">{{ selectedAuditLog.id }}</span>
            </div>
            <div>
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Zeitstempel:</span>
              <span class="font-bold text-slate-900">{{ new Date(selectedAuditLog.created_at).toLocaleString('de-CH') }}</span>
            </div>
            <div>
              <span class="text-slate-500 block text-[10px] uppercase font-bold">Benutzer:</span>
              <span class="font-bold text-slate-900">{{ selectedAuditLog.user_name || 'System' }} ({{ selectedAuditLog.user_email || '-' }})</span>
            </div>
            <div>
              <span class="text-slate-500 block text-[10px] uppercase font-bold">IP-Adresse:</span>
              <span class="font-mono font-bold text-slate-900">{{ selectedAuditLog.ip_address || 'Unbekannt' }}</span>
            </div>
          </div>

          <div>
            <span class="text-slate-700 font-bold block mb-1">Details & Payload:</span>
            <pre class="bg-slate-900 text-emerald-400 p-3 rounded-lg text-[11px] font-mono overflow-x-auto max-h-60">{{ JSON.stringify(selectedAuditLog.details, null, 2) }}</pre>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-200 text-right">
          <button @click="selectedAuditLog = null" class="taskster_button px-6 text-xs h-[38px] rounded-lg">
            Schliessen
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 8: WEBSITE MANAGEMENT (Webseiten-Verwaltung) -->
    <div v-if="activeTab === 'website'" class="space-y-6">
      <div class="liquid_glass rounded-3xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-4">
          <div>
            <h3 class="text-base font-bold text-slate-900">Webseiten-Verwaltung & CMS</h3>
            <p class="text-xs text-slate-600 font-medium">Steuere Inhalte der öffentlichen Webseite, Preismodelle, Ankündigungs-Banner und SEO.</p>
          </div>
          <button
            @click="saveWebsiteSettings"
            :disabled="savingWebsiteSettings"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm flex items-center gap-2 cursor-pointer shrink-0"
          >
            <Save class="w-4 h-4" />
            <span>{{ savingWebsiteSettings ? 'Speichere...' : 'Einstellungen speichern' }}</span>
          </button>
        </div>

        <div v-if="websiteSettingsSavedNotice" class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-xs text-emerald-900 font-bold flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 text-emerald-600" />
          <span>Webseiten-Einstellungen erfolgreich aktualisiert!</span>
        </div>

        <!-- 1. Hero & Kontakt Section -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <Globe class="w-4 h-4 text-cyan-600" />
            <span>Landingpage & Allgemeine Webseiten-Informationen</span>
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <!-- Webseiten- & Browser-Titel (<title>) -->
            <div class="md:col-span-2">
              <div class="flex items-center justify-between mb-1">
                <label class="block font-bold text-slate-800">Webseiten- & Browser-Titel (&lt;title&gt;)</label>
                <span class="text-[11px] text-cyan-700 font-semibold bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200">Browser-Tab & HTML &lt;title&gt;</span>
              </div>
              <input
                v-model="websiteSettings.website_title"
                type="text"
                placeholder="z. B. Taskster – Professionelles Projekt- & Bauleitermanagement"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium"
              />
              <p class="text-[11px] text-slate-500 mt-1">Dieser globale Titel erscheint oben im Browser-Tab, in Bookmarks sowie als Haupttitel bei Google / Social Media.</p>
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1">Hero Hauptüberschrift</label>
              <input
                v-model="websiteSettings.website_hero_title"
                type="text"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1">Hero Untertitel / Beschreibung</label>
              <input
                v-model="websiteSettings.website_hero_subtitle"
                type="text"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1">Support E-Mail Adresse</label>
              <input
                v-model="websiteSettings.website_contact_email"
                type="email"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1">Support Telefonnummer</label>
              <input
                v-model="websiteSettings.website_contact_phone"
                type="text"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium font-mono"
              />
            </div>
          </div>

          <!-- Live Browser-Tab Vorschau -->
          <div class="mt-3 p-3.5 rounded-xl bg-slate-900 text-white space-y-2 border border-slate-800">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
              <span>Live-Vorschau: Browser-Tab</span>
            </div>
            <div class="inline-flex items-center gap-2 bg-slate-800/90 px-3.5 py-1.5 rounded-lg border border-slate-700/80 max-w-full">
              <span class="w-3.5 h-3.5 rounded-full bg-[#00A3C4] text-[9px] font-black text-slate-950 flex items-center justify-center shrink-0">T</span>
              <span class="text-xs font-medium text-slate-200 truncate">{{ websiteSettings.website_title || websiteSettings.website_seo_title || 'Taskster - Professionelles Projekt- & Bauleitermanagement' }}</span>
              <span class="text-slate-500 text-xs ml-1 font-bold">×</span>
            </div>
          </div>
        </div>

        <!-- 2. Preismodell Steuerung -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <CreditCard class="w-4 h-4 text-purple-600" />
            <span>Preismodelle & Tarife auf der Webseite</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
              <span class="font-bold text-slate-800 block text-[11px] uppercase">Free / Basic Plan</span>
              <input
                v-model="websiteSettings.website_pricing_basic_price"
                type="text"
                placeholder="0 CHF"
                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold"
              />
            </div>
            <div class="p-3.5 bg-amber-50/50 border border-amber-200 rounded-xl space-y-2">
              <span class="font-bold text-amber-900 block text-[11px] uppercase">PRO Plan</span>
              <input
                v-model="websiteSettings.website_pricing_pro_price"
                type="text"
                placeholder="29 CHF"
                class="w-full px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs font-bold"
              />
            </div>
            <div class="p-3.5 bg-purple-50/50 border border-purple-200 rounded-xl space-y-2">
              <span class="font-bold text-purple-900 block text-[11px] uppercase">ENTERPRISE Plan</span>
              <input
                v-model="websiteSettings.website_pricing_enterprise_price"
                type="text"
                placeholder="Auf Anfrage"
                class="w-full px-3 py-2 bg-white border border-purple-300 rounded-lg text-xs font-bold"
              />
            </div>
          </div>
        </div>

        <!-- 3. Ankündigung & Wartungsmodus -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <AlertCircle class="w-4 h-4 text-amber-600" />
            <span>In-App Ankündigungs-Banner & Wartungsmodus</span>
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="space-y-3">
              <label class="flex items-center space-x-3 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="websiteSettings.website_announcement_active"
                  class="sr-only peer"
                />
                <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-900"></div>
                <span class="text-xs font-bold text-slate-900">Ankündigung-Banner auf der Webseite anzeigen</span>
              </label>

              <input
                v-model="websiteSettings.website_announcement_text"
                type="text"
                placeholder="Banner-Nachricht eingeben..."
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium"
              />
            </div>

            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl space-y-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <Lock class="w-4 h-4 text-rose-700" />
                  <span class="font-bold text-rose-900 text-xs">Wartungsmodus (Maintenance Switch)</span>
                </div>
                <label class="flex items-center space-x-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="websiteSettings.website_maintenance_mode"
                    class="sr-only peer"
                  />
                  <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-600"></div>
                </label>
              </div>
              <p class="text-[11px] text-rose-700">Wenn aktiviert, können sich nur Superadmins in die Plattform einloggen. Normale Nutzer sehen einen Wartungshinweis.</p>
            </div>
          </div>
        </div>

        <!-- 4. SEO & Metadaten -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <FileText class="w-4 h-4 text-emerald-600" />
            <span>SEO & Suchmaschinen-Optimierung</span>
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div>
              <label class="block font-bold text-slate-800 mb-1">Globaler Meta-Titel</label>
              <input
                v-model="websiteSettings.website_seo_title"
                type="text"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1">Globaler Meta-Beschreibungstext</label>
              <textarea
                v-model="websiteSettings.website_seo_description"
                rows="2"
                class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Google SERP Vorschau -->
          <div class="mt-2 p-3.5 rounded-xl bg-white border border-slate-200 text-xs space-y-1">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Google Suchergebnis-Vorschau</div>
            <div class="text-[11px] text-emerald-700 font-mono">https://taskster.ch</div>
            <div class="text-sm font-bold text-blue-700 hover:underline cursor-pointer">
              {{ websiteSettings.website_title || websiteSettings.website_seo_title || 'Taskster - Professionelles Projekt- & Bauleitermanagement' }}
            </div>
            <div class="text-xs text-slate-600 line-clamp-2">
              {{ websiteSettings.website_seo_description || 'Taskster - Enterprise Projekt- und Bauleitermanagement Plattform' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB: KI & AI-PLÄNE (Steuerung von Modellen & Tarif-Freischaltungen) -->
    <div v-if="activeTab === 'ai'" class="space-y-6">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl border border-white/80">
        
        <!-- Header Info Card -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-purple-500/10 via-cyan-500/10 to-transparent border border-purple-200/60">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center shadow-md shrink-0">
              <Sparkles class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-900">Taskster AI-Engine & Tarif-Berechtigungen</h3>
              <p class="text-xs text-slate-600 mt-0.5 font-medium">
                Steuere hier die KI-Funktionen für Bautagebuch, Aufgaben-Generierung, Sprachnotizen und welche Features für welche Tarife freigeschaltet sind.
              </p>
            </div>
          </div>
          <button
            @click="saveAiSettings"
            :disabled="savingAiSettings"
            class="taskster_button px-6 text-xs h-[42px] rounded-lg shrink-0 shadow-md flex items-center gap-2"
          >
            <Save class="w-4 h-4" />
            <span>{{ savingAiSettings ? 'Speichere...' : 'Einstellungen speichern' }}</span>
          </button>
        </div>

        <!-- Section 1: Tarif-Freischaltungs-Matrix (Plan Entitlements) -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
              <ShieldCheck class="w-4 h-4 text-purple-600" />
              <span>Tarif-Freischaltung & Quotas (Was ist in welchem Plan freigeschaltet?)</span>
            </h4>
            <span class="text-[11px] font-semibold text-slate-500">Zero-Trust serverseitig erzwungen</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Free / Basic Plan -->
            <div class="p-4 rounded-2xl border transition-all" :class="aiSettings.ai_plan_basic_enabled ? 'border-purple-300 bg-purple-50/30' : 'border-slate-200 bg-slate-50/50'">
              <div class="flex items-center justify-between mb-3">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-slate-200 text-slate-700">Free / Basic</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="aiSettings.ai_plan_basic_enabled" class="sr-only peer" />
                  <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
              </div>
              <p class="text-xs text-slate-600 font-medium mb-3">Kostenlose Accounts & Probe-Nutzer</p>
              
              <div class="space-y-3 pt-3 border-t border-slate-200/60 text-xs">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Max. Anfragen / Monat</label>
                  <input
                    v-model.number="aiSettings.ai_plan_basic_monthly_limit"
                    type="number"
                    min="0"
                    max="10000"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none font-medium"
                  />
                </div>
                <div class="flex items-center justify-between pt-1">
                  <span class="font-bold text-slate-700">Audio-Transkription (Whisper)</span>
                  <input type="checkbox" v-model="aiSettings.ai_plan_basic_audio_enabled" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer" />
                </div>
              </div>
            </div>

            <!-- Pro Plan -->
            <div class="p-4 rounded-2xl border-2 transition-all border-[#00A3C4]/60 bg-cyan-50/20">
              <div class="flex items-center justify-between mb-3">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-[#00A3C4] text-white">Pro Plan</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="aiSettings.ai_plan_pro_enabled" class="sr-only peer" />
                  <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#00A3C4]"></div>
                </label>
              </div>
              <p class="text-xs text-slate-600 font-medium mb-3">Handwerker, Bauleiter & Einzelfirmen</p>
              
              <div class="space-y-3 pt-3 border-t border-slate-200/60 text-xs">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Max. Anfragen / Monat</label>
                  <input
                    v-model.number="aiSettings.ai_plan_pro_monthly_limit"
                    type="number"
                    min="10"
                    max="100000"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none font-medium"
                  />
                </div>
                <div class="flex items-center justify-between pt-1">
                  <span class="font-bold text-slate-700">Audio-Transkription (Whisper)</span>
                  <input type="checkbox" v-model="aiSettings.ai_plan_pro_audio_enabled" class="rounded text-[#00A3C4] focus:ring-[#00A3C4] w-4 h-4 cursor-pointer" />
                </div>
              </div>
            </div>

            <!-- Enterprise Plan -->
            <div class="p-4 rounded-2xl border-2 transition-all border-purple-400 bg-purple-50/20">
              <div class="flex items-center justify-between mb-3">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-purple-900 text-white">Enterprise Plan</span>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="aiSettings.ai_plan_enterprise_enabled" class="sr-only peer" />
                  <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-900"></div>
                </label>
              </div>
              <p class="text-xs text-slate-600 font-medium mb-3">Großunternehmen & Generalunternehmer</p>
              
              <div class="space-y-3 pt-3 border-t border-slate-200/60 text-xs">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Max. Anfragen / Monat</label>
                  <input
                    v-model.number="aiSettings.ai_plan_enterprise_monthly_limit"
                    type="number"
                    min="100"
                    max="1000000"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-purple-600 focus:outline-none font-medium"
                  />
                </div>
                <div class="flex items-center justify-between pt-1">
                  <span class="font-bold text-slate-700">Audio-Transkription (Whisper)</span>
                  <input type="checkbox" v-model="aiSettings.ai_plan_enterprise_audio_enabled" class="rounded text-purple-700 focus:ring-purple-700 w-4 h-4 cursor-pointer" />
                </div>
                <div class="flex items-center justify-between pt-1">
                  <span class="font-bold text-slate-700">Eigene API-Keys erlauben (BYOK)</span>
                  <input type="checkbox" v-model="aiSettings.ai_plan_enterprise_custom_key_allowed" class="rounded text-purple-700 focus:ring-purple-700 w-4 h-4 cursor-pointer" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Globale Modell- und Provider-Konfiguration -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <Bot class="w-4 h-4 text-[#00A3C4]" />
            <span>Globale KI-Modelle & Parameter</span>
          </h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div>
              <label class="block font-bold text-slate-800 mb-1">Standard LLM Modell (OpenRouter Identifier)</label>
              <input
                v-model="aiSettings.ai_model"
                type="text"
                placeholder="z.B. google/gemini-2.5-flash"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-mono"
              />
              <p class="text-[11px] text-slate-500 mt-1">Empfohlen: <code>google/gemini-2.5-flash</code></p>
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1">Audio Transkriptions-Modell</label>
              <input
                v-model="aiSettings.ai_audio_model"
                type="text"
                placeholder="openai/whisper-large-v3-turbo"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-mono"
              />
              <p class="text-[11px] text-slate-500 mt-1">Empfohlen: <code>openai/whisper-large-v3-turbo</code></p>
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1">Max. Tokens pro Antwort: {{ aiSettings.ai_max_tokens }}</label>
              <input
                v-model.number="aiSettings.ai_max_tokens"
                type="range"
                min="512"
                max="8192"
                step="256"
                class="w-full accent-[#00A3C4] cursor-pointer"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-800 mb-1">Kreativität / Temperatur: {{ aiSettings.ai_temperature }}</label>
              <input
                v-model.number="aiSettings.ai_temperature"
                type="range"
                min="0.0"
                max="1.0"
                step="0.05"
                class="w-full accent-[#00A3C4] cursor-pointer"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-800 mb-1">Globaler System-Prompt (Rolle für Bau- & Projektassistenten)</label>
            <textarea
              v-model="aiSettings.ai_system_prompt"
              rows="3"
              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium leading-relaxed resize-none"
            ></textarea>
          </div>
        </div>

        <!-- Section 3: Live KI-Testkonsole -->
        <div class="bg-white/80 rounded-2xl p-5 border border-slate-200/80 space-y-4">
          <h4 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
            <Cpu class="w-4 h-4 text-purple-600" />
            <span>Live KI-Testkonsole</span>
          </h4>
          <div class="flex flex-col sm:flex-row gap-3">
            <input
              v-model="testPrompt"
              type="text"
              placeholder="Test-Prompt eingeben..."
              class="flex-1 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-medium"
              @keydown.enter.prevent="runAiTest"
            />
            <button
              @click="runAiTest"
              :disabled="testingAi || !testPrompt.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shrink-0 flex items-center gap-2"
            >
              <RefreshCw v-if="testingAi" class="w-4 h-4 animate-spin" />
              <Sparkles v-else class="w-4 h-4" />
              <span>{{ testingAi ? 'Generiere...' : 'Testen' }}</span>
            </button>
          </div>
          <div v-if="testPromptResult" class="p-4 rounded-xl bg-slate-900 text-slate-100 text-xs font-mono whitespace-pre-wrap leading-relaxed border border-slate-800">
            {{ testPromptResult }}
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
              <label class="block text-xs font-medium text-slate-800 mb-1">Vorlagen-Name *</label>
              <input
                v-model="tmplForm.name"
                type="text"
                required
                placeholder="z.B. Bauleitung Tiefbau & LWL"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-800 mb-1">Kategorie *</label>
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
              <label class="block text-xs font-medium text-slate-800 mb-1">Unterkategorie / Branche</label>
              <input
                v-model="tmplForm.subcategory"
                type="text"
                placeholder="z.B. bau, it, handwerk, renovierung, event"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-800 mb-1">Beschreibung</label>
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
              Vordefinierte Abschnitte
            </label>
            <p class="text-[11px] text-slate-500 mb-2">
              Diese Abschnitte werden automatisch angelegt, wenn ein Projekt mit dieser Vorlage erstellt wird.
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
                    class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-800 cursor-pointer"
                  >
                    <option value="text">Textzeile (kurz)</option>
                    <option value="textarea">Längerer Text / Notizfeld</option>
                    <option value="number">Zahl / Währung / Messwert</option>
                    <option value="select">Auswahlliste (Dropdown)</option>
                    <option value="date">Datum</option>
                    <option value="checkbox">Checkbox (Ja / Nein)</option>
                    <option value="url">Weblink / URL</option>
                    <option value="email">E-Mail-Adresse</option>
                    <option value="phone">Telefonnummer</option>
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

    <!-- Modal: Create New User -->
    <div
      v-if="showCreateUserModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
    >
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl my-8 border border-white/80 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 mb-6">
          <div>
            <div class="inline-flex items-center space-x-1 text-xs font-bold text-emerald-800 px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-200 mb-1">
              <span>👤</span>
              <span>Neuer Benutzer</span>
            </div>
            <h3 class="text-lg font-black text-slate-900">
              Benutzerkonto manuell erstellen
            </h3>
          </div>
          <button
            type="button"
            @click="showCreateUserModal = false"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg cursor-pointer"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="createUser" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Name *</label>
            <input
              v-model="newUserForm.name"
              type="text"
              required
              placeholder="z.B. Beat Meier"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">E-Mail-Adresse *</label>
            <input
              v-model="newUserForm.email"
              type="email"
              required
              placeholder="beat.meier@musterfirma.ch"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="text-xs font-bold text-slate-800">Passwort *</label>
              <button
                type="button"
                @click="generateRandomPassword('new')"
                class="text-[11px] font-bold text-cyan-700 hover:text-cyan-900 flex items-center space-x-1 cursor-pointer"
              >
                <span>🎲</span>
                <span>Zufallspasswort</span>
              </button>
            </div>
            <input
              v-model="newUserForm.password"
              type="text"
              required
              minlength="6"
              placeholder="Initiales Login-Passwort (mind. 6 Zeichen)"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <!-- Plan Selection -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Benutzer-Plan (Tarif)</label>
            <select
              v-model="newUserForm.plan"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option value="basic">Taskster Free / Basic Plan (Basis: max. 1 Ordner, 3 Projekte)</option>
              <option value="pro">Taskster PRO Plan (30 Projekte, Zeitersparnis & Vorlagen)</option>
              <option value="enterprise">Taskster ENTERPRISE Plan (Unbegrenzte Projekte & Export)</option>
            </select>
          </div>

          <!-- Company Assignment -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Unternehmen zuweisen</label>
            <select
              v-model="newUserForm.company_id"
              :disabled="!user?.is_superadmin && Boolean(user?.company_id)"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium disabled:opacity-60"
            >
              <option v-if="user?.is_superadmin" value="">Keine (Privatkunde / Einzelnutzer)</option>
              <option v-for="c in companies" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.subscription_plan ? c.subscription_plan.toUpperCase() : 'STANDARD' }})
              </option>
            </select>
          </div>

          <!-- Company Role -->
          <div v-if="newUserForm.company_id">
            <label class="block text-xs font-bold text-slate-800 mb-1">Rolle im Unternehmen</label>
            <select
              v-model="newUserForm.company_role"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option value="member">Mitarbeiter (member)</option>
              <option value="admin">Administrator (admin)</option>
            </select>
          </div>

          <!-- Admin Subroles & Permissions (if admin or superadmin) -->
          <div v-if="newUserForm.company_role === 'admin' || user?.is_superadmin" class="pt-3 pb-2 border-t border-slate-200/80">
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                <span>🛡️</span>
                <span>Admin-Berechtigungen & Subrollen</span>
              </label>
              <div class="flex items-center space-x-1">
                <button
                  type="button"
                  @click="applyAdminPreset('new', 'all')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  Alle
                </button>
                <button
                  type="button"
                  @click="applyAdminPreset('new', 'users')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  User-Mgmt
                </button>
                <button
                  type="button"
                  @click="applyAdminPreset('new', 'finance')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  Finanzen
                </button>
                <button
                  type="button"
                  @click="applyAdminPreset('new', 'none')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  Keine
                </button>
              </div>
            </div>

            <div class="space-y-2 bg-white/60 p-3 rounded-2xl border border-slate-200/70">
              <label
                v-for="perm in availablePermissions"
                :key="perm.key"
                class="flex items-start space-x-2.5 cursor-pointer select-none"
              >
                <input
                  type="checkbox"
                  :value="perm.key"
                  v-model="newUserForm.admin_permissions"
                  class="w-4 h-4 mt-0.5 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
                />
                <div class="flex-1">
                  <div class="text-xs font-bold text-slate-900 flex items-center space-x-1">
                    <span>{{ perm.icon }}</span>
                    <span>{{ perm.label }}</span>
                  </div>
                  <div class="text-[10px] text-slate-500 leading-tight">{{ perm.desc }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Superadmin Checkbox (Only visible if creator is Superadmin) -->
          <div v-if="user?.is_superadmin" class="pt-3 pb-2 border-t border-slate-200/80">
            <label class="flex items-start space-x-3 cursor-pointer select-none">
              <input
                v-model="newUserForm.is_superadmin"
                type="checkbox"
                class="w-4 h-4 mt-0.5 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
              />
              <div>
                <div class="text-xs font-bold text-purple-950">Superadmin-Berechtigung</div>
                <div class="text-[11px] text-slate-600">
                  Ermöglicht uneingeschränkten Plattform-Vollzugriff auf alle Firmen und Daten.
                </div>
              </div>
            </label>
          </div>

          <!-- Modal Actions -->
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showCreateUserModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="creatingUser"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg cursor-pointer"
            >
              {{ creatingUser ? 'Erstelle...' : 'Benutzer anlegen' }}
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
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl my-8 border border-white/80 max-h-[90vh] overflow-y-auto">
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
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg cursor-pointer"
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

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="text-xs font-bold text-slate-800">Neues Passwort (optional)</label>
              <button
                type="button"
                @click="generateRandomPassword('edit')"
                class="text-[11px] font-bold text-cyan-700 hover:text-cyan-900 flex items-center space-x-1 cursor-pointer"
              >
                <span>🎲</span>
                <span>Zufallspasswort</span>
              </button>
            </div>
            <input
              v-model="editUserForm.new_password"
              type="text"
              placeholder="Leer lassen, falls Passwort unverändert bleiben soll"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs"
            />
          </div>

          <!-- Plan Selection -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Benutzer-Plan (Tarif)</label>
            <select
              v-model="editUserForm.plan"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium"
            >
              <option value="basic">Taskster Free / Basic Plan (Basis: max. 1 Ordner, 3 Projekte)</option>
              <option value="pro">Taskster PRO Plan (30 Projekte, Zeitersparnis & Vorlagen)</option>
              <option value="enterprise">Taskster ENTERPRISE Plan (Unbegrenzte Projekte & Export)</option>
            </select>
          </div>

          <!-- Company Assignment -->
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">Unternehmen / Organisation zuweisen</label>
            <select
              v-model="editUserForm.company_id"
              :disabled="!user?.is_superadmin && Boolean(user?.company_id)"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600 shadow-xs font-medium disabled:opacity-60"
            >
              <option v-if="user?.is_superadmin" value="">Keine (Privatkunde / Einzelnutzer)</option>
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

          <!-- Admin Subroles & Permissions -->
          <div v-if="editUserForm.company_role === 'admin' || user?.is_superadmin" class="pt-3 pb-2 border-t border-slate-200/80">
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-bold text-slate-900 flex items-center space-x-1.5">
                <span>🛡️</span>
                <span>Admin-Berechtigungen & Subrollen</span>
              </label>
              <div class="flex items-center space-x-1">
                <button
                  type="button"
                  @click="applyAdminPreset('edit', 'all')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  Alle
                </button>
                <button
                  type="button"
                  @click="applyAdminPreset('edit', 'users')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  User-Mgmt
                </button>
                <button
                  type="button"
                  @click="applyAdminPreset('edit', 'finance')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  Finanzen
                </button>
                <button
                  type="button"
                  @click="applyAdminPreset('edit', 'none')"
                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  Keine
                </button>
              </div>
            </div>

            <div class="space-y-2 bg-white/60 p-3 rounded-2xl border border-slate-200/70">
              <label
                v-for="perm in availablePermissions"
                :key="perm.key"
                class="flex items-start space-x-2.5 cursor-pointer select-none"
              >
                <input
                  type="checkbox"
                  :value="perm.key"
                  v-model="editUserForm.admin_permissions"
                  class="w-4 h-4 mt-0.5 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
                />
                <div class="flex-1">
                  <div class="text-xs font-bold text-slate-900 flex items-center space-x-1">
                    <span>{{ perm.icon }}</span>
                    <span>{{ perm.label }}</span>
                  </div>
                  <div class="text-[10px] text-slate-500 leading-tight">{{ perm.desc }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Superadmin Checkbox (Only editable by Superadmin) -->
          <div v-if="user?.is_superadmin" class="pt-3 pb-2 border-t border-slate-200/80">
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
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg cursor-pointer"
            >
              Abbrechen
            </button>
            <button
              type="submit"
              :disabled="savingUser"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg cursor-pointer"
            >
              {{ savingUser ? 'Speichern...' : 'Änderungen speichern' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Test Email -->
    <div v-if="showTestEmailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl my-8 border border-white/80">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 mb-5">
          <div>
            <h3 class="text-base font-black text-slate-900">E-Mail Verbindung testen</h3>
            <p class="text-xs text-slate-600">
              Versand über <strong class="text-slate-900">{{ smtpConfig.mail_provider === 'resend' ? 'Resend API (noreply@kurka.ch)' : (smtpConfig.smtp_host + ':' + smtpConfig.smtp_port) }}</strong>
            </p>
          </div>
          <button @click="showTestEmailModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">✕</button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1.5">Absender-Identität (@kurka.ch)</label>
            <select
              v-model="testEmailSender"
              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-bold focus:border-[#00A3C4] focus:outline-none shadow-xs"
            >
              <option value="hey@kurka.ch">Taskster &lt;hey@kurka.ch&gt; — Onboarding &amp; Willkommen (Reply-To: support@kurka.ch)</option>
              <option value="updates@kurka.ch">Taskster &lt;updates@kurka.ch&gt; — Changelogs &amp; News</option>
              <option value="team@kurka.ch">Taskster &lt;team@kurka.ch&gt; — Kollaboration &amp; Zuweisungen</option>
              <option value="notify@kurka.ch">Taskster &lt;notify@kurka.ch&gt; — Benachrichtigungen &amp; Fristen</option>
              <option value="system@kurka.ch">Taskster &lt;system@kurka.ch&gt; — Transaktionsmails &amp; Sicherheit</option>
              <option :value="smtpConfig.smtp_from_email">Taskster &lt;{{ smtpConfig.smtp_from_email }}&gt; — Standard</option>
            </select>
            <p class="text-[11px] text-slate-500 mt-1">
              Wird direkt als Absender im Mail-Header gesetzt.
            </p>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1.5">Empfänger-E-Mail-Adresse *</label>
            <input
              v-model="testEmailTo"
              type="email"
              required
              placeholder="ihre-email@adresse.ch"
              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs"
            />
          </div>

          <div v-if="testEmailResult" class="p-3.5 rounded-xl border text-xs" :class="testEmailResult.success ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
            <div class="font-bold mb-1 flex items-center space-x-1.5">
              <span v-if="testEmailResult.success">✅ {{ testEmailResult.message || 'E-Mail erfolgreich gesendet!' }}</span>
              <span v-else>❌ Fehler: {{ testEmailResult.error }}</span>
            </div>
            <details v-if="testEmailResult.log && testEmailResult.log.length > 0" class="mt-2">
              <summary class="cursor-pointer text-[11px] font-bold text-slate-600 hover:text-slate-900">Versand-Protokoll anzeigen ({{ testEmailResult.log.length }} Zeilen)</summary>
              <pre class="mt-2 p-2 bg-slate-900 text-emerald-400 rounded text-[10px] font-mono max-h-40 overflow-y-auto whitespace-pre-wrap">{{ testEmailResult.log.join('\n') }}</pre>
            </details>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showTestEmailModal = false"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Schliessen
            </button>
            <button
              type="button"
              @click="sendTestEmail"
              :disabled="testingEmail || !testEmailTo"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              <span v-if="testingEmail">Wird gesendet...</span>
              <span v-else>Jetzt Test-Mail senden</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Edit Email Template -->
    <div v-if="showEmailTemplateModal && editingEmailTemplate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 max-w-3xl w-full shadow-2xl my-8 border border-white/80 max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 mb-5">
          <div>
            <div class="inline-flex items-center space-x-1 text-[11px] font-mono font-bold text-[#00A3C4] px-2 py-0.5 rounded bg-cyan-50 border border-cyan-200 mb-1">
              <span>Trigger: {{ editingEmailTemplate.trigger_event }}</span>
            </div>
            <h3 class="text-base font-black text-slate-900">E-Mail-Vorlage anpassen</h3>
          </div>
          <button @click="showEmailTemplateModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1">✕</button>
        </div>

        <form @submit.prevent="saveEmailTemplate" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-800 mb-1">Vorlagen-Name</label>
              <input
                v-model="editingEmailTemplate.name"
                type="text"
                required
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-bold"
              />
            </div>

            <div class="flex items-end pb-1.5">
              <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                <input
                  v-model="editingEmailTemplate.is_active"
                  type="checkbox"
                  class="w-4 h-4 rounded text-[#00A3C4] focus:ring-[#00A3C4] border-slate-300"
                />
                <span class="text-xs font-bold text-slate-800">Vorlage aktiv (E-Mails für diesen Trigger versenden)</span>
              </label>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-800 mb-1">E-Mail-Betreff *</label>
            <input
              v-model="editingEmailTemplate.subject"
              type="text"
              required
              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:border-[#00A3C4] focus:outline-none shadow-xs font-mono"
            />
          </div>

          <!-- Variable chips helper -->
          <div>
            <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Klick zum Einfügen einer Variable in Betreff / Body:</label>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="v in editingEmailTemplate.variables"
                :key="v"
                type="button"
                @click="insertVariableInTemplate(v)"
                class="px-2.5 py-1 rounded-lg text-xs font-mono bg-cyan-50 hover:bg-cyan-100 text-[#0891B2] border border-cyan-300 transition cursor-pointer font-bold"
              >
                + &#123;&#123;{{ v }}&#125;&#125;
              </button>
            </div>
          </div>

          <!-- Body Tabs: HTML vs Preview -->
          <div>
            <div class="flex items-center justify-between border-b border-slate-200 mb-2">
              <div class="flex items-center space-x-2">
                <button
                  type="button"
                  @click="emailTemplatePreviewMode = 'html'"
                  class="px-3 py-1.5 text-xs font-bold border-b-2 transition"
                  :class="emailTemplatePreviewMode === 'html' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                  HTML-Code
                </button>
                <button
                  type="button"
                  @click="emailTemplatePreviewMode = 'preview'"
                  class="px-3 py-1.5 text-xs font-bold border-b-2 transition"
                  :class="emailTemplatePreviewMode === 'preview' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                  Vorschau (HTML)
                </button>
                <button
                  type="button"
                  @click="emailTemplatePreviewMode = 'text'"
                  class="px-3 py-1.5 text-xs font-bold border-b-2 transition"
                  :class="emailTemplatePreviewMode === 'text' ? 'border-[#00A3C4] text-[#00A3C4]' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                  Klartext-Version
                </button>
              </div>
            </div>

            <div v-if="emailTemplatePreviewMode === 'html'">
              <textarea
                v-model="editingEmailTemplate.body_html"
                rows="8"
                class="w-full px-3.5 py-2.5 bg-slate-900 text-slate-100 rounded-xl text-xs font-mono focus:outline-none focus:ring-1 focus:ring-[#00A3C4] shadow-xs"
              ></textarea>
            </div>

            <div v-else-if="emailTemplatePreviewMode === 'preview'" class="p-4 bg-white border border-slate-200 rounded-xl min-h-[160px] max-h-72 overflow-y-auto">
              <div v-html="renderEmailPreview(editingEmailTemplate)"></div>
            </div>

            <div v-else-if="emailTemplatePreviewMode === 'text'">
              <textarea
                v-model="editingEmailTemplate.body_text"
                rows="8"
                class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 font-mono focus:border-[#00A3C4] focus:outline-none shadow-xs"
              ></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200/80">
            <button
              type="button"
              @click="showEmailTemplateModal = false"
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
      </main>
    </div>
    <div v-else class="max-w-md mx-auto py-24 text-center">
      <div class="liquid_glass rounded-3xl p-8 shadow-xl">
        <div class="text-4xl mb-3">🔒</div>
        <h2 class="text-lg font-black text-slate-900 mb-1">Zugriff verweigert</h2>
        <p class="text-xs text-slate-600 mb-5 leading-relaxed">Dieser Bereich ist ausschließlich autorisierten Taskster-Plattformadministratoren vorbehalten.</p>
        <NuxtLink to="/dashboard" class="taskster_button px-6 text-xs h-[42px] rounded-lg inline-flex items-center justify-center">
          Zurück zum Dashboard
        </NuxtLink>
      </div>
    </div>

    <!-- In-App Toast Container (Zero native browser popups) -->
    <teleport to="body">
      <div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-2 pointer-events-none max-w-md w-full px-4">
        <transition-group
          enter-active-class="transition duration-300 ease-out transform"
          enter-from-class="opacity-0 translate-y-4 scale-95"
          enter-to-class="opacity-100 translate-y-0 scale-100"
          leave-active-class="transition duration-200 ease-in transform"
          leave-from-class="opacity-100 translate-y-0 scale-100"
          leave-to-class="opacity-0 translate-y-2 scale-95"
        >
          <div
            v-for="toast in toasts"
            :key="toast.id"
            class="pointer-events-auto p-4 rounded-xl shadow-2xl border flex items-center gap-3 bg-white"
            :class="toast.type === 'error' ? 'border-rose-200 text-rose-900 bg-rose-50/95' : toast.type === 'info' ? 'border-cyan-200 text-cyan-900 bg-cyan-50/95' : 'border-emerald-200 text-emerald-900 bg-emerald-50/95'"
          >
            <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-600 shrink-0" />
            <AlertCircle v-else-if="toast.type === 'error'" class="w-5 h-5 text-rose-600 shrink-0" />
            <Info v-else class="w-5 h-5 text-cyan-600 shrink-0" />
            <span class="text-xs font-semibold leading-relaxed flex-1">{{ toast.message }}</span>
            <button @click="toasts = toasts.filter(t => t.id !== toast.id)" class="text-slate-400 hover:text-slate-700 p-1">
              <X class="w-4 h-4" />
            </button>
          </div>
        </transition-group>
      </div>
    </teleport>

    <!-- In-App Confirmation Dialog (Zero Native confirm()) -->
    <teleport to="body">
      <div
        v-if="confirmDialog.isOpen"
        class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200 overflow-hidden transform transition-all p-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-200">
              <AlertCircle class="w-5 h-5" />
            </div>
            <div class="flex-1">
              <h3 class="text-sm font-bold text-slate-900">{{ confirmDialog.title }}</h3>
              <p class="text-xs text-slate-600 mt-2 leading-relaxed">{{ confirmDialog.message }}</p>
            </div>
          </div>
          <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="closeConfirmDialog(false)"
              class="taskster_button_light px-4 py-2 text-xs font-semibold rounded-lg"
            >
              {{ confirmDialog.cancelText }}
            </button>
            <button
              type="button"
              @click="closeConfirmDialog(true)"
              :class="confirmDialog.isDestructive ? 'taskster_button_accent' : 'taskster_button'"
              class="px-4 py-2 text-xs font-semibold rounded-lg text-white"
            >
              {{ confirmDialog.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import {
  ShieldCheck,
  Users,
  Building2,
  CreditCard,
  ClipboardList,
  Plus,
  Pencil,
  Trash2,
  Lock,
  Search,
  Check,
  X,
  Mail,
  Send,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Eye,
  EyeOff,
  FileText,
  Server,
  Inbox,
  Globe,
  Save,
  Sparkles,
  Info,
  Bot,
  Cpu
} from 'lucide-vue-next'

definePageMeta({
  middleware: [
    async function () {
      if (import.meta.client) {
        const token = localStorage.getItem('taskster_token')
        if (!token) {
          return navigateTo('/login')
        }
        const { initAuth, user } = useAuth()
        if (!user.value) {
          await initAuth()
        }
        const u: any = user.value
        if (!u) return navigateTo('/login')

        // Plattform-Admin: Superadmin ODER explizite Plattform-Permissions.
        // Company Admins (company_role === 'admin') gehoeren ins /company Portal.
        let perms = u.admin_permissions
        if (typeof perms === 'string') {
          try { perms = JSON.parse(perms) } catch { perms = [] }
        }
        const isPlatformAdmin = Boolean(u.is_superadmin) || (Array.isArray(perms) && perms.length > 0)
        if (!isPlatformAdmin) {
          return navigateTo('/dashboard')
        }
      }
    }
  ]
})

const { user, authHeaders } = useAuth()
const route = useRoute()
const router = useRouter()

// In-App Toast System (Zero native alerts)
interface AdminToast {
  id: string
  message: string
  type: 'success' | 'error' | 'info'
}
const toasts = ref<AdminToast[]>([])

function showToast(message: string, type: 'success' | 'error' | 'info' = 'success') {
  const id = 'toast_' + Math.random().toString(36).substring(2, 9)
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 4500)
}

// In-App Confirmation Modal System (Zero native confirm/prompt)
interface ConfirmDialogOptions {
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  isDestructive?: boolean
}

const confirmDialog = ref({
  isOpen: false,
  title: 'Bestätigung erforderlich',
  message: '',
  confirmText: 'Bestätigen',
  cancelText: 'Abbrechen',
  isDestructive: true,
  resolve: null as ((val: boolean) => void) | null
})

function showConfirm(options: ConfirmDialogOptions | string): Promise<boolean> {
  return new Promise((resolve) => {
    if (typeof options === 'string') {
      confirmDialog.value = {
        isOpen: true,
        title: 'Bestätigung erforderlich',
        message: options,
        confirmText: 'Fortfahren',
        cancelText: 'Abbrechen',
        isDestructive: true,
        resolve
      }
    } else {
      confirmDialog.value = {
        isOpen: true,
        title: options.title || 'Bestätigung erforderlich',
        message: options.message,
        confirmText: options.confirmText || 'Fortfahren',
        cancelText: options.cancelText || 'Abbrechen',
        isDestructive: options.isDestructive !== false,
        resolve
      }
    }
  })
}

function closeConfirmDialog(result: boolean) {
  if (confirmDialog.value.resolve) {
    confirmDialog.value.resolve(result)
  }
  confirmDialog.value.isOpen = false
  confirmDialog.value.resolve = null
}

const activeTab = ref<'users' | 'companies' | 'finance' | 'templates' | 'email' | 'invites' | 'website' | 'ai' | 'audit'>('users')
const overview = ref<any>(null)
const users = ref<any[]>([])
const companies = ref<any[]>([])
const loading = ref(true)

const activeSectionBadge = computed(() => {
  switch (activeTab.value) {
    case 'users': return 'Benutzerverwaltung'
    case 'companies': return 'Unternehmen & Mandanten'
    case 'finance': return 'Finanzen & Lizenzen'
    case 'templates': return 'Projekt-Vorlagen'
    case 'email': return 'E-Mail & Versand'
    case 'invites': return 'Mitarbeiter-Einladungen'
    case 'website': return 'Webseiten-Verwaltung'
    case 'ai': return 'Künstliche Intelligenz & Tarife'
    case 'audit': return 'Security & Audit Logs'
    default: return 'Zentrale Administration'
  }
})

const activeSectionTitle = computed(() => {
  switch (activeTab.value) {
    case 'users': return 'Benutzer- & Kundenverwaltung'
    case 'companies': return 'Unternehmen, Mandanten & B2B'
    case 'finance': return 'Finanzen, Abonnements & Lizenzen'
    case 'templates': return 'Projekt- & Aufgaben-Vorlagen'
    case 'email': return 'Zentrale E-Mail-Konfiguration'
    case 'invites': return 'Mitarbeiter & Einladungen'
    case 'website': return 'Webseiten- & CMS-Steuerung'
    case 'ai': return 'KI-Modelle & Tarif-Freischaltungen'
    case 'audit': return 'Sicherheits- & Revisions-Protokolle'
    default: return 'Taskster Plattform-Administration'
  }
})

const activeSectionDescription = computed(() => {
  switch (activeTab.value) {
    case 'users': return 'Verwalte registrierte Benutzer, Rollen, Berechtigungen und Firmenzuweisungen.'
    case 'companies': return 'Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien.'
    case 'finance': return 'Wiederkehrender monatlicher Umsatz (MRR), Firmenabos und Lizenz-Sitze.'
    case 'templates': return 'Vordefinierte Vorlagen für geschäftliche und private Bau- & Projektorganisation.'
    case 'email': return 'Resend & SMTP Einstellungen, E-Mail-Vorlagen und Versandprotokolle.'
    case 'invites': return 'Lade neue Mitarbeiter in dein Unternehmen ein und verwalte Einladungen.'
    case 'website': return 'Steuere Landingpage-Texte, Tarife auf der Webseite, Ankündigungsbanner, SEO & Wartungsmodus.'
    case 'ai': return 'Konfiguriere LLM Sprachmodelle, Whisper Audio-Transkription und welche KI-Features in welchen Plänen freigeschaltet sind.'
    case 'audit': return 'Revisionssichere Protokollierung aller Sicherheits-Events, Benutzeraktionen und Datenänderungen.'
    default: return 'Kundenübersicht, Benutzerverwaltung, Company-Pläne, Zugriffsregeln und Systemgrenzen.'
  }
})

const totalCompanyUsers = computed(() => {
  return companies.value.reduce((sum: number, c: any) => sum + (Number(c.user_count) || 0), 0)
})

const companiesWithUploadAllowed = computed(() => {
  return companies.value.filter((c: any) => c.settings?.allow_document_upload).length
})

const jobTemplatesCount = computed(() => {
  return templates.value.filter((t: any) => t.category === 'job').length
})

const privateTemplatesCount = computed(() => {
  return templates.value.filter((t: any) => t.category === 'private').length
})

const activeEmailTemplatesCount = computed(() => {
  return emailTemplates.value.filter((t: any) => t.is_active).length
})

// Website Settings State
const { fetchSettings: refreshGlobalWebsiteSettings } = useWebsiteSettings()
const websiteSettings = ref<Record<string, any>>({
  website_title: 'Taskster - Professionelles Projekt- & Bauleitermanagement',
  website_hero_title: '',
  website_hero_subtitle: '',
  website_contact_email: '',
  website_contact_phone: '',
  website_pricing_basic_price: '',
  website_pricing_pro_price: '',
  website_pricing_enterprise_price: '',
  website_announcement_active: false,
  website_announcement_text: '',
  website_announcement_type: 'info',
  website_seo_title: '',
  website_seo_description: '',
  website_maintenance_mode: false
})
const loadingWebsiteSettings = ref(false)
const savingWebsiteSettings = ref(false)
const websiteSettingsSavedNotice = ref(false)

async function loadWebsiteSettings() {
  loadingWebsiteSettings.value = true
  try {
    const res: any = await $fetch('/api/admin/website-settings', {
      headers: authHeaders()
    })
    websiteSettings.value = { ...websiteSettings.value, ...res }
  } catch (err: any) {
    showToast(err.data?.statusMessage || err.data?.error || 'Fehler beim Laden der Webseiten-Einstellungen', 'error')
  } finally {
    loadingWebsiteSettings.value = false
  }
}

async function saveWebsiteSettings() {
  savingWebsiteSettings.value = true
  websiteSettingsSavedNotice.value = false
  try {
    await $fetch('/api/admin/website-settings', {
      method: 'POST',
      headers: authHeaders(),
      body: websiteSettings.value
    })
    websiteSettingsSavedNotice.value = true
    refreshGlobalWebsiteSettings()
    showToast('Webseiten-Einstellungen erfolgreich gespeichert!', 'success')
    setTimeout(() => { websiteSettingsSavedNotice.value = false }, 4000)
  } catch (err: any) {
    showToast(err.data?.statusMessage || err.data?.error || err.data?.message || 'Fehler beim Speichern der Webseiten-Einstellungen', 'error')
  } finally {
    savingWebsiteSettings.value = false
  }
}

// AI Settings State & Methods
const aiSettings = ref({
  ai_enabled: true,
  ai_model: 'google/gemini-2.5-flash',
  ai_audio_model: 'openai/whisper-large-v3-turbo',
  ai_temperature: 0.3,
  ai_max_tokens: 2048,
  ai_system_prompt: 'Du bist ein präziser technischer Assistent für das Taskster-Projekt (Nuxt 3, Vue, PHP, MySQL, Zero-Trust-SaaS). Antworte kurz, konkret und auf Deutsch. Gib bei Code immer vollständige, lauffähige Ausschnitte.',
  ai_plan_basic_enabled: false,
  ai_plan_basic_monthly_limit: 10,
  ai_plan_basic_audio_enabled: false,
  ai_plan_pro_enabled: true,
  ai_plan_pro_monthly_limit: 500,
  ai_plan_pro_audio_enabled: true,
  ai_plan_enterprise_enabled: true,
  ai_plan_enterprise_monthly_limit: 5000,
  ai_plan_enterprise_audio_enabled: true,
  ai_plan_enterprise_custom_key_allowed: true
})
const loadingAiSettings = ref(false)
const savingAiSettings = ref(false)
const testPrompt = ref('Fasse die heutigen Vorkommnisse auf der Baustelle zusammen: Betonlieferung mit 30 Minuten Verspätung, Bewehrungsabnahme erfolgreich.')
const testPromptResult = ref('')
const testingAi = ref(false)

async function loadAiSettings() {
  loadingAiSettings.value = true
  try {
    const res: any = await $fetch('/api/admin/ai-settings', {
      headers: authHeaders()
    })
    aiSettings.value = { ...aiSettings.value, ...res }
  } catch (err: any) {
    showToast(err.data?.statusMessage || err.data?.error || 'Fehler beim Laden der AI-Einstellungen', 'error')
  } finally {
    loadingAiSettings.value = false
  }
}

async function saveAiSettings() {
  savingAiSettings.value = true
  try {
    await $fetch('/api/admin/ai-settings', {
      method: 'POST',
      headers: authHeaders(),
      body: aiSettings.value
    })
    showToast('KI- und Tarif-Einstellungen erfolgreich gespeichert!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || err.data?.error || 'Fehler beim Speichern der AI-Einstellungen', 'error')
  } finally {
    savingAiSettings.value = false
  }
}

async function runAiTest() {
  if (!testPrompt.value.trim()) return
  testingAi.value = true
  testPromptResult.value = ''
  try {
    const res: any = await $fetch('/api/ai/chat', {
      method: 'POST',
      headers: authHeaders(),
      body: { prompt: testPrompt.value }
    })
    testPromptResult.value = res.text || res.response || JSON.stringify(res)
    showToast('KI-Antwort erfolgreich empfangen!', 'success')
  } catch (err: any) {
    const msg = err.data?.statusMessage || err.data?.error || err.message || 'KI-Test fehlgeschlagen'
    testPromptResult.value = `Fehler: ${msg}`
    showToast(msg, 'error')
  } finally {
    testingAi.value = false
  }
}

// Audit Logs State
const auditLogs = ref<any[]>([])
const auditLogsTotal = ref(0)
const auditLogsActions = ref<string[]>([])
const loadingAuditLogs = ref(false)
const auditSearch = ref('')
const auditActionFilter = ref('')
const auditPage = ref(1)
const selectedAuditLog = ref<any>(null)

async function fetchAuditLogs() {
  loadingAuditLogs.value = true
  try {
    const res: any = await $fetch('/api/admin/audit-logs', {
      headers: authHeaders(),
      query: {
        limit: 50,
        offset: (auditPage.value - 1) * 50,
        search: auditSearch.value,
        action: auditActionFilter.value
      }
    })
    auditLogs.value = res.logs || []
    auditLogsTotal.value = res.total || 0
    auditLogsActions.value = res.actions || []
  } catch (err: any) {
    showToast(err.data?.statusMessage || err.data?.error || 'Fehler beim Laden der Audit-Logs', 'error')
  } finally {
    loadingAuditLogs.value = false
  }
}

const setTab = (tab: 'users' | 'companies' | 'finance' | 'templates' | 'email' | 'invites' | 'website' | 'ai' | 'audit') => {
  activeTab.value = tab
  router.replace({ query: { ...route.query, tab } })
  if (tab === 'audit') {
    fetchAuditLogs()
  } else if (tab === 'website') {
    loadWebsiteSettings()
  } else if (tab === 'ai') {
    loadAiSettings()
  }
}

function syncTabFromRoute() {
  const qTab = route.query.tab as any
  const validTabs = ['users', 'companies', 'finance', 'templates', 'email', 'invites', 'website', 'ai', 'audit']
  if (qTab && validTabs.includes(qTab)) {
    if (
      (qTab === 'users' && hasPermission('manage_users')) ||
      (qTab === 'companies' && hasPermission('company_settings')) ||
      (qTab === 'finance' && hasPermission('finance')) ||
      (qTab === 'templates' && hasPermission('manage_templates')) ||
      (qTab === 'email' && hasPermission('company_settings')) ||
      qTab === 'invites' ||
      qTab === 'website' ||
      (qTab === 'ai' && hasPermission('company_settings')) ||
      qTab === 'audit'
    ) {
      activeTab.value = qTab
      if (qTab === 'audit') fetchAuditLogs()
      else if (qTab === 'website') loadWebsiteSettings()
      else if (qTab === 'ai') loadAiSettings()
      return
    }
  }
  // Fallback defaults based on permission
  if (hasPermission('manage_users')) {
    activeTab.value = 'users'
  } else if (hasPermission('finance')) {
    activeTab.value = 'finance'
  } else if (hasPermission('company_settings')) {
    activeTab.value = 'companies'
  } else if (hasPermission('manage_templates')) {
    activeTab.value = 'templates'
  }
}

watch(() => route.query.tab, () => {
  syncTabFromRoute()
})

// Email Settings & Trigger Templates state
const emailSubTab = ref<'settings' | 'templates' | 'outbox'>('settings')
const smtpConfig = ref({
  mail_provider: 'resend',
  resend_api_key: '',
  smtp_host: 'mail.kurka.ch',
  smtp_port: 465,
  smtp_secure: 'ssl',
  smtp_user: 'noreply@kurka.ch',
  smtp_password: '',
  smtp_from_email: 'noreply@kurka.ch',
  smtp_from_name: 'Taskster'
})
const showSmtpPassword = ref(false)
const showResendKey = ref(false)
const savingSmtp = ref(false)
const smtpSavedMessage = ref('')
const emailTemplates = ref<any[]>([])
const editingEmailTemplate = ref<any | null>(null)
const showEmailTemplateModal = ref(false)
const emailTemplatePreviewMode = ref<'html' | 'preview' | 'text'>('preview')
const emailOutbox = ref<any[]>([])
const showTestEmailModal = ref(false)
const testEmailTo = ref('')
const testEmailSender = ref('notify@kurka.ch')
const testingEmail = ref(false)
const testEmailResult = ref<{ success: boolean; message?: string; error?: string; log: string[] } | null>(null)

// Permissions system
const availablePermissions = [
  { key: 'manage_users', label: 'Benutzer verwalten', icon: '👤', desc: 'Benutzer manuell anlegen, Rollen & Passwörter ändern' },
  { key: 'finance', label: 'Finanzen & Bestellungen', icon: '💳', desc: 'Umsätze, MRR, Firmenabos & Zahlungsstatus einsehen' },
  { key: 'company_settings', label: 'Firmen & Policies', icon: '🏢', desc: 'Firmendetails, Tarif-Limits und Upload-Regeln bearbeiten' },
  { key: 'manage_templates', label: 'System-Vorlagen', icon: '📋', desc: 'Projekt- und Aufgaben-Vorlagen erstellen und bearbeiten' },
  { key: 'audit_logs', label: 'Sicherheit & Audit', icon: '📜', desc: 'Sicherheits- und Zugriffsprotokolle einsehen' }
]

const hasPermission = (perm: string) => {
  if (!user.value) return false
  if (user.value.is_superadmin) return true
  let perms = user.value.admin_permissions
  if (typeof perms === 'string') {
    try { perms = JSON.parse(perms) } catch { perms = [] }
  }
  return Array.isArray(perms) && perms.includes(perm)
}

const isAnyAdmin = computed(() => {
  if (!user.value) return false
  if (user.value.is_superadmin) return true
  let perms = user.value.admin_permissions
  if (typeof perms === 'string') {
    try { perms = JSON.parse(perms) } catch { perms = [] }
  }
  return Array.isArray(perms) && perms.length > 0
})

const getPermissionBadge = (key: string) => {
  const map: Record<string, string> = {
    manage_users: '👤 User-Mgmt',
    finance: '💳 Finanzen',
    company_settings: '🏢 Firmen',
    manage_templates: '📋 Vorlagen',
    audit_logs: '📜 Audit'
  }
  return map[key] || key
}

const getPermissionLabel = (key: string) => {
  const found = availablePermissions.find(p => p.key === key)
  return found ? found.label : key
}

const generateRandomPassword = (target: 'new' | 'edit') => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*'
  let pwd = ''
  for (let i = 0; i < 12; i++) {
    pwd += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  if (target === 'new') {
    newUserForm.value.password = pwd
  } else {
    editUserForm.value.new_password = pwd
  }
}

const applyAdminPreset = (target: 'new' | 'edit', preset: 'all' | 'users' | 'finance' | 'none') => {
  const targetForm = target === 'new' ? newUserForm.value : editUserForm.value
  if (preset === 'all') {
    targetForm.admin_permissions = ['manage_users', 'finance', 'company_settings', 'manage_templates', 'audit_logs']
  } else if (preset === 'users') {
    targetForm.admin_permissions = ['manage_users']
  } else if (preset === 'finance') {
    targetForm.admin_permissions = ['finance']
  } else {
    targetForm.admin_permissions = []
  }
}

// Create User Modal State
const showCreateUserModal = ref(false)
const creatingUser = ref(false)
const newUserForm = ref({
  name: '',
  email: '',
  password: '',
  plan: 'pro',
  is_pro: true,
  is_superadmin: false,
  company_id: '',
  company_role: 'member',
  admin_permissions: [] as string[]
})

const openCreateUserModal = () => {
  newUserForm.value = {
    name: '',
    email: '',
    password: '',
    plan: 'pro',
    is_pro: true,
    is_superadmin: false,
    company_id: user.value?.company_id || '',
    company_role: 'member',
    admin_permissions: []
  }
  generateRandomPassword('new')
  showCreateUserModal.value = true
}

const createUser = async () => {
  creatingUser.value = true
  try {
    await $fetch('/api/admin/users', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        name: newUserForm.value.name,
        email: newUserForm.value.email,
        password: newUserForm.value.password,
        plan: newUserForm.value.plan,
        is_pro: newUserForm.value.plan !== 'basic',
        is_superadmin: user.value?.is_superadmin ? newUserForm.value.is_superadmin : false,
        company_id: newUserForm.value.company_id || null,
        company_role: newUserForm.value.company_role,
        admin_permissions: newUserForm.value.admin_permissions
      }
    })
    showCreateUserModal.value = false
    await loadAdminData()
    showToast(`Benutzer "${newUserForm.value.name}" erfolgreich angelegt!`, 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Anlegen des Benutzers', 'error')
  } finally {
    creatingUser.value = false
  }
}

// Edit User Modal State
const showEditUserModal = ref(false)
const editUserForm = ref({
  id: '',
  name: '',
  email: '',
  new_password: '',
  plan: 'pro',
  is_pro: true,
  is_superadmin: false,
  company_id: '',
  company_role: 'member',
  admin_permissions: [] as string[]
})
const savingUser = ref(false)

const openEditUserModal = (u: any) => {
  let perms = u.admin_permissions
  if (typeof perms === 'string') {
    try { perms = JSON.parse(perms) } catch { perms = [] }
  }
  const computedPlan = u.plan || u.license_type || (u.is_pro ? 'pro' : 'basic')
  editUserForm.value = {
    id: u.id,
    name: u.name || '',
    email: u.email || '',
    new_password: '',
    plan: computedPlan,
    is_pro: computedPlan !== 'basic',
    is_superadmin: Boolean(u.is_superadmin),
    company_id: u.company_id || '',
    company_role: u.company_role || 'member',
    admin_permissions: Array.isArray(perms) ? [...perms] : []
  }
  showEditUserModal.value = true
}

const saveUserChanges = async () => {
  savingUser.value = true
  try {
    const payload: any = {
      name: editUserForm.value.name,
      email: editUserForm.value.email,
      plan: editUserForm.value.plan,
      is_pro: editUserForm.value.plan !== 'basic',
      is_superadmin: user.value?.is_superadmin ? editUserForm.value.is_superadmin : false,
      company_id: editUserForm.value.company_id || null,
      company_role: editUserForm.value.company_role,
      admin_permissions: editUserForm.value.admin_permissions
    }
    if (editUserForm.value.new_password && editUserForm.value.new_password.trim().length > 0) {
      payload.password = editUserForm.value.new_password.trim()
    }
    await $fetch(`/api/admin/users/${editUserForm.value.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: payload
    })
    showEditUserModal.value = false
    await loadAdminData()
    showToast('Benutzer-Einstellungen erfolgreich gespeichert!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Speichern der Benutzer-Einstellungen', 'error')
  } finally {
    savingUser.value = false
  }
}

// Finance & Orders State
const orders = ref<any[]>([])
const ordersSummary = ref<any>(null)
const loadingOrders = ref(false)

const loadOrdersData = async () => {
  if (!hasPermission('finance')) return
  loadingOrders.value = true
  try {
    const res = await $fetch<any>('/api/admin/orders', { headers: authHeaders() })
    orders.value = res.orders || []
    ordersSummary.value = res.summary || null
  } catch (err: any) {
    console.error('Fehler beim Laden der Bestelldaten:', err)
  } finally {
    loadingOrders.value = false
  }
}

const generateInvoice = (customerName: string) => {
  showToast(`Rechnung für ${customerName} wird generiert...`, 'info')
}

const templates = ref<any[]>([])
const templateCategoryFilter = ref('all')
const templateSearch = ref('')
const showTemplateModal = ref(false)
const editingTemplate = ref<any>(null)

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
  if (!lbl) {
    showToast('Bitte Feldbezeichnung eingeben', 'error')
    return
  }
  const key = lbl.toLowerCase().replace(/[^a-z0-9_]/g, '_')

  let options: string[] = []
  if (newField.value.field_type === 'select') {
    options = newFieldOptionsInput.value.split(',').map((s: string) => s.trim()).filter(Boolean)
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
      showToast('Vorlage erfolgreich aktualisiert!', 'success')
    } else {
      await $fetch('/api/templates', {
        method: 'POST',
        headers: authHeaders(),
        body: tmplForm.value
      })
      showToast('Vorlage erfolgreich erstellt!', 'success')
    }
    showTemplateModal.value = false
    await loadAdminData()
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Speichern der Vorlage', 'error')
  }
}

const deleteTemplate = async (id: string) => {
  if (!(await showConfirm('Möchtest du diese Vorlage wirklich löschen?'))) return
  try {
    await $fetch(`/api/templates/${id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    await loadAdminData()
    showToast('Vorlage erfolgreich gelöscht!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Löschen der Vorlage', 'error')
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
    // 1. Overview (allowed for any admin, scoped in backend)
    try {
      overview.value = await $fetch<any>('/api/admin/overview', { headers: authHeaders() })
    } catch (e) {
      console.warn('Overview fetch error:', e)
    }

    // 2. Users (manage_users)
    if (hasPermission('manage_users')) {
      try {
        const uRes = await $fetch<any>('/api/admin/users', { headers: authHeaders() })
        users.value = uRes.users || []
      } catch (e) {
        console.warn('Users fetch error:', e)
      }
    }

    // 3. Companies (company_settings)
    if (hasPermission('company_settings')) {
      try {
        const cRes = await $fetch<any>('/api/admin/companies', { headers: authHeaders() })
        companies.value = cRes.companies || []
      } catch (e) {
        console.warn('Companies fetch error:', e)
      }
    }

    // 4. Templates (manage_templates)
    if (hasPermission('manage_templates')) {
      try {
        const tRes = await $fetch<any>('/api/templates', { headers: authHeaders() })
        templates.value = tRes.templates || []
      } catch (e) {
        console.warn('Templates fetch error:', e)
      }
    }

    // 5. Finance (finance)
    if (hasPermission('finance')) {
      await loadOrdersData()
    }

    // 6. Company Invitations (if company admin or superadmin)
    if (user.value?.company_id && (user.value?.company_role === 'admin' || user.value?.is_superadmin)) {
      try {
        const invRes = await $fetch<any>('/api/companies/invitations', { headers: authHeaders() })
        pendingInvites.value = invRes.invitations || []
      } catch (e) {
        console.warn('Invitations fetch error:', e)
      }
    }
  } catch (err: any) {
    if (err.statusCode === 403 || err.statusCode === 401) {
      showToast('Zugriff nur für autorisierte Administratoren gestattet.', 'error')
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
      showToast(`Benutzer ${inviteEmail.value} war bereits registriert und wurde dem Unternehmen sofort hinzugefügt!`, 'success')
    } else if (res.action === 'invited') {
      lastInviteLink.value = `${window.location.origin}/login?token=${res.token}`
      showToast('Einladung erfolgreich erstellt!', 'success')
    }
    inviteEmail.value = ''
    await loadAdminData()
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Senden der Einladung', 'error')
  } finally {
    sendingInvite.value = false
  }
}

const copyInviteLink = () => {
  if (lastInviteLink.value && navigator.clipboard) {
    navigator.clipboard.writeText(lastInviteLink.value)
    showToast('Einladungslink in die Zwischenablage kopiert!', 'success')
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
    showToast(`Pro-Status für ${targetUser.name || 'Benutzer'} aktualisiert!`, 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Ändern des Pro-Status', 'error')
  }
}

const updateCompanyPlan = async (c: any) => {
  try {
    await $fetch(`/api/admin/companies/${c.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: { subscription_plan: c.subscription_plan }
    })
    showToast(`Tarif für ${c.name} erfolgreich auf ${c.subscription_plan} aktualisiert!`, 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Aktualisieren des Plans', 'error')
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
    showToast(`Upload-Berechtigung für ${c.name} ${newAllowed ? 'aktiviert' : 'deaktiviert'}!`, 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Aktualisieren der Upload-Policy', 'error')
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
    showToast('Unternehmen und Admin erfolgreich erstellt!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Erstellen des Unternehmens', 'error')
  }
}

// Email Management Methods
const loadEmailSettings = async () => {
  try {
    const res = await $fetch<any>('/api/admin/email-settings', {
      headers: authHeaders()
    })
    if (res?.settings) {
      smtpConfig.value = { ...smtpConfig.value, ...res.settings }
    }
  } catch (err) {
    console.error('Fehler beim Laden der E-Mail-Einstellungen:', err)
  }
}

const saveEmailSettings = async () => {
  savingSmtp.value = true
  smtpSavedMessage.value = ''
  try {
    const res = await $fetch<any>('/api/admin/email-settings', {
      method: 'POST',
      headers: authHeaders(),
      body: smtpConfig.value
    })
    if (res?.settings) {
      smtpConfig.value = { ...smtpConfig.value, ...res.settings }
    }
    smtpSavedMessage.value = 'E-Mail-Einstellungen erfolgreich gespeichert!'
    showToast('E-Mail-Einstellungen erfolgreich gespeichert!', 'success')
    setTimeout(() => { smtpSavedMessage.value = '' }, 4000)
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Speichern der SMTP-Einstellungen', 'error')
  } finally {
    savingSmtp.value = false
  }
}

const loadEmailTemplates = async () => {
  try {
    const res = await $fetch<any>('/api/admin/email-templates', {
      headers: authHeaders()
    })
    if (res?.templates) {
      emailTemplates.value = res.templates
    }
  } catch (err) {
    console.error('Fehler beim Laden der E-Mail-Vorlagen:', err)
  }
}

const openEditEmailTemplateModal = (tmpl: any) => {
  editingEmailTemplate.value = JSON.parse(JSON.stringify(tmpl))
  emailTemplatePreviewMode.value = 'preview'
  showEmailTemplateModal.value = true
}

const insertVariableInTemplate = (varName: string) => {
  if (!editingEmailTemplate.value) return
  const token = `{{${varName}}}`
  if (emailTemplatePreviewMode.value === 'text') {
    editingEmailTemplate.value.body_text = (editingEmailTemplate.value.body_text || '') + ' ' + token
  } else {
    editingEmailTemplate.value.body_html = (editingEmailTemplate.value.body_html || '') + ' ' + token
  }
}

const renderEmailPreview = (tmpl: any) => {
  if (!tmpl || !tmpl.body_html) return ''
  let html = tmpl.body_html
  const dummyMap: Record<string, string> = {
    user_name: 'Beat Meier',
    user_email: 'beat.meier@muster.ch',
    task_title: 'LWL-Spleissung Hauptverteiler',
    project_title: 'FTTH Ausbau Zürich Nord',
    assigned_by: 'Martin Kurka',
    due_date: '28.09.2026',
    author_name: 'Martin Kurka',
    comment_content: 'Messprotokoll wurde soeben hochgeladen.',
    event_title: 'Bauabnahme vor Ort',
    event_start: '25.09.2026 14:00',
    event_end: '25.09.2026 15:30',
    event_location: 'Zentralstrasse 14, 8003 Zürich',
    context_title: 'Projekt FTTH Ausbau',
    mention_text: 'Bitte bis morgen prüfen',
    budget_percent: '85',
    tracked_hours: '34',
    budget_hours: '40',
    company_name: 'Kurka Telecom AG',
    inviter_name: 'Martin Kurka',
    invite_link: 'https://taskster.ch/register?token=demo',
    action_url: 'https://taskster.ch'
  }
  for (const [k, v] of Object.entries(dummyMap)) {
    const regex = new RegExp(`{{\\s*${k}\\s*}}`, 'g')
    html = html.replace(regex, v)
  }
  return html
}

const saveEmailTemplate = async () => {
  if (!editingEmailTemplate.value) return
  try {
    const res = await $fetch<any>(`/api/admin/email-templates/${editingEmailTemplate.value.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: {
        name: editingEmailTemplate.value.name,
        subject: editingEmailTemplate.value.subject,
        body_html: editingEmailTemplate.value.body_html,
        body_text: editingEmailTemplate.value.body_text,
        is_active: editingEmailTemplate.value.is_active
      }
    })
    showEmailTemplateModal.value = false
    await loadEmailTemplates()
    showToast('E-Mail-Vorlage erfolgreich gespeichert!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Speichern der Vorlage', 'error')
  }
}

const toggleEmailTemplateActive = async (tmpl: any) => {
  try {
    const nextState = !tmpl.is_active
    await $fetch<any>(`/api/admin/email-templates/${tmpl.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: { is_active: nextState }
    })
    tmpl.is_active = nextState
    showToast(`Vorlage "${tmpl.name}" ${nextState ? 'aktiviert' : 'deaktiviert'}!`, 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Umschalten der Vorlage', 'error')
  }
}

const resetSingleEmailTemplate = async (tmplId: string) => {
  if (!(await showConfirm('Möchtest du diese Vorlage wirklich auf den Systemstandard zurücksetzen?'))) return
  try {
    await $fetch<any>('/api/admin/email-templates/reset', {
      method: 'POST',
      headers: authHeaders(),
      body: { id: tmplId }
    })
    await loadEmailTemplates()
    showToast('Vorlage erfolgreich auf Systemstandard zurückgesetzt!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Zurücksetzen der Vorlage', 'error')
  }
}

const resetAllEmailTemplates = async () => {
  if (!(await showConfirm('Möchtest du wirklich ALLE Vorlagen unwiderruflich auf den Systemstandard zurücksetzen?'))) return
  try {
    await $fetch<any>('/api/admin/email-templates/reset', {
      method: 'POST',
      headers: authHeaders()
    })
    await loadEmailTemplates()
    showToast('Alle Vorlagen erfolgreich zurückgesetzt!', 'success')
  } catch (err: any) {
    showToast(err.data?.statusMessage || 'Fehler beim Zurücksetzen aller Vorlagen', 'error')
  }
}

const loadEmailOutbox = async () => {
  try {
    const res = await $fetch<any>('/api/admin/email-outbox', {
      headers: authHeaders()
    })
    if (res?.outbox) {
      emailOutbox.value = res.outbox
    }
  } catch (err) {
    console.error('Fehler beim Laden der Outbox:', err)
  }
}

const openTestEmailModal = (sender?: string) => {
  testEmailTo.value = user.value?.email || 'noreply@kurka.ch'
  if (sender) {
    testEmailSender.value = sender
  } else {
    testEmailSender.value = smtpConfig.value.mail_provider === 'resend' ? 'notify@kurka.ch' : (smtpConfig.value.smtp_from_email || 'noreply@kurka.ch')
  }
  testEmailResult.value = null
  showTestEmailModal.value = true
}

const testSpecificSender = (sender: string) => {
  openTestEmailModal(sender)
}

const getTemplateSenderInfo = (triggerEvent: string) => {
  if (['company_invite', 'user_welcome', 'onboarding', 'invite'].includes(triggerEvent)) {
    return { email: 'hey@kurka.ch', badge: 'Onboarding', color: 'bg-purple-100 text-purple-800 border border-purple-200' }
  }
  if (['updates', 'changelog', 'newsletter'].includes(triggerEvent)) {
    return { email: 'updates@kurka.ch', badge: 'News', color: 'bg-blue-100 text-blue-800 border border-blue-200' }
  }
  if (['task_assigned', 'task_comment', 'mention', 'calendar_invite', 'calendar_change', 'calendar_cancel'].includes(triggerEvent)) {
    return { email: 'team@kurka.ch', badge: 'Team', color: 'bg-emerald-100 text-emerald-800 border border-emerald-200' }
  }
  if (['task_due', 'calendar_reminder', 'budget_warning', 'digest'].includes(triggerEvent)) {
    return { email: 'notify@kurka.ch', badge: 'Fristen', color: 'bg-amber-100 text-amber-800 border border-amber-200' }
  }
  if (['password_reset', 'security_alert', 'account_change', '2fa'].includes(triggerEvent)) {
    return { email: 'system@kurka.ch', badge: 'Sicherheit', color: 'bg-rose-100 text-rose-800 border border-rose-200' }
  }
  return { email: 'notify@kurka.ch', badge: 'Standard', color: 'bg-slate-100 text-slate-800 border border-slate-200' }
}

const sendTestEmail = async () => {
  testingEmail.value = true
  testEmailResult.value = null
  try {
    const res = await $fetch<any>('/api/admin/email-test', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        to_email: testEmailTo.value,
        sender_email: testEmailSender.value,
        custom_config: smtpConfig.value
      }
    })
    testEmailResult.value = {
      success: true,
      message: res.message || 'Test-E-Mail erfolgreich versendet!',
      log: res.log || []
    }
    await loadEmailOutbox()
  } catch (err: any) {
    testEmailResult.value = {
      success: false,
      error: err.data?.statusMessage || err.message || 'Fehler beim Senden',
      log: err.data?.log || []
    }
  } finally {
    testingEmail.value = false
  }
}

watch(activeTab, (tab: string) => {
  if (tab === 'email') {
    loadEmailSettings()
    loadEmailTemplates()
    loadEmailOutbox()
  } else if (tab === 'finance') {
    loadOrdersData()
  } else if (tab === 'audit') {
    fetchAuditLogs()
  } else if (tab === 'website') {
    loadWebsiteSettings()
  } else if (tab === 'ai') {
    loadAiSettings()
  }
})

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (!isAnyAdmin.value) {
    navigateTo('/dashboard')
    return
  }

  syncTabFromRoute()

  await loadAdminData()
  if (activeTab.value === 'finance') {
    await loadOrdersData()
  } else if (activeTab.value === 'audit') {
    await fetchAuditLogs()
  } else if (activeTab.value === 'website') {
    await loadWebsiteSettings()
  } else if (activeTab.value === 'ai') {
    await loadAiSettings()
  }
  if (hasPermission('company_settings')) {
    loadEmailSettings()
    loadEmailTemplates()
    loadEmailOutbox()
  }
})
</script>
