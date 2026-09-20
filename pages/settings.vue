<template>
  <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-5">
      <NuxtLink to="/dashboard" class="hover:text-cyan-800 transition-colors flex items-center gap-1">
        <LayoutDashboard class="w-3.5 h-3.5" />
        <span>Dashboard</span>
      </NuxtLink>
      <span>/</span>
      <span class="text-slate-800 font-medium flex items-center gap-1">
        <Settings class="w-3.5 h-3.5 text-[#0891B2]" />
        <span>Einstellungen</span>
      </span>
    </div>

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Einstellungen</h1>
        <p class="text-sm text-slate-600 mt-1">
          Profil, Kalender, Benachrichtigungen und Abrechnung an einem Ort.
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <span
          v-if="dirty"
          class="inline-flex items-center gap-1.5 h-9 px-3 rounded-md bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-800"
        >
          <CircleDot class="w-3.5 h-3.5" />
          Ungespeicherte Änderungen
        </span>
        <button
          type="button"
          class="taskster_button"
          :disabled="saving || !dirty"
          @click="saveAll"
        >
          <Save class="w-4 h-4" />
          <span>{{ saving ? 'Speichern…' : 'Alle Änderungen speichern' }}</span>
        </button>
      </div>
    </div>

    <!-- Feedback -->
    <div v-if="successMsg" class="mb-5 p-3.5 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center gap-2">
      <CheckCircle2 class="w-4 h-4 shrink-0" />
      {{ successMsg }}
    </div>
    <div v-if="errorMsg" class="mb-5 p-3.5 rounded-md bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-center gap-2">
      <AlertCircle class="w-4 h-4 shrink-0" />
      {{ errorMsg }}
    </div>

    <div class="flex flex-col lg:flex-row gap-6 items-start">
      <!-- ================= Kategorien-Navigation ================= -->
      <nav class="w-full lg:w-60 shrink-0 lg:sticky lg:top-20">
        <div class="bg-white border border-slate-200 rounded-lg p-2">
          <button
            v-for="s in sections"
            :key="s.key"
            type="button"
            class="w-full flex items-center gap-3 px-3 h-10 rounded-md text-sm font-medium transition-colors text-left"
            :class="active === s.key
              ? 'bg-cyan-50 text-cyan-800 font-semibold'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            @click="active = s.key"
          >
            <component :is="s.icon" class="w-4 h-4 shrink-0" :class="active === s.key ? 'text-[#0891B2]' : 'text-slate-400'" />
            <span class="flex-1">{{ s.label }}</span>
            <span
              v-if="s.key === 'notifications' && settings.notifications.browser"
              class="w-1.5 h-1.5 rounded-full bg-emerald-500"
              title="Browser-Benachrichtigungen aktiv"
            />
          </button>
        </div>

        <!-- Konto-Info -->
        <div class="mt-3 bg-white border border-slate-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#0891B2] text-white flex items-center justify-center font-bold text-sm shrink-0">
              {{ (user?.name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0">
              <div class="text-sm font-semibold text-slate-900 truncate">{{ user?.name }}</div>
              <div class="text-[11px] text-slate-500 truncate">{{ user?.email }}</div>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1.5">
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold border" :class="planBadgeClass">
              {{ planLabel }}
            </span>
            <span v-if="user?.company_name" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200 truncate max-w-full">
              {{ user.company_name }}
            </span>
          </div>
        </div>
      </nav>

      <!-- ================= Inhalt ================= -->
      <div class="flex-1 min-w-0 space-y-5">

        <!-- ---------- PROFIL ---------- -->
        <section v-show="active === 'profile'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <User class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">Profil</h2>
          </header>
          <div class="p-5 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Vollständiger Name</label>
                <input v-model="profileName" type="text" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">E-Mail-Adresse</label>
                <input :value="user?.email" type="email" disabled class="ts-input bg-slate-50 text-slate-500 cursor-not-allowed" />
                <p class="text-[11px] text-slate-500 mt-1.5">Dient als Login-Kennung und kann nicht geändert werden.</p>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">Darstellung & Sprache</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">App-Sprache</label>
                  <select v-model="settings.language" class="ts-input">
                    <option value="de">Deutsch</option>
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="it">Italiano</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Whisper-Sprache <span class="text-[#0891B2] font-mono text-[10px]">AI</span>
                  </label>
                  <select v-model="settings.whisper_language" class="ts-input">
                    <option value="de">Deutsch (de)</option>
                    <option value="de-CH">Schweizerdeutsch (de-CH)</option>
                    <option value="en">English (en)</option>
                    <option value="fr">Français (fr)</option>
                    <option value="it">Italiano (it)</option>
                    <option value="auto">Automatisch erkennen</option>
                  </select>
                  <p class="text-[10px] text-slate-500 mt-1">Spracheingabe für OpenRouter Whisper</p>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Design</label>
                  <select v-model="settings.theme" class="ts-input">
                    <option value="light">Hell</option>
                    <option value="dark">Dunkel (in Vorbereitung)</option>
                    <option value="system">Systemeinstellung</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Listen-Dichte</label>
                  <select v-model="settings.density" class="ts-input">
                    <option value="comfortable">Komfortabel</option>
                    <option value="compact">Kompakt</option>
                  </select>
                </div>
              </div>
              <div class="mt-4 max-w-xs">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Startseite nach dem Login</label>
                <select v-model="settings.start_page" class="ts-input">
                  <option value="dashboard">Dashboard</option>
                  <option value="calendar">Kalender</option>
                  <option value="time">Zeitrapporte</option>
                  <option value="contacts">Kontakte</option>
                </select>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- KALENDER ---------- -->
        <section v-show="active === 'calendar'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <CalendarDays class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">Kalender</h2>
          </header>
          <div class="p-5 space-y-6">

            <!-- Ansicht & Woche -->
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">Ansicht & Woche</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Ansicht</label>
                  <select v-model="settings.calendar.default_view" class="ts-input">
                    <option value="month">Monat</option>
                    <option value="week">Woche</option>
                    <option value="day">Tag</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Erster Wochentag</label>
                  <select v-model.number="settings.calendar.week_start" class="ts-input">
                    <option :value="1">Montag</option>
                    <option :value="0">Sonntag</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Zeitformat</label>
                  <select v-model="settings.calendar.time_format" class="ts-input">
                    <option value="24h">24 Stunden (17:00)</option>
                    <option value="12h">12 Stunden (5:00 PM)</option>
                  </select>
                </div>
              </div>

              <div class="mt-4 space-y-1">
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_week_numbers" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">Kalenderwochen anzeigen</span>
                    <span class="block text-[11px] text-slate-500">Zeigt die KW-Nummer links neben jeder Woche.</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_weekends" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">Wochenenden anzeigen</span>
                    <span class="block text-[11px] text-slate-500">Samstag und Sonntag in der Wochenansicht einblenden.</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_tasks" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">Aufgaben mit Fälligkeitsdatum anzeigen</span>
                    <span class="block text-[11px] text-slate-500">Aufgaben erscheinen als schreibgeschützte Einträge im Kalender.</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_declined" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">Abgesagte Termine anzeigen</span>
                    <span class="block text-[11px] text-slate-500">Termine, die du abgelehnt hast, weiterhin darstellen.</span>
                  </span>
                </label>
              </div>
            </div>

            <!-- Arbeitszeit -->
            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">Arbeitszeit</h3>
              <p class="text-[11px] text-slate-500 mb-3">
                Bestimmt den hervorgehobenen Bereich in der Wochen- und Tagesansicht.
              </p>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Arbeitsbeginn</label>
                  <input v-model="settings.calendar.workday_start" type="time" class="ts-input" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Arbeitsende</label>
                  <input v-model="settings.calendar.workday_end" type="time" class="ts-input" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Raster-Auflösung</label>
                  <select v-model.number="settings.calendar.slot_minutes" class="ts-input">
                    <option :value="15">15 Minuten</option>
                    <option :value="30">30 Minuten</option>
                    <option :value="60">60 Minuten</option>
                  </select>
                </div>
              </div>
              <p v-if="workdayInvalid" class="mt-2 text-[11px] font-medium text-rose-600">
                Das Arbeitsende muss nach dem Arbeitsbeginn liegen.
              </p>
            </div>

            <!-- Neue Termine -->
            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">Voreinstellungen für neue Termine</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Dauer</label>
                  <select v-model.number="settings.calendar.default_duration_minutes" class="ts-input">
                    <option :value="15">15 Minuten</option>
                    <option :value="30">30 Minuten</option>
                    <option :value="45">45 Minuten</option>
                    <option :value="60">1 Stunde</option>
                    <option :value="90">1,5 Stunden</option>
                    <option :value="120">2 Stunden</option>
                    <option :value="480">Ganzer Arbeitstag</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Erinnerung</label>
                  <select v-model="reminderModel" class="ts-input">
                    <option value="none">Keine</option>
                    <option value="5">5 Minuten vorher</option>
                    <option value="15">15 Minuten vorher</option>
                    <option value="30">30 Minuten vorher</option>
                    <option value="60">1 Stunde vorher</option>
                    <option value="1440">1 Tag vorher</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Kategorie</label>
                  <select v-model="settings.calendar.default_category_id" class="ts-input">
                    <option :value="null">Keine</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Sichtbarkeit</label>
                  <select v-model="settings.calendar.default_visibility" class="ts-input">
                    <option value="private">Privat (nur ich & Eingeladene)</option>
                    <option v-if="user?.company_id" value="company">Für Firma sichtbar</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- BENACHRICHTIGUNGEN ---------- -->
        <section v-show="active === 'notifications'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Bell class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">Benachrichtigungen</h2>
          </header>
          <div class="p-5 space-y-6">

            <!-- Kanäle -->
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">Kanäle</h3>
              <div class="space-y-1">
                <label class="ts-toggle">
                  <input v-model="settings.notifications.in_app" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">In der App</span>
                    <span class="block text-[11px] text-slate-500">Glocken-Symbol in der Navigation. Empfohlen.</span>
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.browser" type="checkbox" class="ts-check" @change="onBrowserToggle" />
                  <span class="flex-1">
                    <span class="block text-sm font-medium text-slate-800">Browser-Benachrichtigungen</span>
                    <span class="block text-[11px] text-slate-500">
                      Hinweise direkt im Betriebssystem – auch wenn Taskster im Hintergrund läuft.
                      <span v-if="browserPermission === 'denied'" class="text-rose-600 font-medium">
                        Der Browser hat die Erlaubnis blockiert; bitte in den Website-Einstellungen freigeben.
                      </span>
                      <span v-else-if="browserPermission === 'granted'" class="text-emerald-600 font-medium">
                        Erlaubnis erteilt.
                      </span>
                    </span>
                  </span>
                  <span
                    v-if="browserPermission === 'granted'"
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                  >
                    Aktiv
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.email" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">E-Mail</span>
                    <span class="block text-[11px] text-slate-500">
                      Termineinladungen und Absagen zusätzlich per Mail (inkl. Kalenderdatei).
                    </span>
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.sound" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">Ton</span>
                    <span class="block text-[11px] text-slate-500">Kurzer Signalton bei neuen Benachrichtigungen.</span>
                  </span>
                </label>
              </div>

              <div class="mt-4 max-w-xs">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Zusammenfassung</label>
                <select v-model="settings.notifications.digest" class="ts-input">
                  <option value="off">Keine Zusammenfassung</option>
                  <option value="daily">Täglich (morgens)</option>
                  <option value="weekly">Wöchentlich (Montag)</option>
                </select>
              </div>
            </div>

            <!-- Ereignisse -->
            <div class="pt-5 border-t border-slate-100">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide">Worüber informieren</h3>
                <div class="flex gap-2">
                  <button type="button" class="text-[11px] font-semibold text-[#0891B2] hover:underline" @click="setAllEvents(true)">Alle an</button>
                  <span class="text-slate-300">·</span>
                  <button type="button" class="text-[11px] font-semibold text-slate-500 hover:underline" @click="setAllEvents(false)">Alle aus</button>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                <label v-for="e in eventOptions" :key="e.key" class="ts-toggle">
                  <input v-model="settings.notifications.events[e.key]" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ e.label }}</span>
                    <span class="block text-[11px] text-slate-500">{{ e.hint }}</span>
                  </span>
                </label>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- ABRECHNUNG ---------- -->
        <section v-show="active === 'billing'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Clock class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">Zeiterfassung & Abrechnung</h2>
          </header>
          <div class="p-5 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Stundenlohn</label>
                <div class="relative">
                  <input v-model="hourlyRate" type="number" step="0.01" min="0" placeholder="120.00" class="ts-input pr-14 font-semibold" />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-semibold pointer-events-none">/ Std.</span>
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard-Währung</label>
                <select v-model="userCurrency" class="ts-input">
                  <option value="CHF">CHF (Schweizer Franken)</option>
                  <option value="EUR">EUR (Euro)</option>
                  <option value="USD">USD (US Dollar)</option>
                  <option value="GBP">GBP (Britisches Pfund)</option>
                </select>
              </div>
            </div>
            <p class="text-[11px] text-slate-500">
              Dieser Stundensatz wird bei der Erfassung von Projekt- und Aufgabenzeiten standardmässig herangezogen.
            </p>
          </div>
        </section>

        <!-- ---------- SICHERHEIT ---------- -->
        <section v-show="active === 'security'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Lock class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">Passwort & Sicherheit</h2>
          </header>
          <div class="p-5">
            <form class="space-y-4 max-w-lg" @submit.prevent="changePassword">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Aktuelles Passwort</label>
                <input v-model="currentPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Neues Passwort (min. 8 Zeichen)</label>
                <input v-model="newPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Neues Passwort bestätigen</label>
                <input v-model="confirmPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div class="pt-1">
                <button type="submit" class="taskster_button" :disabled="savingPassword">
                  <ShieldCheck class="w-4 h-4" />
                  <span>{{ savingPassword ? 'Wird geändert…' : 'Passwort aktualisieren' }}</span>
                </button>
              </div>
            </form>
          </div>
        </section>

        <!-- ---------- TARIF ---------- -->
        <section v-show="active === 'plan'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Zap class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">Tarifplan</h2>
          </header>
          <div class="p-5">
            <div class="p-4 rounded-md bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div class="text-[11px] text-slate-500 uppercase font-semibold mb-1">Dein aktiver Plan</div>
                <div class="text-xl font-bold tracking-tight" :class="planTextClass">{{ planLabel }}</div>
                <p class="text-xs text-slate-600 mt-1">
                  <template v-if="user?.company_name">
                    Du profitierst vom Plan deines Unternehmens ({{ user.company_name }}).
                  </template>
                  <template v-else-if="user?.is_pro">Du geniesst alle PRO-Vorteile.</template>
                  <template v-else>Für Änderungen wende dich an den Support oder deinen Administrator.</template>
                </p>
              </div>
              <button
                v-if="!user?.is_pro && !user?.company_name"
                type="button"
                class="taskster_button shrink-0"
                :disabled="upgradeSent"
                @click="requestUpgrade"
              >
                <Zap class="w-4 h-4" />
                <span>{{ upgradeSent ? 'Anfrage gesendet' : 'Upgrade anfragen' }}</span>
              </button>
            </div>
          </div>
        </section>

        <!-- ---------- UNTERNEHMEN ---------- -->
        <section v-if="isCompanyAdmin" v-show="active === 'company'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center justify-between border-b border-slate-200">
            <div class="flex items-center gap-2">
              <Building2 class="w-4 h-4 text-[#0891B2]" />
              <h2 class="text-base font-semibold text-slate-900">Unternehmen</h2>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-800 border border-cyan-200">
              Company Admin
            </span>
          </header>
          <div class="p-5 space-y-5">
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">Mitarbeiter einladen</h3>
              <form class="flex flex-col sm:flex-row gap-2" @submit.prevent="inviteCompanyMember">
                <input v-model="companyInviteEmail" type="email" required placeholder="mitarbeiter@firma.ch" class="ts-input flex-1" />
                <select v-model="companyInviteRole" class="ts-input sm:w-48">
                  <option value="member">Mitarbeiter</option>
                  <option value="admin">Company Admin</option>
                </select>
                <button type="submit" class="taskster_button shrink-0" :disabled="sendingCompanyInvite || !companyInviteEmail.trim()">
                  <Plus class="w-4 h-4" />
                  <span>{{ sendingCompanyInvite ? 'Sendet…' : 'Einladen' }}</span>
                </button>
              </form>
              <div v-if="companyInviteLink" class="mt-3 p-3 rounded-md bg-cyan-50 border border-cyan-200">
                <div class="text-xs font-semibold text-cyan-900 mb-1">Einladungslink</div>
                <input readonly :value="companyInviteLink" class="w-full h-8 px-2.5 rounded bg-white border border-cyan-200 text-xs font-mono" @click="($event.target as HTMLInputElement).select()" />
              </div>
            </div>

            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">
                Aktuelle Mitarbeiter ({{ companyMembers.length }})
              </h3>
              <div v-if="loadingCompanyMembers" class="text-xs text-slate-500 py-3">Lade Mitglieder…</div>
              <div v-else-if="companyMembers.length === 0" class="text-xs text-slate-500 py-3 italic">Noch keine weiteren Mitarbeiter vorhanden.</div>
              <div v-else class="divide-y divide-slate-100 border border-slate-200 rounded-md overflow-hidden">
                <div v-for="m in companyMembers" :key="m.id" class="flex items-center justify-between px-3 py-2.5 hover:bg-slate-50">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-semibold text-xs shrink-0">
                      {{ (m.name || m.email || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-slate-900 truncate">
                        {{ m.name }}
                        <span v-if="m.id === user?.id" class="text-slate-400 font-normal">(Du)</span>
                      </div>
                      <div class="text-[11px] text-slate-500 truncate">{{ m.email }}</div>
                    </div>
                  </div>
                  <span
                    class="text-[10px] font-semibold px-2 py-0.5 rounded border shrink-0"
                    :class="m.company_role === 'admin'
                      ? 'bg-cyan-50 text-cyan-800 border-cyan-200'
                      : 'bg-slate-100 text-slate-600 border-slate-200'"
                  >
                    {{ m.company_role === 'admin' ? 'Company Admin' : 'Mitarbeiter' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  LayoutDashboard, Settings, User, Clock, Lock, Zap, Building2,
  ShieldCheck, CalendarDays, Bell, Save, CircleDot, CheckCircle2, AlertCircle, Plus
} from 'lucide-vue-next'

const { user, authHeaders, initAuth } = useAuth()

// ---------------------------------------------------------------------------
// Kategorien-Navigation
// ---------------------------------------------------------------------------
const isCompanyAdmin = computed(() => user.value?.company_role === 'admin' && Boolean(user.value?.company_name))

const sections = computed(() => {
  const list: any[] = [
    { key: 'profile', label: 'Profil', icon: User },
    { key: 'calendar', label: 'Kalender', icon: CalendarDays },
    { key: 'notifications', label: 'Benachrichtigungen', icon: Bell },
    { key: 'billing', label: 'Abrechnung', icon: Clock },
    { key: 'security', label: 'Sicherheit', icon: Lock },
    { key: 'plan', label: 'Tarifplan', icon: Zap }
  ]
  if (isCompanyAdmin.value) list.push({ key: 'company', label: 'Unternehmen', icon: Building2 })
  return list
})

const active = ref('profile')

// ---------------------------------------------------------------------------
// Formular-Zustand
// ---------------------------------------------------------------------------
const profileName = ref('')
const hourlyRate = ref<number | string>(120)
const userCurrency = ref('CHF')
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const saving = ref(false)
const savingPassword = ref(false)
const successMsg = ref('')
const errorMsg = ref('')
const upgradeSent = ref(false)

const categories = ref<any[]>([])

/** Lokale Kopie der Einstellungen – wird erst beim Speichern übertragen. */
const settings = ref<any>({
  language: 'de',
  whisper_language: 'de',
  theme: 'light',
  density: 'comfortable',
  start_page: 'dashboard',
  calendar: {
    default_view: 'month',
    week_start: 1,
    show_week_numbers: false,
    show_weekends: true,
    workday_start: '07:00',
    workday_end: '17:00',
    slot_minutes: 30,
    default_duration_minutes: 60,
    default_reminder_minutes: 15,
    default_category_id: null,
    default_visibility: 'private',
    show_tasks: true,
    show_declined: false,
    time_format: '24h'
  },
  notifications: {
    browser: false,
    email: true,
    in_app: true,
    sound: false,
    digest: 'off',
    events: {
      calendar_invite: true,
      calendar_change: true,
      calendar_cancel: true,
      calendar_reminder: true,
      task_assigned: true,
      task_due: true,
      task_comment: true,
      mention: true,
      budget_warning: true
    }
  }
})

/** Snapshot beim Laden – dient der Erkennung ungespeicherter Änderungen. */
const baseline = ref('')

function snapshot() {
  return JSON.stringify({
    name: profileName.value,
    hourly_rate: Number(hourlyRate.value) || 0,
    currency: userCurrency.value,
    settings: settings.value
  })
}

const dirty = computed(() => baseline.value !== '' && snapshot() !== baseline.value)

const workdayInvalid = computed(() =>
  String(settings.value.calendar.workday_end) <= String(settings.value.calendar.workday_start)
)

/** Erinnerung: null bedeutet "Keine" – das Select braucht dafür einen String. */
const reminderModel = computed({
  get: () => settings.value.calendar.default_reminder_minutes === null
    ? 'none'
    : String(settings.value.calendar.default_reminder_minutes),
  set: (v: string) => {
    settings.value.calendar.default_reminder_minutes = v === 'none' ? null : Number(v)
  }
})

const eventOptions = [
  { key: 'calendar_invite', label: 'Termineinladungen', hint: 'Jemand lädt dich zu einem Termin ein.' },
  { key: 'calendar_change', label: 'Terminänderungen', hint: 'Zeit oder Ort eines Termins ändert sich.' },
  { key: 'calendar_cancel', label: 'Terminabsagen', hint: 'Ein Termin wird abgesagt oder gelöscht.' },
  { key: 'calendar_reminder', label: 'Terminerinnerungen', hint: 'Vor Beginn eines Termins.' },
  { key: 'task_assigned', label: 'Aufgabenzuweisung', hint: 'Dir wird eine Aufgabe zugewiesen.' },
  { key: 'task_due', label: 'Fällige Aufgaben', hint: 'Aufgaben, die bald fällig sind.' },
  { key: 'task_comment', label: 'Kommentare', hint: 'Neue Kommentare zu deinen Aufgaben.' },
  { key: 'mention', label: 'Erwähnungen', hint: 'Jemand erwähnt dich mit @Name.' },
  { key: 'budget_warning', label: 'Budgetwarnungen', hint: 'Ein Projektbudget ist erreicht.' }
]

function setAllEvents(value: boolean) {
  for (const e of eventOptions) settings.value.notifications.events[e.key] = value
}

// ---------------------------------------------------------------------------
// Browser-Benachrichtigungen
// ---------------------------------------------------------------------------
const browserPermission = ref<'unsupported' | 'default' | 'granted' | 'denied'>('default')

function readBrowserPermission() {
  if (import.meta.client && typeof Notification !== 'undefined') {
    browserPermission.value = Notification.permission as any
  } else {
    browserPermission.value = 'unsupported'
  }
}

async function onBrowserToggle() {
  if (!import.meta.client || typeof Notification === 'undefined') {
    settings.value.notifications.browser = false
    errorMsg.value = 'Dieser Browser unterstützt keine Benachrichtigungen.'
    return
  }
  if (settings.value.notifications.browser && Notification.permission !== 'granted') {
    const result = await Notification.requestPermission()
    browserPermission.value = result as any
    if (result !== 'granted') {
      settings.value.notifications.browser = false
      errorMsg.value = 'Ohne Erlaubnis des Browsers können keine Benachrichtigungen angezeigt werden.'
    }
  }
}

// ---------------------------------------------------------------------------
// Laden
// ---------------------------------------------------------------------------
function applyUser() {
  const u = user.value
  if (!u) return
  profileName.value = u.name || ''
  hourlyRate.value = u.hourly_rate !== undefined && u.hourly_rate !== null ? u.hourly_rate : 120
  userCurrency.value = u.currency || 'CHF'

  if (u.settings && typeof u.settings === 'object') {
    // Tief zusammenführen, damit neue Felder aus den Defaults erhalten bleiben
    settings.value = {
      ...settings.value,
      ...u.settings,
      calendar: { ...settings.value.calendar, ...(u.settings.calendar || {}) },
      notifications: {
        ...settings.value.notifications,
        ...(u.settings.notifications || {}),
        events: { ...settings.value.notifications.events, ...(u.settings.notifications?.events || {}) }
      }
    }
  }

  baseline.value = snapshot()
}

async function loadCategories() {
  try {
    const res = await $fetch<any>('/api/event-categories', { headers: authHeaders() })
    categories.value = res.categories || []
  } catch {
    categories.value = []
  }
}

onMounted(async () => {
  if (!user.value) await initAuth()
  if (!user.value) {
    navigateTo('/login')
    return
  }
  applyUser()
  readBrowserPermission()
  loadCategories()
})

// ---------------------------------------------------------------------------
// Speichern
// ---------------------------------------------------------------------------
async function saveAll() {
  successMsg.value = ''
  errorMsg.value = ''

  if (!profileName.value.trim()) {
    errorMsg.value = 'Bitte gib einen Namen an.'
    return
  }
  if (workdayInvalid.value) {
    errorMsg.value = 'Das Arbeitsende muss nach dem Arbeitsbeginn liegen.'
    active.value = 'calendar'
    return
  }

  saving.value = true
  try {
    const res = await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: profileName.value.trim(),
        hourly_rate: Number(hourlyRate.value) || 0,
        currency: userCurrency.value,
        settings: settings.value
      }
    })

    if (user.value && res.user) {
      user.value.name = res.user.name
      user.value.hourly_rate = res.user.hourly_rate
      user.value.currency = res.user.currency
      if (res.user.settings) user.value.settings = res.user.settings
    }

    // Normalisierte Serverantwort als neue Basis übernehmen
    if (res.user?.settings) settings.value = res.user.settings
    baseline.value = snapshot()

    successMsg.value = 'Einstellungen gespeichert.'
    setTimeout(() => { successMsg.value = '' }, 4000)
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Einstellungen konnten nicht gespeichert werden.'
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  successMsg.value = ''
  errorMsg.value = ''

  if (newPassword.value !== confirmPassword.value) {
    errorMsg.value = 'Die neuen Passwörter stimmen nicht überein.'
    return
  }

  savingPassword.value = true
  try {
    await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: {
        name: profileName.value,
        current_password: currentPassword.value,
        new_password: newPassword.value
      }
    })
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    successMsg.value = 'Passwort erfolgreich geändert.'
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Passwort konnte nicht geändert werden.'
  } finally {
    savingPassword.value = false
  }
}

async function requestUpgrade() {
  try {
    await $fetch<any>('/api/auth/profile', {
      method: 'PATCH',
      headers: authHeaders(),
      body: { upgrade_request: true }
    })
  } catch {
    // Feld existiert serverseitig evtl. noch nicht – Anfrage gilt trotzdem als gestellt
  }
  upgradeSent.value = true
  successMsg.value = 'Upgrade-Anfrage gesendet. Wir melden uns in Kürze.'
}

// ---------------------------------------------------------------------------
// Unternehmen (Company Admin)
// ---------------------------------------------------------------------------
const companyInviteEmail = ref('')
const companyInviteRole = ref('member')
const companyInviteLink = ref('')
const sendingCompanyInvite = ref(false)
const companyMembers = ref<any[]>([])
const loadingCompanyMembers = ref(false)

async function loadCompanyMembers() {
  if (!isCompanyAdmin.value) return
  loadingCompanyMembers.value = true
  try {
    const res = await $fetch<{ members: any[] }>('/api/companies/members', { headers: authHeaders() })
    companyMembers.value = res.members || []
  } catch {
    companyMembers.value = []
  } finally {
    loadingCompanyMembers.value = false
  }
}

async function inviteCompanyMember() {
  if (!companyInviteEmail.value.trim()) return
  sendingCompanyInvite.value = true
  errorMsg.value = ''
  try {
    const res = await $fetch<any>('/api/companies/invitations', {
      method: 'POST',
      headers: authHeaders(),
      body: { email: companyInviteEmail.value.trim(), role: companyInviteRole.value }
    })
    companyInviteLink.value = res.invite_link || res.link || ''
    companyInviteEmail.value = ''
    successMsg.value = 'Einladung erstellt.'
    await loadCompanyMembers()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || 'Einladung konnte nicht erstellt werden.'
  } finally {
    sendingCompanyInvite.value = false
  }
}

watch(isCompanyAdmin, (v) => { if (v) loadCompanyMembers() }, { immediate: true })

// ---------------------------------------------------------------------------
// Badges
// ---------------------------------------------------------------------------
const planLabel = computed(() =>
  (user.value?.company_plan || (user.value?.is_pro ? 'pro' : 'free')).toUpperCase()
)
const planBadgeClass = computed(() => {
  if (user.value?.company_name) return 'bg-violet-50 text-violet-700 border-violet-200'
  if (user.value?.is_pro) return 'bg-cyan-50 text-cyan-800 border-cyan-200'
  return 'bg-slate-100 text-slate-600 border-slate-200'
})
const planTextClass = computed(() => {
  if (user.value?.company_name) return 'text-violet-800'
  if (user.value?.is_pro) return 'text-cyan-700'
  return 'text-slate-700'
})
</script>

<style scoped>
.ts-input {
  width: 100%;
  height: 36px;
  padding: 0 12px;
  font-size: 14px;
  border-radius: 6px;
  background: #fff;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.ts-input:focus {
  border-color: #0891B2;
  box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.15);
}
.ts-input:disabled {
  cursor: not-allowed;
}

.ts-toggle {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.15s;
}
.ts-toggle:hover {
  background: #f8fafc;
}

.ts-check {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  flex-shrink: 0;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
  accent-color: #0891B2;
  cursor: pointer;
}
</style>
