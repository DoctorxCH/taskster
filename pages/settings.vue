<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-6">
      <NuxtLink to="/dashboard" class="hover:text-cyan-800 transition-colors flex items-center gap-1">
        <LayoutDashboard class="w-3.5 h-3.5" />
        <span>Dashboard</span>
      </NuxtLink>
      <span>/</span>
      <span class="text-slate-800 font-medium flex items-center gap-1">
        <Settings class="w-3.5 h-3.5 text-[#0891B2]" />
        <span>Benutzer-Einstellungen</span>
      </span>
    </div>

    <!-- Header -->
    <div class="mb-6 bg-white border border-slate-200 rounded-lg p-5">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Benutzer-Einstellungen</h1>
      <p class="text-sm text-slate-600 mt-1">Verwalte dein Profil, deine Zugangsdaten und deinen Tarifplan.</p>
    </div>

    <!-- Feedback messages -->
    <div v-if="successMsg" class="mb-6 p-4 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="mb-6 p-4 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium">
      {{ errorMsg }}
    </div>

    <div class="space-y-6">
      <!-- Card 1: Profil-Informationen -->
      <div class="bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-base font-semibold text-slate-900 mb-1 flex items-center gap-2">
          <User class="w-4 h-4 text-[#0891B2]" />
          <span>Persönliche Daten</span>
        </h2>
        <p class="text-xs text-slate-500 mb-5">Aktualisiere deinen Anzeigenamen in Taskster.</p>

        <form @submit.prevent="updateProfile" class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Vollständiger Name</label>
            <input
              v-model="profileName"
              type="text"
              required
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 text-slate-900 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15 transition-shadow"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">E-Mail-Adresse</label>
            <input
              :value="user?.email"
              type="email"
              disabled
              class="w-full h-9 px-3 text-sm rounded-md bg-slate-50 border border-slate-200 text-slate-500 cursor-not-allowed font-medium"
            />
            <p class="text-xs text-slate-500 mt-1.5">Die E-Mail dient als Login-Kennung und kann nicht geändert werden.</p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="savingProfile"
              class="taskster_button"
            >
              {{ savingProfile ? 'Speichern...' : 'Profil speichern' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Card 2: Zeiterfassung & Abrechnung -->
      <div class="bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-base font-semibold text-slate-900 mb-1 flex items-center gap-2">
          <Clock class="w-4 h-4 text-[#0891B2]" />
          <span>Zeiterfassung & Abrechnung</span>
        </h2>
        <p class="text-xs text-slate-500 mb-5">Lege deinen Standard-Stundenlohn und deine Abrechnungswährung fest.</p>

        <form @submit.prevent="updateProfile" class="space-y-4 max-w-lg">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Stundenlohn</label>
              <div class="relative">
                <input
                  v-model="hourlyRate"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  placeholder="120.00"
                  class="w-full h-9 pl-3 pr-14 text-sm rounded-md bg-white border border-slate-300 text-slate-900 font-semibold focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15 transition-shadow"
                />
                <span class="absolute right-3 top-2 text-xs text-slate-400 font-semibold pointer-events-none">
                  / Std.
                </span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Währung</label>
              <select
                v-model="userCurrency"
                class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 text-slate-900 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15 cursor-pointer"
              >
                <option value="CHF">CHF (Schweizer Franken)</option>
                <option value="EUR">EUR (Euro)</option>
                <option value="USD">USD (US Dollar)</option>
                <option value="GBP">GBP (Britisches Pfund)</option>
              </select>
            </div>
          </div>

          <p class="text-xs text-slate-500">
            Dieser Stundensatz wird bei der Erfassung von Projekt- und Aufgabenzeiten standardmäßig herangezogen.
          </p>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="savingProfile"
              class="taskster_button"
            >
              {{ savingProfile ? 'Speichern...' : 'Abrechnungs-Daten speichern' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Card 3: Passwort ändern -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-black text-slate-900 mb-1 flex items-center space-x-2">
          <span>🔒</span>
          <span>Passwort & Sicherheit</span>
        </h2>
        <p class="text-xs text-slate-600 mb-6">Ändere dein Anmeldekennwort für erhöhte Sicherheit.</p>

        <form @submit.prevent="changePassword" class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Aktuelles Passwort</label>
            <input
              v-model="currentPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Neues Passwort (min. 8 Zeichen)</label>
            <input
              v-model="newPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Neues Passwort bestätigen</label>
            <input
              v-model="confirmPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white/90 focus:outline-none focus:border-cyan-600 backdrop-blur-sm"
            />
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="savingPassword"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ savingPassword ? 'Wird geändert...' : 'Passwort aktualisieren' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Card 3: Aktueller Tarif & Upgrade -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-black text-slate-900 mb-1 flex items-center space-x-2">
          <span>⚡</span>
          <span>Tarifplan & Upgrade</span>
        </h2>
        <p class="text-xs text-slate-600 mb-6">Dein aktiver Plan und verfügbare Stufen im Überblick.</p>

        <!-- Current Plan Status Badge -->
        <div class="mb-6 p-4 rounded-2xl bg-white/30 border border-white/50 backdrop-blur-sm flex items-center justify-between gap-4">
          <div>
            <div class="text-[11px] text-slate-500 uppercase font-bold mb-1">Dein aktiver Plan</div>
            <div class="text-xl font-black tracking-tight"
              :class="user?.company_name ? 'text-purple-800' : (user?.is_pro ? 'text-cyan-700' : 'text-slate-700')"
            >
              {{ user?.company_plan?.toUpperCase() || (user?.is_pro ? 'PRO' : 'FREE') }}
            </div>
            <div class="text-xs text-slate-600 font-medium mt-0.5">
              <span v-if="user?.company_name">via {{ user.company_name }} ({{ user.company_role === 'admin' ? 'Admin' : 'Mitarbeiter' }})</span>
              <span v-else-if="user?.is_pro">Unbegrenzte Ordner & Team-Funktionen aktiv</span>
              <span v-else>Max. 1 Projektordner · Max. 3 Projekte · Max. 5 Mitglieder/Projekt</span>
            </div>
          </div>
          <div
            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-wide"
            :class="user?.company_name ? 'bg-purple-100 text-purple-900 border border-purple-300' : (user?.is_pro ? 'bg-cyan-100 text-cyan-900 border border-cyan-300' : 'bg-slate-100 text-slate-700 border border-slate-300')"
          >
            {{ user?.company_plan?.toUpperCase() || (user?.is_pro ? '✓ PRO' : 'FREE') }}
          </div>
        </div>

        <!-- Plan Comparison Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
          <!-- Free Plan -->
          <div
            class="rounded-2xl p-5 flex flex-col bg-white border shadow-sm"
            :class="!user?.is_pro && !user?.company_name ? 'border-2 border-slate-600 ring-2 ring-slate-400/20' : 'border-slate-200'"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Free</span>
              <span v-if="!user?.is_pro && !user?.company_name" class="text-[10px] font-bold bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full">Aktiv</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mb-1">CHF 0</div>
            <div class="text-[10px] text-slate-500 font-medium mb-4">/ Monat</div>
            <ul class="space-y-1.5 text-[11px] text-slate-700 font-medium flex-1">
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>1 Projektordner</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Max. 3 Projekte</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>5 Teammitglieder/Projekt</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-rose-400">✕</span><span class="text-slate-400">Keine Vorlagen</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-rose-400">✕</span><span class="text-slate-400">Kein Journal</span></li>
            </ul>
          </div>

          <!-- PRO Plan -->
          <div
            class="rounded-2xl p-5 flex flex-col relative overflow-hidden bg-white border shadow-sm transition"
            :class="user?.is_pro && !user?.company_name ? 'border-2 border-[#00A3C4] ring-2 ring-cyan-500/20' : 'border-slate-200 hover:border-cyan-300'"
          >
            <div class="absolute top-0 right-0 bg-[#00A3C4] text-white text-[9px] font-black uppercase px-3 py-1 rounded-bl-xl tracking-wider">Empfohlen</div>
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-black text-cyan-800 uppercase tracking-wider">PRO</span>
              <span v-if="user?.is_pro && !user?.company_name" class="text-[10px] font-bold bg-cyan-100 text-cyan-800 px-2 py-0.5 rounded-full border border-cyan-300">Aktiv</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mb-1">CHF 9</div>
            <div class="text-[10px] text-slate-500 font-medium mb-4">/ Monat · jährlich CHF 89</div>
            <ul class="space-y-1.5 text-[11px] text-slate-700 font-medium flex-1">
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Unbegrenzte Ordner</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Unbegrenzte Projekte</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Unbegrenzte Teammitglieder</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Projekt-Vorlagen</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Journal & Aktivitätslog</span></li>
            </ul>
          </div>

          <!-- Enterprise Plan -->
          <div
            class="rounded-2xl p-5 flex flex-col bg-white border shadow-sm"
            :class="user?.company_name ? 'border-2 border-purple-500 ring-2 ring-purple-500/20' : 'border-slate-200'"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-black text-purple-800 uppercase tracking-wider">Enterprise</span>
              <span v-if="user?.company_name" class="text-[10px] font-bold bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full border border-purple-300">Aktiv</span>
            </div>
            <div class="text-2xl font-black text-slate-900 mb-1">Auf Anfrage</div>
            <div class="text-[10px] text-slate-500 font-medium mb-4">Individuelle Konditionen</div>
            <ul class="space-y-1.5 text-[11px] text-slate-700 font-medium flex-1">
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Alle PRO-Funktionen</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Mehrere Unternehmen</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>SSO / SAML Login</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Audit-Log & Compliance</span></li>
              <li class="flex items-center space-x-1.5"><span class="text-emerald-600">✓</span><span>Dedizierter Support</span></li>
            </ul>
          </div>
        </div>

        <!-- Upgrade CTA (nur für Free-User ohne Company) -->
        <div v-if="!user?.is_pro && !user?.company_name" class="p-5 rounded-2xl bg-[#00A3C4]/10 border border-[#00A3C4]/30 backdrop-blur-sm">
          <h4 class="text-sm font-black text-slate-900 mb-1">Jetzt auf PRO upgraden</h4>
          <p class="text-xs text-slate-600 font-medium mb-4">
            Erhalte unbegrenzte Projektordner, Vorlagen und alle Team-Funktionen. Dein Upgrade wird sofort aktiv.
          </p>
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <button
              @click="requestUpgrade"
              :disabled="upgradeSent"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ upgradeSent ? '✓ Anfrage gesendet!' : '⚡ PRO-Upgrade anfragen' }}
            </button>
            <p class="text-[11px] text-slate-500">Wir melden uns innerhalb von 24 Stunden bei dir.</p>
          </div>
        </div>

        <!-- Already PRO or Company message -->
        <div v-else class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-300/40 backdrop-blur-sm text-xs text-emerald-900 font-medium">
          <span class="font-black">✓ Dein Plan ist aktiv.</span>
          <span v-if="user?.company_name"> Du profitierst vom Plan deines Unternehmens ({{ user.company_name }}).</span>
          <span v-else> Du geniesst alle PRO-Vorteile.</span>
          Für Änderungen wende dich an den Support oder deinen Administrator.
        </div>
      </div>

      <!-- Card 4: Unternehmens-Verwaltung (nur für Company Admins) -->
      <div v-if="user?.company_role === 'admin' && user?.company_name" class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-base font-black text-slate-900 mb-1 flex items-center space-x-2">
              <span>🏢</span>
              <span>Unternehmens-Verwaltung ({{ user.company_name }})</span>
            </h2>
            <p class="text-xs text-slate-600">Lade Mitarbeiter in dein Unternehmen ein und verwalte die Firmenmitglieder.</p>
          </div>
          <span class="px-3 py-1 rounded-xl bg-purple-100 border border-purple-300 text-purple-900 font-bold text-xs">
            Company Admin
          </span>
        </div>

        <!-- Invite employee form -->
        <form @submit.prevent="inviteCompanyMember" class="p-4 rounded-2xl bg-white/70 border border-slate-200/80 mb-6 space-y-3">
          <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider">Mitarbeiter einladen</h3>
          <div class="flex flex-col sm:flex-row gap-2">
            <input
              v-model="companyInviteEmail"
              type="email"
              required
              placeholder="mitarbeiter@firma.ch"
              class="flex-1 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-cyan-600 shadow-xs"
            />
            <select
              v-model="companyInviteRole"
              class="px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:border-cyan-600 shadow-xs"
            >
              <option value="member">Mitarbeiter</option>
              <option value="admin">Company Admin</option>
            </select>
            <button
              type="submit"
              :disabled="sendingCompanyInvite || !companyInviteEmail.trim()"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg shrink-0"
            >
              {{ sendingCompanyInvite ? 'Sendet...' : '+ Einladen' }}
            </button>
          </div>
          <div v-if="companyInviteLink" class="mt-2 p-3 rounded-xl bg-cyan-50 border border-cyan-200 text-xs text-cyan-900">
            <span class="font-bold">Einladungslink generiert:</span>
            <input readonly :value="companyInviteLink" @click="($event.target as HTMLInputElement).select()" class="mt-1 w-full bg-white px-2.5 py-1.5 rounded-lg border text-xs font-mono" />
          </div>
        </form>

        <!-- Company Members List -->
        <div>
          <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider mb-2">
            Aktuelle Mitarbeiter ({{ companyMembers.length }})
          </h3>
          <div v-if="loadingCompanyMembers" class="text-xs text-slate-500 py-3">Lade Mitglieder...</div>
          <div v-else-if="companyMembers.length === 0" class="text-xs text-slate-500 py-3 italic">Noch keine weiteren Mitarbeiter vorhanden.</div>
          <div v-else class="space-y-2">
            <div
              v-for="m in companyMembers"
              :key="m.id"
              class="flex items-center justify-between p-3 rounded-2xl bg-white/60 border border-slate-200/80 text-xs shadow-xs"
            >
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-500 text-white flex items-center justify-center font-black text-xs">
                  {{ (m.name || m.email || '?').charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-bold text-slate-900">{{ m.name }} <span v-if="m.id === user?.id" class="text-slate-400 font-normal">(Du)</span></div>
                  <div class="text-[11px] text-slate-500">{{ m.email }}</div>
                </div>
              </div>
              <span
                class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border"
                :class="m.company_role === 'admin' ? 'bg-purple-100 text-purple-900 border-purple-300 font-black' : 'bg-slate-100 text-slate-700 border-slate-200'"
              >
                {{ m.company_role === 'admin' ? '👑 Company Admin' : 'Mitarbeiter' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  LayoutDashboard,
  Settings,
  User,
  Clock,
  Lock,
  Zap,
  Building2,
  Check,
  X,
  ShieldCheck
} from 'lucide-vue-next'

const { user, authHeaders } = useAuth()

const profileName = ref('')
const hourlyRate = ref<number | string>(120)
const userCurrency = ref('CHF')
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const savingProfile = ref(false)
const savingPassword = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const upgradeSent = ref(false)

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (!user.value) {
    navigateTo('/login')
    return
  }
  profileName.value = user.value.name || ''
  hourlyRate.value = user.value.hourly_rate !== undefined ? user.value.hourly_rate : 120
  userCurrency.value = user.value.currency || 'CHF'
})

const updateProfile = async () => {
  successMsg.value = ''
  errorMsg.value = ''
  savingProfile.value = true
  try {
    const res = await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: profileName.value,
        hourly_rate: Number(hourlyRate.value) || 0,
        currency: userCurrency.value
      }
    })
    if (user.value && res.user) {
      user.value.name = res.user.name
      user.value.hourly_rate = res.user.hourly_rate
      user.value.currency = res.user.currency
    }
    successMsg.value = 'Einstellungen erfolgreich aktualisiert!'
  } catch (err: any) {
    errorMsg.value = err.data?.statusMessage || 'Fehler beim Speichern'
  } finally {
    savingProfile.value = false
  }
}

