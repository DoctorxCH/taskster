<template>
  <Teleport to="body">
    <div
      v-if="showStopModal"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md animate-in fade-in duration-150"
      @click.self="closeStopModal"
    >
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center space-x-2.5">
          <span class="w-10 h-10 rounded-2xl bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center text-lg">
            ⏱️
          </span>
          <div>
            <h3 class="text-base font-black text-slate-900">Zeiterfassung abschließen</h3>
            <p class="text-xs text-slate-500 mt-0.5">Gemessene Live-Zeit buchen</p>
          </div>
        </div>
        <button
          type="button"
          @click="closeStopModal"
          class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg"
          title="Weiterlaufen lassen"
        >
          ✕
        </button>
      </div>

      <!-- Time & Cost Display -->
      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500">Gemessene Zeit</span>
          <span class="text-xl font-black text-[#00A3C4] font-mono tracking-tight">
            {{ formatSeconds(state.elapsedSeconds) }}
          </span>
        </div>

        <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-200/60">
          <span class="text-slate-500">Rapportiert auf</span>
          <div class="text-right font-bold text-slate-800 truncate max-w-[220px]">
            <span v-if="state.taskTitle" class="text-cyan-800">📋 {{ state.taskTitle }}</span>
            <span v-else class="text-purple-800">🏢 {{ state.projectTitle || 'Projekt' }}</span>
          </div>
        </div>

        <div v-if="user?.hourly_rate" class="flex items-center justify-between text-xs pt-2 border-t border-slate-200/60">
          <span class="text-slate-500">Geschätzte Kosten</span>
          <span class="font-bold text-emerald-700">
            {{ calculatedCost.toFixed(2) }} {{ state.projectCurrency || 'CHF' }}
            <span class="text-[10px] text-slate-400 font-normal">({{ user.hourly_rate }} / Std.)</span>
          </span>
        </div>
      </div>

      <!-- Note / Description -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Tätigkeit / Beschreibung</label>
        <textarea
          v-model="noteInput"
          rows="2.5"
          placeholder="Was wurde während dieser Zeit erledigt?"
          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-cyan-600"
        ></textarea>
      </div>

      <!-- Footer Buttons -->
      <div class="flex items-center justify-between pt-2 border-t border-slate-100">
        <button
          type="button"
          @click="onDiscard"
          class="text-rose-600 hover:text-rose-800 text-xs font-bold hover:underline"
        >
          Verwerfen
        </button>

        <div class="flex items-center space-x-2">
          <button
            type="button"
            @click="closeStopModal"
            class="taskster_button_light px-4 text-xs h-[38px] rounded-lg"
          >
            Weiterlaufen
          </button>
          <button
            type="button"
            @click="onSave"
            :disabled="isSaving"
            class="taskster_button px-5 text-xs h-[38px] rounded-lg flex items-center space-x-1.5"
          >
            <span>{{ isSaving ? 'Speichern...' : 'Zeit buchen' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</Teleport>
</template>

<script setup lang="ts">
const { state, showStopModal, isSaving, closeStopModal, discardTimer, saveTimerEntry, formatSeconds } = useStopwatch()
const { user, authHeaders } = useAuth()

const noteInput = ref('')

watch(showStopModal, (open) => {
  if (open) {
    noteInput.value = state.value.description || ''
  }
})

const calculatedCost = computed(() => {
  const rate = Number(user.value?.hourly_rate || 0)
  const hours = (state.value.elapsedSeconds || 0) / 3600
  return hours * rate
})

const onSave = async () => {
  await saveTimerEntry(authHeaders, noteInput.value)
  noteInput.value = ''
}

const onDiscard = () => {
  if (confirm('Möchtest du diese Zeitmessung wirklich verwerfen?')) {
    discardTimer()
    noteInput.value = ''
  }
}
</script>
