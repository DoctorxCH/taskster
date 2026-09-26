<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200"
  >
    <div class="bg-white border border-slate-200 rounded-xl max-w-xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Modal Header -->
      <div class="p-4 sm:p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-lg bg-cyan-50 border border-cyan-200 text-[#00A3C4] flex items-center justify-center shrink-0">
            <Mic class="w-5 h-5" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $t('voice.sprachassistent_notiz') }}</h3>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-50 text-cyan-800 border border-cyan-200">
                {{ $t('VoiceRecorderModal.whisper_ai') }}
              </span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
              <span>{{ t('voice.sprache') }}</span>
              <span class="font-semibold text-slate-700">{{ activeLanguageLabel }}</span>
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="closeModal"
          class="p-1.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Modal Content Body -->
      <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
        <!-- Error Alert -->
        <div v-if="errorMessage" class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center justify-between">
          <span>{{ errorMessage }}</span>
          <button type="button" @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 font-bold ml-2">✕</button>
        </div>

        <!-- Success Feedback Banner -->
        <div v-if="successMessage" class="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2 animate-in fade-in">
          <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{{ successMessage }}</span>
        </div>

        <!-- ================= STATE 1: IDLE / READY TO RECORD ================= -->
        <div v-if="state === 'idle'" class="text-center py-6 space-y-4">
          <div class="w-20 h-20 mx-auto rounded-full bg-cyan-50 border-2 border-cyan-200 flex items-center justify-center text-[#00A3C4] shadow-sm">
            <Mic class="w-10 h-10" />
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">{{ $t('voice.aufnahme_starten_title') }}</h4>
            <p class="text-xs text-slate-500 mt-1.5 max-w-sm mx-auto leading-relaxed">
              Sprich deine Aufgabe, Notiz oder Statusmeldung ein. Die KI erkennt Aufgabenname, Nummer oder Adresse und schlägt passende Aktionen vor.
            </p>
          </div>

          <!-- Language selector toggle -->
          <div class="flex items-center justify-center gap-2 pt-1">
            <span class="text-[11px] font-semibold text-slate-500">{{ $t('voice.spracheingabe_label') }}</span>
            <select
              v-model="currentLanguage"
              class="px-2.5 py-1 text-xs font-semibold bg-slate-100 border border-slate-300 rounded-md text-slate-700 focus:outline-none focus:border-[#00A3C4]"
            >
              <option value="de">{{ t('voice.deutsch') }}</option>
              <option value="de-CH">Schweizerdeutsch</option>
              <option value="en">English</option>
              <option value="fr">{{ t('voice.franzoesisch') }}</option>
              <option value="it">Italiano</option>
              <option value="auto">Automatisch</option>
            </select>
          </div>

          <div class="pt-2">
            <button
              type="button"
              @click="startRecording"
              class="taskster_button px-6 text-xs h-[42px] rounded-lg inline-flex items-center space-x-2 cursor-pointer shadow-md"
            >
              <Mic class="w-4 h-4" />
              <span>{{ t('voice.aufnahme_starten') }}</span>
            </button>
          </div>
        </div>

        <!-- ================= STATE 2: RECORDING IN PROGRESS ================= -->
        <div v-else-if="state === 'recording'" class="text-center py-6 space-y-4 select-none">
          <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
            <div class="absolute inset-0 rounded-full bg-rose-500/20 animate-ping"></div>
            <div class="w-20 h-20 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-lg relative z-10">
              <Mic class="w-10 h-10 animate-pulse" />
            </div>
          </div>

          <div>
            <div class="font-mono text-2xl font-bold text-slate-900">
              {{ formattedRecordingTime }}
            </div>
            <p class="text-xs text-rose-600 font-bold mt-1 tracking-wide uppercase">
              Aufnahme läuft... ({{ activeLanguageLabel }})
            </p>
          </div>

          <!-- Live Speech Preview -->
          <div v-if="liveTranscript" class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 font-medium italic max-h-24 overflow-y-auto text-left">
            "{{ liveTranscript }}"
          </div>

          <div class="flex items-center justify-center space-x-3 pt-2">
            <button
              type="button"
              @click="cancelRecording"
              class="taskster_button_light px-6 text-xs h-[42px] rounded-lg"
            >
              Abbrechen
            </button>
            <button
              type="button"
              @click="stopRecording"
              class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg inline-flex items-center space-x-2 cursor-pointer shadow-md"
            >
              <Square class="w-4 h-4 fill-current" />
              <span>{{ t('voice.stoppen_analysieren') }}</span>
            </button>
          </div>
        </div>

        <!-- ================= STATE 3: TRANSCRIBING & ANALYZING ================= -->
        <div v-else-if="state === 'transcribing' || state === 'analyzing'" class="text-center py-10 space-y-3">
          <div class="relative w-12 h-12 mx-auto">
            <Loader2 class="w-12 h-12 text-[#00A3C4] animate-spin" />
            <Sparkles class="w-5 h-5 text-amber-500 absolute inset-0 m-auto animate-pulse" />
          </div>
          <h4 class="text-sm font-bold text-slate-900">
            {{ state === 'transcribing' ? 'Transkribiere Sprache...' : 'KI analysiert Aufgaben & Kontext...' }}
          </h4>
          <p class="text-xs text-slate-500 max-w-xs mx-auto">
            {{ state === 'transcribing' 
                ? 'Die Sprachaufnahme wird in Text umgewandelt...' 
                : 'Die KI analysiert Aufgaben, Adressen und Handlungsschritte.' }}
          </p>
        </div>

        <!-- ================= STATE 4: RESULT & SMART ACTIONS ================= -->
        <div v-else-if="state === 'result'" class="space-y-4">
          <!-- AI Context Smart Box -->
          <div v-if="aiAnalysis" class="p-4 rounded-xl bg-cyan-50/70 border border-cyan-200 space-y-3">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2 text-xs font-bold text-cyan-900">
                <Sparkles class="w-4 h-4 text-[#00A3C4] shrink-0" />
                <span>{{ t('voice.ki_erkennung') }}</span>
              </div>
              <span v-if="aiAnalysis.intent" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-white text-cyan-800 border border-cyan-200">
                {{ intentLabel(aiAnalysis.intent) }}
              </span>
            </div>

            <p class="text-xs text-slate-800 font-medium leading-relaxed">
              {{ aiAnalysis.summary }}
            </p>

            <!-- Detected Task/Project Pill -->
            <div v-if="aiAnalysis.matched_task" class="p-2.5 rounded-lg bg-white border border-cyan-100 flex items-center justify-between gap-2 text-xs">
              <div class="min-w-0">
                <span class="text-[10px] font-bold uppercase text-slate-500 block">{{ t('voice.zugeordnete_aufgabe') }}</span>
                <span class="font-bold text-slate-900 truncate block">{{ aiAnalysis.matched_task.title }}</span>
                <span v-if="aiAnalysis.matched_task.project_title" class="text-[11px] text-slate-500 truncate block">
                  Projekt: {{ aiAnalysis.matched_task.project_title }}
                </span>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                {{ aiAnalysis.matched_task.current_status === 'done' ? 'Erledigt' : 'Offen' }}
              </span>
            </div>

            <!-- Detected Checklist Items -->
            <div v-if="aiAnalysis.checklist_items && aiAnalysis.checklist_items.length > 0" class="p-2.5 rounded-lg bg-white border border-cyan-100 text-xs">
              <span class="text-[10px] font-bold uppercase text-slate-500 block mb-1">{{ t('voice.checklisten_punkte') }}</span>
              <ul class="space-y-1 text-slate-800">
                <li v-for="(item, idx) in aiAnalysis.checklist_items" :key="idx" class="flex items-center gap-1.5">
                  <CheckSquare class="w-3.5 h-3.5 text-[#00A3C4] shrink-0" />
                  <span>{{ item }}</span>
                </li>
              </ul>
            </div>

            <!-- Suggested Smart Action Buttons -->
            <div v-if="aiAnalysis.suggested_actions && aiAnalysis.suggested_actions.length > 0" class="space-y-2 pt-1">
              <span class="text-[10px] font-bold uppercase text-cyan-900 tracking-wider block">
                Empfohlene Aktionen (1-Klick):
              </span>
              <div class="grid grid-cols-1 gap-2">
                <button
                  v-for="act in aiAnalysis.suggested_actions"
                  :key="act.id"
                  type="button"
                  :disabled="actionExecuting"
                  @click="executeSmartAction(act)"
                  class="p-2.5 rounded-lg text-left transition border flex items-center justify-between gap-3 cursor-pointer group"
                  :class="act.type === 'complete_task'
                    ? 'bg-rose-50 border-rose-200 hover:bg-rose-100 text-rose-900'
                    : (act.type === 'update_task' || act.type === 'add_checklist'
                      ? 'bg-white border-cyan-300 hover:bg-cyan-50 text-cyan-950 shadow-sm'
                      : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-800')"
                >
                  <div class="min-w-0">
                    <div class="text-xs font-bold flex items-center gap-1.5">
                      <component
                        :is="getActionIcon(act.type)"
                        class="w-4 h-4 shrink-0"
                        :class="act.type === 'complete_task' ? 'text-rose-600' : 'text-[#00A3C4]'"
                      />
                      <span>{{ act.label }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5 leading-tight">
                      {{ act.description }}
                    </p>
                  </div>
                  <ChevronRight class="w-4 h-4 text-slate-400 group-hover:translate-x-0.5 transition-transform shrink-0" />
                </button>
              </div>
            </div>
          </div>

          <!-- Transcribed Text (Editable) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
              <span>{{ t('voice.transkribierter_text') }}</span>
              <span class="text-[10px] text-cyan-700 font-mono font-semibold">{{ t('voice.whisper_v3') }}</span>
            </label>
            <textarea
              v-model="transcribedText"
              rows="3"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#00A3C4] leading-relaxed"
              :placeholder="t('voice.erkannter_text_placeholder')"
            ></textarea>
          </div>

          <!-- Target Project Selection -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Projekt zuordnen (optional)
            </label>
            <select
              v-model="selectedProjectId"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:border-[#00A3C4]"
            >
              <option value="">{{ t('voice.kein_projekt_notiz') }}</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.title }}
              </option>
            </select>
          </div>

          <!-- Standard Save Actions -->
          <div class="space-y-2 pt-2 border-t border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <button
                type="button"
                @click="saveAsJournal"
                :disabled="saving || actionExecuting || !transcribedText.trim()"
                class="taskster_button px-4 text-xs h-[42px] rounded-lg flex items-center justify-center space-x-1.5 cursor-pointer"
              >
                <FileText class="w-4 h-4" />
                <span>{{ saving ? 'Speichern…' : 'Als Notiz/Journal speichern' }}</span>
              </button>

              <button
                type="button"
                @click="saveAsTask"
                :disabled="saving || actionExecuting || !transcribedText.trim()"
                class="taskster_button_light px-4 text-xs h-[42px] rounded-lg flex items-center justify-center space-x-1.5 cursor-pointer"
              >
                <Plus class="w-4 h-4" />
                <span>{{ t('voice.als_aufgabe_anlegen') }}</span>
              </button>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="copyToClipboard"
                class="flex-1 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg transition flex items-center justify-center space-x-1.5 cursor-pointer"
              >
                <Copy class="w-3.5 h-3.5" />
                <span>{{ copied ? 'In Zwischenablage kopiert!' : 'In Zwischenablage kopieren' }}</span>
              </button>

              <button
                type="button"
                @click="reanalyzeWithAi"
                :disabled="actionExecuting || !transcribedText.trim()"
                :title="t('voice.erneut_analysieren_title')"
                class="px-3 py-2 text-xs font-semibold text-[#00A3C4] hover:bg-cyan-50 border border-cyan-200 rounded-lg transition flex items-center justify-center gap-1 cursor-pointer"
              >
                <Sparkles class="w-3.5 h-3.5" />
                <span>{{ t('voice.neu_analysieren') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import {
  Mic, Square, Loader2, X, FileText, Plus, Copy, Sparkles,
  CheckCircle2, CheckSquare, Edit3, Check, ListPlus, ChevronRight
} from 'lucide-vue-next'

const { t } = useI18n()

const props = defineProps<{
  modelValue: boolean
  defaultProjectId?: string
  projects?: any[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
  (e: 'saved', payload: any): void
}>()

const { token, user } = useAuth()
const authHeaders = () => ({
  'Authorization': `Bearer ${token.value || ''}`
})

type StateMode = 'idle' | 'recording' | 'transcribing' | 'analyzing' | 'result'
const state = ref<StateMode>('idle')
const errorMessage = ref('')
const successMessage = ref('')
const transcribedText = ref('')
const selectedProjectId = ref('')
const currentLanguage = ref('de')
const saving = ref(false)
const actionExecuting = ref(false)
const copied = ref(false)
const aiAnalysis = ref<any>(null)

// MediaRecorder & SpeechRecognition setup
let mediaRecorder: MediaRecorder | null = null
let speechRecognition: any = null
let audioChunks: Blob[] = []
let recordingTimer: any = null
const recordingSeconds = ref(0)
const liveTranscript = ref('')

const languageLabels: Record<string, string> = {
  'de': 'Deutsch',
  'de-CH': 'Schweizerdeutsch',
  'en': 'English',
  'fr': 'Français',
  'it': 'Italiano',
  'auto': 'Automatisch erkennen'
}

const activeLanguageLabel = computed(() => {
  return languageLabels[currentLanguage.value] || 'Deutsch'
})

const formattedRecordingTime = computed(() => {
  const mins = Math.floor(recordingSeconds.value / 60)
  const secs = recordingSeconds.value % 60
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    state.value = 'idle'
    errorMessage.value = ''
    successMessage.value = ''
    transcribedText.value = ''
    liveTranscript.value = ''
    aiAnalysis.value = null
    selectedProjectId.value = props.defaultProjectId || ''
    currentLanguage.value = user.value?.settings?.whisper_language || user.value?.settings?.language || 'de'
    copied.value = false
  } else {
    stopMediaStream()
  }
})

const closeModal = () => {
  stopMediaStream()
  emit('update:modelValue', false)
}

const stopMediaStream = () => {
  if (recordingTimer) {
    clearInterval(recordingTimer)
    recordingTimer = null
  }
  if (speechRecognition) {
    try {
      speechRecognition.stop()
    } catch (_) {}
    speechRecognition = null
  }
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    try {
      mediaRecorder.stop()
    } catch (_) {}
  }
  if (mediaRecorder && mediaRecorder.stream) {
    mediaRecorder.stream.getTracks().forEach(track => track.stop())
  }
  mediaRecorder = null
}

const startRecording = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  liveTranscript.value = ''
  audioChunks = []
  recordingSeconds.value = 0

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
        channelCount: 1,
        sampleRate: 44100
      }
    })

    const mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
      ? 'audio/webm;codecs=opus'
      : (MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : 'audio/webm')

    mediaRecorder = new MediaRecorder(stream, {
      mimeType,
      audioBitsPerSecond: 128000
    })

    mediaRecorder.ondataavailable = (event) => {
      if (event.data && event.data.size > 0) {
        audioChunks.push(event.data)
      }
    }

    mediaRecorder.onstop = async () => {
      if (state.value === 'recording') {
        state.value = 'transcribing'
        await processAudioForTranscription()
      }
    }

    // Start native Web Speech Recognition according to language
    if (typeof window !== 'undefined' && ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window)) {
      try {
        const SpeechRecognitionApi = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
        speechRecognition = new SpeechRecognitionApi()
        speechRecognition.continuous = true
        speechRecognition.interimResults = true

        const langMap: Record<string, string> = {
          'de': 'de-DE',
          'de-CH': 'de-CH',
          'en': 'en-US',
          'fr': 'fr-FR',
          'it': 'it-IT',
          'auto': 'de-DE'
        }
        speechRecognition.lang = langMap[currentLanguage.value] || 'de-DE'

        speechRecognition.onresult = (event: any) => {
          let text = ''
          for (let i = 0; i < event.results.length; i++) {
            text += event.results[i][0].transcript + ' '
          }
          if (text.trim()) {
            liveTranscript.value = text.trim()
          }
        }
        speechRecognition.start()
      } catch (_) {}
    }

    mediaRecorder.start(250)
    state.value = 'recording'
    recordingTimer = setInterval(() => {
      recordingSeconds.value++
    }, 1000)
  } catch (err: any) {
    errorMessage.value = t('voice.mikrofon_verweigert')
  }
}

