<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-start justify-center pt-[10vh] px-4 bg-slate-950/50 backdrop-blur-sm"
        @mousedown.self="close"
      >
        <Transition
          enter-active-class="transition duration-150 ease-out"
          enter-from-class="opacity-0 scale-95 -translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
        >
          <div
            v-if="isOpen"
            class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[70vh]"
            @keydown.esc.stop="close"
          >
            <!-- Suchfeld -->
            <div class="flex items-center gap-3 px-4 h-14 border-b border-slate-200 shrink-0">
              <Search class="w-5 h-5 text-slate-400 shrink-0" />
              <input
                ref="inputRef"
                v-model="query"
                type="text"
                placeholder="Aufgaben, Projekte, Ordner, Personen durchsuchen…"
                class="flex-1 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none"
                autocomplete="off"
                spellcheck="false"
                @keydown.down.prevent="moveSelection(1)"
                @keydown.up.prevent="moveSelection(-1)"
                @keydown.enter.prevent="runSelected"
                @keydown.tab.prevent="moveSelection(1)"
              />
              <Loader2 v-if="loading" class="w-4 h-4 text-slate-400 animate-spin shrink-0" />
              <kbd
                v-else-if="query"
                class="px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-slate-100 border border-slate-200 rounded shrink-0"
              >
                ESC
              </kbd>
            </div>

            <!-- Ergebnisliste -->
            <div ref="listRef" class="flex-1 overflow-y-auto overscroll-contain">
              <!-- Leerzustand: noch keine Eingabe -->
              <div v-if="!query.trim()" class="p-2">
                <div class="px-3 py-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                  Schnellzugriff
                </div>
                <button
                  v-for="(cmd, i) in quickActions"
                  :key="cmd.url"
                  type="button"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left transition-colors"
                  :class="flatIndex === i ? 'bg-cyan-50' : 'hover:bg-slate-50'"
                  @mouseenter="flatIndex = i"
                  @click="navigate(cmd.url)"
                >
                  <component :is="cmd.icon" class="w-4 h-4 text-slate-500 shrink-0" />
                  <span class="flex-1 text-sm font-medium text-slate-800">{{ cmd.title }}</span>
                  <span class="text-[11px] text-slate-400">{{ cmd.hint }}</span>
                </button>
              </div>

              <!-- Keine Treffer -->
              <div v-else-if="!loading && flatItems.length === 0" class="py-14 text-center">
                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                  <SearchX class="w-6 h-6 text-slate-400" />
                </div>
                <p class="text-sm font-semibold text-slate-800">Keine Treffer</p>
                <p class="text-xs text-slate-500 mt-1">
                  Nichts gefunden für „{{ query }}"
                </p>
              </div>

              <!-- Treffer, gruppiert -->
              <div v-else class="p-2">
                <template v-for="group in groups" :key="group.type">
                  <div class="px-3 pt-3 pb-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <span>{{ group.label }}</span>
                    <span class="text-slate-300 font-medium normal-case tracking-normal">
                      {{ group.items.length }}
                    </span>
                  </div>

                  <button
                    v-for="item in group.items"
                    :key="item.type + item.id"
                    :ref="(el) => registerItemEl(el, item)"
                    type="button"
                    class="w-full flex items-start gap-3 px-3 py-2.5 rounded-xl text-left transition-colors"
                    :class="isSelected(item) ? 'bg-cyan-50 ring-1 ring-cyan-200' : 'hover:bg-slate-50'"
                    @mouseenter="setSelected(item)"
                    @click="navigate(item.url)"
                  >
                    <component
                      :is="iconFor(item.icon)"
                      class="w-4 h-4 mt-0.5 shrink-0"
                      :class="isSelected(item) ? 'text-cyan-700' : 'text-slate-400'"
                    />

                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2">
                        <span
                          class="text-sm font-medium truncate"
                          :class="isSelected(item) ? 'text-cyan-900' : 'text-slate-800'"
                        >
                          {{ item.title }}
                        </span>

                        <!-- Status-Badge -->
                        <span
                          v-if="item.status"
                          class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide"
                          :class="statusClass(item.status)"
                        >
                          {{ statusLabel(item.status) }}
                        </span>

                        <!-- Priorität -->
                        <span
                          v-if="item.priority && item.priority !== 'normal'"
                          class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide"
                          :class="priorityClass(item.priority)"
                        >
                          {{ item.priority }}
                        </span>
                      </div>

                      <div v-if="item.subtitle" class="text-xs text-slate-500 truncate mt-0.5">
                        {{ item.subtitle }}
                      </div>

                      <div v-if="item.context" class="text-[11px] text-slate-400 truncate mt-0.5 flex items-center gap-1">
                        <CornerDownRight class="w-3 h-3 shrink-0" />
                        <span class="truncate">{{ item.context }}</span>
                      </div>
                    </div>

                    <ArrowRight
                      v-if="isSelected(item)"
                      class="w-4 h-4 text-cyan-600 shrink-0 mt-0.5"
                    />
                  </button>
                </template>
              </div>
            </div>

            <!-- Fusszeile -->
            <div class="flex items-center justify-between px-4 h-11 border-t border-slate-200 bg-slate-50 shrink-0">
              <div class="flex items-center gap-4 text-[11px] text-slate-500">
                <span class="flex items-center gap-1.5">
                  <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-bold">↑</kbd>
                  <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-bold">↓</kbd>
                  Navigieren
                </span>
                <span class="flex items-center gap-1.5">
                  <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-bold">↵</kbd>
                  Öffnen
                </span>
                <span class="hidden sm:flex items-center gap-1.5">
                  <kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded font-bold">ESC</kbd>
                  Schliessen
                </span>
              </div>
              <div v-if="total > 0" class="text-[11px] text-slate-400 font-medium">
                {{ total }} Treffer
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import {
  Search, SearchX, Loader2, ArrowRight, CornerDownRight,
  LayoutDashboard, Folder, FolderKanban, Clock, Settings, User, Building2,
  Users, ClipboardList, FileText, CreditCard, MessageSquare, Bell, Palette,
  Image as ImageIcon, CheckCircle2, Trash2, Pencil, Square, BarChart3,
  Upload, Paperclip, Lock, Zap, Sun, Columns3, LayoutTemplate, Plus, CircleDot
} from 'lucide-vue-next'

