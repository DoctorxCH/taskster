export interface StopwatchState {
  isRunning: boolean
  startTime: number
  elapsedSeconds: number
  projectId: string
  projectTitle: string
  projectCurrency: string
  taskId: string | null
  taskTitle: string | null
  description: string
}

const STORAGE_KEY = 'taskster_active_stopwatch'

export const useStopwatch = () => {
  const state = useState<StopwatchState>('taskster_stopwatch', () => ({
    isRunning: false,
    startTime: 0,
    elapsedSeconds: 0,
    projectId: '',
    projectTitle: '',
    projectCurrency: 'CHF',
    taskId: null,
    taskTitle: null,
    description: ''
  }))

  const showStopModal = useState<boolean>('taskster_stopwatch_modal', () => false)
  const isSaving = useState<boolean>('taskster_stopwatch_saving', () => false)

  const syncInterval = () => {
    if (state.value.isRunning && state.value.startTime > 0) {
      state.value.elapsedSeconds = Math.max(0, Math.floor((Date.now() - state.value.startTime) / 1000))
    }
  }

  const initStopwatch = () => {
    if (import.meta.client) {
      try {
        const raw = localStorage.getItem(STORAGE_KEY)
        if (raw) {
          const parsed = JSON.parse(raw)
          if (parsed && parsed.isRunning && parsed.startTime) {
            state.value = {
              ...parsed,
              elapsedSeconds: Math.max(0, Math.floor((Date.now() - parsed.startTime) / 1000))
            }
          }
        }
      } catch (e) {
        console.error('Failed to parse stopwatch state from localStorage', e)
      }

      // Ensure single global interval ticker
      if (!(window as any).__taskster_stopwatch_interval) {
        (window as any).__taskster_stopwatch_interval = setInterval(() => {
          syncInterval()
        }, 1000)
      }
    }
  }

  const persist = () => {
    if (import.meta.client) {
      if (state.value.isRunning) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state.value))
      } else {
        localStorage.removeItem(STORAGE_KEY)
      }
    }
  }

  const startTimer = (options: {
    projectId: string
    projectTitle: string
    projectCurrency?: string
    taskId?: string | null
    taskTitle?: string | null
    description?: string
  }) => {
    // If already running on another task, stop it or ask user
    state.value = {
      isRunning: true,
      startTime: Date.now(),
      elapsedSeconds: 0,
      projectId: options.projectId,
      projectTitle: options.projectTitle,
      projectCurrency: options.projectCurrency || 'CHF',
      taskId: options.taskId || null,
      taskTitle: options.taskTitle || null,
      description: options.description || ''
    }
    persist()
  }

  const openStopModal = () => {
    syncInterval()
    showStopModal.value = true
  }

  const closeStopModal = () => {
    showStopModal.value = false
  }

  const discardTimer = () => {
    state.value = {
      isRunning: false,
      startTime: 0,
      elapsedSeconds: 0,
      projectId: '',
      projectTitle: '',
      projectCurrency: 'CHF',
      taskId: null,
      taskTitle: null,
      description: ''
    }
    persist()
    showStopModal.value = false
  }

  const saveTimerEntry = async (authHeaders: () => Record<string, string>, note?: string) => {
    if (!state.value.projectId || state.value.startTime <= 0) return null

    isSaving.value = true
    syncInterval()
    // Duration in minutes (minimum 1 min)
    const durationMinutes = Math.max(1, Math.round(state.value.elapsedSeconds / 60))
    const finalDescription = (note !== undefined ? note : state.value.description) || ''

    try {
      const res = await $fetch<any>('/api/time-entries', {
        method: 'POST',
        headers: authHeaders(),
        body: {
          project_id: state.value.projectId,
          task_id: state.value.taskId || null,
          duration_minutes: durationMinutes,
          entry_date: new Date().toISOString().substring(0, 10),
          description: finalDescription,
          is_manual: 0 // Recorded via live stopwatch -> NO asterisk
        }
      })

      // Reset state after saving
      discardTimer()

      if (import.meta.client) {
        window.dispatchEvent(new CustomEvent('taskster-time-entry-saved', { detail: res }))
      }

      return res
    } catch (err: any) {
      alert(err.data?.statusMessage || 'Fehler beim Speichern der Zeiterfassung')
      throw err
    } finally {
      isSaving.value = false
      showStopModal.value = false
    }
  }

  const formatSeconds = (totalSec: number) => {
    const hours = Math.floor(totalSec / 3600)
    const minutes = Math.floor((totalSec % 3600) / 60)
    const seconds = totalSec % 60

    const hh = String(hours).padStart(2, '0')
    const mm = String(minutes).padStart(2, '0')
    const ss = String(seconds).padStart(2, '0')

    return `${hh}:${mm}:${ss}`
  }

  return {
    state,
    showStopModal,
    isSaving,
    initStopwatch,
    startTimer,
    openStopModal,
    closeStopModal,
    discardTimer,
    saveTimerEntry,
    formatSeconds
  }
}