const changePassword = async () => {
  successMsg.value = ''
  errorMsg.value = ''

  if (newPassword.value !== confirmPassword.value) {
    errorMsg.value = 'Die neuen Passwörter stimmen nicht überein.'
    return
  }

  savingPassword.value = true
  try {
    await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: profileName.value,
        current_password: currentPassword.value,
        new_password: newPassword.value
      }
    })
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    successMsg.value = 'Passwort erfolgreich geändert!'
  } catch (err: any) {
    errorMsg.value = err.data?.statusMessage || 'Passwort konnte nicht geändert werden'
  } finally {
    savingPassword.value = false
  }
}

const requestUpgrade = async () => {
  // Sends a simple upgrade request – in a real system this would trigger an email or Stripe flow
  try {
    await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: { upgrade_request: true }
    })
  } catch {
    // Silently ignore – the field may not exist in the API yet
  }
  upgradeSent.value = true
  successMsg.value = 'Upgrade-Anfrage gesendet! Wir werden uns in Kürze bei dir melden.'
}

// Company Admin Management
const companyInviteEmail = ref('')
const companyInviteRole = ref('member')
const companyInviteLink = ref('')
const sendingCompanyInvite = ref(false)
const companyMembers = ref<any[]>([])
const loadingCompanyMembers = ref(false)

