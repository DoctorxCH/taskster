<template>
  <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40" @mousedown.self="$emit('close')">
    <div class="bg-white rounded-lg shadow-md w-full max-w-2xl max-h-[90vh] flex flex-col">
      <!-- Kopf -->
      <div class="flex items-center justify-between px-5 h-14 border-b border-slate-200 shrink-0">
        <h2 class="text-base font-semibold text-slate-900">
          {{ isEdit ? 'Termin bearbeiten' : 'Neuer Termin' }}
        </h2>
        <button type="button" class="h-8 w-8 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="$emit('close')">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Inhalt -->
      <form class="flex-1 overflow-y-auto p-5 space-y-4" @submit.prevent="save">
        <!-- Betreff -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Betreff <span class="text-rose-500">*</span></label>
          <input
            v-model="form.title"
            type="text"
            required
            placeholder="z.B. Baubesprechung Trasse 410"
            class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
          />
        </div>

        <!-- Ganztägig -->
        <label class="flex items-center gap-2 text-xs font-medium text-slate-700">
          <input v-model="form.all_day" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#0891B2] focus:ring-0" />
          Ganztägiger Termin
        </label>

        <!-- Zeit -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Beginn <span class="text-rose-500">*</span></label>
            <input
              v-model="form.start_at"
              :type="form.all_day ? 'date' : 'datetime-local'"
              required
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ende <span class="text-rose-500">*</span></label>
            <input
              v-model="form.end_at"
              :type="form.all_day ? 'date' : 'datetime-local'"
              required
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            />
          </div>
        </div>

        <!-- Ort -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ort</label>
          <div class="relative">
            <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="form.location"
              type="text"
              placeholder="z.B. Baustelle Zürcherstrasse 45"
              class="w-full h-9 pl-9 pr-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            />
          </div>
        </div>

        <!-- Kategorie + Priorität -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategorie</label>
            <select
              v-model="form.category_id"
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            >
              <option :value="null">Keine</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Priorität</label>
            <select
              v-model="form.priority"
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            >
              <option value="niedrig">Niedrig</option>
              <option value="normal">Normal</option>
              <option value="hoch">Hoch</option>
              <option value="dringend">Dringend</option>
            </select>
          </div>
        </div>

        <!-- Projekt + Sichtbarkeit -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Projekt (optional)</label>
            <select
              v-model="form.project_id"
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            >
              <option :value="null">Kein Projekt</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.title }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sichtbarkeit</label>
            <select
              v-model="form.visibility"
              class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
            >
              <option value="private">Privat (nur ich & Eingeladene)</option>
              <option v-if="user?.company_id" value="company">Für Firma sichtbar</option>
            </select>
          </div>
        </div>

        <!-- Beschreibung -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Beschreibung</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Agenda, Notizen, Anforderungen…"
            class="w-full px-3 py-2 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15 resize-y"
          />
        </div>

        <!-- Teilnehmer -->
        <div class="pt-3 border-t border-slate-200">
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <Users class="w-3.5 h-3.5" />
              Teilnehmer ({{ form.attendees.length }})
            </label>
            <span class="text-[11px] text-slate-400">Eingeladene erhalten eine Benachrichtigung</span>
          </div>

          <!-- Ausgewählte -->
          <div v-if="form.attendees.length > 0" class="flex flex-wrap gap-1.5 mb-2">
            <span
              v-for="(a, i) in form.attendees"
              :key="a.email"
              class="inline-flex items-center gap-1.5 h-7 pl-2 pr-1 rounded-md bg-cyan-50 border border-cyan-200 text-xs font-medium text-cyan-900"
            >
              <span class="w-4 h-4 rounded-full bg-[#0891B2] text-white text-[9px] font-bold flex items-center justify-center">
                {{ (a.name || a.email).charAt(0).toUpperCase() }}
              </span>
              <span class="max-w-[140px] truncate">{{ a.name || a.email }}</span>
              <button type="button" class="text-cyan-600 hover:text-cyan-900" @click="form.attendees.splice(i, 1)">
                <X class="w-3 h-3" />
              </button>
            </span>
          </div>

          <!-- E-Mail eingeben -->
          <div class="flex gap-2">
            <input
              v-model="attendeeInput"
              type="email"
              placeholder="E-Mail-Adresse eingeben…"
              class="flex-1 h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
              @keydown.enter.prevent="addAttendee"
            />
            <button type="button" class="h-9 px-3 text-sm font-semibold rounded-md bg-white text-slate-700 border border-slate-300 hover:bg-slate-50" @click="addAttendee">
              <Plus class="w-4 h-4" />
            </button>
          </div>

          <!-- Team-Vorschläge -->
          <div v-if="availableMembers.length > 0" class="mt-2">
            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Aus dem Team</div>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="m in availableMembers.slice(0, 8)"
                :key="m.id"
                type="button"
                class="h-7 px-2 rounded-md bg-white border border-slate-300 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:border-slate-400 transition-colors flex items-center gap-1.5"
                @click="addMember(m)"
              >
                <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-600 text-[9px] font-bold flex items-center justify-center">
                  {{ m.name.charAt(0).toUpperCase() }}
                </span>
                {{ m.name }}
              </button>
            </div>
          </div>
        </div>

        <!-- Erinnerung -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1.5">Erinnerung</label>
          <select
            v-model="form.reminder_minutes"
            class="w-full h-9 px-3 text-sm rounded-md bg-white border border-slate-300 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
          >
            <option :value="null">Keine</option>
            <option :value="5">5 Minuten vorher</option>
            <option :value="15">15 Minuten vorher</option>
            <option :value="30">30 Minuten vorher</option>
            <option :value="60">1 Stunde vorher</option>
            <option :value="1440">1 Tag vorher</option>
          </select>
        </div>

        <!-- Teilnehmer-Antworten (nur beim Bearbeiten) -->
        <div v-if="isEdit && event?.attendees?.length > 1" class="pt-3 border-t border-slate-200">
          <div class="text-xs font-semibold text-slate-700 mb-2">Antworten</div>
          <div class="space-y-1">
            <div
              v-for="a in event.attendees.filter((x: any) => !x.is_organizer)"
              :key="a.id"
              class="flex items-center justify-between px-2.5 py-1.5 rounded-md bg-slate-50"
            >
              <span class="text-xs text-slate-700 truncate">{{ a.name || a.email }}</span>
              <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0" :class="statusClass(a.status)">
                {{ statusLabel(a.status) }}
              </span>
            </div>
          </div>
        </div>
      </form>

      <!-- Fusszeile -->
      <div class="flex items-center justify-between px-5 h-16 border-t border-slate-200 shrink-0">
        <div>
          <button
            v-if="isEdit && event?.editable"
            type="button"
            class="h-9 px-4 text-sm font-semibold rounded-md bg-[#BE123C] text-white hover:bg-[#9F1239] transition-colors"
            @click="remove"
          >
            Löschen
          </button>
          <a
            v-else-if="isEdit"
            :href="`/api/events/${event.id}/ics`"
            class="h-9 px-4 text-sm font-semibold rounded-md bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 inline-flex items-center gap-2"
            @click.prevent="downloadIcs"
          >
            <Download class="w-4 h-4" />
            ICS
          </a>
        </div>

        <div class="flex items-center gap-2">
          <!-- Antwort-Buttons für Eingeladene -->
          <template v-if="isEdit && !event?.is_organizer && event?.my_status">
            <button type="button" class="h-9 px-3 text-sm font-semibold rounded-md bg-white text-slate-700 border border-slate-300 hover:bg-slate-50" @click="respond('declined')">
              Absagen
            </button>
            <button type="button" class="h-9 px-3 text-sm font-semibold rounded-md bg-white text-slate-700 border border-slate-300 hover:bg-slate-50" @click="respond('tentative')">
              Vorbehalt
            </button>
            <button type="button" class="taskster_button" @click="respond('accepted')">
              Zusagen
            </button>
          </template>

          <template v-else>
            <button type="button" class="h-9 px-4 text-sm font-semibold rounded-md bg-white text-slate-700 border border-slate-300 hover:bg-slate-50" @click="$emit('close')">
              Abbrechen
            </button>
            <button
              v-if="!isEdit || event?.editable"
              type="button"
              class="taskster_button"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? 'Speichern…' : (isEdit ? 'Speichern' : 'Termin erstellen') }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { X, MapPin, Users, Plus, Download } from 'lucide-vue-next'

