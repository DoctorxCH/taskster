<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200"
  >
    <div class="bg-white border border-slate-200 rounded-lg max-w-lg w-full shadow-2xl overflow-hidden flex flex-col">
      <!-- Modal Header -->
      <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
        <div class="flex items-center space-x-2.5">
          <div class="w-9 h-9 rounded-lg bg-cyan-50 border border-cyan-200 text-[#0891B2] flex items-center justify-center shrink-0">
            <Mic class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 tracking-tight">Neue Sprachnotiz</h3>
            <p class="text-[11px] text-slate-500 font-medium">
              Spracheingabe via AI <span class="font-mono text-[#0891B2] font-semibold">openai/whisper-large-v3-turbo</span>
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="closeModal"
          class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Modal Content Body -->
      <div class="p-6 space-y-5">
        <!-- Error Alert -->
        <div v-if="errorMessage" class="p-3.5 rounded-md bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center justify-between">
          <span>{{ errorMessage }}</span>
          <button type="button" @click="errorMessage = ''" class="text-rose-500 hover:text-rose-700 font-bold ml-2">✕</button>
        </div>

        <!-- STATE 1: IDLE / READY TO RECORD -->
        <div v-if="state === 'idle'" class="text-center py-6 space-y-4">
          <div class="w-20 h-20 mx-auto rounded-full bg-cyan-50 border-2 border-cyan-200 flex items-center justify-center text-[#0891B2] shadow-sm">
            <Mic class="w-10 h-10" />
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900">Sprachaufnahme starten</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto leading-relaxed">
              Drücke auf den Button und sprich deine Projekt-Notiz, Aufgabenbeschreibung oder Beobachtung ein.
            </p>
          </div>

          <div class="pt-2">
            <button
              type="button"
              @click="startRecording"
              class="taskster_button px-6 text-xs h-11 rounded-md inline-flex items-center space-x-2 cursor-pointer shadow-md"
            >
              <Mic class="w-4 h-4" />
              <span>Aufnahme starten</span>
            </button>
          </div>
        </div>

        <!-- STATE 2: RECORDING IN PROGRESS -->
        <div v-else-if="state === 'recording'" class="text-center py-6 space-y-4 select-none">
          <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
            <!-- Pulsating animation ring -->
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
              Aufnahme läuft... (Sprechen Sie jetzt)
            </p>
          </div>

          <div class="flex items-center justify-center space-x-3 pt-2">
            <button
              type="button"
              @click="cancelRecording"
              class="taskster_button_light px-4 text-xs h-9 rounded-md"
            >
              Abbrechen
            </button>
            <button
              type="button"
              @click="stopRecording"
              class="taskster_button_accent px-6 text-xs h-9 rounded-md inline-flex items-center space-x-1.5 cursor-pointer shadow-md"
            >
              <Square class="w-3.5 h-3.5 fill-current" />
              <span>Aufnahme stoppen & verarbeiten</span>
            </button>
          </div>
        </div>

        <!-- STATE 3: TRANSCRIBING VIA AI -->
        <div v-else-if="state === 'transcribing'" class="text-center py-10 space-y-3">
          <Loader2 class="w-10 h-10 text-[#0891B2] animate-spin mx-auto" />
          <h4 class="text-sm font-bold text-slate-900">Transkribiere Audionachricht...</h4>
          <p class="text-xs text-slate-500">
            OpenRouter AI (<span class="font-mono text-[#0891B2]">openai/whisper-large-v3-turbo</span>) wandelt Sprache in Text um.
          </p>
        </div>

        <!-- STATE 4: RESULT / EDIT & SAVE -->
        <div v-else-if="state === 'result'" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
              <span>Transkribierter Text (bearbeitbar):</span>
              <span class="text-[10px] text-cyan-700 font-mono font-semibold">Whisper v3 Turbo</span>
            </label>
            <textarea
              v-model="transcribedText"
              rows="4"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-md text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-[#0891B2] leading-relaxed"
              placeholder="Erkannter Text..."
            ></textarea>
          </div>

          <!-- Target Project Selection -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Projekt zuordnen
            </label>
            <select
              v-model="selectedProjectId"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-md text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:border-[#0891B2]"
            >
              <option value="">Kein Projekt (Nur Zwischenablage / Notiz)</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.title }}
              </option>
            </select>
          </div>

          <!-- Save Action Buttons -->
          <div class="space-y-2 pt-2 border-t border-slate-200">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <button
                type="button"
                @click="saveAsJournal"
                :disabled="saving || !transcribedText.trim()"
                class="taskster_button px-4 text-xs h-9 rounded-md flex items-center justify-center space-x-1.5"
              >
                <FileText class="w-3.5 h-3.5" />
                <span>Als Notiz/Journal speichern</span>
              </button>

              <button
                type="button"
                @click="saveAsTask"
                :disabled="saving || !transcribedText.trim() || !selectedProjectId"
                class="taskster_button_light px-4 text-xs h-9 rounded-md flex items-center justify-center space-x-1.5"
                :title="!selectedProjectId ? 'Bitte wähle zuerst ein Projekt aus' : 'Als neue Aufgabe im Projekt anlegen'"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Als Aufgabe anlegen</span>
              </button>
            </div>

            <button
              type="button"
              @click="copyToClipboard"
              class="w-full py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-md transition flex items-center justify-center space-x-1"
            >
              <Copy class="w-3.5 h-3.5" />
              <span>{{ copied ? 'In Zwischenablage kopiert!' : 'In Zwischenablage kopieren' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue'
import { Mic, Square, Loader2, X, FileText, Plus, Copy } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: boolean
  defaultProjectId?: string
  projects?: any[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
  (e: 'saved', payload: any): void
}>()

const { token } = useAuth()
const authHeaders = () => ({
  'Authorization': `Bearer ${token.value || ''}`
})

type StateMode = 'idle' | 'recording' | 'transcribing' | 'result'
const state = ref<StateMode>('idle')
const errorMessage = ref('')
const transcribedText = ref('')
const selectedProjectId = ref('')
const saving = ref(false)
const copied = ref(false)

// MediaRecorder setup
let mediaRecorder: MediaRecorder | null = null
let audioChunks: Blob[] = []
let recordingTimer: any = null
const recordingSeconds = ref(0)

const formattedRecordingTime = computed(() => {
  const mins = Math.floor(recordingSeconds.value / 60)
  const secs = recordingSeconds.value % 60
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
})

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    state.value = 'idle'
    errorMessage.value = ''
    transcribedText.value = ''
    selectedProjectId.value = props.defaultProjectId || ''
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
  audioChunks = []
  recordingSeconds.value = 0

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    const options = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
      ? { mimeType: 'audio/webm;codecs=opus' }
      : (MediaRecorder.isTypeSupported('audio/mp4') ? { mimeType: 'audio/mp4' } : {})

    mediaRecorder = new MediaRecorder(stream, options)
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

    mediaRecorder.start(250)
    state.value = 'recording'
    recordingTimer = setInterval(() => {
      recordingSeconds.value++
    }, 1000)
  } catch (err: any) {
    errorMessage.value = 'Mikrofonzugriff verweigert oder nicht unterstützt. Bitte erteile Mikrofon-Berechtigung im Browser.'
  }
}