const stopRecording = () => {
  if (recordingTimer) {
    clearInterval(recordingTimer)
    recordingTimer = null
  }
  if (speechRecognition) {
    try {
      speechRecognition.stop()
    } catch (_) {}
  }
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
  }
}

const cancelRecording = () => {
  state.value = 'idle'
  stopMediaStream()
}

const processAudioForTranscription = async () => {
  try {
    const mimeType = mediaRecorder?.mimeType || 'audio/webm'
    const audioBlob = new Blob(audioChunks, { type: mimeType })
    stopMediaStream()

    if (audioBlob.size === 0 && !liveTranscript.value) {
      errorMessage.value = t('voice.keine_audiodaten')
      state.value = 'idle'
      return
    }

    // Convert Blob to base64
    const reader = new FileReader()
    const base64Promise = new Promise<string>((resolve, reject) => {
      reader.onloadend = () => {
        const res = reader.result as string
        resolve(res)
      }
      reader.onerror = reject
    })
    reader.readAsDataURL(audioBlob)
    const base64Data = await base64Promise

    let resultText = ''
    try {
      const response = await $fetch<{ success: boolean; text: string; model?: string }>('/api/ai/transcribe', {
        method: 'POST',
        headers: authHeaders(),
        body: {
          audio: base64Data,
          mimeType,
          model: 'openai/whisper-large-v3-turbo',
          language: currentLanguage.value
        }
      })
      if (response.success && response.text) {
        resultText = response.text
      }
    } catch (e) {
      console.warn('[Whisper API] Upstream error, checking live transcript fallback:', e)
    }

    const finalText = (resultText || liveTranscript.value || '').trim()
    if (finalText) {
      transcribedText.value = finalText
      state.value = 'analyzing'
      await analyzeVoiceIntent(finalText)
    } else {
      errorMessage.value = t('voice.keine_sprache')
      state.value = 'idle'
    }
  } catch (err: any) {
    const fallback = liveTranscript.value.trim()
    if (fallback) {
      transcribedText.value = fallback
      state.value = 'analyzing'
      await analyzeVoiceIntent(fallback)
    } else {
      errorMessage.value = err.data?.statusMessage || err.message || 'Fehler bei der Transkription'
      state.value = 'idle'
    }
  }
}