const props = defineProps<{
  event?: any
  defaultDate?: string | null
  defaultHour?: number | null
  categories: any[]
  projects: any[]
  members: any[]
  defaults?: any
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved'): void
  (e: 'deleted'): void
}>()

const { user, authHeaders } = useAuth()

const isEdit = computed(() => Boolean(props.event?.id))
const saving = ref(false)
const attendeeInput = ref('')

// ---------------------------------------------------------------------------
// Formular initialisieren
// ---------------------------------------------------------------------------
const pad = (n: number) => String(n).padStart(2, '0')

function toLocalInput(d: Date): string {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function toDateInput(d: Date): string {
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const form = ref<any>({
  title: '',
  description: '',
  location: '',
  start_at: '',
  end_at: '',
  all_day: false,
  priority: 'normal',
  visibility: 'private',
  category_id: null,
  project_id: null,
  reminder_minutes: 15,
  attendees: [] as any[]
})

function initForm() {
  if (props.event) {
    const e = props.event
    const allDay = Boolean(e.allDay)
    form.value = {
      title: e.title || '',
      description: e.description || '',
      location: e.location || '',
      start_at: allDay ? String(e.start).slice(0, 10) : toLocalInput(new Date(String(e.start).replace(' ', 'T'))),
      end_at: allDay ? String(e.end).slice(0, 10) : toLocalInput(new Date(String(e.end).replace(' ', 'T'))),
      all_day: allDay,
      priority: e.priority || 'normal',
      visibility: e.visibility || 'private',
      category_id: e.category_id || null,
      project_id: e.project_id || null,
      reminder_minutes: e.reminder_minutes ?? 15,
      attendees: (e.attendees || [])
        .filter((a: any) => !a.is_organizer)
        .map((a: any) => ({ email: a.email, name: a.name, role: a.role }))
    }
  } else {
    // Neuer Termin: Standard = nächste volle Stunde, Dauer aus den Einstellungen
    const d = props.defaults || {}
    const durationMin = Number(d.default_duration_minutes) > 0 ? Number(d.default_duration_minutes) : 60
    const base = props.defaultDate ? new Date(props.defaultDate + 'T00:00:00') : new Date()
    if (props.defaultHour != null) {
      base.setHours(props.defaultHour, 0, 0, 0)
    } else if (!props.defaultDate) {
      base.setMinutes(0, 0, 0)
      base.setHours(base.getHours() + 1)
    } else {
      // Innerhalb der Arbeitszeit starten, sonst um 09:00
      const [wh] = String(d.workday_start || '09:00').split(':').map(Number)
      base.setHours(Number.isFinite(wh) ? wh : 9, 0, 0, 0)
    }
    const end = new Date(base.getTime() + durationMin * 60 * 1000)

    form.value = {
      title: '',
      description: '',
      location: '',
      start_at: toLocalInput(base),
      end_at: toLocalInput(end),
      all_day: false,
      priority: 'normal',
      visibility: d.default_visibility === 'company' ? 'company' : 'private',
      category_id: d.default_category_id || null,
      project_id: null,
      reminder_minutes: d.default_reminder_minutes === undefined ? 15 : d.default_reminder_minutes,
      attendees: []
    }
  }
}

// ---------------------------------------------------------------------------
// Teilnehmer
// ---------------------------------------------------------------------------
const availableMembers = computed(() => {
  const chosen = new Set(form.value.attendees.map((a: any) => String(a.email).toLowerCase()))
  return props.members.filter((m: any) => !chosen.has(String(m.email).toLowerCase()))
})

function addAttendee() {
  const email = attendeeInput.value.trim().toLowerCase()
  if (!email || !email.includes('@')) return
  if (form.value.attendees.some((a: any) => String(a.email).toLowerCase() === email)) {
    attendeeInput.value = ''
    return
  }
  const known = props.members.find((m: any) => String(m.email).toLowerCase() === email)
  form.value.attendees.push({ email, name: known?.name || null, role: 'required' })
  attendeeInput.value = ''
}

function addMember(m: any) {
  const email = String(m.email).toLowerCase()
  if (form.value.attendees.some((a: any) => String(a.email).toLowerCase() === email)) return
  form.value.attendees.push({ email, name: m.name, role: 'required' })
}

// ---------------------------------------------------------------------------
// Speichern
// ---------------------------------------------------------------------------
function normalizeDateTime(value: string, allDay: boolean, isEnd: boolean): string {
  if (allDay) {
    // Ganztägig: Ende exklusiv → bei gleichem Tag +1 Tag
    return value
  }
  return value.replace('T', ' ') + ':00'
}

async function save() {
  if (!form.value.title.trim()) return
  saving.value = true

  try {
    const allDay = form.value.all_day
    let startAt = normalizeDateTime(form.value.start_at, allDay, false)
    let endAt = normalizeDateTime(form.value.end_at, allDay, true)

    // Ganztägig: Ende mindestens Start + 1 Tag
    if (allDay && endAt <= startAt) {
      const d = new Date(startAt + 'T00:00:00')
      d.setDate(d.getDate() + 1)
      endAt = toDateInput(d)
    }

    const payload = {
      title: form.value.title.trim(),
      description: form.value.description || null,
      location: form.value.location || null,
      start_at: startAt,
      end_at: endAt,
      all_day: allDay,
      priority: form.value.priority,
      visibility: form.value.visibility,
      category_id: form.value.category_id,
      project_id: form.value.project_id,
      reminder_minutes: form.value.reminder_minutes,
      attendees: form.value.attendees
    }

    if (isEdit.value) {
      await $fetch(`/api/events/${props.event.id}`, {
        method: 'PUT',
        headers: authHeaders(),
        body: payload
      })
    } else {
      await $fetch('/api/events', {
        method: 'POST',
        headers: authHeaders(),
        body: payload
      })
    }

    emit('saved')
  } catch (err: any) {
    alert(err?.data?.statusMessage || 'Termin konnte nicht gespeichert werden')
  } finally {
    saving.value = false
  }
}

async function remove() {
  if (!confirm(`Termin "${props.event.title}" wirklich löschen? Alle Teilnehmer werden informiert.`)) return
  try {
    await $fetch(`/api/events/${props.event.id}`, {
      method: 'DELETE',
      headers: authHeaders()
    })
    emit('deleted')
  } catch (err: any) {
    alert(err?.data?.statusMessage || 'Termin konnte nicht gelöscht werden')
  }
}

async function respond(status: 'accepted' | 'declined' | 'tentative') {
  try {
    await $fetch(`/api/events/${props.event.id}/respond`, {
      method: 'POST',
      headers: authHeaders(),
      body: { status }
    })
    emit('saved')
  } catch (err: any) {
    alert(err?.data?.statusMessage || 'Antwort konnte nicht gesendet werden')
  }
}

/**
 * ICS-Download mit Auth-Header. Ein reiner <a href> kann den Bearer-Token nicht
 * mitsenden, deshalb laden wir die Datei per fetch als Blob und speichern sie
 * über einen temporären Object-URL-Link.
 */
async function downloadIcs() {
  if (!props.event?.id) return
  try {
    const res = await fetch(`/api/events/${props.event.id}/ics`, { headers: authHeaders() })
    if (!res.ok) throw new Error('HTTP ' + res.status)
    const blob = await res.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `termin-${props.event.id}.ics`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  } catch {
    alert('ICS-Datei konnte nicht geladen werden.')
  }
}

// ---------------------------------------------------------------------------
// Badges
// ---------------------------------------------------------------------------
const statusClass = (s: string) => ({
  'bg-emerald-50 text-emerald-700 border border-emerald-200': s === 'accepted',
  'bg-rose-50 text-rose-700 border border-rose-200': s === 'declined',
  'bg-amber-50 text-amber-700 border border-amber-200': s === 'tentative',
  'bg-slate-100 text-slate-600 border border-slate-200': s === 'pending'
})

const statusLabel = (s: string) =>
  ({ accepted: 'Zugesagt', declined: 'Abgesagt', tentative: 'Vorbehalt', pending: 'Offen' } as any)[s] || s

// ---------------------------------------------------------------------------
// Ganztägig-Umschaltung: Feldtypen anpassen
// ---------------------------------------------------------------------------
watch(() => form.value.all_day, (allDay) => {
  if (allDay) {
    form.value.start_at = String(form.value.start_at).slice(0, 10)
    form.value.end_at = String(form.value.end_at).slice(0, 10)
  } else {
    if (!String(form.value.start_at).includes('T')) {
      form.value.start_at = form.value.start_at + 'T09:00'
    }
    if (!String(form.value.end_at).includes('T')) {
      form.value.end_at = form.value.end_at + 'T10:00'
    }
  }
})

onMounted(initForm)
</script>
