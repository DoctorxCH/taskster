<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb in Liquid Glass Pill -->
    <div class="mb-6">
      <div class="inline-flex items-center space-x-2 text-xs text-slate-700 font-semibold px-4 py-2 rounded-2xl liquid_glass_pill">
        <NuxtLink to="/dashboard" class="hover:text-cyan-700 transition flex items-center space-x-1">
          <span>🏠</span>
          <span>Dashboard</span>
        </NuxtLink>
        <span class="text-slate-400">/</span>
        <span class="text-slate-900 font-bold flex items-center space-x-1">
          <span>⚙️</span>
          <span>Benutzer-Einstellungen</span>
        </span>
      </div>
    </div>

    <!-- Header in Liquid Glass Card -->
    <div class="mb-8 liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
      <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Benutzer-Einstellungen</h1>
      <p class="text-xs text-slate-600 mt-1 font-medium">Verwalte dein Profil, deine Zugangsdaten und deine Arbeitsbereich-Informationen.</p>
    </div>

    <!-- Feedback messages -->
    <div v-if="successMsg" class="mb-6 p-4 rounded-2xl bg-emerald-500/15 border border-emerald-300 text-emerald-950 text-xs font-bold liquid_glass_pill">
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="mb-6 p-4 rounded-2xl bg-rose-500/15 border border-rose-300 text-rose-950 text-xs font-bold liquid_glass_pill">
      {{ errorMsg }}
    </div>

    <div class="space-y-8">
      <!-- Card 1: Profil-Informationen -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-black text-slate-900 mb-1 flex items-center space-x-2">
          <span>👤</span>
          <span>Persönliche Daten</span>
        </h2>
        <p class="text-xs text-slate-500 mb-6">Aktualisiere deinen Anzeigenamen in Taskster.</p>

        <form @submit.prevent="updateProfile" class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Vollständiger Name</label>
            <input
              v-model="profileName"
              type="text"
              required
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
            <input
              :value="user?.email"
              type="email"
              disabled
              class="w-full px-3.5 py-2.5 bg-slate-100/90 border border-slate-300 rounded-xl text-xs text-slate-500 cursor-not-allowed font-medium"
            />
            <p class="text-[11px] text-slate-500 mt-1">Die E-Mail dient als Login-Kennung und kann aus Sicherheitsgründen nicht direkt geändert werden.</p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="savingProfile"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg"
            >
              {{ savingProfile ? 'Speichern...' : 'Profil speichern' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Card 2: Passwort ändern -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-black text-slate-900 mb-1 flex items-center space-x-2">
          <span>🔒</span>
          <span>Passwort & Sicherheit</span>
        </h2>
        <p class="text-xs text-slate-500 mb-6">Ändere dein Anmeldekennwort für erhöhte Sicherheit.</p>

        <form @submit.prevent="changePassword" class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Aktuelles Passwort</label>
            <input
              v-model="currentPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Neues Passwort (min. 8 Zeichen)</label>
            <input
              v-model="newPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Neues Passwort bestätigen</label>
            <input
              v-model="confirmPassword"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 bg-white/90 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600"
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

      <!-- Card 3: Arbeitsbereich & Tarif -->
      <div class="liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl">
        <h2 class="text-base font-black text-slate-900 mb-1 flex items-center space-x-2">
          <span>🏢</span>
          <span>Unternehmen & Tarifplan</span>
        </h2>
        <p class="text-xs text-slate-500 mb-4">Übersicht über deine Unternehmenszugehörigkeit und deinen Plan.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200">
            <div class="text-[11px] text-slate-400 uppercase font-bold">Organisation</div>
            <div class="text-base font-black text-slate-900 mt-1">
              {{ user?.company_name || 'Privater Einzelbenutzer' }}
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
              Rolle: {{ user?.company_role === 'admin' ? 'Company Admin' : (user?.company_role || 'Keine') }}
            </div>
          </div>

          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200">
            <div class="text-[11px] text-slate-400 uppercase font-bold">Aktiver Tarifplan</div>
            <div class="text-base font-black text-cyan-700 mt-1 uppercase">
              {{ user?.company_plan || (user?.is_pro ? 'PRO' : 'FREE') }}
            </div>
            <div class="text-xs text-slate-500 mt-0.5">
              {{ user?.is_pro || user?.company_name ? 'Unbegrenzte Ordner & Team-Funktionen' : 'Max. 1 Projektordner, max. 5 Mitglieder/Projekt' }}
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
