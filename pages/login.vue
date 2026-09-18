<template>
  <div class="max-w-md mx-auto my-12 px-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex w-12 h-12 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 items-center justify-center font-black text-slate-950 text-2xl shadow-lg shadow-emerald-500/20 mb-3">
          T
        </div>
        <h2 class="text-2xl font-bold text-white tracking-tight">Taskster Authentifizierung</h2>
        <p class="text-xs text-slate-400 mt-1">Moderne Projekt- & Aufgabenmanagement Plattform</p>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-800 mb-6">
        <button
          @click="activeTab = 'login'"
          class="flex-1 py-2 text-sm font-semibold border-b-2 transition"
          :class="activeTab === 'login' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
        >
          Anmelden
        </button>
        <button
          @click="activeTab = 'register'"
          class="flex-1 py-2 text-sm font-semibold border-b-2 transition"
          :class="activeTab === 'register' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'"
        >
          Registrieren
        </button>
      </div>

      <!-- Invitation Banner if token is present -->
      <div v-if="invitationInfo" class="mb-5 p-3 rounded-xl bg-purple-950/60 border border-purple-800 text-purple-200 text-xs">
        <div class="font-bold text-sm text-purple-300 mb-0.5">Einladung zu {{ invitationInfo.company_name }}</div>
        <div>Du wurdest eingeladen, diesem Unternehmen beizutreten. Erstelle ein Konto oder melde dich an, um den Beitritt abzuschliessen.</div>
      </div>

      <!-- Error banner -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-lg bg-rose-950/60 border border-rose-800/80 text-rose-300 text-xs">
        {{ errorMessage }}
      </div>

      <!-- Success banner -->
      <div v-if="successMessage" class="mb-5 p-3 rounded-lg bg-emerald-950/60 border border-emerald-800/80 text-emerald-300 text-xs">
        {{ successMessage }}
      </div>

      <!-- Login Form -->
      <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">E-Mail-Adresse</label>
          <input
            v-model="loginEmail"
            type="email"
            required
            placeholder="name@domain.ch"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Passwort</label>
          <input
            v-model="loginPassword"
            type="password"
            required
            placeholder="••••••••"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 rounded-lg font-bold text-sm bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition disabled:opacity-50"
        >
          {{ loading ? 'Authentifiziere...' : 'Anmelden' }}
        </button>
      </form>

      <!-- Register Form -->
      <form v-else @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Vollständiger Name</label>
          <input
            v-model="regName"
            type="text"
            required
            placeholder="Max Mustermann"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">E-Mail-Adresse</label>
          <input
            v-model="regEmail"
            type="email"
            required
            placeholder="max@domain.ch"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Passwort</label>
          <input
            v-model="regPassword"
            type="password"
            required
            placeholder="Mindestens 8 Zeichen"
            class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2.5 rounded-lg font-bold text-sm bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 transition disabled:opacity-50"
        >
          {{ loading ? 'Konto wird erstellt...' : (invitationToken ? 'Konto erstellen & Unternehmen beitreten' : 'Registrieren & Starten') }}
        </button>
      </form>

      <!-- One-Click Demo Logins -->
      <div class="mt-8 pt-6 border-t border-slate-800">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 text-center">
          ⚡ 1-Klick Schnell-Login (Demo-Rollen)
        </p>
        <div class="grid grid-cols-1 gap-2">
          <button
            @click="quickLogin('admin@taskster.io', 'password123')"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-purple-950/40 hover:bg-purple-900/60 border border-purple-800/60 text-left transition group"
          >
            <div>
              <span class="text-xs font-bold text-purple-300">Site Superadmin</span>
              <p class="text-[10px] text-purple-400">admin@taskster.io (Voller Plattform- & Systemzugriff)</p>
            </div>
            <span class="text-xs text-purple-300 group-hover:translate-x-0.5 transition-transform">→</span>
          </button>

          <button
            @click="quickLogin('marc@swissinfra.ch', 'password123')"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-emerald-950/40 hover:bg-emerald-900/60 border border-emerald-800/60 text-left transition group"
          >
            <div>
              <span class="text-xs font-bold text-emerald-300">Company Admin</span>
              <p class="text-[10px] text-emerald-400">marc@swissinfra.ch (Unternehmens- & Teamverwaltung)</p>
            </div>
            <span class="text-xs text-emerald-300 group-hover:translate-x-0.5 transition-transform">→</span>
          </button>

          <button
            @click="quickLogin('sarah.editor@swissinfra.ch', 'password123')"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-teal-950/40 hover:bg-teal-900/60 border border-teal-800/60 text-left transition group"
          >
            <div>
              <span class="text-xs font-bold text-teal-300">Projekt Editor</span>
              <p class="text-[10px] text-teal-400">sarah.editor@swissinfra.ch (Aufgaben erstellen & bearbeiten)</p>
            </div>
            <span class="text-xs text-teal-300 group-hover:translate-x-0.5 transition-transform">→</span>
          </button>

          <button
            @click="quickLogin('lukas.viewer@subunternehmer.ch', 'password123')"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-amber-950/40 hover:bg-amber-900/60 border border-amber-800/60 text-left transition group"
          >
            <div>
              <span class="text-xs font-bold text-amber-300">Projekt Viewer</span>
              <p class="text-[10px] text-amber-400">lukas.viewer@subunternehmer.ch (Read-Only / Leserechte)</p>
            </div>
            <span class="text-xs text-amber-300 group-hover:translate-x-0.5 transition-transform">→</span>
          </button>

          <button
            @click="quickLogin('peter@muster.ch', 'password123')"
            class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-800/60 hover:bg-slate-800 border border-slate-700 text-left transition group"
          >
            <div>
              <span class="text-xs font-bold text-slate-200">Kunde im Free-Plan</span>
              <p class="text-[10px] text-slate-400">peter@muster.ch (1 Ordner-Limit, max 5 Mitglieder)</p>
            </div>
            <span class="text-xs text-slate-300 group-hover:translate-x-0.5 transition-transform">→</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { setAuth } = useAuth()

