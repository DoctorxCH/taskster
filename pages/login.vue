<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-12">
      
      <!-- Left Column: Friendly Hero Visual (Nature / Fresh Aesthetic) -->
      <div class="hidden md:flex md:col-span-5 relative bg-gradient-to-br from-cyan-600 to-teal-700 text-white p-8 flex-col justify-between overflow-hidden">
        <img
          src="/wallpapers/mountain-lake.jpg"
          alt="Inspiring Workspace"
          class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-35"
        />
        <div class="relative z-10">
          <img src="/logo.png" alt="Taskster Logo" class="h-9 w-auto brightness-0 invert mb-6" />
          <h3 class="text-2xl font-black leading-tight tracking-tight text-white">
            Einfach. Klar.<br/>Projektmanagement.
          </h3>
          <p class="text-xs text-cyan-100 mt-2 leading-relaxed">
            Organisiere Aufgaben, Abschnitte und Teamarbeit in einer fröhlichen, modernen Umgebung.
          </p>
        </div>

        <div class="relative z-10 pt-6 border-t border-white/20">
          <div class="flex items-center space-x-2 text-xs text-cyan-100 font-medium">
            <Sparkles class="w-4 h-4 text-cyan-200" />
            <span>Zero-Trust Architektur & Schweizer Präzision</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Clean Light Form -->
      <div class="md:col-span-7 p-8 sm:p-10 flex flex-col justify-center">
        <!-- Header -->
        <div class="mb-6">
          <div class="md:hidden flex items-center space-x-2 mb-4">
            <img src="/logo.png" alt="Taskster" class="h-8 w-auto" />
          </div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">
            {{ activeTab === 'login' ? $t('login.willkommen_bei_taskster') : $t('login.konto_erstellen') }}
          </h2>
          <p class="text-xs text-slate-500 mt-1">
            {{ activeTab === 'login' ? $t('login.melde_dich_an') : $t('login.starte_sofort') }}
          </p>
        </div>

        <!-- Tabs -->
        <div class="flex p-1 bg-slate-100 rounded-xl mb-6">
          <button
            @click="activeTab = 'login'"
            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
            :class="activeTab === 'login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
          >
            {{ $t('login.anmelden') }}
          </button>
          <button
            @click="activeTab = 'register'"
            class="flex-1 py-2 text-xs font-bold rounded-lg transition-all"
            :class="activeTab === 'register' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'"
          >
            {{ $t('login.registrieren') }}
          </button>
        </div>

        <!-- Invitation Banner if token is present -->
        <div v-if="invitationInfo" class="mb-5 p-3.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-800 text-xs">
          <div class="font-bold text-sm text-purple-900 mb-0.5">Einladung zu {{ invitationInfo.company_name }}</div>
          <div>Du wurdest eingeladen, diesem Unternehmen beizutreten.</div>
        </div>

        <!-- Error banner -->
        <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
          {{ errorMessage }}
        </div>

        <!-- Success banner -->
        <div v-if="successMessage" class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
          {{ successMessage }}
        </div>

        <!-- Login Form -->
        <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
            <input
              v-model="loginEmail"
              type="email"
              required
              placeholder="name@domain.ch"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Passwort</label>
            <input
              v-model="loginPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="loading"
              class="taskster_button w-full px-6 text-xs h-[42px] rounded-lg"
            >
              {{ loading ? 'Wird angemeldet...' : 'Anmelden' }}
            </button>
          </div>
        </form>

        <!-- Register Form -->
        <form v-else @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Vollständiger Name</label>
            <input
              v-model="regName"
              type="text"
              required
              placeholder="Max Mustermann"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
            <input
              v-model="regEmail"
              type="email"
              required
              placeholder="max@domain.ch"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Passwort</label>
            <input
              v-model="regPassword"
              type="password"
              required
              placeholder="Mindestens 6 Zeichen"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
            />
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="loading"
              class="taskster_button w-full px-6 text-xs h-[42px] rounded-lg"
            >
              {{ loading ? 'Konto wird erstellt...' : 'Registrieren' }}
            </button>
          </div>
        </form>

        <!-- One-Click Demo Logins -->
        <div class="mt-8 pt-6 border-t border-slate-200">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 text-center flex items-center justify-center space-x-1">
            <Zap class="w-3.5 h-3.5 text-amber-500 inline" />
            <span>{{ $t('login.schnell_login_titel') }}</span>
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              @click="quickLogin('admin@kurka.ch', 'password123')"
              class="p-2 rounded-xl bg-purple-50 hover:bg-purple-100 border border-purple-200 text-left transition"
            >
              <div class="text-xs font-bold text-purple-900">Site Superadmin</div>
              <div class="text-[10px] text-purple-700">admin@kurka.ch</div>
            </button>

            <button
              @click="quickLogin('marc@kurka.ch', 'password123')"
              class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-left transition"
            >
              <div class="text-xs font-bold text-emerald-900">Company Admin</div>
              <div class="text-[10px] text-emerald-700">marc@kurka.ch</div>
            </button>

            <button
              @click="quickLogin('sarah.editor@kurka.ch', 'password123')"
              class="p-2 rounded-xl bg-cyan-50 hover:bg-cyan-100 border border-cyan-200 text-left transition"
            >
              <div class="text-xs font-bold text-cyan-900">Projekt Editor</div>
              <div class="text-[10px] text-cyan-700">sarah.editor@kurka.ch</div>
            </button>

            <button
              @click="quickLogin('lukas.viewer@kurka.ch', 'password123')"
              class="p-2 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200 text-left transition"
            >
              <div class="text-xs font-bold text-amber-900">Projekt Viewer</div>
              <div class="text-[10px] text-amber-700">lukas.viewer@kurka.ch</div>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { Sparkles, Zap } from 'lucide-vue-next'

const route = useRoute()
const { setAuth } = useAuth()
const { t, setLocale } = useI18n()

const activeTab = ref<'login' | 'register'>((route.query.tab as string) === 'register' || Boolean(route.query.token) ? 'register' : 'login')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const invitationToken = ref((route.query.token as string) || '')
const invitationInfo = ref<any>(null)

const loginEmail = ref('marc@kurka.ch')
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
    if (res.user?.settings?.language && ['de', 'en', 'sk'].includes(res.user.settings.language)) {
      try {
        await setLocale(res.user.settings.language)
      } catch (e) {
        console.error('[i18n] Failed to set locale on login:', e)
      }
    }
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
    if (res.user?.settings?.language && ['de', 'en', 'sk'].includes(res.user.settings.language)) {
      try {
        await setLocale(res.user.settings.language)
      } catch (e) {
        console.error('[i18n] Failed to set locale on register:', e)
      }
    }
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
