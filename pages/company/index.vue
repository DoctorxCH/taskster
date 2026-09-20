<template>
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <!-- Zugriff verweigert -->
    <div v-if="!isCompanyAdmin" class="max-w-md mx-auto py-20 text-center">
      <div class="bg-white border border-slate-200 rounded-lg p-8">
        <div class="w-12 h-12 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-4">
          <Lock class="w-6 h-6" />
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-1">Zugriff verweigert</h2>
        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
          Dieser Bereich ist ausschließlich Firmen-Administratoren des eigenen Unternehmens vorbehalten.
        </p>
        <NuxtLink
          :to="isSuperadminWithoutCompany ? '/admin' : '/dashboard'"
          class="taskster_button"
        >
          {{ isSuperadminWithoutCompany ? 'Zur Plattform-Administration' : 'Zurück zum Dashboard' }}
        </NuxtLink>
      </div>
    </div>

    <template v-else>
      <!-- Breadcrumb -->
      <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-6">
        <NuxtLink to="/dashboard" class="hover:text-cyan-800 transition-colors flex items-center gap-1">
          <LayoutDashboard class="w-3.5 h-3.5" />
          <span>Dashboard</span>
        </NuxtLink>
        <span>/</span>
        <span class="text-slate-800 font-medium flex items-center gap-1">
          <Building2 class="w-3.5 h-3.5 text-[#0891B2]" />
          <span>Firmen-Administration</span>
        </span>
      </div>

      <!-- Header -->
      <div class="bg-white border border-slate-200 rounded-lg p-5 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-cyan-50 border border-cyan-200 text-cyan-800 text-xs font-semibold mb-2">
              <Building2 class="w-3.5 h-3.5 text-[#0891B2]" />
              <span>Firmen-Administration</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
              {{ company?.name || 'Mein Unternehmen' }}
            </h1>
            <p class="text-sm text-slate-600 mt-1">
              Mitarbeiter, Firmenvorlagen, Plan &amp; Lizenzen, Richtlinien und Support – alles an einem Ort.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <span
              class="inline-flex items-center h-7 px-3 rounded-sm text-xs font-medium border"
              :class="planBadgeClass"
            >
              {{ company?.plan_name || 'Starter Plan' }}
            </span>
            <button
              @click="activeTab = 'members'; openInviteModal()"
              class="taskster_button"
            >
              <UserPlus class="w-4 h-4" />
              <span>Mitarbeiter einladen</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Feedback -->
      <div v-if="successMsg" class="mb-6 p-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
        {{ successMsg }}
      </div>
      <div v-if="errorMsg" class="mb-6 p-4 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium">
        {{ errorMsg }}
      </div>

      <!-- Metrics -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="p-4 rounded-lg bg-white border border-slate-200 text-center">
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Mitarbeiter</div>
          <div class="text-xl font-bold text-slate-900 mt-1 tabular-nums">{{ stats.members || 0 }}</div>
          <div class="text-xs text-slate-500">von {{ stats.max_seats || 0 }} Sitzen</div>
        </div>

        <div class="p-4 rounded-lg bg-white border border-slate-200 text-center">
          <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">Co-Admins</div>
          <div class="text-xl font-bold text-emerald-900 mt-1 tabular-nums">{{ stats.admins || 0 }}</div>
          <div class="text-xs text-slate-500">mit Vollzugriff</div>
        </div>

        <div class="p-4 rounded-lg bg-white border border-slate-200 text-center">
          <div class="text-xs font-semibold text-cyan-700 uppercase tracking-wide">Projekte</div>
          <div class="text-xl font-bold text-[#0891B2] mt-1 tabular-nums">{{ stats.projects || 0 }}</div>
          <div class="text-xs text-slate-500">im Unternehmen</div>
        </div>

        <div class="p-4 rounded-lg bg-white border border-slate-200 text-center">
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Aufgaben</div>
          <div class="text-xl font-bold text-slate-900 mt-1 tabular-nums">{{ stats.tasks || 0 }}</div>
          <div class="text-xs text-slate-500">in Listen gepflegt</div>
        </div>

        <div class="p-4 rounded-2xl liquid_glass_card text-center">
          <div class="text-[11px] font-bold text-amber-700 uppercase">Firmenvorlagen</div>
          <div class="text-2xl font-black text-amber-900 mt-1">{{ stats.templates || 0 }}</div>
          <div class="text-[10px] text-slate-500 font-medium">nur für diese Firma</div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="liquid_glass_pill rounded-2xl px-4 py-1.5 mb-6 flex items-center space-x-2 overflow-x-auto shadow-sm">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center space-x-2 cursor-pointer shrink-0"
          :class="activeTab === tab.key ? 'bg-white text-emerald-800 shadow-sm' : 'text-slate-700 hover:text-slate-900 hover:bg-white/50'"
        >
          <span>{{ tab.icon }}</span>
          <span>{{ tab.label }}</span>
        </button>
      </div>

      <!-- TAB: MITARBEITER & CO-ADMINS -->
      <div v-if="activeTab === 'members'" class="space-y-6">
        <div class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
          <div class="p-4 sm:p-6 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Mitarbeiter &amp; Co-Administratoren</h3>
              <p class="text-xs text-slate-600 font-medium">
                Co-Admins dürfen dieses Firmen-Portal ebenfalls verwalten.
              </p>
            </div>
            <button
              @click="openInviteModal()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm shrink-0"
            >
              <span>+ Mitarbeiter einladen</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
                <tr>
                  <th class="py-3.5 px-4">Name &amp; E-Mail</th>
                  <th class="py-3.5 px-4">Rolle</th>
                  <th class="py-3.5 px-4">Seit</th>
                  <th class="py-3.5 px-4 text-right">Aktionen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200/60 text-slate-800">
                <tr v-for="m in members" :key="m.id" class="hover:bg-white/60 transition">
                  <td class="py-3.5 px-4">
                    <div class="flex items-center space-x-3">
                      <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center text-xs font-black shrink-0">
                        {{ m.name?.charAt(0).toUpperCase() }}
                      </div>
                      <div class="min-w-0">
                        <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                          <span>{{ m.name }}</span>
                          <span v-if="m.id === user?.id" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-cyan-100 text-cyan-800 border border-cyan-200">DU</span>
                        </div>
                        <div class="text-[11px] text-slate-500 font-mono truncate">{{ m.email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <span
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                      :class="m.company_role === 'admin'
                        ? 'bg-emerald-100 text-emerald-900 border-emerald-300'
                        : 'bg-slate-200 text-slate-700 border-slate-300'"
                    >
                      {{ m.company_role === 'admin' ? 'Co-Admin' : 'Mitarbeiter' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-slate-600 font-medium">
                    {{ formatDate(m.created_at) }}
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <div class="inline-flex items-center space-x-2">
                      <button
                        v-if="m.company_role === 'admin'"
                        @click="changeRole(m, 'member')"
                        :disabled="m.id === user?.id"
                        class="taskster_button_light px-4 text-xs h-[34px] rounded-lg shadow-xs disabled:opacity-40 disabled:cursor-not-allowed"
                        :title="m.id === user?.id ? 'Du kannst dich nicht selbst herabstufen' : 'Zum Mitarbeiter herabstufen'"
                      >
                        Herabstufen
                      </button>
                      <button
                        v-else
                        @click="changeRole(m, 'admin')"
                        class="taskster_button px-4 text-xs h-[34px] rounded-lg shadow-xs"
                        title="Zum Co-Admin ernennen"
                      >
                        Zum Co-Admin
                      </button>

                      <button
                        @click="removeMember(m)"
                        :disabled="m.id === user?.id"
                        class="taskster_button_accent px-4 text-xs h-[34px] rounded-lg shadow-xs disabled:opacity-40 disabled:cursor-not-allowed"
                        :title="m.id === user?.id ? 'Du kannst dich nicht selbst entfernen' : 'Aus Unternehmen entfernen'"
                      >
                        Entfernen
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="members.length === 0">
                  <td colspan="4" class="py-10 text-center text-slate-500 text-xs font-medium">
                    Noch keine Mitarbeiter im Unternehmen.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Offene Einladungen -->
        <div v-if="invitations.length > 0" class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
          <div class="p-4 sm:p-6 border-b border-slate-200/80">
            <h3 class="text-sm font-bold text-slate-900">Offene Einladungen</h3>
            <p class="text-xs text-slate-600 font-medium">Diese Personen wurden eingeladen und haben sich noch nicht registriert.</p>
          </div>
          <div class="divide-y divide-slate-200/60">
            <div v-for="inv in invitations" :key="inv.id" class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 font-mono truncate">{{ inv.email }}</div>
                <div class="text-[11px] text-slate-500 font-medium">
                  Rolle: {{ inv.role === 'admin' ? 'Co-Admin' : 'Mitarbeiter' }} · eingeladen am {{ formatDate(inv.created_at) }}
                </div>
              </div>
              <button
                @click="copyInviteLink(inv.token)"
                class="taskster_button_light px-4 text-xs h-[34px] rounded-lg shadow-xs shrink-0"
              >
                <span>🔗</span>
                <span>Link kopieren</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: FIRMENVORLAGEN -->
      <div v-else-if="activeTab === 'templates'" class="space-y-6">
        <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Firmeneigene Projektvorlagen</h3>
              <p class="text-xs text-slate-600 font-medium">
                Diese Vorlagen sind ausschließlich für Mitarbeiter deines Unternehmens sichtbar.
              </p>
            </div>
            <button
              @click="openTemplateModal()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shadow-sm shrink-0"
            >
              <span>+ Neue Firmenvorlage</span>
            </button>
          </div>
        </div>

        <div v-if="templates.length === 0" class="liquid_glass rounded-3xl p-10 text-center shadow-xl">
          <div class="text-4xl mb-3">📋</div>
          <h3 class="text-sm font-black text-slate-900 mb-1">Noch keine Firmenvorlagen</h3>
          <p class="text-xs text-slate-600 mb-5 font-medium">
            Erstelle eigene Vorlagen mit Listen und Custom Fields – passend zu deinen Abläufen.
          </p>
          <button @click="openTemplateModal()" class="taskster_button px-6 text-xs h-[42px] rounded-lg">
            Erste Vorlage erstellen
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="t in templates"
            :key="t.id"
            class="liquid_glass_card hover:border-[#00A3C4] rounded-3xl p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between space-y-4"
          >
            <div>
              <div class="flex items-start justify-between mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300">
                  Firma
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 border border-slate-200 text-slate-700">
                  {{ t.category === 'private' ? 'Privat' : 'Job' }}
                </span>
              </div>
              <h4 class="text-sm font-black text-slate-900 leading-snug">{{ t.name }}</h4>
              <p class="text-[11px] text-slate-600 mt-1 font-medium line-clamp-2">{{ t.description || 'Keine Beschreibung' }}</p>

              <div class="flex flex-wrap gap-1.5 mt-3">
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 border border-slate-200 text-slate-700">
                  {{ (t.lists || []).length }} Listen
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90 border border-slate-200 text-slate-700">
                  {{ (t.fields || []).length }} Felder
                </span>
              </div>
            </div>

            <div class="flex items-center space-x-2 pt-3 border-t border-slate-200/60">
              <button
                @click="openTemplateModal(t)"
                class="taskster_button_light px-4 text-xs h-[34px] rounded-lg shadow-xs flex-1"
              >
                Bearbeiten
              </button>
              <button
                @click="deleteTemplate(t)"
                class="taskster_button_accent px-4 text-xs h-[34px] rounded-lg shadow-xs"
              >
                Löschen
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: PLAN & LIZENZEN -->
      <div v-else-if="activeTab === 'billing'" class="space-y-6">
        <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
          <h3 class="text-sm font-bold text-slate-900 mb-1">Aktueller Plan &amp; Lizenzauslastung</h3>
          <p class="text-xs text-slate-600 font-medium mb-6">Übersicht deines Abonnements und der verfügbaren Sitzplätze.</p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="rounded-2xl p-5 bg-white/70 border border-white/60">
              <div class="text-[11px] font-bold text-slate-600 uppercase">Abo-Plan</div>
              <div class="text-lg font-black text-slate-900 mt-1">{{ company?.plan_name || '—' }}</div>
              <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                {{ (company?.plan_monthly || 0) > 0 ? (company.plan_monthly + ' ' + (company?.currency || 'CHF') + ' / Monat') : 'Kostenlos' }}
              </div>
            </div>

            <div class="rounded-2xl p-5 bg-white/70 border border-white/60">
              <div class="text-[11px] font-bold text-slate-600 uppercase">Sitzplätze</div>
              <div class="text-lg font-black text-slate-900 mt-1">
                {{ stats.seats_used || 0 }} / {{ stats.max_seats || 0 }}
              </div>
              <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all"
                  :class="seatUsagePercent >= 90 ? 'bg-rose-500' : seatUsagePercent >= 70 ? 'bg-amber-500' : 'bg-emerald-500'"
                  :style="{ width: Math.min(seatUsagePercent, 100) + '%' }"
                />
              </div>
              <div class="text-[11px] text-slate-500 font-medium mt-1">{{ seatUsagePercent }}% belegt</div>
            </div>

            <div class="rounded-2xl p-5 bg-white/70 border border-white/60">
              <div class="text-[11px] font-bold text-slate-600 uppercase">Nächste Verlängerung</div>
              <div class="text-lg font-black text-slate-900 mt-1">{{ company?.next_renewal || '—' }}</div>
              <div class="text-[11px] text-slate-500 font-medium mt-0.5">Monatliche Abrechnung</div>
            </div>
          </div>
        </div>

        <!-- Upgrade / Bestellung -->
        <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
          <h3 class="text-sm font-bold text-slate-900 mb-1">Plan upgraden oder Sitze anfordern</h3>
          <p class="text-xs text-slate-600 font-medium mb-6">
            Deine Anfrage wird direkt an das Taskster-Team übermittelt.
          </p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <button
              v-for="p in plans"
              :key="p.key"
              @click="upgradeForm.plan = p.key"
              type="button"
              class="text-left rounded-2xl p-5 border-2 transition cursor-pointer"
              :class="upgradeForm.plan === p.key
                ? 'border-[#00A3C4] bg-cyan-50/70 shadow-md'
                : 'border-white/60 bg-white/60 hover:border-slate-300'"
            >
              <div class="flex items-center justify-between">
                <span class="text-sm font-black text-slate-900">{{ p.name }}</span>
                <span
                  v-if="company?.subscription_plan === p.key"
                  class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300"
                >
                  Aktuell
                </span>
              </div>
              <div class="text-lg font-black text-[#00A3C4] mt-1">
                {{ p.monthly > 0 ? p.monthly + ' CHF' : 'Gratis' }}
              </div>
              <div class="text-[11px] text-slate-600 font-medium">{{ p.seats }} Sitzplätze inklusive</div>
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Gewünschte Sitzplätze</label>
              <input
                v-model.number="upgradeForm.seats"
                type="number"
                min="1"
                class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Bemerkung (optional)</label>
              <input
                v-model="upgradeForm.note"
                type="text"
                placeholder="z.B. Wachstum im Q4"
                class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
              />
            </div>
          </div>

          <div class="flex items-center justify-end mt-6">
            <button
              @click="requestUpgrade"
              :disabled="sendingUpgrade"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg disabled:opacity-50"
            >
              {{ sendingUpgrade ? 'Wird gesendet...' : 'Upgrade anfragen' }}
            </button>
          </div>
        </div>

        <!-- Anfragen-Historie -->
        <div v-if="upgradeRequests.length > 0" class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
          <div class="p-4 sm:p-6 border-b border-slate-200/80">
            <h3 class="text-sm font-bold text-slate-900">Meine Upgrade-Anfragen</h3>
          </div>
          <div class="divide-y divide-slate-200/60">
            <div v-for="r in upgradeRequests" :key="r.id" class="p-4 sm:px-6 flex items-center justify-between gap-3">
              <div>
                <div class="text-xs font-bold text-slate-900">{{ planLabel(r.plan) }} · {{ r.seats || '—' }} Sitze</div>
                <div class="text-[11px] text-slate-500 font-medium">{{ r.requested_at }} · von {{ r.requested_by_name }}</div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                {{ r.status === 'pending' ? 'In Prüfung' : r.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: FIRMEN-EINSTELLUNGEN -->
      <div v-else-if="activeTab === 'settings'" class="space-y-6">
        <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
          <h3 class="text-sm font-bold text-slate-900 mb-1">Firmendaten</h3>
          <p class="text-xs text-slate-600 font-medium mb-6">Name deines Unternehmens in Taskster.</p>

          <form @submit.prevent="saveSettings" class="space-y-4 max-w-lg">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Firmenname</label>
              <input
                v-model="settingsForm.name"
                type="text"
                required
                class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Unternehmens-ID</label>
              <input
                :value="company?.id"
                type="text"
                disabled
                class="w-full px-3.5 py-2.5 bg-white/30 border border-white/40 rounded-xl text-xs text-slate-600 cursor-not-allowed font-mono backdrop-blur-sm"
              />
            </div>

            <div class="pt-6 border-t border-slate-200/60">
              <h4 class="text-xs font-black text-slate-900 mb-1">🔐 Zero-Trust Upload-Richtlinie</h4>
              <p class="text-[11px] text-slate-600 font-medium mb-3">
                Steuere, ob Mitarbeiter deines Unternehmens Dokumente hochladen dürfen.
              </p>

              <button
                type="button"
                @click="settingsForm.allow_document_upload = !settingsForm.allow_document_upload"
                class="px-4 py-2.5 rounded-xl text-xs font-bold border transition flex items-center space-x-2 cursor-pointer"
                :class="settingsForm.allow_document_upload
                  ? 'bg-emerald-100 text-emerald-900 border-emerald-300'
                  : 'bg-rose-100 text-rose-900 border-rose-300'"
              >
                <span>{{ settingsForm.allow_document_upload ? '✓ Uploads erlaubt' : '🚫 Uploads gesperrt (Policy)' }}</span>
              </button>
            </div>

            <div class="flex items-center justify-end pt-4">
              <button
                type="submit"
                :disabled="savingSettings"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg disabled:opacity-50"
              >
                {{ savingSettings ? 'Speichern...' : 'Einstellungen speichern' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- TAB: SUPPORT -->
      <div v-else-if="activeTab === 'support'" class="space-y-6">
        <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
          <h3 class="text-sm font-bold text-slate-900 mb-1">Support anfordern</h3>
          <p class="text-xs text-slate-600 font-medium mb-6">
            Stelle eine Anfrage direkt an das Taskster-Team. Business-Kunden erhalten bevorzugte Bearbeitung.
          </p>

          <form @submit.prevent="sendSupport" class="space-y-4 max-w-2xl">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Betreff</label>
                <input
                  v-model="supportForm.subject"
                  type="text"
                  required
                  placeholder="z.B. Frage zu Sitzplätzen"
                  class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
                />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Priorität</label>
                <select
                  v-model="supportForm.priority"
                  class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 font-semibold focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
                >
                  <option value="low">Niedrig</option>
                  <option value="normal">Normal</option>
                  <option value="high">Hoch</option>
                  <option value="urgent">Dringend</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Nachricht</label>
              <textarea
                v-model="supportForm.message"
                rows="5"
                required
                placeholder="Beschreibe dein Anliegen möglichst konkret..."
                class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm resize-y"
              ></textarea>
            </div>

            <div class="flex items-center justify-end">
              <button
                type="submit"
                :disabled="sendingSupport"
                class="taskster_button px-6 text-xs h-[42px] rounded-lg disabled:opacity-50"
              >
                {{ sendingSupport ? 'Wird gesendet...' : 'Support-Anfrage senden' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Ticket-Historie -->
        <div v-if="supportTickets.length > 0" class="liquid_glass rounded-3xl overflow-hidden shadow-xl">
          <div class="p-4 sm:p-6 border-b border-slate-200/80">
            <h3 class="text-sm font-bold text-slate-900">Meine Support-Anfragen</h3>
          </div>
          <div class="divide-y divide-slate-200/60">
            <div v-for="t in supportTickets" :key="t.id" class="p-4 sm:px-6">
              <div class="flex items-center justify-between gap-3">
                <div class="text-xs font-bold text-slate-900">{{ t.subject }}</div>
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border shrink-0"
                  :class="t.status === 'open'
                    ? 'bg-amber-100 text-amber-900 border-amber-300'
                    : 'bg-emerald-100 text-emerald-900 border-emerald-300'"
                >
                  {{ t.status === 'open' ? 'Offen' : t.status }}
                </span>
              </div>
              <p class="text-[11px] text-slate-600 font-medium mt-1">{{ t.message }}</p>
              <div class="text-[10px] text-slate-500 font-medium mt-1">
                {{ t.created_at }} · Priorität: {{ priorityLabel(t.priority) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- MODAL: Mitarbeiter einladen -->
    <div v-if="showInviteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
          <div>
            <h3 class="text-lg font-black text-slate-900">Mitarbeiter einladen</h3>
            <p class="text-xs text-slate-500 mt-0.5">Bestehende Nutzer werden sofort hinzugefügt, neue erhalten einen Einladungslink.</p>
          </div>
          <button @click="showInviteModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg">✕</button>
        </div>

        <form @submit.prevent="sendInvite" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
            <input
              v-model="inviteForm.email"
              type="email"
              required
              placeholder="mitarbeiter@firma.ch"
              class="w-full px-3.5 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Rolle</label>
            <select
              v-model="inviteForm.role"
              class="w-full px-3.5 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:border-cyan-600"
            >
              <option value="member">Mitarbeiter</option>
              <option value="admin">Co-Admin (Vollzugriff auf Firmenverwaltung)</option>
            </select>
          </div>

          <div v-if="generatedInviteLink" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200">
            <div class="text-[11px] font-bold text-emerald-900 mb-1">Einladungslink (bitte weitergeben):</div>
            <div class="flex items-center space-x-2">
              <input
                :value="generatedInviteLink"
                readonly
                class="flex-1 px-2.5 py-1.5 bg-white border border-emerald-200 rounded-lg text-[11px] font-mono text-slate-700"
              />
              <button type="button" @click="copyInviteLink()" class="taskster_button_light px-3 text-xs h-[34px] rounded-lg">Kopieren</button>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button type="button" @click="showInviteModal = false" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">Abbrechen</button>
            <button type="submit" :disabled="sendingInvite" class="taskster_button px-6 text-xs h-[42px] rounded-lg disabled:opacity-50">
              {{ sendingInvite ? 'Wird gesendet...' : 'Einladung senden' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: Firmenvorlage -->
    <div v-if="showTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
          <div>
            <h3 class="text-lg font-black text-slate-900">
              {{ editingTemplate ? 'Firmenvorlage bearbeiten' : 'Neue Firmenvorlage' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Nur für Mitarbeiter deines Unternehmens sichtbar.</p>
          </div>
          <button @click="showTemplateModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg">✕</button>
        </div>

        <form @submit.prevent="saveTemplate" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Name</label>
              <input
                v-model="templateForm.name"
                type="text"
                required
                placeholder="z.B. Bauleitung Standard"
                class="w-full px-3.5 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Kategorie</label>
              <select
                v-model="templateForm.category"
                class="w-full px-3.5 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:border-cyan-600"
              >
                <option value="job">Job / Geschäftlich</option>
                <option value="private">Privat</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Beschreibung</label>
            <textarea
              v-model="templateForm.description"
              rows="2"
              class="w-full px-3.5 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600 resize-y"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Listen (Spalten im Board)</label>
            <div class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="(l, i) in templateForm.lists"
                :key="i"
                class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[11px] font-bold text-slate-700"
              >
                <span>{{ l }}</span>
                <button type="button" @click="templateForm.lists.splice(i, 1)" class="text-slate-400 hover:text-rose-600 font-black">×</button>
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <input
                v-model="newListInput"
                type="text"
                placeholder="Listenname hinzufügen"
                class="flex-1 px-3.5 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600"
              />
              <button type="button" @click="addList" class="taskster_button_light px-4 text-xs h-[42px] rounded-lg">Hinzufügen</button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-2">Custom Fields (Projekt &amp; Aufgabe)</label>
            <div class="space-y-2 mb-3">
              <div
                v-for="(f, i) in templateForm.fields"
                :key="i"
                class="flex items-center justify-between p-2.5 rounded-xl bg-white/80 border border-slate-200"
              >
                <div class="min-w-0">
                  <div class="text-[11px] font-bold text-slate-900">{{ f.label }}</div>
                  <div class="text-[10px] text-slate-500 font-medium">
                    {{ f.field_type }} · {{ f.entity_type === 'project' ? 'Projekt' : 'Aufgabe' }}
                  </div>
                </div>
                <button type="button" @click="templateForm.fields.splice(i, 1)" class="text-slate-400 hover:text-rose-600 font-black px-2">×</button>
              </div>
              <div v-if="templateForm.fields.length === 0" class="text-[11px] text-slate-500 italic font-medium">
                Noch keine Felder definiert.
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <input
                v-model="newFieldForm.label"
                type="text"
                placeholder="Feldbezeichnung"
                class="sm:col-span-1 px-3 py-2 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600"
              />
              <select
                v-model="newFieldForm.field_type"
                class="px-3 py-2 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:border-cyan-600 cursor-pointer"
              >
                <option value="text">Textzeile (kurz)</option>
                <option value="textarea">Längerer Text / Notizfeld</option>
                <option value="number">Zahl / Währung</option>
                <option value="date">Datum</option>
                <option value="select">Auswahlliste (Dropdown)</option>
                <option value="checkbox">Checkbox (Ja / Nein)</option>
                <option value="url">Weblink / URL</option>
                <option value="email">E-Mail-Adresse</option>
                <option value="phone">Telefonnummer</option>
              </select>
              <select
                v-model="newFieldForm.entity_type"
                class="px-3 py-2 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:border-cyan-600"
              >
                <option value="task">Aufgabe</option>
                <option value="project">Projekt</option>
              </select>
            </div>
            <input
              v-if="newFieldForm.field_type === 'select'"
              v-model="newFieldForm.optionsInput"
              type="text"
              placeholder="Optionen (kommagetrennt)"
              class="w-full mt-2 px-3 py-2 bg-white/70 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600"
            />
            <button type="button" @click="addField" class="mt-2 taskster_button_light px-4 text-xs h-[36px] rounded-lg">
              + Feld hinzufügen
            </button>
          </div>

          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
            <button type="button" @click="showTemplateModal = false" class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">Abbrechen</button>
            <button type="submit" :disabled="savingTemplate" class="taskster_button px-6 text-xs h-[42px] rounded-lg disabled:opacity-50">
              {{ savingTemplate ? 'Speichern...' : 'Vorlage speichern' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Building2,
  Users,
  ShieldCheck,
  Plus,
  Pencil,
  Trash2,
  Mail,
  UserPlus,
  Search,
  X,
  Check,
  Lock,
  LayoutDashboard
} from 'lucide-vue-next'

definePageMeta({
  middleware: [
    function () {
      if (import.meta.client) {
        const token = localStorage.getItem('taskster_token')
        if (!token) {
          return navigateTo('/login')
        }
      }
    }
  ]
})

const { user, authHeaders, initAuth } = useAuth()

type TabKey = 'members' | 'templates' | 'billing' | 'settings' | 'support'

const tabs: { key: TabKey; label: string; icon: string }[] = [
  { key: 'members', label: 'Mitarbeiter & Co-Admins', icon: '👥' },
  { key: 'templates', label: 'Firmenvorlagen', icon: '📋' },
  { key: 'billing', label: 'Plan & Lizenzen', icon: '💳' },
  { key: 'settings', label: 'Firmen-Einstellungen', icon: '⚙️' },
  { key: 'support', label: 'Support', icon: '💬' }
]

const activeTab = ref<TabKey>('members')

const company = ref<any>(null)
const stats = ref<any>({})
const members = ref<any[]>([])
const invitations = ref<any[]>([])
const templates = ref<any[]>([])
const upgradeRequests = ref<any[]>([])
const supportTickets = ref<any[]>([])

const loading = ref(true)
const successMsg = ref('')
const errorMsg = ref('')

const isCompanyAdmin = computed(() => {
  if (!user.value) return false
  // Firmen-Admin: company_role === 'admin' mit zugewiesenem Unternehmen
  if (user.value.company_id && user.value.company_role === 'admin') return true
  // Plattform-Superadmin mit Firmenzuordnung darf ebenfalls verwalten
  if (user.value.is_superadmin && user.value.company_id) return true
  return false
})

// Superadmin ohne Firmenzuordnung gehört ins Plattform-Portal
const isSuperadminWithoutCompany = computed(() =>
  Boolean(user.value?.is_superadmin && !user.value?.company_id)
)

const seatUsagePercent = computed(() => {
  const max = Number(stats.value?.max_seats || 0)
  const used = Number(stats.value?.seats_used || 0)
  if (!max) return 0
  return Math.round((used / max) * 100)
})

const planBadgeClass = computed(() => {
  const plan = company.value?.subscription_plan
  if (plan === 'enterprise') return 'bg-purple-100 text-purple-900 border-purple-300'
  if (plan === 'pro') return 'bg-cyan-100 text-cyan-900 border-cyan-300'
  return 'bg-slate-200 text-slate-700 border-slate-300'
})

const plans = [
  { key: 'starter', name: 'Starter Plan', monthly: 0, seats: 5 },
  { key: 'pro', name: 'Pro Business Plan', monthly: 49, seats: 25 },
  { key: 'enterprise', name: 'Enterprise Custom', monthly: 189, seats: 100 }
]

const planLabel = (key: string) => plans.find(p => p.key === key)?.name || key
const priorityLabel = (key: string) => ({ low: 'Niedrig', normal: 'Normal', high: 'Hoch', urgent: 'Dringend' } as any)[key] || key

const formatDate = (value: string) => {
  if (!value) return '—'
  const d = new Date(value.replace(' ', 'T'))
  if (isNaN(d.getTime())) return value
  return d.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const flash = (msg: string) => {
  successMsg.value = msg
  errorMsg.value = ''
  setTimeout(() => { successMsg.value = '' }, 4000)
}

const flashError = (err: any, fallback: string) => {
  errorMsg.value = err?.data?.statusMessage || err?.statusMessage || fallback
  successMsg.value = ''
  setTimeout(() => { errorMsg.value = '' }, 6000)
}

// ---------------------------------------------------------------
// Daten laden
// ---------------------------------------------------------------
const loadCompanyData = async () => {
  loading.value = true
  try {
    const [details, memberRes] = await Promise.all([
      $fetch<any>('/api/company/details', { headers: authHeaders() }),
      $fetch<any>('/api/companies/members', { headers: authHeaders() }).catch(() => ({ members: [] }))
    ])

    company.value = details.company
    stats.value = details.stats || {}
    upgradeRequests.value = details.upgrade_requests || []
    supportTickets.value = details.support_tickets || []
    members.value = memberRes.members || []

    settingsForm.value.name = details.company?.name || ''
    settingsForm.value.allow_document_upload = details.company?.settings?.allow_document_upload !== false

    await Promise.all([loadTemplates(), loadInvitations()])
  } catch (err: any) {
    if (err?.statusCode === 403 || err?.statusCode === 401) {
      navigateTo('/dashboard')
      return
    }
    flashError(err, 'Fehler beim Laden der Firmendaten')
  } finally {
    loading.value = false
  }
}

const loadTemplates = async () => {
  try {
    const res = await $fetch<any>('/api/company/templates', { headers: authHeaders() })
    templates.value = res.templates || []
  } catch (err: any) {
    console.warn('Templates fetch error:', err)
  }
}

const loadInvitations = async () => {
  try {
    const res = await $fetch<any>('/api/companies/invitations', { headers: authHeaders() })
    invitations.value = (res.invitations || []).filter((i: any) => i.status === 'pending')
  } catch {
    invitations.value = []
  }
}

// ---------------------------------------------------------------
// Mitarbeiter
// ---------------------------------------------------------------
const showInviteModal = ref(false)
const sendingInvite = ref(false)
const generatedInviteLink = ref('')
const inviteForm = ref({ email: '', role: 'member' })

const openInviteModal = () => {
  inviteForm.value = { email: '', role: 'member' }
  generatedInviteLink.value = ''
  showInviteModal.value = true
}

const sendInvite = async () => {
  sendingInvite.value = true
  generatedInviteLink.value = ''
  try {
    const res = await $fetch<any>('/api/companies/members', {
      method: 'POST',
      headers: authHeaders(),
      body: { email: inviteForm.value.email, role: inviteForm.value.role }
    })

    if (res.action === 'added') {
      flash(`${inviteForm.value.email} wurde dem Unternehmen hinzugefügt.`)
      showInviteModal.value = false
    } else {
      generatedInviteLink.value = `${window.location.origin}/login?token=${res.token}`
      flash(`Einladung für ${inviteForm.value.email} erstellt.`)
    }

    inviteForm.value.email = ''
    await Promise.all([loadCompanyData(), loadInvitations()])
  } catch (err: any) {
    flashError(err, 'Fehler beim Einladen des Mitarbeiters')
  } finally {
    sendingInvite.value = false
  }
}

const copyInviteLink = (token?: string) => {
  const link = token
    ? `${window.location.origin}/login?token=${token}`
    : generatedInviteLink.value
  if (!link) return
  navigator.clipboard?.writeText(link)
  flash('Einladungslink in die Zwischenablage kopiert.')
}

const changeRole = async (member: any, role: 'admin' | 'member') => {
  try {
    await $fetch(`/api/company/members/${member.id}`, {
      method: 'PATCH',
      headers: authHeaders(),
      body: { role }
    })
    member.company_role = role
    flash(role === 'admin'
      ? `${member.name} ist jetzt Co-Admin.`
      : `${member.name} ist jetzt Mitarbeiter.`)
    await loadCompanyData()
  } catch (err: any) {
    flashError(err, 'Fehler beim Ändern der Rolle')
  }
}

const removeMember = async (member: any) => {
  if (!confirm(`${member.name} wirklich aus dem Unternehmen entfernen?`)) return
  try {
    await $fetch(`/api/company/members/${member.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    flash(`${member.name} wurde aus dem Unternehmen entfernt.`)
    await loadCompanyData()
  } catch (err: any) {
    flashError(err, 'Fehler beim Entfernen des Mitarbeiters')
  }
}

// ---------------------------------------------------------------
// Firmenvorlagen
// ---------------------------------------------------------------
const showTemplateModal = ref(false)
const savingTemplate = ref(false)
const editingTemplate = ref<any>(null)
const newListInput = ref('')
const newFieldForm = ref({ label: '', field_type: 'text', entity_type: 'task', optionsInput: '' })

const templateForm = ref({
  name: '',
  category: 'job',
  description: '',
  lists: [] as string[],
  fields: [] as any[]
})

const openTemplateModal = (tmpl?: any) => {
  editingTemplate.value = tmpl || null
  newListInput.value = ''
  newFieldForm.value = { label: '', field_type: 'text', entity_type: 'task', optionsInput: '' }

  if (tmpl) {
    templateForm.value = {
      name: tmpl.name || '',
      category: tmpl.category || 'job',
      description: tmpl.description || '',
      lists: [...(tmpl.lists || [])],
      fields: JSON.parse(JSON.stringify(tmpl.fields || []))
    }
  } else {
    templateForm.value = {
      name: '',
      category: 'job',
      description: '',
      lists: ['Planung', 'In Bearbeitung', 'Abnahme', 'Erledigt'],
      fields: []
    }
  }
  showTemplateModal.value = true
}

const addList = () => {
  const v = newListInput.value.trim()
  if (v && !templateForm.value.lists.includes(v)) {
    templateForm.value.lists.push(v)
    newListInput.value = ''
  }
}

const addField = () => {
  const label = newFieldForm.value.label.trim()
  if (!label) return
  templateForm.value.fields.push({
    field_key: label.toLowerCase().replace(/[^a-z0-9_]/g, '_'),
    label,
    field_type: newFieldForm.value.field_type,
    entity_type: newFieldForm.value.entity_type,
    options: newFieldForm.value.field_type === 'select'
      ? newFieldForm.value.optionsInput.split(',').map(s => s.trim()).filter(Boolean)
      : [],
    logic_rules: null,
    is_required: false
  })
  newFieldForm.value = { label: '', field_type: 'text', entity_type: 'task', optionsInput: '' }
}

const saveTemplate = async () => {
  savingTemplate.value = true
  try {
    if (editingTemplate.value) {
      await $fetch(`/api/templates/${editingTemplate.value.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: templateForm.value
      })
      flash('Firmenvorlage aktualisiert.')
    } else {
      await $fetch('/api/templates', {
        method: 'POST',
        headers: authHeaders(),
        body: templateForm.value
      })
      flash('Firmenvorlage erstellt.')
    }
    showTemplateModal.value = false
    await Promise.all([loadTemplates(), loadCompanyData()])
  } catch (err: any) {
    flashError(err, 'Fehler beim Speichern der Vorlage')
  } finally {
    savingTemplate.value = false
  }
}

const deleteTemplate = async (tmpl: any) => {
  if (!confirm(`Vorlage "${tmpl.name}" wirklich löschen?`)) return
  try {
    await $fetch(`/api/templates/${tmpl.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    flash('Firmenvorlage gelöscht.')
    await Promise.all([loadTemplates(), loadCompanyData()])
  } catch (err: any) {
    flashError(err, 'Fehler beim Löschen der Vorlage')
  }
}

// ---------------------------------------------------------------
// Plan & Upgrade
// ---------------------------------------------------------------
const sendingUpgrade = ref(false)
const upgradeForm = ref({ plan: 'pro', seats: 25, note: '' })

const requestUpgrade = async () => {
  sendingUpgrade.value = true
  try {
    await $fetch('/api/company/upgrade', {
      method: 'POST',
      headers: authHeaders(),
      body: upgradeForm.value
    })
    flash('Upgrade-Anfrage wurde an das Taskster-Team übermittelt.')
    await loadCompanyData()
  } catch (err: any) {
    flashError(err, 'Fehler beim Senden der Upgrade-Anfrage')
  } finally {
    sendingUpgrade.value = false
  }
}

// ---------------------------------------------------------------
// Firmen-Einstellungen
// ---------------------------------------------------------------
const savingSettings = ref(false)
const settingsForm = ref({ name: '', allow_document_upload: true })

const saveSettings = async () => {
  savingSettings.value = true
  try {
    await $fetch('/api/company/details', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: settingsForm.value.name,
        settings: { allow_document_upload: settingsForm.value.allow_document_upload }
      }
    })
    flash('Firmen-Einstellungen gespeichert.')
    await loadCompanyData()
  } catch (err: any) {
    flashError(err, 'Fehler beim Speichern der Einstellungen')
  } finally {
    savingSettings.value = false
  }
}

// ---------------------------------------------------------------
// Support
// ---------------------------------------------------------------
const sendingSupport = ref(false)
const supportForm = ref({ subject: '', message: '', priority: 'normal' })

const sendSupport = async () => {
  sendingSupport.value = true
  try {
    await $fetch('/api/company/support', {
      method: 'POST',
      headers: authHeaders(),
      body: supportForm.value
    })
    flash('Support-Anfrage wurde gesendet.')
    supportForm.value = { subject: '', message: '', priority: 'normal' }
    await loadCompanyData()
  } catch (err: any) {
    flashError(err, 'Fehler beim Senden der Support-Anfrage')
  } finally {
    sendingSupport.value = false
  }
}

// ---------------------------------------------------------------
// Init
// ---------------------------------------------------------------
onMounted(async () => {
  if (!user.value) {
    await initAuth()
  }
  if (!isCompanyAdmin.value) {
    return
  }
  await loadCompanyData()
})
</script>