const analyzeVoiceIntent = async (text: string) => {
  try {
    const res = await $fetch<any>('/api/ai/analyze-voice', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        text,
        current_project_id: selectedProjectId.value || null
      }
    })

    if (res.success && res.analysis) {
      aiAnalysis.value = res.analysis
      if (res.analysis.matched_project?.id && !selectedProjectId.value) {
        selectedProjectId.value = res.analysis.matched_project.id
      }
    }
  } catch (err) {
    console.warn('[AI Intent Analysis] Skipped or failed:', err)
  } finally {
    state.value = 'result'
  }
}

const reanalyzeWithAi = async () => {
  if (!transcribedText.value.trim()) return
  state.value = 'analyzing'
  await analyzeVoiceIntent(transcribedText.value)
}

const intentLabel = (intent: string) => {
  switch (intent) {
    case 'complete_task': return 'Aufgabe abschliessen'
    case 'update_task': return t('voice.aufgabe_ergaenzen')
    case 'add_checklist': return 'Checkliste'
    case 'create_task': return 'Neue Aufgabe'
    default: return 'Notiz / Journal'
  }
}

const getActionIcon = (type: string) => {
  switch (type) {
    case 'complete_task': return Check
    case 'update_task': return Edit3
    case 'add_checklist': return ListPlus
    case 'create_task': return Plus
    default: return FileText
  }
}

