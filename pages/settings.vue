<template>
  <div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-1.5 text-xs text-slate-500 mb-5">
      <NuxtLink to="/dashboard" class="hover:text-cyan-800 transition-colors flex items-center gap-1">
        <LayoutDashboard class="w-3.5 h-3.5" />
        <span>{{ $t('common.dashboard') }}</span>
      </NuxtLink>
      <span>/</span>
      <span class="text-slate-800 font-medium flex items-center gap-1">
        <SettingsIcon class="w-3.5 h-3.5 text-[#0891B2]" />
        <span>{{ $t('common.einstellungen') }}</span>
      </span>
    </div>

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $t('common.einstellungen') }}</h1>
        <p class="text-sm text-slate-600 mt-1">
          {{ $t('settings.profil_kalender_benachrichtigungen_') }}
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <span
          v-if="dirty"
          class="inline-flex items-center gap-1.5 h-9 px-3 rounded-md bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-800"
        >
          <CircleDot class="w-3.5 h-3.5" />
          {{ $t('common.ungespeicherte_anderungen') }}
        </span>
        <button
          type="button"
          class="taskster_button cursor-pointer"
          :disabled="saving || !dirty"
          @click="saveAll"
        >
          <Save class="w-4 h-4" />
          <span>{{ saving ? $t('settings.speichern_laeuft') : $t('common.alle_anderungen_speichern') }}</span>
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
              :title="$t('settings.browserbenachrichtigungen_aktiv')"
            />          </button>
        </div>

        <!-- Konto-Info -->
        <div class="mt-3 bg-white border border-slate-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#0891B2] text-white flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden">
              <img v-if="user?.avatar" :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
              <span v-else>{{ (user?.name || '?').charAt(0).toUpperCase() }}</span>
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
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.profil') }}</h2>
          </header>
          <div class="p-5 space-y-5">
            <!-- Profilbild (Avatar) -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
              <div class="relative group">
                <div class="w-20 h-20 rounded-2xl bg-[#0891B2] text-white flex items-center justify-center font-black text-2xl shrink-0 overflow-hidden shadow-md border-2 border-white">
                  <img v-if="profileAvatar" :src="profileAvatar" :alt="profileName" class="w-full h-full object-cover" />
                  <span v-else>{{ (profileName || user?.name || '?').charAt(0).toUpperCase() }}</span>
                </div>
                <button
                  type="button"
                  @click="triggerAvatarUpload"
                  class="absolute inset-0 bg-slate-900/50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white text-[10px] font-bold cursor-pointer"
                  :title="$t('settings.bild_aendern')"
                >
                  <Camera class="w-5 h-5 mb-0.5" />
                  <span>{{ $t('settings.aendern') }}</span>
                </button>
              </div>

              <div class="space-y-1.5 flex-1">
                <h4 class="text-xs font-bold text-slate-900">{{ $t('settings.dein_profilbild') }}</h4>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                  {{ $t('settings.profilbild_hinweis') }}
                </p>
                <div class="flex items-center gap-2 pt-1">
                  <input
                    ref="avatarInputRef"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    class="hidden"
                    @change="onAvatarFileSelected"
                  />
                  <button
                    type="button"
                    class="taskster_button_light px-4 text-xs h-[34px] rounded-lg cursor-pointer"
                    @click="triggerAvatarUpload"
                  >
                    <Upload class="w-3.5 h-3.5 mr-1" />
                    <span>{{ $t('settings.bild_hochladen') }}</span>
                  </button>
                  <button
                    v-if="profileAvatar"
                    type="button"
                    class="taskster_button_accent px-4 text-xs h-[34px] rounded-lg cursor-pointer"
                    @click="removeAvatar"
                  >
                    <Trash2 class="w-3.5 h-3.5 mr-1" />
                    <span>{{ $t('settings.entfernen') }}</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('login.vollständiger_name') }}</label>
                <input v-model="profileName" type="text" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.email_adresse') }}</label>
                <input :value="user?.email" type="email" disabled class="ts-input bg-slate-50 text-slate-500 cursor-not-allowed" />
                <p class="text-[11px] text-slate-500 mt-1.5">{{ $t('settings.dient_als_loginkennung_und_kann_nic') }}</p>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.darstellung_sprache') }}</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('common.app_sprache') }}</label>
                  <select v-model="settings.language" @change="onLanguageChange" class="ts-input cursor-pointer">
                    <option value="de">Deutsch (DE)</option>
                    <option value="en">English (EN)</option>
                    <option value="sk">Slovenčina (SK)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    {{ $t('settings.whisper_sprache') }} <span class="text-[#0891B2] font-mono text-[10px]">AI</span>
                  </label>
                  <select v-model="settings.whisper_language" class="ts-input">
                    <option value="de">Deutsch (de)</option>
                    <option value="de-CH">Schweizerdeutsch (de-CH)</option>
                    <option value="en">English (en)</option>
                    <option value="fr">Français (fr)</option>
                    <option value="it">Italiano (it)</option>
                    <option value="auto">{{ $t('settings.automatisch_erkennen') }}</option>
                  </select>
                  <p class="text-[10px] text-slate-500 mt-1">{{ $t('settings.spracheingabe_für_openrouter_whispe') }}</p>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.design') }}</label>
                  <select v-model="settings.theme" class="ts-input">
                    <option value="light">{{ $t('settings.hell') }}</option>
                    <option value="dark">{{ $t('settings.dunkel_in_vorbereitung') }}</option>
                    <option value="system">{{ $t('settings.systemeinstellung') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.listen_dichte') }}</label>
                  <select v-model="settings.density" class="ts-input">
                    <option value="comfortable">{{ $t('settings.komfortabel') }}</option>
                    <option value="compact">{{ $t('settings.kompakt') }}</option>
                  </select>
                </div>
              </div>
              <div class="mt-4 max-w-xs">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.startseite_nach_dem_login') }}</label>
                <select v-model="settings.start_page" class="ts-input">
                  <option value="dashboard">{{ $t('common.dashboard') }}</option>
                  <option value="calendar">{{ $t('common.kalender') }}</option>
                  <option value="time">{{ $t('common.zeitrapporte') }}</option>
                  <option value="contacts">{{ $t('common.kontakte') }}</option>
                </select>
              </div>
            </div>
          </div>
        </section>

        <!-- ---------- KALENDER ---------- -->
        <section v-show="active === 'calendar'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <CalendarDays class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.kalender') }}</h2>
          </header>
          <div class="p-5 space-y-6">

            <!-- Ansicht & Woche -->
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.ansicht_woche') }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_ansicht') }}</label>
                  <select v-model="settings.calendar.default_view" class="ts-input">
                    <option value="month">{{ $t('settings.monat') }}</option>
                    <option value="week">{{ $t('settings.woche') }}</option>
                    <option value="day">{{ $t('settings.tag') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.erster_wochentag') }}</label>
                  <select v-model.number="settings.calendar.week_start" class="ts-input">
                    <option :value="1">{{ $t('settings.montag') }}</option>
                    <option :value="0">{{ $t('settings.sonntag') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.zeitformat') }}</label>
                  <select v-model="settings.calendar.time_format" class="ts-input">
                    <option value="24h">{{ $t('settings.24_stunden_1700') }}</option>
                    <option value="12h">{{ $t('settings.12_stunden_500_pm') }}</option>
                  </select>
                </div>
              </div>

              <div class="mt-4 space-y-1">
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_week_numbers" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.kalenderwochen_anzeigen') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.zeigt_die_kwnummer_links_neben_jede') }}</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_weekends" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.wochenenden_anzeigen') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.samstag_und_sonntag_in_der_wochenan') }}</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_tasks" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.aufgaben_mit_fälligkeitsdatum_anzei') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.aufgaben_erscheinen_als_schreibgesc') }}</span>
                  </span>
                </label>
                <label class="ts-toggle">
                  <input v-model="settings.calendar.show_declined" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.abgesagte_termine_anzeigen') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.termine_die_du_abgelehnt_hast_weite') }}</span>
                  </span>
                </label>
              </div>
            </div>

            <!-- Arbeitszeit -->
            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">{{ $t('settings.arbeitszeit') }}</h3>
              <p class="text-[11px] text-slate-500 mb-3">
                {{ $t('settings.bestimmt_den_hervorgehobenen_bereic') }}
              </p>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.arbeitsbeginn') }}</label>
                  <input v-model="settings.calendar.workday_start" type="time" class="ts-input" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.arbeitsende') }}</label>
                  <input v-model="settings.calendar.workday_end" type="time" class="ts-input" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.rasterauflösung') }}</label>
                  <select v-model.number="settings.calendar.slot_minutes" class="ts-input">
                    <option :value="15">{{ $t('settings.15_minuten') }}</option>
                    <option :value="30">{{ $t('settings.30_minuten') }}</option>
                    <option :value="60">{{ $t('settings.60_minuten') }}</option>
                  </select>
                </div>
              </div>
              <p v-if="workdayInvalid" class="mt-2 text-[11px] font-medium text-rose-600">
                {{ $t('settings.das_arbeitsende_muss_nach_dem_arbei') }}
              </p>
            </div>

            <!-- Neue Termine -->
            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.voreinstellungen_für_neue_termine') }}</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_dauer') }}</label>
                  <select v-model.number="settings.calendar.default_duration_minutes" class="ts-input">
                    <option :value="15">{{ $t('settings.15_minuten') }}</option>
                    <option :value="30">{{ $t('settings.30_minuten') }}</option>
                    <option :value="45">{{ $t('settings.45_minuten') }}</option>
                    <option :value="60">{{ $t('settings.1_stunde') }}</option>
                    <option :value="90">{{ $t('settings.15_stunden') }}</option>
                    <option :value="120">{{ $t('settings.2_stunden') }}</option>
                    <option :value="480">{{ $t('settings.ganzer_arbeitstag') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_erinnerung') }}</label>
                  <select v-model="reminderModel" class="ts-input">
                    <option value="none">{{ $t('settings.keine') }}</option>
                    <option value="5">{{ $t('settings.5_minuten_vorher') }}</option>
                    <option value="15">{{ $t('settings.15_minuten_vorher') }}</option>
                    <option value="30">{{ $t('settings.30_minuten_vorher') }}</option>
                    <option value="60">{{ $t('settings.1_stunde_vorher') }}</option>
                    <option value="1440">{{ $t('settings.1_tag_vorher') }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_kategorie') }}</label>
                  <select v-model="settings.calendar.default_category_id" class="ts-input">
                    <option :value="null">{{ $t('settings.keine') }}</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_sichtbarkeit') }}</label>
                  <select v-model="settings.calendar.default_visibility" class="ts-input">
                    <option value="private">{{ $t('settings.privat_nur_ich_eingeladene') }}</option>
                    <option v-if="user?.company_id" value="company">{{ $t('settings.für_firma_sichtbar') }}</option>
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
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.benachrichtigungen') }}</h2>
          </header>
          <div class="p-5 space-y-6">

            <!-- Kanäle -->
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.kanäle') }}</h3>
              <div class="space-y-1">
                <label class="ts-toggle">
                  <input v-model="settings.notifications.in_app" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.in_der_app') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.glockensymbol_in_der_navigation_emp') }}</span>
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.browser" type="checkbox" class="ts-check" @change="onBrowserToggle" />
                  <span class="flex-1">
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.browser_benachrichtigungen') }}</span>
                    <span class="block text-[11px] text-slate-500">
                      {{ $t('settings.hinweise_direkt_im_betriebssystem_a') }}
                      <span v-if="browserPermission === 'denied'" class="text-rose-600 font-medium">
                        {{ $t('settings.der_browser_hat_die_erlaubnis_block') }}
                      </span>
                      <span v-else-if="browserPermission === 'granted'" class="text-emerald-600 font-medium">
                        {{ $t('settings.erlaubnis_erteilt') }}
                      </span>
                    </span>
                  </span>
                  <span
                    v-if="browserPermission === 'granted'"
                    class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                  >
                    {{ $t('settings.aktiv') }}
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.email" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.email_benachrichtigungen') }}</span>
                    <span class="block text-[11px] text-slate-500">
                      {{ $t('settings.erhalte_emails_für_zugewiesene_aufg') }}
                    </span>
                  </span>
                </label>

                <label class="ts-toggle">
                  <input v-model="settings.notifications.sound" type="checkbox" class="ts-check" />
                  <span>
                    <span class="block text-sm font-medium text-slate-800">{{ $t('settings.ton') }}</span>
                    <span class="block text-[11px] text-slate-500">{{ $t('settings.kurzer_signalton_bei_neuen_benachri') }}</span>
                  </span>
                </label>
              </div>

              <div class="mt-4 max-w-xs">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.zusammenfassung') }}</label>
                <select v-model="settings.notifications.digest" class="ts-input">
                  <option value="off">{{ $t('settings.keine_zusammenfassung') }}</option>
                  <option value="daily">{{ $t('settings.täglich_morgens') }}</option>
                  <option value="weekly">{{ $t('settings.wöchentlich_montag') }}</option>
                </select>
              </div>
            </div>

            <!-- Ereignisse -->
            <div class="pt-5 border-t border-slate-100">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide">{{ $t('settings.worüber_informieren') }}</h3>
                <div class="flex gap-2">
                  <button type="button" class="text-[11px] font-semibold text-[#0891B2] hover:underline" @click="setAllEvents(true)">{{ $t('settings.alle_an') }}</button>
                  <span class="text-slate-300">·</span>
                  <button type="button" class="text-[11px] font-semibold text-slate-500 hover:underline" @click="setAllEvents(false)">{{ $t('settings.alle_aus') }}</button>
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
            <h2 class="text-base font-semibold text-slate-900">{{ $t('settings.zeiterfassung_abrechnung') }}</h2>
          </header>
          <div class="p-5 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standard_stundenlohn') }}</label>
                <div class="relative">
                  <input v-model="hourlyRate" type="number" step="0.01" min="0" placeholder="120.00" class="ts-input pr-14 font-semibold" />
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-semibold pointer-events-none">{{ $t('settings.std') }}</span>
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.standardwährung') }}</label>
                <select v-model="userCurrency" class="ts-input">
                  <option value="CHF">{{ $t('settings.chf_schweizer_franken') }}</option>
                  <option value="EUR">{{ $t('settings.eur_euro') }}</option>
                  <option value="USD">{{ $t('settings.usd_us_dollar') }}</option>
                  <option value="GBP">{{ $t('settings.gbp_britisches_pfund') }}</option>
                </select>
              </div>
            </div>
            <p class="text-[11px] text-slate-500">
              {{ $t('settings.dieser_stundensatz_wird_bei_der_erf') }}
            </p>
          </div>
        </section>

        <!-- ---------- SICHERHEIT ---------- -->
        <section v-show="active === 'security'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Lock class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('settings.passwort_sicherheit') }}</h2>
          </header>
          <div class="p-5">
            <form class="space-y-4 max-w-lg" @submit.prevent="changePassword">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.aktuelles_passwort') }}</label>
                <input v-model="currentPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.neues_passwort_min_8_zeichen') }}</label>
                <input v-model="newPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">{{ $t('settings.neues_passwort_bestätigen') }}</label>
                <input v-model="confirmPassword" type="password" required placeholder="••••••••" class="ts-input" />
              </div>
              <div class="pt-1">
                <button type="submit" class="taskster_button" :disabled="savingPassword">
                  <ShieldCheck class="w-4 h-4" />
                  <span>{{ savingPassword ? $t('settings.wird_geaendert') : $t('settings.passwort_aktualisieren') }}</span>
                </button>
              </div>
            </form>
          </div>
        </section>

        <!-- ---------- TARIF ---------- -->
        <section v-show="active === 'plan'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center gap-2 border-b border-slate-200">
            <Zap class="w-4 h-4 text-[#0891B2]" />
            <h2 class="text-base font-semibold text-slate-900">{{ $t('common.tarifplan') }}</h2>
          </header>
          <div class="p-5">
            <div class="p-4 rounded-md bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <div class="text-[11px] text-slate-500 uppercase font-semibold mb-1">{{ $t('settings.dein_aktiver_plan') }}</div>
                <div class="text-xl font-bold tracking-tight" :class="planTextClass">{{ planLabel }}</div>
                <p class="text-xs text-slate-600 mt-1">
                  <template v-if="user?.company_name">
                    {{ $t('settings.du_profitierst_vom_plan_deines_unte') }} ({{ user.company_name }})
                  </template>
                  <template v-else-if="user?.is_pro">{{ $t('settings.du_geniesst_alle_provorteile') }}</template>
                  <template v-else>{{ $t('settings.für_änderungen_wende_dich_an_den_su') }}</template>
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
                <span>{{ upgradeSent ? $t('settings.anfrage_gesendet') : $t('settings.upgrade_anfragen') }}</span>
              </button>
            </div>
          </div>
        </section>

        <!-- ---------- UNTERNEHMEN ---------- -->
        <section v-if="isCompanyAdmin" v-show="active === 'company'" class="bg-white border border-slate-200 rounded-lg">
          <header class="px-5 h-14 flex items-center justify-between border-b border-slate-200">
            <div class="flex items-center gap-2">
              <Building2 class="w-4 h-4 text-[#0891B2]" />
              <h2 class="text-base font-semibold text-slate-900">{{ $t('common.unternehmen') }}</h2>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-cyan-50 text-cyan-800 border border-cyan-200">
              Company Admin
            </span>
          </header>
          <div class="p-5 space-y-5">
            <div>
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">{{ $t('settings.mitarbeiter_einladen') }}</h3>
              <form class="flex flex-col sm:flex-row gap-2" @submit.prevent="inviteCompanyMember">
                <input v-model="companyInviteEmail" type="email" required placeholder="mitarbeiter@firma.ch" class="ts-input flex-1" />
                <select v-model="companyInviteRole" class="ts-input sm:w-48">
                  <option value="member">{{ $t('settings.mitarbeiter') }}</option>
                  <option value="admin">Company Admin</option>
                </select>
                <button type="submit" class="taskster_button shrink-0" :disabled="sendingCompanyInvite || !companyInviteEmail.trim()">
                  <Plus class="w-4 h-4" />
                  <span>{{ sendingCompanyInvite ? $t('settings.sendet') : $t('settings.einladen') }}</span>
                </button>
              </form>
              <div v-if="companyInviteLink" class="mt-3 p-3 rounded-md bg-cyan-50 border border-cyan-200">
                <div class="text-xs font-semibold text-cyan-900 mb-1">{{ $t('settings.einladungslink') }}</div>
                <input readonly :value="companyInviteLink" class="w-full h-8 px-2.5 rounded bg-white border border-cyan-200 text-xs font-mono" @click="($event.target as HTMLInputElement).select()" />
              </div>
            </div>

            <div class="pt-5 border-t border-slate-100">
              <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">
                {{ $t('settings.aktuelle_mitarbeiter') }} ({{ companyMembers.length }})
              </h3>
              <div v-if="loadingCompanyMembers" class="text-xs text-slate-500 py-3">{{ $t('settings.lade_mitglieder') }}</div>
              <div v-else-if="companyMembers.length === 0" class="text-xs text-slate-500 py-3 italic">{{ $t('settings.noch_keine_weiteren_mitarbeiter_vor') }}</div>
              <div v-else class="divide-y divide-slate-100 border border-slate-200 rounded-md overflow-hidden">
                <div v-for="m in companyMembers" :key="m.id" class="flex items-center justify-between px-3 py-2.5 hover:bg-slate-50">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-semibold text-xs shrink-0">
                      {{ (m.name || m.email || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-slate-900 truncate">
                        {{ m.name }}
                        <span v-if="m.id === user?.id" class="text-slate-400 font-normal">{{ $t('settings.du') }}</span>
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
                    {{ m.company_role === 'admin' ? 'Company Admin' : $t('settings.mitarbeiter') }}
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
  LayoutDashboard, Settings as SettingsIcon, User, Clock, Lock, Zap, Building2,
  ShieldCheck, CalendarDays, Bell, Save, CircleDot, CheckCircle2, AlertCircle, Plus,
  Camera, Upload, Trash2
} from 'lucide-vue-next'

const { user, token, setAuth, authHeaders, initAuth } = useAuth()
const { t, setLocale } = useI18n()

async function onLanguageChange() {
  if (settings.value.language && ['de', 'en', 'sk'].includes(settings.value.language)) {
    try {
      await setLocale(settings.value.language)
    } catch (err) {
      console.error('[i18n] Failed to switch locale:', err)
    }
  }
}

// ---------------------------------------------------------------------------
// Kategorien-Navigation
// ---------------------------------------------------------------------------
const isCompanyAdmin = computed(() => user.value?.company_role === 'admin' && Boolean(user.value?.company_name))

const sections = computed(() => {
  const list: any[] = [
    { key: 'profile', label: t('common.profil'), icon: User },
    { key: 'calendar', label: t('common.kalender'), icon: CalendarDays },
    { key: 'notifications', label: t('common.benachrichtigungen'), icon: Bell },
    { key: 'billing', label: t('common.abrechnung'), icon: Clock },
    { key: 'security', label: t('common.sicherheit'), icon: Lock },
    { key: 'plan', label: t('common.tarifplan'), icon: Zap }
  ]
  if (isCompanyAdmin.value) list.push({ key: 'company', label: t('common.unternehmen'), icon: Building2 })
  return list
})

const active = ref('profile')

// ---------------------------------------------------------------------------
// Formular-Zustand
// ---------------------------------------------------------------------------
const profileName = ref('')
const profileAvatar = ref<string | null>(null)
const avatarInputRef = ref<HTMLInputElement | null>(null)
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
    avatar: profileAvatar.value,
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

const eventOptions = computed(() => [
  { key: 'calendar_invite', label: t('settings.termineinladungen'), hint: t('settings.jemand_lädt_dich_zu_einem_termin_ei') },
  { key: 'calendar_change', label: t('settings.terminänderungen'), hint: t('settings.zeit_oder_ort_eines_termins_ändert_') },
  { key: 'calendar_cancel', label: t('settings.terminabsagen'), hint: t('settings.ein_termin_wird_abgesagt_oder_gelös') },
  { key: 'calendar_reminder', label: t('settings.terminerinnerungen'), hint: t('settings.vor_beginn_eines_termins') },
  { key: 'task_assigned', label: t('settings.aufgabenzuweisung'), hint: t('settings.dir_wird_eine_aufgabe_zugewiesen') },
  { key: 'task_due', label: t('settings.fällige_aufgaben'), hint: t('settings.aufgaben_die_bald_fällig_sind') },
  { key: 'task_comment', label: t('settings.kommentare'), hint: t('settings.neue_kommentare_zu_deinen_aufgaben') },
  { key: 'mention', label: t('settings.erwähnungen'), hint: t('settings.jemand_erwähnt_dich_mit_name') },
  { key: 'budget_warning', label: t('settings.budgetwarnungen'), hint: t('settings.ein_projektbudget_ist_erreicht') }
])

function setAllEvents(value: boolean) {
  for (const e of eventOptions.value) settings.value.notifications.events[e.key] = value
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
    errorMsg.value = t('settings.dieser_browser_unterstützt_keine_be')
    return
  }
  if (settings.value.notifications.browser && Notification.permission !== 'granted') {
    const result = await Notification.requestPermission()
    browserPermission.value = result as any
    if (result !== 'granted') {
      settings.value.notifications.browser = false
      errorMsg.value = t('settings.ohne_erlaubnis_des_browsers_können_')
    }
  }
}

// ---------------------------------------------------------------------------
// Profilbild (Avatar) Handling
// ---------------------------------------------------------------------------
function triggerAvatarUpload() {
  avatarInputRef.value?.click()
}

function removeAvatar() {
  profileAvatar.value = null
  if (avatarInputRef.value) {
    avatarInputRef.value.value = ''
  }
}

function onAvatarFileSelected(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    errorMsg.value = t('settings.bitte_gueltige_bilddatei')
    return
  }

  const reader = new FileReader()
  reader.onload = (event) => {
    const img = new Image()
    img.onload = () => {
      // Quadratisch auf 256x256 px skalieren & zuschneiden
      const size = 256
      const canvas = document.createElement('canvas')
      canvas.width = size
      canvas.height = size
      const ctx = canvas.getContext('2d')
      if (!ctx) return

      const minDim = Math.min(img.width, img.height)
      const sx = (img.width - minDim) / 2
      const sy = (img.height - minDim) / 2

      ctx.drawImage(img, sx, sy, minDim, minDim, 0, 0, size, size)

      let dataUrl = canvas.toDataURL('image/webp', 0.85)
      if (!dataUrl.startsWith('data:image/webp')) {
        dataUrl = canvas.toDataURL('image/jpeg', 0.85)
      }

      profileAvatar.value = dataUrl
      input.value = ''
    }
    img.src = event.target?.result as string
  }
  reader.readAsDataURL(file)
}