const emit = defineEmits<{ (e: 'navigate', url: string): void }>()

const isOpen = ref(false)
const query = ref('')
const loading = ref(false)
const groups = ref<any[]>([])
const total = ref(0)
const flatIndex = ref(0)
const inputRef = ref<HTMLInputElement | null>(null)
const listRef = ref<HTMLElement | null>(null)
const itemEls = new Map<string, HTMLElement>()

const { authHeaders, user } = useAuth()

// ---------------------------------------------------------------------------
// Icon-Registry (Lucide)
// ---------------------------------------------------------------------------
const ICONS: Record<string, any> = {
  LayoutDashboard, Folder, FolderKanban, Clock, Settings, User, Building2,
  Users, ClipboardList, FileText, CreditCard, MessageSquare, Bell, Palette,
  Image: ImageIcon, CheckCircle2, Trash2, Pencil, Square, BarChart3,
  Upload, Paperclip, Lock, Zap, Sun, Columns3, LayoutTemplate, CircleDot
}

const iconFor = (name: string) => ICONS[name] || ClipboardList

// ---------------------------------------------------------------------------
// Schnellzugriff (leere Suche)
// ---------------------------------------------------------------------------
const quickActions = computed(() => {
  // Free-/Single-User (ohne Company) haben keine Ordner-Ebene.
  const isFreeUser = !user.value?.is_pro && !user.value?.company_id && !user.value?.is_superadmin
  const actions = [
    { title: 'Dashboard', hint: 'Startseite', url: '/dashboard', icon: LayoutDashboard },
    { title: 'Zeitrapporte', hint: 'Zeiterfassung', url: '/time', icon: Clock }
  ]
  if (!isFreeUser) {
    actions.push({ title: 'Neuer Projektordner', hint: 'Erstellen', url: '/dashboard?new=folder', icon: Plus })
  }
  actions.push({ title: 'Einstellungen', hint: 'Profil & Tarif', url: '/settings', icon: Settings })
  return actions
})

// ---------------------------------------------------------------------------
// Flache Liste für Tastatur-Navigation
// ---------------------------------------------------------------------------
const flatItems = computed(() => {
  if (!query.value.trim()) return quickActions.map((c) => ({ ...c, type: 'quick', id: c.url }))
  return groups.value.flatMap((g: any) => g.items)
})

