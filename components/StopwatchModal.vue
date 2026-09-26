<template>
  <Teleport to="body">
    <div
      v-if="showStopModal"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/40"
      @click.self="closeStopModal"
    >
      <div class="bg-white border border-slate-200 rounded-lg shadow-md max-w-md w-full flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 h-14 border-b border-slate-200">
          <div class="flex items-center gap-2">
            <Clock class="w-5 h-5 text-[#0891B2]" />
            <h3 class="text-base font-semibold text-slate-900">{{ $t('stopwatch.zeiterfassung_abschliessen') }}</h3>
          </div>
          <button
            type="button"
            @click="closeStopModal"
            class="h-8 w-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
            :title="$t('stopwatch.weiterlaufen_lassen')"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-5 space-y-4 overflow-y-auto">
          <!-- Time & Cost Display Card -->
          <div class="p-4 rounded-md bg-slate-50 border border-slate-200 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $t('stopwatch.gemessene_zeit') }}</span>
              <span class="text-2xl font-bold text-[#0891B2] tabular-nums">
                {{ formatSeconds(state.elapsedSeconds) }}
              </span>
            </div>

            <div class="flex items-center justify-between text-sm pt-2 border-t border-slate-200">
              <span class="text-slate-500">{{ $t('stopwatch.rapportiert_auf') }}</span>
              <div class="text-right font-medium text-slate-800 truncate max-w-[200px]">
                <span v-if="state.taskTitle" class="text-cyan-800">{{ state.taskTitle }}</span>
                <span v-else class="text-slate-800">{{ state.projectTitle || 'Projekt' }}</span>
              </div>
            </div>

            <div v-if="user?.hourly_rate" class="flex items-center justify-between text-sm pt-2 border-t border-slate-200">
              <span class="text-slate-500">{{ $t('stopwatch.geschaetzte_kosten') }}</span>
              <span class="font-semibold text-emerald-700 tabular-nums">
                {{ calculatedCost.toFixed(2) }} {{ state.projectCurrency || 'CHF' }}
                <span class="text-xs text-slate-400 font-normal">({{ user.hourly_rate }} / {{ $t('stopwatch.std') }})</span>
              </span>
            </div>
          </div>

          <!-- Note Input -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
              {{ $t('stopwatch.taetigkeit') }}
            </label>
            <textarea
              v-model="noteInput"
              rows="3"
              :placeholder="$t('stopwatch.taetigkeit_placeholder')"
              class="w-full px-3 py-2 text-sm rounded-md bg-white border border-slate-300 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15 transition-shadow"
            ></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-5 h-16 border-t border-slate-200 bg-slate-50/50">
          <button
            type="button"
            @click="onDiscard"
            class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline"
          >
            {{ $t('stopwatch.verwerfen') }}
          </button>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="closeStopModal"
              class="taskster_button_light"
            >
              {{ $t('stopwatch.weiterlaufen') }}
            </button>
            <button
              type="button"
              @click="onSave"
              :disabled="isSaving"
              class="taskster_button"
            >
              {{ isSaving ? $t('common.speichern') + '...' : $t('stopwatch.zeit_buchen') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { Clock, X } from 'lucide-vue-next'
const { t } = useI18n()

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
  if (confirm(t('stopwatch.verwerfen_bestaetigung'))) {
    discardTimer()
    noteInput.value = ''
  }
}
</script>

