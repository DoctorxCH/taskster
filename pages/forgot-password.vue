<template>
  <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200/80 shadow-xl p-8 sm:p-10">
      
      <div class="flex items-center space-x-2 mb-6">
        <img src="/logo.png" alt="Taskster" class="h-8 w-auto" />
      </div>

      <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Passwort vergessen?</h2>
        <p class="text-xs text-slate-500 mt-1">
          Gib deine E-Mail-Adresse ein. Wir senden dir einen sicheren Link zum Zurücksetzen deines Passworts.
        </p>
      </div>

      <!-- Error banner -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
        {{ errorMessage }}
      </div>

      <!-- Success banner -->
      <div v-if="successMessage" class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium space-y-2">
        <div class="font-bold flex items-center space-x-1.5 text-emerald-900">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>E-Mail gesendet</span>
        </div>
        <p class="leading-relaxed">{{ successMessage }}</p>
      </div>

      <form v-if="!successMessage" @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">E-Mail-Adresse</label>
          <input
            v-model="email"
            type="email"
            required
            placeholder="name@domain.ch"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-cyan-600 focus:ring-2 focus:ring-cyan-600/20 transition"
          />
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="loading"
            class="taskster_button w-full px-6 text-xs h-[42px] rounded-lg"
          >
            {{ loading ? 'Wird gesendet...' : 'Reset-Link anfordern' }}
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

const email = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const handleSubmit = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  loading.value = true

  try {
    const res = await $fetch<{ message: string }>('/api/auth/forgot-password', {
      method: 'POST',
      body: { email: email.value }
    })
    successMessage.value = res.message || 'Falls ein Konto mit dieser E-Mail existiert, haben wir dir einen Link gesendet.'
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || 'Anfrage konnte nicht verarbeitet werden.'
  } finally {
    loading.value = false
  }
}
</script>