// ---------------------------------------------------------------------------
// Laden
// ---------------------------------------------------------------------------
function applyUser() {
  const u = user.value
  if (!u) return
  profileName.value = u.name || ''
  profileAvatar.value = u.avatar || null
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
    if (settings.value.language && ['de', 'en', 'sk'].includes(settings.value.language)) {
      try {
        setLocale(settings.value.language)
      } catch (err) {
        console.error('[i18n] Failed to set initial locale:', err)
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
    errorMsg.value = t('settings.bitte_gib_einen_namen_an')
    return
  }
  if (workdayInvalid.value) {
    errorMsg.value = t('settings.das_arbeitsende_muss_nach_dem_arbei')
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
        avatar: profileAvatar.value,
        hourly_rate: Number(hourlyRate.value) || 0,
        currency: userCurrency.value,
        settings: settings.value
      }
    })

    if (user.value && res.user) {
      user.value.name = res.user.name
      user.value.hourly_rate = res.user.hourly_rate
      user.value.currency = res.user.currency
      user.value.avatar = res.user.avatar || null
      if (res.user.settings) user.value.settings = res.user.settings
      setAuth(token.value || '', res.user)
    }

    // Normalisierte Serverantwort als neue Basis übernehmen
    if (res.user?.settings) settings.value = res.user.settings
    if (settings.value.language && ['de', 'en', 'sk'].includes(settings.value.language)) {
      try {
        await setLocale(settings.value.language)
      } catch (err) {
        console.error('[i18n] Failed to switch locale on save:', err)
      }
    }
    baseline.value = snapshot()

    successMsg.value = t('settings.einstellungen_gespeichert') || 'Einstellungen gespeichert.'
    setTimeout(() => { successMsg.value = '' }, 4000)
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || t('settings.einstellungen_konnten_nicht_gespeic') || 'Einstellungen konnten nicht gespeichert werden.'
  } finally {
    saving.value = false
  }
}

async function changePassword() {
  successMsg.value = ''
  errorMsg.value = ''

  if (newPassword.value !== confirmPassword.value) {
    errorMsg.value = t('settings.die_neuen_passwörter_stimmen_nicht_')
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
    successMsg.value = t('settings.passwort_erfolgreich_geändert')
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || t('settings.passwort_konnte_nicht_geändert_werd')
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
  successMsg.value = t('settings.upgradeanfrage_gesendet_wir_melden_')
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
    successMsg.value = t('settings.einladung_erstellt')
    await loadCompanyMembers()
  } catch (err: any) {
    errorMsg.value = err?.data?.statusMessage || t('settings.einladung_konnte_nicht_erstellt_wer')
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