const stopRecording = () => {
  if (recordingTimer) {
    clearInterval(recordingTimer)
    recordingTimer = null
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

    if (audioBlob.size === 0) {
      errorMessage.value = 'Keine Audio-Daten aufgenommen.'
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

    const response = await $fetch<{ success: boolean; text: string; model?: string }>('/api/ai/transcribe', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        audio: base64Data,
        mimeType,
        model: 'openai/whisper-large-v3-turbo'
      }
    })

    if (response.success && response.text) {
      transcribedText.value = response.text
      state.value = 'result'
    } else {
      errorMessage.value = 'Keine Sprache erkannt oder Transkription fehlgeschlagen.'
      state.value = 'idle'
    }
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || err.message || 'Fehler bei der Transkription'
    state.value = 'idle'
  }
}

const saveAsJournal = async () => {
  if (!transcribedText.value.trim()) return
  saving.value = true
  errorMessage.value = ''

  try {
    const title = 'Sprachnotiz (' + new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' }) + ')'
    await $fetch('/api/journals', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: selectedProjectId.value || null,
        entry_type: 'voice',
        title,
        content: transcribedText.value
      }
    })

    emit('saved', { type: 'journal', text: transcribedText.value, project_id: selectedProjectId.value })
    closeModal()
  } catch (err: any) {
    errorMessage.value = err.data?.statusMessage || 'Fehler beim Speichern der Sprachnotiz'
  } finally {
    saving.value = false
  }
}

const saveAsTask = async () => {
  if (!transcribedText.value.trim() || !selectedProjectId.value) return
  saving.value = true
  errorMessage.value = ''

  try {
    // 1. Fetch first section / list of project
    const projData = await $fetch<any>(`/api/projects/${selectedProjectId.value}`, {
      headers: authHeaders()
    })

    const listId = projData.lists?.[0]?.id
    if (!listId) {
      throw new Error('Das gewählte Projekt hat noch keine Abschnitte/Listen')
    }

    // 2. Create task
    const taskTitle = transcribedText.value.length > 50 
      ? transcribedText.value.slice(0, 47) + '...' 
      : transcribedText.value

    await $fetch('/api/tasks', {
      method: 'POST',
      headers: authHeaders(),
      body: {
        project_id: selectedProjectId.value,
        list_id: listId,
        title: taskTitle,
        description: 'Aus Sprachnotiz (openai/whisper-large-v3-turbo):\n\n' + transcribedText.value,
        status: 'todo'
      }
    })

    emit('saved', { type: 'task', text: transcribedText.value, project_id: selectedProjectId.value })
    closeModal()
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