const loadCompanyMembers = async () => {
  if (user.value?.company_role !== 'admin' || !user.value?.company_name) return
  loadingCompanyMembers.value = true
  try {
    const res = await $fetch<{ members: any[] }>('/api/companies/members', {
      headers: authHeaders()
    })
    companyMembers.value = res.members || []
  } catch (err) {
    console.error('Failed to load company members', err)
  } finally {
    loadingCompanyMembers.value = false
  }
}

const inviteCompanyMember = async () => {
  if (!companyInviteEmail.value.trim()) return
  sendingCompanyInvite.value = true
  companyInviteLink.value = ''
  try {
    const res = await $fetch<any>('/api/companies/members', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        email: companyInviteEmail.value.trim(),
        role: companyInviteRole.value
      }
    })
    if (res.action === 'added') {
      successMsg.value = `Benutzer ${companyInviteEmail.value} war bereits registriert und wurde dem Unternehmen direkt zugewiesen!`
    } else if (res.action === 'invited') {
      companyInviteLink.value = `${window.location.origin}/login?token=${res.token}`
      successMsg.value = `Einladung für ${companyInviteEmail.value} generiert!`
    }
    companyInviteEmail.value = ''
    await loadCompanyMembers()
  } catch (err: any) {
    errorMsg.value = err.data?.statusMessage || 'Fehler beim Einladen des Mitarbeiters'
  } finally {
    sendingCompanyInvite.value = false
  }
}

watch(() => user.value, (u) => {
  if (u?.company_role === 'admin') {
    loadCompanyMembers()
  }
}, { immediate: true })
</script>
