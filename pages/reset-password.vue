<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200/80 shadow-xl p-8 sm:p-10">
      
      <div class="flex items-center space-x-2 mb-6">
        <img src="/logo.png" alt="Taskster" class="h-8 w-auto" />
      </div>

      <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Neues Passwort festlegen</h2>
        <p v-if="userName" class="text-xs text-slate-500 mt-1">
          Hallo {{ userName }}! Gib hier dein neues Passwort ein.
        </p>
      </div>

      <!-- Initial token verification loading -->
      <div v-if="verifyingToken" class="p-6 text-center text-xs font-bold text-slate-500 space-y-2">
        <div class="w-6 h-6 border-2 border-[#00A3C4] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p>Sicherheits-Link wird überprüft...</p>
      </div>

      <!-- Error banner -->
      <div v-else-if="errorMessage" class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium space-y-3">
        <div class="font-bold text-sm text-rose-900">Link ungültig oder abgelaufen</div>
        <p>{{ errorMessage }}</p>
        <div>
          <NuxtLink to="/forgot-password" class="taskster_button inline-block px-4 py-2 text-xs rounded-lg">
            Neuen Link anfordern
          </NuxtLink>
        </div>
      </div>

      <!-- Success banner -->
      <div v-else-if="successMessage" class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium space-y-3">
        <div class="font-bold text-sm text-emerald-900 flex items-center space-x-1.5">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>Passwort geändert!</span>
        </div>
        <p>{{ successMessage }}</p>
        <div>
          <NuxtLink to="/login" class="taskster_button inline-block px-6 text-xs h-[42px] rounded-lg">
            Jetzt anmelden
          </NuxtLink>
        </div>
      </div>

      <!-- Reset Form -->
      <form v-else @submit.prevent="handleReset" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Neues Passwort</label>
          <input
            v-model="password"
            type="password"
            required
            placeholder="Mindestens 8 Zeichen"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Passwort wiederholen</label>
          <input
            v-model="passwordConfirm"
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
            {{ loading ? 'Wird gespeichert...' : 'Passwort zurücksetzen' }}
          </button>
        </div>
      </form>

      <div class="mt-6 pt-6 border-t border-slate-100 text-center">
        <NuxtLink to="/login" class="text-xs font-bold text-[#00A3C4] hover:underline inline-flex items-center space-x-1">
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Zurück zur Anmeldung</span>
        </NuxtLink>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft, CheckCircle2 } from 'lucide-vue-next'

const route = useRoute()
const token = ref((route.query.token as string) || '')

const verifyingToken = ref(true)
const userName = ref('')
const password = ref('')
const passwordConfirm = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

onMounted(async () => {
  if (!token.value) {
    verifyingToken.value = false
    errorMessage.value = 'Kein Sicherheitstoken angegeben.'
    return
  }

  try {
    const res = await $fetch<{ valid: boolean; email: string; user_name: string }>(`/api/auth/verify-reset-token?token=${encodeURIComponent(token.value)}`)
    userName.value = res.user_name || ''
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || 'Dieser Link ist ungültig oder abgelaufen.'
  } finally {
    verifyingToken.value = false
  }
})

const handleReset = async () => {
  errorMessage.value = ''
  if (password.value.length < 8) {
    errorMessage.value = 'Das Passwort muss mindestens 8 Zeichen lang sein.'
    return
  }

  if (password.value !== passwordConfirm.value) {
    errorMessage.value = 'Die Passwörter stimmen nicht überein.'
    return
  }

  loading.value = true

  try {
    const res = await $fetch<{ message: string }>('/api/auth/reset-password', {
      method: 'POST',
      body: {
        token: token.value,
        password: password.value
      }
    })
    successMessage.value = res.message || 'Dein Passwort wurde erfolgreich aktualisiert.'
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || 'Passwort konnte nicht zurückgesetzt werden.'
  } finally {
    loading.value = false
  }
}
</script>