// Execute 1-Click AI Smart Action
const executeSmartAction = async (action: any) => {
  actionExecuting.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const text = transcribedText.value.trim()

    if (action.type === 'complete_task') {
      const taskId = action.task_id || aiAnalysis.value?.matched_task?.id
      if (!taskId) throw new Error('Aufgaben-ID nicht gefunden')

      // Fetch current task description
      const taskRes = await $fetch<any>(`/api/tasks/${taskId}`, { headers: authHeaders() })
      const currentDesc = taskRes.task?.description || ''
      const appendNote = `\n\n[Erledigt via Sprachassistent ${new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })}]: ${text}`

      await $fetch(`/api/tasks/${taskId}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: {
          status: 'done',
          description: currentDesc ? currentDesc + appendNote : text
        }
      })

      successMessage.value = `Aufgabe '${aiAnalysis.value?.matched_task?.title || 'Aufgabe'}' als erledigt markiert!`
      emit('saved', { type: 'complete_task', task_id: taskId, text })
    }
    else if (action.type === 'update_task') {
      const taskId = action.task_id || aiAnalysis.value?.matched_task?.id
      if (!taskId) throw new Error('Aufgaben-ID nicht gefunden')

      const taskRes = await $fetch<any>(`/api/tasks/${taskId}`, { headers: authHeaders() })
      const currentDesc = taskRes.task?.description || ''
      const appendNote = `\n\n[Sprachnotiz ${new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })}]:\n${text}`

      await $fetch(`/api/tasks/${taskId}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: {
          description: (currentDesc + appendNote).trim()
        }
      })

      successMessage.value = `Notiz an Aufgabe '${aiAnalysis.value?.matched_task?.title || 'Aufgabe'}' angehängt!`
      emit('saved', { type: 'update_task', task_id: taskId, text })
    }
    else if (action.type === 'add_checklist') {
      const taskId = action.task_id || aiAnalysis.value?.matched_task?.id
      if (!taskId) throw new Error('Aufgaben-ID nicht gefunden')

      const taskRes = await $fetch<any>(`/api/tasks/${taskId}`, { headers: authHeaders() })
      let checklist = taskRes.task?.checklist || []
      if (typeof checklist === 'string') {
        try { checklist = JSON.parse(checklist) } catch (_) { checklist = [] }
      }

      const newItems = (aiAnalysis.value?.checklist_items || []).map((t: string) => ({
        id: 'chk_' + Math.random().toString(36).substring(2, 9),
        title: t,
        completed: false
      }))

      const updatedChecklist = [...checklist, ...newItems]

      await $fetch(`/api/tasks/${taskId}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: {
          checklist: updatedChecklist
        }
      })

      successMessage.value = `${newItems.length} Checklisten-Punkte hinzugefügt!`
      emit('saved', { type: 'add_checklist', task_id: taskId, items: newItems })
    }
    else if (action.type === 'create_task') {
      const targetProj = action.project_id || selectedProjectId.value || aiAnalysis.value?.matched_project?.id
      await createTaskInternal(targetProj)
      successMessage.value = 'Neue Aufgabe erfolgreich erstellt!'
    }
    else {
      // create_journal
      const targetProj = action.project_id || selectedProjectId.value || aiAnalysis.value?.matched_project?.id || null
      await saveJournalInternal(targetProj)
      successMessage.value = 'Sprachnotiz im Journal gespeichert!'
    }

    setTimeout(() => {
      closeModal()
    }, 1200)
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || err.message || t('voice.fehler_ausfuehren')
  } finally {
    actionExecuting.value = false
  }
}

const saveJournalInternal = async (projectId: string | null) => {
  const title = 'Sprachnotiz (' + new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' }) + ')'
  await $fetch('/api/journals', {
    method: 'POST',
    headers: authHeaders(),
    body: {
      project_id: projectId || null,
      task_id: aiAnalysis.value?.matched_task?.id || null,
      entry_type: 'voice',
      title,
      content: transcribedText.value
    }
  })
  emit('saved', { type: 'journal', text: transcribedText.value, project_id: projectId })
}

const createTaskInternal = async (projectId?: string) => {
  let projId = projectId || selectedProjectId.value

  // If no project selected, fallback to user's first available project
  if (!projId && props.projects && props.projects.length > 0) {
    projId = props.projects[0].id
  }

  if (!projId) {
    const defaultProjRes = await $fetch<any>('/api/folders', { headers: authHeaders() })
    const allProjs = (defaultProjRes.folders || []).flatMap((f: any) => f.projects || [])
    if (allProjs.length > 0) projId = allProjs[0].id
  }

  if (!projId) {
    throw new Error('Bitte erstelle zuerst ein Projekt, um Aufgaben anzulegen.')
  }

  const projData = await $fetch<any>(`/api/projects/${projId}`, {
    headers: authHeaders()
  })

  const listId = projData.lists?.[0]?.id
  if (!listId) {
      throw new Error(t('voice.projekt_keine_abschnitte'))
  }

  const taskTitle = aiAnalysis.value?.extracted_task_title || (
    transcribedText.value.length > 50 
      ? transcribedText.value.slice(0, 47) + '...' 
      : transcribedText.value
  )

  const checklistObj = (aiAnalysis.value?.checklist_items || []).map((t: string) => ({
    id: 'chk_' + Math.random().toString(36).substring(2, 9),
    title: t,
    completed: false
  }))

  await $fetch('/api/tasks', {
    method: 'POST',
    headers: authHeaders(),
    body: {
      project_id: projId,
      list_id: listId,
      title: taskTitle,
      description: t('voice.praefix_assistent') + transcribedText.value,
      checklist: checklistObj,
      status: 'todo'
    }
  })

  emit('saved', { type: 'task', text: transcribedText.value, project_id: projId })
}

const saveAsJournal = async () => {
  if (!transcribedText.value.trim()) return
  saving.value = true
  errorMessage.value = ''

  try {
    await saveJournalInternal(selectedProjectId.value || null)
    successMessage.value = 'Notiz erfolgreich gespeichert!'
    setTimeout(() => { closeModal() }, 800)
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || err.message || 'Fehler beim Speichern der Sprachnotiz'
  } finally {
    saving.value = false
  }
}

const saveAsTask = async () => {
  if (!transcribedText.value.trim()) return
  saving.value = true
  errorMessage.value = ''

  try {
    await createTaskInternal(selectedProjectId.value)
    successMessage.value = 'Aufgabe erfolgreich angelegt!'
    setTimeout(() => { closeModal() }, 800)
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || err.message || 'Fehler beim Erstellen der Aufgabe'
  } finally {
    saving.value = false
  }
}

const copyToClipboard = () => {
  if (transcribedText.value) {
    navigator.clipboard.writeText(transcribedText.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 3000)
  }
}

onUnmounted(() => {
  stopMediaStream()
})
</script>
