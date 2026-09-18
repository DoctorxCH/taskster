<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-400 mb-6">
      <NuxtLink to="/dashboard" class="hover:text-emerald-400 transition">Dashboard</NuxtLink>
      <span>/</span>
      <span class="text-slate-200 font-medium">Benutzer-Einstellungen</span>
    </div>

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Benutzer-Einstellungen</h1>
      <p class="text-xs text-slate-400 mt-1">Verwalte dein Profil, deine Zugangsdaten und deine Arbeitsbereich-Informationen.</p>
    </div>

    <!-- Feedback messages -->
    <div v-if="successMsg" class="mb-6 p-4 rounded-xl bg-emerald-950/60 border border-emerald-800 text-emerald-300 text-xs">
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
      {{ errorMsg }}
    </div>

    <div class="space-y-8">
      <!-- Card 1: Profil-Informationen -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <h2 class="text-base font-bold text-white mb-1 flex items-center space-x-2">
          <span>👤</span>
          <span>Persönliche Daten</span>
        </h2>
        <p class="text-xs text-slate-400 mb-6">Aktualisiere deinen Anzeigenamen in Taskster.</p>

        <form @submit.prevent="updateProfile" class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Vollständiger Name</label>
            <input
              v-model="profileName"
              type="text"
              required
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">E-Mail-Adresse</label>
            <input
              :value="user?.email"
              type="email"
              disabled
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-lg text-sm text-slate-500 cursor-not-allowed"
            />
            <p class="text-[11px] text-slate-500 mt-1">Die E-Mail dient als Login-Kennung und kann aus Sicherheitsgründen nicht direkt geändert werden.</p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="savingProfile"
              class="px-5 py-2 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-slate-950 transition disabled:opacity-50"
            >
              {{ savingProfile ? 'Speichern...' : 'Profil speichern' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Card 2: Passwort ändern -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <h2 class="text-base font-bold text-white mb-1 flex items-center space-x-2">
          <span>🔒</span>
          <span>Passwort & Sicherheit</span>
        </h2>
        <p class="text-xs text-slate-400 mb-6">Ändere dein Anmeldekennwort für erhöhte Sicherheit.</p>

        <form @submit.prevent="changePassword" class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Aktuelles Passwort</label>
            <input
              v-model="currentPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Neues Passwort (min. 8 Zeichen)</label>
            <input
              v-model="newPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Neues Passwort bestätigen</label>
            <input
              v-model="confirmPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-lg text-sm text-slate-100 placeholder-slate-600 focus:outline-none focus:border-emerald-500"
            />
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="savingPassword"
              class="px-5 py-2 rounded-lg text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition disabled:opacity-50"
            >
              {{ savingPassword ? 'Wird geändert...' : 'Passwort aktualisieren' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Card 3: Arbeitsbereich & Tarif -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <h2 class="text-base font-bold text-white mb-1 flex items-center space-x-2">
          <span>🏢</span>
          <span>Unternehmen & Mitgliedschaft</span>
        </h2>
        <p class="text-xs text-slate-400 mb-4">Übersicht über deine Unternehmenszugehörigkeit und deinen Tarifplan.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-4 rounded-xl bg-slate-950 border border-slate-800">
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Organisation</div>
            <div class="text-sm font-bold text-white mt-1">
              {{ user?.company_name || 'Privater Einzelbenutzer' }}
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
              Rolle: {{ user?.company_role === 'admin' ? 'Company Admin' : (user?.company_role || 'Keine') }}
            </div>
          </div>

          <div class="p-4 rounded-xl bg-slate-950 border border-slate-800">
            <div class="text-[11px] text-slate-400 uppercase font-semibold">Aktiver Tarifplan</div>
            <div class="text-sm font-bold text-emerald-400 mt-1 uppercase">
              {{ user?.company_plan || (user?.is_pro ? 'PRO' : 'FREE') }}
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
              {{ user?.is_pro || user?.company_name ? 'Unbegrenzte Ordner & erweiterte Teamfunktionen' : 'Max. 1 Projektordner, max. 5 Mitglieder/Projekt' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { user, authHeaders } = useAuth()

const profileName = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const savingProfile = ref(false)
const savingPassword = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

onMounted(async () => {
  if (!user.value) {
    const { initAuth } = useAuth()
    await initAuth()
  }
  if (!user.value) {
    navigateTo('/login')
    return
  }
  profileName.value = user.value.name
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
        name: profileName.value
      }
    })
    if (user.value) {
      user.value.name = res.user.name
    }
    successMsg.value = 'Profil erfolgreich aktualisiert!'
  } catch (err: any) {
    errorMsg.value = err.data?.statusMessage || 'Fehler beim Speichern des Profils'
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
</script>