const activeTab = ref<'login' | 'register'>((route.query.tab as string) === 'register' || Boolean(route.query.token) ? 'register' : 'login')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const invitationToken = ref((route.query.token as string) || '')
const invitationInfo = ref<any>(null)

const loginEmail = ref('marc@swissinfra.ch')
const loginPassword = ref('password123')

const regName = ref('')
const regEmail = ref('')
const regPassword = ref('')

onMounted(async () => {
  if (invitationToken.value) {
    try {
      const res = await $fetch<any>(`/api/companies/invitations/info?token=${invitationToken.value}`)
      invitationInfo.value = res.invitation
      if (res.invitation?.email) {
        regEmail.value = res.invitation.email
        loginEmail.value = res.invitation.email
      }
      activeTab.value = 'register'
    } catch (err: any) {
      errorMessage.value = err.data?.statusMessage || 'Ungültige oder abgelaufene Einladung'
    }
  }
})

const handleLogin = async () => {
  errorMessage.value = ''
  loading.value = true
  try {
    const res = await $fetch<{ token: string; user: any }>('/api/auth/login', {
      method: 'POST',
      body: {
        email: loginEmail.value,
        password: loginPassword.value,
        invitation_token: invitationToken.value || undefined
      }
    })
    setAuth(res.token, res.user)
    navigateTo('/dashboard')
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || 'Anmeldung fehlgeschlagen'
  } finally {
    loading.value = false
  }
}

const handleRegister = async () => {
  errorMessage.value = ''
  loading.value = true
  try {
    const res = await $fetch<{ token: string; user: any }>('/api/auth/register', {
      method: 'POST',
      body: {
        name: regName.value,
        email: regEmail.value,
        password: regPassword.value,
        invitation_token: invitationToken.value || undefined
      }
    })
    setAuth(res.token, res.user)
    navigateTo('/dashboard')
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || 'Registrierung fehlgeschlagen'
  } finally {
    loading.value = false
  }
}

const quickLogin = async (email: string, pw: string) => {
  loginEmail.value = email
  loginPassword.value = pw
  await handleLogin()
}
</script>