const isSelected = (item: any) => {
  const list = flatItems.value
  return list[flatIndex.value] && list[flatIndex.value].id === item.id && list[flatIndex.value].type === item.type
}

const setSelected = (item: any) => {
  const idx = flatItems.value.findIndex((i: any) => i.id === item.id && i.type === item.type)
  if (idx !== -1) flatIndex.value = idx
}

const registerItemEl = (el: any, item: any) => {
  if (el) itemEls.set(item.type + item.id, el as HTMLElement)
}

const moveSelection = (delta: number) => {
  const len = flatItems.value.length
  if (len === 0) return
  flatIndex.value = (flatIndex.value + delta + len) % len
  scrollToSelected()
}

const scrollToSelected = async () => {
  await nextTick()
  const item = flatItems.value[flatIndex.value]
  if (!item) return
  const el = itemEls.get(item.type + item.id)
  el?.scrollIntoView({ block: 'nearest' })
}

// ---------------------------------------------------------------------------
// Badge-Styling
// ---------------------------------------------------------------------------
const statusClass = (status: string) => ({
  'bg-emerald-50 text-emerald-700 border border-emerald-200': status === 'done',
  'bg-cyan-50 text-cyan-700 border border-cyan-200': status === 'in_progress',
  'bg-amber-50 text-amber-700 border border-amber-200': status === 'review',
  'bg-slate-100 text-slate-600 border border-slate-200': status === 'todo'
})

const statusLabel = (status: string) =>
  ({ todo: 'Todo', in_progress: 'In Arbeit', review: 'Prüfung', done: 'Erledigt' } as any)[status] || status

const priorityClass = (p: string) => ({
  'bg-rose-50 text-rose-700 border border-rose-200': p === 'dringend',
  'bg-amber-50 text-amber-700 border border-amber-200': p === 'hoch',
  'bg-slate-100 text-slate-500 border border-slate-200': p === 'niedrig'
})

// ---------------------------------------------------------------------------
// Suche (debounced)
// ---------------------------------------------------------------------------
let debounceTimer: ReturnType<typeof setTimeout> | null = null
let requestId = 0

const runSearch = async (q: string) => {
  const current = ++requestId
  loading.value = true
  try {
    const res = await $fetch<any>('/api/search', {
      headers: authHeaders(),
      params: { q, limit: 6 }
    })
    // Veraltete Antworten verwerfen
    if (current !== requestId) return
    groups.value = res.groups || []
    total.value = res.total || 0
    flatIndex.value = 0
  } catch {
    if (current !== requestId) return
    groups.value = []
    total.value = 0
  } finally {
    if (current === requestId) loading.value = false
  }
}

watch(query, (q) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (!q.trim()) {
    groups.value = []
    total.value = 0
    flatIndex.value = 0
    loading.value = false
    return
  }
  loading.value = true
  debounceTimer = setTimeout(() => runSearch(q.trim()), 180)
})

// ---------------------------------------------------------------------------
// Öffnen / Schliessen
// ---------------------------------------------------------------------------
const open = () => {
  isOpen.value = true
  query.value = ''
  groups.value = []
  total.value = 0
  flatIndex.value = 0
  nextTick(() => inputRef.value?.focus())
}

const close = () => {
  isOpen.value = false
  query.value = ''
  groups.value = []
  total.value = 0
}

const toggle = () => (isOpen.value ? close() : open())

const navigate = (url: string) => {
  close()
  emit('navigate', url)
  navigateTo(url)
}

const runSelected = () => {
  const item = flatItems.value[flatIndex.value]
  if (item?.url) navigate(item.url)
}

// ---------------------------------------------------------------------------
// Globaler Shortcut: Strg+K / Cmd+K  (mit preventDefault!)
// ---------------------------------------------------------------------------
const onKeydown = (e: KeyboardEvent) => {
  const isCmdK = (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k'
  if (isCmdK) {
    e.preventDefault()   // verhindert, dass der Browser seine Adressleiste öffnet
    e.stopPropagation()
    toggle()
    return
  }
  if (e.key === 'Escape' && isOpen.value) {
    e.preventDefault()
    close()
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown, true)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown, true)
  if (debounceTimer) clearTimeout(debounceTimer)
})

defineExpose({ open, close, toggle })
</script>
