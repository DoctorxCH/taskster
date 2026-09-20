<template>
  <div class="relative" ref="rootEl">
    <div class="relative">
      <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
      <input
        :value="modelValue"
        type="text"
        :placeholder="placeholder"
        autocomplete="off"
        class="w-full h-9 pl-9 pr-9 text-sm rounded-md bg-white border border-slate-300 text-slate-900 focus:outline-none focus:border-[#0891B2] focus:ring-2 focus:ring-[#0891B2]/15"
        @input="onInput"
        @focus="onFocus"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="chooseHighlighted"
        @keydown.esc="close"
      />
      <Loader2 v-if="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 animate-spin" />
      <button
        v-else-if="modelValue"
        type="button"
        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700"
        title="Adresse leeren"
        @click="clear"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>

    <!-- Vorschläge -->
    <div
      v-if="open && suggestions.length"
      class="absolute left-0 right-0 top-full mt-1 z-50 bg-white border border-slate-200 rounded-md shadow-lg overflow-hidden max-h-64 overflow-y-auto"
    >
      <button
        v-for="(s, i) in suggestions"
        :key="s.lat + ',' + s.lon + i"
        type="button"
        class="w-full text-left px-3 py-2 text-xs border-b border-slate-100 last:border-0 transition-colors flex items-start gap-2"
        :class="i === highlighted ? 'bg-cyan-50' : 'hover:bg-slate-50'"
        @mousedown.prevent="choose(s)"
        @mouseenter="highlighted = i"
      >
        <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
        <span class="min-w-0 flex-1 text-slate-700 leading-snug">{{ s.label }}</span>
      </button>
    </div>

    <!-- Kein Treffer -->
    <div
      v-else-if="open && !loading && query.length >= 3 && !suggestions.length"
      class="absolute left-0 right-0 top-full mt-1 z-50 bg-white border border-slate-200 rounded-md shadow-lg px-3 py-2.5"
    >
      <p class="text-xs text-slate-500">Keine Adresse gefunden.</p>
      <a
        :href="`https://www.openstreetmap.org/search?query=${encodeURIComponent(query)}`"
        target="_blank"
        rel="noopener noreferrer"
        class="text-[11px] font-semibold text-[#0891B2] hover:underline"
      >
        Auf OpenStreetMap suchen →
      </a>
    </div>

    <!-- Status: Koordinaten vorhanden -->
    <p v-if="lat !== null && lon !== null" class="mt-1.5 text-[11px] text-emerald-700 flex items-center gap-1.5">
      <CheckCircle2 class="w-3 h-3 shrink-0" />
      Standort erkannt
      <a
        :href="`https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=17/${lat}/${lon}`"
        target="_blank"
        rel="noopener noreferrer"
        class="font-semibold text-[#0891B2] hover:underline"
      >
        auf Karte prüfen
      </a>
    </p>
  </div>
</template>

<script setup lang="ts">
import { MapPin, Loader2, X, CheckCircle2 } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  modelValue: string
  latitude?: number | null
  longitude?: number | null
  placeholder?: string
}>(), {
  latitude: null,
  longitude: null,
  placeholder: 'Strasse, Nr., PLZ Ort'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'update:latitude', value: number | null): void
  (e: 'update:longitude', value: number | null): void
  (e: 'resolved', point: { lat: number; lon: number } | null): void
}>()

const { suggest, geocode } = useAddressSearch()

const rootEl = ref<HTMLElement | null>(null)
const query = ref(props.modelValue || '')
const suggestions = ref<any[]>([])
const open = ref(false)
const loading = ref(false)
const highlighted = ref(-1)

const lat = computed(() => props.latitude)
const lon = computed(() => props.longitude)

let debounceTimer: ReturnType<typeof setTimeout> | null = null
/** Zählt Suchläufe, damit verspätete Antworten verworfen werden. */
let requestSeq = 0

watch(() => props.modelValue, (v) => {
  if (v !== query.value) query.value = v || ''
})

function onInput(e: Event) {
  const value = (e.target as HTMLInputElement).value
  query.value = value
  emit('update:modelValue', value)

  // Adresse geändert → alte Koordinaten sind ungültig
  if (props.latitude !== null || props.longitude !== null) {
    emit('update:latitude', null)
    emit('update:longitude', null)
    emit('resolved', null)
  }

  if (debounceTimer) clearTimeout(debounceTimer)
  if (value.trim().length < 3) {
    suggestions.value = []
    open.value = false
    return
  }

  debounceTimer = setTimeout(runSearch, 350)
}

async function runSearch() {
  const seq = ++requestSeq
  loading.value = true
  open.value = true
  const hits = await suggest(query.value)
  // Veraltete Antwort verwerfen
  if (seq !== requestSeq) return
  suggestions.value = hits
  highlighted.value = hits.length ? 0 : -1
  loading.value = false
}

function onFocus() {
  if (suggestions.value.length) open.value = true
}

function move(delta: number) {
  if (!suggestions.value.length) return
  open.value = true
  const n = suggestions.value.length
  highlighted.value = (highlighted.value + delta + n) % n
}

function chooseHighlighted() {
  if (open.value && highlighted.value >= 0 && suggestions.value[highlighted.value]) {
    choose(suggestions.value[highlighted.value])
  } else {
    // Enter ohne Auswahl: Adresse direkt geocodieren
    resolveCurrent()
  }
}

async function choose(s: any) {
  query.value = s.label
  emit('update:modelValue', s.label)
  emit('update:latitude', s.lat)
  emit('update:longitude', s.lon)
  emit('resolved', { lat: s.lat, lon: s.lon })
  suggestions.value = []
  open.value = false
}

/** Geocodiert die eingegebene Adresse ohne Auswahl aus der Liste. */
async function resolveCurrent() {
  const q = query.value.trim()
  if (q.length < 3) return
  loading.value = true
  const point = await geocode(q)
  loading.value = false
  open.value = false
  if (point) {
    emit('update:latitude', point.lat)
    emit('update:longitude', point.lon)
    emit('resolved', point)
  }
}

function clear() {
  query.value = ''
  emit('update:modelValue', '')
  emit('update:latitude', null)
  emit('update:longitude', null)
  emit('resolved', null)
  suggestions.value = []
  open.value = false
}

function close() {
  open.value = false
}

function onDocumentClick(e: MouseEvent) {
  if (rootEl.value && !rootEl.value.contains(e.target as Node)) close()
}

onMounted(() => document.addEventListener('click', onDocumentClick))
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  if (debounceTimer) clearTimeout(debounceTimer)
})

/**
 * Beim Öffnen eines Formulars mit bereits gespeicherter Adresse, aber ohne
 * Koordinaten (Altdaten), werden diese einmalig nachgeladen.
 */
onMounted(async () => {
  if (query.value.trim().length >= 3 && props.latitude === null && props.longitude === null) {
    const point = await geocode(query.value)
    if (point) {
      emit('update:latitude', point.lat)
      emit('update:longitude', point.lon)
      emit('resolved', point)
    }
  }
})
</script>